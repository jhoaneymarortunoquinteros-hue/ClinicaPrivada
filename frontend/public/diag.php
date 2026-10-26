<?php
declare(strict_types=1);

/**
 * DIAGNÓSTICO TEMPORAL DE RED — se borra tras usarlo.
 * Comprueba si desde Render sale tráfico hacia el SMTP de Gmail y por qué puerto.
 * Uso: /diag.php?k=vs-diag-2026
 */

if (($_GET['k'] ?? '') !== 'vs-diag-2026') {
    http_response_code(404);
    exit('not found');
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

function try_connect(string $label, string $host, int $port, bool $ssl, bool $forceIpv4): void
{
    $target = $host;
    $note = '';

    if ($forceIpv4) {
        $ip4 = gethostbyname($host);
        if ($ip4 === $host) {
            echo str_pad($label, 34) . "DNS: no resolvió IPv4\n";
            return;
        }
        $target = $ip4;
        $note = " (IPv4 $ip4)";
    }

    $remote = ($ssl ? 'ssl://' : 'tcp://') . $target . ':' . $port;
    $ctx = stream_context_create([
        'ssl' => [
            'peer_name'        => $host,
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);

    $t0 = microtime(true);
    $errno = 0;
    $errstr = '';
    $sock = @stream_socket_client($remote, $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $ctx);
    $ms = (int) round((microtime(true) - $t0) * 1000);

    if ($sock === false) {
        echo str_pad($label, 34) . "FALLO ($ms ms) errno=$errno $errstr$note\n";
        return;
    }

    $banner = '';
    if (! $ssl) {
        $line = @fgets($sock, 256);
        $banner = is_string($line) ? trim($line) : '';
    }
    echo str_pad($label, 34) . "OK ($ms ms)$note" . ($banner !== '' ? " | $banner" : '') . "\n";
    @fclose($sock);
}

echo "=== Salida a internet desde Render ===\n";

// Control: HTTPS siempre debería funcionar
try_connect('HTTPS 1.1.1.1:443', '1.1.1.1', 443, true, false);
try_connect('HTTPS smtp.gmail.com:443', 'smtp.gmail.com', 443, true, true);

echo "\n=== DNS ===\n";
$ip = gethostbyname('smtp.gmail.com');
echo str_pad('gethostbyname IPv4', 34) . $ip . "\n";
$aaaa = @dns_get_record('smtp.gmail.com', DNS_AAAA);
if (is_array($aaaa) && $aaaa !== []) {
    foreach ($aaaa as $r) {
        echo str_pad('AAAA (IPv6)', 34) . ($r['ipv6'] ?? '?') . "\n";
    }
} else {
    echo str_pad('AAAA (IPv6)', 34) . "(sin registros)\n";
}

echo "\n=== SMTP Gmail ===\n";
try_connect('587 STARTTLS hostname', 'smtp.gmail.com', 587, false, false);
try_connect('587 STARTTLS IPv4', 'smtp.gmail.com', 587, false, true);
try_connect('465 SSL IPv4', 'smtp.gmail.com', 465, true, true);
try_connect('25 sin TLS IPv4', 'smtp.gmail.com', 25, false, true);

echo "\n=== Salida general ===\n";
try_connect('HTTP example.com:80 IPv4', 'example.com', 80, false, true);
try_connect('HTTPS api.resend.com:443', 'api.resend.com', 443, true, true);

echo "\n=== Entorno ===\n";
echo str_pad('PORT', 34) . (getenv('PORT') ?: '(no definido)') . "\n";
echo str_pad('MAIL_DRIVER', 34) . (getenv('MAIL_DRIVER') ?: '(no definido)') . "\n";
echo str_pad('IPv6 soportado por PHP', 34) . (defined('AF_INET6') ? 'si' : 'no') . "\n";
