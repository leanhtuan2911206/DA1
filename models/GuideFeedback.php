<?php

class GuideFeedback extends BaseModel
{
    protected $table = 'guide_feedbacks';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$this->table} LIMIT 1");
        } catch (Throwable $e) {
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    guide_id INT NOT NULL,
                    booking_id INT NULL,
                    tour_id INT NULL,
                    feedback_type ENUM('tour', 'hotel', 'restaurant', 'vehicle', 'service', 'supplier', 'other') NOT NULL DEFAULT 'tour',
                    supplier_name VARCHAR(255) NULL,
                    rating INT NULL COMMENT 'Điểm đánh giá từ 1-5',
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    suggestions TEXT NULL COMMENT 'Đề xuất cải thiện',
                    status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, reviewed, resolved',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_guide_id (guide_id),
                    INDEX idx_booking_id (booking_id),
                    INDEX idx_tour_id (tour_id),
                    INDEX idx_feedback_type (feedback_type),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->pdo->exec($sql);
        }
    }

    public function create(array $data): int|false
    {
        try {
            $sql = "
                INSERT INTO {$this->table} 
                (guide_id, booking_id, tour_id, feedback_type, supplier_name, rating, title, content, suggestions, status)
                VALUES 
                (:guide_id, :booking_id, :tour_id, :feedback_type, :supplier_name, :rating, :title, :content, :suggestions, :status)
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':guide_id', $data['guide_id'], PDO::PARAM_INT);
            $stmt->bindValue(':booking_id', $data['booking_id'] ?? null, $data['booking_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':tour_id', $data['tour_id'] ?? null, $data['tour_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':feedback_type', $data['feedback_type'] ?? 'tour', PDO::PARAM_STR);
            $stmt->bindValue(':supplier_name', $data['supplier_name'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':rating', $data['rating'] ?? null, $data['rating'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindValue(':content', $data['content'], PDO::PARAM_STR);
            $stmt->bindValue(':suggestions', $data['suggestions'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'pending', PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getByGuideId(int $guideId, array $filters = []): array
    {
        $sql = "SELECT gf.*, 
                       t.name as tour_name,
                       b.customer_name as booking_customer,
                       b.start_date as booking_start_date,
                       h.HoTen as guide_name
                FROM {$this->table} gf
                LEFT JOIN tours t ON t.id = gf.tour_id
                LEFT JOIN bookings b ON b.id = gf.booking_id
                LEFT JOIN hdv h ON h.HDV_ID = gf.guide_id
                WHERE gf.guide_id = :guide_id";
        
        $params = [':guide_id' => $guideId];
        
        if (!empty($filters['feedback_type'])) {
            $sql .= " AND gf.feedback_type = :feedback_type";
            $params[':feedback_type'] = $filters['feedback_type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND gf.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['booking_id'])) {
            $sql .= " AND gf.booking_id = :booking_id";
            $params[':booking_id'] = $filters['booking_id'];
        }
        
        $sql .= " ORDER BY gf.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT gf.*, 
                       t.name as tour_name,
                       b.customer_name as booking_customer,
                       b.start_date as booking_start_date,
                       h.HoTen as guide_name
                FROM {$this->table} gf
                LEFT JOIN tours t ON t.id = gf.tour_id
                LEFT JOIN bookings b ON b.id = gf.booking_id
                LEFT JOIN hdv h ON h.HDV_ID = gf.guide_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['guide_id'])) {
            $sql .= " AND gf.guide_id = :guide_id";
            $params[':guide_id'] = $filters['guide_id'];
        }
        
        if (!empty($filters['feedback_type'])) {
            $sql .= " AND gf.feedback_type = :feedback_type";
            $params[':feedback_type'] = $filters['feedback_type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND gf.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['tour_id'])) {
            $sql .= " AND gf.tour_id = :tour_id";
            $params[':tour_id'] = $filters['tour_id'];
        }
        
        $sql .= " ORDER BY gf.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT gf.*, 
                       t.name as tour_name,
                       b.customer_name as booking_customer,
                       b.start_date as booking_start_date,
                       h.HoTen as guide_name
                FROM {$this->table} gf
                LEFT JOIN tours t ON t.id = gf.tour_id
                LEFT JOIN bookings b ON b.id = gf.booking_id
                LEFT JOIN hdv h ON h.HDV_ID = gf.guide_id
                WHERE gf.id = :id LIMIT 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = ['status', 'title', 'content', 'rating', 'suggestions'];
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = :id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            return $stmt->execute();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getFeedbackTypes(): array
    {
        return [
            'tour' => 'Tour',
            'hotel' => 'Khách sạn',
            'restaurant' => 'Nhà hàng',
            'vehicle' => 'Xe vận chuyển',
            'service' => 'Dịch vụ',
            'supplier' => 'Nhà cung cấp',
            'other' => 'Khác'
        ];
    }
}

