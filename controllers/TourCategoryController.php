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
            
            // 3. Kiểm tra dữ liệu hợp lệ (Validation cơ bản)
            if (empty($name)) {
                $_SESSION['error'] = 'Tên danh mục không được để trống.';
                header('Location:' . BASE_URL . '?action=tour-categories-create');
                exit;
            }

            // 4. Lưu vào Database
            try {
                $model = new TourCategory(); 
                $model->insert($name); // Dùng phương thức insert đã có trong TourCategory Model
                
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
    // Các phương thức khác: create(), store(), edit(), update(), delete() sẽ được thêm sau này
}

