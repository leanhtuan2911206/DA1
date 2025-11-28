<?php

class ServiceController
{
    private function auth()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['user'])) { header('Location: ' . BASE_URL . '?action=login'); exit; }
    }

    public function index(): void
    {
        $this->auth();
        $view = 'admin/services';
        $title = 'Quản lý dịch vụ đoàn';
        $hideNavbar = true;
        $filters = [
            'booking_id' => isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null,
            'type'       => trim($_GET['type'] ?? ''),
            'status'     => trim($_GET['status'] ?? ''),
        ];

        $list = [];
        $bookings = [];
        try {
            $s = new ServiceOrder();
            $list = $s->list(array_filter($filters));
            $bookings = (new Booking())->listSimple([]);
        } catch (Throwable $e) { $list = []; $bookings = []; }

        require_once PATH_VIEW . 'main.php';
    }

    public function create(): void
    {
        $this->auth();
        $view = 'admin/services-create';
        $title = 'Đặt dịch vụ';
        $hideNavbar = true;
        $bookings = [];
        try { $bookings = (new Booking())->listSimple([]); } catch (Throwable $e) {}
        require_once PATH_VIEW . 'main.php';
    }

    public function store(): void
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '?action=services-create'); exit; }
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $type = trim($_POST['service_type'] ?? '');
        $supplier = trim($_POST['supplier_name'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);
        $status = trim($_POST['status'] ?? 'pending');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($bookingId <= 0 || $type === '' || $supplier === '') { $_SESSION['error'] = 'Vui lòng nhập đủ dữ liệu bắt buộc.'; header('Location: ' . BASE_URL . '?action=services-create'); exit; }
        try {
            $s = new ServiceOrder();
            $ok = $s->create([
                'booking_id'   => $bookingId,
                'service_type' => $type,
                'supplier_name'=> $supplier,
                'quantity'     => $quantity,
                'status'       => $status,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'notes'        => $notes,
            ]);
            $_SESSION['success'] = $ok ? 'Đặt dịch vụ thành công!' : 'Không thể đặt dịch vụ.';
        } catch (Throwable $e) { $_SESSION['error'] = 'Đã xảy ra lỗi.'; }
        header('Location: ' . BASE_URL . '?action=services');
        exit;
    }

    public function edit(): void
    {
        $this->auth();
        $view = 'admin/services-edit';
        $title = 'Sửa dịch vụ';
        $hideNavbar = true;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $service = null;
        try { $service = (new ServiceOrder())->find($id); } catch (Throwable $e) {}
        require_once PATH_VIEW . 'main.php';
    }

    public function update(): void
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '?action=services'); exit; }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $type = trim($_POST['service_type'] ?? '');
        $supplier = trim($_POST['supplier_name'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);
        $status = trim($_POST['status'] ?? 'pending');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        try {
            $ok = (new ServiceOrder())->update($id, [
                'booking_id'   => $bookingId,
                'service_type' => $type,
                'supplier_name'=> $supplier,
                'quantity'     => $quantity,
                'status'       => $status,
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'notes'        => $notes,
            ]);
            $_SESSION['success'] = $ok ? 'Cập nhật dịch vụ thành công!' : 'Không thể cập nhật dịch vụ.';
        } catch (Throwable $e) { $_SESSION['error'] = 'Đã xảy ra lỗi.'; }
        header('Location: ' . BASE_URL . '?action=services');
        exit;
    }

    public function delete(): void
    {
        $this->auth();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        try { $_SESSION['success'] = (new ServiceOrder())->delete($id) ? 'Xóa dịch vụ thành công!' : 'Xóa thất bại.'; }
        catch (Throwable $e) { $_SESSION['error'] = 'Đã xảy ra lỗi.'; }
        header('Location: ' . BASE_URL . '?action=services');
        exit;
    }
}

