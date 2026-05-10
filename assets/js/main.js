function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function getAppUrl() {
    return document.body.getAttribute('data-app-url') || '';
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function showNotification(message, type = 'info') {
    const shell = document.querySelector('.page-shell') || document.querySelector('.auth-shell');
    if (!shell) {
        return;
    }

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<span>${escapeHtml(message)}</span><button type="button" class="alert-close" data-dismiss-alert aria-label="Dismiss">&times;</button>`;
    shell.prepend(alert);
    const dismissButton = alert.querySelector('[data-dismiss-alert]');
    if (dismissButton) {
        dismissButton.addEventListener('click', () => alert.remove());
    }
}

async function ajaxJson(url, method = 'GET', payload = null) {
    const options = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': getCsrfToken(),
        },
    };

    if (payload !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
    }

    const response = await fetch(url, options);
    const rawText = await response.text();
    let data = {};

    try {
        data = rawText ? JSON.parse(rawText) : {};
    } catch (error) {
        data = {
            message: rawText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || 'Request failed.',
        };
    }

    if (!response.ok) {
        const message = data && data.message ? data.message : 'Request failed.';
        throw new Error(message);
    }

    return data;
}

function dismissAlerts() {
    document.querySelectorAll('[data-dismiss-alert]').forEach((button) => {
        button.addEventListener('click', () => {
            const alert = button.closest('.alert');
            if (alert) {
                alert.remove();
            }
        });
    });
}

function splitOptions(rawOptions) {
    return rawOptions
        .split(/\r\n|\r|\n|,/)
        .map((item) => item.trim())
        .filter((item, index, list) => item !== '' && list.indexOf(item) === index);
}

function initializeBuilder() {
    const root = document.getElementById('form-builder-root');
    if (!root) {
        return;
    }

    const form = document.getElementById('form-builder-form');
    const sequenceList = document.getElementById('sequence-list');
    const fieldList = document.getElementById('field-list');
    const sequenceTemplate = document.getElementById('sequence-template');
    const fieldTemplate = document.getElementById('field-template');
    const validateButton = document.getElementById('validate-live-btn');
    const copyFormButton = document.getElementById('copy-form-btn');
    const employees = JSON.parse(root.dataset.employees || '[]');
    const initialState = JSON.parse(root.dataset.builder || '{}');
    const themePrimaryInput = document.getElementById('theme-primary-input');
    const themeBackgroundInput = document.getElementById('theme-background-input');
    const themeRadiusInput = document.getElementById('theme-radius-input');
    const themeFontInput = document.getElementById('theme-font-input');
    const styleImageInput = document.getElementById('theme-style-image');
    const styleImagePreview = document.getElementById('ai-style-preview');

    let sequenceCounter = 0;
    let fieldCounter = 0;

    function nextKey(prefix) {
        const value = Date.now() + Math.floor(Math.random() * 1000);
        return `${prefix}_${value}`;
    }

    function currentSequences() {
        return Array.from(sequenceList.querySelectorAll('[data-sequence-row]')).map((row, index) => ({
            key: row.dataset.key,
            sequence_name: row.querySelector('[data-sequence-name-input]').value.trim(),
            sequence_order: index + 1,
            user_ids: Array.from(row.querySelector('[data-sequence-user-select]').selectedOptions).map((option) => Number(option.value)),
        }));
    }

    function currentFields() {
        return Array.from(fieldList.querySelectorAll('[data-field-row]')).map((row, index) => ({
            key: row.dataset.key,
            field_label: row.querySelector('[data-field-label-input]').value.trim(),
            field_type: row.querySelector('[data-field-type-input]').value,
            sequence_key: row.querySelector('[data-field-sequence-select]').value,
            is_required: row.querySelector('[data-field-required-input]').checked,
            placeholder: row.querySelector('[data-field-placeholder-input]').value.trim(),
            options_text: row.querySelector('[data-field-options-input]').value,
            options: splitOptions(row.querySelector('[data-field-options-input]').value),
            field_order: index + 1,
        }));
    }

    function builderState() {
        return {
            title: document.getElementById('builder-title').value.trim(),
            description: document.getElementById('builder-description').value.trim(),
            status: document.getElementById('builder-status').value,
            theme: {
                primaryColor: themePrimaryInput.value,
                backgroundColor: themeBackgroundInput.value,
                borderRadius: themeRadiusInput.value.trim(),
                fontFamily: themeFontInput.value.trim(),
            },
            sequences: currentSequences(),
            fields: currentFields(),
        };
    }

    function formTemplateState() {
        const state = builderState();
        const sequenceNamesByKey = new Map(state.sequences.map((sequence) => [sequence.key, sequence.sequence_name]));

        return {
            title: state.title,
            description: state.description,
            status: state.status,
            theme: state.theme,
            sequences: state.sequences.map((sequence) => ({
                sequence_name: sequence.sequence_name,
                sequence_order: sequence.sequence_order,
                assigned_employees: employees
                    .filter((employee) => sequence.user_ids.includes(Number(employee.id)))
                    .map((employee) => ({
                        id: Number(employee.id),
                        name: employee.name,
                        email: employee.email,
                    })),
            })),
            fields: state.fields.map((field) => ({
                field_label: field.field_label,
                field_type: field.field_type,
                sequence_name: sequenceNamesByKey.get(field.sequence_key) || '',
                is_required: field.is_required,
                placeholder: field.placeholder,
                options: field.options,
                field_order: field.field_order,
            })),
        };
    }

    async function copyTextToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        const copied = document.execCommand('copy');
        textarea.remove();

        if (!copied) {
            throw new Error('Clipboard copy is not available in this browser.');
        }
    }

    async function copyCurrentFormTemplate() {
        refreshSequenceReferences();
        syncThemePreview();

        const template = formTemplateState();
        const templateJson = JSON.stringify(template, null, 2);

        try {
            await copyTextToClipboard(templateJson);
            showNotification('Form template copied to clipboard.', 'success');
        } catch (error) {
            showNotification(error.message || 'Unable to copy form template.', 'danger');
        }
    }

    function syncThemePreview() {
        themePrimaryInput.value = themePrimaryInput.value || '#0f766e';
        themeBackgroundInput.value = themeBackgroundInput.value || '#f4efe6';
        themeRadiusInput.value = themeRadiusInput.value.trim() || '10px';
        themeFontInput.value = themeFontInput.value.trim() || 'Trebuchet MS';
    }

    function updateStyleImagePreview() {
        if (!styleImageInput || !styleImagePreview) {
            return;
        }

        const file = styleImageInput.files && styleImageInput.files[0] ? styleImageInput.files[0] : null;
        if (!file) {
            styleImagePreview.hidden = true;
            return;
        }

        const image = styleImagePreview.querySelector('img');
        const label = styleImagePreview.querySelector('small');
        image.src = URL.createObjectURL(file);
        label.textContent = file.name;
        styleImagePreview.hidden = false;
    }

    function populateEmployeeSelect(select, selectedIds) {
        select.innerHTML = '';
        employees.forEach((employee) => {
            const option = document.createElement('option');
            option.value = String(employee.id);
            option.textContent = `${employee.name} (${employee.email})`;
            if (selectedIds.includes(Number(employee.id))) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    function normalizeText(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function namesFromValue(value) {
        if (Array.isArray(value)) {
            return value;
        }

        return String(value || '')
            .split(/\r\n|\r|\n|,|;| and /i)
            .map((item) => item.trim())
            .filter(Boolean);
    }

    function employeeIdsForNames(names) {
        const normalizedNames = namesFromValue(names).map(normalizeText).filter(Boolean);
        if (!normalizedNames.length) {
            return [];
        }

        return employees
            .filter((employee) => {
                const employeeName = normalizeText(employee.name);
                const employeeEmail = normalizeText(employee.email);
                return normalizedNames.some((name) => employeeName.includes(name) || name.includes(employeeName) || employeeEmail.includes(name));
            })
            .map((employee) => Number(employee.id));
    }

    function sequenceKeyForName(name, sequences) {
        const normalizedName = normalizeText(name);
        if (!normalizedName) {
            return '';
        }

        const matched = sequences.find((sequence) => {
            const normalizedSequence = normalizeText(sequence.sequence_name);
            return normalizedSequence !== '' && (normalizedSequence.includes(normalizedName) || normalizedName.includes(normalizedSequence));
        });

        return matched ? matched.key : '';
    }

    function sequenceKeyForEmployeeName(name, sequences) {
        const ids = employeeIdsForNames(name);
        if (!ids.length) {
            return '';
        }

        const matched = sequences.find((sequence) => (sequence.user_ids || []).some((id) => ids.includes(Number(id))));
        return matched ? matched.key : '';
    }

    function refreshSequenceReferences() {
        const sequences = currentSequences();

        Array.from(sequenceList.querySelectorAll('[data-sequence-row]')).forEach((row, index) => {
            row.querySelector('[data-sequence-order-input]').name = `sequences[${row.dataset.key}][sequence_order]`;
            row.querySelector('[data-sequence-order-input]').value = String(index + 1);
            row.querySelector('.builder-item-title').textContent = `Sequence ${index + 1}`;
        });

        Array.from(fieldList.querySelectorAll('[data-field-row]')).forEach((row, index) => {
            row.querySelector('[data-field-order-input]').name = `fields[${row.dataset.key}][field_order]`;
            row.querySelector('[data-field-order-input]').value = String(index + 1);
            row.querySelector('.builder-item-title').textContent = `Field ${index + 1}`;

            const select = row.querySelector('[data-field-sequence-select]');
            const selected = select.value;
            select.innerHTML = '';

            sequences.forEach((sequence) => {
                const option = document.createElement('option');
                option.value = sequence.key;
                option.textContent = sequence.sequence_name || `Sequence ${sequence.sequence_order}`;
                if (selected === sequence.key) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            if (!select.value && sequences[0]) {
                select.value = sequences[0].key;
            }
        });
    }

    function findSequenceKeyForFieldLabel(label) {
        const normalizedLabel = normalizeText(label);
        if (!normalizedLabel) {
            return '';
        }

        const sequences = currentSequences();
        const matched = sequences.find((sequence) => {
            const normalizedSequence = normalizeText(sequence.sequence_name);
            return normalizedSequence !== '' && (normalizedLabel.includes(normalizedSequence) || normalizedSequence.includes(normalizedLabel));
        });

        return matched ? matched.key : '';
    }

    function fieldSequenceKey(field, sequences) {
        return sequenceKeyForName(field.sequence || field.sequence_name || field.workflow_step || field.step || '', sequences)
            || sequenceKeyForEmployeeName(field.assigned_employee_name || field.assigned_employee || field.assigned_to || field.employee || field.assignee || '', sequences)
            || sequenceKeyForName(field.label || field.field_label || '', sequences)
            || (sequences[0] ? sequences[0].key : '');
    }

    function assignSequenceFromLabel(row) {
        const sequenceKey = findSequenceKeyForFieldLabel(row.querySelector('[data-field-label-input]').value);
        if (sequenceKey) {
            row.querySelector('[data-field-sequence-select]').value = sequenceKey;
        }
    }

    function updateFieldOptionVisibility(row) {
        const type = row.querySelector('[data-field-type-input]').value;
        const optionsField = row.querySelector('[data-field-options-input]').closest('.field-stack');
        optionsField.style.display = type === 'dropdown' ? 'grid' : 'none';
    }

    function attachSequenceRow(sequence = {}) {
        sequenceCounter += 1;
        const key = sequence.key || nextKey('seq');
        const fragment = sequenceTemplate.content.cloneNode(true);
        const row = fragment.querySelector('[data-sequence-row]');

        row.dataset.key = key;

        const orderInput = row.querySelector('[data-sequence-order-input]');
        const nameInput = row.querySelector('[data-sequence-name-input]');
        const userSelect = row.querySelector('[data-sequence-user-select]');
        const removeButton = row.querySelector('[data-remove-sequence]');

        nameInput.name = `sequences[${key}][sequence_name]`;
        nameInput.value = sequence.sequence_name || '';
        orderInput.name = `sequences[${key}][sequence_order]`;
        orderInput.value = String(sequence.sequence_order || sequenceCounter);
        userSelect.name = `sequences[${key}][user_ids][]`;
        populateEmployeeSelect(userSelect, sequence.user_ids || []);

        removeButton.addEventListener('click', () => {
            row.remove();
            refreshSequenceReferences();
        });

        nameInput.addEventListener('input', refreshSequenceReferences);

        sequenceList.appendChild(fragment);
        refreshSequenceReferences();
    }

    function attachFieldRow(field = {}) {
        fieldCounter += 1;
        const key = field.key || nextKey('field');
        const fragment = fieldTemplate.content.cloneNode(true);
        const row = fragment.querySelector('[data-field-row]');

        row.dataset.key = key;

        const labelInput = row.querySelector('[data-field-label-input]');
        const typeInput = row.querySelector('[data-field-type-input]');
        const sequenceSelect = row.querySelector('[data-field-sequence-select]');
        const requiredInput = row.querySelector('[data-field-required-input]');
        const placeholderInput = row.querySelector('[data-field-placeholder-input]');
        const optionsInput = row.querySelector('[data-field-options-input]');
        const orderInput = row.querySelector('[data-field-order-input]');
        const removeButton = row.querySelector('[data-remove-field]');

        labelInput.name = `fields[${key}][field_label]`;
        typeInput.name = `fields[${key}][field_type]`;
        sequenceSelect.name = `fields[${key}][sequence_key]`;
        requiredInput.name = `fields[${key}][is_required]`;
        placeholderInput.name = `fields[${key}][placeholder]`;
        optionsInput.name = `fields[${key}][options_text]`;
        orderInput.name = `fields[${key}][field_order]`;

        labelInput.value = field.field_label || '';
        typeInput.value = field.field_type || 'text';
        requiredInput.checked = Boolean(field.is_required);
        requiredInput.value = '1';
        placeholderInput.value = field.placeholder || '';
        optionsInput.value = (field.options || []).join('\n');
        orderInput.value = String(field.field_order || fieldCounter);

        typeInput.addEventListener('change', () => updateFieldOptionVisibility(row));
        labelInput.addEventListener('blur', () => assignSequenceFromLabel(row));
        removeButton.addEventListener('click', () => {
            row.remove();
            refreshSequenceReferences();
        });

        fieldList.appendChild(fragment);
        refreshSequenceReferences();
        if (field.sequence_key) {
            sequenceSelect.value = field.sequence_key;
        }
        updateFieldOptionVisibility(row);
    }

    function rebuildFromState(state) {
        sequenceList.innerHTML = '';
        fieldList.innerHTML = '';
        sequenceCounter = 0;
        fieldCounter = 0;

        (state.sequences && state.sequences.length ? state.sequences : []).forEach((sequence) => attachSequenceRow(sequence));
        (state.fields && state.fields.length ? state.fields : []).forEach((field) => attachFieldRow(field));

        if (!sequenceList.children.length) {
            attachSequenceRow({ sequence_name: 'Requested By', sequence_order: 1, user_ids: [] });
        }

        if (!fieldList.children.length) {
            const firstSequence = currentSequences()[0];
            attachFieldRow({
                field_label: 'Field 1',
                field_type: 'text',
                sequence_key: firstSequence ? firstSequence.key : '',
                is_required: true,
                placeholder: '',
                options: [],
                field_order: 1,
            });
        }

        refreshSequenceReferences();
    }

    document.getElementById('add-sequence-btn').addEventListener('click', () => {
        attachSequenceRow({ sequence_name: '', user_ids: [] });
    });

    document.getElementById('add-field-btn').addEventListener('click', () => {
        const firstSequence = currentSequences()[0];
        attachFieldRow({
            field_label: '',
            field_type: 'text',
            sequence_key: firstSequence ? firstSequence.key : '',
            is_required: true,
            placeholder: '',
            options: [],
        });
    });

    if (styleImageInput) {
        styleImageInput.addEventListener('change', updateStyleImagePreview);
    }

    if (copyFormButton) {
        copyFormButton.addEventListener('click', copyCurrentFormTemplate);
    }

    validateButton.addEventListener('click', async () => {
        try {
            const response = await ajaxJson(`${getAppUrl()}/api/forms/validate-live`, 'POST', builderState());
            showNotification(response.message, response.success ? 'success' : 'warning');
            if (Array.isArray(response.errors) && response.errors.length) {
                showNotification(response.errors.join(' '), 'warning');
            }
        } catch (error) {
            showNotification(error.message, 'danger');
        }
    });

    form.addEventListener('submit', () => {
        refreshSequenceReferences();
        syncThemePreview();
    });

    rebuildFromState(initialState);
    syncThemePreview();

    window.FlowFormBuilder = {
        readBuilderState: builderState,
        employeePromptContext() {
            return employees.map((employee) => `${employee.name} <${employee.email}>`).join('\n');
        },
        applyAiSuggestion(payload) {
            const state = builderState();
            let sequences = state.sequences.length ? state.sequences : [{ key: nextKey('seq'), sequence_name: 'Requested By', sequence_order: 1, user_ids: [] }];

            if (Array.isArray(payload.sequences) && payload.sequences.length) {
                sequences = payload.sequences
                    .map((sequence, index) => {
                        const assignedNames = sequence.assigned_employee_names || sequence.assigned_employees || sequence.employees || sequence.employee || sequence.assignee || [];
                        return {
                            key: nextKey('seq'),
                            sequence_name: sequence.name || sequence.sequence_name || `Sequence ${index + 1}`,
                            sequence_order: index + 1,
                            user_ids: employeeIdsForNames(assignedNames),
                        };
                    })
                    .filter((sequence) => sequence.sequence_name.trim() !== '');
            }

            if (Array.isArray(payload.fields) && payload.fields.length) {
                state.fields = payload.fields.map((field, index) => ({
                    key: nextKey('field'),
                    field_label: field.label || `Field ${index + 1}`,
                    field_type: field.type || 'text',
                    sequence_key: fieldSequenceKey(field, sequences),
                    is_required: field.required !== false,
                    placeholder: field.placeholder || '',
                    options: Array.isArray(field.options) ? field.options : [],
                    field_order: index + 1,
                }));
            }

            if (payload.css) {
                themePrimaryInput.value = payload.css.primaryColor || themePrimaryInput.value;
                themeBackgroundInput.value = payload.css.backgroundColor || themeBackgroundInput.value;
                themeRadiusInput.value = payload.css.borderRadius || themeRadiusInput.value;
                themeFontInput.value = payload.css.fontFamily || themeFontInput.value;
            }

            rebuildFromState({
                ...state,
                sequences,
                fields: state.fields,
            });

            syncThemePreview();
        },
    };
}

document.addEventListener('DOMContentLoaded', () => {
    document.body.setAttribute('data-app-url', document.body.getAttribute('data-app-url') || `${window.location.origin}/flowform`);
    dismissAlerts();
    initializeBuilder();
});
