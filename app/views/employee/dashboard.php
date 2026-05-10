<section class="hero-row">
    <div>
        <p class="eyebrow">Employee Workspace</p>
        <h1>My Queue</h1>
        <p class="muted-copy">Open the forms currently in progress for your assigned sequences.</p>
    </div>
</section>

<section class="card-grid">
    <?php if ($assignedForms === []): ?>
        <article class="panel-card">
            <p class="empty-state">No pending forms are assigned to you right now.</p>
        </article>
    <?php else: ?>
        <?php foreach ($assignedForms as $form): ?>
            <article class="panel-card queue-card">
                <div class="section-heading">
                    <div>
                        <h2><?= e($form['title']); ?></h2>
                        <p><?= e($form['description'] ?: 'No description provided.'); ?></p>
                    </div>
                    <span class="status-pill status-<?= e($form['status']); ?>"><?= e(ucfirst($form['status'])); ?></span>
                </div>
                <div class="queue-meta">
                    <span>Current Step</span>
                    <strong><?= e($form['sequence_name']); ?></strong>
                </div>
                <a class="btn-primary" href="<?= e(app_url('employee/forms/' . $form['id'])); ?>">Open Form</a>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
