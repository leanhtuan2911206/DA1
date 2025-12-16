<?php

class ServiceController
{
    private function auth()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['user'])) { header('Location: ' . BASE_URL . '?action=login'); exit; }
    }

    private function normalizeDateTime(?string $value): ?string
    {
        $v = trim((string)$value);
        if ($v === '') { return null; }
        $v = str_replace('T', ' ', $v);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) { $v .= ':00'; }
        return $v;
    }

    public function index(): void
    {
        $this->auth();
        $view = 'admin/services';
        $title = 'Quản lý dịch vụ đoàn';
        $hideNavbar = true;
        $filters = [];
        if (isset($_GET['booking_id']) && $_GET['booking_id'] !== '') {
            $filters['booking_id'] = (int)$_GET['booking_id'];
        }
        if (isset($_GET['type'])) {
            $filters['type'] = trim($_GET['type']);
        }
        if (isset($_GET['status'])) {
            $filters['status'] = trim($_GET['status']);
        }

        $list = [];
        $bookings = [];
        $types = [];
        try {
            $s = new ServiceOrder();
            $allServices = $s->list(array_filter($filters));
            $bookings = (new Booking())->listSimple([]);
            $types = $s->distinctTypes();
            
            // Nhóm các dịch vụ theo booking_id
            $groupedByBooking = [];
            foreach ($allServices as $service) {
                $bookingId = $service['booking_id'];
                if (!isset($groupedByBooking[$bookingId])) {
                    $groupedByBooking[$bookingId] = [];
                }
                $groupedByBooking[$bookingId][] = $service;
            }
            
            // Chuyển đổi thành danh sách đơn giản
            $list = [];
            foreach ($groupedByBooking as $bookingId => $services) {
                $list[] = [
                    'booking_id' => $bookingId,
                    'services' => $services
                ];
            }
        } catch (Throwable $e) { 
            $list = []; 
            $bookings = []; 
        }

        require_once PATH_VIEW . 'main.php';
    }

    public function create(): void
    {
        $this->auth();
        $view = 'admin/services-create';
        $title = 'Đặt dịch vụ';
        $hideNavbar = true;
        $bookings = [];
        $vehicles = [];
        $hotels = [];
        $flights = [];
        $restaurants = [];
        $activities = [];
        try { $bookings = (new Booking())->listSimple([]); } catch (Throwable $e) {}
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
            $vehicles = ($pdo->query("SELECT id, name, license_plate, driver_name, driver_phone, capacity FROM master_vehicles ORDER BY name"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $hotels = ($pdo->query("SELECT id, hotel_name, address, star_rating, contact_phone, room_types_available FROM master_hotels ORDER BY hotel_name"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $flights = ($pdo->query("SELECT id, flight_number, airline, route_origin, route_destination, default_price FROM master_flights ORDER BY flight_number"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $restaurants = ($pdo->query("SELECT id, restaurant_name, address, cuisine_type, contact_phone, max_capacity FROM master_restaurants ORDER BY restaurant_name"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $activities = ($pdo->query("SELECT id, location_name, address, ticket_type_info, contact_person FROM master_activities ORDER BY location_name"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { $vehicles = []; $hotels = []; $flights = []; $restaurants = []; $activities = []; }
        require_once PATH_VIEW . 'main.php';
    }

    public function store(): void
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '?action=services-create'); exit; }
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $type = isset($_POST['service_type']) ? trim($_POST['service_type']) : '';
        $supplier = isset($_POST['supplier_name']) ? trim($_POST['supplier_name']) : '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'chờ';
        $startTime = $this->normalizeDateTime(isset($_POST['start_time']) ? $_POST['start_time'] : '');
        $endTime = $this->normalizeDateTime(isset($_POST['end_time']) ? $_POST['end_time'] : '');
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        $masterVehicleId = isset($_POST['master_vehicle_id']) ? (int)$_POST['master_vehicle_id'] : null;
        $masterHotelId = isset($_POST['master_hotel_id']) ? (int)$_POST['master_hotel_id'] : null;
        $masterFlightId = isset($_POST['master_flight_id']) ? (int)$_POST['master_flight_id'] : null;
        $masterRestaurantId = isset($_POST['master_restaurant_id']) ? (int)$_POST['master_restaurant_id'] : null;
        $masterActivityId = isset($_POST['master_activity_id']) ? (int)$_POST['master_activity_id'] : null;
        $selectedMap = [];
        if ($masterVehicleId) { $selectedMap['vehicle'] = $masterVehicleId; }
        if ($masterHotelId) { $selectedMap['hotel'] = $masterHotelId; }
        if ($masterFlightId) { $selectedMap['flight'] = $masterFlightId; }
        if ($masterRestaurantId) { $selectedMap['restaurant'] = $masterRestaurantId; }
        if ($masterActivityId) { $selectedMap['activity'] = $masterActivityId; }
        if ($type !== '' && isset($selectedMap[$type])) {
            // giữ nguyên loại do admin chọn nếu khớp với master đã chọn
        } elseif (!empty($selectedMap)) {
            // nếu admin chưa đổi Loại dịch vụ, chọn loại theo master đầu tiên được chọn
            $type = array_key_first($selectedMap);
        }
        if ($bookingId <= 0) { $_SESSION['error'] = 'Vui lòng chọn tour.'; header('Location: ' . BASE_URL . '?action=services-create'); exit; }
        try {
            $s = new ServiceOrder();

            $getSupplier = function(string $tp, ?int $id): string {
                if (!$id) return '';
                try {
                    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
                    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                    switch ($tp) {
                        case 'vehicle': $stmt = $pdo->prepare('SELECT name AS n FROM master_vehicles WHERE id = ?'); break;
                        case 'hotel': $stmt = $pdo->prepare('SELECT hotel_name AS n FROM master_hotels WHERE id = ?'); break;
                        case 'flight': $stmt = $pdo->prepare('SELECT airline AS n FROM master_flights WHERE id = ?'); break;
                        case 'restaurant': $stmt = $pdo->prepare('SELECT restaurant_name AS n FROM master_restaurants WHERE id = ?'); break;
                        case 'activity': $stmt = $pdo->prepare('SELECT location_name AS n FROM master_activities WHERE id = ?'); break;
                        default: return '';
                    }
                    $stmt->execute([$id]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $name = isset($row['n']) ? $row['n'] : '';
                    return trim($name);
                } catch (Throwable $e) { return ''; }
            };

            $created = 0;
            $items = [];
            foreach (['vehicle'=>$masterVehicleId,'hotel'=>$masterHotelId,'flight'=>$masterFlightId,'restaurant'=>$masterRestaurantId,'activity'=>$masterActivityId] as $tp=>$mid) {
                if ($mid) { $items[] = [$tp,$mid]; }
            }
            if (empty($items)) { $items[] = [$type, null]; }
            foreach ($items as [$tp,$mid]) {
                $sup = $supplier !== '' ? $supplier : $getSupplier($tp, $mid);
                $ok = $s->create([
                    'booking_id'   => $bookingId,
                    'service_type' => $tp,
                    'supplier_name'=> $sup,
                    'quantity'     => $quantity,
                    'status'       => $status,
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'notes'        => $notes,
                    'master_vehicle_id' => ($tp==='vehicle') ? $mid : null,
                    'master_hotel_id' => ($tp==='hotel') ? $mid : null,
                    'master_flight_id' => ($tp==='flight') ? $mid : null,
                    'master_restaurant_id' => ($tp==='restaurant') ? $mid : null,
                    'master_activity_id' => ($tp==='activity') ? $mid : null,
                ]);
                if ($ok) { $created++; }
            }

            if ($masterVehicleId) {
                $driverName = isset($_POST['driver_name']) ? trim($_POST['driver_name']) : '';
                $driverPhone = isset($_POST['driver_phone']) ? trim($_POST['driver_phone']) : '';
                $licensePlate = isset($_POST['license_plate']) ? trim($_POST['license_plate']) : '';
                $capacity = isset($_POST['driver_capacity']) ? trim((string)$_POST['driver_capacity']) : '';
                if ($driverName !== '' || $driverPhone !== '' || $licensePlate !== '') {
                    try {
                        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
                        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                        $sql = 'UPDATE master_vehicles SET driver_name=:dn, driver_phone=:dp, license_plate=:lp' . ($capacity !== '' ? ', capacity=:cp' : '') . ' WHERE id=:id';
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindValue(':dn', $driverName !== '' ? $driverName : null, PDO::PARAM_STR);
                        $stmt->bindValue(':dp', $driverPhone !== '' ? $driverPhone : null, PDO::PARAM_STR);
                        $stmt->bindValue(':lp', $licensePlate !== '' ? $licensePlate : null, PDO::PARAM_STR);
                        if ($capacity !== '') { $stmt->bindValue(':cp', $capacity, PDO::PARAM_INT); }
                        $stmt->bindValue(':id', $masterVehicleId, PDO::PARAM_INT);
                        $stmt->execute();
                    } catch (Throwable $e) {}
                }
            }
            $_SESSION['success'] = ($created>0) ? ('Đặt ' . $created . ' dịch vụ thành công!') : 'Không thể đặt dịch vụ.';
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
        $vehicles = [];
        $hotels = [];
        $flights = [];
        $restaurants = [];
        $activities = [];
        try { $service = (new ServiceOrder())->find($id); } catch (Throwable $e) {}
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
            $vehicles = ($pdo->query("SELECT id, name, license_plate, driver_name, driver_phone, capacity FROM master_vehicles ORDER BY name"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $hotels = ($pdo->query("SELECT id, hotel_name, address, star_rating, contact_phone, room_types_available FROM master_hotels ORDER BY hotel_name"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $flights = ($pdo->query("SELECT id, flight_number, airline, route_origin, route_destination, default_price FROM master_flights ORDER BY flight_number"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $restaurants = ($pdo->query("SELECT id, restaurant_name, address, cuisine_type, contact_phone, max_capacity FROM master_restaurants ORDER BY restaurant_name"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $activities = ($pdo->query("SELECT id, location_name, address, ticket_type_info, contact_person FROM master_activities ORDER BY location_name"))->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { $vehicles = []; $hotels = []; $flights = []; $restaurants = []; $activities = []; }
        require_once PATH_VIEW . 'main.php';
    }

    public function update(): void
    {
        $this->auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '?action=services'); exit; }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
        $type = isset($_POST['service_type']) ? trim($_POST['service_type']) : '';
        $supplier = isset($_POST['supplier_name']) ? trim($_POST['supplier_name']) : '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'chờ';
        $startTime = $this->normalizeDateTime(isset($_POST['start_time']) ? $_POST['start_time'] : '');
        $endTime = $this->normalizeDateTime(isset($_POST['end_time']) ? $_POST['end_time'] : '');
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        $masterVehicleId = isset($_POST['master_vehicle_id']) ? (int)$_POST['master_vehicle_id'] : null;
        $masterHotelId = isset($_POST['master_hotel_id']) ? (int)$_POST['master_hotel_id'] : null;
        $masterFlightId = isset($_POST['master_flight_id']) ? (int)$_POST['master_flight_id'] : null;
        $masterRestaurantId = isset($_POST['master_restaurant_id']) ? (int)$_POST['master_restaurant_id'] : null;
        $masterActivityId = isset($_POST['master_activity_id']) ? (int)$_POST['master_activity_id'] : null;
        // xác định loại theo master nếu cần
        $selectedMap = [];
        if ($masterVehicleId) { $selectedMap['vehicle'] = $masterVehicleId; }
        if ($masterHotelId) { $selectedMap['hotel'] = $masterHotelId; }
        if ($masterFlightId) { $selectedMap['flight'] = $masterFlightId; }
        if ($masterRestaurantId) { $selectedMap['restaurant'] = $masterRestaurantId; }
        if ($masterActivityId) { $selectedMap['activity'] = $masterActivityId; }
        if ($type !== '' && isset($selectedMap[$type])) {
            // giữ nguyên
        } elseif (!empty($selectedMap)) {
            $type = array_key_first($selectedMap);
        }

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
                'master_vehicle_id' => ($type==='vehicle') ? $masterVehicleId : null,
                'master_hotel_id' => ($type==='hotel') ? $masterHotelId : null,
                'master_flight_id' => ($type==='flight') ? $masterFlightId : null,
                'master_restaurant_id' => ($type==='restaurant') ? $masterRestaurantId : null,
                'master_activity_id' => ($type==='activity') ? $masterActivityId : null,
            ]);

            if ($type === 'vehicle' && $masterVehicleId) {
                $driverName = isset($_POST['driver_name']) ? trim($_POST['driver_name']) : '';
                $driverPhone = isset($_POST['driver_phone']) ? trim($_POST['driver_phone']) : '';
                $licensePlate = isset($_POST['license_plate']) ? trim($_POST['license_plate']) : '';
                $capacity = isset($_POST['driver_capacity']) ? trim((string)$_POST['driver_capacity']) : '';
                if ($driverName !== '' || $driverPhone !== '' || $licensePlate !== '') {
                    try {
                        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
                        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                        $sql = 'UPDATE master_vehicles SET driver_name=:dn, driver_phone=:dp, license_plate=:lp' . ($capacity !== '' ? ', capacity=:cp' : '') . ' WHERE id=:id';
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindValue(':dn', $driverName !== '' ? $driverName : null, PDO::PARAM_STR);
                        $stmt->bindValue(':dp', $driverPhone !== '' ? $driverPhone : null, PDO::PARAM_STR);
                        $stmt->bindValue(':lp', $licensePlate !== '' ? $licensePlate : null, PDO::PARAM_STR);
                        if ($capacity !== '') { $stmt->bindValue(':cp', $capacity, PDO::PARAM_INT); }
                        $stmt->bindValue(':id', $masterVehicleId, PDO::PARAM_INT);
                        $stmt->execute();
                    } catch (Throwable $e) {}
                }
            }
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
