<?php

class TourGroup extends BaseModel
{
    protected $table = 'tour_groups';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    protected function ensureSchema(): void
    {
        // Kiểm tra và tạo bảng nếu chưa tồn tại
        try {
            $this->pdo->query("SELECT 1 FROM {$this->table} LIMIT 1");
        } catch (Throwable $e) {
            // Bảng chưa tồn tại, tạo mới
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tour_id INT NOT NULL,
                    booking_id INT NOT NULL,
                    group_name VARCHAR(255) NOT NULL,
                    start_date DATE,
                    end_date DATE,
                    total_guests INT DEFAULT 0,
                    status VARCHAR(50) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
                    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
        }
    }

    public function getAll(): array
    {
        try {
            $sql = "
                SELECT 
                    tg.*,
                    t.name AS tour_name,
                    b.customer_name,
                    COUNT(tgu.id) AS actual_guests
                FROM {$this->table} AS tg
                LEFT JOIN tours AS t ON t.id = tg.tour_id
                LEFT JOIN bookings AS b ON b.id = tg.booking_id
                LEFT JOIN tour_guests AS tgu ON tgu.group_id = tg.id
                GROUP BY tg.id
                ORDER BY tg.created_at DESC
            ";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('TourGroup::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    public function getWithDetails(int $groupId): ?array
    {
        $sql = "
            SELECT 
                tg.*,
                t.name AS tour_name,
                b.customer_name,
                b.start_date AS booking_date,
                COUNT(tgu.id) AS actual_guests
            FROM {$this->table} AS tg
            LEFT JOIN tours AS t ON t.id = tg.tour_id
            LEFT JOIN bookings AS b ON b.id = tg.booking_id
            LEFT JOIN tour_guests AS tgu ON tgu.group_id = tg.id
            WHERE tg.id = :group_id
            GROUP BY tg.id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|false
    {
        $sql = "
            INSERT INTO {$this->table}
            (tour_id, booking_id, group_name, start_date, end_date, total_guests, status)
            VALUES
            (:tour_id, :booking_id, :group_name, :start_date, :end_date, :total_guests, :status)
        ";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':tour_id', $data['tour_id'], PDO::PARAM_INT);
            $stmt->bindValue(':booking_id', $data['booking_id'], PDO::PARAM_INT);
            $stmt->bindValue(':group_name', $data['group_name'], PDO::PARAM_STR);
            $stmt->bindValue(':start_date', $data['start_date'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $data['end_date'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':total_guests', $data['total_guests'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status'] ?? 'pending', PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('TourGroup::create error: ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET group_name = :group_name,
                start_date = :start_date,
                end_date = :end_date,
                total_guests = :total_guests,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':group_name', $data['group_name'], PDO::PARAM_STR);
            $stmt->bindValue(':start_date', $data['start_date'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $data['end_date'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':total_guests', $data['total_guests'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':status', $data['status'] ?? 'pending', PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('TourGroup::update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật trạng thái của đoàn (pending, in_progress, completed, cancelled)
     */
    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('TourGroup::updateStatus error: ' . $e->getMessage());
            return false;
        }
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function delete(int $id): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Xóa khách của đoàn trước
            $sqlGuests = "DELETE FROM tour_guests WHERE group_id = :id";
            $stmtGuests = $this->pdo->prepare($sqlGuests);
            $stmtGuests->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtGuests->execute();
            
            // Xóa đoàn
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if ($result) {
                $this->pdo->commit();
                return true;
            } else {
                $this->pdo->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            try {
                $this->pdo->rollBack();
            } catch (Throwable $_) {}
            error_log('TourGroup::delete error: ' . $e->getMessage());
            return false;
        }
    }
}
