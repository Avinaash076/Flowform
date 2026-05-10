<section class="hero-row">
    <div>
        <p class="eyebrow">Form Review</p>
        <h1><?= e($form['title']); ?></h1>
        <p class="muted-copy"><?= e($form['description'] ?: 'No description provided.'); ?></p>
    </div>
    <div class="hero-actions">
        <span class="status-pill status-<?= e($form['status']); ?>"><?= e(ucfirst($form['status'])); ?></span>
        <a class="btn-ghost" href="<?= e(app_url('dashboard')); ?>">Back to Dashboard</a>
    </div>
</section>

<section class="content-grid content-grid-wide">
    <article class="panel-card">
        <div class="section-heading">
            <h2>Workflow</h2>
            <p>Created by <?= e((string) ($form['creator_name'] ?? 'Unknown')); ?>.</p>
        </div>
        <div class="sequence-tags">
            <?php foreach ($form['sequences'] as $sequence): ?>
                <div class="sequence-chip">
                    <strong><?= e($sequence['sequence_name']); ?></strong>
                    <small><?= e($sequence['employee_names'] ?: 'No assignee'); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel-card">
        <div class="section-heading">
            <h2>Fields</h2>
            <p><?= count($form['fields']); ?> fields configured for this form.</p>
        </div>
        <div class="list-stack compact-list">
            <?php foreach ($form['fields'] as $field): ?>
                <div class="list-row">
                    <div>
                        <strong><?= e($field['field_label']); ?></strong>
                        <small><?= e(str_replace('_', ' ', ucfirst($field['field_type']))); ?><?= $field['is_required'] ? ' - Required' : ''; ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="panel-card">
    <div class="section-heading">
        <h2>User Submissions</h2>
        <p>Read-only view of values submitted by assigned users.</p>
    </div>

    <?php if ($submissionReport === []): ?>
        <p class="empty-state">No submitted values are available for this form yet.</p>
    <?php else: ?>
        <div class="submission-report">
            <?php foreach ($submissionReport as $sequence): ?>
                <section class="submission-sequence">
                    <h3><?= e($sequence['sequence_name']); ?></h3>
                    <?php foreach ($sequence['users'] as $user): ?>
                        <article class="submission-user">
                            <div class="submission-user-heading">
                                <div>
                                    <strong><?= e($user['user_name']); ?></strong>
                                    <small><?= e($user['user_email']); ?></small>
                                </div>
                                <small>Submitted <?= e((string) $user['submitted_at']); ?></small>
                            </div>
                            <div class="submission-values">
                                <?php foreach ($user['values'] as $value): ?>
                                    <div class="submission-value">
                                        <span><?= e($value['field_label']); ?></span>
                                        <?php if ($value['field_type'] === 'file' && $value['value'] !== ''): ?>
                                            <a href="<?= e(app_url($value['value'])); ?>" target="_blank" rel="noopener">View file</a>
                                        <?php elseif ($value['field_type'] === 'user_dropdown' && $value['value'] !== ''): ?>
                                            <?php $userMeta = json_decode((string) $value['value'], true); ?>
                                            <?php if (is_array($userMeta)): ?>
                                                <strong><?= e($user['user_name'] . ' at ' . ($userMeta['selected_at'] ?? '-')); ?></strong>
                                            <?php else: ?>
                                                <strong><?= e($value['value']); ?></strong>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <strong><?= e($value['value'] !== '' ? $value['value'] : '-'); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
