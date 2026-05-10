<?php

declare(strict_types=1);

class SequenceModel extends BaseModel
{
    public function createFormSequences(int $formId, array $sequences): array
    {
        $map = [];

        foreach ($sequences as $index => $sequence) {
            $order = (int) ($sequence['sequence_order'] ?? ($index + 1));
            $this->execute(
                'INSERT INTO sequences (form_id, sequence_name, sequence_order) VALUES (?, ?, ?)',
                [$formId, $sequence['sequence_name'], $order]
            );

            $sequenceId = $this->lastInsertId();
            $userIds = array_values(array_unique(array_map('intval', $sequence['user_ids'] ?? [])));

            foreach ($userIds as $userId) {
                $this->execute(
                    'INSERT INTO sequence_employees (sequence_id, user_id) VALUES (?, ?)',
                    [$sequenceId, $userId]
                );
            }

            $map[$sequence['key']] = [
                'id' => $sequenceId,
                'sequence_name' => $sequence['sequence_name'],
                'sequence_order' => $order,
                'user_ids' => $userIds,
            ];
        }

        return $map;
    }

    public function initializeWorkflow(int $formId, array $sequences, string $formStatus): void
    {
        usort($sequences, static fn (array $left, array $right): int => ((int) $left['sequence_order']) <=> ((int) $right['sequence_order']));

        foreach ($sequences as $index => $sequence) {
            $status = 'pending';
            $startedAt = null;

            if ($formStatus === 'live' && $index === 0) {
                $status = 'in_progress';
                $startedAt = date('Y-m-d H:i:s');
            }

            $this->execute(
                'INSERT INTO workflow_status (form_id, sequence_id, status, started_at, completed_at) VALUES (?, ?, ?, ?, NULL)',
                [$formId, $sequence['id'], $status, $startedAt]
            );
        }
    }

    public function getSequencesForForm(int $formId, bool $includeUserIds = false): array
    {
        $sequences = $this->fetchAll(
            'SELECT id, form_id, sequence_name, sequence_order
             FROM sequences
             WHERE form_id = ?
             ORDER BY sequence_order ASC, id ASC',
            [$formId]
        );

        foreach ($sequences as &$sequence) {
            $employees = $this->fetchAll(
                'SELECT u.id, u.name, u.email
                 FROM sequence_employees se
                 INNER JOIN users u ON u.id = se.user_id
                 WHERE se.sequence_id = ?
                 ORDER BY u.name ASC',
                [(int) $sequence['id']]
            );

            $sequence['employees'] = $employees;
            $sequence['user_ids'] = array_map(static fn (array $employee): int => (int) $employee['id'], $employees);

            if (!$includeUserIds) {
                $sequence['employee_names'] = implode(', ', array_column($employees, 'name'));
            }
        }

        return $sequences;
    }

    public function completeAndAdvance(int $formId, int $currentSequenceId): array
    {
        $this->beginTransaction();

        try {
            $currentSequence = $this->fetchOne(
                'SELECT sequence_order, sequence_name FROM sequences WHERE id = ? AND form_id = ? LIMIT 1',
                [$currentSequenceId, $formId]
            );

            if (!$currentSequence) {
                throw new RuntimeException('Current sequence could not be found.');
            }

            $this->execute(
                'UPDATE workflow_status
                 SET status = "completed", completed_at = NOW()
                 WHERE form_id = ? AND sequence_id = ?',
                [$formId, $currentSequenceId]
            );

            $nextSequence = $this->fetchOne(
                'SELECT id, sequence_name, sequence_order
                 FROM sequences
                 WHERE form_id = ? AND sequence_order > ?
                 ORDER BY sequence_order ASC
                 LIMIT 1',
                [$formId, (int) $currentSequence['sequence_order']]
            );

            if ($nextSequence) {
                $this->execute(
                    'UPDATE workflow_status
                     SET status = "in_progress", started_at = COALESCE(started_at, NOW())
                     WHERE form_id = ? AND sequence_id = ?',
                    [$formId, (int) $nextSequence['id']]
                );

                $this->execute('UPDATE forms SET status = "live" WHERE id = ?', [$formId]);
                $nextSequence['employees'] = $this->getSequenceEmployees((int) $nextSequence['id']);
            } else {
                $this->execute('UPDATE forms SET status = "completed" WHERE id = ?', [$formId]);
            }

            $this->commit();

            return [
                'next_sequence' => $nextSequence ?: null,
            ];
        } catch (Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    public function getSequenceEmployees(int $sequenceId): array
    {
        return $this->fetchAll(
            'SELECT u.id, u.name, u.email
             FROM sequence_employees se
             INNER JOIN users u ON u.id = se.user_id
             WHERE se.sequence_id = ?
             ORDER BY u.name ASC',
            [$sequenceId]
        );
    }

    public function getActiveSequenceForForm(int $formId): ?array
    {
        $sequence = $this->fetchOne(
            'SELECT s.id, s.sequence_name, s.sequence_order
             FROM workflow_status ws
             INNER JOIN sequences s ON s.id = ws.sequence_id
             WHERE ws.form_id = ? AND ws.status = "in_progress"
             ORDER BY s.sequence_order ASC, s.id ASC
             LIMIT 1',
            [$formId]
        );

        if (!$sequence) {
            return null;
        }

        $sequence['employees'] = $this->getSequenceEmployees((int) $sequence['id']);
        return $sequence;
    }
}
