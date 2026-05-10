<?php

declare(strict_types=1);

class FormController extends BaseController
{
    private FormModel $formModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->userModel = new UserModel();
    }

    // TODO: Next - Build form edit/versioning workflow

    public function builder(): void
    {
        $this->requireAdmin();

        $builderData = $this->defaultBuilderData();

        if (is_post_request()) {
            $this->validateCsrfOrFail();
            $builderData = $this->parseBuilderPayload();
            $validation = $this->formModel->validateFormDefinition($builderData, $builderData['status'] === 'live');

            if ($validation !== []) {
                set_flash_message(implode(' ', $validation), 'danger');
            } else {
                $formId = $this->formModel->createForm($builderData, (int) current_user_id());
                if ($builderData['status'] === 'live') {
                    $this->notifyCurrentSequence($formId);
                }
                $message = $builderData['status'] === 'live'
                    ? 'Form created and published successfully.'
                    : 'Draft form saved successfully.';
                set_flash_message($message . ' Reference ID: #' . $formId, 'success');
                $this->redirect('forms');
            }
        }

        $this->render('admin/form-builder', [
            'title' => 'Form Builder',
            'employees' => $this->userModel->getEmployees(),
            'builderData' => $builderData,
            'extraHead' => '<script src="https://js.puter.com/v2/"></script>',
            'pageScripts' => [asset_url('js/ai-chat.js')],
        ]);
    }

    public function publish(int $formId): void
    {
        $this->requireAdmin();
        $this->validateCsrfOrFail();

        $result = $this->formModel->publishForm($formId);
        if ($result['success']) {
            if (!empty($result['published_now'])) {
                $mailResult = $this->notifyCurrentSequence($formId);
                if (($mailResult['failed'] ?? 0) > 0) {
                    set_flash_message(
                        $result['message'] . ' Mail failed for first sequence user: ' . ($mailResult['last_error'] ?: 'Unknown SMTP error.'),
                        'warning'
                    );
                    $this->redirect('forms');
                }

                if (($mailResult['sent'] ?? 0) === 0) {
                    set_flash_message($result['message'] . ' No first sequence user email was sent. ' . ($mailResult['last_error'] ?: 'Check first sequence assignment.'), 'warning');
                    $this->redirect('forms');
                }
            }
            set_flash_message($result['message'], 'success');
        } else {
            set_flash_message($result['message'], 'danger');
        }

        $this->redirect('forms');
    }

    public function validateLive(): void
    {
        $this->requireAdmin();
        $this->requireAjax();
        $this->validateCsrfOrFail();

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $this->jsonError('Invalid JSON payload.');
        }

        $errors = $this->formModel->validateFormDefinition($payload, true);

        $this->json([
            'success' => $errors === [],
            'message' => $errors === [] ? 'Form is valid for live publication.' : 'Validation failed.',
            'errors' => $errors,
        ]);
    }

    private function defaultBuilderData(): array
    {
        return [
            'title' => '',
            'description' => '',
            'status' => 'draft',
            'theme' => [
                'primaryColor' => '#0f766e',
                'backgroundColor' => '#f4efe6',
                'borderRadius' => '10px',
                'fontFamily' => 'Trebuchet MS',
            ],
            'sequences' => [
                [
                    'key' => 'seq_1',
                    'sequence_name' => 'Requested By',
                    'sequence_order' => 1,
                    'user_ids' => [],
                ],
            ],
            'fields' => [
                [
                    'key' => 'field_1',
                    'field_label' => 'Employee Name',
                    'field_type' => 'text',
                    'sequence_key' => 'seq_1',
                    'is_required' => true,
                    'placeholder' => 'Enter employee name',
                    'options' => [],
                    'field_order' => 1,
                ],
            ],
        ];
    }

    private function parseBuilderPayload(): array
    {
        $status = $this->allowedValue($this->inputString('status', 'draft'), ['draft', 'live'], 'draft');
        $theme = $_POST['theme'] ?? [];
        $themePrimary = $this->sanitizeColor((string) ($theme['primaryColor'] ?? '#0f766e'), '#0f766e');
        $themeBackground = $this->sanitizeColor((string) ($theme['backgroundColor'] ?? '#f4efe6'), '#f4efe6');
        $themeRadius = trim((string) ($theme['borderRadius'] ?? '10px'));
        $themeFont = trim((string) ($theme['fontFamily'] ?? 'Trebuchet MS'));
        $themeReferenceImage = $this->storeThemeReferenceImage();

        $sequences = [];
        foreach ((array) ($_POST['sequences'] ?? []) as $key => $sequence) {
            $sequenceName = trim((string) ($sequence['sequence_name'] ?? ''));
            if ($sequenceName === '') {
                continue;
            }

            $userIds = array_values(array_unique(array_filter(array_map('intval', (array) ($sequence['user_ids'] ?? [])))));
            $sequences[] = [
                'key' => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $key),
                'sequence_name' => $sequenceName,
                'sequence_order' => (int) ($sequence['sequence_order'] ?? (count($sequences) + 1)),
                'user_ids' => $userIds,
            ];
        }

        usort($sequences, static fn (array $left, array $right): int => $left['sequence_order'] <=> $right['sequence_order']);

        $fields = [];
        foreach ((array) ($_POST['fields'] ?? []) as $key => $field) {
            $label = trim((string) ($field['field_label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $fieldType = $this->allowedValue((string) ($field['field_type'] ?? 'text'), ['text', 'date', 'number', 'dropdown', 'file', 'checkbox', 'user_dropdown'], 'text');
            $options = $fieldType === 'dropdown'
                ? $this->extractOptions((string) ($field['options_text'] ?? ''))
                : [];

            $fields[] = [
                'key' => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $key),
                'field_label' => $label,
                'field_type' => $fieldType,
                'sequence_key' => preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($field['sequence_key'] ?? '')),
                'is_required' => isset($field['is_required']) && (string) $field['is_required'] === '1',
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                'options' => $options,
                'field_order' => (int) ($field['field_order'] ?? (count($fields) + 1)),
            ];
        }

        usort($fields, static fn (array $left, array $right): int => $left['field_order'] <=> $right['field_order']);

        return [
            'title' => $this->inputString('title'),
            'description' => $this->inputTextArea('description'),
            'status' => $status,
            'theme' => [
                'primaryColor' => $themePrimary,
                'backgroundColor' => $themeBackground,
                'borderRadius' => $themeRadius === '' ? '10px' : $themeRadius,
                'fontFamily' => $themeFont === '' ? 'Trebuchet MS' : $themeFont,
                'referenceImage' => $themeReferenceImage,
            ],
            'sequences' => $sequences,
            'fields' => $fields,
        ];
    }

    private function storeThemeReferenceImage(): string
    {
        $upload = $this->uploadedFile('style_image');
        if (!$upload || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ((int) $upload['error'] !== UPLOAD_ERR_OK) {
            set_flash_message('The style image could not be uploaded.', 'warning');
            return '';
        }

        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
        if ($imageInfo === false) {
            set_flash_message('Only image files can be used as a style reference.', 'warning');
            return '';
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $mimeType = (string) ($imageInfo['mime'] ?? '');
        if (!isset($allowedMimeTypes[$mimeType])) {
            set_flash_message('Style image must be a JPG, PNG, WEBP, or GIF file.', 'warning');
            return '';
        }

        $directory = ASSET_PATH . '/uploads/form_styles';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create style image upload directory.');
        }

        $filename = sprintf('style_%s.%s', bin2hex(random_bytes(8)), $allowedMimeTypes[$mimeType]);
        $target = $directory . '/' . $filename;
        if (!move_uploaded_file($tmpName, $target)) {
            throw new RuntimeException('Unable to store style image.');
        }

        return 'assets/uploads/form_styles/' . $filename;
    }

    private function extractOptions(string $rawOptions): array
    {
        $options = preg_split('/\r\n|\r|\n|,/', $rawOptions) ?: [];
        $options = array_map(static fn (string $option): string => trim($option), $options);
        return array_values(array_filter(array_unique($options), static fn (string $option): bool => $option !== ''));
    }

    private function sanitizeColor(string $color, string $fallback): string
    {
        return preg_match('/^#[a-fA-F0-9]{6}$/', $color) ? $color : $fallback;
    }

    private function notifyCurrentSequence(int $formId): array
    {
        $form = $this->formModel->getAdminFormDetail($formId);
        if (!$form || $form['status'] !== 'live') {
            return ['sent' => 0, 'failed' => 0, 'last_error' => 'Form is not live.'];
        }

        $sequence = (new SequenceModel())->getActiveSequenceForForm($formId);
        if (!$sequence) {
            return ['sent' => 0, 'failed' => 0, 'last_error' => 'No active workflow sequence was found.'];
        }

        if (empty($sequence['employees'])) {
            return ['sent' => 0, 'failed' => 0, 'last_error' => 'The active first sequence has no assigned employee.'];
        }

        $sent = 0;
        $failed = 0;
        $lastError = '';

        foreach ($sequence['employees'] as $employee) {
            $body = nl2br(e(sprintf(
                "Dear %s,\n\nA form is now live and waiting for your action.\n\nForm: %s\nYour Step: %s\n\nPlease login to complete your section:\n%s\n\nRegards,\nFlowForm System",
                $employee['name'],
                $form['title'],
                $sequence['sequence_name'],
                app_url('login')
            )));

            $ok = sendMail(
                (string) $employee['email'],
                (string) $employee['name'],
                'Action Required: FlowForm - ' . $form['title'],
                $body
            );

            if ($ok) {
                $sent++;
            } else {
                $failed++;
                $lastError = lastMailError();
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'last_error' => $lastError];
    }
}
