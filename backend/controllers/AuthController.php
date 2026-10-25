<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Mailer;
use App\Models\Paciente;
use App\Models\PasswordReset;
use App\Models\Usuario;

/**
 * Login, logout, recuperación de contraseña y auto-registro de pacientes.
 */
final class AuthController
{
    public function login(): void
    {
        if (Auth::check()) {
            redirect('dashboard/index');
        }

        if (is_post()) {
            $identifier = (string) input('username');
            $password = (string) input('password');

            if ($identifier === '' || $password === '') {
                validation_fail(
                    ['username' => 'El nombre de usuario y la contraseña son obligatorios.'],
                    ['username']
                );
            }

            if (Auth::attempt($identifier, $password)) {
                clear_form_state();
                flash('success', 'Bienvenido(a), ' . Auth::name() . '.');
                redirect('dashboard/index');
            }

            // Fuerza bruta: pequeña pausa deliberada.
            usleep(400000);
            validation_fail(
                ['password' => 'Nombre de usuario o contraseña incorrectos, o cuenta desactivada.'],
                ['username']
            );
        }

        $notice = ($_GET['error'] ?? '') === 'cuenta'
            ? 'Tu cuenta fue desactivada. Contacta al administrador de la clínica.'
            : null;

        view('auth/login', [
            'title'  => 'Iniciar sesión',
            'notice' => $notice,
            'errors' => errors(),
        ], 'layouts/auth');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('auth/login');
    }

    /**
     * Recuperación de contraseña: envía un correo con un enlace de un solo uso
     * (60 minutos de vigencia).
     *
     * Siempre responde con el mismo mensaje, exista o no el correo, para no
     * revelar qué direcciones están registradas.
     */
    public function recuperar(): void
    {
        if (Auth::check()) {
            redirect('dashboard/index');
        }

        if (is_post()) {
            $email = mb_strtolower((string) input('email'), 'UTF-8');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                validation_fail(
                    ['email' => 'Ingresa un correo electrónico válido.'],
                    ['email'],
                    'auth/recuperar'
                );
            }

            $user = Usuario::findByEmail($email);
            if ($user) {
                $this->sendResetEmail($user);
                PasswordReset::purgeExpired();
            }

            clear_form_state();
            flash('success', 'Se han enviado las instrucciones a tu correo.');
            redirect('auth/recuperar');
        }

