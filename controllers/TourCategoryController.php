<?php 

class TourCategoryController
{
    private function requireAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location:' . BASE_URL . '?action=login');
            exit;
        }
    }

    public function index()
    {
        $this->requireAuth();

        $view = 'admin/tour-categories';
        $title = 'Quản lý Danh mục Tour';
        $hideNavbar = true;
        $listCategories = [];
        try {
            $model = new TourCategory();
            $listCategories = $model->getAll();
        } catch (Throwable $e) {
            $listCategories = [];
        }
        require_once PATH_VIEW . 'main.php'; 
    }
    public function create()
    {
        $this->requireAuth();
        $view = 'admin/tour-categories-create';
        $title = 'Thêm Danh mục Tour';
        $hideNavbar = true;
        require_once PATH_VIEW . 'main.php'; 
    }
    public function store()
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:' . BASE_URL . '?action=tour-categories-create');
            exit;
        }
        
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        
        if (empty($name)) {
            $_SESSION['error'] = 'Tên danh mục không được để trống.';
            header('Location:' . BASE_URL . '?action=tour-categories-create');
            exit;
        }

        try {
            $model = new TourCategory();
            $descValue = $description !== '' ? $description : null;
            $model->insert($name, $descValue);
            $_SESSION['success'] = "Thêm danh mục \"" . htmlspecialchars($name) . "\" thành công!";
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Lỗi database: Không thể thêm danh mục.';
            header('Location:' . BASE_URL . '?action=tour-categories-create');
            exit;
        }

        header('Location:' . BASE_URL . '?action=tour-categories');
        exit;
    }
    public function edit()
    {
        $this->requireAuth();

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
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:' . BASE_URL . '?action=tour-categories');
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

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
            $_SESSION['error'] = 'Không thể cập nhật danh mục lúc này.';
            header('Location:' . BASE_URL . '?action=tour-categories-edit&id=' . $id);
            exit;
        }

        header('Location:' . BASE_URL . '?action=tour-categories');
        exit;
    }

    public function delete()
    {
        $this->requireAuth();

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
            $_SESSION['error'] = 'Không thể xóa danh mục lúc này.';
        }

        header('Location:' . BASE_URL . '?action=tour-categories');
        exit;
    }
}

