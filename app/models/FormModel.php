<?php

declare(strict_types=1);

class FormModel extends BaseModel
{
    public function getDashboardStats(): array
    {
        $forms = $this->fetchOne(
            'SELECT
                COUNT(*) AS total_forms,
                SUM(status = "draft") AS draft_forms,
                SUM(status = "live") AS live_forms,
                SUM(status = "completed") AS completed_forms
             FROM forms'
        ) ?? [];

        $users = (new UserModel())->countEmployees();
        $submissions = $this->fetchOne('SELECT COUNT(*) AS total_submissions FROM submissions') ?? [];

        return [
            'total_forms' => (int) ($forms['total_forms'] ?? 0),
            'draft_forms' => (int) ($forms['draft_forms'] ?? 0),
            'live_forms' => (int) ($forms['live_forms'] ?? 0),
            'completed_forms' => (int) ($forms['completed_forms'] ?? 0),
            'employees' => $users,
            'submissions' => (int) ($submissions['total_submissions'] ?? 0),
        ];
    }

    public function getRecentForms(): array
    {
        return $this->fetchAll(
            'SELECT f.id, f.title, f.status, f.created_at, u.name AS creator_name
             FROM forms f
             LEFT JOIN users u ON u.id = f.created_by
             ORDER BY f.created_at DESC
             LIMIT 6'
        );
    }

    public function getAdminFormDetail(int $formId): ?array
    {
        $themeSelect = $this->columnExists('forms', 'theme_config') ? ', f.theme_config' : ', NULL AS theme_config';
        $form = $this->fetchOne(
            'SELECT
                f.id,
                f.title,
                f.description,
                f.status,
                f.created_at,
                u.name AS creator_name
                ' . $themeSelect . '
             FROM forms f
             LEFT JOIN users u ON u.id = f.created_by
             WHERE f.id = ?
             LIMIT 1',
            [$formId]
        );

        if (!$form) {
            return null;
        }

        $theme = json_decode((string) ($form['theme_config'] ?? ''), true);
        $form['theme'] = is_array($theme) ? $theme : [];
        $form['sequences'] = (new SequenceModel())->getSequencesForForm($formId);
        $form['fields'] = (new FieldModel())->getByForm($formId);

        return $form;
    }

    public function getAdminForms(): array
    {
        $forms = $this->fetchAll(
            'SELECT
                f.id,
                f.title,
                f.description,
                f.status,
                f.created_at,
                u.name AS creator_name,
                COUNT(DISTINCT s.id) AS sequence_count,
                COUNT(DISTINCT ff.id) AS field_count,
                MAX(CASE WHEN ws.status = "in_progress" THEN s.sequence_name END) AS current_sequence_name
             FROM forms f
             LEFT JOIN users u ON u.id = f.created_by
             LEFT JOIN sequences s ON s.form_id = f.id
             LEFT JOIN form_fields ff ON ff.form_id = f.id
             LEFT JOIN workflow_status ws ON ws.form_id = f.id AND ws.sequence_id = s.id
             GROUP BY f.id, f.title, f.description, f.status, f.created_at, u.name
             ORDER BY f.created_at DESC'
        );

        foreach ($forms as &$form) {
            $form['sequences'] = (new SequenceModel())->getSequencesForForm((int) $form['id']);
        }

        return $forms;
    }

    public function getAssignedFormsForEmployee(int $userId): array
    {
        return $this->fetchAll(
            'SELECT DISTINCT
                f.id,
                f.title,
                f.description,
                f.status,
                COALESCE(active_s.sequence_name, s.sequence_name) AS sequence_name,
                ws.started_at,
                f.created_at
             FROM forms f
             INNER JOIN sequences s ON s.form_id = f.id
             INNER JOIN sequence_employees se ON se.sequence_id = s.id
             LEFT JOIN workflow_status ws ON ws.form_id = f.id AND ws.status = "in_progress"
             LEFT JOIN sequences active_s ON active_s.id = ws.sequence_id
             WHERE se.user_id = ?
               AND f.status = "live"
             ORDER BY COALESCE(ws.started_at, f.created_at) DESC',
            [$userId]
        );
    }

