<?php
require_once 'models/HdvModel.php';

class PartnerController
{
    public function dashboard(): void
    {
        // 1. Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'hdv') { 
            header('Location: ' . BASE_URL . '?action=login'); exit; 
        }

        $userId = $_SESSION['user']['id'];
        $guideId = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
        
        $hdvModel = new HdvModel();

        // --- SỬA LỖI Ở ĐÂY: Dùng hàm của Model thay vì gọi pdo trực tiếp ---
        if ($guideId === 0) {
            // Gọi hàm bạn đã viết trong HdvModel.php
            $guideId = $hdvModel->getGuideIdByUserId($userId);
            
            if ($guideId > 0) {
                $_SESSION['user']['guide_id'] = $guideId; // Lưu session
            }
        }
        // -------------------------------------------------------------

        // 2. Logic lấy dữ liệu phân bổ
        $currentTab = $_GET['tab'] ?? 'assignments';
        $assignments = [];
        $trip_detail = null;
        $error_message = null;

        // Nếu đã tìm thấy Guide ID thì mới đi lấy dữ liệu
        if ($guideId > 0) {
            
            // Luôn lấy danh sách phân bổ để dùng cho cả 2 tab
            $assignments = $hdvModel->getMyAssignments($guideId);

            if ($currentTab === 'detail') {
                $bookingId = $_GET['booking_id'] ?? 0;
                
                // TỰ ĐỘNG CHỌN TOUR: Nếu vào tab chi tiết mà chưa chọn tour (booking_id=0)
                // Thì tự động lấy tour mới nhất trong danh sách assignments để hiển thị
                if ($bookingId == 0 && !empty($assignments)) {
                    $bookingId = $assignments[0]['booking_id'];
                }

                if ($bookingId > 0) {
                    $trip_detail = $hdvModel->getTripDetail($bookingId, $guideId);
                    
                    // Xử lý hiển thị giờ
                    if ($trip_detail && !empty($trip_detail['itinerary'])) {
                        foreach ($trip_detail['itinerary'] as &$item) {
                            $item['display_time'] = substr($item['time_start'] ?? '', 0, 5);
                        }
                    }
                } else {
                    // Trường hợp danh sách assignments rỗng
                    $error_message = "Bạn chưa chọn tour nào hoặc chưa được phân công tour.";
                }
            }
        } else {
            $error_message = "Tài khoản này chưa được liên kết với hồ sơ HDV nào. Vui lòng báo Admin.";
        }

        // 3. Gọi View
        $view = 'admin/hdv_detail'; // Đảm bảo tên file view đúng với thư mục views của bạn
        $title = 'Trang của HDV';
        $hideNavbar = true; 
        
        require_once PATH_VIEW . 'main.php'; 
    }
}
?>