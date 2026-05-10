<?php

declare(strict_types=1);

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $sessionPath = BASE_PATH . '/storage/sessions';

    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0775, true) && !is_dir($sessionPath)) {
        throw new RuntimeException('Unable to create local session storage.');
    }

    session_name('FLOWFORMSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_TIMEOUT);
    ini_set('session.save_path', $sessionPath);

    session_start();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    expire_session_if_inactive();
}

function expire_session_if_inactive(): void
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['last_activity'])) {
        return;
    }

    if ((time() - (int) $_SESSION['last_activity']) <= SESSION_TIMEOUT) {
        return;
    }

    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['flash_message'] = [
        'type' => 'warning',
        'text' => 'Session expired due to 30 minutes of inactivity. Please log in again.',
    ];
}

function touch_session_activity(): void
{
    if (empty($_SESSION['user_id'])) {
        return;
    }

    $_SESSION['last_activity'] = time();

    $lastRegenerated = (int) ($_SESSION['last_regenerated'] ?? 0);
    if ((time() - $lastRegenerated) >= 300) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = time();
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = (string) $user['name'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) $user['role'];
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regenerated'] = time();
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function user_is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_user_role(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

function current_user_name(): string
{
    return (string) ($_SESSION['user_name'] ?? '');
}

function current_user(): ?array
{
    if (!user_is_logged_in()) {
        return null;
    }

    return [
        'id' => current_user_id(),
        'name' => current_user_name(),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
        'role' => current_user_role(),
    ];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

function set_flash_message(string $text, string $type = 'info'): void
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'text' => $text,
    ];
}

function consume_flash_message(): ?array
{
    if (empty($_SESSION['flash_message'])) {
        return null;
    }

    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    return $message;
}

function remember_intended_url(?string $url = null): void
{
    $url = $url ?: current_request_suffix();
    $_SESSION['intended_url'] = $url;
}

function pull_intended_url(string $default = '/dashboard'): string
{
    $url = $_SESSION['intended_url'] ?? $default;
    unset($_SESSION['intended_url']);

    return is_string($url) && str_starts_with($url, '/') ? $url : $default;
}
