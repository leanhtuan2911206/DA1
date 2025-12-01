<?php

class CustomerController
{
    public function index(): void
    {
        $this->ensureAuthenticated();

        $bookingModel = new Booking();
        $customerModel = new Customer();

        try {
            $bookings = $bookingModel->listSimple();
        } catch (Throwable $e) {
            error_log('CustomerController::index listSimple error: ' . $e->getMessage());
            $bookings = [];
        }

        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
        if ($bookingId <= 0 && !empty($bookings)) {
            $bookingId = (int) ($bookings[0]['id'] ?? 0);
        }

        $selectedBooking = null;
        $customers = [];
        if ($bookingId > 0) {
            $selectedBooking = $bookingModel->findWithTour($bookingId);
            if ($selectedBooking) {
                $customers = $customerModel->getByBooking($bookingId);
            } else {
                $_SESSION['error'] = 'Không tìm thấy booking đã chọn.';
            }
        }

        $genderOptions = ['Male' => 'Nam', 'Female' => 'Nữ', 'Other' => 'Khác'];
        $paymentStatuses = Customer::PAYMENT_STATUSES;

        $view = 'admin/customers';
        $title = 'Quản lý khách hàng';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function create(): void
    {
        $this->ensureAuthenticated();

        $bookingModel = new Booking();
        $customerModel = new Customer();

        try {
            $bookings = $bookingModel->listSimple();
        } catch (Throwable $e) { $bookings = []; }

        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
        $selectedBooking = null;
        if ($bookingId > 0) {
            $selectedBooking = $bookingModel->findWithTour($bookingId);
        }

        $genderOptions = ['Male' => 'Nam', 'Female' => 'Nữ', 'Other' => 'Khác'];
        $paymentStatuses = Customer::PAYMENT_STATUSES;

        $view = 'admin/customers-create';
        $title = 'Thêm khách vào booking';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function edit(): void
    {
        $this->ensureAuthenticated();

        $bookingModel = new Booking();
        $customerModel = new Customer();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
        if ($id <= 0 || $bookingId <= 0) {
            $_SESSION['error'] = 'Thông tin không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=customers');
            exit;
        }

        $editingCustomer = $customerModel->find($id);
        if (!$editingCustomer || (int) $editingCustomer['booking_id'] !== $bookingId) {
            $_SESSION['error'] = 'Không tìm thấy khách cần sửa.';
            header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
            exit;
        }

        try { $bookings = $bookingModel->listSimple(); } catch (Throwable $e) { $bookings = []; }
        $selectedBooking = $bookingModel->findWithTour($bookingId);

        $genderOptions = ['Male' => 'Nam', 'Female' => 'Nữ', 'Other' => 'Khác'];
        $paymentStatuses = Customer::PAYMENT_STATUSES;

        $view = 'admin/customers-edit';
        $title = 'Chỉnh sửa khách hàng';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function store(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        $bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
        $fullName = trim($_POST['full_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $idType = trim($_POST['id_type'] ?? '');
        $idNumber = trim($_POST['id_number'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $paymentStatus = trim($_POST['payment_status'] ?? '');
        $specialRequests = trim($_POST['special_requests'] ?? '');

        if ($bookingId <= 0 || $fullName === '') {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
            header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
            exit;
        }

        $bookingModel = new Booking();
        $booking = $bookingModel->find($bookingId);
        if (!$booking) {
            $_SESSION['error'] = 'Booking không tồn tại.';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        $customerModel = new Customer();
        $paymentStatus = in_array($paymentStatus, Customer::PAYMENT_STATUSES, true) ? $paymentStatus : Customer::PAYMENT_STATUSES[0];
        $gender = in_array($gender, ['Male', 'Female', 'Other'], true) ? $gender : null;
        $dateOfBirth = $dateOfBirth !== '' ? $dateOfBirth : null;

        $data = [
            'booking_id' => $bookingId,
            'full_name' => $fullName,
            'gender' => $gender,
            'date_of_birth' => $dateOfBirth,
            'id_type' => $idType ?: null,
            'id_number' => $idNumber ?: null,
            'contact_phone' => $contactPhone ?: null,
            'email' => $email ?: null,
            'address' => $address ?: null,
            'payment_status' => $paymentStatus,
            'special_requests' => $specialRequests ?: null,
        ];

        $result = $customerModel->create($data);
        if ($result !== false) {
            $_SESSION['success'] = 'Đã thêm khách mới vào booking.';
        } else {
            $error = $customerModel->getLastError();
            if ($error && str_contains($error, 'Duplicate entry') && $idNumber !== '') {
                $_SESSION['error'] = 'Số giấy tờ/CMND đã tồn tại trong cùng booking. Vui lòng kiểm tra lại.';
            } else {
                $_SESSION['error'] = 'Không thể lưu khách. Vui lòng thử lại.';
            }
        }

        header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
        exit;
    }

    public function update(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
        $fullName = trim($_POST['full_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $idType = trim($_POST['id_type'] ?? '');
        $idNumber = trim($_POST['id_number'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $paymentStatus = trim($_POST['payment_status'] ?? '');
        $specialRequests = trim($_POST['special_requests'] ?? '');

        if ($id <= 0 || $bookingId <= 0 || $fullName === '') {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ.';
            header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
            exit;
        }

        $customerModel = new Customer();
        $existing = $customerModel->find($id);
        if (!$existing || (int) $existing['booking_id'] !== $bookingId) {
            $_SESSION['error'] = 'Không tìm thấy khách cần sửa.';
            header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
            exit;
        }

        $paymentStatus = in_array($paymentStatus, Customer::PAYMENT_STATUSES, true) ? $paymentStatus : Customer::PAYMENT_STATUSES[0];
        $gender = in_array($gender, ['Male', 'Female', 'Other'], true) ? $gender : null;
        $dateOfBirth = $dateOfBirth !== '' ? $dateOfBirth : null;

        $data = [
            'full_name' => $fullName,
            'gender' => $gender,
            'date_of_birth' => $dateOfBirth,
            'id_type' => $idType ?: null,
            'id_number' => $idNumber ?: null,
            'contact_phone' => $contactPhone ?: null,
            'email' => $email ?: null,
            'address' => $address ?: null,
            'payment_status' => $paymentStatus,
            'special_requests' => $specialRequests ?: null,
        ];

        if ($customerModel->update($id, $data)) {
            $_SESSION['success'] = 'Đã cập nhật thông tin khách.';
        } else {
            $error = $customerModel->getLastError();
            if ($error && str_contains($error, 'Duplicate entry') && $idNumber !== '') {
                $_SESSION['error'] = 'Số giấy tờ/CMND đã tồn tại trong cùng booking. Vui lòng kiểm tra lại.';
            } else {
                $_SESSION['error'] = 'Không thể cập nhật khách.';
            }
        }

        header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
        exit;
    }

    public function delete(): void
    {
        $this->ensureAuthenticated();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
        if ($id <= 0 || $bookingId <= 0) {
            $_SESSION['error'] = 'Không tìm thấy khách cần xóa.';
            header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
            exit;
        }

        $customerModel = new Customer();
        $existing = $customerModel->find($id);
        if (!$existing || (int) $existing['booking_id'] !== $bookingId) {
            $_SESSION['error'] = 'Khách không thuộc booking này.';
            header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
            exit;
        }

        if ($customerModel->delete($id)) {
            $_SESSION['success'] = 'Đã xóa khách khỏi booking.';
        } else {
            $_SESSION['error'] = 'Không thể xóa khách.';
        }

        header('Location: ' . BASE_URL . '?action=customers&booking_id=' . $bookingId);
        exit;
    }

    protected function ensureAuthenticated(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }
    }
}

