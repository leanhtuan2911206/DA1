<?php
require_once 'models/HdvModel.php';

class PartnerController
{
    public function dashboard(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'hdv') { 
            header('Location: ' . BASE_URL . '?action=login'); exit; 
        }

        $userId = $_SESSION['user']['id'];
        $hdvModel = new HdvModel();
        
        // Lấy Guide ID
        $guideId = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
        if ($guideId === 0) {
            $guideId = $hdvModel->getGuideIdByUserId($userId);
            if ($guideId > 0) $_SESSION['user']['guide_id'] = $guideId;
        }

        $currentTab = $_GET['tab'] ?? 'assignments'; // Mặc định là assignments nếu không có tab
        $assignments = [];
        $trip_detail = null;
        $error_message = null;

        if ($guideId > 0) {
            $assignments = $hdvModel->getMyAssignments($guideId);

            // --- SỬA LOGIC: Cho phép lấy chi tiết ở cả tab 'detail' VÀ 'itinerary' ---
            if ($currentTab === 'detail' || $currentTab === 'itinerary') {
                $bookingId = $_GET['booking_id'] ?? 0;
                
                // Nếu chưa chọn tour, lấy tour đầu tiên
                if ($bookingId == 0 && !empty($assignments)) {
                    $bookingId = $assignments[0]['booking_id'];
                }

                if ($bookingId > 0) {
                    $trip_detail = $hdvModel->getTripDetail($bookingId, $guideId);
                    
                    // Xử lý giờ hiển thị
                    if ($trip_detail && !empty($trip_detail['itinerary'])) {
                        foreach ($trip_detail['itinerary'] as &$item) {
                            $item['display_time'] = substr($item['time_start'] ?? '', 0, 5);
                        }
                    }
                } else {
                    $error_message = "Bạn chưa chọn tour nào hoặc chưa được phân công tour.";
                }
            }
        } else {
            $error_message = "Tài khoản chưa liên kết hồ sơ HDV.";
        }

        $view = 'admin/hdv_detail';
        $title = 'Trang của HDV';
        $hideNavbar = true; 
        
        require_once PATH_VIEW . 'main.php'; 
    }
    // Thêm hàm này vào trong class PartnerController
    public function updateActivity(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Kiểm tra đăng nhập
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'hdv') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }

        // Lấy dữ liệu JSON gửi lên từ Javascript
        $input = json_decode(file_get_contents('php://input'), true);
        $bookingId = $input['booking_id'] ?? 0;
        $itineraryId = $input['itinerary_id'] ?? 0;
        $status = $input['status'] ?? 'pending';

        if ($bookingId > 0 && $itineraryId > 0) {
            $hdvModel = new HdvModel();
            $result = $hdvModel->updateActivityStatus($bookingId, $itineraryId, $status);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
        }
        exit;
    }
    
    // Hàm cập nhật lịch trình (cho phép HDV chỉnh sửa)
    public function updateItinerary(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Kiểm tra đăng nhập
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'hdv') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
        }

        // Lấy dữ liệu JSON gửi lên từ Javascript
        $input = json_decode(file_get_contents('php://input'), true);
        $itineraryId = isset($input['id']) ? (int)$input['id'] : 0;
        $timeStart = trim($input['time_start'] ?? '');
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $location = trim($input['location'] ?? '');

        if ($itineraryId > 0 && !empty($title) && !empty($timeStart)) {
            $hdvModel = new HdvModel();
            $result = $hdvModel->updateItinerary($itineraryId, $timeStart, $title, $description, $location);
            echo json_encode(['success' => $result, 'message' => $result ? 'Cập nhật thành công' : 'Lỗi khi cập nhật']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        }
        exit;
    }
    
    // Hàm xử lý check-in khách
    public function checkinGuest(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Kiểm tra đăng nhập
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'hdv') {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }
        
        // Kiểm tra phương thức POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=partner&tab=detail');
            exit;
        }
        
        // Lấy dữ liệu từ form
        $guestId = isset($_POST['guest_id']) ? (int)$_POST['guest_id'] : 0;
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $checkinStatus = isset($_POST['checkin_status']) ? trim($_POST['checkin_status']) : '';
        
        // Kiểm tra dữ liệu hợp lệ
        if ($guestId <= 0 || $bookingId <= 0 || empty($checkinStatus)) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        // Kiểm tra trạng thái check-in hợp lệ
        $validStatuses = ['not_arrived', 'arrived', 'checked_in'];
        if (!in_array($checkinStatus, $validStatuses)) {
            $_SESSION['error'] = 'Trạng thái check-in không hợp lệ';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        // Cập nhật trạng thái check-in
        $hdvModel = new HdvModel();
        $result = $hdvModel->updateGuestCheckin($guestId, $bookingId, $checkinStatus);
        
        if ($result) {
            $_SESSION['success'] = 'Đã cập nhật trạng thái check-in thành công';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật trạng thái check-in';
        }
        
        // Quay lại trang chi tiết
        header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
        exit;
    }
}
?>