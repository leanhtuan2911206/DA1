<?php

class TourController
{
    public function index(): void
    {
        $this->ensureAuthenticated();

        $view = 'admin/tours';
        $title = 'Quản lý danh sách tour';
        $hideNavbar = true;

        $tourModel = new Tour();
        $categoryModel = new TourCategory();

        $filters = [
            'keyword'      => trim($_GET['keyword'] ?? ''),
            'category_id'  => $_GET['category_id'] ?? '',
            'destination'  => trim($_GET['destination'] ?? ''),
            'price_order'  => $_GET['price_order'] ?? '',
            'tour_status'  => $_GET['tour_status'] ?? '',
        ];

        try {
            $tours = $tourModel->listWithCategory($filters);
            // Thêm thông tin phân công HDV cho mỗi tour
            foreach ($tours as &$tour) {
                $tourId = (int)($tour['id'] ?? 0);
                if ($tourId > 0) {
                    $tour['assigned_guide_count'] = $tourModel->getAssignedBookingCount($tourId);
                    $tour['has_assignment'] = $tourModel->hasAssignment($tourId);
                } else {
                    $tour['assigned_guide_count'] = 0;
                    $tour['has_assignment'] = false;
                }
            }
            unset($tour); // Hủy tham chiếu
        } catch (Throwable $e) {
            $tours = [];
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            $categories = $categoryModel->getAll();
        } catch (Throwable $e) {
            $categories = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function edit(): void
    {
        $this->ensureAuthenticated();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        $tourModel = new Tour();
        $tour = $tourModel->find($id);
        if (!$tour) {
            $_SESSION['error'] = 'Tour không tồn tại.';
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        $categoryModel = new TourCategory();
        try {
            $categories = $categoryModel->getAll();
        } catch (Throwable $e) {
            $categories = [];
        }

        $view = 'admin/tours-edit';
        $title = 'Sửa tour';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function update(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $itinerary = trim($_POST['itinerary'] ?? '');
        $policy = trim($_POST['policy'] ?? '');
        $tour_status = trim($_POST['tour_status'] ?? '');

        if ($id <= 0 || empty($name) || $category_id <= 0) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin.';
            header('Location: ' . BASE_URL . '?action=tours-edit&id=' . $id);
            exit;
        }

        try {
            $tourModel = new Tour();
            $existing = $tourModel->find($id);
            if (!$existing) {
                $_SESSION['error'] = 'Tour không tồn tại.';
                header('Location: ' . BASE_URL . '?action=tours');
                exit;
            }
            $imageDbPath = null;
            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['image'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $_SESSION['error'] = 'Lỗi upload ảnh.';
                    header('Location: ' . BASE_URL . '?action=tours-edit&id=' . $id);
                    exit;
                }
                $originalName = $file['name'];
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                $ext = $ext ? strtolower($ext) : '';
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if ($ext && !in_array($ext, $allowed)) {
                    $_SESSION['error'] = 'Định dạng ảnh không hợp lệ.';
                    header('Location: ' . BASE_URL . '?action=tours-edit&id=' . $id);
                    exit;
                }
                if (!is_dir(PATH_ASSETS_UPLOADS)) {
                    @mkdir(PATH_ASSETS_UPLOADS, 0777, true);
                }
                $filename = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
                $target = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    $_SESSION['error'] = 'Không thể lưu file ảnh.';
                    header('Location: ' . BASE_URL . '?action=tours-edit&id=' . $id);
                    exit;
                }
                $imageDbPath = 'assets/uploads/' . $filename;
            }

            $updateResult = $tourModel->update(
                $id,
                $name,
                $category_id,
                $price,
                $description ?: null,
                $itinerary ?: null,
                $policy ?: null,
                $imageDbPath,
                $tour_status !== '' ? $tour_status : null
            );
            
            if ($updateResult) {
                if ($imageDbPath && !empty($existing['image'])) {
                    $oldFile = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($existing['image']);
                    if (file_exists($oldFile)) { @unlink($oldFile); }
                }
                $_SESSION['success'] = 'Cập nhật tour thành công.';
            } else {
                $err = $tourModel->getLastError();
                $_SESSION['error'] = 'Không thể cập nhật tour.';
                $_SESSION['update_tour_debug'] = [
                    'id' => $id,
                    'error' => $err,
                    'post' => $_POST
                ];
                header('Location: ' . BASE_URL . '?action=tours-edit&id=' . $id);
                exit;
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Lỗi khi cập nhật tour.';
            header('Location: ' . BASE_URL . '?action=tours-edit&id=' . $id);
            exit;
        }

        header('Location: ' . BASE_URL . '?action=tours');
        exit;
    }

    public function create(): void
    {
        $this->ensureAuthenticated();

        $categoryModel = new TourCategory();
        try {
            $categories = $categoryModel->getAll();
        } catch (Throwable $e) {
            $categories = [];
        }

        // Load tour templates để có thể chọn và tự động điền itinerary/policy
        $templateModel = new TourTemplate();
        try {
            $templates = $templateModel->getAll();
        } catch (Throwable $e) {
            $templates = [];
        }

        // Load tours có sẵn để có thể copy itinerary/policy
        $tourModel = new Tour();
        try {
            $existingTours = $tourModel->listWithCategory([]);
        } catch (Throwable $e) {
            $existingTours = [];
        }

        $view = 'admin/tours-create';
        $title = 'Thêm tour';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function store(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $itinerary = trim($_POST['itinerary'] ?? '');
        $policy = trim($_POST['policy'] ?? '');
        $tour_status = trim($_POST['tour_status'] ?? '');

        if (empty($name) || $category_id <= 0) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin.';
            header('Location: ' . BASE_URL . '?action=tours-create');
            exit;
        }

        try {
            // Handle image upload if provided
            $imageDbPath = null;
            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['image'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $_SESSION['error'] = 'Lỗi upload ảnh.';
                    header('Location: ' . BASE_URL . '?action=tours-create');
                    exit;
                }

                $originalName = $file['name'];
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                $ext = $ext ? strtolower($ext) : '';
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if ($ext && !in_array($ext, $allowed)) {
                    $_SESSION['error'] = 'Định dạng ảnh không hợp lệ.';
                    header('Location: ' . BASE_URL . '?action=tours-create');
                    exit;
                }

                if (!is_dir(PATH_ASSETS_UPLOADS)) {
                    @mkdir(PATH_ASSETS_UPLOADS, 0777, true);
                }

                $filename = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
                $target = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($file['tmp_name'], $target)) {
                    $_SESSION['error'] = 'Không thể lưu file ảnh.';
                    header('Location: ' . BASE_URL . '?action=tours-create');
                    exit;
                }

                // store relative web path like 'assets/uploads/filename'
                $imageDbPath = 'assets/uploads/' . $filename;
            }

            $tourModel = new Tour();
            $newId = $tourModel->insert(
                $name,
                $category_id,
                $price,
                $description ?: null,
                $itinerary ?: null,
                $policy ?: null,
                $imageDbPath,
                $tour_status !== '' ? $tour_status : null
            );
            if ($newId) {
                $_SESSION['success'] = 'Thêm tour thành công.';
            } else {
                $_SESSION['error'] = 'Không thể lưu tour.';
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Lỗi khi lưu tour.';
        }

        header('Location: ' . BASE_URL . '?action=tours');
        exit;
    }

    public function delete(): void
    {
        $this->ensureAuthenticated();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        try {
            $tourModel = new Tour();
            // Try to remove image file if exists
            try {
                $existing = $tourModel->find($id);
                if (!empty($existing) && !empty($existing['image'])) {
                    $filename = basename($existing['image']);
                    $full = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($full)) {
                        @unlink($full);
                    }
                }
            } catch (Throwable $_e) {
                // ignore file deletion errors
            }

            // Delete row
            $tourModel->delete($id);

            // Resequence ids to be contiguous starting from 1
            $tourModel->resequenceIds();
            $_SESSION['success'] = 'Đã xóa tour.';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Lỗi khi xóa tour.';
        }

        header('Location: ' . BASE_URL . '?action=tours');
        exit;
    }

