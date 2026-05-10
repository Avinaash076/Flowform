<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';

spl_autoload_register(static function (string $className): void {
    $locations = [
        CONTROLLER_PATH . '/' . $className . '.php',
        MODEL_PATH . '/' . $className . '.php',
    ];

    foreach ($locations as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

start_secure_session();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$routePath = current_route_path();
$_SERVER['FLOWFORM_ROUTE'] = $routePath;

$routes = [
    ['methods' => ['GET'], 'pattern' => '#^$#', 'controller' => 'AuthController', 'action' => 'home'],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^login$#', 'controller' => 'AuthController', 'action' => 'login'],
    ['methods' => ['GET'], 'pattern' => '#^logout$#', 'controller' => 'AuthController', 'action' => 'logout'],
    ['methods' => ['GET'], 'pattern' => '#^dashboard$#', 'controller' => 'AdminController', 'action' => 'dashboard'],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^employees$#', 'controller' => 'AdminController', 'action' => 'employees'],
    ['methods' => ['GET'], 'pattern' => '#^forms$#', 'controller' => 'AdminController', 'action' => 'forms'],
    ['methods' => ['POST'], 'pattern' => '#^forms/copy$#', 'controller' => 'AdminController', 'action' => 'copyForm'],
    ['methods' => ['GET'], 'pattern' => '#^forms/(\d+)$#', 'controller' => 'AdminController', 'action' => 'formDetail'],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^forms/builder$#', 'controller' => 'FormController', 'action' => 'builder'],
    ['methods' => ['POST'], 'pattern' => '#^forms/(\d+)/publish$#', 'controller' => 'FormController', 'action' => 'publish'],
    ['methods' => ['POST'], 'pattern' => '#^api/forms/validate-live$#', 'controller' => 'FormController', 'action' => 'validateLive', 'ajax' => true],
    ['methods' => ['GET'], 'pattern' => '#^employee/dashboard$#', 'controller' => 'EmployeeController', 'action' => 'dashboard'],
    ['methods' => ['GET'], 'pattern' => '#^employee/forms/(\d+)$#', 'controller' => 'EmployeeController', 'action' => 'fillForm'],
    ['methods' => ['POST'], 'pattern' => '#^employee/forms/(\d+)/submit$#', 'controller' => 'SequenceController', 'action' => 'submit'],
];

try {
    foreach ($routes as $route) {
        if (!in_array($method, $route['methods'], true)) {
            continue;
        }

        if (!preg_match($route['pattern'], $routePath, $matches)) {
            continue;
        }

        if (($route['ajax'] ?? false) === true && !is_ajax_request()) {
            send_http_error(403, 'Direct API access is forbidden.');
        }

        $controllerName = $route['controller'];
        $action = $route['action'];

        if (!class_exists($controllerName)) {
            throw new RuntimeException('Controller not found: ' . $controllerName);
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            throw new RuntimeException('Action not found: ' . $controllerName . '::' . $action);
        }

        $params = array_values(array_filter(array_slice($matches, 1), static fn ($value): bool => $value !== ''));
        // Convert numeric strings to integers
        $params = array_map(static fn ($value): mixed => is_numeric($value) ? (int) $value : $value, $params);
        $controller->{$action}(...$params);
        exit;
    }

    send_http_error(404, 'The requested page was not found.');
} catch (Throwable $exception) {
    error_log($exception->__toString());

    if (APP_DEBUG) {
        send_http_error(500, $exception->getMessage());
    }

    send_http_error(500, 'An unexpected error occurred.');
}

function send_http_error(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $statusCode; ?> | <?= e(APP_NAME); ?></title>
        <link rel="stylesheet" href="<?= e(asset_url('css/style.css')); ?>">
    </head>
    <body class="error-page">
        <section class="error-shell">
            <p class="eyebrow">FlowForm</p>
            <h1><?= $statusCode; ?></h1>
            <p><?= e($message); ?></p>
            <a class="btn-primary" href="<?= e(app_url('login')); ?>">Back to Login</a>
        </section>
    </body>
    </html>
    <?php
    exit;
}