    public function getEmployeeFormContext(int $formId, int $userId): ?array
    {
        $themeSelect = $this->columnExists('forms', 'theme_config') ? ', f.theme_config' : ', NULL AS theme_config';
        $context = $this->fetchOne(
            'SELECT
                f.id,
                f.title,
                f.description,
                f.status,
                active_ws.sequence_id AS current_sequence_id,
                active_s.sequence_name
                ' . $themeSelect . '
             FROM forms f
             INNER JOIN sequences assigned_s ON assigned_s.form_id = f.id
             INNER JOIN sequence_employees assigned_se ON assigned_se.sequence_id = assigned_s.id
             LEFT JOIN workflow_status active_ws ON active_ws.form_id = f.id AND active_ws.status = "in_progress"
             LEFT JOIN sequences active_s ON active_s.id = active_ws.sequence_id
             WHERE f.id = ?
               AND assigned_se.user_id = ?
               AND f.status = "live"
             LIMIT 1',
            [$formId, $userId]
        );

        if (!$context) {
            return null;
        }

        $theme = json_decode((string) ($context['theme_config'] ?? ''), true);
        $context['theme'] = is_array($theme) ? $theme : [];
        $context['sequences'] = (new SequenceModel())->getSequencesForForm($formId);
        $context['fields'] = (new FieldModel())->getByForm($formId);
        $context['is_current_user_turn'] = false;

        if (!empty($context['current_sequence_id'])) {
            $assignedToCurrentSequence = $this->fetchOne(
                'SELECT 1
                 FROM sequence_employees
                 WHERE sequence_id = ? AND user_id = ?
                 LIMIT 1',
                [(int) $context['current_sequence_id'], $userId]
            );
            $context['is_current_user_turn'] = (bool) $assignedToCurrentSequence;
        }

        return $context;
    }

    public function validateFormDefinition(array $payload, bool $enforceLiveRules = false): array
    {
        $errors = [];
        $allowedFieldTypes = ['text', 'date', 'number', 'dropdown', 'file', 'checkbox', 'user_dropdown'];

        if (trim((string) ($payload['title'] ?? '')) === '') {
            $errors[] = 'Form title is required.';
        }

        $sequences = array_values(array_filter((array) ($payload['sequences'] ?? []), static fn (array $sequence): bool => trim((string) ($sequence['sequence_name'] ?? '')) !== ''));
        $fields = array_values(array_filter((array) ($payload['fields'] ?? []), static fn (array $field): bool => trim((string) ($field['field_label'] ?? '')) !== ''));

        if ($sequences === []) {
            $errors[] = 'At least one workflow sequence is required.';
        }

        if ($fields === []) {
            $errors[] = 'At least one form field is required.';
        }

        $sequenceKeys = [];
        foreach ($sequences as $sequence) {
            $key = (string) ($sequence['key'] ?? '');
            if ($key === '') {
                $errors[] = 'Each sequence needs a stable key.';
                continue;
            }

            $sequenceKeys[$key] = $sequence;
            if ($enforceLiveRules && empty($sequence['user_ids'])) {
                $errors[] = sprintf('Sequence "%s" must have at least one assigned employee before going live.', $sequence['sequence_name']);
            }
        }

        foreach ($fields as $field) {
            $type = (string) ($field['field_type'] ?? 'text');
            $sequenceKey = (string) ($field['sequence_key'] ?? '');

            if (!in_array($type, $allowedFieldTypes, true)) {
                $errors[] = sprintf('Field "%s" has an invalid type.', $field['field_label']);
            }

            if (!isset($sequenceKeys[$sequenceKey])) {
                $errors[] = sprintf('Field "%s" must be linked to a valid sequence.', $field['field_label']);
            }

            if ($type === 'dropdown' && empty($field['options'])) {
                $errors[] = sprintf('Dropdown field "%s" needs at least one option.', $field['field_label']);
            }
        }

        return array_values(array_unique($errors));
    }

