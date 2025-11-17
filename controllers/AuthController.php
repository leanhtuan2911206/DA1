<?php

// Giả sử các file model và cấu hình đã được include/autoload
// Ví dụ: require_once 'models/User.php';

class AuthController
{
    // Hiển thị form đăng nhập và xử lý POST
    public function login()
    {
        $error = '';
        $view = 'auth/login';
        $title = 'Đăng Nhập';
        $hideNavbar = true;
        
        // Xử lý khi người dùng gửi form
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Validation: Kiểm tra dữ liệu đầu vào
            if (empty($email)) {
                $error = 'Vui lòng nhập email!';
            } elseif (empty($password)) {
                $error = 'Vui lòng nhập mật khẩu!';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email không hợp lệ!';
            } else {
                try {
                    $userModel = new User();
                    $user = $userModel->findByEmail($email);

                    // Kiểm tra người dùng tồn tại và mật khẩu
                    // LƯU Ý: Trong thực tế, bạn PHẢI sử dụng hàm băm (hash) mật khẩu như password_verify().
                    if ($user && $password === $user['password']) { 
                        // Đăng nhập thành công
                        // Bắt đầu session và lưu thông tin người dùng
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $_SESSION['user'] = [
                            'id'    => $user['id'],
                            'name'  => $user['name'],
                            'email' => $user['email'],
                            // Thêm các thông tin khác nếu cần
                        ];

                        // Chuyển hướng đến trang quản trị
                        header('Location: ' . BASE_URL . '?action=admin');
                        exit;
                    } else {
                        $error = 'Email hoặc mật khẩu không chính xác!';
                    }
                } catch (Exception $e) {
                    // Xử lý lỗi database hoặc lỗi khác
                    $error = 'Đã xảy ra lỗi. Vui lòng thử lại sau!';
                }
            }
        }

        // Dùng layout chính để hiển thị form
        require_once PATH_VIEW . 'main.php';
    }

    // Xử lý Đăng Xuất
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Xóa tất cả các biến session
        $_SESSION = [];
        // Hủy session
        session_destroy();
        // Chuyển hướng về trang chủ hoặc trang đăng nhập
        header('Location: ' . BASE_URL);
        exit;
    }
}