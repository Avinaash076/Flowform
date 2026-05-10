<?php

declare(strict_types=1);

if (!defined('APP_NAME')) {
    $detectedScheme = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $detectedScheme = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $detectedScheme = 'https';
    }

    $detectedHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/flowform/index.php';
    $basePath = str_replace('\\', '/', dirname($scriptName));
    $basePath = rtrim($basePath, '/');
    if ($basePath === '' || $basePath === '.') {
        $basePath = '/flowform';
    }

    define('APP_NAME', 'FlowForm');
    define('APP_VERSION', '1.0.0');
    define('APP_DEBUG', true);
    define('APP_URL', $detectedScheme . '://' . $detectedHost . $basePath);

    define('BASE_PATH', dirname(__DIR__));
    define('CONFIG_PATH', BASE_PATH . '/config');
    define('APP_PATH', BASE_PATH . '/app');
    define('CONTROLLER_PATH', APP_PATH . '/controllers');
    define('MODEL_PATH', APP_PATH . '/models');
    define('VIEW_PATH', APP_PATH . '/views');
    define('ASSET_PATH', BASE_PATH . '/assets');

    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'flowform');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    define('SESSION_TIMEOUT', 1800);
}

function app_url(string $path = ''): string
{
    $base = rtrim(APP_URL, '/');
    $path = trim($path, '/');

    return $path === '' ? $base : $base . '/' . $path;
}

function asset_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return app_url('assets/' . $path);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_route_path(): string
{
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';

    if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
        $requestPath = substr($requestPath, strlen($basePath));
    }

    return trim($requestPath, '/');
}

function current_request_suffix(): string
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';

    if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    return '/' . ltrim($requestUri, '/');
}

function is_post_request(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_ajax_request(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}
