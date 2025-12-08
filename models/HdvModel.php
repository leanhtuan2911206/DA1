<?php
class HdvModel extends BaseModel {
    
    public function __construct()
    {
        parent::__construct();
        $this->ensureTripActivityStatusTable();
    }
    
    private function ensureTripActivityStatusTable()
    {
        try {
            $this->pdo->query("SELECT 1 FROM trip_activity_status LIMIT 1");
        } catch (Throwable $e) {
            // Bảng chưa tồn tại, tạo mới
            $sql = "
                CREATE TABLE IF NOT EXISTS trip_activity_status (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    booking_id INT NOT NULL,
                    itinerary_id INT NOT NULL,
                    status VARCHAR(50) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_booking_itinerary (booking_id, itinerary_id),
                    INDEX idx_booking_id (booking_id),
                    INDEX idx_itinerary_id (itinerary_id),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $this->pdo->exec($sql);
        }
    }
    
    public function getMyAssignments($hdvId) {
        if ($hdvId <= 0) {
            return [];
        }
        
        try {
            $sql = "SELECT ta.*, b.customer_name, t.name as tour_name, b.total_people, b.start_date 
                    FROM tour_assignments ta
                    LEFT JOIN bookings b ON ta.booking_id = b.id
                    LEFT JOIN tours t ON b.tour_id = t.id
                    WHERE ta.HDV_ID = ? 
                    ORDER BY ta.assign_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$hdvId]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('HdvModel::getMyAssignments error: ' . $e->getMessage());
            return [];
        }
    }

