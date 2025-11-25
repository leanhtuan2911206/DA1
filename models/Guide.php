<?php

class Guide extends BaseModel
{
    protected $table = 'hdv';

    public function countAll(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(?string $status = null): int
    {
        if ($status === null || $status === '') {
            return $this->countAll();
        }

        // Nếu cột `status` không tồn tại thì không thể lọc theo trạng thái
        if (!$this->columnExists($this->table, 'status')) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = :status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':status', $this->normalizeStatus($status), PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function list(array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $conditions = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $conditions[] = "(HoTen LIKE :keyword OR LienHe LIKE :keyword OR NgonNgu LIKE :keyword)";
            $params[':keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['status'])) {
            // Chỉ lọc theo status nếu cột tồn tại
            if ($this->columnExists($this->table, 'status')) {
                $conditions[] = "status = :status";
                $params[':status'] = $this->normalizeStatus($filters['status']);
            }
        }

        if (!empty($filters['language'])) {
            $conditions[] = "NgonNgu LIKE :language";
            $params[':language'] = '%' . $filters['language'] . '%';
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        // Sắp xếp mặc định theo thứ tự ID tăng dần để hiện theo thứ tự thêm
        $sql .= ' ORDER BY HDV_ID ASC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE HDV_ID = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $guide = $stmt->fetch(PDO::FETCH_ASSOC);
        return $guide ?: null;
    }

    public function create(array $data): int|false
    {
        $payload = $this->mapData($data);
        // Thêm created_at/updated_at nếu cột tồn tại
        if ($this->columnExists($this->table, 'created_at')) {
            $payload['created_at'] = date('Y-m-d H:i:s');
        }
        if ($this->columnExists($this->table, 'updated_at')) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
        }

        // Lọc ra chỉ còn các cột thực sự tồn tại trong bảng
        foreach (array_keys($payload) as $col) {
            if (!$this->columnExists($this->table, $col)) {
                unset($payload[$col]);
            }
        }

        // Nếu bảng có cột `ma_so` (mã số hiển thị), gán số thứ tự nhỏ nhất chưa dùng
        if ($this->columnExists($this->table, 'ma_so') && !array_key_exists('ma_so', $payload)) {
            $payload['ma_so'] = $this->getNextAvailableSequence('ma_so');
        }

        $columns = array_keys($payload);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        foreach ($payload as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        if ($stmt->execute()) {
            return (int) $this->pdo->lastInsertId();
        }

        return false;
    }

    public function update(int $id, array $data): bool
    {
        $payload = $this->mapData($data, true);
        // Chỉ thêm updated_at nếu cột tồn tại
        if ($this->columnExists($this->table, 'updated_at')) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
        }

        // Lọc ra chỉ còn các cột thực sự tồn tại trong bảng
        foreach (array_keys($payload) as $col) {
            if (!$this->columnExists($this->table, $col)) {
                unset($payload[$col]);
            }
        }

        if (empty($payload)) {
            return false;
        }

        $set = [];
        foreach (array_keys($payload) as $column) {
            $set[] = "{$column} = :{$column}";
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE HDV_ID = :id',
            $this->table,
            implode(', ', $set)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        foreach ($payload as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE HDV_ID = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (Throwable $e) {
            error_log('Guide::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resequence primary keys HDV_ID to be continuous from 1..N and reset AUTO_INCREMENT.
     * WARNING: This will rewrite primary keys and may break foreign key relations.
     */
    public function resequenceIds(): bool
    {
        try {
            // Use user variable to renumber rows in current order
            $this->pdo->exec("SET @n = 0");
            $this->pdo->exec("UPDATE {$this->table} SET HDV_ID = (@n := @n + 1) ORDER BY HDV_ID");

            // Reset AUTO_INCREMENT to max+1
            $max = (int) $this->pdo->query("SELECT MAX(HDV_ID) AS m FROM {$this->table}")->fetchColumn();
            $next = $max + 1;
            $this->pdo->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = {$next}");

            return true;
        } catch (Throwable $e) {
            error_log('Guide::resequenceIds error: ' . $e->getMessage());
            return false;
        }
    }

    private function mapData(array $data, bool $isUpdate = false): array
    {
        $mapped = [
            'HoTen'            => $this->sanitizeString($data['full_name'] ?? ''),
            'NgaySinh'         => $this->sanitizeDate($data['dob'] ?? null),
            'GioiTinh'         => $this->sanitizeString($data['gender'] ?? null),
            'LienHe'           => $this->sanitizeString($data['contact'] ?? null),
            'NgonNgu'          => $this->sanitizeString($data['languages'] ?? null),
            'DiaChi'           => $this->sanitizeString($data['address'] ?? null),
            'ChungChiHDV'      => $this->sanitizeString($data['certificate'] ?? null),
            'KinhNghiem'       => $this->sanitizeInt($data['experience_years'] ?? null),
            'NgayBatDauLam'    => $this->sanitizeDate($data['start_date'] ?? null),
            'TrangThaiSucKhoe' => $this->sanitizeString($data['health_status'] ?? null),
            'GhiChuNoiBo'      => $this->sanitizeString($data['internal_note'] ?? null),
            'DiemDanhGia'      => $this->sanitizeFloat($data['rating'] ?? null),
            'NhanXetDanhGia'   => $this->sanitizeString($data['review_note'] ?? null),
            // 'hdv_group_id' có thể không tồn tại trên một số cơ sở dữ liệu
            // Kiểm tra tồn tại cột trước khi ánh xạ
            'hdv_group_id'     => null,
            'status'           => $this->normalizeStatus($data['status'] ?? 'active'),
        ];

        // Nếu cột hdv_group_id tồn tại trong bảng thì gán giá trị
        try {
            if ($this->columnExists($this->table, 'hdv_group_id')) {
                $mapped['hdv_group_id'] = $this->sanitizeInt($data['group_id'] ?? 0, 0);
            } else {
                unset($mapped['hdv_group_id']);
            }
        } catch (Throwable $e) {
            // Nếu có lỗi khi kiểm tra, bỏ qua việc ánh xạ trường nhóm
            unset($mapped['hdv_group_id']);
        }

        if (array_key_exists('password', $data)) {
            $mapped['password'] = $this->hashPassword((string)$data['password']);
        } elseif (!$isUpdate) {
            $mapped['password'] = $this->hashPassword('');
        }

        return $mapped;
    }

    private function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function sanitizeDate(?string $value): ?string
    {
        $value = $this->sanitizeString($value);
        if ($value === null) {
            return null;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function sanitizeInt($value, ?int $default = null): ?int
    {
        if ($value === '' || $value === null) {
            return $default;
        }
        if (!is_numeric($value)) {
            return $default;
        }
        return (int) $value;
    }

    private function sanitizeFloat($value): ?float
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return round((float) $value, 1);
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $allowed = ['active', 'inactive', 'on_leave'];
        if (!in_array($status, $allowed, true)) {
            return 'active';
        }
        return $status;
    }

    /**
     * Trả về số thứ tự nhỏ nhất chưa được dùng cho cột $column (1..N).
     */
    private function getNextAvailableSequence(string $column): int
    {
        try {
            $sql = sprintf('SELECT %s FROM %s WHERE %s IS NOT NULL ORDER BY %s ASC', $column, $this->table, $column, $column);
            $stmt = $this->pdo->query($sql);
            $existing = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            $expected = 1;
            foreach ($existing as $val) {
                $n = (int)$val;
                if ($n < $expected) {
                    continue;
                }
                if ($n > $expected) {
                    // found gap
                    return $expected;
                }
                $expected++;
            }

            return $expected;
        } catch (Throwable $e) {
            error_log('Guide::getNextAvailableSequence error: ' . $e->getMessage());
            // fallback to max+1
            $max = (int)$this->pdo->query(sprintf('SELECT MAX(%s) FROM %s', $column, $this->table))->fetchColumn();
            return $max + 1;
        }
    }

    private function hashPassword(string $plain): string
    {
        if ($plain === '') {
            $plain = bin2hex(random_bytes(6));
        }
        return password_hash($plain, PASSWORD_BCRYPT);
    }
}