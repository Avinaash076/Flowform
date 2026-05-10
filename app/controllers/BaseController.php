<?php

declare(strict_types=1);

abstract class BaseController
{
    protected function requireLogin(): void
    {
        if (!user_is_logged_in()) {
            remember_intended_url();
            $this->redirect('login');
        }

        touch_session_activity();
    }

    protected function requireAdmin(): void
    {
        $this->requireLogin();

        if (current_user_role() !== 'admin') {
            $this->abort(403, 'Administrator access is required.');
        }
    }

    protected function requireEmployee(): void
    {
        $this->requireLogin();

        if (current_user_role() !== 'employee') {
            $this->abort(403, 'Employee access is required.');
        }
    }

    protected function requireAjax(): void
    {
        if (!is_ajax_request()) {
            $this->abort(403, 'Direct API access is forbidden.');
        }
    }

    protected function validateCsrfOrFail(): void
    {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!verify_csrf_token(is_string($token) ? $token : null)) {
            $this->abort(419, 'The form session is invalid. Refresh and try again.');
        }
    }

    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile = VIEW_PATH . '/' . $view . '.php';
        $layoutFile = VIEW_PATH . '/layouts/' . $layout . '.php';

        if (!is_file($viewFile) || !is_file($layoutFile)) {
            throw new RuntimeException('View or layout file is missing.');
        }

        $shared = [
            'appName' => APP_NAME,
            'appUrl' => APP_URL,
            'assetUrl' => asset_url(),
            'currentUser' => current_user(),
            'csrfToken' => csrf_token(),
            'flash' => consume_flash_message(),
            'currentRoute' => current_route_path(),
        ];

        extract(array_merge($shared, $data), EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        require $layoutFile;
    }

    protected function redirect(string $path = ''): void
    {
        header('Location: ' . app_url($path));
        exit;
    }

    protected function redirectIntended(string $fallback = '/dashboard'): void
    {
        $path = pull_intended_url($fallback);
        header('Location: ' . app_url(ltrim($path, '/')));
        exit;
    }

    protected function abort(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        $this->render('shared/error', [
            'title' => $statusCode . ' Error',
            'statusCode' => $statusCode,
            'message' => $message,
        ], 'auth');
        exit;
    }

    protected function json(array $payload, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function jsonError(string $message, int $statusCode = 422, array $extra = []): void
    {
        $this->json(array_merge(['success' => false, 'message' => $message], $extra), $statusCode);
    }

    protected function inputString(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;
        return trim(filter_var((string) $value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW));
    }

    protected function inputTextArea(string $key, string $default = ''): string
    {
        $value = (string) ($_POST[$key] ?? $default);
        return trim(str_replace("\0", '', $value));
    }

    protected function allowedValue(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    protected function uploadedFile(string $key): ?array
    {
        if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
            return null;
        }

        return $_FILES[$key];
    }
}