    // 2. Lấy chi tiết đầy đủ của 1 chuyến đi
    public function getTripDetail($bookingId, $hdvId) {
        // A. Lấy thông tin chính (Bao gồm assign_date và end_date)
        $sql = "SELECT t.name as tour_name, b.start_date as departure_date, b.total_people as customer_count,
                       ta.meeting_point, ta.start_time, ta.notes as assign_notes,
                       ta.assign_date, ta.end_date,
                       b.customer_name as leader_name, b.customer_phone as leader_phone,
                       b.id as booking_code, t.id as tour_id, ta.HDV_ID
                FROM tour_assignments ta
                JOIN bookings b ON ta.booking_id = b.id
                JOIN tours t ON b.tour_id = t.id
                WHERE ta.booking_id = ? AND ta.HDV_ID = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bookingId, $hdvId]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        // B. Lấy các dữ liệu phụ (Dịch vụ)
        $stmt = $this->pdo->prepare("SELECT service_type as type, supplier_name as name, quantity as qty, status, master_vehicle_id FROM booking_services WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        $data['services'] = $stmt->fetchAll();
        
        // B1. Lấy thông tin nhà xe và tài xế từ dịch vụ vận chuyển
        $vehicleInfo = null;
        $transportService = null;
        foreach ($data['services'] as $service) {
            $serviceType = strtolower($service['type'] ?? '');
            // Tìm dịch vụ vận chuyển: vehicle, vận chuyển, transport, xe
            if ($serviceType === 'vehicle' || strpos($serviceType, 'vận chuyển') !== false || strpos($serviceType, 'transport') !== false || strpos($serviceType, 'xe') !== false) {
                $transportService = $service;
                $masterVehicleId = isset($service['master_vehicle_id']) ? (int)$service['master_vehicle_id'] : 0;
                if ($masterVehicleId > 0) {
                    // Lấy thông tin xe từ master_vehicles
                    $sqlVehicle = "SELECT name, driver_name, driver_phone, license_plate, capacity 
                                   FROM master_vehicles 
                                   WHERE id = ?";
                    $stmtVehicle = $this->pdo->prepare($sqlVehicle);
                    $stmtVehicle->execute([$masterVehicleId]);
                    $vehicleInfo = $stmtVehicle->fetch();
                    if ($vehicleInfo) {
                        $vehicleInfo['supplier_name'] = $service['name'] ?? '';
                        $vehicleInfo['quantity'] = $service['qty'] ?? 0;
                        $vehicleInfo['status'] = $service['status'] ?? '';
                    }
                } else {
                    // Nếu không có master_vehicle_id, vẫn lưu thông tin dịch vụ
                    $vehicleInfo = [
                        'supplier_name' => $service['name'] ?? '',
                        'quantity' => $service['qty'] ?? 0,
                        'status' => $service['status'] ?? ''
                    ];
                }
                break;
            }
        }
        $data['vehicle_info'] = $vehicleInfo;
        $data['transport_service'] = $transportService;

        $this->ensureTripActivityStatusTable();
        
        $tourId = isset($data['tour_id']) ? (int)$data['tour_id'] : 0;
        $itinerary = [];
        
        if ($tourId > 0) {
            try {
                // Lấy lịch trình theo booking_id để mỗi booking có lịch trình riêng
                // Ưu tiên lấy lịch trình riêng của booking (booking_id = ?)
                // Nếu không có, lấy lịch trình chung của tour (booking_id IS NULL)
                $sql = "SELECT * FROM tour_itineraries 
                        WHERE tour_id = ? AND booking_id = ?
                        ORDER BY day_number ASC, time_start ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tourId, $bookingId]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Nếu không có lịch trình riêng của booking, lấy lịch trình chung của tour
                if (empty($items)) {
                    $sql = "SELECT * FROM tour_itineraries 
                            WHERE tour_id = ? AND (booking_id IS NULL OR booking_id = 0)
                            ORDER BY day_number ASC, time_start ASC";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$tourId]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                // Xử lý và chuẩn hóa dữ liệu - loại bỏ duplicate và đảm bảo kiểu dữ liệu đúng
                $processedItems = [];
                $seenIds = []; // Để loại bỏ duplicate ID
                
                foreach ($items as $item) {
                    $itemId = isset($item['id']) ? (int)$item['id'] : 0;
                    
                    // Loại bỏ duplicate dựa trên ID (chỉ nếu ID > 0)
                    if ($itemId > 0 && isset($seenIds[$itemId])) {
                        continue;
                    }
                    if ($itemId > 0) {
                        $seenIds[$itemId] = true;
                    }
                    
                    // Chuẩn hóa dữ liệu
                    $item['id'] = $itemId;
                    $item['tour_id'] = isset($item['tour_id']) ? (int)$item['tour_id'] : 0;
                    
                    // Xử lý day_number - đảm bảo là integer và >= 1
                    $rawDayNum = $item['day_number'] ?? null;
                    $dayNum = 1; // Mặc định
                    if ($rawDayNum !== null) {
                        if (is_numeric($rawDayNum)) {
                            $dayNum = (int)$rawDayNum;
                        } elseif (is_string($rawDayNum)) {
                            $dayNum = (int)trim($rawDayNum);
                        } else {
                            $dayNum = (int)$rawDayNum;
                        }
                    }
                    $item['day_number'] = max(1, $dayNum); // Đảm bảo >= 1
                    
                    // Lấy status từ trip_activity_status
                    $item['current_status'] = 'pending';
                    if ($itemId > 0 && $bookingId > 0) {
                        try {
                            $statusSql = "SELECT status FROM trip_activity_status 
                                          WHERE itinerary_id = ? AND booking_id = ? 
                                          ORDER BY updated_at DESC, created_at DESC 
                                          LIMIT 1";
                            $statusStmt = $this->pdo->prepare($statusSql);
                            $statusStmt->execute([$itemId, $bookingId]);
                            $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);
                            if ($statusRow && !empty($statusRow['status'])) {
                                $item['current_status'] = $statusRow['status'];
                            }
                        } catch (Throwable $e) {
                            // Giữ nguyên 'pending' nếu lỗi
                        }
                    }
                    
                    $processedItems[] = $item;
                }
                
                $itinerary = $processedItems;
            } catch (Throwable $e) {
                error_log('HdvModel::getTripDetail - Error fetching itinerary: ' . $e->getMessage());
                $itinerary = [];
            }
        }
        
