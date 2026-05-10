<?php

declare(strict_types=1);

class FieldModel extends BaseModel
{
    public function createFormFields(int $formId, array $fields, array $sequenceMap): void
    {
        foreach ($fields as $index => $field) {
            $sequenceKey = $this->sequenceKeyForField($field, $sequenceMap);
            if (!isset($sequenceMap[$sequenceKey])) {
                throw new RuntimeException('Field sequence mapping is invalid.');
            }

            $options = json_encode([
                'placeholder' => $field['placeholder'] ?? '',
                'options' => $field['options'] ?? [],
            ], JSON_UNESCAPED_SLASHES);

            $this->execute(
                'INSERT INTO form_fields (form_id, sequence_id, field_label, field_type, field_options, is_required, field_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $formId,
                    (int) $sequenceMap[$sequenceKey]['id'],
                    $field['field_label'],
                    $field['field_type'],
                    $options,
                    !empty($field['is_required']) ? 1 : 0,
                    (int) ($field['field_order'] ?? ($index + 1)),
                ]
            );
        }
    }

    private function sequenceKeyForField(array $field, array $sequenceMap): string
    {
        if (!empty($field['preserve_sequence_key'])) {
            return (string) ($field['sequence_key'] ?? '');
        }

        $label = $this->normalizedName((string) ($field['field_label'] ?? ''));
        foreach ($sequenceMap as $key => $sequence) {
            $sequenceName = $this->normalizedName((string) ($sequence['sequence_name'] ?? ''));
            if ($label !== '' && $sequenceName !== '' && (str_contains($label, $sequenceName) || str_contains($sequenceName, $label))) {
                return (string) $key;
            }
        }

        return (string) ($field['sequence_key'] ?? '');
    }

    private function normalizedName(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        return trim($value);
    }

    public function getByForm(int $formId): array
    {
        $fields = $this->fetchAll(
            'SELECT id, form_id, sequence_id, field_label, field_type, field_options, is_required, field_order
             FROM form_fields
             WHERE form_id = ?
             ORDER BY field_order ASC, id ASC',
            [$formId]
        );

        return array_map([$this, 'withFieldMeta'], $fields);
    }

    public function getByFormAndSequence(int $formId, int $sequenceId): array
    {
        $fields = $this->fetchAll(
            'SELECT id, form_id, sequence_id, field_label, field_type, field_options, is_required, field_order
             FROM form_fields
             WHERE form_id = ? AND sequence_id = ?
             ORDER BY field_order ASC, id ASC',
            [$formId, $sequenceId]
        );

        return array_map([$this, 'withFieldMeta'], $fields);
    }

    private function withFieldMeta(array $field): array
    {
        $meta = json_decode((string) ($field['field_options'] ?? ''), true);
        if (!is_array($meta)) {
            $meta = [
                'placeholder' => '',
                'options' => [],
            ];
        }

        $field['is_required'] = (bool) $field['is_required'];
        $field['placeholder'] = (string) ($meta['placeholder'] ?? '');
        $field['options'] = array_values(array_filter((array) ($meta['options'] ?? []), static fn ($value): bool => is_string($value) && $value !== ''));

        return $field;
    }
}
