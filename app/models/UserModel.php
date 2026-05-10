<?php

declare(strict_types=1);

class UserModel extends BaseModel
{
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT id, name, email, role, created_at FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    public function createUser(string $name, string $email, string $password, string $role = 'employee'): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->execute(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            [$name, $email, $hash, $role]
        );

        return $this->lastInsertId();
    }

    public function getEmployees(): array
    {
        return $this->fetchAll(
            'SELECT u.id, u.name, u.email, u.role, u.created_at,
                    COUNT(DISTINCT se.sequence_id) AS assigned_sequences
             FROM users u
             LEFT JOIN sequence_employees se ON se.user_id = u.id
             WHERE u.role = ?
             GROUP BY u.id, u.name, u.email, u.role, u.created_at
             ORDER BY u.name ASC',
            ['employee']
        );
    }

    public function countEmployees(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS total FROM users WHERE role = ?', ['employee']);
        return (int) ($row['total'] ?? 0);
    }

    public function getUserDropdownOptions(): array
    {
        return $this->fetchAll('SELECT id, name, email, role FROM users ORDER BY name ASC');
    }

    public function getCurrentUserDropdownOption(int $userId): array
    {
        $user = $this->fetchOne(
            'SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1',
            [$userId]
        );

        return $user ? [$user] : [];
    }
}
