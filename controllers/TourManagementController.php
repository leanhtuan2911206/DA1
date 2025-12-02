<?php

class TourManagementController
{
    public function index(): void
    {
        $this->ensureAuthenticated();

        $view = 'admin/tour-management';
        $title = 'Diễn hành tour';
        $hideNavbar = true;

        $tourModel = new Tour();
        $groupModel = new TourGroup();

        $filters = [
            'tour_id' => $_GET['tour_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        try {
            $tours = $tourModel->listWithCategory([]);
            $allGroups = $groupModel->getAll() ?? [];
            
            // Lọc theo filter
            if (!empty($filters['tour_id'])) {
                $allGroups = array_filter($allGroups, function($group) use ($filters) {
                    return $group['tour_id'] == $filters['tour_id'];
                });
            }
            if (!empty($filters['status'])) {
                $allGroups = array_filter($allGroups, function($group) use ($filters) {
                    return $group['status'] == $filters['status'];
                });
            }
        } catch (Throwable $e) {
            error_log('TourManagementController::index error: ' . $e->getMessage());
            $tours = [];
            $allGroups = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function listGuests(): void
    {
        $this->ensureAuthenticated();

        $groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
        if ($groupId <= 0) {
            $_SESSION['error'] = 'Đoàn không tồn tại';
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $view = 'admin/tour-guests';
        $title = 'Danh sách khách đoàn';
        $hideNavbar = true;

        $groupModel = new TourGroup();
        $guestModel = new TourGuest();
        $roomModel = new HotelRoom();

        try {
            $group = $groupModel->getWithDetails($groupId);
            if (!$group) {
                $_SESSION['error'] = 'Không tìm thấy đoàn khách';
                header('Location: ' . BASE_URL . '?action=tour-management');
                exit;
            }

            $guests = $guestModel->getByGroup($groupId);
            $rooms = $roomModel->getAll();
        } catch (Throwable $e) {
            error_log('TourManagementController::listGuests error: ' . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra';
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function createGroup(): void
    {
        $this->ensureAuthenticated();

        $view = 'admin/tour-group-create';
        $title = 'Tạo đoàn khách mới';
        $hideNavbar = true;

        $tourModel = new Tour();
        $bookingModel = new Booking();

        try {
            $tours = $tourModel->listWithCategory([]);
            $bookings = $bookingModel->listSimple([]);
        } catch (Throwable $e) {
            error_log('TourManagementController::createGroup error: ' . $e->getMessage());
            $tours = [];
            $bookings = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function storeGroup(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $tour_id = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
        $booking_id = isset($_POST['booking_id']) && $_POST['booking_id'] !== '' ? (int) $_POST['booking_id'] : null;
        $group_name = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';
        $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
        $total_guests = isset($_POST['total_guests']) ? (int) $_POST['total_guests'] : 0;

        if ($tour_id <= 0 || empty($group_name) || $total_guests <= 0) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
            header('Location: ' . BASE_URL . '?action=tour-group-create');
            exit;
        }

        $groupModel = new TourGroup();
        $data = [
            'tour_id' => $tour_id,
            'booking_id' => $booking_id,
            'group_name' => $group_name,
            'start_date' => $start_date ?: null,
            'end_date' => $end_date ?: null,
            'total_guests' => $total_guests,
            'status' => 'pending'
        ];

        $result = $groupModel->create($data);
        if ($result) {
            $_SESSION['success'] = 'Tạo đoàn khách thành công';
            header('Location: ' . BASE_URL . '?action=tour-management');
        } else {
            $_SESSION['error'] = 'Không thể tạo đoàn khách';
            header('Location: ' . BASE_URL . '?action=tour-group-create');
        }
        exit;
    }

    public function updateGroupStatus(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';

        $allowed = ['pending', 'in_progress', 'completed', 'cancelled'];
        if ($group_id <= 0 || !in_array($status, $allowed, true)) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ khi cập nhật trạng thái';
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $groupModel = new TourGroup();
        if ($groupModel->updateStatus($group_id, $status)) {
            $_SESSION['success'] = 'Cập nhật trạng thái đoàn thành công';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật trạng thái đoàn';
        }

        header('Location: ' . BASE_URL . '?action=tour-management');
        exit;
    }

    public function deleteGroup(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        if ($group_id <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $groupModel = new TourGroup();
        if ($groupModel->delete($group_id)) {
            $_SESSION['success'] = 'Xóa đoàn thành công';
        } else {
            $_SESSION['error'] = 'Không thể xóa đoàn';
        }

        header('Location: ' . BASE_URL . '?action=tour-management');
        exit;
    }

    public function addGuest(): void
    {
        $this->ensureAuthenticated();

        $groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
        if ($groupId <= 0) {
            $_SESSION['error'] = 'Đoàn không tồn tại';
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $view = 'admin/tour-guest-create';
        $title = 'Thêm khách vào đoàn';
        $hideNavbar = true;

        $groupModel = new TourGroup();
        $roomModel = new HotelRoom();

        try {
            $group = $groupModel->find($groupId);
            if (!$group) {
                $_SESSION['error'] = 'Không tìm thấy đoàn khách';
                header('Location: ' . BASE_URL . '?action=tour-management');
                exit;
            }
            $rooms = $roomModel->getAll();
        } catch (Throwable $e) {
            error_log('TourManagementController::addGuest error: ' . $e->getMessage());
            $group = null;
            $rooms = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function storeGuest(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
        $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '';
        $id_type = isset($_POST['id_type']) ? trim($_POST['id_type']) : '';
        $id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : 'unpaid';
        $special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';

        if ($group_id <= 0 || empty($full_name)) {
            $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
            header('Location: ' . BASE_URL . '?action=tour-guest-add&group_id=' . $group_id);
            exit;
        }

        try {
            $guestModel = new TourGuest();
            $data = [
                'group_id' => $group_id,
                'full_name' => $full_name,
                'phone' => $phone ?: null,
                'gender' => $gender ?: null,
                'date_of_birth' => $date_of_birth ?: null,
                'id_type' => $id_type ?: null,
                'id_number' => $id_number ?: null,
                'email' => $email ?: null,
                'address' => $address ?: null,
                'payment_status' => $payment_status,
                'special_requests' => $special_requests ?: null,
            ];

            $result = $guestModel->create($data);
            if ($result) {
                $_SESSION['success'] = 'Thêm khách thành công';
                header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $group_id);
            } else {
                $_SESSION['error'] = 'Không thể thêm khách. Vui lòng kiểm tra lại dữ liệu.';
                header('Location: ' . BASE_URL . '?action=tour-guest-add&group_id=' . $group_id);
            }
        } catch (Throwable $e) {
            error_log('TourManagementController::storeGuest error: ' . $e->getMessage());
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            header('Location: ' . BASE_URL . '?action=tour-guest-add&group_id=' . $group_id);
        }
        exit;
    }

    public function editGuest(): void
    {
        $this->ensureAuthenticated();

        $guestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

        if ($guestId <= 0 || $groupId <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $view = 'admin/tour-guest-edit';
        $title = 'Chỉnh sửa thông tin khách';
        $hideNavbar = true;

        $guestModel = new TourGuest();
        $roomModel = new HotelRoom();

        try {
            $guest = $guestModel->find($guestId);
            if (!$guest || $guest['group_id'] != $groupId) {
                $_SESSION['error'] = 'Không tìm thấy khách';
                header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $groupId);
                exit;
            }
            $rooms = $roomModel->getAll();
        } catch (Throwable $e) {
            error_log('TourManagementController::editGuest error: ' . $e->getMessage());
            $_SESSION['error'] = 'Có lỗi xảy ra';
            header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $groupId);
            exit;
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function updateGuest(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
        $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '';
        $id_type = isset($_POST['id_type']) ? trim($_POST['id_type']) : '';
        $id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : 'unpaid';
        $special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';

        if ($id <= 0 || $group_id <= 0 || empty($full_name)) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $group_id);
            exit;
        }

        $guestModel = new TourGuest();
        $data = [
            'full_name' => $full_name,
            'phone' => $phone ?: null,
            'gender' => $gender ?: null,
            'date_of_birth' => $date_of_birth ?: null,
            'id_type' => $id_type ?: null,
            'id_number' => $id_number ?: null,
            'email' => $email ?: null,
            'address' => $address ?: null,
            'payment_status' => $payment_status,
            'special_requests' => $special_requests ?: null,
        ];

        if ($guestModel->update($id, $data)) {
            $_SESSION['success'] = 'Cập nhật thông tin khách thành công';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật khách';
        }

        header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $group_id);
        exit;
    }

    public function checkinGuest(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        
        if ($id <= 0 || $group_id <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $group_id);
            exit;
        }

        $guestModel = new TourGuest();
        
        // Cập nhật check-in status nếu có
        if (!empty($_POST['checkin_status'])) {
            $checkin_status = trim($_POST['checkin_status']);
            if (!$guestModel->updateCheckinStatus($id, $checkin_status)) {
                $_SESSION['error'] = 'Không thể cập nhật check-in';
            }
        }
        
        // Cập nhật payment status nếu có
        if (!empty($_POST['payment_status'])) {
            $payment_status = trim($_POST['payment_status']);
            if (!$guestModel->updatePaymentStatus($id, $payment_status)) {
                $_SESSION['error'] = 'Không thể cập nhật thanh toán';
            }
        }
        
        if (empty($_SESSION['error'])) {
            $_SESSION['success'] = 'Cập nhật thông tin thành công';
        }

        header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $group_id);
        exit;
    }

    public function assignRoom(): void
    {
        $this->ensureAuthenticated();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $guest_id = isset($_POST['guest_id']) ? (int) $_POST['guest_id'] : 0;
        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
        $room_id = isset($_POST['room_id']) ? (int) $_POST['room_id'] : 0;

        if ($guest_id <= 0 || $group_id <= 0 || $room_id <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $group_id);
            exit;
        }

        $guestModel = new TourGuest();
        if ($guestModel->assignRoom($guest_id, $room_id)) {
            $_SESSION['success'] = 'Phân phòng thành công';
        } else {
            $_SESSION['error'] = 'Không thể phân phòng';
        }

        header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $group_id);
        exit;
    }

    public function deleteGuest(): void
    {
        $this->ensureAuthenticated();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $group_id = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

        if ($id <= 0 || $group_id <= 0) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $guestModel = new TourGuest();
        if ($guestModel->delete($id)) {
            $_SESSION['success'] = 'Xóa khách thành công';
        } else {
            $_SESSION['error'] = 'Không thể xóa khách';
        }

        header('Location: ' . BASE_URL . '?action=tour-guests&group_id=' . $group_id);
        exit;
    }

    public function printList(): void
    {
        $groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
        if ($groupId <= 0) {
            header('Location: ' . BASE_URL . '?action=tour-management');
            exit;
        }

        $groupModel = new TourGroup();
        $guestModel = new TourGuest();

        try {
            $group = $groupModel->getWithDetails($groupId);
            $guests = $guestModel->getByGroup($groupId);
        } catch (Throwable $e) {
            error_log('TourManagementController::printList error: ' . $e->getMessage());
            $group = null;
            $guests = [];
        }

        // Render print view
        require_once PATH_VIEW . 'admin/tour-guests-print.php';
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
