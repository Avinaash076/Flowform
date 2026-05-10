<?php
$builderStateJson = json_encode($builderData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$employeesJson = json_encode($employees, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

<section class="hero-row">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>AI Form Builder</h1>
        <p class="muted-copy">Create sequences, assign owners, generate fields with AI, and validate before publishing.</p>
    </div>
    <div class="hero-actions">
        <button type="button" class="btn-secondary" id="validate-live-btn">Validate for Live</button>
        <a class="btn-ghost" href="<?= e(app_url('forms')); ?>">Back to Forms</a>
    </div>
</section>

<section class="builder-shell" id="form-builder-root" data-builder='<?= e($builderStateJson); ?>' data-employees='<?= e($employeesJson); ?>'>
    <form class="builder-workspace" id="form-builder-form" method="POST" action="<?= e(app_url('forms/builder')); ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">

        <div class="panel-card">
            <div class="section-heading">
                <h2>Form Details</h2>
                <p>Start with the core metadata and desired publication state.</p>
            </div>
            <div class="two-col-grid">
                <label class="field-stack">
                    <span>Title</span>
                    <input type="text" name="title" id="builder-title" value="<?= e((string) ($builderData['title'] ?? '')); ?>" required>
                </label>
                <label class="field-stack">
                    <span>Status</span>
                    <select name="status" id="builder-status">
                        <option value="draft" <?= (($builderData['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="live" <?= (($builderData['status'] ?? 'draft') === 'live') ? 'selected' : ''; ?>>Live</option>
                    </select>
                </label>
            </div>
            <label class="field-stack">
                <span>Description</span>
                <textarea name="description" id="builder-description" rows="4"><?= e((string) ($builderData['description'] ?? '')); ?></textarea>
            </label>
        </div>

        <div class="panel-card">
            <div class="section-heading">
                <h2>Sequences</h2>
                <p>Each sequence represents one workflow step such as Requested By or Approved By.</p>
            </div>
            <div id="sequence-list" class="builder-stack"></div>
            <button type="button" class="btn-secondary" id="add-sequence-btn">Add Sequence</button>
        </div>

        <div class="panel-card">
            <div class="section-heading">
                <h2>Fields</h2>
                <p>Assign every field to the sequence that owns that piece of the workflow.</p>
            </div>
            <div id="field-list" class="builder-stack"></div>
            <button type="button" class="btn-secondary" id="add-field-btn">Add Field</button>
        </div>

        <input type="hidden" name="theme[primaryColor]" id="theme-primary-input" value="<?= e((string) ($builderData['theme']['primaryColor'] ?? '#0f766e')); ?>">
        <input type="hidden" name="theme[backgroundColor]" id="theme-background-input" value="<?= e((string) ($builderData['theme']['backgroundColor'] ?? '#f4efe6')); ?>">
        <input type="hidden" name="theme[borderRadius]" id="theme-radius-input" value="<?= e((string) ($builderData['theme']['borderRadius'] ?? '10px')); ?>">
        <input type="hidden" name="theme[fontFamily]" id="theme-font-input" value="<?= e((string) ($builderData['theme']['fontFamily'] ?? 'Trebuchet MS')); ?>">

        <div class="builder-submit-row">
            <button type="submit" class="btn-primary">Save Form</button>
        </div>
    </form>

    <aside class="ai-panel">
        <div class="ai-header">
            <span>AI Assistant</span>
            <small>Powered by DeepSeek</small>
        </div>
        <div class="ai-messages" id="ai-messages">
            <div class="ai-bubble">
                Hi! Describe your form and I will generate fields automatically.
                Try: "Create employee onboarding form with name, department and joining date"
            </div>
        </div>
        <div class="ai-input-area">
            <label class="ai-file-upload">
                <span>Style image</span>
                <input type="file" name="style_image" id="theme-style-image" accept="image/png,image/jpeg,image/webp,image/gif">
            </label>
            <div class="ai-style-preview" id="ai-style-preview" hidden>
                <img src="" alt="Selected style reference">
                <small></small>
            </div>
            <textarea id="ai-input" placeholder="Describe your form..."></textarea>
            <button type="button" onclick="sendToAI()">Generate</button>
        </div>
    </aside>
</section>

<template id="sequence-template">
    <div class="builder-item sequence-item" data-sequence-row>
        <div class="builder-item-header">
            <strong class="builder-item-title">Sequence</strong>
            <button type="button" class="icon-button" data-remove-sequence title="Remove sequence">x</button>
        </div>
        <input type="hidden" data-sequence-order-input>
        <label class="field-stack">
            <span>Sequence Name</span>
            <input type="text" data-sequence-name-input required>
        </label>
        <label class="field-stack">
            <span>Assigned Employees</span>
            <select multiple data-sequence-user-select></select>
        </label>
    </div>
</template>

<template id="field-template">
    <div class="builder-item field-item" data-field-row>
        <div class="builder-item-header">
            <strong class="builder-item-title">Field</strong>
            <button type="button" class="icon-button" data-remove-field title="Remove field">x</button>
        </div>
        <div class="two-col-grid">
            <label class="field-stack">
                <span>Field Label</span>
                <input type="text" data-field-label-input required>
            </label>
            <label class="field-stack">
                <span>Field Type</span>
                <select data-field-type-input>
                    <option value="text">Text</option>
                    <option value="date">Date</option>
                    <option value="number">Number</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="file">File</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="user_dropdown">User Dropdown</option>
                </select>
            </label>
        </div>
        <div class="two-col-grid">
            <label class="field-stack">
                <span>Sequence</span>
                <select data-field-sequence-select></select>
            </label>
            <label class="field-stack field-inline-toggle">
                <span>Required</span>
                <input type="checkbox" value="1" data-field-required-input>
            </label>
        </div>
        <label class="field-stack">
            <span>Placeholder</span>
            <input type="text" data-field-placeholder-input>
        </label>
        <label class="field-stack">
            <span>Dropdown Options</span>
            <textarea rows="3" data-field-options-input placeholder="One option per line or comma-separated"></textarea>
        </label>
        <input type="hidden" data-field-order-input>
    </div>
</template>