        $data['itinerary'] = $itinerary;

        // C. Lấy khách hàng (Ưu tiên lấy từ tour_guests nếu đã sync/tạo group)
        $stmt = $this->pdo->prepare("SELECT id FROM tour_groups WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        $group = $stmt->fetch();

        if ($group) {
            // Nếu đã có group -> Lấy từ tour_guests (có id và checkin_status)
            $sqlGuests = "SELECT id, full_name, gender, date_of_birth, phone as contact_phone, email, id_type, id_number, payment_status, checkin_status, special_requests 
                          FROM tour_guests 
                          WHERE group_id = ?";
            $stmt = $this->pdo->prepare($sqlGuests);
            $stmt->execute([$group['id']]);
            $guestList = $stmt->fetchAll();

            // Nếu tour_guests rỗng, lấy từ customers và tìm id trong tour_guests
            if (empty($guestList)) {
                $sqlCustomers = "SELECT full_name, gender, date_of_birth, contact_phone, email, id_type, id_number, payment_status, special_requests 
                                 FROM customers 
                                 WHERE booking_id = ?";
                $stmt = $this->pdo->prepare($sqlCustomers);
                $stmt->execute([$bookingId]);
                $customers = $stmt->fetchAll();
                
                // Tìm id từ tour_guests theo tên khách
                foreach ($customers as &$customer) {
                    $sqlFind = "SELECT id FROM tour_guests WHERE group_id = ? AND full_name = ? LIMIT 1";
                    $stmtFind = $this->pdo->prepare($sqlFind);
                    $stmtFind->execute([$group['id'], $customer['full_name']]);
                    $found = $stmtFind->fetch();
                    $customer['id'] = $found ? (int)$found['id'] : 0;
                    $customer['checkin_status'] = 'not_arrived';
                }
                unset($customer);
                $data['customer_list'] = $customers;
            } else {
                $data['customer_list'] = $guestList;
            }
        } else {
            // Chưa có group -> Lấy từ customers, không có id
            $sqlCustomers = "SELECT full_name, gender, date_of_birth, contact_phone, email, id_type, id_number, payment_status, special_requests 
                             FROM customers 
                             WHERE booking_id = ?";
            $stmt = $this->pdo->prepare($sqlCustomers);
            $stmt->execute([$bookingId]);
            $customers = $stmt->fetchAll();
            
            // Thêm id = 0 và checkin_status mặc định
            foreach ($customers as &$customer) {
                $customer['id'] = 0;
                $customer['checkin_status'] = 'not_arrived';
            }
            unset($customer);
            $data['customer_list'] = $customers;
        }

        return $data;
    }

