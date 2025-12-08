<?php

class GuideController
{
    public function index(): void
    {
        $this->ensureAuthenticated();

        $filters = [
            'keyword'  => trim($_GET['keyword'] ?? ''),
            'status'   => $_GET['status'] ?? '',
            'language' => trim($_GET['language'] ?? ''),
            'gender'   =>  trim($_GET['gender'] ?? ''),
        ];

        $guideModel = new Guide();
        $guideModel->ensureStatusColumn();
        try {
            $guides = $guideModel->list($filters);
            $summary = [
                'total'    => $guideModel->countAll(),
                'active'   => $guideModel->countByStatus('active'),
                'inactive' => $guideModel->countByStatus('inactive'),
                'on_leave' => $guideModel->countByStatus('on_leave'),
            ];
            // Nếu có tham số new (id vừa tạo), lấy và đẩy bản ghi đó lên đầu danh sách để dễ thấy
            $newId = isset($_GET['new']) ? (int) $_GET['new'] : 0;
            if ($newId > 0) {
                try {
                    $newGuide = $guideModel->find($newId);
                    if ($newGuide) {
                        // Nếu bản ghi chưa có trong danh sách, thêm vào đầu
                        $exists = false;
                        foreach ($guides as $g) {
                            if (isset($g['HDV_ID']) && (int)$g['HDV_ID'] === $newId) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            array_unshift($guides, $newGuide);
                        }
                    }
                } catch (Throwable $e) {
                    // Không phá flow nếu không tìm được bản ghi
                }
            }
        } catch (Throwable $e) {
            error_log('GuideController::index error: ' . $e->getMessage());
            $guides = [];
            $summary = ['total' => 0, 'active' => 0, 'inactive' => 0, 'on_leave' => 0];
            $_SESSION['error'] = $_SESSION['error'] ?? 'Không thể tải danh sách nhân sự.';
        }

        $view = 'admin/guides';
        $title = 'Quản lý nhân sự';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function create(): void
    {
        $this->ensureAuthenticated();

        $view = 'admin/guides-create';
        $title = 'Thêm nhân sự';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    // File: GuideController.php

    public function store(): void
    {
        $this->ensureAuthenticated();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        $data = $this->extractFormData($_POST);
        $errors = $this->validate($data);

        // Lấy thông tin đăng nhập từ form
        $username = $data['contact']; // Dùng SĐT hoặc Email làm tên đăng nhập
        $password = $_POST['password'] ?? ''; 
        $fullName = $data['full_name'];

        if ($errors) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['guide_form_old'] = $_POST;
            header('Location: ' . BASE_URL . '?action=guides-create');
            exit;
        }

        try {
            $guideModel = new Guide();
            $guideModel->ensureStatusColumn();

            // --- BƯỚC 1: TẠO TÀI KHOẢN USER TRƯỚC ---
            // Tự động tạo user dựa trên Contact (SĐT/Email) và Password nhập vào
            // Role sẽ tự động được set là 'hdv' trong createAccount()
            $userId = $guideModel->createAccount($username, $password, $fullName);
            
            if ($userId === false) {
                 throw new RuntimeException('Không thể tạo tài khoản đăng nhập (có thể SĐT/Email đã tồn tại).');
            }
            
            // Đảm bảo role là 'hdv' (double check)
            $guideModel->ensureUserRoleIsHdv($userId);

            // --- BƯỚC 2: GÁN USER_ID VÀO DỮ LIỆU HDV ---
            $data['user_id'] = $userId;

            // --- BƯỚC 3: TẠO HDV ---
            $newId = $guideModel->create($data);
            
            if ($newId === false) {
                // (Tùy chọn) Nếu tạo HDV thất bại, có thể xóa user vừa tạo để tránh rác data
                throw new RuntimeException('Không thể lưu hồ sơ nhân sự.');
            }
            
            $_SESSION['success'] = 'Thêm nhân sự và tạo tài khoản thành công.';
        } catch (Throwable $e) {
            error_log('GuideController::store error: ' . $e->getMessage());
            $_SESSION['error'] = $e->getMessage(); // Hiển thị lỗi cụ thể
            $_SESSION['guide_form_old'] = $_POST;
            header('Location: ' . BASE_URL . '?action=guides-create');
            exit;
        }

        header('Location: ' . BASE_URL . '?action=guides&new=' . $newId);
        exit;
    }

    public function edit(): void
    {
        $this->ensureAuthenticated();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID nhân sự không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        $guideModel = new Guide();
        $guide = $guideModel->find($id);
        if (!$guide) {
            $_SESSION['error'] = 'Không tìm thấy nhân sự.';
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        $view = 'admin/guides-edit';
        $title = 'Chỉnh sửa nhân sự';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function update(): void
    {
        $this->ensureAuthenticated();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID nhân sự không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        $data = $this->extractFormData($_POST, true);
        $errors = $this->validate($data, true);

        if ($errors) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['guide_form_old'] = $_POST;
            header('Location: ' . BASE_URL . '?action=guides-edit&id=' . $id);
            exit;
        }

        try {
            $guideModel = new Guide();
            $guideModel->ensureStatusColumn();
            $guideModel->ensureUserIdColumn();
            
            // Đảm bảo role của user liên kết là 'hdv' trước khi cập nhật
            $existingGuide = $guideModel->find($id);
            if ($existingGuide && !empty($existingGuide['user_id'])) {
                $guideModel->ensureUserRoleIsHdv((int)$existingGuide['user_id']);
            }
            
            if (!$guideModel->update($id, $data)) {
                throw new RuntimeException('Không thể cập nhật nhân sự.');
            }

            // Cập nhật mật khẩu user nếu có nhập (và đảm bảo role là 'hdv')
            if (!empty($data['password'])) {
                $guideModel->updateAccountPassword($id, $data['password']);
            }
            
            // Đảm bảo role vẫn là 'hdv' sau khi cập nhật
            $updatedGuide = $guideModel->find($id);
            if ($updatedGuide && !empty($updatedGuide['user_id'])) {
                $guideModel->ensureUserRoleIsHdv((int)$updatedGuide['user_id']);
            }

            $_SESSION['success'] = 'Cập nhật nhân sự thành công.';
        } catch (Throwable $e) {
            error_log('GuideController::update error: ' . $e->getMessage());
            $_SESSION['error'] = 'Không thể cập nhật nhân sự. Vui lòng thử lại.';
            $_SESSION['guide_form_old'] = $_POST;
            header('Location: ' . BASE_URL . '?action=guides-edit&id=' . $id);
            exit;
        }

        header('Location: ' . BASE_URL . '?action=guides');
        exit;
    }

    public function delete(): void
    {
        $this->ensureAuthenticated();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'ID nhân sự không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        try {
            $guideModel = new Guide();
            if (!$guideModel->delete($id)) {
                throw new RuntimeException('Không thể xóa nhân sự.');
            }
            $_SESSION['success'] = 'Đã xóa nhân sự.';
        } catch (Throwable $e) {
            error_log('GuideController::delete error: ' . $e->getMessage());
            $_SESSION['error'] = 'Không thể xóa nhân sự.';
        }

        header('Location: ' . BASE_URL . '?action=guides');
        exit;
    }

    public function updateStatus(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Phương thức không hợp lệ.'], 405);
            }

            // Non-AJAX: redirect back to list
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = 'Phương thức không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = $_POST['status'] ?? '';

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        $allowed = ['active', 'inactive', 'on_leave'];
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], 400);
            }
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = 'Dữ liệu không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        try {
            $guideModel = new Guide();
            $updated = $guideModel->update($id, ['status' => $status]);
            if (!$updated) {
                throw new RuntimeException('Không thể cập nhật trạng thái.');
            }

            $summary = [
                'total'    => $guideModel->countAll(),
                'active'   => $guideModel->countByStatus('active'),
                'inactive' => $guideModel->countByStatus('inactive'),
                'on_leave' => $guideModel->countByStatus('on_leave'),
            ];

            if ($isAjax) {
                $this->jsonResponse([
                    'success'     => true,
                    'status'      => $status,
                    'badge'       => $this->getStatusBadge($status),
                    'summary'     => $summary,
                    'message'     => 'Đã cập nhật trạng thái.'
                ]);
            }

            // Non-AJAX: use session flash and redirect back to guides list so the updated badge will show on reload
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['success'] = 'Đã cập nhật trạng thái.';
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        } catch (Throwable $e) {
            // Log the error for server-side debugging
            error_log('GuideController::updateStatus error: ' . $e->getMessage());

            // If AJAX, return JSON response with error info
            if ($isAjax) {
                // In development return the detailed error to make debugging easier
                if (defined('APP_DEBUG') && APP_DEBUG === true) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Có lỗi xảy ra, vui lòng thử lại.',
                        'detail' => $e->getMessage(),
                    ], 500);
                }
                // Generic error for non-debug environments
                $this->jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.'], 500);
            }

            // Non-AJAX: set generic message and redirect back
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = 'Có lỗi xảy ra, vui lòng thử lại.';
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }
    }

    private function extractFormData(array $source, bool $isUpdate = false): array
    {
        $data = [
            'full_name'       => trim($source['name'] ?? ''),
            'dob'             => $source['dob'] ?? null,
            'gender'          => $source['gender'] ?? null,
            'contact'         => trim($source['contact'] ?? ''),
            'languages'       => trim($source['languages'] ?? ''),
            'address'         => trim($source['address'] ?? ''),
            'certificate'     => trim($source['certificate'] ?? ''),
            'experience_years'=> $source['experience'] ?? null,
            'start_date'      => $source['start_date'] ?? null,
            'health_status'   => trim($source['health_status'] ?? ''),
            'internal_note'   => trim($source['internal_note'] ?? ''),
            'rating'          => $source['rating'] ?? null,
            'review_note'     => trim($source['review_note'] ?? ''),
            'group_id'        => $source['group_id'] ?? 0,
            'status'          => $source['status'] ?? 'active',
        ];

        if (!$isUpdate || ($source['password'] ?? '') !== '') {
            $data['password'] = $source['password'] ?? '';
        }

        return $data;
    }

    private function validate(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        if ($data['full_name'] === '') {
            $errors[] = 'Vui lòng nhập họ tên hướng dẫn viên.';
        }

        if ($data['contact'] === '') {
            $errors[] = 'Vui lòng nhập thông tin liên hệ.';
        }

        if (!$isUpdate && empty($data['password'])) {
            $errors[] = 'Vui lòng nhập mật khẩu ban đầu.';
        }

        return $errors;
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

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    private function getStatusBadge(string $status): array
    {
        return match ($status) {
            'inactive' => ['label' => 'Tạm dừng', 'class' => 'bg-secondary'],
            'on_leave' => ['label' => 'Đang nghỉ phép', 'class' => 'bg-warning text-dark'],
            default    => ['label' => 'Đang làm việc', 'class' => 'bg-success'],
        };
    }
}



