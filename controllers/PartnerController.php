<?php

class PartnerController
{
    private function requireHDVAuth()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'hdv') { 
            header('Location: ' . BASE_URL . '?action=login'); exit; 
        }
    }

    private function getGuideId()
    {
        $userId = $_SESSION['user']['id'];
        $guideId = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
        if ($guideId === 0) {
            $hdvModel = new HdvModel();
            $guideId = $hdvModel->getGuideIdByUserId($userId);
            if ($guideId > 0) {
                $_SESSION['user']['guide_id'] = $guideId;
            }
        }
        return $guideId;
    }

    private function getPDO()
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
            return new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
        } catch (Throwable $e) {
            return null;
        }
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

            // Tự động chọn tour đầu tiên nếu chưa có booking_id và có assignments
            // Chỉ tự động redirect nếu tab là 'detail' hoặc 'itinerary' hoặc không có tab
            $bookingId = $_GET['booking_id'] ?? 0;
            if ($bookingId <= 0 && !empty($assignments) && ($currentTab === 'detail' || $currentTab === 'itinerary' || !isset($_GET['tab']))) {
                // Chọn tour đầu tiên (hoặc tour gần nhất theo assign_date)
                $firstAssignment = $assignments[0];
                $autoSelectedBookingId = isset($firstAssignment['booking_id']) ? (int)$firstAssignment['booking_id'] : 0;
                if ($autoSelectedBookingId > 0) {
                    // Tự động redirect đến tour đầu tiên với tab detail
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
    // Thêm hàm này vào trong class PartnerController
    public function updateActivity(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Kiểm tra đăng nhập
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'hdv') {
            // Kiểm tra xem có phải AJAX request không
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
            }
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Kiểm tra phương thức
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit;
            }
            header('Location: ' . BASE_URL . '?action=partner&tab=itinerary');
            exit;
        }

        // Lấy dữ liệu từ POST form hoặc JSON
        $bookingId = 0;
        $itineraryId = 0;
        $status = 'pending';
        
        // Ưu tiên đọc từ $_POST (form submit)
        if (!empty($_POST['booking_id']) && !empty($_POST['itinerary_id'])) {
            $bookingId = (int)$_POST['booking_id'];
            $itineraryId = (int)$_POST['itinerary_id'];
            $status = trim($_POST['status'] ?? 'pending');
        } else {
            // Nếu không có POST, thử đọc từ JSON (AJAX)
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $bookingId = (int)($input['booking_id'] ?? 0);
                $itineraryId = (int)($input['itinerary_id'] ?? 0);
                $status = trim($input['status'] ?? 'pending');
            }
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($bookingId > 0 && $itineraryId > 0) {
            $hdvModel = new HdvModel();
            $result = $hdvModel->updateActivityStatus($bookingId, $itineraryId, $status);
            
            if ($isAjax) {
                echo json_encode(['success' => $result]);
            } else {
                // Redirect về trang itinerary với thông báo
                if ($result) {
                    $_SESSION['success'] = 'Đã cập nhật trạng thái hoạt động thành công';
                } else {
                    $_SESSION['error'] = 'Không thể cập nhật trạng thái hoạt động';
                }
                $bookingCode = $bookingId;
                header('Location: ' . BASE_URL . '?action=partner&tab=itinerary&booking_id=' . $bookingCode);
            }
        } else {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
            } else {
                $_SESSION['error'] = 'Dữ liệu không hợp lệ';
                header('Location: ' . BASE_URL . '?action=partner&tab=itinerary');
            }
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
    
    // Hàm xử lý cập nhật yêu cầu đặc biệt của khách
    public function updateGuestSpecialRequests(): void
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
        $specialRequests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
        
        // Kiểm tra dữ liệu hợp lệ
        if ($guestId <= 0 || $bookingId <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        // Kiểm tra xem guest có thuộc booking này không (bảo mật)
        $hdvModel = new HdvModel();
        $tripDetail = $hdvModel->getTripDetail($bookingId, $_SESSION['user']['guide_id'] ?? 0);
        
        if (!$tripDetail) {
            $_SESSION['error'] = 'Không tìm thấy thông tin tour';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        // Kiểm tra guest có trong danh sách khách của tour này không
        $guestFound = false;
        foreach ($tripDetail['customer_list'] ?? [] as $customer) {
            if (isset($customer['id']) && (int)$customer['id'] === $guestId) {
                $guestFound = true;
                break;
            }
        }
        
        if (!$guestFound) {
            $_SESSION['error'] = 'Không tìm thấy khách trong tour này';
            header('Location: ' . BASE_URL . '?action=partner&tab=detail&booking_id=' . $bookingId);
            exit;
        }
        
        // Cập nhật yêu cầu đặc biệt
        $guestModel = new TourGuest();
        $specialRequests = $specialRequests !== '' ? $specialRequests : null;
        
        if ($guestModel->updateSpecialRequests($guestId, $specialRequests)) {
            $_SESSION['success'] = 'Cập nhật yêu cầu đặc biệt thành công';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật yêu cầu đặc biệt';
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
        $tour = null; $logs = []; $itinerary = []; $editingLog = null; $bookings = [];
        
        // Lấy guide_id nếu chưa có trong session
        if ($guideId <= 0 && isset($_SESSION['user']['id'])) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                $stmt = $pdo->prepare('SELECT HDV_ID FROM hdv WHERE user_id = :uid LIMIT 1');
                $stmt->execute([':uid' => (int)$_SESSION['user']['id']]);
                $r = $stmt->fetch();
                if ($r && isset($r['HDV_ID'])) {
                    $guideId = (int)$r['HDV_ID'];
                    $_SESSION['user']['guide_id'] = $guideId;
                } else {
                }
            } catch (Throwable $e) {
            }
        }
        
        // Lấy danh sách tours được phân công cho HDV - CHỈ lấy tours được phân công
        if ($guideId > 0) {
            try {
                // Sử dụng HdvModel để lấy assignments (đã được kiểm chứng)
                $hdvModel = new HdvModel();
                $assignments = $hdvModel->getMyAssignments($guideId);
                
                if (!empty($assignments)) {
                    // Lấy danh sách tour_id từ assignments
                    $tourIds = [];
                    foreach ($assignments as $ass) {
                        $tid = (int)($ass['tour_id'] ?? 0);
                        if ($tid > 0) {
                            $tourIds[$tid] = true;
                        }
                    }
                    
                    if (!empty($tourIds)) {
                        // Lấy thông tin tours từ danh sách tour_id
                        $tourModel = new Tour();
                        $allTours = $tourModel->listWithCategory([]);
                        
                        // Lọc chỉ lấy tours được phân công
                        foreach ($allTours as $t) {
                            $tid = (int)($t['id'] ?? 0);
                            if ($tid > 0 && isset($tourIds[$tid])) {
                                $tours[] = $t;
                            }
                        }
                        
                    } else {
                    }
                } else {
                }
            } catch (Throwable $e) {
                $tours = [];
            }
        } else {
            $tours = [];
        }
        if ($tourId > 0) {
            // Kiểm tra xem tour này có trong danh sách tours được phân công không
            $isAssigned = false;
            
            // Kiểm tra nhanh: tour có trong danh sách $tours đã lấy ở trên không
            foreach ($tours as $t) {
                if ((int)($t['id'] ?? 0) === $tourId) {
                    $isAssigned = true;
                    break;
                }
            }
            
            // Nếu không tìm thấy trong danh sách, kiểm tra lại bằng query
            if (!$isAssigned && $guideId > 0) {
                try {
                    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                    $hasAssign = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
                    $hasBookings = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
                    
                    if ($hasAssign && $hasBookings) {
                        // Kiểm tra bằng HDV_ID (cột chính trong tour_assignments)
                        $st = $pdo->prepare('SELECT COUNT(*) FROM tour_assignments ta 
                                             JOIN bookings b ON b.id = ta.booking_id 
                                             WHERE b.tour_id = :tid 
                                             AND ta.HDV_ID = :gid');
                        $st->execute([':tid' => $tourId, ':gid' => $guideId]);
                        $count = (int)$st->fetchColumn();
                        $isAssigned = $count > 0;
                    }
                } catch (Throwable $e) {
                }
            }
            
            // Nếu tour không được phân công cho HDV này, không cho phép truy cập
            if (!$isAssigned) {
                $_SESSION['error'] = 'Bạn không được phân công tour này. Chỉ có thể xem nhật ký các tour được phân công cho bạn.';
                header('Location: ' . BASE_URL . '?action=partner-logs');
                exit;
            }
            
            try {
                $tourModel = new Tour();
                $tour = $tourModel->find($tourId);
                $itinerary = $tourModel->getItineraryByTourId($tourId);
                
                // Lấy danh sách bookings của tour được phân công cho HDV này
                if ($guideId > 0) {
                    try {
                        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                        $pdo2 = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                        $hasAssign2 = (int)$pdo2->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
                        $hasBookings2 = (int)$pdo2->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
                        if ($hasAssign2 && $hasBookings2) {
                            $st = $pdo2->prepare('SELECT b.id, b.customer_name, b.start_date, ta.assign_date 
                                                 FROM bookings b 
                                                 INNER JOIN tour_assignments ta ON ta.booking_id = b.id 
                                                 WHERE b.tour_id = :tid AND ta.HDV_ID = :gid
                                                 ORDER BY ta.assign_date DESC, b.start_date DESC');
                            $st->execute([':tid' => $tourId, ':gid' => $guideId]);
                            $bookings = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                        }
                    } catch (Throwable $e) {
                    }
                }
                
                $logModel = new TourLog();
                // Lấy nhật ký của HDV này cho tour
                $logs = $logModel->getByTourId($tourId, $guideId);
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
            $pdo = $this->getPDO();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare('SELECT tour_id FROM bookings WHERE id = :id LIMIT 1');
                    $stmt->execute([':id' => $bookingId]);
                    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($booking && !empty($booking['tour_id'])) {
                        $tourId = (int)$booking['tour_id'];
                    }
                } catch (Throwable $e) {}
            }
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