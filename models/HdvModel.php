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
    
    // 1. Lấy danh sách tour được phân công
    public function getMyAssignments($hdvId) {
        if ($hdvId <= 0) {
            error_log('HdvModel::getMyAssignments - Invalid HDV_ID: ' . $hdvId);
            return [];
        }
        
        try {
            // Log để debug
            error_log('HdvModel::getMyAssignments - Searching for HDV_ID: ' . $hdvId);
            
            // Kiểm tra xem có phân bổ nào với HDV_ID này không
            $checkSql = "SELECT COUNT(*) as cnt FROM tour_assignments WHERE HDV_ID = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$hdvId]);
            $checkResult = $checkStmt->fetch();
            error_log('HdvModel::getMyAssignments - Found ' . ($checkResult['cnt'] ?? 0) . ' assignments for HDV_ID: ' . $hdvId);
            
            $sql = "SELECT ta.*, b.customer_name, t.name as tour_name, b.total_people, b.start_date 
                    FROM tour_assignments ta
                    LEFT JOIN bookings b ON ta.booking_id = b.id
                    LEFT JOIN tours t ON b.tour_id = t.id
                    WHERE ta.HDV_ID = ? 
                    ORDER BY ta.assign_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$hdvId]);
            $results = $stmt->fetchAll();
            
            error_log('HdvModel::getMyAssignments - Returning ' . count($results) . ' assignments');
            return $results;
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
            error_log('HdvModel::getTripDetail - No data found for booking_id: ' . $bookingId . ', hdvId: ' . $hdvId);
            return null;
        }

        // Log để debug
        error_log('HdvModel::getTripDetail - Found trip detail. tour_id: ' . ($data['tour_id'] ?? 'NULL') . ', booking_id: ' . $bookingId);
        
        // Kiểm tra xem booking có tour_id đúng không
        try {
            $bookingCheckSql = "SELECT tour_id FROM bookings WHERE id = ?";
            $bookingCheckStmt = $this->pdo->prepare($bookingCheckSql);
            $bookingCheckStmt->execute([$bookingId]);
            $bookingTourId = $bookingCheckStmt->fetchColumn();
            error_log('HdvModel::getTripDetail - Booking tour_id from bookings table: ' . ($bookingTourId ?? 'NULL'));
            
            // Kiểm tra xem có lịch trình nào trong database không (không filter theo tour_id)
            $allItinerarySql = "SELECT tour_id, day_number, COUNT(*) as cnt FROM tour_itineraries GROUP BY tour_id, day_number";
            $allItineraryStmt = $this->pdo->query($allItinerarySql);
            $allItineraryResults = $allItineraryStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('HdvModel::getTripDetail - All itinerary in DB (by tour_id and day): ' . json_encode($allItineraryResults));
        } catch (Throwable $e) {
            error_log('HdvModel::getTripDetail - Error checking booking: ' . $e->getMessage());
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

        // [QUAN TRỌNG] Lấy lịch trình KÈM THEO TRẠNG THÁI
        // Đảm bảo bảng tồn tại trước khi query
        $this->ensureTripActivityStatusTable();
        
        $tourId = isset($data['tour_id']) ? (int)$data['tour_id'] : 0;
        error_log('HdvModel::getTripDetail - Fetching itinerary for tour_id: ' . $tourId . ', booking_id: ' . $bookingId);
        
        // Kiểm tra xem có bao nhiêu lịch trình trong database cho tour này
        if ($tourId > 0) {
            try {
                // Kiểm tra tổng số lịch trình
                $countSql = "SELECT COUNT(*) as total FROM tour_itineraries WHERE tour_id = ?";
                $countStmt = $this->pdo->prepare($countSql);
                $countStmt->execute([$tourId]);
                $totalCount = $countStmt->fetch()['total'] ?? 0;
                error_log('HdvModel::getTripDetail - Total itinerary in DB for tour_id ' . $tourId . ': ' . $totalCount);
                
                // Kiểm tra theo từng ngày
                $checkSql = "SELECT day_number, COUNT(*) as cnt FROM tour_itineraries WHERE tour_id = ? GROUP BY day_number ORDER BY day_number";
                $checkStmt = $this->pdo->prepare($checkSql);
                $checkStmt->execute([$tourId]);
                $checkResults = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
                $dayCounts = [];
                foreach ($checkResults as $row) {
                    $dayCounts[(int)$row['day_number']] = (int)$row['cnt'];
                }
                error_log('HdvModel::getTripDetail - Itinerary in DB by day: ' . json_encode($dayCounts));
                
                // Kiểm tra chi tiết lịch trình ngày 2
                $day2Sql = "SELECT id, day_number, time_start, title FROM tour_itineraries WHERE tour_id = ? AND day_number = 2";
                $day2Stmt = $this->pdo->prepare($day2Sql);
                $day2Stmt->execute([$tourId]);
                $day2Results = $day2Stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('HdvModel::getTripDetail - Day 2 items in DB for tour_id ' . $tourId . ': ' . count($day2Results));
                foreach ($day2Results as $day2Item) {
                    error_log('HdvModel::getTripDetail - Day 2 item: id=' . ($day2Item['id'] ?? 'NULL') . ', time=' . ($day2Item['time_start'] ?? 'NULL') . ', title=' . substr($day2Item['title'] ?? '', 0, 30));
                }
                
                // Kiểm tra xem có lịch trình ngày 2 cho tour_id khác không
                $allDay2Sql = "SELECT tour_id, COUNT(*) as cnt FROM tour_itineraries WHERE day_number = 2 GROUP BY tour_id";
                $allDay2Stmt = $this->pdo->query($allDay2Sql);
                $allDay2Results = $allDay2Stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($allDay2Results)) {
                    error_log('HdvModel::getTripDetail - Day 2 exists for other tour_ids: ' . json_encode($allDay2Results));
                }
            } catch (Throwable $e) {
                error_log('HdvModel::getTripDetail - Error checking DB: ' . $e->getMessage());
            }
        }
        
        $rawItinerary = [];
        
        if ($tourId <= 0) {
            error_log('HdvModel::getTripDetail - Invalid tour_id: ' . $tourId);
        } else {
            // Lấy lịch trình từ tour_itineraries - DÙNG CÙNG QUERY NHƯ ADMIN
            // Query giống hệt Tour::getItineraryByTourId() để đảm bảo nhất quán
            try {
                // Kiểm tra bảng tồn tại
                $exists = (int)$this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_itineraries'")->fetchColumn() > 0;
                if (!$exists) {
                    $rawItinerary = [];
                    error_log('HdvModel::getTripDetail - Table tour_itineraries does not exist');
                } else {
                    // Query giống hệt Tour::getItineraryByTourId()
                    $sqlItinerary = "SELECT * FROM tour_itineraries WHERE tour_id = ? ORDER BY day_number ASC, time_start ASC";
                    $stmt = $this->pdo->prepare($sqlItinerary);
                    $stmt->execute([$tourId]);
                    $rawItinerary = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log('HdvModel::getTripDetail - Query found ' . count($rawItinerary) . ' items');
                    
                    // Log chi tiết các day_number
                    $dayNumbers = [];
                    foreach ($rawItinerary as $item) {
                        $dayNum = (int)($item['day_number'] ?? 0);
                        if ($dayNum > 0) {
                            $dayNumbers[$dayNum] = ($dayNumbers[$dayNum] ?? 0) + 1;
                        }
                    }
                    error_log('HdvModel::getTripDetail - Day numbers in result: ' . json_encode($dayNumbers));
                    
                    // TỰ ĐỘNG KIỂM TRA VÀ SỬA: Nếu database có ngày 2 nhưng query không lấy được
                    if (isset($dayCounts) && isset($dayCounts[2]) && $dayCounts[2] > 0 && !isset($dayNumbers[2])) {
                        error_log('HdvModel::getTripDetail - AUTO-FIX: Day 2 exists in DB (' . $dayCounts[2] . ' items) but not in query result for tour_id ' . $tourId);
                        
                        // Thử query lại với điều kiện rõ ràng hơn
                        $day2CheckSql = "SELECT * FROM tour_itineraries WHERE tour_id = ? AND day_number = 2 ORDER BY time_start ASC";
                        $day2CheckStmt = $this->pdo->prepare($day2CheckSql);
                        $day2CheckStmt->execute([$tourId]);
                        $day2Items = $day2CheckStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($day2Items)) {
                            error_log('HdvModel::getTripDetail - AUTO-FIX: Found ' . count($day2Items) . ' day 2 items with direct query, adding to result');
                            // Thêm vào kết quả
                            foreach ($day2Items as $day2Item) {
                                $day2Item['current_status'] = 'pending';
                                $rawItinerary[] = $day2Item;
                            }
                            // Sắp xếp lại
                            usort($rawItinerary, function($a, $b) {
                                $dayA = (int)($a['day_number'] ?? 0);
                                $dayB = (int)($b['day_number'] ?? 0);
                                if ($dayA !== $dayB) return $dayA <=> $dayB;
                                $timeA = $a['time_start'] ?? '';
                                $timeB = $b['time_start'] ?? '';
                                return strcmp($timeA, $timeB);
                            });
                            error_log('HdvModel::getTripDetail - AUTO-FIX: After adding day 2, total items: ' . count($rawItinerary));
                            
                            // Cập nhật lại dayNumbers
                            $dayNumbers = [];
                            foreach ($rawItinerary as $item) {
                                $dayNum = (int)($item['day_number'] ?? 0);
                                if ($dayNum > 0) {
                                    $dayNumbers[$dayNum] = ($dayNumbers[$dayNum] ?? 0) + 1;
                                }
                            }
                            error_log('HdvModel::getTripDetail - AUTO-FIX: Updated day numbers: ' . json_encode($dayNumbers));
                        } else {
                            error_log('HdvModel::getTripDetail - AUTO-FIX: Direct query also found no day 2 items for tour_id ' . $tourId);
                        }
                    }
                    
                    // Sau đó lấy status từ trip_activity_status cho từng item
                    foreach ($rawItinerary as &$item) {
                        $itemId = isset($item['id']) ? (int)$item['id'] : 0;
                        $item['current_status'] = 'pending'; // Mặc định
                        
                        if ($itemId > 0) {
                            try {
                                $statusSql = "SELECT status FROM trip_activity_status 
                                              WHERE itinerary_id = ? AND booking_id = ? 
                                              ORDER BY updated_at DESC, created_at DESC 
                                              LIMIT 1";
                                $statusStmt = $this->pdo->prepare($statusSql);
                                $statusStmt->execute([$itemId, $bookingId]);
                                $statusRow = $statusStmt->fetch();
                                if ($statusRow && !empty($statusRow['status'])) {
                                    $item['current_status'] = $statusRow['status'];
                                }
                            } catch (Throwable $e) {
                                // Nếu lỗi khi lấy status, giữ nguyên 'pending'
                            }
                        }
                    }
                    unset($item);
                }
            } catch (Throwable $e) {
                error_log('HdvModel::getTripDetail - Error fetching itinerary: ' . $e->getMessage());
                error_log('HdvModel::getTripDetail - Stack trace: ' . $e->getTraceAsString());
                // Fallback: lấy itinerary không có status
                try {
                    $sqlItineraryFallback = "SELECT i.*, 'pending' as current_status 
                                             FROM tour_itineraries i
                                             WHERE i.tour_id = ? 
                                             ORDER BY i.day_number, i.time_start";
                    $stmt = $this->pdo->prepare($sqlItineraryFallback);
                    $stmt->execute([$tourId]);
                    $rawItinerary = $stmt->fetchAll();
                    error_log('HdvModel::getTripDetail - Fallback query found ' . count($rawItinerary) . ' itinerary items');
                } catch (Throwable $e2) {
                    error_log('HdvModel::getTripDetail - Fallback query also failed: ' . $e2->getMessage());
                    $rawItinerary = [];
                }
            }
        }

        // Xử lý loại bỏ trùng lặp (Dedup) dựa trên ID - CHỈ loại bỏ khi ID trùng
        $deduped = [];
        $seenIds = []; // Track các ID đã thấy
        
        foreach ($rawItinerary as $item) {
            $itemId = isset($item['id']) ? (int)$item['id'] : 0;
            $dayNum = (int)($item['day_number'] ?? 0);
            
            // CHỈ loại bỏ nếu ID trùng (không loại bỏ theo day_number)
            if ($itemId > 0) {
                if (isset($seenIds[$itemId])) {
                    // Item này đã tồn tại (theo ID), bỏ qua
                    error_log('HdvModel::getTripDetail - Duplicate ID skipped: id=' . $itemId . ', day=' . $dayNum);
                    continue;
                }
                $seenIds[$itemId] = true;
            }
            
            // Thêm vào danh sách (kể cả khi không có ID)
            $deduped[] = $item;
        }
        // Gán lại mảng đã lọc trùng
        $data['itinerary'] = $deduped;
        
        // Log chi tiết các ID và day_number sau khi dedup
        $finalIds = [];
        $finalDayNumbers = [];
        foreach ($deduped as $item) {
            $itemId = isset($item['id']) ? (int)$item['id'] : 0;
            $dayNum = (int)($item['day_number'] ?? 0);
            if ($itemId > 0) {
                $finalIds[$itemId] = ($finalIds[$itemId] ?? 0) + 1;
            }
            if ($dayNum > 0) {
                $finalDayNumbers[$dayNum] = ($finalDayNumbers[$dayNum] ?? 0) + 1;
            }
        }
        error_log('HdvModel::getTripDetail - Final itinerary count: ' . count($deduped) . ' (after dedup)');
        error_log('HdvModel::getTripDetail - Final IDs: ' . json_encode($finalIds));
        error_log('HdvModel::getTripDetail - Final day numbers: ' . json_encode($finalDayNumbers));

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
                error_log('HdvModel::getGuideIdByUserId - Error finding by email/name: ' . $e->getMessage());
            }
            
            return 0;
        } catch (Throwable $e) {
            error_log('HdvModel::getGuideIdByUserId error: ' . $e->getMessage());
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
