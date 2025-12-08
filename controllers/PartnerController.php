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

    public function logs(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '?action=login'); exit; }
        $role = strtolower($_SESSION['user']['role'] ?? '');
        if ($role !== 'hdv') { header('Location: ' . BASE_URL . '?action=admin'); exit; }
        $guideId = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
        $tourId = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
        $editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
        $dayFilter = isset($_GET['day']) ? (int)$_GET['day'] : 0;
        $typeFilter = isset($_GET['log_type']) ? trim((string)$_GET['log_type']) : '';
        $statusFilter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
        $view = 'admin/hdv_logs';
        $title = 'Nhật ký tour';
        $hideNavbar = true;
        $showPartnerSidebar = true;
        $tours = [];
        $tour = null; $logs = []; $itinerary = []; $editingLog = null;
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
            $hasAssign = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
            $hasBookings = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
            if ($hasAssign && $hasBookings && $guideId > 0) {
                $st = $pdo->prepare('SELECT DISTINCT b.tour_id FROM tour_assignments ta JOIN bookings b ON b.id = ta.booking_id WHERE (ta.guide_id = :gid OR ta.HDV_ID = :gid) AND b.tour_id IS NOT NULL');
                $st->execute([':gid' => $guideId]);
                $ids = array_map(function($r){ return (int)($r['tour_id'] ?? 0); }, $st->fetchAll());
                if (!empty($ids)) {
                    $in = implode(',', array_map('intval', $ids));
                    $st2 = $pdo->query('SELECT t.*, tc.name AS category_name, COALESCE(t.tour_status, "Hoạt động") AS status FROM tours t LEFT JOIN tour_categories tc ON tc.id = t.category_id WHERE t.id IN (' . $in . ') ORDER BY t.created_at DESC');
                    $tours = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            }
            if (empty($tours)) {
                $tourModel = new Tour();
                $tours = $tourModel->listWithCategory([]);
            }
        } catch (Throwable $e) {}
        if ($tourId > 0) {
            try {
                $tourModel = new Tour();
                $tour = $tourModel->find($tourId);
                $itinerary = $tourModel->getItineraryByTourId($tourId);
                $logModel = new TourLog();
                $logs = $logModel->getByTourId($tourId, null);
                // Fallback: raw query to ensure visibility
                if (empty($logs)) {
                    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                    $pdo2 = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                    $st = $pdo2->prepare('SELECT * FROM tour_logs WHERE tour_id = :tid ORDER BY COALESCE(log_date, created_at) DESC');
                    $st->execute([':tid' => $tourId]);
                    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    if (!empty($rows)) { $logs = $rows; }
                }
                if ($dayFilter > 0) {
                    $map = [];
                    foreach ($itinerary as $item) { $map[(int)$item['id']] = (int)($item['day_number'] ?? 0); }
                    $logs = array_values(array_filter($logs, function($l) use ($map, $dayFilter) {
                        $iid = (int)($l['itinerary_id'] ?? 0);
                        return $iid && isset($map[$iid]) && $map[$iid] === $dayFilter;
                    }));
                }
                if ($typeFilter !== '') { $logs = array_values(array_filter($logs, function($l) use ($typeFilter) { return isset($l['log_type']) && $l['log_type'] === $typeFilter; })); }
                if ($statusFilter !== '') { $logs = array_values(array_filter($logs, function($l) use ($statusFilter) { return isset($l['status']) && $l['status'] === $statusFilter; })); }

                if ($editId > 0) {
                    try {
                        $editingLog = $logModel->find($editId);
                        if ($editingLog && (int)($editingLog['tour_id'] ?? 0) !== $tourId) { $editingLog = null; }
                    } catch (Throwable $e) { $editingLog = null; }
                }
            } catch (Throwable $e) {}
        }
        require_once PATH_VIEW . 'main.php';
    }
}
?>