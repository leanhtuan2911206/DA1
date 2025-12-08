<?php
class HdvModel extends BaseModel {
    
    // 1. Lấy danh sách tour được phân công
    public function getMyAssignments($hdvId) {
        $sql = "SELECT ta.*, b.customer_name, t.name as tour_name, b.total_people, b.start_date 
                FROM tour_assignments ta
                JOIN bookings b ON ta.booking_id = b.id
                JOIN tours t ON b.tour_id = t.id
                WHERE ta.HDV_ID = ? ORDER BY ta.assign_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hdvId]);
        return $stmt->fetchAll();
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

        if (!$data) return null;

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
        // Join bảng tour_itineraries với bảng trip_activity_status
        $sqlItinerary = "SELECT i.*, 
                                COALESCE(s.status, 'pending') as current_status 
                         FROM tour_itineraries i
                         LEFT JOIN trip_activity_status s 
                            ON i.id = s.itinerary_id AND s.booking_id = ?
                         WHERE i.tour_id = ? 
                         ORDER BY i.day_number, i.time_start";
        
        $stmt = $this->pdo->prepare($sqlItinerary);
        $stmt->execute([$bookingId, $data['tour_id']]);
        $rawItinerary = $stmt->fetchAll();
           
        $data['itinerary'] = $rawItinerary;

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
        // Dùng INSERT ... ON DUPLICATE KEY UPDATE để nếu chưa có thì thêm, có rồi thì sửa
        $sql = "INSERT INTO trip_activity_status (booking_id, itinerary_id, status) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE status = VALUES(status)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$bookingId, $itineraryId, $status]);
    }
    public function getGuideIdByUserId($userId) {
        $sql = "SELECT HDV_ID FROM hdv WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql); // Ở trong Model thì dùng được $this->pdo
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['HDV_ID'] : 0;
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