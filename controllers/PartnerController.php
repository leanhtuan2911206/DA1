<?php

class PartnerController
{
    private function requireHDVAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'hdv') { 
            header('Location: ' . BASE_URL . '?action=login'); exit; 
        }
    }

    private function getGuideId(): int
    {
        $userId = $_SESSION['user']['id'] ?? 0;
        $guideId = (int)($_SESSION['user']['guide_id'] ?? 0);
        if ($guideId === 0 && $userId > 0) {
            $hdvModel = new HdvModel();
            $guideId = $hdvModel->getGuideIdByUserId($userId);
            if ($guideId > 0) {
                $_SESSION['user']['guide_id'] = $guideId;
            }
        }
        return $guideId;
    }


    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonResponse(array $data): void
    {
        echo json_encode($data);
        exit;
    }

    private function parseInput(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        return is_array($input) ? $input : [];
    }

    public function dashboard(): void
    {
        $this->requireHDVAuth();
        $guideId = $this->getGuideId();
        $hdvModel = new HdvModel();

        $currentTab = $_GET['tab'] ?? 'detail';
        $assignments = [];
        $trip_detail = null;
        $error_message = null;

        if ($guideId > 0) {
            $assignments = $hdvModel->getMyAssignments($guideId);

            $bookingId = $_GET['booking_id'] ?? 0;
            if ($bookingId <= 0 && !empty($assignments) && ($currentTab === 'detail' || $currentTab === 'itinerary' || !isset($_GET['tab']))) {
                $firstAssignment = $assignments[0];
                $autoSelectedBookingId = isset($firstAssignment['booking_id']) ? (int)$firstAssignment['booking_id'] : 0;
                if ($autoSelectedBookingId > 0) {
                    $redirectTab = $currentTab === 'itinerary' ? 'itinerary' : 'detail';
                    header('Location: ' . BASE_URL . '?action=partner&tab=' . $redirectTab . '&booking_id=' . $autoSelectedBookingId);
                    exit;
                }
            }

            if ($currentTab === 'detail' || $currentTab === 'itinerary') {
                if ($bookingId > 0) {
                    $trip_detail = $hdvModel->getTripDetail($bookingId, $guideId);
                    
                    if ($trip_detail && !empty($trip_detail['itinerary'])) {
                        foreach ($trip_detail['itinerary'] as &$item) {
                            $item['display_time'] = substr($item['time_start'] ?? '', 0, 5);
                        }
                    }
                } else {
                    if (empty($assignments)) {
                        $error_message = "Bạn chưa được phân công tour nào.";
                    } else {
                        $error_message = "Không tìm thấy thông tin tour.";
                    }
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

    public function updateActivity(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $isAjax = $this->isAjax();
        
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'hdv') {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            }
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            }
            header('Location: ' . BASE_URL . '?action=partner&tab=itinerary');
            exit;
        }

        $input = $this->parseInput();
        $bookingId = (int)($input['booking_id'] ?? $_POST['booking_id'] ?? 0);
        $itineraryId = (int)($input['itinerary_id'] ?? $_POST['itinerary_id'] ?? 0);
        $status = trim($input['status'] ?? $_POST['status'] ?? 'pending');

        if ($bookingId > 0 && $itineraryId > 0) {
            $hdvModel = new HdvModel();
            $result = $hdvModel->updateActivityStatus($bookingId, $itineraryId, $status);
            
            if ($isAjax) {
                $this->jsonResponse(['success' => $result]);
            }
            
            $_SESSION[$result ? 'success' : 'error'] = $result 
                ? 'Đã cập nhật trạng thái hoạt động thành công' 
                : 'Không thể cập nhật trạng thái hoạt động';
            header('Location: ' . BASE_URL . '?action=partner&tab=itinerary&booking_id=' . $bookingId);
        } else {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid data']);
            }
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=partner&tab=itinerary');
        }
        exit;
    }
    
    public function updateItinerary(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'hdv') {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $input = $this->parseInput();
        $itineraryId = (int)($input['id'] ?? 0);
        $timeStart = trim($input['time_start'] ?? '');
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $location = trim($input['location'] ?? '');

        if ($itineraryId > 0 && !empty($title) && !empty($timeStart)) {
            $hdvModel = new HdvModel();
            try {
                $result = $hdvModel->updateItinerary($itineraryId, $timeStart, $title, $description, $location);
                $this->jsonResponse([
                    'success' => $result === true,
                    'message' => $result === true ? 'Cập nhật thành công' : (is_string($result) ? $result : 'Lỗi cơ sở dữ liệu')
                ]);
            } catch (Throwable $e) {
                $this->jsonResponse(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        }
    }
    
    public function checkinGuest(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $isAjax = $this->isAjax();
        
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'hdv') {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ']);
            }
            header('Location: ' . BASE_URL . '?action=partner&tab=detail');
            exit;
        }
        
        $input = $this->parseInput();
        $guestId = (int)($input['guest_id'] ?? $_POST['guest_id'] ?? 0);
        $bookingId = (int)($input['booking_id'] ?? $_POST['booking_id'] ?? 0);
        $checkinStatus = trim($input['checkin_status'] ?? $_POST['checkin_status'] ?? '');
        
        if ($guestId <= 0 || $bookingId <= 0 || empty($checkinStatus)) {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            }
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        $validStatuses = ['not_arrived', 'arrived', 'checked_in'];
        if (!in_array($checkinStatus, $validStatuses)) {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Trạng thái check-in không hợp lệ']);
            }
            $_SESSION['error'] = 'Trạng thái check-in không hợp lệ';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        $hdvModel = new HdvModel();
        $result = $hdvModel->updateGuestCheckin($guestId, $bookingId, $checkinStatus);
        
        if ($isAjax) {
            $this->jsonResponse(['success' => (bool)$result]);
        }
        
        $_SESSION[$result ? 'success' : 'error'] = $result 
            ? 'Đã cập nhật trạng thái check-in thành công' 
            : 'Không thể cập nhật trạng thái check-in';
        header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
        exit;
    }
    
    public function updateGuestSpecialRequests(): void
    {
        $this->requireHDVAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=partner&tab=detail');
            exit;
        }
        
        $guestId = (int)($_POST['guest_id'] ?? 0);
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $specialRequests = trim($_POST['special_requests'] ?? '');
        
        if ($guestId <= 0 || $bookingId <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        $hdvModel = new HdvModel();
        $guideId = $this->getGuideId();
        $tripDetail = $hdvModel->getTripDetail($bookingId, $guideId);
        
        if (!$tripDetail) {
            $_SESSION['error'] = 'Không tìm thấy thông tin tour';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        $guestFound = false;
        foreach ($tripDetail['customer_list'] ?? [] as $customer) {
            if ((int)($customer['id'] ?? 0) === $guestId) {
                $guestFound = true;
                break;
            }
        }
        
        if (!$guestFound) {
            $_SESSION['error'] = 'Không tìm thấy khách trong tour này';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        $guestModel = new TourGuest();
        $result = $guestModel->updateSpecialRequests($guestId, $specialRequests !== '' ? $specialRequests : null);
        
        $_SESSION[$result ? 'success' : 'error'] = $result 
            ? 'Cập nhật yêu cầu đặc biệt thành công' 
            : 'Không thể cập nhật yêu cầu đặc biệt';
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
        $tour = null; $logs = []; $itinerary = []; $editingLog = null; $bookings = [];
        
        if ($guideId <= 0) {
            $guideId = $this->getGuideId();
        }
        
        if ($guideId > 0) {
            try {
                $hdvModel = new HdvModel();
                $assignments = $hdvModel->getMyAssignments($guideId);
                
                if (!empty($assignments)) {
                    $tourIds = [];
                    foreach ($assignments as $ass) {
                        $tid = (int)($ass['tour_id'] ?? 0);
                        if ($tid > 0) {
                            $tourIds[$tid] = true;
                        }
                    }
                    
                    if (!empty($tourIds)) {
                        $tourModel = new Tour();
                        $allTours = $tourModel->listWithCategory([]);
                        foreach ($allTours as $t) {
                            $tid = (int)($t['id'] ?? 0);
                            if ($tid > 0 && isset($tourIds[$tid])) {
                                $tours[] = $t;
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                $tours = [];
            }
        } else {
            $tours = [];
        }
        if ($tourId > 0) {
            $isAssigned = false;
            foreach ($tours as $t) {
                if ((int)($t['id'] ?? 0) === $tourId) {
                    $isAssigned = true;
                    break;
                }
            }
            
            if (!$isAssigned && $guideId > 0) {
                try {
                    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
                    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                    $st = $pdo->prepare('SELECT COUNT(*) FROM tour_assignments ta 
                                         JOIN bookings b ON b.id = ta.booking_id 
                                         WHERE b.tour_id = :tid AND ta.HDV_ID = :gid');
                    $st->execute([':tid' => $tourId, ':gid' => $guideId]);
                    $isAssigned = (int)$st->fetchColumn() > 0;
                } catch (Throwable $e) {}
            }
            
            if (!$isAssigned) {
                $_SESSION['error'] = 'Bạn không được phân công tour này. Chỉ có thể xem nhật ký các tour được phân công cho bạn.';
                header('Location: ' . BASE_URL . '?action=partner-logs');
                exit;
            }
            
            try {
                $tourModel = new Tour();
                $tour = $tourModel->find($tourId);
                $itinerary = $tourModel->getItineraryByTourId($tourId);
                
                if ($guideId > 0) {
                    try {
                        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
                        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                        $st = $pdo->prepare('SELECT b.id, b.customer_name, b.start_date, ta.assign_date 
                                             FROM bookings b 
                                             INNER JOIN tour_assignments ta ON ta.booking_id = b.id 
                                             WHERE b.tour_id = :tid AND ta.HDV_ID = :gid
                                             ORDER BY ta.assign_date DESC, b.start_date DESC');
                        $st->execute([':tid' => $tourId, ':gid' => $guideId]);
                        $bookings = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    } catch (Throwable $e) {}
                }
                
                $logModel = new TourLog();
                $logs = $logModel->getByTourId($tourId, $guideId);
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
                        if ($editingLog && (int)($editingLog['tour_id'] ?? 0) !== $tourId) {
                            $editingLog = null;
                        }
                    } catch (Throwable $e) {
                        $editingLog = null;
                    }
                }
            } catch (Throwable $e) {}
        }
        require_once PATH_VIEW . 'main.php';
    }

    public function feedback()
    {
        $this->requireHDVAuth();
        $guideId = $this->getGuideId();

        if ($guideId <= 0) {
            $_SESSION['error'] = 'Tài khoản chưa liên kết hồ sơ HDV.';
            header('Location: ' . BASE_URL . '?action=partner');
            exit;
        }

        $hdvModel = new HdvModel();
        $feedbackModel = new GuideFeedback();
        $bookingId = (int)($_GET['booking_id'] ?? 0);

        $filters = [
            'guide_id' => $guideId,
            'feedback_type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'booking_id' => $bookingId,
        ];

        $assignments = $hdvModel->getMyAssignments($guideId);
        $feedbacks = $feedbackModel->getByGuideId($guideId, $filters);

        $bookingInfo = null;
        if ($bookingId > 0) {
            foreach ($assignments as $ass) {
                if ((int)($ass['booking_id'] ?? 0) === $bookingId) {
                    $bookingInfo = $ass;
                    break;
                }
            }
        }

        $view = 'admin/hdv_feedback';
        $title = 'Phản hồi đánh giá';
        $hideNavbar = true;
        $showPartnerSidebar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function feedbackStore()
    {
        $this->requireHDVAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=partner-feedback');
            exit;
        }

        $guideId = $this->getGuideId();
        if ($guideId <= 0) {
            $_SESSION['error'] = 'Tài khoản chưa liên kết hồ sơ HDV.';
            header('Location: ' . BASE_URL . '?action=partner-feedback');
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $feedbackType = trim($_POST['feedback_type'] ?? 'tour');
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $tourId = (int)($_POST['tour_id'] ?? 0);
        $supplierName = trim($_POST['supplier_name'] ?? '');
        $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;
        $suggestions = trim($_POST['suggestions'] ?? '');

        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ tiêu đề và nội dung phản hồi.';
            $_SESSION['feedback_form_old'] = $_POST;
            header('Location: ' . BASE_URL . '?action=partner-feedback' . ($bookingId > 0 ? '&booking_id=' . $bookingId : ''));
            exit;
        }

        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            $_SESSION['error'] = 'Điểm đánh giá phải từ 1 đến 5.';
            $_SESSION['feedback_form_old'] = $_POST;
            header('Location: ' . BASE_URL . '?action=partner-feedback' . ($bookingId > 0 ? '&booking_id=' . $bookingId : ''));
            exit;
        }

        if ($bookingId > 0 && $tourId <= 0) {
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
                $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                $stmt = $pdo->prepare('SELECT tour_id FROM bookings WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => $bookingId]);
                $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($booking && !empty($booking['tour_id'])) {
                    $tourId = (int)$booking['tour_id'];
                }
            } catch (Throwable $e) {}
        }

        if ($bookingId > 0) {
            $hdvModel = new HdvModel();
            $assignments = $hdvModel->getMyAssignments($guideId);
            $isValid = false;
            foreach ($assignments as $ass) {
                if ((int)($ass['booking_id'] ?? 0) === $bookingId) {
                    $isValid = true;
                    break;
                }
            }
            if (!$isValid) {
                $_SESSION['error'] = 'Bạn không có quyền gửi phản hồi cho booking này.';
                header('Location: ' . BASE_URL . '?action=partner-feedback');
                exit;
            }
        }

        $feedbackModel = new GuideFeedback();
        $data = [
            'guide_id' => $guideId,
            'booking_id' => $bookingId > 0 ? $bookingId : null,
            'tour_id' => $tourId > 0 ? $tourId : null,
            'feedback_type' => $feedbackType,
            'supplier_name' => $supplierName !== '' ? $supplierName : null,
            'rating' => $rating,
            'title' => $title,
            'content' => $content,
            'suggestions' => $suggestions !== '' ? $suggestions : null,
            'status' => 'pending',
        ];

        $result = $feedbackModel->create($data);
        $_SESSION[$result ? 'success' : 'error'] = $result 
            ? 'Đã gửi phản hồi đánh giá thành công.' 
            : 'Không thể gửi phản hồi. Vui lòng thử lại.';

        header('Location: ' . BASE_URL . '?action=partner-feedback' . ($bookingId > 0 ? '&booking_id=' . $bookingId : ''));
        exit;
    }
}
?>