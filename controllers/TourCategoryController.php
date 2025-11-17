<?php 

class TourCategoryController
{
    public function index()
    {
        // 1. Kiểm tra đăng nhập (BẮT BUỘC cho trang admin)
        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
        if(!isset($_SESSION['user'])){
            header('Location:' .BASE_URL .'?action=login');
            exit;
        }

        // 2. Thiết lập View và biến
        $view = 'admin/tour-categories'; 
        $title = 'Quản lý Danh mục Tour';
        $hideNavbar = true; // Ẩn navbar trên cùng, dùng sidebar

        // 3. Lấy dữ liệu từ Model
        $listCategories = [];
        try {
            $model = new TourCategory(); 
            $listCategories = $model->getAll(); 
        } catch (Throwable $e) {
            // Xử lý lỗi - ghi log để debug
            error_log("Database error in TourCategoryController: " . $e->getMessage());
            // Hiển thị lỗi để debug (tạm thời)
            // echo "Error: " . $e->getMessage(); die;
            $listCategories = [];
        }

        // 4. Tải Layout chính (truyền các biến $view, $title, $hideNavbar, $listCategories)
        require_once PATH_VIEW . 'main.php'; 
    }
    public function create()
    {
        // 1. Kiểm tra đăng nhập
        if(session_status() === PHP_SESSION_NONE){ session_start(); }
        if(!isset($_SESSION['user'])){
            header('Location:' .BASE_URL .'?action=login');
            exit;
        }

        // 2. Thiết lập View và biến
        $view = 'admin/tour-categories-create'; // Tên file view mới
        $title = 'Thêm Danh mục Tour';
        $hideNavbar = true; 
        
        // 3. Tải Layout chính
        require_once PATH_VIEW . 'main.php'; 
    }
    public function store()
    {
        // 1. Kiểm tra đăng nhập
        if(session_status() === PHP_SESSION_NONE){ session_start(); }
        if(!isset($_SESSION['user'])){
            header('Location:' .BASE_URL .'?action=login');
            exit;
        }

        // CHỈ XỬ LÝ KHI PHƯƠNG THỨC LÀ POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 2. Lấy và làm sạch dữ liệu
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            // 3. Kiểm tra dữ liệu hợp lệ (Validation cơ bản)
            if (empty($name)) {
                $_SESSION['error'] = 'Tên danh mục không được để trống.';
                header('Location:' . BASE_URL . '?action=tour-categories-create');
                exit;
            }

            // 4. Lưu vào Database
            try {
                $model = new TourCategory(); 
                $model->insert($name, $description !== '' ? $description : null); // Dùng phương thức insert đã có trong TourCategory Model
                
                $_SESSION['success'] = "Thêm danh mục **\"" . htmlspecialchars($name) . "\"** thành công!";
                
            } catch (Throwable $e) {
                error_log("TourCategoryController::store() error: " . $e->getMessage());
                $_SESSION['error'] = 'Lỗi database: Không thể thêm danh mục.';
                header('Location:' . BASE_URL . '?action=tour-categories-create');
                exit;
            }

            // 5. Chuyển hướng về trang danh sách
            header('Location:' . BASE_URL . '?action=tour-categories');
            exit;

        } else {
            // Nếu không phải POST, chuyển hướng về trang thêm mới
            header('Location:' . BASE_URL . '?action=tour-categories-create');
            exit;
        }
    }
    public function edit()
    {
        if(session_status() === PHP_SESSION_NONE){ session_start(); }
        if(!isset($_SESSION['user'])){
            header('Location:' .BASE_URL .'?action=login');
            exit;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Danh mục không hợp lệ.';
            header('Location:' . BASE_URL . '?action=tour-categories');
            exit;
        }

        $model = new TourCategory();
        $category = $model->find($id);

        if (!$category) {
            $_SESSION['error'] = 'Danh mục không tồn tại.';
            header('Location:' . BASE_URL . '?action=tour-categories');
            exit;
        }

        $view = 'admin/tour-categories-edit';
        $title = 'Sửa Danh mục Tour';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function update()
    {
        if(session_status() === PHP_SESSION_NONE){ session_start(); }
        if(!isset($_SESSION['user'])){
            header('Location:' .BASE_URL .'?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:' . BASE_URL . '?action=tour-categories');
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0) {
            $_SESSION['error'] = 'Danh mục không hợp lệ.';
            header('Location:' . BASE_URL . '?action=tour-categories');
            exit;
        }

        if (empty($name)) {
            $_SESSION['error'] = 'Tên danh mục không được để trống.';
            header('Location:' . BASE_URL . '?action=tour-categories-edit&id=' . $id);
            exit;
        }

        try {
            $model = new TourCategory();
            $category = $model->find($id);
            if (!$category) {
                $_SESSION['error'] = 'Danh mục không tồn tại.';
            } else {
                $model->update($id, $name, $description !== '' ? $description : null);
                $_SESSION['success'] = 'Đã cập nhật danh mục "' . htmlspecialchars($name) . '".';
            }
        } catch (Throwable $e) {
            error_log("TourCategoryController::update() error: " . $e->getMessage());
            $_SESSION['error'] = 'Không thể cập nhật danh mục lúc này.';
            header('Location:' . BASE_URL . '?action=tour-categories-edit&id=' . $id);
            exit;
        }

        header('Location:' . BASE_URL . '?action=tour-categories');
        exit;
    }

    public function delete()
    {
        if(session_status() === PHP_SESSION_NONE){ session_start(); }
        if(!isset($_SESSION['user'])){
            header('Location:' .BASE_URL .'?action=login');
            exit;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Danh mục không hợp lệ.';
            header('Location:' . BASE_URL . '?action=tour-categories');
            exit;
        }

        try {
            $model = new TourCategory();
            $category = $model->find($id);

            if (!$category) {
                $_SESSION['error'] = 'Danh mục không tồn tại.';
            } else {
                $model->delete($id);
                $_SESSION['success'] = 'Đã xóa danh mục "' . htmlspecialchars($category['name']) . '".';
            }
        } catch (Throwable $e) {
            error_log("TourCategoryController::delete() error: " . $e->getMessage());
            $_SESSION['error'] = 'Không thể xóa danh mục lúc này.';
        }

        header('Location:' . BASE_URL . '?action=tour-categories');
        exit;
    }
}

