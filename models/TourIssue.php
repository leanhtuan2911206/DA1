<?php

class TourIssue extends BaseModel
{
    protected $table = 'tour_issues';

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
                    tour_id INT NOT NULL,
                    booking_id INT NULL,
                    issue_type VARCHAR(50) DEFAULT 'issue',
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    severity VARCHAR(20) DEFAULT 'medium',
                    status VARCHAR(20) DEFAULT 'open',
                    reported_by INT NULL,
                    resolved_at DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_tour_id (tour_id),
                    INDEX idx_booking_id (booking_id),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
        }
    }

    public function getByTourId(int $tourId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE tour_id = :tour_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':tour_id', $tourId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByBookingId(int $bookingId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE booking_id = :booking_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data)
    {
        $sql = "
            INSERT INTO {$this->table} 
            (tour_id, booking_id, issue_type, title, description, severity, status, reported_by)
            VALUES 
            (:tour_id, :booking_id, :issue_type, :title, :description, :severity, :status, :reported_by)
        ";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':tour_id', $data['tour_id'], PDO::PARAM_INT);
            $stmt->bindValue(':booking_id', $data['booking_id'] ?? null, $data['booking_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':issue_type', $data['issue_type'] ?? 'issue', PDO::PARAM_STR);
            $stmt->bindValue(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindValue(':description', $data['description'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':severity', $data['severity'] ?? 'medium', PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'open', PDO::PARAM_STR);
            $stmt->bindValue(':reported_by', $data['reported_by'] ?? null, $data['reported_by'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            
            if ($stmt->execute()) {
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
}

