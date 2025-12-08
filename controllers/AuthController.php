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
            // Cho phép đăng nhập bằng email hoặc số điện thoại (contact)
            if (empty($email)) {
                $error = 'Vui lòng nhập email hoặc số điện thoại!';
            } elseif (empty($password)) {
                $error = 'Vui lòng nhập mật khẩu!';
            } else {
                try {
                    $userModel = new User();
                    $user = $userModel->findByEmail($email);
                    
                    // Nếu không tìm thấy theo email, thử tìm theo contact trong bảng hdv
                    if (!$user) {
                        try {
                            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                            
                            // Tìm HDV có LienHe (contact) trùng với email đăng nhập
                            $stmt = $pdo->prepare('SELECT user_id FROM hdv WHERE LienHe = :contact LIMIT 1');
                            $stmt->execute([':contact' => $email]);
                            $row = $stmt->fetch();
                            if ($row && !empty($row['user_id'])) {
                                $user = $userModel->find((int)$row['user_id']);
                            }
                        } catch (Throwable $e) {
                            error_log('AuthController::login - Error finding user by contact: ' . $e->getMessage());
                        }
                    }

                    // Kiểm tra người dùng tồn tại và mật khẩu
                    $passwordValid = false;
                    if ($user) {
                        $storedPassword = trim($user['password'] ?? '');
                        $inputPassword = trim($password);
                        
                        // Kiểm tra xem password có được hash hay chưa (bắt đầu bằng $2y$, $2a$, hoặc $2b$)
                        $isHashed = preg_match('/^\$2[ayb]\$/', $storedPassword);
                        
                        if ($isHashed) {
                            // Password đã được hash, sử dụng password_verify()
                            $passwordValid = password_verify($inputPassword, $storedPassword);
                        } else {
                            // Password chưa được hash (legacy), so sánh trực tiếp
                            $passwordValid = ($inputPassword === $storedPassword);
                        }
                    }
                    
                    if ($user && $passwordValid) { 
                        // Đăng nhập thành công
                        // Bắt đầu session và lưu thông tin người dùng
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $role = isset($user['role']) ? strtolower((string)$user['role']) : '';
                        $guideId = isset($user['guide_id']) ? (int)$user['guide_id'] : 0;
                        
                        // Nếu là HDV, tìm HDV_ID từ bảng hdv
                        if ($role === 'hdv' && $guideId === 0) {
                            try {
                                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                                $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                                
                                // Ưu tiên 1: Tìm HDV_ID từ user_id (cột user_id trong bảng hdv)
                                $stmt = $pdo->prepare('SELECT HDV_ID FROM hdv WHERE user_id = :uid LIMIT 1');
                                $stmt->execute([':uid' => (int)$user['id']]);
                                $row = $stmt->fetch();
                                if ($row && isset($row['HDV_ID'])) { 
                                    $guideId = (int)$row['HDV_ID']; 
                                }
                                
                                // Ưu tiên 2: Nếu không tìm thấy, thử tìm theo LienHe (contact) - vì email đăng nhập có thể là contact
                                if ($guideId === 0) {
                                    $stmt = $pdo->prepare('SELECT HDV_ID FROM hdv WHERE LienHe = :contact LIMIT 1');
                                    $stmt->execute([':contact' => (string)$user['email']]);
                                    $row = $stmt->fetch();
                                    if ($row && isset($row['HDV_ID'])) { 
                                        $guideId = (int)$row['HDV_ID'];
                                        // Cập nhật user_id vào bảng hdv để lần sau tìm nhanh hơn
                                        $updateStmt = $pdo->prepare('UPDATE hdv SET user_id = :uid WHERE HDV_ID = :gid');
                                        $updateStmt->execute([':uid' => (int)$user['id'], ':gid' => $guideId]);
                                    }
                                }
                                
                                // Ưu tiên 3: Nếu vẫn không tìm thấy, thử tìm theo tên (name)
                                if ($guideId === 0 && !empty($user['name'])) {
                                    $stmt = $pdo->prepare('SELECT HDV_ID FROM hdv WHERE HoTen = :name LIMIT 1');
                                    $stmt->execute([':name' => (string)$user['name']]);
                                    $row = $stmt->fetch();
                                    if ($row && isset($row['HDV_ID'])) { 
                                        $guideId = (int)$row['HDV_ID'];
                                        // Cập nhật user_id vào bảng hdv để lần sau tìm nhanh hơn
                                        $updateStmt = $pdo->prepare('UPDATE hdv SET user_id = :uid WHERE HDV_ID = :gid');
                                        $updateStmt->execute([':uid' => (int)$user['id'], ':gid' => $guideId]);
                                    }
                                }
                            } catch (Throwable $e) { 
                                error_log('AuthController::login - Error finding guide_id: ' . $e->getMessage());
                            }
                        }
                        $_SESSION['user'] = [
                            'id'    => $user['id'],
                            'name'  => $user['name'],
                            'email' => $user['email'],
                            'role'  => $role,
                            'guide_id' => $guideId,
                        ];
                        $url = BASE_URL . ($role === 'hdv' ? '?action=partner' : '?action=admin');
                        header('Location: ' . $url);
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