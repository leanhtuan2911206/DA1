<?php

class Assignment extends BaseModel
{
    protected $table = 'tour_assignments';
    private $lastError = '';

    private function ensureTableExists(): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                HDV_ID INT NOT NULL,
                assign_date DATE NULL,
                meeting_point VARCHAR(255) NULL,
                start_time TIME NULL,
                end_time TIME NULL,
                driver_id INT NULL,
                support_id INT NULL,
                notes TEXT NULL
            )");
            $this->ensureColumns();
        } catch (Throwable $e) { $this->lastError = $e->getMessage(); error_log('Assignment::ensureTableExists error: ' . $e->getMessage()); }
    }

    private function tableExists(): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :tbl";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':db', DB_NAME, PDO::PARAM_STR);
            $stmt->bindValue(':tbl', $this->table, PDO::PARAM_STR);
            $stmt->execute();
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) { return false; }
    }

    private function ensureColumns(): void
    {
        // Thêm các cột nếu thiếu để tránh lỗi 1054 Unknown column
        try {
            if (!$this->columnExists($this->table, 'booking_id')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN booking_id INT NOT NULL DEFAULT 0");
            }
            if (!$this->columnExists($this->table, 'HDV_ID')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN HDV_ID INT NOT NULL DEFAULT 0");
            }
            if (!$this->columnExists($this->table, 'user_id')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN user_id INT NULL DEFAULT NULL");
            } else { try { $this->pdo->exec("ALTER TABLE {$this->table} MODIFY COLUMN user_id INT NULL DEFAULT NULL"); } catch (Throwable $e) {} }
            if (!$this->columnExists($this->table, 'schedule_id')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN schedule_id INT NULL DEFAULT NULL");
            } else {
                try { $this->pdo->exec("ALTER TABLE {$this->table} MODIFY COLUMN schedule_id INT NULL DEFAULT NULL"); } catch (Throwable $e) {}
            }
            if (!$this->columnExists($this->table, 'assign_date')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN assign_date DATE NULL");
            }
            if (!$this->columnExists($this->table, 'end_date')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN end_date DATE NULL");
            }
            if (!$this->columnExists($this->table, 'meeting_point')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN meeting_point VARCHAR(255) NULL");
            }
            if (!$this->columnExists($this->table, 'start_time')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN start_time TIME NULL");
            }
            if (!$this->columnExists($this->table, 'end_time')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN end_time TIME NULL");
            }
            if (!$this->columnExists($this->table, 'driver_id')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN driver_id INT NULL");
            }
            if (!$this->columnExists($this->table, 'support_id')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN support_id INT NULL");
            }
            if (!$this->columnExists($this->table, 'notes')) {
                $this->pdo->exec("ALTER TABLE {$this->table} ADD COLUMN notes TEXT NULL");
            }
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log('Assignment::ensureColumns error: ' . $e->getMessage());
        }
    }

    public function getAllSimple(array $filters = []): array
    {
        $this->ensureTableExists();
        $this->ensureColumns();
        $sql = "SELECT id, booking_id, HDV_ID, assign_date, end_date, meeting_point, start_time, end_time, driver_id, support_id, notes FROM {$this->table}";
        $where = [];
        $params = [];
        if (!empty($filters['booking_id'])) { $where[] = 'booking_id = :bid'; $params[':bid'] = (int)$filters['booking_id']; }
        if (!empty($filters['HDV_ID'])) { $where[] = 'HDV_ID = :gid'; $params[':gid'] = (int)$filters['HDV_ID']; }
        if (!empty($filters['date_from'])) { $where[] = 'assign_date >= :df'; $params[':df'] = $filters['date_from']; }
        if (!empty($filters['date_to'])) { $where[] = 'assign_date <= :dt'; $params[':dt'] = $filters['date_to']; }
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY assign_date ASC, id DESC';
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
            if (!$stmt->execute()) { $this->lastError = json_encode($stmt->errorInfo()); return []; }
            return $stmt->fetchAll();
        } catch (Throwable $e) { $this->lastError = $e->getMessage(); return []; }
    }

    public function insertSimple(array $data): bool
    {
        $this->ensureTableExists();
        $this->ensureColumns();
        if (!$this->tableExists()) { error_log('Assignment::insertSimple error: table not exists'); return false; }
        $sql = "INSERT INTO {$this->table} (booking_id, HDV_ID, assign_date, end_date, meeting_point, start_time, end_time, driver_id, support_id, notes, schedule_id) VALUES (:booking_id, :HDV_ID, :assign_date, :end_date, :meeting_point, :start_time, :end_time, :driver_id, :support_id, :notes, :schedule_id)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':booking_id', (int)$data['booking_id'], PDO::PARAM_INT);
            $stmt->bindValue(':HDV_ID', (int)$data['HDV_ID'], PDO::PARAM_INT);
            if (!empty($data['assign_date'])) { $stmt->bindValue(':assign_date', $data['assign_date'], PDO::PARAM_STR); } else { $stmt->bindValue(':assign_date', null, PDO::PARAM_NULL); }
            if (!empty($data['end_date'])) { $stmt->bindValue(':end_date', $data['end_date'], PDO::PARAM_STR); } else { $stmt->bindValue(':end_date', null, PDO::PARAM_NULL); }
            if (!empty($data['meeting_point'])) { $stmt->bindValue(':meeting_point', $data['meeting_point'], PDO::PARAM_STR); } else { $stmt->bindValue(':meeting_point', null, PDO::PARAM_NULL); }
            if (!empty($data['start_time'])) { $stmt->bindValue(':start_time', $data['start_time'], PDO::PARAM_STR); } else { $stmt->bindValue(':start_time', null, PDO::PARAM_NULL); }
            if (!empty($data['end_time'])) { $stmt->bindValue(':end_time', $data['end_time'], PDO::PARAM_STR); } else { $stmt->bindValue(':end_time', null, PDO::PARAM_NULL); }
            if (!empty($data['driver_id'])) { $stmt->bindValue(':driver_id', (int)$data['driver_id'], PDO::PARAM_INT); } else { $stmt->bindValue(':driver_id', null, PDO::PARAM_NULL); }
            if (!empty($data['support_id'])) { $stmt->bindValue(':support_id', (int)$data['support_id'], PDO::PARAM_INT); } else { $stmt->bindValue(':support_id', null, PDO::PARAM_NULL); }
            if (!empty($data['notes'])) { $stmt->bindValue(':notes', $data['notes'], PDO::PARAM_STR); } else { $stmt->bindValue(':notes', null, PDO::PARAM_NULL); }
            $sId = isset($data['schedule_id']) ? (int)$data['schedule_id'] : 0;
            if ($sId > 0) { $stmt->bindValue(':schedule_id', $sId, PDO::PARAM_INT); }
            else { $stmt->bindValue(':schedule_id', null, PDO::PARAM_NULL); }
            $ok = $stmt->execute();
            if (!$ok) { $this->lastError = json_encode($stmt->errorInfo()); }
            return $ok;
        } catch (Throwable $e) { $this->lastError = $e->getMessage(); error_log('Assignment::insertSimple error: ' . $e->getMessage()); return false; }
    }

    public function deleteByIdSimple(int $id): bool
    {
        $this->ensureTableExists();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if (!$ok) { $this->lastError = json_encode($stmt->errorInfo()); }
            return $ok;
        } catch (Throwable $e) { $this->lastError = $e->getMessage(); return false; }
    }

    public function getLastError(): string
    {
        return (string)$this->lastError;
    }

    public function findByIdSimple(int $id): ?array
    {
        $this->ensureTableExists();
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) { $this->lastError = $e->getMessage(); return null; }
    }

    public function updateSimple(int $id, array $data): bool
    {
        $this->ensureTableExists();
        $sql = "UPDATE {$this->table} SET booking_id=:booking_id, HDV_ID=:HDV_ID, assign_date=:assign_date, end_date=:end_date, meeting_point=:meeting_point, start_time=:start_time, end_time=:end_time, driver_id=:driver_id, support_id=:support_id, notes=:notes, schedule_id=:schedule_id, user_id=:user_id WHERE id=:id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':booking_id', (int)$data['booking_id'], PDO::PARAM_INT);
            $stmt->bindValue(':HDV_ID', (int)$data['HDV_ID'], PDO::PARAM_INT);
            $stmt->bindValue(':assign_date', $data['assign_date'] ?: null, $data['assign_date'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':end_date', $data['end_date'] ?: null, $data['end_date'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':meeting_point', $data['meeting_point'] ?: null, $data['meeting_point'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':start_time', $data['start_time'] ?: null, $data['start_time'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':end_time', $data['end_time'] ?: null, $data['end_time'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':driver_id', isset($data['driver_id']) && $data['driver_id'] !== null ? (int)$data['driver_id'] : null, isset($data['driver_id']) && $data['driver_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':support_id', isset($data['support_id']) && $data['support_id'] !== null ? (int)$data['support_id'] : null, isset($data['support_id']) && $data['support_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', $data['notes'] ?: null, $data['notes'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':schedule_id', isset($data['schedule_id']) && $data['schedule_id'] ? (int)$data['schedule_id'] : null, isset($data['schedule_id']) && $data['schedule_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':user_id', isset($data['user_id']) && $data['user_id'] ? (int)$data['user_id'] : null, isset($data['user_id']) && $data['user_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $ok = $stmt->execute();
            if (!$ok) { $this->lastError = json_encode($stmt->errorInfo()); }
            return $ok;
        } catch (Throwable $e) { $this->lastError = $e->getMessage(); return false; }
    }
}
