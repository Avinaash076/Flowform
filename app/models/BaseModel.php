<?php

declare(strict_types=1);

abstract class BaseModel
{
    protected PDO $db;
    private array $columnCache = [];

    public function __construct()
    {
        $this->db = db();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $statement = $this->db->prepare($sql);
        return $statement->execute($params);
    }

    protected function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    protected function beginTransaction(): void
    {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    protected function commit(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    protected function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    protected function columnExists(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnCache)) {
            return $this->columnCache[$cacheKey];
        }

        $statement = $this->db->prepare('SHOW COLUMNS FROM `' . $table . '` WHERE `Field` = ?');
        $statement->execute([$column]);
        $this->columnCache[$cacheKey] = (bool) $statement->fetch();

        return $this->columnCache[$cacheKey];
    }
}
