<?php

declare(strict_types=1);

class SequenceController extends BaseController
{
    private FormModel $formModel;
    private SubmissionModel $submissionModel;
    private SequenceModel $sequenceModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->submissionModel = new SubmissionModel();
        $this->sequenceModel = new SequenceModel();
    }

    // TODO: Next - Build sequence reassignment tools

    public function submit(int $formId): void
    {
        $this->requireEmployee();
        $this->validateCsrfOrFail();

        $context = $this->formModel->getEmployeeFormContext($formId, (int) current_user_id());
        if (!$context) {
            set_flash_message('That form is not available for submission.', 'danger');
            $this->redirect('employee/dashboard');
        }

        if (empty($context['is_current_user_turn']) || empty($context['current_sequence_id'])) {
            set_flash_message('This form is visible to you, but it is not your active sequence yet.', 'danger');
            $this->redirect('employee/forms/' . $formId);
        }

        $values = [];
        $errors = [];
        $editableFields = array_values(array_filter(
            $context['fields'],
            static fn (array $field): bool => (int) $field['sequence_id'] === (int) $context['current_sequence_id']
        ));

        foreach ($editableFields as $field) {
            $fieldId = (int) $field['id'];
            $fieldKey = 'field_' . $fieldId;
            $value = '';

            if ($field['field_type'] === 'file') {
                $upload = $this->uploadedFile($fieldKey);
                if ($upload && (int) $upload['error'] === UPLOAD_ERR_OK) {
                    $value = $this->storeUploadedFile($upload, $formId, $fieldId);
                } elseif (!empty($field['is_required'])) {
                    $errors[] = $field['field_label'] . ' is required.';
                }
            } elseif ($field['field_type'] === 'checkbox') {
                $value = isset($_POST[$fieldKey]) ? '1' : '0';
                if (!empty($field['is_required']) && $value !== '1') {
                    $errors[] = $field['field_label'] . ' must be checked.';
                }
            } elseif ($field['field_type'] === 'user_dropdown') {
                $selectedAt = trim((string) ($_POST[$fieldKey . '_selected_at'] ?? ''));
                $timestamp = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $selectedAt);
                $value = json_encode([
                    'user_id' => (int) current_user_id(),
                    'selected_at' => $timestamp ? $timestamp->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                ], JSON_UNESCAPED_SLASHES);
            } else {
                $value = trim((string) ($_POST[$fieldKey] ?? ''));
                if (!empty($field['is_required']) && $value === '') {
                    $errors[] = $field['field_label'] . ' is required.';
                }
            }

            $values[$fieldId] = $value;
        }

        if ($errors !== []) {
            set_flash_message(implode(' ', $errors), 'danger');
            $this->redirect('employee/forms/' . $formId);
        }

        $this->submissionModel->replaceSequenceSubmission(
            $formId,
            (int) $context['current_sequence_id'],
            (int) current_user_id(),
            $values
        );

        $advance = $this->sequenceModel->completeAndAdvance($formId, (int) $context['current_sequence_id']);

        if (!empty($advance['next_sequence'])) {
            $this->notifyNextSequence($context['title'], $advance['next_sequence']);
            set_flash_message('Your section is complete. The next sequence has been notified.', 'success');
        } else {
            set_flash_message('Your section is complete. The workflow has finished.', 'success');
        }

        $this->redirect('employee/dashboard');
    }

    private function storeUploadedFile(array $upload, int $formId, int $fieldId): string
    {
        $directory = ASSET_PATH . '/uploads/form_' . $formId;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $originalName = (string) ($upload['name'] ?? 'upload.bin');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';
        $filename = sprintf('field_%d_%s.%s', $fieldId, bin2hex(random_bytes(6)), $safeExtension);
        $target = $directory . '/' . $filename;

        if (!move_uploaded_file((string) $upload['tmp_name'], $target)) {
            throw new RuntimeException('Unable to store uploaded file.');
        }

        return 'assets/uploads/form_' . $formId . '/' . $filename;
    }

    private function notifyNextSequence(string $formTitle, array $nextSequence): void
    {
        foreach ($nextSequence['employees'] as $employee) {
            $body = nl2br(e(sprintf(
                "Dear %s,\n\nYou have a new form pending your action.\n\nForm: %s\nYour Step: %s\n\nPlease login to complete your section:\n%s\n\nRegards,\nFlowForm System",
                $employee['name'],
                $formTitle,
                $nextSequence['sequence_name'],
                app_url('login')
            )));

            sendMail(
                (string) $employee['email'],
                (string) $employee['name'],
                'Action Required: FlowForm - ' . $formTitle,
                $body
            );
        }
    }
}
