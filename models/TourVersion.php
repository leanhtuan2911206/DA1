<?php
    class TourVersion extends BaseModel{
        protected $table='tour_versions';
        public function __construct()
        {
          parent::__construct();
          $this->createTable();
        }

        private function createTable(){
            try {
            $this->pdo->query("SELECT 1 FROM {$this->table} LIMIT 1");
        } catch (Throwable $e) {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tour_id INT NOT NULL,
                version_type ENUM('seasonal', 'promotional', 'special') NOT NULL DEFAULT 'seasonal',
                name VARCHAR(255) NOT NULL,
                price DECIMAL(15, 2) DEFAULT NULL,
                itinerary TEXT DEFAULT NULL,
                services TEXT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                start_date DATE DEFAULT NULL,
                end_date DATE DEFAULT NULL,
                status VARCHAR(50) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_tour_id (tour_id),
                INDEX idx_dates (start_date, end_date),
                FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->pdo->exec($sql);
        }
        }
        // lấy tất cả phiên bản tour
        public function getByTourId($tourId){
            $sql= "SELECT * FROM {$this->table} WHERE tour_id = ? ORDER BY created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tourId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // lấy 1 phiên bản theo ID
        public function find($id){
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        // Tạo mới một phiên bản
        public function create($data)  {
             $sql = "INSERT INTO {$this->table} 
                (tour_id, version_type, name, price, itinerary, services, description, start_date, end_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['tour_id'],
                $data['version_type'] ?? 'seasonal',
                $data['name'],
                $data['price'] ?? null,
                $data['itinerary'] ?? null,
                $data['services'] ?? null,
                $data['description'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['status'] ?? 'active',
            ]);
            return $result ? (int)$this->pdo->lastInsertId(): false;
        }
        // Cập nhật phiên bản 
        public function update($id, $data)  {
            $sql ="UPDATE {$this->table} SET 
                version_type = ?, name = ?, price = ?, itinerary = ?, services = ?, 
                description = ?, start_date = ?, end_date = ?, status = ?, updated_at = NOW()
                WHERE id = ?";
               
             $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $data['version_type'] ?? 'seasonal',
                    $data['name'],
                    $data['price'] ?? null,
                    $data['itinerary'] ?? null,
                    $data['services'] ?? null,
                    $data['description'] ?? null,
                    $data['start_date'] ?? null,
                    $data['end_date'] ?? null,
                    $data['status'] ?? 'active',
                    $id,
                ]);
        }
        // Xóa phiên bản
        public function delete($id) {
            $sql="DELETE FROM {$this->table} WHERE id=?";
            $stmt= $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        }
        // Lấy phiên bản tour áp dụng ngày cụ thể
         public function getActiveVersionByDate($tourId, $date)
        {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE tour_id = ? AND status = 'active'
                    AND (
                        (start_date IS NULL AND end_date IS NULL) OR
                        (start_date IS NULL AND end_date >= ?) OR
                        (start_date <= ? AND end_date IS NULL) OR
                        (? BETWEEN start_date AND end_date)
                    )
                    ORDER BY 
                        CASE version_type 
                            WHEN 'promotional' THEN 1
                            WHEN 'special' THEN 2
                            WHEN 'seasonal' THEN 3
                        END
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tourId, $date, $date, $date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : null;
        }

    }
?>