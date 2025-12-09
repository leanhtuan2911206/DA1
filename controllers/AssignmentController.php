<?php

class AssignmentController
{
    private function auth()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['user'])) { header('Location: ' . BASE_URL . '?action=login'); exit; }
    }

    public function index()
    {
        $this->auth();
        $view = 'admin/assignments';
        $title = 'Quản lý khởi hành và phân bổ nhân sự';
        $hideNavbar = true;

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $role = isset($_SESSION['user']['role']) ? strtolower((string)$_SESSION['user']['role']) : '';
        $guideIdSession = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : null;
        $filters = [
            'booking_id' => isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null,
            'HDV_ID'     => isset($_GET['HDV_ID']) ? (int)$_GET['HDV_ID'] : null,
            'tour_id'    => isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : null,
            'date_from'  => trim($_GET['date_from'] ?? ''),
            'date_to'    => trim($_GET['date_to'] ?? ''),
        ];
        if ($role === 'hdv' && $guideIdSession) { $filters['HDV_ID'] = $guideIdSession; }
        $list = [];
        $bookings = [];
        $guides = [];
        try {
            $a = new Assignment();
            $list = $a->getAllSimple(array_filter($filters));
            $bookings = $this->getBookingsSimple();
            $guides = (new Guide())->list([]);
        } catch (Throwable $e) { $list = []; $bookings = []; $guides = []; }

        require_once PATH_VIEW . 'main.php';
    }
    

    public function create()
    {
        $this->auth();
        $view = 'admin/assignments-create';
        $title = 'Tạo phân bổ nhân sự';
        $hideNavbar = true;
        $bookings = $this->getBookingsSimple();
        $guides = [];
        try { 
            $guideModel = new Guide();
            $guideModel->ensureStatusColumn();
            // Chỉ lấy HDV có status = 'active' (không lấy 'inactive' và 'on_leave')
            $allGuides = $guideModel->list(['status' => 'active']);
            // Lấy danh sách HDV đã được phân bổ
            $assignmentModel = new Assignment();
            $assignedGuideIds = $assignmentModel->getAssignedGuideIds();
            
            // Loại bỏ các HDV đã được phân bổ
            foreach ($allGuides as $guide) {
                $guideId = (int)($guide['HDV_ID'] ?? 0);
                if (!in_array($guideId, $assignedGuideIds)) {
                    $guides[] = $guide;
                }
            }
        } catch (Throwable $e) { 
            $guides = []; 
        }
        require_once PATH_VIEW . 'main.php';
    }

    public function store()
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '?action=assignments-create'); exit; }
        $bookingId   = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $guideId     = isset($_POST['HDV_ID']) ? (int)$_POST['HDV_ID'] : 0;
        $assignDate  = trim($_POST['assign_date'] ?? '');
        $endDate     = trim($_POST['end_date'] ?? '');
        $meetingPoint= trim($_POST['meeting_point'] ?? '');
        $startTime   = trim($_POST['start_time'] ?? '');
        $endTime     = trim($_POST['end_time'] ?? '');
        if ($startTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $startTime)) { $startTime .= ':00'; }
        if ($endTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $endTime)) { $endTime .= ':00'; }
        $supportId   = isset($_POST['support_id']) ? (int)$_POST['support_id'] : null;
        $notes       = trim($_POST['notes'] ?? '');
        $userId      = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;

        if ($bookingId <= 0 || $guideId <= 0) { $_SESSION['error'] = 'Vui lòng chọn booking và HDV.'; header('Location: ' . BASE_URL . '?action=assignments-create'); exit; }
        
        // Kiểm tra HDV đang nghỉ phép
        try {
            $guideModel = new Guide();
            $guideModel->ensureStatusColumn();
            $guide = $guideModel->find($guideId);
            if ($guide) {
                $status = strtolower(trim($guide['status'] ?? 'active'));
                if ($status === 'on_leave') {
                    $_SESSION['error'] = 'Hướng dẫn viên này đang nghỉ phép, không thể phân bổ.';
                    header('Location: ' . BASE_URL . '?action=assignments-create');
                    exit;
                }
            }
        } catch (Throwable $e) {
        }
        
        // Kiểm tra HDV đã được phân bổ chưa
        try {
            $model = new Assignment();
            if ($model->isGuideAssigned($guideId)) {
                $_SESSION['error'] = 'Hướng dẫn viên này đã được phân bổ cho tour khác. Vui lòng chọn HDV khác.';
                header('Location: ' . BASE_URL . '?action=assignments-create');
                exit;
            }
        } catch (Throwable $e) {
        }
        
        try {
            $ok = $model->insertSimple([
                'booking_id'   => $bookingId,
                'HDV_ID'       => $guideId,
                'assign_date'  => $assignDate,
                'end_date'     => $endDate,
                'meeting_point'=> $meetingPoint,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'support_id'   => $supportId,
                'notes'        => $notes,
                'user_id'      => $userId,
            ]);
            if ($ok) { $_SESSION['success'] = 'Tạo phân bổ thành công!'; } else { $_SESSION['error'] = 'Không thể tạo phân bổ: ' . $model->getLastError(); }
        } catch (Throwable $e) { $_SESSION['error'] = 'Đã xảy ra lỗi.'; }
        header('Location: ' . BASE_URL . '?action=assignments');
        exit;
    }

    public function delete()
    {
        $this->auth();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { header('Location: ' . BASE_URL . '?action=assignments'); exit; }
        try { $_SESSION['success'] = (new Assignment())->deleteByIdSimple($id) ? 'Xóa phân bổ thành công!' : 'Xóa thất bại.'; }
        catch (Throwable $e) { $_SESSION['error'] = 'Đã xảy ra lỗi.'; }
        header('Location: ' . BASE_URL . '?action=assignments');
        exit;
    }

    private function getBookingsSimple()
    {
        try {
            return (new Booking())->listSimple([]);
        } catch (Throwable $e) { return []; }
    }

    public function edit()
    {
        $this->auth();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { header('Location: ' . BASE_URL . '?action=assignments'); exit; }
        $view = 'admin/assignments-edit';
        $title = 'Sửa phân bổ nhân sự';
        $hideNavbar = true;
        $assignment = null;
        $bookings = $this->getBookingsSimple();
        $guides = [];
        try { 
            $assignmentModel = new Assignment();
            $assignment = $assignmentModel->findByIdSimple($id);
            
            // Lấy danh sách HDV, loại bỏ các HDV đã được phân bổ (trừ HDV hiện tại của assignment này)
            $guideModel = new Guide();
            $guideModel->ensureStatusColumn();
            // Chỉ lấy HDV có status = 'active' (không lấy 'inactive' và 'on_leave')
            $allGuides = $guideModel->list(['status' => 'active']);
            $assignedGuideIds = $assignmentModel->getAssignedGuideIds();
            $currentGuideId = $assignment ? (int)($assignment['HDV_ID'] ?? 0) : 0;
            
            foreach ($allGuides as $guide) {
                $guideId = (int)($guide['HDV_ID'] ?? 0);
                // Cho phép hiển thị HDV hiện tại hoặc HDV chưa được phân bổ
                if ($guideId === $currentGuideId || !in_array($guideId, $assignedGuideIds)) {
                    $guides[] = $guide;
                }
            }
            
            // Nếu HDV hiện tại đang nghỉ phép, vẫn thêm vào danh sách để có thể chỉnh sửa
            if ($currentGuideId > 0) {
                $currentGuide = $guideModel->find($currentGuideId);
                if ($currentGuide) {
                    $currentStatus = strtolower(trim($currentGuide['status'] ?? 'active'));
                    // Nếu HDV hiện tại đang nghỉ phép, thêm vào danh sách
                    if ($currentStatus === 'on_leave') {
                        $exists = false;
                        foreach ($guides as $g) {
                            if ((int)($g['HDV_ID'] ?? 0) === $currentGuideId) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $guides[] = $currentGuide;
                        }
                    }
                }
            }
        } catch (Throwable $e) {}
        require_once PATH_VIEW . 'main.php';
    }

    public function update()
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '?action=assignments'); exit; }
        $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $bookingId   = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $guideId     = isset($_POST['HDV_ID']) ? (int)$_POST['HDV_ID'] : 0;
        $assignDate  = trim($_POST['assign_date'] ?? '');
        $endDate     = trim($_POST['end_date'] ?? '');
        $meetingPoint= trim($_POST['meeting_point'] ?? '');
        $startTime   = trim($_POST['start_time'] ?? '');
        $endTime     = trim($_POST['end_time'] ?? '');
        if ($startTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $startTime)) { $startTime .= ':00'; }
        if ($endTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $endTime)) { $endTime .= ':00'; }
        $supportId   = isset($_POST['support_id']) ? (int)$_POST['support_id'] : null;
        $notes       = trim($_POST['notes'] ?? '');
        $userId      = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
        if ($id <= 0 || $bookingId <= 0 || $guideId <= 0) { $_SESSION['error'] = 'Dữ liệu không hợp lệ.'; header('Location: ' . BASE_URL . '?action=assignments'); exit; }
        
        // Kiểm tra HDV đang nghỉ phép (trừ khi đang cập nhật assignment hiện tại với cùng HDV)
        try {
            $guideModel = new Guide();
            $guideModel->ensureStatusColumn();
            $guide = $guideModel->find($guideId);
            if ($guide) {
                $status = strtolower(trim($guide['status'] ?? 'active'));
                if ($status === 'on_leave') {
                    // Kiểm tra xem có phải đang cập nhật assignment hiện tại với cùng HDV không
                    $assignmentModel = new Assignment();
                    $currentAssignment = $assignmentModel->findByIdSimple($id);
                    $currentGuideId = $currentAssignment ? (int)($currentAssignment['HDV_ID'] ?? 0) : 0;
                    
                    // Nếu không phải cùng HDV, không cho phép
                    if ($guideId !== $currentGuideId) {
                        $_SESSION['error'] = 'Hướng dẫn viên này đang nghỉ phép, không thể phân bổ.';
                        header('Location: ' . BASE_URL . '?action=assignments-edit&id=' . $id);
                        exit;
                    }
                }
            }
        } catch (Throwable $e) {
        }
        
        // Kiểm tra HDV đã được phân bổ chưa (loại trừ assignment hiện tại)
        try {
            $model = new Assignment();
            if ($model->isGuideAssigned($guideId, $id)) {
                $_SESSION['error'] = 'Hướng dẫn viên này đã được phân bổ cho tour khác. Vui lòng chọn HDV khác.';
                header('Location: ' . BASE_URL . '?action=assignments-edit&id=' . $id);
                exit;
            }
        } catch (Throwable $e) {
        }
        
        try {
            $ok = $model->updateSimple($id, [
                'booking_id'   => $bookingId,
                'HDV_ID'       => $guideId,
                'assign_date'  => $assignDate,
                'end_date'     => $endDate,
                'meeting_point'=> $meetingPoint,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'support_id'   => $supportId,
                'notes'        => $notes,
                'schedule_id'  => isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : null,
                'user_id'      => $userId,
            ]);
            $_SESSION['success'] = $ok ? 'Cập nhật phân bổ thành công!' : 'Không thể cập nhật: ' . $model->getLastError();
        } catch (Throwable $e) { $_SESSION['error'] = 'Đã xảy ra lỗi.'; }
        header('Location: ' . BASE_URL . '?action=assignments');
        exit;
    }
}
