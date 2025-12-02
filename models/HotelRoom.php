<?php

class HotelRoom extends BaseModel
{
    protected $table = 'hotel_rooms';

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
                    hotel_name VARCHAR(255) NOT NULL,
                    room_number VARCHAR(50) NOT NULL,
                    room_type VARCHAR(100),
                    capacity INT DEFAULT 1,
                    price DECIMAL(10, 2) DEFAULT 0,
                    notes TEXT,
                    status VARCHAR(50) DEFAULT 'available',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
        }
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY hotel_name ASC, room_number ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getAvailable(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'available' ORDER BY hotel_name ASC, room_number ASC";
        $stmt = $this->pdo->query($sql);
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
            (hotel_name, room_number, room_type, capacity, price, notes, status)
            VALUES
            (:hotel_name, :room_number, :room_type, :capacity, :price, :notes, :status)
        ";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':hotel_name', $data['hotel_name'], PDO::PARAM_STR);
            $stmt->bindValue(':room_number', $data['room_number'], PDO::PARAM_STR);
            $stmt->bindValue(':room_type', $data['room_type'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':capacity', $data['capacity'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':price', $data['price'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(':notes', $data['notes'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'available', PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return (int) $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('HotelRoom::create error: ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE {$this->table}
            SET hotel_name = :hotel_name,
                room_number = :room_number,
                room_type = :room_type,
                capacity = :capacity,
                price = :price,
                notes = :notes,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':hotel_name', $data['hotel_name'], PDO::PARAM_STR);
            $stmt->bindValue(':room_number', $data['room_number'], PDO::PARAM_STR);
            $stmt->bindValue(':room_type', $data['room_type'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':capacity', $data['capacity'] ?? 1, PDO::PARAM_INT);
            $stmt->bindValue(':price', $data['price'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(':notes', $data['notes'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'available', PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('HotelRoom::update error: ' . $e->getMessage());
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
            error_log('HotelRoom::delete error: ' . $e->getMessage());
            return false;
        }
    }
}
