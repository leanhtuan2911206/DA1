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
        // A. Lấy thông tin chính (Tour, Booking, Assignment)
        $sql = "SELECT t.name as tour_name, b.start_date as departure_date, b.total_people as customer_count,
                       ta.meeting_point, ta.start_time, ta.notes as assign_notes,
                       b.customer_name as leader_name, b.customer_phone as leader_phone, b.customer_email as leader_email,
                       b.id as booking_code, t.id as tour_id
                FROM tour_assignments ta
                JOIN bookings b ON ta.booking_id = b.id
                JOIN tours t ON b.tour_id = t.id
                WHERE ta.booking_id = ? AND ta.HDV_ID = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$bookingId, $hdvId]);
        $data = $stmt->fetch();

        if (!$data) return null; // Không tìm thấy

        // B. Lấy các dữ liệu phụ (Dịch vụ, Lịch trình, Khách hàng)
        
        // Dịch vụ (Xe, KS...)
        $stmt = $this->pdo->prepare("SELECT service_type as type, supplier_name as name, quantity as qty, status FROM booking_services WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        $data['services'] = $stmt->fetchAll();

        // Lịch trình (Theo Tour ID lấy được ở bước A)
        $stmt = $this->pdo->prepare("SELECT day_number, time_start, title, description, location FROM tour_itineraries WHERE tour_id = ? ORDER BY day_number, time_start");
        $stmt->execute([$data['tour_id']]);
        $data['itinerary'] = $stmt->fetchAll();
        
        // C. Lấy danh sách khách hàng (Ưu tiên lấy từ tour_guests nếu đã sync/tạo group)
        // 1. Tìm group_id từ booking_id
        $stmt = $this->pdo->prepare("SELECT id FROM tour_groups WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        $group = $stmt->fetch();

        if ($group) {
            // Nếu đã có group -> Lấy từ tour_guests (dữ liệu chính xác nhất đã qua xử lý/check-in)
            $sqlGuests = "SELECT full_name, gender, date_of_birth, phone as contact_phone, special_requests 
                          FROM tour_guests 
                          WHERE group_id = ?";
            $stmt = $this->pdo->prepare($sqlGuests);
            $stmt->execute([$group['id']]);
            $guestList = $stmt->fetchAll();

            // FALLBACK: Nếu tour_guests rỗng (chưa sync), lấy từ customers
            if (empty($guestList)) {
                $sqlCustomers = "SELECT full_name, gender, date_of_birth, contact_phone, special_requests 
                                 FROM customers 
                                 WHERE booking_id = ?";
                $stmt = $this->pdo->prepare($sqlCustomers);
                $stmt->execute([$bookingId]);
                $data['customer_list'] = $stmt->fetchAll();
            } else {
                $data['customer_list'] = $guestList;
            }
        } else {
            // Nếu chưa có group -> Lấy từ customers (dữ liệu gốc từ booking)
            $sqlCustomers = "SELECT full_name, gender, date_of_birth, contact_phone, special_requests 
                             FROM customers 
                             WHERE booking_id = ?";
            $stmt = $this->pdo->prepare($sqlCustomers);
            $stmt->execute([$bookingId]);
            $data['customer_list'] = $stmt->fetchAll();
        }

        return $data;
    }
    public function getGuideIdByUserId($userId) {
        $sql = "SELECT HDV_ID FROM hdv WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql); // Ở trong Model thì dùng được $this->pdo
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['HDV_ID'] : 0;
    }
    // File: models/HdvModel.php


}
?>