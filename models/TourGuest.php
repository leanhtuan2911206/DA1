<?php

class TourGuest extends BaseModel
{
    protected $table = 'tour_guests';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    protected function ensureSchema(): void
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$this->table} LIMIT 1");
        } catch (Throwable $e) {
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id INT NOT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    phone VARCHAR(20),
                    gender VARCHAR(20),
                    date_of_birth DATE,
                    id_type VARCHAR(50),
                    id_number VARCHAR(100),
                    email VARCHAR(255),
                    address TEXT,
                    payment_status VARCHAR(50) DEFAULT 'unpaid',
                    checkin_status VARCHAR(50) DEFAULT 'not_arrived',
                    room_id INT,
                    special_requests TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (group_id) REFERENCES tour_groups(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
        }
    }

    public function getByGroup(int $groupId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE group_id = :group_id ORDER BY id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int|false
    {
        $sql = "
            INSERT INTO {$this->table}
            (group_id, full_name, phone, gender, date_of_birth, id_type, id_number, email, address, payment_status, special_requests)
            VALUES
            (:group_id, :full_name, :phone, :gender, :date_of_birth, :id_type, :id_number, :email, :address, :payment_status, :special_requests)
        ";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':group_id', $data['group_id'], PDO::PARAM_INT);
            $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
            $this->bindNullable($stmt, ':phone', $data['phone'] ?? null);
            $this->bindNullable($stmt, ':gender', $data['gender'] ?? null);
            $this->bindNullable($stmt, ':date_of_birth', $data['date_of_birth'] ?? null);
            $this->bindNullable($stmt, ':id_type', $data['id_type'] ?? null);
            $this->bindNullable($stmt, ':id_number', $data['id_number'] ?? null);
            $this->bindNullable($stmt, ':email', $data['email'] ?? null);
            $this->bindNullable($stmt, ':address', $data['address'] ?? null);
            $stmt->bindValue(':payment_status', $data['payment_status'] ?? 'unpaid', PDO::PARAM_STR);
            $this->bindNullable($stmt, ':special_requests', $data['special_requests'] ?? null);
            
            if ($stmt->execute()) {
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET full_name = :full_name,
                phone = :phone,
                gender = :gender,
                date_of_birth = :date_of_birth,
                id_type = :id_type,
                id_number = :id_number,
                email = :email,
                address = :address,
                payment_status = :payment_status,
                special_requests = :special_requests,
                updated_at = NOW()
            WHERE id = :id
        ";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':full_name', $data['full_name'], PDO::PARAM_STR);
            $this->bindNullable($stmt, ':phone', $data['phone'] ?? null);
            $this->bindNullable($stmt, ':gender', $data['gender'] ?? null);
            $this->bindNullable($stmt, ':date_of_birth', $data['date_of_birth'] ?? null);
            $this->bindNullable($stmt, ':id_type', $data['id_type'] ?? null);
            $this->bindNullable($stmt, ':id_number', $data['id_number'] ?? null);
            $this->bindNullable($stmt, ':email', $data['email'] ?? null);
            $this->bindNullable($stmt, ':address', $data['address'] ?? null);
            $stmt->bindValue(':payment_status', $data['payment_status'] ?? 'unpaid', PDO::PARAM_STR);
            $this->bindNullable($stmt, ':special_requests', $data['special_requests'] ?? null);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateCheckinStatus(int $id, string $status): bool
    {
        $sql = "UPDATE {$this->table} SET checkin_status = :status, updated_at = NOW() WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updatePaymentStatus(int $id, string $status): bool
    {
        $sql = "UPDATE {$this->table} SET payment_status = :status, updated_at = NOW() WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateSpecialRequests(int $id, ?string $specialRequests): bool
    {
        $sql = "UPDATE {$this->table} SET special_requests = :special_requests, updated_at = NOW() WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $this->bindNullable($stmt, ':special_requests', $specialRequests);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function assignRoom(int $guestId, int $roomId): bool
    {
        $sql = "UPDATE {$this->table} SET room_id = :room_id, updated_at = NOW() WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $guestId, PDO::PARAM_INT);
            $stmt->bindValue(':room_id', $roomId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    protected function bindNullable(PDOStatement $stmt, string $param, $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, $value, PDO::PARAM_STR);
        }
    }
}
