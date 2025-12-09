<?php

class Customer extends BaseModel
{
    protected $table = 'customers';
    protected ?string $lastError = null;

    public const PAYMENT_STATUSES = ['unpaid', 'deposit', 'paid'];

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    /**
     * Đảm bảo bảng customers có đầy đủ cột cần thiết cho việc quản lý khách theo booking.
     */
    protected function ensureSchema(): void
    {
        $this->ensureColumn('booking_id', "ALTER TABLE {$this->table} ADD COLUMN booking_id INT NULL AFTER id");
        $this->ensureColumn('payment_status', "ALTER TABLE {$this->table} ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER address");
        $this->ensureColumn('special_requests', "ALTER TABLE {$this->table} ADD COLUMN special_requests TEXT NULL AFTER payment_status");
        $this->ensureGenderNullable();
        $this->ensureNullableColumn('id_number');

        // Loại bỏ mọi unique cũ trên id_number để cho phép nhập trùng (nếu cần)
        $this->dropUniqueIndexOnColumn('id_number');

        // Tạo index cho booking_id để truy vấn nhanh hơn (bỏ qua lỗi nếu đã tồn tại)
        try {
            $this->pdo->exec("CREATE INDEX idx_customers_booking_id ON {$this->table} (booking_id)");
        } catch (Throwable $e) {
            // Bỏ qua lỗi duplicate index
        }

        // Thêm khóa ngoại tới bookings nếu chưa có
        try {
            $sql = "
                ALTER TABLE {$this->table}
                ADD CONSTRAINT fk_customers_booking
                FOREIGN KEY (booking_id) REFERENCES bookings(id)
                ON DELETE CASCADE
            ";
            $this->pdo->exec($sql);
        } catch (Throwable $e) {
            // Có thể khóa ngoại đã tồn tại, bỏ qua lỗi
        }
    }

    protected function ensureGenderNullable(): void
    {
        try {
            $sql = "
                SELECT IS_NULLABLE
                FROM information_schema.columns
                WHERE table_schema = :schema
                  AND table_name = :table
                  AND column_name = 'gender'
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':schema', DB_NAME, PDO::PARAM_STR);
            $stmt->bindValue(':table', $this->table, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && strtoupper($row['IS_NULLABLE'] ?? 'YES') === 'NO') {
                $this->pdo->exec("
                    ALTER TABLE {$this->table}
                    MODIFY gender ENUM('Male','Female','Other') NULL
                ");
            }
        } catch (Throwable $e) {
            // ignore if cannot alter
        }
    }

