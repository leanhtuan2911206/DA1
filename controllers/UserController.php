<?php

class UserController
{
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        $view = 'admin/users';
        $title = 'Quản lý tài khoản';
        $hideNavbar = true;

        $users = [];
        try {
            $userModel = new User();
            $users = $userModel->getAll();
        } catch (Throwable $e) {
            $users = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        $view = 'admin/users-create';
        $title = 'Tạo tài khoản mới';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=users-create');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin hợp lệ.';
            header('Location: ' . BASE_URL . '?action=users-create');
            exit;
        }

        try {
            $userModel = new User();

            $exists = $userModel->findByEmail($email);
            if ($exists) {
                $_SESSION['error'] = 'Email đã tồn tại.';
                header('Location: ' . BASE_URL . '?action=users-create');
                exit;
            }

            $created = $userModel->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            if ($created) {
                $_SESSION['success'] = 'Tạo tài khoản thành công!';
                header('Location: ' . BASE_URL . '?action=users');
                exit;
            }

            $_SESSION['error'] = 'Không thể tạo tài khoản.';
            header('Location: ' . BASE_URL . '?action=users-create');
            exit;
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Đã xảy ra lỗi.';
            header('Location: ' . BASE_URL . '?action=users-create');
            exit;
        }
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Tài khoản không tồn tại.';
            header('Location: ' . BASE_URL . '?action=users');
            exit;
        }

        $user = null;
        try {
            $userModel = new User();
            $user = $userModel->find($id);
        } catch (Throwable $e) {
            $user = null;
        }

        $view = 'admin/users-edit';
        $title = 'Sửa tài khoản';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=users');
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=users-edit&id=' . $id);
            exit;
        }

        try {
            $userModel = new User();

            $exists = $userModel->findByEmail($email);
            if ($exists && (int)$exists['id'] !== $id) {
                $_SESSION['error'] = 'Email đã được sử dụng.';
                header('Location: ' . BASE_URL . '?action=users-edit&id=' . $id);
                exit;
            }

            $updated = $userModel->update($id, [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            if ($updated) {
                $_SESSION['success'] = 'Cập nhật tài khoản thành công!';
                header('Location: ' . BASE_URL . '?action=users');
                exit;
            }

            $_SESSION['error'] = 'Không thể cập nhật tài khoản.';
            header('Location: ' . BASE_URL . '?action=users-edit&id=' . $id);
            exit;
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Đã xảy ra lỗi.';
            header('Location: ' . BASE_URL . '?action=users-edit&id=' . $id);
            exit;
        }
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Tài khoản không tồn tại.';
            header('Location: ' . BASE_URL . '?action=users');
            exit;
        }

        try {
            $userModel = new User();
            $deleted = $userModel->delete($id);
            $_SESSION['success'] = $deleted ? 'Đã xóa tài khoản.' : 'Xóa tài khoản thất bại.';
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Đã xảy ra lỗi khi xóa.';
        }

        header('Location: ' . BASE_URL . '?action=users');
        exit;
    }
}
