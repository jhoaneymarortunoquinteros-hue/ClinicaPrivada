<?php
/**
 * Plantilla del correo de recuperación de contraseña.
 * Variables: $name (string), $link (string), $minutes (int), $app (string)
 * Estilos inline (los clientes de correo no ejecutan CSS externo).
 */

$name = trim((string) ($name ?? ''));
$link = (string) ($link ?? '');
$minutes = (int) ($minutes ?? 60);
$app = (string) ($app ?? 'Clínica VidaSalud');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer tu contraseña</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
<tr>
    <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="max-width:520px;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">

            <tr>
                <td style="background:#0f766e;padding:20px 24px;">
                    <span style="color:#ffffff;font-size:18px;font-weight:bold;"><?= e($app) ?></span>
                    <span style="color:#ccfbf1;font-size:13px;"> · Gestión de citas médicas</span>
                </td>
            </tr>

            <tr>
                <td style="padding:26px 24px;">
                    <h1 style="margin:0 0 14px;font-size:20px;color:#0f172a;">
                        Restablecer tu contraseña
                    </h1>

                    <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                        Hola <strong><?= e($name !== '' ? $name : 'usuario') ?></strong>, recibimos una
                        solicitud para crear una contraseña nueva en tu cuenta de <?= e($app) ?>.
                    </p>

                    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0;">
                        <tr>
                            <td style="background:#0f766e;border-radius:8px;">
                                <a href="<?= e($link) ?>"
                                   style="display:inline-block;padding:13px 24px;color:#ffffff;
                                          font-size:15px;font-weight:bold;text-decoration:none;border-radius:8px;">
                                    Crear contraseña nueva
                                </a>
                            </td>
                        </tr>
                    </table>

                    <p style="margin:0 0 6px;font-size:13px;color:#64748b;">
                        Si el botón no funciona, copia este enlace en tu navegador:
                    </p>
                    <p style="margin:0 0 18px;font-size:13px;word-break:break-all;">
                        <a href="<?= e($link) ?>" style="color:#0d9488;"><?= e($link) ?></a>
                    </p>

                    <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                        El enlace caduca en <strong><?= e((string) $minutes) ?> minutos</strong> y solo
                        puede usarse una vez. Si no fuiste tú quien lo solicitó, ignora este mensaje:
                        tu contraseña no cambiará.
                    </p>
                </td>
            </tr>

            <tr>
                <td style="background:#f8fafc;padding:16px 24px;border-top:1px solid #e2e8f0;">
                    <p style="margin:0;font-size:12px;color:#94a3b8;">
                        © <?= e(date('Y')) ?> <?= e($app) ?> · Clínica privada · Este es un correo automático,
                        no respondas a él.
                    </p>
                </td>
            </tr>

        </table>
    </td>
</tr>
</table>
</body>
</html>
