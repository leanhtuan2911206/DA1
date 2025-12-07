<?php
class PartnerController
{
    public function dashboard(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '?action=login'); exit; }
        $role = strtolower($_SESSION['user']['role'] ?? '');
        if ($role !== 'hdv') { header('Location: ' . BASE_URL . '?action=admin'); exit; }
        $guideId = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
        $assignments = [];
        $trip_detail = null;
        if ($guideId === 0) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                $stmt = $pdo->prepare('SELECT id FROM hdv WHERE user_id = :uid LIMIT 1');
                $stmt->execute([':uid' => (int)$_SESSION['user']['id']]);
                $r = $stmt->fetch(); if ($r && isset($r['id'])) { $guideId = (int)$r['id']; $_SESSION['user']['guide_id'] = $guideId; }
            } catch (Throwable $e) {}
        }
        try {
            if ($guideId > 0) {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                // Metrics for dashboard cards
                $metrics = [
                    'total_customers' => 0,
                    'open_tours' => 0,
                    'revenue' => 0.0,
                    'pending_bookings' => 0,
                    'total_bookings' => 0,
                ];
                try { $metrics['total_customers'] = (int)($pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn() ?: 0); } catch (Throwable $e) {}
                try { $metrics['open_tours'] = (int)($pdo->query("SELECT COUNT(*) FROM tours WHERE COALESCE(tour_status,'Active') IN ('Active','Upcoming')")->fetchColumn() ?: 0); } catch (Throwable $e) {}
                try { $metrics['total_bookings'] = (int)($pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn() ?: 0); } catch (Throwable $e) {}
                try { $metrics['pending_bookings'] = (int)($pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn() ?: 0); } catch (Throwable $e) {}
                try { $metrics['revenue'] = (float)($pdo->query("SELECT COALESCE(SUM(deposit_amount),0) FROM bookings WHERE status IN ('confirmed','completed')")->fetchColumn() ?: 0.0); } catch (Throwable $e) {}
                $st = $pdo->prepare('SELECT * FROM tour_assignments WHERE guide_id = :gid ORDER BY id DESC');
                $st->execute([':gid' => $guideId]);
                $assignments = $st->fetchAll();
                if (!empty($assignments)) {
                    $first = $assignments[0];
                    $scheduleId = (int)($first['tour_schedule_id'] ?? ($first['schedule_id'] ?? 0));
                    if ($scheduleId > 0) {
                        $st = $pdo->prepare('SELECT ts.*, t.name AS tour_name FROM tour_schedules ts LEFT JOIN tours t ON t.id = ts.tour_id WHERE ts.id = :sid LIMIT 1');
                        $st->execute([':sid' => $scheduleId]);
                        $sch = $st->fetch();
                        $tourId = (int)($sch['tour_id'] ?? 0);
                        $tourName = (string)($sch['tour_name'] ?? '—');
                        $startDate = $sch['start_date'] ?? null;
                        $custCount = 0;
                        try { $st = $pdo->prepare('SELECT COUNT(*) AS c FROM tour_guests WHERE schedule_id = :sid'); $st->execute([':sid' => $scheduleId]); $rr = $st->fetch(); $custCount = (int)($rr['c'] ?? 0); } catch (Throwable $e) {}
                        $itinerary = [];
                        try {
                            $st = $pdo->prepare('SELECT day, content FROM tour_itineraries WHERE schedule_id = :sid ORDER BY day');
                            $st->execute([':sid' => $scheduleId]);
                            foreach ($st->fetchAll() as $row) { $itinerary[] = ['day' => (int)$row['day'], 'activities' => (string)$row['content']]; }
                        } catch (Throwable $e) {}
                        $services = [];
                        $customer_info = null; $stats = null;
                        try {
                            $st = $pdo->prepare('SELECT service_type, supplier_name, quantity, status FROM booking_services WHERE tour_schedule_id = :sid');
                            $st->execute([':sid' => $scheduleId]);
                            $sv = $st->fetchAll();
                            if (!$sv) {
                                $st = $pdo->prepare('SELECT id FROM bookings WHERE tour_id = :tid AND start_date = :sd LIMIT 1');
                                $st->execute([':tid' => $tourId, ':sd' => $startDate]);
                                $bid = (int)($st->fetch()['id'] ?? 0);
                                if ($bid > 0) {
                                    $st = $pdo->prepare('SELECT service_type, supplier_name, quantity, status FROM booking_services WHERE booking_id = :bid');
                                    $st->execute([':bid' => $bid]);
                                    $sv = $st->fetchAll();
                                    // Build customer info & stats from booking/customers
                                    try {
                                        $st = $pdo->prepare('SELECT * FROM bookings WHERE id = :bid LIMIT 1');
                                        $st->execute([':bid' => $bid]);
                                        $bookingRow = $st->fetch();
                                        if ($bookingRow) {
                                            $customer_info = [
                                                'leader' => (string)($bookingRow['customer_name'] ?? ''),
                                                'phone' => (string)($bookingRow['customer_phone'] ?? ''),
                                                'email' => (string)($bookingRow['customer_email'] ?? ''),
                                                'code' => 'BK-' . (int)$bid,
                                                'paid' => (string)($bookingRow['status'] ?? ''),
                                            ];
                                            $stAdult = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE booking_id = :bid AND (date_of_birth IS NULL OR TIMESTAMPDIFF(YEAR,date_of_birth,CURDATE()) >= 12)");
                                            $stAdult->execute([':bid' => $bid]);
                                            $adultCount = (int)($stAdult->fetchColumn() ?: 0);
                                            $stChild = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE booking_id = :bid AND date_of_birth IS NOT NULL AND TIMESTAMPDIFF(YEAR,date_of_birth,CURDATE()) < 12");
                                            $stChild->execute([':bid' => $bid]);
                                            $childCount = (int)($stChild->fetchColumn() ?: 0);
                                            $stAdded = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE booking_id = :bid');
                                            $stAdded->execute([':bid' => $bid]);
                                            $addedCount = (int)($stAdded->fetchColumn() ?: 0);
                                            $stSpecial = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE booking_id = :bid AND special_requests IS NOT NULL AND TRIM(special_requests) <> ''");
                                            $stSpecial->execute([':bid' => $bid]);
                                            $specialCount = (int)($stSpecial->fetchColumn() ?: 0);
                                            $totalPeople = (int)($bookingRow['total_people'] ?? 0);
                                            $available = max(0, $totalPeople - $addedCount);
                                            $stats = [
                                                ['label' => 'Người lớn', 'value' => $adultCount . ' người', 'class' => 'stat-orange', 'icon' => '👥'],
                                                ['label' => 'Trẻ em', 'value' => $childCount . ' người', 'class' => 'stat-pink', 'icon' => '🍩'],
                                                ['label' => 'Còn trống', 'value' => $available . ' người', 'class' => 'stat-green', 'icon' => '📄'],
                                                ['label' => 'Cần lưu ý', 'value' => $specialCount . ' người', 'class' => 'stat-violet', 'icon' => '⚕️'],
                                            ];
                                        }
                                    } catch (Throwable $e) {}
                                }
                            }
                            foreach ($sv as $s) { $services[] = ['type' => $s['service_type'] ?? '', 'name' => $s['supplier_name'] ?? '', 'qty' => (int)($s['quantity'] ?? 0), 'status' => $s['status'] ?? '']; }
                        } catch (Throwable $e) {}
                        $trip_detail = [
                            'tour_name' => $tourName,
                            'departure_date' => $startDate,
                            'customer_count' => $custCount,
                            'assigned_driver' => !empty($first['driver_id']) ? ('Tài xế #' . (int)$first['driver_id']) : '',
                            'itinerary' => $itinerary,
                            'services' => $services,
                        ];
                        if ($customer_info) { $GLOBALS['customer_info'] = $customer_info; }
                        if ($stats) { $GLOBALS['stats'] = $stats; }
                    }
                }
            }
        } catch (Throwable $e) {}
        $view = 'admin/hdv_detail';
        $title = 'Trang của HDV';
        $hideNavbar = true;
        $showPartnerSidebar = true;
        $currentTab = isset($_GET['tab']) && $_GET['tab'] === 'assignments' ? 'assignments' : 'detail';
        require_once PATH_VIEW . 'main.php';
    }
}