    // [MỚI] Hàm cập nhật trạng thái hoạt động (Check-in)
    public function updateActivityStatus($bookingId, $itineraryId, $status) {
        $this->ensureTripActivityStatusTable();
        
        // Dùng INSERT ... ON DUPLICATE KEY UPDATE để nếu chưa có thì thêm, có rồi thì sửa
        $sql = "INSERT INTO trip_activity_status (booking_id, itinerary_id, status) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE status = VALUES(status)";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$bookingId, $itineraryId, $status]);
        } catch (Throwable $e) {
            error_log('HdvModel::updateActivityStatus error: ' . $e->getMessage());
            return false;
        }
    }
    public function getGuideIdByUserId($userId) {
        if ($userId <= 0) {
            return 0;
        }
        
        try {
            // Ưu tiên 1: Tìm theo user_id
            if ($this->columnExists('hdv', 'user_id')) {
                $sql = "SELECT HDV_ID FROM hdv WHERE user_id = ? LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                if ($result && isset($result['HDV_ID'])) {
                    return (int)$result['HDV_ID'];
                }
            }
            
            // Ưu tiên 2: Nếu không tìm thấy, tìm theo email trong bảng users
            try {
                $userSql = "SELECT email, name FROM users WHERE id = ? LIMIT 1";
                $userStmt = $this->pdo->prepare($userSql);
                $userStmt->execute([$userId]);
                $userData = $userStmt->fetch();
                
                if ($userData) {
                    // Tìm theo LienHe (contact) - vì email đăng nhập có thể là contact
                    if (!empty($userData['email'])) {
                        $contactSql = "SELECT HDV_ID FROM hdv WHERE LienHe = ? LIMIT 1";
                        $contactStmt = $this->pdo->prepare($contactSql);
                        $contactStmt->execute([$userData['email']]);
                        $contactResult = $contactStmt->fetch();
                        if ($contactResult && isset($contactResult['HDV_ID'])) {
                            $guideId = (int)$contactResult['HDV_ID'];
                            // Cập nhật user_id vào bảng hdv để lần sau tìm nhanh hơn
                            if ($this->columnExists('hdv', 'user_id')) {
                                $updateSql = "UPDATE hdv SET user_id = ? WHERE HDV_ID = ?";
                                $updateStmt = $this->pdo->prepare($updateSql);
                                $updateStmt->execute([$userId, $guideId]);
                            }
                            return $guideId;
                        }
                    }
                    
                    // Tìm theo tên (HoTen)
                    if (!empty($userData['name'])) {
                        $nameSql = "SELECT HDV_ID FROM hdv WHERE HoTen = ? LIMIT 1";
                        $nameStmt = $this->pdo->prepare($nameSql);
                        $nameStmt->execute([$userData['name']]);
                        $nameResult = $nameStmt->fetch();
                        if ($nameResult && isset($nameResult['HDV_ID'])) {
                            $guideId = (int)$nameResult['HDV_ID'];
                            // Cập nhật user_id vào bảng hdv để lần sau tìm nhanh hơn
                            if ($this->columnExists('hdv', 'user_id')) {
                                $updateSql = "UPDATE hdv SET user_id = ? WHERE HDV_ID = ?";
                                $updateStmt = $this->pdo->prepare($updateSql);
                                $updateStmt->execute([$userId, $guideId]);
                            }
                            return $guideId;
                        }
                    }
                }
            } catch (Throwable $e) {
                // Silent fail
            }
            
            return 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
    
    
    // Hàm cập nhật lịch trình (cho phép HDV chỉnh sửa)
    public function updateItinerary($itineraryId, $timeStart, $title, $description, $location) {
        $sql = "UPDATE tour_itineraries 
                SET time_start = ?, 
                    title = ?, 
                    description = ?, 
                    location = ?,
                    updated_at = NOW()
                WHERE id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$timeStart, $title, $description, $location, $itineraryId]);
        } catch (PDOException $e) {
            error_log('HdvModel::updateItinerary error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Hàm cập nhật trạng thái check-in của khách
    public function updateGuestCheckin($guestId, $bookingId, $checkinStatus) {
        // Kiểm tra xem guest có thuộc booking này không
        $sqlCheck = "SELECT tg.id 
                     FROM tour_guests tg
                     JOIN tour_groups tgr ON tg.group_id = tgr.id
                     WHERE tg.id = ? AND tgr.booking_id = ?";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute([$guestId, $bookingId]);
        $guest = $stmtCheck->fetch();
        
        if (!$guest) {
            return false; // Không tìm thấy guest hoặc không thuộc booking này
        }
        
        // Cập nhật trạng thái check-in
        $sql = "UPDATE tour_guests 
                SET checkin_status = ?, 
                    updated_at = NOW()
                WHERE id = ?";
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$checkinStatus, $guestId]);
        } catch (PDOException $e) {
            error_log('HdvModel::updateGuestCheckin error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