        view('auth/forgot', [
            'title'  => 'Recuperar contraseña',
            'errors' => errors(),
        ], 'layouts/auth');
    }

    /**
     * Muestra el formulario de nueva contraseña y, en POST, la guarda.
     * Ruta: auth/restablecer?token=...
     */
    public function restablecer(): void
    {
        if (Auth::check()) {
            redirect('dashboard/index');
        }

        $token = (string) input('token');
        $reset = PasswordReset::findValid($token);

        if (!$reset) {
            flash('error', 'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.');
            redirect('auth/recuperar');
        }

        if (is_post()) {
            $password = (string) input('password');
            $confirm = (string) input('confirm');

            $errors = [];
            if (($msg = password_error($password)) !== null) {
                $errors['password'] = $msg;
            } elseif ($password !== $confirm) {
                $errors['confirm'] = 'Las contraseñas no coinciden.';
            }

            if ($errors !== []) {
                validation_fail($errors, ['password', 'confirm'], 'auth/restablecer', ['token' => $token]);
            }

            Usuario::setPassword((int) $reset['user_id'], $password);
            PasswordReset::consume((int) $reset['user_id']);

            flash('success', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
            redirect('auth/login');
        }

        view('auth/reset', [
            'title'  => 'Nueva contraseña',
            'token'  => $token,
            'name'   => trim((string) $reset['first_name']),
            'errors' => errors(),
        ], 'layouts/auth');
    }

    /** Genera el token y entrega el correo (o lo deja en el log, si no hay SMTP). */
    private function sendResetEmail(array $user): void
    {
        $token = PasswordReset::issue(
            (int) $user['id'],
            (string) ($_SERVER['REMOTE_ADDR'] ?? '')
        );

        $link = absolute_url('auth/restablecer', ['token' => $token]);
        $appName = (string) config('app.name', 'Clínica VidaSalud');
        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

        $html = render_view('emails/reset', [
            'name'    => $name,
            'link'    => $link,
            'minutes' => PasswordReset::LIFETIME_MINUTES,
            'app'     => $appName,
        ]);
        $text = sprintf(
            "Hola %s,\n\nRecibimos una solicitud para restablecer tu contraseña de %s.\n\n"
            . "Copia y pega este enlace en tu navegador:\n%s\n\n"
            . "El enlace caduca en %d minutos y solo puede usarse una vez.\n"
            . "Si no fuiste tú, ignora este mensaje; tu contraseña no cambiará.\n",
            $name !== '' ? $name : 'usuario',
            $appName,
            $link,
            PasswordReset::LIFETIME_MINUTES
        );

        Mailer::send(
            (string) $user['email'],
            'Recupera tu contraseña · ' . $appName,
            $html,
            $text
        );
    }

    /** Auto-registro público: crea un usuario con rol "paciente". */
    public function register(): void
    {
        if (Auth::check()) {
            redirect('dashboard/index');
        }

        $fields = ['first_name', 'last_name', 'username', 'phone', 'document_number', 'password'];

        if (is_post()) {
            $username = (string) input('username');

            $data = [
                'first_name'     => (string) input('first_name'),
                'last_name'      => (string) input('last_name'),
                'username'       => $username,
                'phone'          => (string) input('phone'),
                'document_number'=> (string) input('document_number'),
                'password'       => (string) input('password'),
                'confirm'        => (string) input('confirm'),
            ];

            $errors = [];
            if ($data['first_name'] === '') {
                $errors['first_name'] = 'Ingresa tu nombre.';
            }
            if ($data['last_name'] === '') {
                $errors['last_name'] = 'Ingresa tus apellidos.';
            }

            $usernameError = username_error($data['username']);
            if ($usernameError !== null) {
                $errors['username'] = $usernameError;
            } elseif (Usuario::usernameExists($data['username'])) {
                $errors['username'] = 'Ese nombre de usuario ya está en uso. Elige otro.';
            }

            if ($data['document_number'] === '') {
                $errors['document_number'] = 'Ingresa tu número de documento.';
            } elseif (Paciente::documentExists($data['document_number'])) {
                $errors['document_number'] = 'Ya existe un paciente con ese documento.';
            }

            $passwordError = password_error($data['password']);
            if ($passwordError !== null) {
                $errors['password'] = $passwordError;
            } elseif ($data['password'] !== $data['confirm']) {
                $errors['confirm'] = 'Las contraseñas no coinciden.';
            }

            if ($errors) {
                validation_fail($errors, $fields, 'auth/register');
            }

            try {
                $pdo = db();
                $pdo->beginTransaction();
                $userId = Usuario::create([
                    'role_id'    => Usuario::roleId('paciente'),
                    'username'   => mb_strtolower($data['username'], 'UTF-8'),
                    'email'      => null,
                    'password'   => $data['password'],
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'phone'      => $data['phone'] !== '' ? $data['phone'] : null,
                    'active'     => true,
                ]);
                Paciente::create([
                    'user_id'          => $userId,
                    'document_type'    => (string) input('document_type', 'DNI') ?: 'DNI',
                    'document_number'  => $data['document_number'],
                    'birth_date'       => (string) input('birth_date'),
                    'gender'           => (string) input('gender'),
                    'blood_type'       => (string) input('blood_type'),
                    'address'          => (string) input('address'),
                    'emergency_contact_name'  => (string) input('emergency_contact_name'),
                    'emergency_contact_phone' => (string) input('emergency_contact_phone'),
                ]);
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                validation_fail(['username' => db_error_message($e)], $fields, 'auth/register');
            }

            clear_form_state();
            flash('success', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
            redirect('auth/login');
        }

        view('auth/register', [
            'title'  => 'Crear cuenta de paciente',
            'errors' => errors(),
        ], 'layouts/auth');
    }
}
