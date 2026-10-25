<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Cliente SMTP mínimo, sin dependencias externas (no usa composer).
 *
 * Drivers soportados (clave mail.driver o variable MAIL_DRIVER):
 *   - 'smtp' : envío real por STARTTLS (587) o TLS implícito (465)
 *   - 'log'  : escribe el mensaje completo en backend/storage/mail.log
 *              (útil en local o mientras no hay credenciales SMTP)
 *
 * Si el envío SMTP falla se recurre al driver 'log' para que la petición
 * del usuario nunca se rompa; el error queda en el log de PHP.
 */
final class Mailer
{
    private const CRLF = "\r\n";

    /** Construye y envía (o registra) un mensaje en HTML y texto plano. */
    public static function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        /** @var array $cfg */
        $cfg = (array) config('mail', []);
        $driver = strtolower((string) ($cfg['driver'] ?? 'smtp'));
        $from = (string) ($cfg['from'] ?? 'no-reply@localhost');
        $fromName = (string) ($cfg['from_name'] ?? (string) config('app.name', 'No-Reply'));

        if ($text === '') {
            $text = trim(strip_tags($html));
        }

        $message = self::buildMessage($from, $fromName, $to, $subject, $html, $text);

        // Sin credenciales o driver 'log' -> solo se registra.
        if ($driver === 'log' || ($cfg['host'] ?? '') === '' || ($cfg['user'] ?? '') === '') {
            return self::log($to, $subject, $message);
        }

        try {
            return self::smtp($cfg, $from, $to, $message);
        } catch (\Throwable $e) {
            error_log('[mailer] SMTP falló: ' . $e->getMessage());
            // Degradación controlada: el enlace queda en el archivo de log.
            return self::log($to, $subject, $message);
        }
    }

    /** ¿El correo sale de verdad o solo se registra? */
    public static function isLogging(): bool
    {
        $cfg = (array) config('mail', []);
        return strtolower((string) ($cfg['driver'] ?? 'smtp')) === 'log'
            || ($cfg['host'] ?? '') === ''
            || ($cfg['user'] ?? '') === '';
    }

    /** Archivo donde el driver 'log' deja los mensajes. */
    public static function logFile(): string
    {
        return BASE_PATH . '/backend/storage/mail.log';
    }

    // ------------------------------------------------------------------
    //  SMTP
    // ------------------------------------------------------------------
    private static function smtp(array $cfg, string $from, string $to, string $message): bool
    {
        $host = (string) $cfg['host'];
        $port = (int) ($cfg['port'] ?? 587);
        $enc = strtolower((string) ($cfg['encryption'] ?? 'tls'));
        $timeout = (int) ($cfg['timeout'] ?? 25);

        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        if ($socket === false) {
            throw new \RuntimeException("No conecto con $remote ($errno) $errstr");
        }
        stream_set_timeout($socket, $timeout);

        try {
            self::expect($socket, [220]);

            self::write($socket, 'EHLO ' . (gethostname() ?: 'localhost'));
            self::expect($socket, [250]);

            // TLS implícito (465) ya viene con la conexión; STARTTLS (587) se negocia.
            if ($enc === 'tls') {
                self::write($socket, 'STARTTLS');
                self::expect($socket, [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo establecer el canal TLS con ' . $host);
                }
                self::write($socket, 'EHLO ' . (gethostname() ?: 'localhost'));
                self::expect($socket, [250]);
            }

            $user = (string) ($cfg['user'] ?? '');
            $pass = (string) ($cfg['pass'] ?? '');
            if ($user !== '') {
                self::write($socket, 'AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $pass));
                self::expect($socket, [235]);
            }

            self::write($socket, 'MAIL FROM:<' . $from . '>');
            self::expect($socket, [250]);
            self::write($socket, 'RCPT TO:<' . $to . '>');
            self::expect($socket, [250, 251]);
            self::write($socket, 'DATA');
            self::expect($socket, [354]);

            self::write($socket, self::dotStuff($message) . self::CRLF . '.');
            self::expect($socket, [250]);

            self::write($socket, 'QUIT');
            return true;
        } finally {
            @fclose($socket);
        }
    }

    // ------------------------------------------------------------------
    //  Bajo nivel
    // ------------------------------------------------------------------
    /** @param resource $socket */
    private static function write($socket, string $line): void
    {
        if (@fwrite($socket, $line . self::CRLF) === false) {
            throw new \RuntimeException('Fallo al escribir en el socket SMTP');
        }
    }

    /** @param resource $socket */
    private static function expect($socket, array $codes): void
    {
        $raw = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $raw .= $line;
            // "250-..." continúa; "250 ..." termina.
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
            if (strlen($raw) > 65536) {
                break;
            }
        }

        $code = (int) substr($raw, 0, 3);
        if ($code === 0 || !in_array($code, $codes, true)) {
            throw new \RuntimeException('Respuesta SMTP inesperada: ' . trim($raw));
        }
    }

    /** Evita que una línea que empieza con "." corte el cuerpo del mensaje. */
    private static function dotStuff(string $body): string
    {
        return preg_replace('/^\./m', '..', $body) ?? $body;
    }

    // ------------------------------------------------------------------
    //  Redacción MIME
    // ------------------------------------------------------------------
    private static function buildMessage(string $from, string $fromName, string $to, string $subject, string $html, string $text): string
    {
        $boundary = '=_vidasalud_' . bin2hex(random_bytes(12));
        $date = date('r');
        $messageId = '<' . bin2hex(random_bytes(16)) . '@' . (gethostname() ?: 'localhost') . '>';

        $headers = [
            'From'         => sprintf('%s <%s>', self::encodeWord($fromName), $from),
            'To'           => $to,
            'Subject'      => self::encodeWord($subject),
            'Date'         => $date,
            'Message-ID'   => $messageId,
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
        ];

        $head = '';
        foreach ($headers as $name => $value) {
            $head .= $name . ': ' . $value . self::CRLF;
        }

        $body =
            '--' . $boundary . self::CRLF .
            'Content-Type: text/plain; charset=UTF-8' . self::CRLF .
            'Content-Transfer-Encoding: base64' . self::CRLF . self::CRLF .
            chunk_split(base64_encode($text)) . self::CRLF .
            '--' . $boundary . self::CRLF .
            'Content-Type: text/html; charset=UTF-8' . self::CRLF .
            'Content-Transfer-Encoding: base64' . self::CRLF . self::CRLF .
            chunk_split(base64_encode($html)) . self::CRLF .
            '--' . $boundary . '--';

        return $head . self::CRLF . $body;
    }

    /** Cabecera con acentos/ñ en formato RFC 2047 (=?UTF-8?B?..?=). */
    private static function encodeWord(string $value): string
    {
        if (preg_match('/^[\x20-\x7E]*$/', $value)) {
            return $value;
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    // ------------------------------------------------------------------
    //  Driver log
    // ------------------------------------------------------------------
    private static function log(string $to, string $subject, string $message): bool
    {
        $file = self::logFile();
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            error_log('[mailer] no pude crear ' . $dir);
            return false;
        }

        $entry = sprintf(
            "========== %s ==========\nPara: %s\nAsunto: %s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $message
        );

        return @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
