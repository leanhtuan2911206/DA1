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
                $debug['message'] = "Tour id {$newId} được tìm thấy trong danh sách.";
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

        if ($id <= 0 || empty($name) || $category_id <= 0) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin.';
            header('Location: ' . BASE_URL . '?action=tours-edit&id=' . $id);
            exit;
        }

        try {
            $tourModel = new Tour();
            $tourModel->update($id, $name, $category_id, $price, $description ?: null, $itinerary ?: null, $policy ?: null);
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
            $newId = $tourModel->insert($name, $category_id, $price, $description ?: null, $itinerary ?: null, $policy ?: null, $imageDbPath);
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
}

