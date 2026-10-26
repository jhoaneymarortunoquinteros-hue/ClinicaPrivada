<?php
declare(strict_types=1);

/**
 * DIAGNÓSTICO TEMPORAL DE RED — se borra tras usarlo.
 * Busca qué proveedores de correo son alcanzables desde Render.
 * Uso: /diag.php?k=vs-diag-2026
 */

if (($_GET['k'] ?? '') !== 'vs-diag-2026') {
    http_response_code(404);
    exit('not found');
}

set_time_limit(180);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

function tcp(string $host, int $port, float $timeout = 5.0): array
{
    $ip4 = gethostbyname($host);
    if ($ip4 === $host) {
        return ['ok' => false, 'ms' => 0, 'err' => 'DNS fallo', 'banner' => ''];
    }

    $t0 = microtime(true);
    $errno = 0;
    $errstr = '';
    $sock = @stream_socket_client('tcp://' . $ip4 . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    $ms = (int) round((microtime(true) - $t0) * 1000);

    if ($sock === false) {
        return ['ok' => false, 'ms' => $ms, 'err' => "errno=$errno $errstr", 'banner' => ''];
    }

    $banner = @fgets($sock, 200);
    @fclose($sock);

    return [
        'ok'     => true,
        'ms'     => $ms,
        'err'    => '',
        'banner' => is_string($banner) ? trim($banner) : '',
    ];
}

function https_api(string $host, float $timeout = 6.0): array
{
    $t0 = microtime(true);
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => $timeout, 'ignore_errors' => true]]);
    $res = @file_get_contents('https://' . $host . '/', false, $ctx);
    $ms = (int) round((microtime(true) - $t0) * 1000);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0] . ' ', $m)) {
        $code = (int) $m[1];
    }
    return ['ok' => $res !== false || $code > 0, 'ms' => $ms, 'code' => $code];
}

echo "=== SMTP (puerto 587) ===\n";
$smtp587 = [
    'smtp-relay.brevo.com',
    'smtp.sendgrid.net',
    'smtp.mailgun.org',
    'email-smtp.us-east-1.amazonaws.com',
    'smtp.hostinger.com',
    'smtp.hostgator.com',
    'smtp.office365.com',
    'smtp.gmail.com',
];
foreach ($smtp587 as $h) {
    $r = tcp($h, 587);
    echo str_pad($h, 40) . ($r['ok'] ? 'OK   ' . $r['ms'] . 'ms  ' . $r['banner'] : 'FALLO ' . $r['ms'] . 'ms  ' . $r['err']) . "\n";
}

echo "\n=== SMTP (puerto 465 SSL) ===\n";
foreach (['smtp.resend.com', 'smtp.gmail.com', 'mail.smtp2go.com', 'smtp.digitalocean.com'] as $h) {
    $r = tcp($h, 465);
    echo str_pad($h, 40) . ($r['ok'] ? 'OK   ' . $r['ms'] . 'ms  ' . $r['banner'] : 'FALLO ' . $r['ms'] . 'ms  ' . $r['err']) . "\n";
}

echo "\n=== APIs HTTPS ===\n";
foreach (['api.resend.com', 'api.brevo.com', 'api.sendgrid.com', 'api.mailgun.net', 'api.postmarkapp.com'] as $h) {
    $r = https_api($h);
    echo str_pad($h, 40) . ($r['ok'] ? 'OK   ' . $r['ms'] . 'ms  HTTP ' . $r['code'] : 'FALLO ' . $r['ms'] . 'ms') . "\n";
}

echo "\n=== Entorno ===\n";
echo str_pad('PORT', 40) . (getenv('PORT') ?: '(no definido)') . "\n";
