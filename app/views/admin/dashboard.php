<section class="hero-row">
    <div>
        <p class="eyebrow">Administrator</p>
        <h1>Dashboard</h1>
        <p class="muted-copy">Track form volume, active workflows, and overall submission load from one place.</p>
    </div>
    <div class="hero-actions">
        <a class="btn-secondary" href="<?= e(app_url('employees')); ?>">Manage Employees</a>
        <a class="btn-primary" href="<?= e(app_url('forms/builder')); ?>">Build Form</a>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <span>Total Forms</span>
        <strong><?= (int) ($stats['total_forms'] ?? 0); ?></strong>
    </article>
    <article class="metric-card">
        <span>Live Workflows</span>
        <strong><?= (int) ($stats['live_forms'] ?? 0); ?></strong>
    </article>
    <article class="metric-card">
        <span>Employees</span>
        <strong><?= (int) ($stats['employees'] ?? 0); ?></strong>
    </article>
    <article class="metric-card">
        <span>Submissions</span>
        <strong><?= (int) ($stats['submissions'] ?? 0); ?></strong>
    </article>
</section>

<section class="content-grid content-grid-wide">
    <article class="panel-card">
        <div class="section-heading">
            <h2>Status Breakdown</h2>
            <p>Current form inventory by lifecycle stage.</p>
        </div>
        <div class="status-breakdown">
            <div><label>Draft</label><strong><?= (int) ($stats['draft_forms'] ?? 0); ?></strong></div>
            <div><label>Live</label><strong><?= (int) ($stats['live_forms'] ?? 0); ?></strong></div>
            <div><label>Completed</label><strong><?= (int) ($stats['completed_forms'] ?? 0); ?></strong></div>
        </div>
    </article>

    <article class="panel-card">
        <div class="section-heading">
            <h2>Recent Forms</h2>
            <p>Latest forms created in the system.</p>
        </div>
        <?php if ($recentForms === []): ?>
            <p class="empty-state">No forms created yet.</p>
        <?php else: ?>
            <div class="list-stack">
                <?php foreach ($recentForms as $form): ?>
                    <a class="list-row clickable-list-row" href="<?= e(app_url('forms/' . $form['id'])); ?>">
                        <div>
                            <strong><?= e($form['title']); ?></strong>
                            <small>Created by <?= e((string) ($form['creator_name'] ?? 'Unknown')); ?></small>
                        </div>
                        <span class="row-action-group">
                            <span class="status-pill status-<?= e($form['status']); ?>"><?= e(ucfirst($form['status'])); ?></span>
                            <span class="btn-mini">Open</span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
