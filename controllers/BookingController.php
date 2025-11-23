<?php

class BookingController
{
    public function index()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Thiết lập view
        $view = 'admin/bookings';
        $title = 'Quản lý Booking';
        $hideNavbar = true;

        // Lấy dữ liệu
        $bookingsGrouped = [];
        $tours = [];
        $filters = [
            'tour_id' => $_GET['tour_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'booking_type' => $_GET['booking_type'] ?? '',
            'customer_name' => $_GET['customer_name'] ?? '',
            'start_date_from' => $_GET['start_date_from'] ?? '',
            'start_date_to' => $_GET['start_date_to'] ?? '',
        ];

        try {
            $bookingModel = new Booking();
            $tourModel = new Tour();
            
            // Lấy danh sách tour để hiển thị trong filter
            $tours = $tourModel->listWithCategory([]);
            
            // Lấy booking nhóm theo tour
            $bookingsGrouped = $bookingModel->getBookingsGroupedByTour($filters);
        } catch (Throwable $e) {
            error_log("Error in BookingController::index: " . $e->getMessage());
            $bookingsGrouped = [];
            $tours = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function create()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Thiết lập view
        $view = 'admin/bookings-create';
        $title = 'Tạo Booking Mới';
        $hideNavbar = true;

        // Lấy danh sách tour
        $tours = [];
        try {
            $tourModel = new Tour();
            $tours = $tourModel->listWithCategory([]);
        } catch (Throwable $e) {
            error_log("Error loading tours: " . $e->getMessage());
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function store()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy dữ liệu từ form
        $tour_id = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
        $total_people = isset($_POST['total_people']) ? (int)$_POST['total_people'] : 1;
        $booking_type = isset($_POST['booking_type']) ? $_POST['booking_type'] : 'individual';
        $special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
        $deposit_amount = isset($_POST['deposit_amount']) ? (float)$_POST['deposit_amount'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'pending';

        // Lấy user_id từ session
        $changed_by = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

        // Kiểm tra dữ liệu bắt buộc
        if (empty($tour_id) || empty($start_date) || empty($customer_name) || $total_people < 1) {
            $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
            header('Location: ' . BASE_URL . '?action=bookings-create');
            exit;
        }

        // Tạo booking
        try {
            $bookingModel = new Booking();
            $data = [
                'tour_id' => $tour_id,
                'start_date' => $start_date,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email,
                'total_people' => $total_people,
                'booking_type' => $booking_type,
                'special_requests' => $special_requests,
                'deposit_amount' => $deposit_amount,
                'status' => $status,
                'changed_by' => $changed_by,
            ];

            $bookingId = $bookingModel->create($data);

            if ($bookingId) {
                $_SESSION['success'] = 'Tạo booking thành công!';
                header('Location: ' . BASE_URL . '?action=bookings');
                exit;
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi tạo booking!';
                header('Location: ' . BASE_URL . '?action=bookings-create');
                exit;
            }
        } catch (Throwable $e) {
            error_log("Error creating booking: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '?action=bookings-create');
            exit;
        }
    }

    public function edit()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy ID booking
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Thiết lập view
        $view = 'admin/bookings-edit';
        $title = 'Chỉnh sửa Booking';
        $hideNavbar = true;

        // Lấy dữ liệu
        $booking = null;
        $tours = [];
        try {
            $bookingModel = new Booking();
            $tourModel = new Tour();
            
            $booking = $bookingModel->findWithTour($id);
            $tours = $tourModel->listWithCategory([]);

            if (!$booking) {
                $_SESSION['error'] = 'Booking không tồn tại!';
                header('Location: ' . BASE_URL . '?action=bookings');
                exit;
            }
        } catch (Throwable $e) {
            error_log("Error loading booking: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra khi tải dữ liệu!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function update()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy ID booking
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Lấy dữ liệu từ form
        $tour_id = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
        $customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
        $customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
        $total_people = isset($_POST['total_people']) ? (int)$_POST['total_people'] : 1;
        $booking_type = isset($_POST['booking_type']) ? $_POST['booking_type'] : 'individual';
        $special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
        $deposit_amount = isset($_POST['deposit_amount']) ? (float)$_POST['deposit_amount'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'pending';

        // Lấy user_id từ session
        $changed_by = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

        // Kiểm tra dữ liệu bắt buộc
        if (empty($tour_id) || empty($start_date) || empty($customer_name) || $total_people < 1) {
            $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
            header('Location: ' . BASE_URL . '?action=bookings-edit&id=' . $id);
            exit;
        }

        // Cập nhật booking
        try {
            $bookingModel = new Booking();
            $data = [
                'tour_id' => $tour_id,
                'start_date' => $start_date,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email,
                'total_people' => $total_people,
                'booking_type' => $booking_type,
                'special_requests' => $special_requests,
                'deposit_amount' => $deposit_amount,
                'status' => $status,
                'changed_by' => $changed_by,
            ];

            $result = $bookingModel->update($id, $data);

            if ($result) {
                $_SESSION['success'] = 'Cập nhật booking thành công!';
                header('Location: ' . BASE_URL . '?action=bookings');
                exit;
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật booking!';
                header('Location: ' . BASE_URL . '?action=bookings-edit&id=' . $id);
                exit;
            }
        } catch (Throwable $e) {
            error_log("Error updating booking: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '?action=bookings-edit&id=' . $id);
            exit;
        }
    }

    public function updateStatus()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy dữ liệu
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $changed_by = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

        if ($id <= 0 || empty($status)) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Cập nhật trạng thái
        try {
            $bookingModel = new Booking();
            $result = $bookingModel->updateStatus($id, $status, $changed_by);

            if ($result) {
                $_SESSION['success'] = 'Cập nhật trạng thái thành công!';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật trạng thái!';
            }
        } catch (Throwable $e) {
            error_log("Error updating status: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '?action=bookings');
        exit;
    }

    public function delete()
    {
        // Kiểm tra đăng nhập
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        // Lấy ID booking
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = 'Booking không tồn tại!';
            header('Location: ' . BASE_URL . '?action=bookings');
            exit;
        }

        // Xóa booking
        try {
            $bookingModel = new Booking();
            $result = $bookingModel->delete($id);

            if ($result) {
                $_SESSION['success'] = 'Xóa booking thành công!';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi xóa booking!';
            }
        } catch (Throwable $e) {
            error_log("Error deleting booking: " . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '?action=bookings');
        exit;
    }
}

