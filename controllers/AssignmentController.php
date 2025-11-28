<?php

class AssignmentController
{
    private function auth()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['user'])) { header('Location: ' . BASE_URL . '?action=login'); exit; }
    }

    public function index(): void
    {
        $this->auth();
        $view = 'admin/assignments';
        $title = 'Quản lý khởi hành và phân bổ nhân sự';
        $hideNavbar = true;

        $filters = [
            'booking_id' => isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null,
            'HDV_ID'     => isset($_GET['HDV_ID']) ? (int)$_GET['HDV_ID'] : null,
            'date_from'  => trim($_GET['date_from'] ?? ''),
            'date_to'    => trim($_GET['date_to'] ?? ''),
        ];
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

    public function create(): void
    {
        $this->auth();
        $view = 'admin/assignments-create';
        $title = 'Tạo phân bổ nhân sự';
        $hideNavbar = true;
        $bookings = $this->getBookingsSimple();
        $guides = [];
        try { $guides = (new Guide())->list([]); } catch (Throwable $e) { $guides = []; }
        require_once PATH_VIEW . 'main.php';
    }

    public function store(): void
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '?action=assignments-create'); exit; }
        $bookingId   = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $guideId     = isset($_POST['HDV_ID']) ? (int)$_POST['HDV_ID'] : 0;
        $assignDate  = trim($_POST['assign_date'] ?? '');
        $meetingPoint= trim($_POST['meeting_point'] ?? '');
        $startTime   = trim($_POST['start_time'] ?? '');
        $endTime     = trim($_POST['end_time'] ?? '');
        if ($startTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $startTime)) { $startTime .= ':00'; }
        if ($endTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $endTime)) { $endTime .= ':00'; }
        $driverId    = isset($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
        $supportId   = isset($_POST['support_id']) ? (int)$_POST['support_id'] : null;
        $notes       = trim($_POST['notes'] ?? '');
        $userId      = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;

        if ($bookingId <= 0 || $guideId <= 0) { $_SESSION['error'] = 'Vui lòng chọn booking và HDV.'; header('Location: ' . BASE_URL . '?action=assignments-create'); exit; }
        try {
            $model = new Assignment();
            $ok = $model->insertSimple([
                'booking_id'   => $bookingId,
                'HDV_ID'       => $guideId,
                'assign_date'  => $assignDate,
                'meeting_point'=> $meetingPoint,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'driver_id'    => $driverId,
                'support_id'   => $supportId,
                'notes'        => $notes,
                'user_id'      => $userId,
            ]);
            if ($ok) { $_SESSION['success'] = 'Tạo phân bổ thành công!'; } else { $_SESSION['error'] = 'Không thể tạo phân bổ: ' . $model->getLastError(); }
        } catch (Throwable $e) { $_SESSION['error'] = 'Đã xảy ra lỗi.'; }
        header('Location: ' . BASE_URL . '?action=assignments');
        exit;
    }

    public function delete(): void
    {
        $this->auth();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { header('Location: ' . BASE_URL . '?action=assignments'); exit; }
        try { $_SESSION['success'] = (new Assignment())->deleteByIdSimple($id) ? 'Xóa phân bổ thành công!' : 'Xóa thất bại.'; }
        catch (Throwable $e) { $_SESSION['error'] = 'Đã xảy ra lỗi.'; }
        header('Location: ' . BASE_URL . '?action=assignments');
        exit;
    }

    private function getBookingsSimple(): array
    {
        try {
            return (new Booking())->listSimple([]);
        } catch (Throwable $e) { return []; }
    }

    public function edit(): void
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
        try { $assignment = (new Assignment())->findByIdSimple($id); $guides = (new Guide())->list([]); } catch (Throwable $e) {}
        require_once PATH_VIEW . 'main.php';
    }

    public function update(): void
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '?action=assignments'); exit; }
        $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $bookingId   = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $guideId     = isset($_POST['HDV_ID']) ? (int)$_POST['HDV_ID'] : 0;
        $assignDate  = trim($_POST['assign_date'] ?? '');
        $meetingPoint= trim($_POST['meeting_point'] ?? '');
        $startTime   = trim($_POST['start_time'] ?? '');
        $endTime     = trim($_POST['end_time'] ?? '');
        if ($startTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $startTime)) { $startTime .= ':00'; }
        if ($endTime !== '' && preg_match('/^\d{1,2}:\d{2}$/', $endTime)) { $endTime .= ':00'; }
        $driverId    = isset($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
        $supportId   = isset($_POST['support_id']) ? (int)$_POST['support_id'] : null;
        $notes       = trim($_POST['notes'] ?? '');
        $userId      = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
        if ($id <= 0 || $bookingId <= 0 || $guideId <= 0) { $_SESSION['error'] = 'Dữ liệu không hợp lệ.'; header('Location: ' . BASE_URL . '?action=assignments'); exit; }
        try {
            $model = new Assignment();
            $ok = $model->updateSimple($id, [
                'booking_id'   => $bookingId,
                'HDV_ID'       => $guideId,
                'assign_date'  => $assignDate,
                'meeting_point'=> $meetingPoint,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'driver_id'    => $driverId,
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