    public function getTourInfo(): void
    {
        $this->ensureAuthenticated();

        header('Content-Type: application/json');

        $type = $_GET['type'] ?? ''; // 'template', 'tour', hoặc 'category'
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            if ($type === 'category' && $id > 0) {
                // Lấy template theo category_id
                $templateModel = new TourTemplate();
                $data = $templateModel->findByCategoryId($id);
                if ($data) {
                    echo json_encode([
                        'success' => true,
                        'itinerary' => $data['default_itinerary'] ?? '',
                        'policy' => $data['default_policy'] ?? ''
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Không tìm thấy template cho danh mục này.',
                        'itinerary' => '',
                        'policy' => ''
                    ]);
                }
            } elseif ($type === 'template' && $id > 0) {
                $templateModel = new TourTemplate();
                $data = $templateModel->find($id);
                if ($data) {
                    echo json_encode([
                        'success' => true,
                        'itinerary' => $data['default_itinerary'] ?? '',
                        'policy' => $data['default_policy'] ?? ''
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Template không tìm thấy.']);
                }
            } elseif ($type === 'tour' && $id > 0) {
                $tourModel = new Tour();
                $data = $tourModel->find($id);
                if ($data) {
                    echo json_encode([
                        'success' => true,
                        'itinerary' => $data['itinerary'] ?? '',
                        'policy' => $data['policy'] ?? ''
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Tour không tìm thấy.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Tham số không hợp lệ.']);
            }
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thông tin.']);
        }
        exit;
    }

    public function getItineraryJson(): void
    {
        $this->ensureAuthenticated();
        header('Content-Type: application/json');

        $tourId = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
        $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;

        if ($tourId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Tour ID không hợp lệ.']);
            exit;
        }

        try {
            $tourModel = new Tour();
            $tour = $tourModel->find($tourId);
            if (!$tour) {
                echo json_encode(['success' => false, 'message' => 'Tour không tồn tại.']);
                exit;
            }

            $itineraries = $tourModel->getItineraryByTourId($tourId, $bookingId);
            
            // Group by day
            $groupedByDay = [];
            foreach ($itineraries as $item) {
                $dayNum = max(1, (int)($item['day_number'] ?? 1));
                if (!isset($groupedByDay[$dayNum])) {
                    $groupedByDay[$dayNum] = [];
                }
                $groupedByDay[$dayNum][] = $item;
            }
            ksort($groupedByDay);

            echo json_encode([
                'success' => true,
                'tour' => [
                    'id' => $tour['id'],
                    'name' => $tour['name'],
                ],
                'itineraries' => $itineraries,
                'groupedByDay' => $groupedByDay
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy lịch trình.']);
        }
        exit;
    }

    private function ensureAuthenticated(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }
    }
    public function detail()
    {
        if(session_status() === PHP_SESSION_NONE){ session_start(); }
        if(!isset($_SESSION['user'])){ header('Location: ' . BASE_URL . '?action=login'); exit; }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            $_SESSION['error'] = 'Không tìm thấy ID Tour';
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        $tourModel = new Tour();
        
        // 1. Lấy thông tin chung tour
        $tour = $tourModel->find($id);
        
        if (!$tour) {
            $_SESSION['error'] = 'Tour không tồn tại';
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        // 2. Gọi View
        $view = 'admin/tours-detail'; 
        $title = 'Chi tiết tour: ' . $tour['name'];
        $hideNavbar = true;
        require_once PATH_VIEW . 'main.php'; 
    }
    // Mở file controllers/TourController.php và thêm các phương thức sau:

    public function editItinerary(): void
    {
        $this->ensureAuthenticated();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID lịch trình không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        $tourModel = new Tour();
        $itinerary = $tourModel->getItineraryById($id);

        if (!$itinerary) {
            $_SESSION['error'] = 'Lịch trình không tồn tại.';
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        // Lấy thông tin tour để hiển thị tên và link quay lại
        $tour = $tourModel->find($itinerary['tour_id']);

        $view = 'admin/tours-itinerary-edit';
        $title = 'Sửa lịch trình: ' . ($tour['name'] ?? '');
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function updateItinerary(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $tour_id = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
        $day_number = (int)($_POST['day_number'] ?? 1);
        $time_start = trim($_POST['time_start'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if ($id <= 0 || empty($title)) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin.';
            header('Location: ' . BASE_URL . '?action=tours-itinerary-edit&id=' . $id);
            exit;
        }

        $tourModel = new Tour();
        $result = $tourModel->updateItinerary($id, $day_number, $time_start, $title, $description, $location);

        if ($result) {
            // Nếu tour_id bị thiếu, lấy lại từ DB để redirect đúng
            if ($tour_id <= 0) {
                $updatedItinerary = $tourModel->getItineraryById($id);
                $tour_id = $updatedItinerary['tour_id'] ?? 0;
            }

            $_SESSION['success'] = 'Cập nhật lịch trình thành công.';
            // Nếu có booking_id, redirect về bookings-detail, ngược lại về tours-detail
            $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : (isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0);
            if ($booking_id > 0) {
                header('Location: ' . BASE_URL . '?action=bookings-detail&id=' . $booking_id);
            } else {
                header('Location: ' . BASE_URL . '?action=tours-detail&id=' . $tour_id);
            }
        } else {
            $errorMsg = 'Lỗi khi cập nhật dữ liệu.';
            if (isset($tourModel->lastError) && !empty($tourModel->lastError)) {
                $errorMsg .= ' Chi tiết: ' . $tourModel->lastError;
            }
            $_SESSION['error'] = $errorMsg;
            header('Location: ' . BASE_URL . '?action=tours-itinerary-edit&id=' . $id);
        }
        exit;
    }

    public function deleteItinerary(): void
    {
        $this->ensureAuthenticated();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $tour_id = isset($_GET['tour_id']) ? (int) $_GET['tour_id'] : 0;

        if ($id <= 0) {
            $_SESSION['error'] = 'ID không hợp lệ.';
            if ($tour_id > 0) {
                header('Location: ' . BASE_URL . '?action=tours-detail&id=' . $tour_id);
            } else {
                header('Location: ' . BASE_URL . '?action=tours');
            }
            exit;
        }

        $tourModel = new Tour();
        
        // Nếu chưa có tour_id, thử lấy từ DB trước khi xóa
        if ($tour_id <= 0) {
            $itinerary = $tourModel->getItineraryById($id);
            $tour_id = $itinerary['tour_id'] ?? 0;
        }

        $result = $tourModel->deleteItinerary($id);

        if ($result) {
            $_SESSION['success'] = 'Đã xóa lịch trình.';
        } else {
            $_SESSION['error'] = 'Lỗi khi xóa lịch trình.';
        }

        // Nếu có booking_id, redirect về bookings-detail, ngược lại về tours-detail
        $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
        if ($booking_id > 0) {
            header('Location: ' . BASE_URL . '?action=bookings-detail&id=' . $booking_id);
        } elseif ($tour_id > 0) {
            header('Location: ' . BASE_URL . '?action=tours-detail&id=' . $tour_id);
        } else {
            header('Location: ' . BASE_URL . '?action=tours');
        }
        exit;
    }

    public function logsList(): void
    {
        $this->ensureAuthenticated();
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $role = strtolower($_SESSION['user']['role'] ?? '');
        if ($role !== 'admin') {
            header('Location: ' . BASE_URL . '?action=partner-logs');
            exit;
        }

        $tourId = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
        $guideId = isset($_GET['guide_id']) ? (int)$_GET['guide_id'] : 0;
        $dayFilter = isset($_GET['day']) ? (int)$_GET['day'] : 0;
        $typeFilter = isset($_GET['log_type']) ? trim((string)$_GET['log_type']) : '';
        $statusFilter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
        $view = 'admin/tour-logs';
        $title = 'Nhật ký tour';
        $hideNavbar = true;
        if ($tourId <= 0) {
            try {
                $tourModel = new Tour();
                $keyword = trim($_GET['keyword'] ?? '');
                $filters = [];
                if ($keyword !== '') { $filters['keyword'] = $keyword; }
                $tours = $tourModel->listWithCategory($filters);
            } catch (Throwable $e) {
                $tours = [];
            }
            $tour = null; $logs = []; $itinerary = []; $guides = [];
            require_once PATH_VIEW . 'main.php';
            return;
        }

        $tourModel = new Tour();
        $logModel = new TourLog();
        $guideModel = new Guide();
        $bookingModel = new Booking();

        try {
            $tour = $tourModel->find($tourId);
            
            // Lấy danh sách bookings của tour
            $bookings = $bookingModel->listSimple(['tour_id' => $tourId]);
            
            // Lấy booking_id từ filter (nếu có)
            $bookingIdFilter = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
            
            // Lấy nhật ký: nếu có booking_id filter thì lấy theo booking, không thì lấy theo tour
            if ($bookingIdFilter > 0) {
                $logs = $logModel->getByBookingId($bookingIdFilter);
                // Lấy itinerary của booking cụ thể
                $itinerary = $tourModel->getItineraryByTourId($tourId, $bookingIdFilter);
            } else {
                $logs = $logModel->getByTourId($tourId, $guideId ?: null);
                // Lấy itinerary chung của tour
                $itinerary = $tourModel->getItineraryByTourId($tourId);
            }
            
            $guides = $guideModel->listAssignedByTour($tourId);
            if (empty($guides)) { $guides = $guideModel->list(); }
            
            if ($dayFilter > 0) {
                $map = [];
                foreach ($itinerary as $item) { $map[(int)$item['id']] = (int)($item['day_number'] ?? 0); }
                $logs = array_values(array_filter($logs, function($l) use ($map, $dayFilter) {
                    $iid = (int)($l['itinerary_id'] ?? 0);
                    return $iid && isset($map[$iid]) && $map[$iid] === $dayFilter;
                }));
            }
            if ($typeFilter !== '') {
                $logs = array_values(array_filter($logs, function($l) use ($typeFilter) {
                    return isset($l['log_type']) && $l['log_type'] === $typeFilter;
                }));
            }
            if ($statusFilter !== '') {
                $logs = array_values(array_filter($logs, function($l) use ($statusFilter) {
                    return isset($l['status']) && $l['status'] === $statusFilter;
                }));
            }
        } catch (Throwable $e) {
            $tour = null; $logs = []; $itinerary = []; $guides = []; $bookings = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function logsEdit(): void
    {
        $this->ensureAuthenticated();
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $tourId = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
        
        if ($id <= 0) {
            $_SESSION['error'] = 'ID không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=tour-logs-list' . ($tourId > 0 ? '&tour_id=' . $tourId : ''));
            exit;
        }
        
        $logModel = new TourLog();
        $log = $logModel->find($id);
        
        if (!$log) {
            $_SESSION['error'] = 'Nhật ký không tồn tại.';
            header('Location: ' . BASE_URL . '?action=tour-logs-list' . ($tourId > 0 ? '&tour_id=' . $tourId : ''));
            exit;
        }
        
        // Nếu tour_id không được truyền, lấy từ log
        if ($tourId <= 0) {
            $tourId = (int)($log['tour_id'] ?? 0);
        }
        
        if ($tourId <= 0) {
            $_SESSION['error'] = 'Tour ID không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=tour-logs-list');
            exit;
        }
        
        $tourModel = new Tour();
        $tour = $tourModel->find($tourId);
        
        if (!$tour) {
            $_SESSION['error'] = 'Tour không tồn tại.';
            header('Location: ' . BASE_URL . '?action=tour-logs-list');
            exit;
        }
        
        // Lấy itinerary để có thể chọn ngày (nếu cần)
        $itinerary = $tourModel->getItineraryByTourId($tourId);
        
        // Lấy danh sách bookings (nếu cần)
        $bookingModel = new Booking();
        $bookings = $bookingModel->listSimple(['tour_id' => $tourId]);
        
        $view = 'admin/tour-logs';
        $title = 'Sửa nhật ký tour';
        $hideNavbar = true;
        $editingLog = $log;
        
        require_once PATH_VIEW . 'main.php';
    }

    public function logsStore(): void
    {
        $this->ensureAuthenticated();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $role = strtolower($_SESSION['user']['role'] ?? '');
        $tourId = (int)($_POST['tour_id'] ?? 0);
        $logType = trim($_POST['log_type'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
        $guideId = isset($_POST['guide_id']) ? (int)$_POST['guide_id'] : 0;
        $itineraryId = isset($_POST['itinerary_id']) ? (int)$_POST['itinerary_id'] : 0;
        $logDate = trim($_POST['log_date'] ?? '');
        $weather = trim($_POST['weather'] ?? '');
        $health = trim($_POST['health_status'] ?? '');
        $activities = trim($_POST['special_activities'] ?? '');
        $handling = trim($_POST['handling_notes'] ?? '');
        $feedback = trim($_POST['customer_feedback'] ?? '');
        $events = trim($_POST['events'] ?? '');
        $highlights = trim($_POST['highlights'] ?? '');
        $ratingCoordination = isset($_POST['rating_coordination']) ? (int)$_POST['rating_coordination'] : null;
        $ratingSpirit = isset($_POST['rating_spirit']) ? (int)$_POST['rating_spirit'] : null;
        $ratingComment = trim($_POST['rating_comment'] ?? '');
        $dayNumber = isset($_POST['day_number']) ? (int)$_POST['day_number'] : 0;
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $imageDbPath = null;
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Lỗi upload ảnh.';
                header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
                exit;
            }
            $originalName = $file['name'];
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $ext = $ext ? strtolower($ext) : '';
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if ($ext && !in_array($ext, $allowed)) {
                $_SESSION['error'] = 'Định dạng ảnh không hợp lệ.';
                header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
                exit;
            }
            if (!is_dir(PATH_ASSETS_UPLOADS)) { @mkdir(PATH_ASSETS_UPLOADS, 0777, true); }
            $filename = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
            $target = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                $_SESSION['error'] = 'Không thể lưu file ảnh.';
                header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
                exit;
            }
            $imageDbPath = 'assets/uploads/' . $filename;
        }

        if ($role === 'hdv') {
            // HDV có thể chọn log_type từ form, mặc định là 'daily'
            if ($logType === '') {
                $logType = 'daily';
            }
            
            if ($tourId <= 0 || $logDate === '' || $title === '' || $description === '') {
                $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc (Tour, Ngày, Tiêu đề, Mô tả).';
                header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
                exit;
            }
            
            // Validate theo loại nhật ký
            if ($logType === 'incident' && $handling === '') {
                $_SESSION['error'] = 'Vui lòng nhập cách xử lý cho sự cố.';
                header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
                exit;
            }
            if ($logType === 'feedback' && $feedback === '') {
                $_SESSION['error'] = 'Vui lòng nhập phản hồi của khách hàng.';
                header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
                exit;
            }
            
            if ($guideId <= 0) {
                try {
                    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                    $stmt = $pdo->prepare('SELECT id FROM hdv WHERE user_id = :uid LIMIT 1');
                    $stmt->execute([':uid' => (int)($_SESSION['user']['id'] ?? 0)]);
                    $r = $stmt->fetch(); if ($r && isset($r['id'])) { $guideId = (int)$r['id']; $_SESSION['user']['guide_id'] = $guideId; }
                } catch (Throwable $e) {}
            }
            
            // Tự động lấy booking_id nếu chưa chọn
            if ($bookingId <= 0 && $tourId > 0 && $guideId > 0) {
                try {
                    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                    $hasAssign = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tour_assignments'")->fetchColumn() > 0;
                    $hasBookings = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'bookings'")->fetchColumn() > 0;
                    if ($hasAssign && $hasBookings) {
                        $stmt = $pdo->prepare('SELECT b.id FROM bookings b 
                                               INNER JOIN tour_assignments ta ON ta.booking_id = b.id 
                                               WHERE b.tour_id = :tid AND (ta.guide_id = :gid OR ta.HDV_ID = :gid)
                                               ORDER BY ta.assign_date DESC, b.start_date DESC 
                                               LIMIT 1');
                        $stmt->execute([':tid' => $tourId, ':gid' => $guideId]);
                        $r = $stmt->fetch();
                        if ($r && isset($r['id'])) {
                            $bookingId = (int)$r['id'];
                        }
                    }
                } catch (Throwable $e) {
                }
            }
        } else {
            if ($tourId <= 0 || $logType === '' || $title === '') {
                $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
                header('Location: ' . BASE_URL . '?action=tour-logs-list&tour_id=' . $tourId);
                exit;
            }
            if (in_array($logType, ['incident','feedback','timeline'], true) && $itineraryId <= 0) {
                $_SESSION['error'] = 'Vui lòng chọn mục lịch trình (ngày) cho nhật ký.';
                header('Location: ' . BASE_URL . '?action=tour-logs-list&tour_id=' . $tourId);
                exit;
            }
        }

        // Xây dựng mô tả đầy đủ dựa trên loại nhật ký
        $parts = [];
        if (!empty($description)) { $parts[] = $description; }
        
        // Xử lý theo loại nhật ký
        switch ($logType) {
            case 'incident':
                if (!empty($weather)) $parts[] = 'Thời tiết: ' . $weather;
                if (!empty($health)) $parts[] = 'Sức khỏe khách: ' . $health;
                if (!empty($activities)) $parts[] = 'Hoạt động đặc biệt: ' . $activities;
                if (!empty($handling)) $parts[] = 'Cách xử lý: ' . $handling;
                break;
            case 'feedback':
                if (!empty($feedback)) $parts[] = 'Phản hồi khách: ' . $feedback;
                break;
            case 'rating':
                if (!empty($ratingCoordination)) $parts[] = 'Đánh giá phối hợp: ' . $ratingCoordination . '/5';
                if (!empty($ratingSpirit)) $parts[] = 'Tinh thần làm việc: ' . $ratingSpirit . '/5';
                if (!empty($ratingComment)) $parts[] = 'Bình luận đánh giá: ' . $ratingComment;
                break;
            case 'timeline':
                if (!empty($events)) $parts[] = 'Sự kiện: ' . $events;
                if (!empty($highlights)) $parts[] = 'Hoạt động nổi bật: ' . $highlights;
                if (!empty($feedback)) $parts[] = 'Phản hồi khách: ' . $feedback;
                break;
            case 'daily':
            default:
                // Nhật ký ngày - có thể thêm thông tin thời tiết, hoạt động nếu có
                if (!empty($weather)) $parts[] = 'Thời tiết: ' . $weather;
                if (!empty($activities)) $parts[] = 'Hoạt động đặc biệt: ' . $activities;
                break;
        }
        
        $fullDesc = trim(implode("\n", array_filter($parts)));

        $logModel = new TourLog();
        if ($role === 'hdv' && $guideId <= 0 && isset($_SESSION['user']['guide_id'])) {
            $guideId = (int)$_SESSION['user']['guide_id'];
        }
        $data = [
            'tour_id' => $tourId,
            'log_type' => $logType,
            'title' => $title,
            'description' => $fullDesc,
            'status' => $status,
            'rating' => $rating,
        ];
        if ($guideId > 0) { $data['guide_id'] = $guideId; }
        if ($bookingId > 0) { $data['booking_id'] = $bookingId; }
        if ($itineraryId > 0) { $data['itinerary_id'] = $itineraryId; }
        if ($logDate !== '') { $data['log_date'] = $logDate; }
        if ($imageDbPath) { $data['image_path'] = $imageDbPath; }
        $res = $logModel->create($data);
        if ($res) {
            $_SESSION['success'] = 'Thêm nhật ký thành công.';
            $_SESSION['last_log_inserted'] = [
                'id' => $res,
                'tour_id' => $tourId,
                'title' => $title,
                'description' => $fullDesc,
                'log_date' => $logDate,
                'image_path' => $imageDbPath,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        } else {
            $_SESSION['success'] = 'Không thể thêm nhật ký.';
        }
        $redirectUrl = BASE_URL . ($role==='admin' ? ('?action=tour-logs-list&tour_id=' . $tourId) : ('?action=partner-logs&tour_id=' . $tourId));
        if ($bookingId > 0) {
            $redirectUrl .= '&booking_id=' . $bookingId;
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function logsUpdate(): void
    {
        $this->ensureAuthenticated();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }
        
        // Xác định role và redirect URL base
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $role = strtolower($_SESSION['user']['role'] ?? '');
        $isAdmin = ($role === 'admin');
        
        $id = (int)($_POST['id'] ?? 0);
        $tourId = (int)($_POST['tour_id'] ?? 0);
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');
        $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;
        $guideId = isset($_POST['guide_id']) ? (int)$_POST['guide_id'] : 0;
        $itineraryId = isset($_POST['itinerary_id']) ? (int)$_POST['itinerary_id'] : 0;
        $logDate = trim($_POST['log_date'] ?? '');
        
        // Helper function để tạo redirect URL
        $getRedirectUrl = function($tourId, $bookingId = 0) use ($isAdmin) {
            $url = BASE_URL . ($isAdmin ? ('?action=tour-logs-list&tour_id=' . $tourId) : ('?action=partner-logs&tour_id=' . $tourId));
            if ($bookingId > 0) {
                $url .= '&booking_id=' . $bookingId;
            }
            return $url;
        };
        
        // Helper function để redirect về trang edit khi có lỗi
        $getEditUrl = function($id, $tourId, $bookingId = 0) use ($isAdmin) {
            $url = BASE_URL . ($isAdmin ? ('?action=tour-logs-edit&id=' . $id . '&tour_id=' . $tourId) : ('?action=partner-logs&tour_id=' . $tourId . '&edit_id=' . $id));
            if ($bookingId > 0) {
                $url .= '&booking_id=' . $bookingId;
            }
            return $url;
        };
        
        if ($id <= 0 || $tourId <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ.';
            header('Location: ' . $getEditUrl($id, $tourId, $bookingId));
            exit;
        }
        
        if (empty($title) && empty($description)) {
            $_SESSION['error'] = 'Vui lòng nhập tiêu đề hoặc mô tả.';
            header('Location: ' . $getEditUrl($id, $tourId, $bookingId));
            exit;
        }
        
        if ($title === '' && $description !== '') { 
            $title = mb_substr($description, 0, 80); 
        }
        
        $logModel = new TourLog();
        $existing = $logModel->find($id);
        
        if (!$existing) {
            $_SESSION['error'] = 'Nhật ký không tồn tại.';
            header('Location: ' . $getEditUrl($id, $tourId, $bookingId));
            exit;
        }
        
        // Khi edit: giữ nguyên itinerary_id cũ nếu không có giá trị mới
        // Xóa bỏ validation bắt buộc itinerary_id khi edit
        $currentI = (int)($existing['itinerary_id'] ?? 0);
        if ($itineraryId <= 0 && $currentI > 0) {
            $itineraryId = $currentI; // Giữ nguyên itinerary_id cũ
        }
        
        $imageDbPath = null; 
        $oldImage = null;
        
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($existing && !empty($existing['image_path'])) { 
                $oldImage = $existing['image_path']; 
            }
            $file = $_FILES['image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Lỗi upload ảnh.';
                header('Location: ' . $getEditUrl($id, $tourId, $bookingId));
                exit;
            }
            $originalName = $file['name']; 
            $ext = pathinfo($originalName, PATHINFO_EXTENSION); 
            $ext = $ext ? strtolower($ext) : '';
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if ($ext && !in_array($ext, $allowed)) {
                $_SESSION['error'] = 'Định dạng ảnh không hợp lệ.';
                header('Location: ' . $getEditUrl($id, $tourId, $bookingId));
                exit;
            }
            if (!is_dir(PATH_ASSETS_UPLOADS)) { 
                @mkdir(PATH_ASSETS_UPLOADS, 0777, true); 
            }
            $filename = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
            $target = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($file['tmp_name'], $target)) {
                $_SESSION['error'] = 'Không thể lưu file ảnh.';
                header('Location: ' . $getEditUrl($id, $tourId, $bookingId));
                exit;
            }
            $imageDbPath = 'assets/uploads/' . $filename;
        }
        
        try {
            $data = [
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'rating' => $rating,
            ];
            if ($guideId > 0) { $data['guide_id'] = $guideId; }
            // Chỉ cập nhật itinerary_id nếu có giá trị (đã được xử lý ở trên)
            if ($itineraryId > 0) { 
                $data['itinerary_id'] = $itineraryId; 
            }
            if ($logDate !== '') { $data['log_date'] = $logDate; }
            if ($imageDbPath) { $data['image_path'] = $imageDbPath; }
            
            $ok = $logModel->update($id, $data);
            
            if (!$ok) {
                $_SESSION['error'] = 'Không thể cập nhật nhật ký. Vui lòng thử lại.';
                // Redirect về trang edit để người dùng có thể sửa lại
                $editUrl = BASE_URL . ($isAdmin ? ('?action=tour-logs-edit&id=' . $id . '&tour_id=' . $tourId) : ('?action=partner-logs&tour_id=' . $tourId . '&edit_id=' . $id));
                if ($bookingId > 0) {
                    $editUrl .= '&booking_id=' . $bookingId;
                }
                header('Location: ' . $editUrl);
                exit;
            }
            
            if ($ok && $imageDbPath && $oldImage) {
                $oldFile = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($oldImage);
                if (is_file($oldFile)) { 
                    @unlink($oldFile); 
                }
            }
            
            $_SESSION['success'] = 'Cập nhật nhật ký thành công.';
            header('Location: ' . $getRedirectUrl($tourId, $bookingId));
            exit;
        } catch (Throwable $e) {
            error_log('TourController::logsUpdate error: ' . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật nhật ký: ' . $e->getMessage();
            // Redirect về trang edit để người dùng có thể sửa lại
            $editUrl = BASE_URL . ($isAdmin ? ('?action=tour-logs-edit&id=' . $id . '&tour_id=' . $tourId) : ('?action=partner-logs&tour_id=' . $tourId . '&edit_id=' . $id));
            if ($bookingId > 0) {
                $editUrl .= '&booking_id=' . $bookingId;
            }
            header('Location: ' . $editUrl);
            exit;
        }
    }

    public function logsDelete(): void
    {
        $this->ensureAuthenticated();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $tourId = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
        if ($id <= 0 || $tourId <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }
        $logModel = new TourLog();
        $ok = $logModel->delete($id);
        $_SESSION['success'] = $ok ? 'Đã xóa nhật ký.' : 'Không thể xóa nhật ký.';
        header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
        exit;
    }
}
