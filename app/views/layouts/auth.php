<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($title ?? APP_NAME) . ' | ' . APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')); ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/visual-effects.css')); ?>">
    <meta name="csrf-token" content="<?= e($csrfToken); ?>">
    <?php if (!empty($extraHead)): ?>
        <?= $extraHead; ?>
    <?php endif; ?>
</head>
<body class="auth-body login-cursor-page" data-app-url="<?= e(APP_URL); ?>">
    <main class="auth-shell">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']); ?>">
                <span><?= e($flash['text']); ?></span>
                <button type="button" class="alert-close" data-dismiss-alert aria-label="Dismiss">&times;</button>
            </div>
        <?php endif; ?>
        <?= $content; ?>
    </main>
    <script src="<?= e(asset_url('js/main.js')); ?>"></script>
    <?php foreach (($pageScripts ?? []) as $script): ?>
        <script src="<?= e($script); ?>"></script>
    <?php endforeach; ?>
</body>
</html>
