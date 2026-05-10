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
    <meta name="csrf-token" content="<?= e($csrfToken); ?>">
    <?php if (!empty($extraHead)): ?>
        <?= $extraHead; ?>
    <?php endif; ?>
</head>
<body class="app-body route-<?= e(str_replace(['/', '_'], '-', $currentRoute ?: 'dashboard')); ?>" data-app-url="<?= e(APP_URL); ?>">
    <div class="site-frame">
        <aside class="sidebar">
            <div class="brand-block">
                <a class="brand-mark" href="<?= e(app_url('dashboard')); ?>">FlowForm <span>⚡</span></a>
            </div>
            <?php if ($currentUser): ?>
                <nav class="topnav" aria-label="Primary navigation">
                    <a href="<?= e(app_url(($currentUser['role'] ?? '') === 'admin' ? 'dashboard' : 'employee/dashboard')); ?>" class="<?= ($currentRoute === 'dashboard' || $currentRoute === 'employee/dashboard') ? 'is-active' : ''; ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6v-9h-6v9Zm0-16v5h6V4h-6Z"/></svg>
                        <span>Dashboard</span>
                    </a>
                    <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                        <a href="<?= e(app_url('employees')); ?>" class="<?= $currentRoute === 'employees' ? 'is-active' : ''; ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11ZM8 11c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11Zm0 2c-2.67 0-6 1.34-6 4v2h12v-2c0-2.66-3.33-4-6-4Zm8 0c-.32 0-.68.02-1.06.07 1.28.92 2.06 2.16 2.06 3.93v2h5v-2c0-2.66-3.33-4-6-4Z"/></svg>
                            <span>Employees</span>
                        </a>
                        <a href="<?= e(app_url('forms')); ?>" class="<?= ($currentRoute === 'forms' || preg_match('#^forms/\d+$#', $currentRoute)) ? 'is-active' : ''; ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h9l5 5v15H6V2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>
                            <span>My Forms</span>
                        </a>
                        <a href="<?= e(app_url('forms/builder')); ?>" class="<?= $currentRoute === 'forms/builder' ? 'is-active' : ''; ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v4H4V4Zm0 6h7v10H4V10Zm9 0h7v4h-7v-4Zm0 6h7v4h-7v-4Z"/></svg>
                            <span>Builder</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= e(app_url('employee/dashboard')); ?>" class="<?= str_starts_with($currentRoute, 'employee/') ? 'is-active' : ''; ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v3H4V5Zm0 5h16v3H4v-3Zm0 5h10v3H4v-3Z"/></svg>
                            <span>My Queue</span>
                        </a>
                    <?php endif; ?>
                    <a href="#" class="">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.02-.66-.07-.98l2.11-1.65-2-3.46-2.49 1a7.28 7.28 0 0 0-1.69-.98L15 3h-4l-.36 2.93c-.6.23-1.17.56-1.69.98l-2.49-1-2 3.46 2.11 1.65c-.04.32-.07.65-.07.98s.02.66.07.98l-2.11 1.65 2 3.46 2.49-1c.52.42 1.09.75 1.69.98L11 21h4l.36-2.93c.6-.23 1.17-.56 1.69-.98l2.49 1 2-3.46-2.11-1.65ZM13 15.5A3.5 3.5 0 1 1 13 8a3.5 3.5 0 0 1 0 7.5Z"/></svg>
                        <span>Settings</span>
                    </a>
                </nav>
                <div class="sidebar-user">
                    <div class="avatar"><?= e(strtoupper(substr((string) ($currentUser['name'] ?? 'U'), 0, 1))); ?></div>
                    <div>
                        <strong><?= e((string) ($currentUser['name'] ?? '')); ?></strong>
                        <small><?= e(ucfirst((string) ($currentUser['role'] ?? ''))); ?></small>
                    </div>
                    <a class="logout-link" href="<?= e(app_url('logout')); ?>">Logout</a>
                </div>
            <?php endif; ?>
        </aside>

        <header class="topbar">
            <div>
                <h1><?= e($title ?? 'Dashboard'); ?></h1>
            </div>
            <?php if ($currentUser): ?>
                <div class="navbar-actions">
                    <label class="nav-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 20-5.2-5.2a7 7 0 1 0-1.4 1.4L20 21l1-1ZM5 10a5 5 0 1 1 10 0A5 5 0 0 1 5 10Z"/></svg>
                        <input type="search" placeholder="Search">
                    </label>
                    <button type="button" class="bell-button" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6v-5a7 7 0 0 0-5-6.71V3a2 2 0 1 0-4 0v1.29A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2Z"/></svg>
                        <span>3</span>
                    </button>
                    <div class="nav-user">
                        <div class="avatar"><?= e(strtoupper(substr((string) ($currentUser['name'] ?? 'U'), 0, 1))); ?></div>
                        <strong><?= e((string) ($currentUser['name'] ?? '')); ?></strong>
                        <span>⌄</span>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <main class="page-shell">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']); ?>">
                    <span><?= e($flash['text']); ?></span>
                    <button type="button" class="alert-close" data-dismiss-alert aria-label="Dismiss">&times;</button>
                </div>
            <?php endif; ?>
            <?= $content; ?>
        </main>
    </div>

    <script src="<?= e(asset_url('js/main.js')); ?>"></script>
    <?php foreach (($pageScripts ?? []) as $script): ?>
        <script src="<?= e($script); ?>"></script>
    <?php endforeach; ?>
</body>
</html>