    protected function ensureNullableColumn(string $column): void
    {
        try {
            $sql = "
                SELECT IS_NULLABLE, DATA_TYPE, COLUMN_TYPE
                FROM information_schema.columns
                WHERE table_schema = :schema
                  AND table_name = :table
                  AND column_name = :column
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':schema', DB_NAME, PDO::PARAM_STR);
            $stmt->bindValue(':table', $this->table, PDO::PARAM_STR);
            $stmt->bindValue(':column', $column, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && strtoupper($row['IS_NULLABLE'] ?? 'YES') === 'NO') {
                $columnType = $row['COLUMN_TYPE'] ?? 'varchar(255)';
                $this->pdo->exec("
                    ALTER TABLE {$this->table}
                    MODIFY {$column} {$columnType} NULL
                ");
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    protected function ensureColumn(string $column, string $alterSql): void
    {
        if ($this->columnExists($this->table, $column)) {
            return;
        }

        try {
            $this->pdo->exec($alterSql);
        } catch (Throwable $e) {
        }
    }

    protected function dropUniqueIndexOnColumn(string $column): void
    {
        try {
            $sql = "
                SELECT DISTINCT INDEX_NAME 
                FROM information_schema.statistics
                WHERE table_schema = :schema
                  AND table_name = :table
                  AND column_name = :column
                  AND NON_UNIQUE = 0
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':schema', DB_NAME, PDO::PARAM_STR);
            $stmt->bindValue(':table', $this->table, PDO::PARAM_STR);
            $stmt->bindValue(':column', $column, PDO::PARAM_STR);
            $stmt->execute();
            $indexes = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($indexes as $indexName) {
                $this->pdo->exec("ALTER TABLE {$this->table} DROP INDEX `{$indexName}`");
            }
        } catch (Throwable $e) {
            // Ignore drop errors
        }
    }

    protected function ensureUniqueIndex(array $columns, string $indexName): void
    {
        if ($this->indexExists($indexName)) {
            return;
        }
        $cols = implode(',', array_map(fn($c) => "`{$c}`", $columns));
        try {
            $this->pdo->exec("ALTER TABLE {$this->table} ADD UNIQUE INDEX `{$indexName}` ({$cols})");
        } catch (Throwable $e) {
            // ignore
        }
    }

    protected function indexExists(string $indexName): bool
    {
        try {
            $sql = "
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = :schema
                  AND table_name = :table
                  AND index_name = :index
                LIMIT 1
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':schema', DB_NAME, PDO::PARAM_STR);
            $stmt->bindValue(':table', $this->table, PDO::PARAM_STR);
            $stmt->bindValue(':index', $indexName, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getByBooking(int $bookingId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE booking_id = :booking_id ORDER BY id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int|false
    {
        $this->lastError = null;

        $sql = "
            INSERT INTO {$this->table}
            (booking_id, full_name, gender, date_of_birth, id_type, id_number, contact_phone, email, address, payment_status, special_requests, created_at, updated_at)
            VALUES
            (:booking_id, :full_name, :gender, :date_of_birth, :id_type, :id_number, :contact_phone, :email, :address, :payment_status, :special_requests, NOW(), NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':booking_id', $data['booking_id'], PDO::PARAM_INT);
        $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
        $this->bindNullable($stmt, ':gender', $data['gender']);
        $this->bindNullable($stmt, ':date_of_birth', $data['date_of_birth']);
        $this->bindNullable($stmt, ':id_type', $data['id_type']);
        $this->bindNullable($stmt, ':id_number', $data['id_number']);
        $this->bindNullable($stmt, ':contact_phone', $data['contact_phone']);
        $this->bindNullable($stmt, ':email', $data['email']);
        $this->bindNullable($stmt, ':address', $data['address']);
        $stmt->bindValue(':payment_status', $data['payment_status'] ?? 'unpaid', PDO::PARAM_STR);
        $this->bindNullable($stmt, ':special_requests', $data['special_requests']);

        try {
            if ($stmt->execute()) {
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->lastError = null;

        $sql = "
            UPDATE {$this->table}
            SET full_name = :full_name,
                gender = :gender,
                date_of_birth = :date_of_birth,
                id_type = :id_type,
                id_number = :id_number,
                contact_phone = :contact_phone,
                email = :email,
                address = :address,
                payment_status = :payment_status,
                special_requests = :special_requests,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
        $this->bindNullable($stmt, ':gender', $data['gender']);
        $this->bindNullable($stmt, ':date_of_birth', $data['date_of_birth']);
        $this->bindNullable($stmt, ':id_type', $data['id_type']);
        $this->bindNullable($stmt, ':id_number', $data['id_number']);
        $this->bindNullable($stmt, ':contact_phone', $data['contact_phone']);
        $this->bindNullable($stmt, ':email', $data['email']);
        $this->bindNullable($stmt, ':address', $data['address']);
        $stmt->bindValue(':payment_status', $data['payment_status'] ?? 'unpaid', PDO::PARAM_STR);
        $this->bindNullable($stmt, ':special_requests', $data['special_requests']);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function countByBooking(int $bookingId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE booking_id = :booking_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    protected function bindNullable(PDOStatement $stmt, string $param, $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, $value, PDO::PARAM_STR);
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}

