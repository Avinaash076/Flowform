<section class="hero-row">
    <div>
        <p class="eyebrow">Employee Workspace</p>
        <h1><?= e($form['title']); ?></h1>
        <p class="muted-copy"><?= e($form['sequence_name'] ?: 'No active sequence'); ?> is the current active sequence.</p>
    </div>
</section>

<section class="panel-card">
    <div class="section-heading">
        <h2>Complete Your Section</h2>
        <p><?= e($form['description'] ?: 'Fill in the required fields and submit your step.'); ?></p>
    </div>

    <?php if (!empty($form['theme']['referenceImage'])): ?>
        <div class="form-reference-image">
            <img src="<?= e(app_url((string) $form['theme']['referenceImage'])); ?>" alt="Form style reference">
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(app_url('employee/forms/' . $form['id'] . '/submit')); ?>" enctype="multipart/form-data" class="stack-form employee-form-grid" id="employee-fill-form">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">

        <?php foreach ($form['fields'] as $field): ?>
            <?php $fieldName = 'field_' . $field['id']; ?>
            <?php $fieldValue = $existingValues[$field['id']] ?? ''; ?>
            <?php $isEditable = !empty($form['is_current_user_turn']) && (int) $field['sequence_id'] === (int) $form['current_sequence_id']; ?>
            <?php
            $userDropdownMeta = [];
            if ($field['field_type'] === 'user_dropdown' && $fieldValue !== '') {
                $decoded = json_decode((string) $fieldValue, true);
                $userDropdownMeta = is_array($decoded) ? $decoded : ['user_id' => $fieldValue, 'selected_at' => ''];
            }
            $selectedAt = (string) ($userDropdownMeta['selected_at'] ?? '');
            $selectedAtInput = $selectedAt !== '' ? date('Y-m-d\TH:i', strtotime($selectedAt)) : '';
            ?>
            <label class="field-stack employee-field-row <?= $isEditable ? '' : 'field-readonly'; ?>">
                <span><?= e($field['field_label']); ?><?= !empty($field['is_required']) && $isEditable ? ' *' : ''; ?></span>

                <?php if ($field['field_type'] === 'text' || $field['field_type'] === 'date' || $field['field_type'] === 'number'): ?>
                    <input
                        type="<?= e($field['field_type']); ?>"
                        name="<?= e($fieldName); ?>"
                        value="<?= e($fieldValue); ?>"
                        placeholder="<?= e($field['placeholder']); ?>"
                        <?= !empty($field['is_required']) && $isEditable ? 'required' : ''; ?>
                        <?= $isEditable ? '' : 'readonly'; ?>
                    >
                <?php elseif ($field['field_type'] === 'dropdown'): ?>
                    <select name="<?= e($fieldName); ?>" <?= !empty($field['is_required']) && $isEditable ? 'required' : ''; ?> <?= $isEditable ? '' : 'disabled'; ?>>
                        <option value="">Select an option</option>
                        <?php foreach ($field['options'] as $option): ?>
                            <option value="<?= e($option); ?>" <?= $fieldValue === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($field['field_type'] === 'user_dropdown'): ?>
                    <div class="user-date-pair">
                        <select name="<?= e($fieldName); ?>" data-user-dropdown <?= !empty($field['is_required']) && $isEditable ? 'required' : ''; ?> <?= $isEditable ? '' : 'disabled'; ?>>
                            <?php if ($isEditable): ?>
                                <option value="">Select user</option>
                                <?php foreach ($userOptions as $user): ?>
                                    <option value="<?= (int) $user['id']; ?>" selected>
                                        <?= e($user['name'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option selected><?= e(!empty($userDropdownMeta['user_id']) ? 'Submitted user #' . $userDropdownMeta['user_id'] : 'Pending'); ?></option>
                            <?php endif; ?>
                        </select>
                        <input
                            type="datetime-local"
                            name="<?= e($fieldName); ?>_selected_at"
                            value="<?= e($selectedAtInput); ?>"
                            data-user-selected-at
                            <?= $isEditable ? 'readonly' : 'disabled'; ?>
                        >
                    </div>
                <?php elseif ($field['field_type'] === 'checkbox'): ?>
                    <input type="checkbox" name="<?= e($fieldName); ?>" value="1" <?= $fieldValue === '1' ? 'checked' : ''; ?> <?= $isEditable ? '' : 'disabled'; ?>>
                <?php elseif ($field['field_type'] === 'file'): ?>
                    <input type="file" name="<?= e($fieldName); ?>" <?= !empty($field['is_required']) && $isEditable ? 'required' : ''; ?> <?= $isEditable ? '' : 'disabled'; ?>>
                    <?php if ($fieldValue !== ''): ?>
                        <small>Existing file: <?= e($fieldValue); ?></small>
                    <?php endif; ?>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>

        <div class="builder-submit-row">
            <button type="submit" class="btn-primary" <?= !empty($form['is_current_user_turn']) ? '' : 'disabled'; ?>>Submit Sequence</button>
            <a class="btn-ghost" href="<?= e(app_url('employee/dashboard')); ?>">Cancel</a>
        </div>
    </form>
</section>

<script>
document.querySelectorAll('[data-user-dropdown]').forEach((select) => {
    const selectedAt = select.closest('.user-date-pair')?.querySelector('[data-user-selected-at]');
    const setCurrentDateTime = () => {
        if (!selectedAt || selectedAt.disabled || selectedAt.value || !select.value) {
            return;
        }

        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        selectedAt.value = now.toISOString().slice(0, 16);
    };

    select.addEventListener('change', () => {
        if (selectedAt) {
            selectedAt.value = '';
        }
        setCurrentDateTime();
    });
    setCurrentDateTime();
});
</script>
