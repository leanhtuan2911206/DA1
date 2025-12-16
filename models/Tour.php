<?php

class Tour extends BaseModel
{
    protected $table = 'tours';
    public $lastError = null;

    public function countAll()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function listDashboard($limit = 10)
    {
        $sql = "
            SELECT 
                t.id,
                t.name,
                t.price,
                t.itinerary AS place,
                t.policy,
                t.created_at,
                tc.name AS type,
                COALESCE(t.tour_status, 'Hoạt động') AS status
            FROM {$this->table} AS t
            LEFT JOIN tour_categories AS tc ON tc.id = t.category_id
            ORDER BY t.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $name, $category_id, $price, $description = null, $itinerary = null, $policy = null, $image = null, $status = null)
    {
        // Kiểm tra tour có tồn tại không
        $existing = $this->find($id);
        if (!$existing) {
            $this->lastError = ['error' => 'Tour không tồn tại', 'id' => $id];
            return false;
        }
        
        // Kiểm tra category_id có tồn tại không
        try {
            $checkCat = $this->pdo->prepare("SELECT id FROM tour_categories WHERE id = ?");
            $checkCat->execute([$category_id]);
            if (!$checkCat->fetch()) {
                $this->lastError = ['error' => 'Category không tồn tại', 'category_id' => $category_id];
                return false;
            }
        } catch (Throwable $e) {
        }
        
        // Kiểm tra các cột có tồn tại không
        $hasTourStatus = $this->columnExists($this->table, 'tour_status');
        $hasUpdatedAt = $this->columnExists($this->table, 'updated_at');
        
        // Xây dựng SQL query động
        $sets = [
            'name = ?',
            'category_id = ?',
            'price = ?',
            'description = ?',
            'itinerary = ?',
            'policy = ?'
        ];
        $params = [$name, $category_id, $price, $description, $itinerary, $policy];
        
        // Xử lý image: chỉ update nếu có giá trị mới
        if ($image !== null) {
            $sets[] = 'image = ?';
            $params[] = $image;
        }
        
        if ($hasTourStatus) {
            if ($status !== null) {
                $sets[] = 'tour_status = ?';
                $params[] = $status;
            }
        }
        
        if ($hasUpdatedAt) {
            $sets[] = 'updated_at = NOW()';
        }
        
        $params[] = $id; // Thêm id vào cuối cho WHERE clause
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $res = $stmt->execute($params);
            if ($res) {
                return true;
            }
            
            $errorInfo = $stmt->errorInfo();
            $this->lastError = [
                'errorInfo' => $errorInfo,
                'sqlState' => isset($errorInfo[0]) ? $errorInfo[0] : null,
                'errorCode' => isset($errorInfo[1]) ? $errorInfo[1] : null,
                'errorMessage' => isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown error',
                'sql' => $sql,
                'params' => [
                    'id' => $id,
                    'name' => $name,
                    'category_id' => $category_id,
                    'price' => $price,
                    'image' => $image,
                    'status' => $status,
                    'hasTourStatus' => $hasTourStatus,
                    'hasUpdatedAt' => $hasUpdatedAt
                ]
            ];
            return false;
        } catch (PDOException $e) {
            $this->lastError = [
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $sql,
                'params' => [
                    'id' => $id,
                    'name' => $name,
                    'category_id' => $category_id,
                    'price' => $price,
                    'image' => $image,
                    'status' => $status,
                    'hasTourStatus' => $hasTourStatus,
                    'hasUpdatedAt' => $hasUpdatedAt
                ]
            ];
            return false;
        }
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function insert($name, $category_id, $price = 0, $description = null, $itinerary = null, $policy = null, $image = null, $status = null)
    {
        // Kiểm tra category_id có tồn tại không
        try {
            $checkCat = $this->pdo->prepare("SELECT id FROM tour_categories WHERE id = ?");
            $checkCat->execute([$category_id]);
            if (!$checkCat->fetch()) {
                $this->lastError = ['error' => 'Category không tồn tại', 'category_id' => $category_id];
                return false;
            }
        } catch (Throwable $e) {
        }
        
        // Kiểm tra xem bảng có các cột không
        $hasTourStatus = $this->columnExists($this->table, 'tour_status');
        $hasCreatedAt = $this->columnExists($this->table, 'created_at');
        
        // Xây dựng SQL query dựa trên các cột có sẵn
        $columns = ['name', 'category_id', 'price', 'description', 'itinerary', 'policy', 'image'];
        $placeholders = ['?', '?', '?', '?', '?', '?', '?'];
        $params = [$name, $category_id, $price, $description, $itinerary, $policy, $image];
        
        if ($hasTourStatus) {
            $columns[] = 'tour_status';
            $placeholders[] = '?';
            $params[] = $status;
        }
        
        if ($hasCreatedAt) {
            $columns[] = 'created_at';
            $placeholders[] = 'NOW()';
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $res = $stmt->execute($params);
            if ($res) {
                return (int) $this->pdo->lastInsertId();
            }

            $errorInfo = $stmt->errorInfo();
            $this->lastError = [
                'errorInfo' => $errorInfo,
                'sqlState' => isset($errorInfo[0]) ? $errorInfo[0] : null,
                'errorCode' => isset($errorInfo[1]) ? $errorInfo[1] : null,
                'errorMessage' => isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown error',
                'sql' => $sql,
                'params' => [
                    'name' => $name,
                    'category_id' => $category_id,
                    'price' => $price,
                    'image' => $image,
                    'status' => $status,
                    'hasTourStatus' => $hasTourStatus,
                    'hasCreatedAt' => $hasCreatedAt
                ]
            ];
            return false;
        } catch (PDOException $e) {
            $this->lastError = [
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql' => $sql,
                'params' => [
                    'name' => $name,
                    'category_id' => $category_id,
                    'price' => $price,
                    'image' => $image,
                    'status' => $status,
                    'hasTourStatus' => $hasTourStatus,
                    'hasCreatedAt' => $hasCreatedAt
                ]
            ];
            return false;
        }
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Resequence primary key ids so they become contiguous starting at 1.
     * WARNING: This rewrites primary keys and will break foreign keys if other tables reference `tours.id`.
     * Use only if there are no FK dependencies.
     * @return bool
     */
    public function resequenceIds()
    {
        try {
            $this->pdo->beginTransaction();
            // reset user variable then update ids in order
            $this->pdo->exec("SET @i = 0");
            $this->pdo->exec("UPDATE {$this->table} SET id = (@i := @i + 1) ORDER BY id");
            // reset auto-increment to next value
            $this->pdo->exec("ALTER TABLE {$this->table} AUTO_INCREMENT = 1");
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            try { $this->pdo->rollBack(); } catch (Throwable $_) {}
            return false;
        }
    }

    /**
     * Lấy danh sách tour phục vụ trang quản lý cùng bộ lọc cơ bản.
     */
    public function listWithCategory(array $filters = [])
    {
        $sql = "
            SELECT 
                t.*,
                tc.name AS category_name,
                COALESCE(t.tour_status, 'Hoạt động') AS status
            FROM {$this->table} AS t
            LEFT JOIN tour_categories AS tc ON tc.id = t.category_id
        ";

        $conditions = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $conditions[] = "(t.name LIKE :keyword OR t.description LIKE :keyword)";
            $params[':keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $conditions[] = "t.category_id = :category_id";
            $params[':category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['destination'])) {
            $conditions[] = "(t.itinerary LIKE :destination OR t.policy LIKE :destination)";
            $params[':destination'] = '%' . $filters['destination'] . '%';
        }

        if (!empty($filters['tour_status'])) {
            $conditions[] = "t.tour_status = :tour_status";
            $params[':tour_status'] = $filters['tour_status'];
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $order = ' ORDER BY t.created_at DESC';
        if (($filters['price_order'] ?? '') === 'asc') {
            $order = ' ORDER BY t.price ASC';
        } elseif (($filters['price_order'] ?? '') === 'desc') {
            $order = ' ORDER BY t.price DESC';
        }

        $sql .= $order;

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getItineraryByTourId($tourId, $bookingId = null)
    {
        try {
            $exists = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_itineraries'")->fetchColumn() > 0;
            if (!$exists) { return []; }
            
            // Nếu có booking_id, chỉ lấy lịch trình riêng của booking
            // TUYỆT ĐỐI KHÔNG fallback về lịch trình chung nếu booking đó chưa có lịch trình
            // Để đảm bảo khi tạo booking mới cho tour không phải mẫu, nó sẽ rỗng
            if ($bookingId !== null && $bookingId > 0) {
                $sql = "SELECT * FROM tour_itineraries 
                        WHERE tour_id = ? AND (booking_id = ? OR booking_id = CAST(? AS UNSIGNED) OR CAST(COALESCE(booking_id, 0) AS UNSIGNED) = ?)
                        ORDER BY CAST(day_number AS UNSIGNED) ASC, id ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tourId, $bookingId, $bookingId, $bookingId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Trả về kết quả (có thể rỗng nếu chưa thêm lịch trình nào)
                return $results;
            } else {
                // Lấy lịch trình chung của tour (booking_id IS NULL hoặc = 0)
                // Dùng cho trường hợp xem chi tiết tour mẫu, hoặc tour gốc
                $sql = "SELECT * FROM tour_itineraries 
                        WHERE tour_id = ? AND (booking_id IS NULL OR CAST(booking_id AS UNSIGNED) = 0)
                        ORDER BY CAST(day_number AS UNSIGNED) ASC, id ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tourId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            return [];
        }
    }
    // Mở file models/Tour.php và thêm các phương thức sau vào trong class Tour:

    // Lấy danh sách item từ mẫu (template) dựa trên template_id
    public function getTemplateItems($templateId)
    {
        $sql = "SELECT * FROM template_itineraries WHERE template_id = ? ORDER BY CAST(day_number AS UNSIGNED) ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$templateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Thêm mới một dòng lịch trình cho Tour (có thể gắn với booking_id để mỗi booking có lịch trình riêng)
    public function insertItinerary($tour_id, $day_number, $time_start, $title, $description, $location, $booking_id = null)
    {
        try {
            $exists = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_itineraries'")->fetchColumn() > 0;
            if (!$exists) {
                $createTableSql = "CREATE TABLE IF NOT EXISTS tour_itineraries (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tour_id INT NOT NULL,
                    booking_id INT NULL,
                    day_number INT NOT NULL DEFAULT 1,
                    time_start VARCHAR(50) DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT DEFAULT NULL,
                    location VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_tour_id (tour_id),
                    INDEX idx_booking_id (booking_id),
                    INDEX idx_day_number (day_number),
                    FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
                    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $this->pdo->exec($createTableSql);
            } else {
                // Kiểm tra và thêm cột booking_id nếu chưa có
                $hasBookingId = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tour_itineraries' AND column_name = 'booking_id'")->fetchColumn() > 0;
                if (!$hasBookingId) {
                    try {
                        $this->pdo->exec("ALTER TABLE tour_itineraries ADD COLUMN booking_id INT NULL AFTER tour_id");
                        $this->pdo->exec("ALTER TABLE tour_itineraries ADD INDEX idx_booking_id (booking_id)");
                        $this->pdo->exec("ALTER TABLE tour_itineraries ADD FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE");
                    } catch (Throwable $e) {
                    }
                }
            }
        } catch (Throwable $e) {
        }
        
        // Đảm bảo booking_id là null hoặc integer > 0
        $bookingIdValue = null;
        if ($booking_id !== null && $booking_id > 0) {
            $bookingIdValue = (int)$booking_id;
        }
        
        
        $sql = "INSERT INTO tour_itineraries (tour_id, booking_id, day_number, time_start, title, description, location, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        try {
            $stmt = $this->pdo->prepare($sql);
            // Bind parameters với đúng kiểu dữ liệu
            $stmt->bindValue(1, (int)$tour_id, PDO::PARAM_INT);
            if ($bookingIdValue !== null) {
                $stmt->bindValue(2, $bookingIdValue, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(2, null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(3, (int)$day_number, PDO::PARAM_INT);
            $stmt->bindValue(4, $time_start ?: null, PDO::PARAM_STR);
            $stmt->bindValue(5, $title, PDO::PARAM_STR);
            $stmt->bindValue(6, $description ?: null, PDO::PARAM_STR);
            $stmt->bindValue(7, $location ?: null, PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Cập nhật một dòng lịch trình
    public function updateItinerary($id, $day_number, $time_start, $title, $description, $location)
    {
        $sql = "UPDATE tour_itineraries 
                SET day_number = ?, 
                    time_start = ?, 
                    title = ?, 
                    description = ?, 
                    location = ?
                WHERE id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$day_number, $time_start, $title, $description, $location, $id]);
            if (!$result) {
                $this->lastError = implode(" ", $stmt->errorInfo());
            }
            return $result;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Lấy chi tiết một dòng lịch trình theo ID
    public function getItineraryById($id)
    {
        $sql = "SELECT * FROM tour_itineraries WHERE id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Xóa một dòng lịch trình
    public function deleteItinerary($id)
    {
        $sql = "DELETE FROM tour_itineraries WHERE id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    // Tìm lịch trình trùng lặp (kiểm tra cả booking_id nếu có)
    public function findItineraryByDetails($tour_id, $day_number, $time_start, $title, $booking_id = null)
    {
        if ($booking_id !== null && $booking_id > 0) {
            $sql = "SELECT id FROM tour_itineraries 
                    WHERE tour_id = ? AND booking_id = ? AND day_number = ? AND time_start = ? AND title = ? 
                    LIMIT 1";
            $params = [$tour_id, $booking_id, $day_number, $time_start, $title];
        } else {
            $sql = "SELECT id FROM tour_itineraries 
                    WHERE tour_id = ? AND booking_id IS NULL AND day_number = ? AND time_start = ? AND title = ? 
                    LIMIT 1";
            $params = [$tour_id, $day_number, $time_start, $title];
        }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Lấy danh sách hướng dẫn viên đã được phân công cho tour
     * @param int $tourId ID của tour
     * @return array Danh sách HDV với thông tin phân công
     */
    public function getAssignedGuides($tourId)
    {
        if ($tourId <= 0) {
            return [];
        }

        try {
            // Kiểm tra bảng tour_assignments và bookings có tồn tại không
            $hasAssignments = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
            $hasBookings = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
            
            if (!$hasAssignments || !$hasBookings) {
                return [];
            }

            $sql = "SELECT 
                        ta.id AS assignment_id,
                        ta.booking_id,
                        ta.HDV_ID,
                        ta.assign_date,
                        ta.end_date,
                        ta.meeting_point,
                        ta.start_time,
                        ta.end_time,
                        ta.notes,
                        h.HoTen AS guide_name,
                        h.LienHe AS guide_contact,
                        h.NgonNgu AS guide_languages,
                        b.start_date AS booking_start_date,
                        b.customer_name,
                        b.total_people
                    FROM tour_assignments ta
                    INNER JOIN bookings b ON b.id = ta.booking_id
                    INNER JOIN hdv h ON h.HDV_ID = ta.HDV_ID
                    WHERE b.tour_id = ?
                    ORDER BY ta.assign_date DESC, ta.id DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tourId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Lấy danh sách booking của tour đã được phân công cho HDV
     * @param int $tourId ID của tour
     * @return array Danh sách booking với thông tin phân công
     */
    public function getAssignedBookings($tourId)
    {
        if ($tourId <= 0) {
            return [];
        }

        try {
            $hasAssignments = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
            $hasBookings = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
            
            if (!$hasAssignments || !$hasBookings) {
                return [];
            }

            $sql = "SELECT 
                        b.id AS booking_id,
                        b.start_date,
                        b.customer_name,
                        b.total_people,
                        ta.id AS assignment_id,
                        ta.HDV_ID,
                        ta.assign_date,
                        ta.end_date,
                        ta.meeting_point,
                        ta.start_time,
                        ta.end_time,
                        h.HoTen AS guide_name
                    FROM bookings b
                    INNER JOIN tour_assignments ta ON ta.booking_id = b.id
                    INNER JOIN hdv h ON h.HDV_ID = ta.HDV_ID
                    WHERE b.tour_id = ?
                    ORDER BY b.start_date DESC, ta.assign_date DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tourId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Kiểm tra tour có được phân công cho HDV nào chưa
     * @param int $tourId ID của tour
     * @return bool true nếu đã được phân công, false nếu chưa
     */
    public function hasAssignment($tourId)
    {
        if ($tourId <= 0) {
            return false;
        }

        try {
            $hasAssignments = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
            $hasBookings = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
            
            if (!$hasAssignments || !$hasBookings) {
                return false;
            }

            $sql = "SELECT COUNT(*) 
                    FROM tour_assignments ta
                    INNER JOIN bookings b ON b.id = ta.booking_id
                    WHERE b.tour_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tourId]);
            $count = (int)$stmt->fetchColumn();
            return $count > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Lấy số lượng booking của tour đã được phân công
     * @param int $tourId ID của tour
     * @return int Số lượng booking đã được phân công
     */
    public function getAssignedBookingCount($tourId)
    {
        if ($tourId <= 0) {
            return 0;
        }

        try {
            $hasAssignments = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
            $hasBookings = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
            
            if (!$hasAssignments || !$hasBookings) {
                return 0;
            }

            $sql = "SELECT COUNT(DISTINCT ta.booking_id) 
                    FROM tour_assignments ta
                    INNER JOIN bookings b ON b.id = ta.booking_id
                    WHERE b.tour_id = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tourId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Lấy danh sách booking của tour chưa được phân công
     * @param int $tourId ID của tour
     * @return array Danh sách booking chưa được phân công
     */
    public function getUnassignedBookings($tourId)
    {
        if ($tourId <= 0) {
            return [];
        }

        try {
            $hasAssignments = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
            $hasBookings = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
            
            if (!$hasBookings) {
                return [];
            }

            if ($hasAssignments) {
                // Lấy các booking chưa có trong tour_assignments
                $sql = "SELECT b.*
                        FROM bookings b
                        WHERE b.tour_id = ?
                        AND b.id NOT IN (SELECT booking_id FROM tour_assignments WHERE booking_id IS NOT NULL)
                        ORDER BY b.start_date DESC, b.created_at DESC";
            } else {
                // Nếu chưa có bảng tour_assignments, trả về tất cả booking của tour
                $sql = "SELECT b.*
                        FROM bookings b
                        WHERE b.tour_id = ?
                        ORDER BY b.start_date DESC, b.created_at DESC";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tourId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}

