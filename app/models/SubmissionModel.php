<?php

declare(strict_types=1);

class SubmissionModel extends BaseModel
{
    public function replaceSequenceSubmission(int $formId, int $sequenceId, int $userId, array $values): void
    {
        $this->beginTransaction();

        try {
            $this->execute(
                'DELETE FROM submissions WHERE form_id = ? AND sequence_id = ? AND user_id = ?',
                [$formId, $sequenceId, $userId]
            );

            foreach ($values as $fieldId => $value) {
                $this->execute(
                    'INSERT INTO submissions (form_id, sequence_id, user_id, field_id, value) VALUES (?, ?, ?, ?, ?)',
                    [$formId, $sequenceId, $userId, (int) $fieldId, (string) $value]
                );
            }

            $this->commit();
        } catch (Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    public function getSubmissionMap(int $formId, int $sequenceId, int $userId): array
    {
        $rows = $this->fetchAll(
            'SELECT field_id, value
             FROM submissions
             WHERE form_id = ? AND sequence_id = ? AND user_id = ?',
            [$formId, $sequenceId, $userId]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['field_id']] = (string) $row['value'];
        }

        return $map;
    }

    public function getLatestFormSubmissionMap(int $formId): array
    {
        $rows = $this->fetchAll(
            'SELECT sub.field_id, sub.value
             FROM submissions sub
             INNER JOIN (
                SELECT field_id, MAX(id) AS latest_id
                FROM submissions
                WHERE form_id = ?
                GROUP BY field_id
             ) latest ON latest.latest_id = sub.id
             ORDER BY sub.id ASC',
            [$formId]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['field_id']] = (string) $row['value'];
        }

        return $map;
    }

    public function getFormSubmissionReport(int $formId): array
    {
        $rows = $this->fetchAll(
            'SELECT
                sub.field_id,
                sub.value,
                sub.submitted_at,
                ff.field_label,
                ff.field_type,
                s.id AS sequence_id,
                s.sequence_name,
                s.sequence_order,
                u.id AS user_id,
                u.name AS user_name,
                u.email AS user_email
             FROM submissions sub
             INNER JOIN form_fields ff ON ff.id = sub.field_id
             INNER JOIN sequences s ON s.id = sub.sequence_id
             INNER JOIN users u ON u.id = sub.user_id
             WHERE sub.form_id = ?
             ORDER BY s.sequence_order ASC, u.name ASC, ff.field_order ASC, sub.id ASC',
            [$formId]
        );

        $report = [];
        foreach ($rows as $row) {
            $sequenceId = (int) $row['sequence_id'];
            $userId = (int) $row['user_id'];

            if (!isset($report[$sequenceId])) {
                $report[$sequenceId] = [
                    'sequence_name' => $row['sequence_name'],
                    'sequence_order' => (int) $row['sequence_order'],
                    'users' => [],
                ];
            }

            if (!isset($report[$sequenceId]['users'][$userId])) {
                $report[$sequenceId]['users'][$userId] = [
                    'user_name' => $row['user_name'],
                    'user_email' => $row['user_email'],
                    'submitted_at' => $row['submitted_at'],
                    'values' => [],
                ];
            }

            $report[$sequenceId]['users'][$userId]['values'][] = $row;
            if (strcmp((string) $row['submitted_at'], (string) $report[$sequenceId]['users'][$userId]['submitted_at']) > 0) {
                $report[$sequenceId]['users'][$userId]['submitted_at'] = $row['submitted_at'];
            }
        }

        foreach ($report as &$sequence) {
            $sequence['users'] = array_values($sequence['users']);
        }

        return array_values($report);
    }
}
