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

    public function store(): void
    {
        $this->ensureAuthenticated();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=guides');
            exit;
        }

        $data = $this->extractFormData($_POST);
        $errors = $this->validate($data);

        if ($errors) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['guide_form_old'] = $_POST;
            header('Location: ' . BASE_URL . '?action=guides-create');
            exit;
        }

        try {
            $guideModel = new Guide();
            $guideModel->ensureStatusColumn();
            $newId = $guideModel->create($data);
            if ($newId === false) {
                throw new RuntimeException('Không thể lưu nhân sự.');
            }
            $_SESSION['success'] = 'Thêm nhân sự thành công.';
        } catch (Throwable $e) {
            error_log('GuideController::store error: ' . $e->getMessage());
            $_SESSION['error'] = 'Không thể lưu nhân sự. Vui lòng thử lại.';
            $_SESSION['guide_form_old'] = $_POST;
            header('Location: ' . BASE_URL . '?action=guides-create');
            exit;
        }

        // Chuyển về trang danh sách và đánh dấu bản ghi vừa tạo
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
            if (!$guideModel->update($id, $data)) {
                throw new RuntimeException('Không thể cập nhật nhân sự.');
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
}

