<section class="hero-row">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>Forms</h1>
        <p class="muted-copy">Review draft and live forms, inspect sequence ownership, and publish validated drafts.</p>
    </div>
    <div class="hero-actions">
        <a class="btn-primary" href="<?= e(app_url('forms/builder')); ?>">New Form</a>
    </div>
</section>

<?php if (!empty($completedForms)): ?>
    <section class="panel-card copy-form-panel">
        <div class="section-heading">
            <div>
                <h2>Copy Completed Form</h2>
                <p>Select a completed form and create the same template again as a draft.</p>
            </div>
        </div>
        <form method="POST" action="<?= e(app_url('forms/copy')); ?>" class="copy-form-row">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
            <label class="field-stack">
                <span>Completed Form</span>
                <select name="source_form_id" required>
                    <option value="">Choose form to copy</option>
                    <?php foreach ($completedForms as $completedForm): ?>
                        <option value="<?= (int) $completedForm['id']; ?>"><?= e($completedForm['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn-secondary">Copy Form</button>
        </form>
    </section>
<?php endif; ?>

<?php if ($forms === []): ?>
    <section class="panel-card">
        <p class="empty-state">No forms are available yet.</p>
    </section>
<?php else: ?>
    <section class="card-grid">
        <?php foreach ($forms as $form): ?>
            <article class="panel-card form-summary-card">
                <div class="section-heading">
                    <div>
                        <h2><a class="row-title-link" href="<?= e(app_url('forms/' . $form['id'])); ?>"><?= e($form['title']); ?></a></h2>
                        <p><?= e($form['description'] ?: 'No description provided.'); ?></p>
                    </div>
                    <span class="status-pill status-<?= e($form['status']); ?>"><?= e(ucfirst($form['status'])); ?></span>
                </div>

                <div class="inline-metrics">
                    <span><?= (int) $form['sequence_count']; ?> sequences</span>
                    <span><?= (int) $form['field_count']; ?> fields</span>
                    <span><?= e((string) ($form['current_sequence_name'] ?? 'Not started')); ?></span>
                </div>

                <div class="sequence-tags">
                    <?php foreach ($form['sequences'] as $sequence): ?>
                        <div class="sequence-chip">
                            <strong><?= e($sequence['sequence_name']); ?></strong>
                            <small><?= e($sequence['employee_names'] ?: 'No assignee'); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card-actions">
                    <?php if ($form['status'] === 'draft'): ?>
                        <form method="POST" action="<?= e(app_url('forms/' . $form['id'] . '/publish')); ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                            <button type="submit" class="btn-secondary">Validate &amp; Publish</button>
                        </form>
                    <?php else: ?>
                        <span class="muted-copy">Created by <?= e((string) ($form['creator_name'] ?? 'Unknown')); ?></span>
                        <?php if ($form['status'] === 'completed'): ?>
                            <form method="POST" action="<?= e(app_url('forms/copy')); ?>">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                <input type="hidden" name="source_form_id" value="<?= (int) $form['id']; ?>">
                                <button type="submit" class="btn-secondary">Copy This Form</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
