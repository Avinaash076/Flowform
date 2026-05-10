<?php

declare(strict_types=1);

class AuthController extends BaseController
{
    // TODO: Next - Build password reset workflow

    public function home(): void
    {
        if (user_is_logged_in()) {
            $this->redirect('dashboard');
        }

        $this->redirect('login');
    }

    public function login(): void
    {
        if (user_is_logged_in()) {
            $this->redirect('dashboard');
        }

        if (is_post_request()) {
            $this->validateCsrfOrFail();

            $email = filter_var($this->inputString('email'), FILTER_VALIDATE_EMAIL);
            $password = (string) ($_POST['password'] ?? '');

            if ($email === false || $password === '') {
                set_flash_message('Email and password are required.', 'danger');
                $this->render('auth/login', ['title' => 'Login'], 'auth');
                return;
            }

            $user = $this->userModel()->findByEmail((string) $email);

            if (!$user || !password_verify($password, (string) $user['password'])) {
                set_flash_message('Invalid login credentials.', 'danger');
                $this->render('auth/login', ['title' => 'Login'], 'auth');
                return;
            }

            login_user($user);

            if ($user['role'] === 'admin') {
                $this->redirectIntended('/dashboard');
            }

            $this->redirectIntended('/employee/dashboard');
        }

        $this->render('auth/login', ['title' => 'Login'], 'auth');
    }

    public function logout(): void
    {
        logout_user();
        start_secure_session();
        set_flash_message('You have been logged out.', 'info');
        $this->redirect('login');
    }

    private function userModel(): UserModel
    {
        static $model = null;

        if (!$model instanceof UserModel) {
            $model = new UserModel();
        }

        return $model;
    }
}
