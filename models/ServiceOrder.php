<?php

class ServiceOrder extends BaseModel
{
    protected $table = 'booking_services';

    private function ensureTable(): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                service_type VARCHAR(50) NOT NULL,
                supplier_name VARCHAR(255) NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                status VARCHAR(50) NOT NULL DEFAULT 'pending',
                start_time DATETIME NULL,
                end_time DATETIME NULL,
                notes TEXT NULL
            )");
        } catch (Throwable $e) {}
    }

    public function list(array $filters = []): array
    {
        $this->ensureTable();
        $sql = "SELECT * FROM {$this->table}";
        $where = [];
        $params = [];
        if (!empty($filters['booking_id'])) { $where[] = 'booking_id = :bid'; $params[':bid'] = (int)$filters['booking_id']; }
        if (!empty($filters['type'])) { $where[] = 'service_type = :type'; $params[':type'] = $filters['type']; }
        if (!empty($filters['status'])) { $where[] = 'status = :status'; $params[':status'] = $filters['status']; }
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY id DESC';
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) { return []; }
    }

    public function create(array $data): bool
    {
        $this->ensureTable();
        $sql = "INSERT INTO {$this->table} (booking_id, service_type, supplier_name, quantity, status, start_time, end_time, notes) VALUES (:booking_id, :service_type, :supplier_name, :quantity, :status, :start_time, :end_time, :notes)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':booking_id', (int)$data['booking_id'], PDO::PARAM_INT);
            $stmt->bindValue(':service_type', $data['service_type'], PDO::PARAM_STR);
            $stmt->bindValue(':supplier_name', $data['supplier_name'], PDO::PARAM_STR);
            $stmt->bindValue(':quantity', (int)($data['quantity'] ?? 1), PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status'] ?? 'pending', PDO::PARAM_STR);
            $stmt->bindValue(':start_time', $data['start_time'] ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':end_time', $data['end_time'] ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':notes', $data['notes'] ?: null, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (Throwable $e) { return false; }
    }

    public function find(int $id): ?array
    {
        $this->ensureTable();
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) { return null; }
    }

    public function update(int $id, array $data): bool
    {
        $this->ensureTable();
        $sql = "UPDATE {$this->table} SET booking_id=:booking_id, service_type=:service_type, supplier_name=:supplier_name, quantity=:quantity, status=:status, start_time=:start_time, end_time=:end_time, notes=:notes WHERE id=:id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':booking_id', (int)$data['booking_id'], PDO::PARAM_INT);
            $stmt->bindValue(':service_type', $data['service_type'], PDO::PARAM_STR);
            $stmt->bindValue(':supplier_name', $data['supplier_name'], PDO::PARAM_STR);
            $stmt->bindValue(':quantity', (int)($data['quantity'] ?? 1), PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status'] ?? 'pending', PDO::PARAM_STR);
            $stmt->bindValue(':start_time', $data['start_time'] ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':end_time', $data['end_time'] ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':notes', $data['notes'] ?: null, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (Throwable $e) { return false; }
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Throwable $e) { return false; }
    }
}

