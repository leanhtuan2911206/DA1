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
                status ENUM('chờ','xác nhận','hoàn tất','hủy','tạm ngưng') NOT NULL DEFAULT 'chờ',
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

    public function distinctTypes(): array
    {
        $this->ensureTable();
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT service_type FROM {$this->table} ORDER BY service_type ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            return array_values(array_filter(array_map('strval', $rows)));
        } catch (Throwable $e) { return []; }
    }

    public function create(array $data): bool
    {
        $this->ensureTable();
        $columns = ['booking_id','service_type','supplier_name','quantity','status','start_time','end_time','notes'];
        $placeholders = [':booking_id',':service_type',':supplier_name',':quantity',':status',':start_time',':end_time',':notes'];
        foreach (['master_vehicle_id','master_hotel_id','master_flight_id','master_restaurant_id','master_activity_id'] as $col) {
            if ($this->columnExists($this->table, $col)) {
                array_unshift($columns, $col);
                array_unshift($placeholders, ':' . $col);
            }
        }
        $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach (['master_vehicle_id','master_hotel_id','master_flight_id','master_restaurant_id','master_activity_id'] as $col) {
                $ph = ':' . $col;
                if (in_array($ph, $placeholders, true)) {
                    $stmt->bindValue($ph, isset($data[$col]) ? (int)$data[$col] : null, PDO::PARAM_INT);
                }
            }
            $stmt->bindValue(':booking_id', (int)$data['booking_id'], PDO::PARAM_INT);
            $stmt->bindValue(':service_type', $data['service_type'], PDO::PARAM_STR);
            $stmt->bindValue(':supplier_name', $data['supplier_name'], PDO::PARAM_STR);
            $stmt->bindValue(':quantity', (int)($data['quantity'] ?? 1), PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status'] ?? 'chờ', PDO::PARAM_STR);
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
        $setParts = ['booking_id=:booking_id','service_type=:service_type','supplier_name=:supplier_name','quantity=:quantity','status=:status','start_time=:start_time','end_time=:end_time','notes=:notes'];
        foreach (['master_vehicle_id','master_hotel_id','master_flight_id','master_restaurant_id','master_activity_id'] as $col) {
            if ($this->columnExists($this->table, $col)) {
                array_unshift($setParts, $col . '=:' . $col);
            }
        }
        $sql = "UPDATE {$this->table} SET " . implode(',', $setParts) . " WHERE id=:id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            foreach (['master_vehicle_id','master_hotel_id','master_flight_id','master_restaurant_id','master_activity_id'] as $col) {
                $ph = ':' . $col;
                if (strpos($sql, $col . '=' . $ph) !== false) {
                    $stmt->bindValue($ph, isset($data[$col]) ? (int)$data[$col] : null, PDO::PARAM_INT);
                }
            }
            $stmt->bindValue(':booking_id', (int)$data['booking_id'], PDO::PARAM_INT);
            $stmt->bindValue(':service_type', $data['service_type'], PDO::PARAM_STR);
            $stmt->bindValue(':supplier_name', $data['supplier_name'], PDO::PARAM_STR);
            $stmt->bindValue(':quantity', (int)($data['quantity'] ?? 1), PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status'] ?? 'chờ', PDO::PARAM_STR);
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
