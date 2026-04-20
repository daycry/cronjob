<?php

declare(strict_types=1);

namespace Daycry\CronJob\Controllers;

use Config\Services;

class Login extends BaseCronJob
{
    private const SESSION_LOGIN_ATTEMPTS = 'cronjob_login_attempts';
    private const SESSION_LOCKOUT_UNTIL = 'cronjob_lockout_until';

    /**
     * Displays the form the login to the site.
     */
    public function index()
    {
        if ($this->session->get('cronjob')) {
            return redirect()->to('cronjob/dashboard');
        }

        // Verificar si está bloqueado por intentos fallidos
        if ($this->isLockedOut()) {
            $lockoutTime = $this->session->get(self::SESSION_LOCKOUT_UNTIL);
            $remainingTime = $lockoutTime - time();

            $this->viewData['error'] = "Cuenta bloqueada por múltiples intentos fallidos. Intente nuevamente en " .
                                     ceil($remainingTime / 60) . " minutos.";
        }

        return view(config('CronJob')->views['login'], $this->viewData);
    }

    public function validation()
    {
        $config = config('CronJob');

        // Verificar configuración de seguridad
        if (empty($config->username) || empty($config->password)) {
            log_message('error', 'CronJob: Credenciales no configuradas en el archivo de configuración');
            return redirect()->to('cronjob')->with('error', 'Configuración de seguridad incompleta');
        }

        // Verificar si está bloqueado
        if ($this->isLockedOut()) {
            return redirect()->to('cronjob');
        }

        // Validación CSRF si está habilitada
        if ($config->enableCSRFProtection && !$this->validateCSRF()) {
            log_message('warning', 'CronJob: Intento de acceso con token CSRF inválido desde IP: ' . $this->request->getIPAddress());
            return redirect()->to('cronjob')->with('error', 'Token de seguridad inválido');
        }

        $validation = Services::validation();
        $validation->setRule('username', 'Username', 'required|max_length[100]');
        $validation->setRule('password', 'Password', 'required|max_length[255]');

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->to('cronjob');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Validar credenciales de forma segura
        if (!$this->validateCredentials($username, $password, $config)) {
            $this->recordFailedAttempt();
            log_message('warning', 'CronJob: Intento de login fallido para usuario: ' . $username . ' desde IP: ' . $this->request->getIPAddress());

            return redirect()->to('cronjob')->with('error', 'Credenciales incorrectas');
        }

        // Login exitoso - limpiar intentos fallidos
        $this->clearFailedAttempts();

        // Configurar sesión segura
        $this->setupSecureSession();

        log_message('info', 'CronJob: Login exitoso para usuario: ' . $username . ' desde IP: ' . $this->request->getIPAddress());

        return redirect()->to('cronjob/dashboard');
    }

    public function logout()
    {
        // Destruir sesión completamente
        $this->session->destroy();

        return redirect()->to('cronjob');
    }

    /**
     * Verifica si la cuenta está bloqueada por intentos fallidos
     */
    private function isLockedOut(): bool
    {
        $lockoutUntil = $this->session->get(self::SESSION_LOCKOUT_UNTIL);

        if ($lockoutUntil && time() < $lockoutUntil) {
            return true;
        }

        // Si ya pasó el tiempo de bloqueo, limpiar
        if ($lockoutUntil && time() >= $lockoutUntil) {
            $this->clearFailedAttempts();
        }

        return false;
    }

    /**
     * Registra un intento de login fallido
     */
    private function recordFailedAttempt(): void
    {
        $config = config('CronJob');
        $attempts = $this->session->get(self::SESSION_LOGIN_ATTEMPTS, 0) + 1;

        $this->session->set(self::SESSION_LOGIN_ATTEMPTS, $attempts);

        if ($attempts >= $config->maxLoginAttempts) {
            $lockoutUntil = time() + $config->lockoutTime;
            $this->session->set(self::SESSION_LOCKOUT_UNTIL, $lockoutUntil);
        }
    }

    /**
     * Limpia los intentos fallidos de login
     */
    private function clearFailedAttempts(): void
    {
        $this->session->remove(self::SESSION_LOGIN_ATTEMPTS);
        $this->session->remove(self::SESSION_LOCKOUT_UNTIL);
    }

    /**
     * Valida las credenciales de forma segura
     */
    private function validateCredentials(string $username, string $password, $config): bool
    {
        // Usar hash_equals para prevenir timing attacks
        $usernameValid = hash_equals($config->username, $username);
        $passwordValid = hash_equals($config->password, $password);

        return $usernameValid && $passwordValid;
    }

    /**
     * Configura una sesión segura
     */
    private function setupSecureSession(): void
    {
        $config = config('CronJob');

        // Regenerar ID de sesión para prevenir session fixation
        $this->session->regenerate();

        $this->session->set([
            'cronjob' => true,
            'cronjob_login_time' => time(),
            'cronjob_ip' => $this->request->getIPAddress(),
            'cronjob_user_agent' => $this->request->getUserAgent()
        ]);
    }

    /**
     * Valida el token CSRF
     */
    private function validateCSRF(): bool
    {
        try {
            $csrfToken = $this->request->getPost('csrf_token');
            $sessionToken = $this->session->get('csrf_token');

            return $csrfToken && $sessionToken && hash_equals($sessionToken, $csrfToken);
        } catch (\Exception $e) {
            return false;
        }
    }
}
