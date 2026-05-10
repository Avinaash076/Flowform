<section class="panel-card error-card">
    <p class="eyebrow">FlowForm</p>
    <h1><?= (int) ($statusCode ?? 500); ?></h1>
    <p><?= e((string) ($message ?? 'An unexpected error occurred.')); ?></p>
    <a class="btn-primary" href="<?= e(app_url('login')); ?>">Return to Login</a>
</section>