    public function createForm(array $payload, int $createdBy): int
    {
        $sequenceModel = new SequenceModel();
        $fieldModel = new FieldModel();

        $this->beginTransaction();

        try {
            $themeJson = json_encode($payload['theme'] ?? [], JSON_UNESCAPED_SLASHES);
            $hasThemeColumn = $this->columnExists('forms', 'theme_config');

            if ($hasThemeColumn) {
                $this->execute(
                    'INSERT INTO forms (title, description, status, created_by, theme_config) VALUES (?, ?, ?, ?, ?)',
                    [$payload['title'], $payload['description'], $payload['status'], $createdBy, $themeJson]
                );
            } else {
                $this->execute(
                    'INSERT INTO forms (title, description, status, created_by) VALUES (?, ?, ?, ?)',
                    [$payload['title'], $payload['description'], $payload['status'], $createdBy]
                );
            }

            $formId = $this->lastInsertId();
            $sequenceMap = $sequenceModel->createFormSequences($formId, $payload['sequences']);
            $fieldModel->createFormFields($formId, $payload['fields'], $sequenceMap);
            $sequenceModel->initializeWorkflow($formId, array_values($sequenceMap), $payload['status']);

            $this->commit();
            return $formId;
        } catch (Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    public function copyCompletedForm(int $sourceFormId, int $createdBy): array
    {
        $sourceForm = $this->getAdminFormDetail($sourceFormId);
        if (!$sourceForm) {
            return ['success' => false, 'message' => 'The selected form could not be found.'];
        }

        if ($sourceForm['status'] !== 'completed') {
            return ['success' => false, 'message' => 'Only completed forms can be copied.'];
        }

        $sequenceKeyById = [];
        $sequences = [];
        foreach ($sourceForm['sequences'] as $index => $sequence) {
            $key = 'copy_seq_' . (int) $sequence['id'];
            $sequenceKeyById[(int) $sequence['id']] = $key;
            $sequences[] = [
                'key' => $key,
                'sequence_name' => $sequence['sequence_name'],
                'sequence_order' => (int) ($sequence['sequence_order'] ?? ($index + 1)),
                'user_ids' => array_map('intval', $sequence['user_ids'] ?? []),
            ];
        }

        $fields = [];
        foreach ($sourceForm['fields'] as $index => $field) {
            $sourceSequenceId = (int) $field['sequence_id'];
            if (!isset($sequenceKeyById[$sourceSequenceId])) {
                continue;
            }

            $fields[] = [
                'key' => 'copy_field_' . (int) $field['id'],
                'field_label' => $field['field_label'],
                'field_type' => $field['field_type'],
                'sequence_key' => $sequenceKeyById[$sourceSequenceId],
                'is_required' => !empty($field['is_required']),
                'placeholder' => (string) ($field['placeholder'] ?? ''),
                'options' => $field['options'] ?? [],
                'field_order' => (int) ($field['field_order'] ?? ($index + 1)),
                'preserve_sequence_key' => true,
            ];
        }

        $payload = [
            'title' => 'Copy of ' . $sourceForm['title'],
            'description' => (string) ($sourceForm['description'] ?? ''),
            'status' => 'draft',
            'theme' => $sourceForm['theme'] ?? [],
            'sequences' => $sequences,
            'fields' => $fields,
        ];

        $errors = $this->validateFormDefinition($payload, false);
        if ($errors !== []) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $newFormId = $this->createForm($payload, $createdBy);

        return [
            'success' => true,
            'message' => 'Completed form copied as a new draft. Reference ID: #' . $newFormId,
            'form_id' => $newFormId,
        ];
    }

    public function publishForm(int $formId): array
    {
        $form = $this->fetchOne('SELECT id, title, status FROM forms WHERE id = ? LIMIT 1', [$formId]);
        if (!$form) {
            return ['success' => false, 'message' => 'Form not found.'];
        }

        if ($form['status'] === 'live') {
            return ['success' => true, 'message' => 'Form is already live.', 'published_now' => false];
        }

        $payload = [
            'title' => $form['title'],
            'sequences' => [],
            'fields' => [],
        ];

        $sequences = (new SequenceModel())->getSequencesForForm($formId, true);
        foreach ($sequences as $sequence) {
            $payload['sequences'][] = [
                'key' => 'db_' . $sequence['id'],
                'sequence_name' => $sequence['sequence_name'],
                'user_ids' => array_map('intval', $sequence['user_ids']),
            ];
        }

        $fields = (new FieldModel())->getByForm($formId);
        foreach ($fields as $field) {
            $payload['fields'][] = [
                'field_label' => $field['field_label'],
                'field_type' => $field['field_type'],
                'sequence_key' => 'db_' . $field['sequence_id'],
                'options' => $field['options'],
            ];
        }

        $errors = $this->validateFormDefinition($payload, true);
        if ($errors !== []) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $this->beginTransaction();

        try {
            $this->execute('UPDATE forms SET status = "live" WHERE id = ?', [$formId]);
            $this->execute('DELETE FROM workflow_status WHERE form_id = ?', [$formId]);
            (new SequenceModel())->initializeWorkflow($formId, $sequences, 'live');
            $this->commit();

            return ['success' => true, 'message' => 'Form published successfully.', 'published_now' => true];
        } catch (Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }
}
