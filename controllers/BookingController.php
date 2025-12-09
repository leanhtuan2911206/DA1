<?php

class BookingController
{
    public function index()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Thiết lập view
        $view = 'admin/bookings';
        $title = 'Quản lý Booking';
        $hideNavbar = true;

        // Lấy dữ liệu
        $bookingsGrouped = [];
        $tours = [];
        $filters = [
            'tour_id' => $_GET['tour_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'booking_type' => $_GET['booking_type'] ?? '',
            'customer_name' => $_GET['customer_name'] ?? '',
            'start_date_from' => $_GET['start_date_from'] ?? '',
            'start_date_to' => $_GET['start_date_to'] ?? '',
        ];

        try {
            $bookingModel = new Booking();
            $tourModel = new Tour();
            $assignmentModel = new Assignment();
            
            // Lấy danh sách tour để hiển thị trong filter
            $tours = $tourModel->listWithCategory([]);
            
            // Lấy booking nhóm theo tour
            $bookingsGrouped = $bookingModel->getBookingsGroupedByTour($filters);
            
            // Thêm thông tin phân công cho mỗi booking
            foreach ($bookingsGrouped as &$tourGroup) {
                foreach ($tourGroup['bookings'] as &$booking) {
                    $assignment = $assignmentModel->getAssignmentByBookingId($booking['id']);
                    $booking['assignment'] = $assignment;
                    $booking['has_assignment'] = !empty($assignment);
                }
                unset($booking);
            }
            unset($tourGroup);
        } catch (Throwable $e) {
            $bookingsGrouped = [];
            $tours = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function create()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Thiết lập view
        $view = 'admin/bookings-create';
        $title = 'Tạo Booking Mới';
        $hideNavbar = true;

        // Lấy danh sách tour
        $tours = [];
        try {
            $tourModel = new Tour();
            $tours = $tourModel->listWithCategory([]);
        } catch (Throwable $e) {
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function store()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy dữ liệu từ form
        $tour_id = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
        $total_people = isset($_POST['total_people']) ? (int)$_POST['total_people'] : 1;
        $booking_type = isset($_POST['booking_type']) ? $_POST['booking_type'] : 'individual';
        $special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
        $deposit_amount = isset($_POST['deposit_amount']) ? (float)$_POST['deposit_amount'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'pending';

        // Lấy user_id từ session
        $changed_by = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

        // Kiểm tra dữ liệu bắt buộc
        if (empty($tour_id) || empty($start_date) || empty($customer_name) || $total_people < 1) {
            $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
            header('Location: ' . BASE_URL . '?action=bookings-create');
            exit;
        }

        // Tạo booking
        try {
            $bookingModel = new Booking();
            $data = [
                'tour_id' => $tour_id,
                'start_date' => $start_date,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email,
                'total_people' => $total_people,
                'booking_type' => $booking_type,
                'special_requests' => $special_requests,
                'deposit_amount' => $deposit_amount,
                'status' => $status,
                'changed_by' => $changed_by,
            ];

            $bookingId = $bookingModel->create($data);

            if ($bookingId) {
                // Tự động tạo customer record đầu tiên từ thông tin booking
                // Để khi xem danh sách khách hàng, tên người đặt tour tự động có trong đó
                try {
                    $customerModel = new Customer();
                    $existingCustomers = $customerModel->getByBooking($bookingId);
                    
                    // Chỉ tạo nếu chưa có customer nào trong booking
                    if (empty($existingCustomers) && !empty($customer_name)) {
                        $customerData = [
                            'booking_id' => $bookingId,
                            'full_name' => $customer_name,
                            'contact_phone' => $customer_phone ?: null,
                            'email' => $customer_email ?: null,
                            'payment_status' => $status === 'deposit' ? 'deposit' : ($status === 'paid' || $status === 'completed' || $status === 'confirmed' ? 'paid' : 'unpaid'),
                            'gender' => null,
                            'date_of_birth' => null,
                            'id_type' => null,
                            'id_number' => null,
                            'address' => null,
                            'special_requests' => null,
                        ];
                        
                        $customerModel->create($customerData);
                    }
                } catch (Throwable $e) {
                }
                
                $_SESSION['success'] = 'Tạo booking thành công!';
                header('Location: ' . BASE_URL . '?action=bookings');
                exit;
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi tạo booking!';
                header('Location: ' . BASE_URL . '?action=bookings-create');
                exit;
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '?action=bookings-create');
            exit;
        }
    }

    public function edit()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy ID booking
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Thiết lập view
        $view = 'admin/bookings-edit';
        $title = 'Chỉnh sửa Booking';
        $hideNavbar = true;

        // Lấy dữ liệu
        $booking = null;
        $tours = [];
        try {
            $bookingModel = new Booking();
            $tourModel = new Tour();
            
            $booking = $bookingModel->findWithTour($id);
            $tours = $tourModel->listWithCategory([]);

            if (!$booking) {
                $_SESSION['error'] = 'Booking không tồn tại!';
                header('Location: ' . BASE_URL . '?action=bookings');
                exit;
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra khi tải dữ liệu!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        require_once PATH_VIEW . 'main.php';
    }

    // Xem chi tiết booking với lịch trình
    public function detail()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy ID booking
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Thiết lập view
        $view = 'admin/bookings-detail';
        $title = 'Chi tiết Booking';
        $hideNavbar = true;

        // Lấy dữ liệu
        $booking = null;
        $tour = null;
        $itineraries = [];
        try {
            $bookingModel = new Booking();
            $tourModel = new Tour();
            
            $booking = $bookingModel->findWithTour($id);

            if (!$booking) {
                $_SESSION['error'] = 'Booking không tồn tại!';
                header('Location: ' . BASE_URL . '?action=bookings');
                exit;
            }

            // Lấy thông tin tour
            if (isset($booking['tour_id']) && $booking['tour_id'] > 0) {
                $tour = $tourModel->find($booking['tour_id']);
                
                // Lấy lịch trình chi tiết theo booking_id để mỗi booking có lịch trình riêng
                if ($tour) {
                    $itineraries = $tourModel->getItineraryByTourId($tour['id'], $booking['id']);
                }
            }

            // Lấy nhật ký tour
            $tourLogs = [];
            if (isset($booking['tour_id']) && $booking['tour_id'] > 0) {
                $logModel = new TourLog();
                $tourLogs = $logModel->getByBookingId($booking['id']);
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra khi tải dữ liệu!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        require_once PATH_VIEW . 'main.php';
    }

    // Thêm nhật ký tour từ booking
    public function createLog()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy booking_id từ GET
        $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        
        if ($booking_id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại.';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Lấy thông tin booking và tour
        $bookingModel = new Booking();
        $booking = $bookingModel->findWithTour($booking_id);

        if (!$booking || !isset($booking['tour_id'])) {
            $_SESSION['error'] = 'Booking hoặc tour không tồn tại.';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        $tour_id = (int)$booking['tour_id'];
        $tourModel = new Tour();
        $tour = $tourModel->find($tour_id);

        if (!$tour) {
            $_SESSION['error'] = 'Tour không tồn tại.';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Lấy dữ liệu cần thiết
        $itineraries = $tourModel->getItineraryByTourId($tour_id, $booking_id);
        $guideModel = new Guide();
        $guides = $guideModel->list();

        // Thiết lập view
        $view = 'admin/bookings-log-create';
        $title = 'Thêm nhật ký tour: ' . $tour['name'];
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function storeLog()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Nếu là preview (chọn log_type), quay lại form với log_type đã chọn
        if (isset($_POST['preview']) && $_POST['preview'] == '1' && !empty($_POST['log_type'])) {
            header('Location: ' . BASE_URL . '?action=bookings-log-create&booking_id=' . (int)$_POST['booking_id'] . '&log_type=' . urlencode($_POST['log_type']));
            exit;
        }

        // Lấy dữ liệu từ form
        $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $tour_id = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
        $logType = trim($_POST['log_type'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');
        $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;
        $guideId = isset($_POST['guide_id']) ? (int)$_POST['guide_id'] : 0;
        $itineraryId = isset($_POST['itinerary_id']) ? (int)$_POST['itinerary_id'] : 0;
        $logDate = trim($_POST['log_date'] ?? '');
        
        // Các trường mới theo yêu cầu
        $weather = trim($_POST['weather'] ?? '');
        $health = trim($_POST['health_status'] ?? '');
        $activities = trim($_POST['special_activities'] ?? '');
        $handling = trim($_POST['handling_notes'] ?? '');
        $feedback = trim($_POST['customer_feedback'] ?? '');
        $ratingComment = trim($_POST['rating_comment'] ?? '');
        $ratingCoordination = isset($_POST['rating_coordination']) && $_POST['rating_coordination'] !== '' ? (int)$_POST['rating_coordination'] : null;
        $ratingSpirit = isset($_POST['rating_spirit']) && $_POST['rating_spirit'] !== '' ? (int)$_POST['rating_spirit'] : null;

        if ($tour_id <= 0 || $logType === '' || $title === '') {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
            if ($booking_id > 0) {
                header('Location: ' . BASE_URL . '?action=bookings-log-create&booking_id=' . $booking_id);
            } else {
                header('Location: ' . BASE_URL . '?action=bookings');
            }
            exit;
        }

        // Xử lý upload ảnh
        $imageDbPath = null;
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    if (!is_dir(PATH_ASSETS_UPLOADS)) {
                        @mkdir(PATH_ASSETS_UPLOADS, 0777, true);
                    }
                    $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $target = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $imageDbPath = 'assets/uploads/' . $filename;
                    }
                }
            }
        }

        // Tạo mô tả đầy đủ từ các trường
        $parts = [];
        if ($weather !== '') { $parts[] = '🌤️ Thời tiết: ' . $weather; }
        if ($health !== '') { $parts[] = '🏥 Tình trạng sức khỏe khách: ' . $health; }
        if ($activities !== '') { $parts[] = '⭐ Hoạt động đặc biệt: ' . $activities; }
        if ($handling !== '') { $parts[] = '📝 Cách xử lý tình huống: ' . $handling; }
        if ($feedback !== '') { $parts[] = '💬 Phản hồi khách hàng: ' . $feedback; }
        if ($logType === 'rating') {
            if ($ratingCoordination) { $parts[] = '🤝 Đánh giá phối hợp: ' . $ratingCoordination . '/5'; }
            if ($ratingSpirit) { $parts[] = '💪 Tinh thần làm việc: ' . $ratingSpirit . '/5'; }
            if ($ratingComment !== '') { $parts[] = '📝 Nhận xét: ' . $ratingComment; }
        }
        
        // Gộp description với các parts
        $descParts = array_filter([$description]);
        if (!empty($parts)) {
            $descParts[] = implode("\n", $parts);
        }
        $fullDesc = trim(implode("\n\n", $descParts));

        // Lưu nhật ký
        $logModel = new TourLog();
        $data = [
            'tour_id' => $tour_id,
            'log_type' => $logType,
            'title' => $title,
            'description' => $fullDesc,
            'status' => $status,
            'rating' => $rating,
        ];
        if ($guideId > 0) { $data['guide_id'] = $guideId; }
        if ($itineraryId > 0) { $data['itinerary_id'] = $itineraryId; }
        if ($logDate !== '') { $data['log_date'] = $logDate; }
        if ($imageDbPath) { $data['image_path'] = $imageDbPath; }

        $result = $logModel->create($data);

        if ($result) {
            $_SESSION['success'] = 'Đã thêm nhật ký thành công.';
            header('Location: ' . BASE_URL . '?action=bookings-detail&id=' . $booking_id);
        } else {
            $_SESSION['error'] = 'Lỗi khi lưu nhật ký.';
            if ($booking_id > 0) {
                header('Location: ' . BASE_URL . '?action=bookings-log-create&booking_id=' . $booking_id);
            } else {
                header('Location: ' . BASE_URL . '?action=bookings');
            }
        }
        exit;
    }

    public function update()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy ID booking
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Lấy dữ liệu từ form
        $tour_id = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
        $total_people = isset($_POST['total_people']) ? (int)$_POST['total_people'] : 1;
        $booking_type = isset($_POST['booking_type']) ? $_POST['booking_type'] : 'individual';
        $special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
        $deposit_amount = isset($_POST['deposit_amount']) ? (float)$_POST['deposit_amount'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'pending';

        // Lấy user_id từ session
        $changed_by = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

        // Kiểm tra dữ liệu bắt buộc
        if (empty($tour_id) || empty($start_date) || empty($customer_name) || $total_people < 1) {
            $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
            header('Location: ' . BASE_URL . '?action=bookings-edit&id=' . $id);
            exit;
        }

        // Cập nhật booking
        try {
            $bookingModel = new Booking();
            $data = [
                'tour_id' => $tour_id,
                'start_date' => $start_date,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email,
                'total_people' => $total_people,
                'booking_type' => $booking_type,
                'special_requests' => $special_requests,
                'deposit_amount' => $deposit_amount,
                'status' => $status,
                'changed_by' => $changed_by,
            ];

            $result = $bookingModel->update($id, $data);

            if ($result) {
                $_SESSION['success'] = 'Cập nhật booking thành công!';
                header('Location: ' . BASE_URL . '?action=bookings');
                exit;
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật booking!';
                header('Location: ' . BASE_URL . '?action=bookings-edit&id=' . $id);
                exit;
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '?action=bookings-edit&id=' . $id);
            exit;
        }
    }

    public function updateStatus()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy dữ liệu
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $changed_by = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

        if ($id <= 0 || empty($status)) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Cập nhật trạng thái
        try {
            $bookingModel = new Booking();
            $oldBooking = $bookingModel->find($id);
            $result = $bookingModel->updateStatus($id, $status, $changed_by);

            if ($result) {
                // Kiểm tra xem có cần đồng bộ payment_status cho khách không
                $customerModel = new Customer();
                $customers = $customerModel->getByBooking($id);
                $customerCount = count($customers);
                
                $statusMessages = [
                    'deposit' => 'Đã đặt cọc',
                    'paid' => 'Đã thanh toán',
                    'completed' => 'Đã hoàn thành',
                    'confirmed' => 'Đã xác nhận',
                    'pending' => 'Chờ xác nhận',
                    'cancelled' => 'Đã hủy'
                ];
                
                $statusText = $statusMessages[$status] ?? $status;
                
                if ($customerCount > 0 && in_array($status, ['deposit', 'paid', 'completed', 'confirmed'])) {
                    $paymentStatusText = $status === 'deposit' ? 'đã đặt cọc' : 'đã thanh toán';
                    $_SESSION['success'] = "Cập nhật trạng thái booking thành '$statusText' và đồng bộ trạng thái thanh toán ($paymentStatusText) cho $customerCount khách trong booking.";
                } else {
                    $_SESSION['success'] = "Cập nhật trạng thái booking thành '$statusText' thành công!";
                }
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật trạng thái!';
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '?action=bookings');
        exit;
    }

    public function delete()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy ID booking
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Xóa booking
        try {
            $bookingModel = new Booking();
            $result = $bookingModel->delete($id);

            if ($result) {
                $_SESSION['success'] = 'Xóa booking thành công!';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi xóa booking!';
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '?action=bookings');
        exit;
    }

    // Thêm lịch trình cho booking (thực chất là thêm cho tour của booking đó)
    public function createItinerary()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy booking_id từ GET
        $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        
        if ($booking_id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại.';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Lấy thông tin booking và tour
        $bookingModel = new Booking();
        $booking = $bookingModel->findWithTour($booking_id);

        if (!$booking || !isset($booking['tour_id'])) {
            $_SESSION['error'] = 'Booking hoặc tour không tồn tại.';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        $tour_id = (int)$booking['tour_id'];
        $tourModel = new Tour();
        $tour = $tourModel->find($tour_id);

        if (!$tour) {
            $_SESSION['error'] = 'Tour không tồn tại.';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Lấy dữ liệu mẫu (dựa theo category của tour hoặc template_id nếu có)
        $templateItems = [];
        if (!empty($tour['template_id'])) {
            $templateItems = $tourModel->getTemplateItems($tour['template_id']);
        } elseif (!empty($tour['category_id'])) {
            // Fallback: lấy template theo category
            $templateModel = new TourTemplate();
            $template = $templateModel->findByCategoryId($tour['category_id']);
            if ($template) {
                $templateItems = $tourModel->getTemplateItems($template['id']);
            }
        }

        // Thiết lập view
        $view = 'admin/bookings-itinerary-create';
        $title = 'Thêm lịch trình: ' . $tour['name'];
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function storeItinerary()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Lấy booking_id và tour_id
        $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $tour_id = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
        $day_number = (int)($_POST['day_number'] ?? 1);
        $time_start = trim($_POST['time_start'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if ($tour_id <= 0 || empty($title)) {
            $_SESSION['error'] = 'Vui lòng nhập tên hoạt động.';
            if ($booking_id > 0) {
                header('Location: ' . BASE_URL . '?action=bookings-itinerary-create&booking_id=' . $booking_id);
            } else {
                header('Location: ' . BASE_URL . '?action=bookings');
            }
            exit;
        }

        $tourModel = new Tour();

        // Kiểm tra trùng lặp trước khi thêm (kiểm tra theo booking_id nếu có)
        $existing = $tourModel->findItineraryByDetails($tour_id, $day_number, $time_start, $title, $booking_id > 0 ? $booking_id : null);
        if ($existing) {
            $_SESSION['error'] = 'Lịch trình này đã tồn tại (Ngày ' . $day_number . ' - ' . htmlspecialchars($time_start) . ').';
            if ($booking_id > 0) {
                header('Location: ' . BASE_URL . '?action=bookings-itinerary-create&booking_id=' . $booking_id);
            } else {
                header('Location: ' . BASE_URL . '?action=bookings');
            }
            exit;
        }

        // Thêm lịch trình với booking_id để mỗi booking có lịch trình riêng
        $result = $tourModel->insertItinerary($tour_id, $day_number, $time_start, $title, $description, $location, $booking_id > 0 ? $booking_id : null);

        if ($result) {
            $_SESSION['success'] = 'Đã thêm lịch trình mới.';
            // Nếu người dùng chọn "Lưu và thêm tiếp"
            if (isset($_POST['save_and_continue']) && $booking_id > 0) {
                header('Location: ' . BASE_URL . '?action=bookings-itinerary-create&booking_id=' . $booking_id);
            } else {
                // Redirect về bookings-detail để xem lịch trình vừa thêm
                if ($booking_id > 0) {
                    header('Location: ' . BASE_URL . '?action=bookings-detail&id=' . $booking_id);
                } else {
                    header('Location: ' . BASE_URL . '?action=bookings');
                }
            }
        } else {
            $_SESSION['error'] = 'Lỗi khi lưu dữ liệu.';
            if ($booking_id > 0) {
                header('Location: ' . BASE_URL . '?action=bookings-itinerary-create&booking_id=' . $booking_id);
            } else {
                header('Location: ' . BASE_URL . '?action=bookings');
            }
        }
        exit;
    }
}

