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
        } catch (Throwable $e) {
            error_log('TourController::index list error: ' . $e->getMessage());
            $tours = [];
        }

        // Debug: if we just created a tour, verify it's present in the list or exists in DB
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION['new_tour_id'])) {
            $newId = (int) $_SESSION['new_tour_id'];
            $foundInList = false;
            foreach ($tours as $t) {
                if (isset($t['id']) && (int)$t['id'] === $newId) {
                    $foundInList = true;
                    break;
                }
            }

            $debug = [
                'id' => $newId,
                'foundInList' => $foundInList,
                'direct' => null,
                'message' => '',
            ];

            if ($foundInList) {
                $debug['message'] = "Thêm tour thành công.";
                error_log('TourController: ' . $debug['message']);
            } else {
                $debug['message'] = "Tour id {$newId} KHÔNG tìm thấy trong danh sách, sẽ kiểm tra trực tiếp.";
                error_log('TourController: ' . $debug['message']);
                try {
                    $direct = $tourModel->find($newId);
                    $debug['direct'] = $direct ?: null;
                    if ($direct) {
                        error_log('TourController: direct find returned tour: ' . json_encode($direct));
                    } else {
                        error_log('TourController: direct find returned NO ROW for id ' . $newId);
                    }
                } catch (Throwable $e) {
                    error_log('TourController::index direct find error: ' . $e->getMessage());
                    $debug['message'] .= ' Lỗi khi tìm trực tiếp.';
                }
            }

            // Lưu kết quả debug vào session để view có thể hiển thị lên trang
            $_SESSION['new_tour_debug'] = $debug;
            // Xóa id debug cũ
            unset($_SESSION['new_tour_id']);
        }

        try {
            $categories = $categoryModel->getAll();
        } catch (Throwable $e) {
            error_log('TourController::index category error: ' . $e->getMessage());
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
            error_log('TourController::edit category error: ' . $e->getMessage());
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

            $tourModel->update(
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
            if ($imageDbPath && !empty($existing['image'])) {
                $oldFile = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($existing['image']);
                if (file_exists($oldFile)) { @unlink($oldFile); }
            }
            $_SESSION['success'] = 'Cập nhật tour thành công.';
        } catch (Throwable $e) {
            error_log('TourController::update error: ' . $e->getMessage());
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
            error_log('TourController::create category error: ' . $e->getMessage());
            $categories = [];
        }

        // Load tour templates để có thể chọn và tự động điền itinerary/policy
        $templateModel = new TourTemplate();
        try {
            $templates = $templateModel->getAll();
        } catch (Throwable $e) {
            error_log('TourController::create template error: ' . $e->getMessage());
            $templates = [];
        }

        // Load tours có sẵn để có thể copy itinerary/policy
        $tourModel = new Tour();
        try {
            $existingTours = $tourModel->listWithCategory([]);
        } catch (Throwable $e) {
            error_log('TourController::create existing tours error: ' . $e->getMessage());
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
                    error_log('TourController::store - move_uploaded_file failed for ' . $target);
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
                // Keep new inserted id for debugging check in index()
                $_SESSION['new_tour_id'] = $newId;
            } else {
                $_SESSION['error'] = 'Không thể lưu tour.';
                // Lưu lỗi chi tiết vào session để hiển thị trên trang (debug)
                $err = $tourModel->getLastError();
                $_SESSION['new_tour_debug'] = [
                    'id' => null,
                    'foundInList' => false,
                    'direct' => null,
                    'message' => 'Lỗi khi insert. Xem chi tiết dưới.',
                    'error' => $err,
                    'post' => $_POST,
                ];
            }
        } catch (Throwable $e) {
            error_log('TourController::store exception: ' . $e->getMessage());
            error_log('TourController::store POST data: ' . json_encode($_POST));
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
            $ok = $tourModel->resequenceIds();
            if (!$ok) {
                error_log('TourController::delete - resequenceIds failed');
            }

            $_SESSION['success'] = 'Đã xóa tour.';
        } catch (Throwable $e) {
            error_log('TourController::delete error: ' . $e->getMessage());
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
            error_log('TourController::getTourInfo error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thông tin.']);
        }
        exit;
    }

    public function getItineraryJson(): void
    {
        $this->ensureAuthenticated();
        header('Content-Type: application/json');
        $tourId = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
        if ($tourId <= 0) { echo json_encode(['success'=>false,'message'=>'tour_id invalid','items'=>[]]); exit; }
        try {
            $tourModel = new Tour();
            $items = $tourModel->getItineraryByTourId($tourId);
            echo json_encode(['success'=>true,'items'=>$items]);
        } catch (Throwable $e) {
            error_log('TourController::getItineraryJson error: ' . $e->getMessage());
            echo json_encode(['success'=>false,'message'=>'error','items'=>[]]);
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

        // 2. Lấy lịch trình chi tiết
        $itineraries = $tourModel->getItineraryByTourId($id);

        // 3. Gọi View
        $view = 'admin/tours-detail'; 
        $title = 'Chi tiết lịch trình: ' . $tour['name'];
        $hideNavbar = true;
        require_once PATH_VIEW . 'main.php'; 
    }
    // Mở file controllers/TourController.php và thêm các phương thức sau:

    public function createItinerary(): void
    {
        $this->ensureAuthenticated();

        $tour_id = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
        
        $tourModel = new Tour();
        $tour = $tourModel->find($tour_id);

        if (!$tour) {
            $_SESSION['error'] = 'Tour không tồn tại.';
            header('Location: ' . BASE_URL . '?action=tours');
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

        $view = 'admin/tours-itinerary-create';
        $title = 'Thêm lịch trình: ' . $tour['name'];
        $hideNavbar = true; // Ẩn menu cho thoáng

        require_once PATH_VIEW . 'main.php';
    }

    public function storeItinerary(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }

        $tour_id = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
        $day_number = (int)($_POST['day_number'] ?? 1);
        $time_start = trim($_POST['time_start'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if ($tour_id <= 0 || empty($title)) {
            $_SESSION['error'] = 'Vui lòng nhập tên hoạt động.';
            header('Location: ' . BASE_URL . '?action=tours-itinerary-create&tour_id=' . $tour_id);
            exit;
        }

        $tourModel = new Tour();

        // Kiểm tra trùng lặp trước khi thêm
        $existing = $tourModel->findItineraryByDetails($tour_id, $day_number, $time_start, $title);
        if ($existing) {
            $_SESSION['error'] = 'Lịch trình này đã tồn tại (Ngày ' . $day_number . ' - ' . htmlspecialchars($time_start) . ').';
            header('Location: ' . BASE_URL . '?action=tours-itinerary-create&tour_id=' . $tour_id);
            exit;
        }

        $result = $tourModel->insertItinerary($tour_id, $day_number, $time_start, $title, $description, $location);

        if ($result) {
            $_SESSION['success'] = 'Đã thêm lịch trình mới.';
            // Nếu người dùng chọn "Lưu và thêm tiếp"
            if (isset($_POST['save_and_continue'])) {
                header('Location: ' . BASE_URL . '?action=tours-itinerary-create&tour_id=' . $tour_id);
            } else {
                header('Location: ' . BASE_URL . '?action=tours-detail&id=' . $tour_id);
            }
        } else {
            $_SESSION['error'] = 'Lỗi khi lưu dữ liệu.';
            header('Location: ' . BASE_URL . '?action=tours-itinerary-create&tour_id=' . $tour_id);
        }
        exit;
    }

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
            header('Location: ' . BASE_URL . '?action=tours-detail&id=' . $tour_id);
        } else {
            $errorMsg = 'Lỗi khi cập nhật dữ liệu.';
            // Nếu model có lưu lastError, hiển thị ra để debug
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

        if ($tour_id > 0) {
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
                error_log('TourController::logsList list tours error: ' . $e->getMessage());
                $tours = [];
            }
            $tour = null; $logs = []; $itinerary = []; $guides = [];
            require_once PATH_VIEW . 'main.php';
            return;
        }

        $tourModel = new Tour();
        $logModel = new TourLog();
        $guideModel = new Guide();

        try {
            $tour = $tourModel->find($tourId);
            $logs = $logModel->getByTourId($tourId, $guideId ?: null);
            $itinerary = $tourModel->getItineraryByTourId($tourId);
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
            error_log('TourController::logsList error: ' . $e->getMessage());
            $tour = null; $logs = []; $itinerary = []; $guides = [];
        }

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
        $dayNumber = isset($_POST['day_number']) ? (int)$_POST['day_number'] : 0;
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
            $logType = 'daily';
            if ($tourId <= 0 || ($logDate === '' && $dayNumber <= 0 && $itineraryId <= 0) || $note === '') {
                $_SESSION['error'] = 'Vui lòng chọn ngày và nhập nội dung nhật ký.';
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

        if ($role === 'hdv') {
            $fullDesc = $note;
            if ($title === '' && ($itineraryId > 0 || $dayNumber > 0 || $logDate !== '')) { $title = 'Nhật ký ngày'; }
            if ($title === '' && $note !== '') { $title = mb_substr($note, 0, 80); }
            if ($dayNumber > 0) { $fullDesc = 'Ngày: ' . $dayNumber . "\n" . $fullDesc; }
            if ($logDate !== '') { $fullDesc = 'Ngày: ' . $logDate . "\n" . $fullDesc; }
        } else {
            $parts = [];
            if ($weather !== '') { $parts[] = 'Thời tiết: ' . $weather; }
            if ($health !== '') { $parts[] = 'Sức khỏe khách: ' . $health; }
            if ($activities !== '') { $parts[] = 'Hoạt động đặc biệt: ' . $activities; }
            if ($handling !== '') { $parts[] = 'Cách xử lý: ' . $handling; }
            if ($feedback !== '') { $parts[] = 'Phản hồi khách: ' . $feedback; }
            if ($logType === 'rating') {
                if ($ratingCoordination) { $parts[] = 'Đánh giá phối hợp: ' . $ratingCoordination . '/5'; }
                if ($ratingSpirit) { $parts[] = 'Tinh thần làm việc: ' . $ratingSpirit . '/5'; }
            }
            if ($logType === 'timeline') {
                if ($events !== '') { $parts[] = 'Sự kiện: ' . $events; }
                if ($highlights !== '') { $parts[] = 'Hoạt động nổi bật: ' . $highlights; }
                if ($feedback !== '') { $parts[] = 'Phản hồi khách: ' . $feedback; }
            }
            $fullDesc = trim(implode("\n", array_filter([$description, $parts ? implode("\n", $parts) : ''])));
        }

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
        header('Location: ' . BASE_URL . ($role==='admin' ? ('?action=tour-logs-list&tour_id=' . $tourId) : ('?action=partner-logs&tour_id=' . $tourId)));
        exit;
    }

    public function logsUpdate(): void
    {
        $this->ensureAuthenticated();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tours');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        $tourId = (int)($_POST['tour_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
        $guideId = isset($_POST['guide_id']) ? (int)$_POST['guide_id'] : 0;
        $itineraryId = isset($_POST['itinerary_id']) ? (int)$_POST['itinerary_id'] : 0;
        if ($id <= 0 || $tourId <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
            exit;
        }
        if ($title === '' && $description !== '') { $title = mb_substr($description, 0, 80); }
        $logModel = new TourLog();
        $existing = $logModel->find($id);
        if ($existing && in_array($existing['log_type'] ?? '', ['incident','feedback','timeline'], true)) {
            $currentI = (int)($existing['itinerary_id'] ?? 0);
            if ($currentI <= 0 && $itineraryId <= 0) {
                $_SESSION['error'] = 'Nhật ký loại này yêu cầu gắn với một ngày (lịch trình).';
                header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
                exit;
            }
        }
        
        $imageDbPath = null; $oldImage = null;
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $existing = $logModel->find($id);
            if ($existing && !empty($existing['image_path'])) { $oldImage = $existing['image_path']; }
            $file = $_FILES['image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Lỗi upload ảnh.';
                header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
                exit;
            }
            $originalName = $file['name']; $ext = pathinfo($originalName, PATHINFO_EXTENSION); $ext = $ext ? strtolower($ext) : '';
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
        $data = [
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'rating' => $rating,
        ];
        if ($guideId > 0) { $data['guide_id'] = $guideId; }
        if ($itineraryId > 0) { $data['itinerary_id'] = $itineraryId; }
        if ($logDate !== '') { $data['log_date'] = $logDate; }
        if ($imageDbPath) { $data['image_path'] = $imageDbPath; }
        $ok = $logModel->update($id, $data);
        if ($ok && $imageDbPath && $oldImage) {
            $oldFile = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($oldImage);
            if (is_file($oldFile)) { @unlink($oldFile); }
        }
        $_SESSION['success'] = $ok ? 'Cập nhật nhật ký thành công.' : 'Không thể cập nhật nhật ký.';
        header('Location: ' . BASE_URL . '?action=partner-logs&tour_id=' . $tourId);
        exit;
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
