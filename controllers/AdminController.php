<?php

class AdminController 
{
    public function index()
    {
        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
        // kiểm tra đăng nhâp 
        if(!isset($_SESSION['user'])){
            header('Location:' .BASE_URL .'?action=login');
            exit;
        }
        $view = 'admin/index'; 
        // $title = 'Trang Quản Trị';


        // Thêm biến để ẩn navbar
        $hideNavbar = true;

        // Lấy số liệu từ database
        try {
            $tourModel = new Tour();
            $bookingModel = new Booking();
            $userModel = new User();
            $guideModel = new Guide();

            $tourCount = 0;
            $bookingCount = 0;
            $userCount = 0;
            $guideCount = 0;
            $customerCount = 0;
            $tourOpenCount = 0;
            $pendingBookings = 0;
            $revenue = 0.0;
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
                $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                $tourCount = (int)($pdo->query('SELECT COUNT(*) FROM tours')->fetchColumn() ?: 0);
                $bookingCount = (int)($pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn() ?: 0);
                $userCount = (int)($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() ?: 0);
                // Khách từ trang bookings (customers)
                $customerCount = (int)($pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn() ?: 0);
                // Cộng thêm khách từ trang đoàn (tour_guests) nếu có
                try { $customerCount += (int)($pdo->query('SELECT COUNT(*) FROM tour_guests')->fetchColumn() ?: 0); } catch (Throwable $_) {}

                // Tour đang mở: kết hợp hai nguồn rồi lấy lớn hơn
                $fromStatus = 0;
                $fromBookings = 0;
                try { $fromStatus = (int)($pdo->query("SELECT COUNT(*) FROM tours WHERE LOWER(COALESCE(tour_status,'active')) IN ('active','upcoming','hoạt động')")->fetchColumn() ?: 0); } catch (Throwable $_) {}
                try { $fromBookings = (int)($pdo->query("SELECT COUNT(DISTINCT tour_id) FROM bookings WHERE status IN ('pending','deposit','confirmed','completed')")->fetchColumn() ?: 0); } catch (Throwable $_) {}
                $tourOpenCount = max($fromStatus, $fromBookings);

                // Booking chờ xử lý
                $pendingBookings = (int)($pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn() ?: 0);
                // Doanh thu: tổng đặt cọc của các booking đã tiến triển
                $revenue = (float)($pdo->query("SELECT COALESCE(SUM(deposit_amount),0) FROM bookings WHERE status IN ('deposit','confirmed','completed')")->fetchColumn() ?: 0.0);
            } catch (Throwable $e) {
                // keep defaults
            }
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
                $pdoGuide = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                $guideCount = (int)($pdoGuide->query('SELECT COUNT(*) FROM hdv')->fetchColumn() ?: 0);
            } catch (Throwable $_) { 
                $guideCount = 0; 
            }

            $tours = $tourModel->listDashboard(12);

            $selectedMonth = (int) ($_GET['month'] ?? date('n'));
            $selectedYear  = (int) ($_GET['year']  ?? date('Y'));
            $dailyCounts   = $bookingModel->dailyCountsByMonth($selectedYear, $selectedMonth);

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
            $chartLabels = [];
            $chartValues = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $chartLabels[] = str_pad((string)$d, 2, '0', STR_PAD_LEFT);
                $chartValues[] = isset($dailyCounts[$d]) ? (int)$dailyCounts[$d] : 0;
            }

            $revPeriod   = isset($_GET['period']) ? strtolower((string)$_GET['period']) : 'month';
            $revYear     = (int)($_GET['rev_year'] ?? $selectedYear);
            $revMonth    = (int)($_GET['rev_month'] ?? $selectedMonth);
            $revQuarter  = (int)($_GET['rev_quarter'] ?? 1);
            $revTourId   = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;
            $toursList   = [];
            try { $toursList = $this->fetchToursSimple(); } catch (Throwable $_) { $toursList = []; }
            $revLabels = [];
            $revValues = [];
            $revenueSelectedTotal = 0.0;
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
                $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                if ($revPeriod === 'month') {
                    $sql = "SELECT DAY(start_date) d, COALESCE(SUM(deposit_amount),0) rev FROM bookings WHERE YEAR(start_date)=:y AND MONTH(start_date)=:m AND status IN ('deposit','confirmed','completed')" . ($revTourId>0?" AND tour_id=:tid":"") . " GROUP BY DAY(start_date) ORDER BY d";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':y', $revYear, PDO::PARAM_INT);
                    $stmt->bindValue(':m', $revMonth, PDO::PARAM_INT);
                    if ($revTourId>0) { $stmt->bindValue(':tid', $revTourId, PDO::PARAM_INT); }
                    $stmt->execute();
                    $rows = $stmt->fetchAll();
                    $days = cal_days_in_month(CAL_GREGORIAN, $revMonth, $revYear);
                    for ($d=1;$d<=$days;$d++){ $revLabels[] = str_pad((string)$d,2,'0',STR_PAD_LEFT); $revValues[] = 0; }
                    foreach ($rows as $r){ $idx = (int)$r['d']; if ($idx>=1 && $idx<=count($revValues)) { $revValues[$idx-1] = (float)$r['rev']; $revenueSelectedTotal += (float)$r['rev']; } }
                } elseif ($revPeriod === 'quarter') {
                    $q = max(1,min(4,$revQuarter));
                    $months = [($q-1)*3+1, ($q-1)*3+2, ($q-1)*3+3];
                    $sql = "SELECT MONTH(start_date) m, COALESCE(SUM(deposit_amount),0) rev FROM bookings WHERE YEAR(start_date)=:y AND MONTH(start_date) IN (:m1,:m2,:m3) AND status IN ('deposit','confirmed','completed')" . ($revTourId>0?" AND tour_id=:tid":"") . " GROUP BY MONTH(start_date) ORDER BY m";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':y', $revYear, PDO::PARAM_INT);
                    $stmt->bindValue(':m1', $months[0], PDO::PARAM_INT);
                    $stmt->bindValue(':m2', $months[1], PDO::PARAM_INT);
                    $stmt->bindValue(':m3', $months[2], PDO::PARAM_INT);
                    if ($revTourId>0) { $stmt->bindValue(':tid', $revTourId, PDO::PARAM_INT); }
                    $stmt->execute();
                    $rows = $stmt->fetchAll();
                    foreach ($months as $m){ $revLabels[] = 'Tháng ' . $m; $revValues[] = 0; }
                    $map = array_flip($months);
                    foreach ($rows as $r){ $m=(int)$r['m']; if (isset($map[$m])) { $revValues[$map[$m]] = (float)$r['rev']; $revenueSelectedTotal += (float)$r['rev']; } }
                } else {
                    $sql = "SELECT MONTH(start_date) m, COALESCE(SUM(deposit_amount),0) rev FROM bookings WHERE YEAR(start_date)=:y AND status IN ('deposit','confirmed','completed')" . ($revTourId>0?" AND tour_id=:tid":"") . " GROUP BY MONTH(start_date) ORDER BY m";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':y', $revYear, PDO::PARAM_INT);
                    if ($revTourId>0) { $stmt->bindValue(':tid', $revTourId, PDO::PARAM_INT); }
                    $stmt->execute();
                    $rows = $stmt->fetchAll();
                    for ($m=1;$m<=12;$m++){ $revLabels[] = 'Tháng ' . $m; $revValues[] = 0; }
                    foreach ($rows as $r){ $m=(int)$r['m']; if ($m>=1 && $m<=12) { $revValues[$m-1] = (float)$r['rev']; $revenueSelectedTotal += (float)$r['rev']; } }
                }
            } catch (Throwable $_) {
                $revLabels = [];
                $revValues = [];
                $revenueSelectedTotal = 0.0;
            }

            // Lấy danh sách tour đã hoàn thành
            $completedTours = [];
            try {
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
                $pdoCompleted = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
                $sql = "
                    SELECT DISTINCT
                        t.id,
                        t.name AS tour_name,
                        tc.name AS category_name,
                        t.price,
                        COUNT(DISTINCT b.id) AS total_bookings,
                        COALESCE(SUM(b.deposit_amount), 0) AS total_revenue,
                        MAX(b.start_date) AS last_tour_date
                    FROM tours t
                    LEFT JOIN tour_categories tc ON tc.id = t.category_id
                    LEFT JOIN bookings b ON b.tour_id = t.id AND b.status = 'completed'
                    WHERE EXISTS (
                        SELECT 1 FROM bookings b2 
                        WHERE b2.tour_id = t.id AND b2.status = 'completed'
                    )
                    GROUP BY t.id, t.name, tc.name, t.price
                    ORDER BY last_tour_date DESC, total_revenue DESC
                ";
                $stmt = $pdoCompleted->prepare($sql);
                $stmt->execute();
                $completedTours = $stmt->fetchAll();
                
                // Lấy sự cố/sự kiện cho từng tour
                $tourIssueModel = new TourIssue();
                foreach ($completedTours as &$tour) {
                    $tour['issues'] = $tourIssueModel->getByTourId((int)$tour['id']);
                }
                unset($tour);
            } catch (Throwable $e) {
                $completedTours = [];
            }
        } catch (Throwable $e) {
            // Nếu lỗi, fallback về 0 để trang vẫn hiển thị
            $tourCount = 0;
            $bookingCount = 0;
            $userCount = 0;
            $guideCount = 0;
            $customerCount = 0;
            $tourOpenCount = 0;
            $pendingBookings = 0;
            $revenue = 0.0;
            $tours = [];
            $selectedMonth = (int) date('n');
            $selectedYear  = (int) date('Y');
            $chartLabels = [];
            $chartValues = [];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $chartLabels[] = str_pad((string)$d, 2, '0', STR_PAD_LEFT);
                $chartValues[] = 0;
            }
            $revPeriod = 'month';
            $revYear = (int)date('Y');
            $revMonth = (int)date('n');
            $revQuarter = 1;
            $revTourId = 0;
            $toursList = [];
            $revLabels = [];
            $revValues = [];
            $revenueSelectedTotal = 0.0;
            $completedTours = [];
        }

        require_once PATH_VIEW . 'main.php'; 
    }

    private function fetchToursSimple(): array
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
            $stmt = $pdo->query('SELECT id, name FROM tours ORDER BY name ASC');
            return $stmt->fetchAll();
        } catch (Throwable $e) { return []; }
    }

    public function getRevenueData()
    {
        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
        if(!isset($_SESSION['user'])){
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        header('Content-Type: application/json');
        
        $revPeriod   = isset($_GET['period']) ? strtolower((string)$_GET['period']) : 'month';
        $revYear     = (int)($_GET['rev_year'] ?? date('Y'));
        $revMonth    = (int)($_GET['rev_month'] ?? date('n'));
        $revQuarter  = (int)($_GET['rev_quarter'] ?? 1);
        $revTourId   = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0;

        $revLabels = [];
        $revValues = [];
        $revenueSelectedTotal = 0.0;

        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
            
            if ($revPeriod === 'month') {
                $sql = "SELECT DAY(start_date) d, COALESCE(SUM(deposit_amount),0) rev FROM bookings WHERE YEAR(start_date)=:y AND MONTH(start_date)=:m AND status IN ('deposit','confirmed','completed')" . ($revTourId>0?" AND tour_id=:tid":"") . " GROUP BY DAY(start_date) ORDER BY d";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':y', $revYear, PDO::PARAM_INT);
                $stmt->bindValue(':m', $revMonth, PDO::PARAM_INT);
                if ($revTourId>0) { $stmt->bindValue(':tid', $revTourId, PDO::PARAM_INT); }
                $stmt->execute();
                $rows = $stmt->fetchAll();
                $days = cal_days_in_month(CAL_GREGORIAN, $revMonth, $revYear);
                for ($d=1;$d<=$days;$d++){ $revLabels[] = str_pad((string)$d,2,'0',STR_PAD_LEFT); $revValues[] = 0; }
                foreach ($rows as $r){ $idx = (int)$r['d']; if ($idx>=1 && $idx<=count($revValues)) { $revValues[$idx-1] = (float)$r['rev']; $revenueSelectedTotal += (float)$r['rev']; } }
            } elseif ($revPeriod === 'quarter') {
                $q = max(1,min(4,$revQuarter));
                $months = [($q-1)*3+1, ($q-1)*3+2, ($q-1)*3+3];
                $sql = "SELECT MONTH(start_date) m, COALESCE(SUM(deposit_amount),0) rev FROM bookings WHERE YEAR(start_date)=:y AND MONTH(start_date) IN (:m1,:m2,:m3) AND status IN ('deposit','confirmed','completed')" . ($revTourId>0?" AND tour_id=:tid":"") . " GROUP BY MONTH(start_date) ORDER BY m";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':y', $revYear, PDO::PARAM_INT);
                $stmt->bindValue(':m1', $months[0], PDO::PARAM_INT);
                $stmt->bindValue(':m2', $months[1], PDO::PARAM_INT);
                $stmt->bindValue(':m3', $months[2], PDO::PARAM_INT);
                if ($revTourId>0) { $stmt->bindValue(':tid', $revTourId, PDO::PARAM_INT); }
                $stmt->execute();
                $rows = $stmt->fetchAll();
                foreach ($months as $m){ $revLabels[] = 'Tháng ' . $m; $revValues[] = 0; }
                $map = array_flip($months);
                foreach ($rows as $r){ $m=(int)$r['m']; if (isset($map[$m])) { $revValues[$map[$m]] = (float)$r['rev']; $revenueSelectedTotal += (float)$r['rev']; } }
            } else {
                $sql = "SELECT MONTH(start_date) m, COALESCE(SUM(deposit_amount),0) rev FROM bookings WHERE YEAR(start_date)=:y AND status IN ('deposit','confirmed','completed')" . ($revTourId>0?" AND tour_id=:tid":"") . " GROUP BY MONTH(start_date) ORDER BY m";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':y', $revYear, PDO::PARAM_INT);
                if ($revTourId>0) { $stmt->bindValue(':tid', $revTourId, PDO::PARAM_INT); }
                $stmt->execute();
                $rows = $stmt->fetchAll();
                for ($m=1;$m<=12;$m++){ $revLabels[] = 'Tháng ' . $m; $revValues[] = 0; }
                foreach ($rows as $r){ $m=(int)$r['m']; if ($m>=1 && $m<=12) { $revValues[$m-1] = (float)$r['rev']; $revenueSelectedTotal += (float)$r['rev']; } }
            }
        } catch (Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        echo json_encode([
            'labels' => $revLabels,
            'values' => $revValues,
            'total' => $revenueSelectedTotal
        ]);
        exit;
    }
    public function tourCategories()
    {
        // 1. Kiểm tra đăng nhập (Áp dụng từ index() qua)
        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
        if(!isset($_SESSION['user'])){
            header('Location:' .BASE_URL .'?action=login');
            exit;
        }
        
        $view = 'admin/tour-categories'; 
        $title = 'Quản lý Danh mục Tour'; // Tiêu đề trang
        $hideNavbar = true; // Giữ nguyên sidebar/footer
        
        // 2. Lấy dữ liệu từ Model (Cần đảm bảo file TourCategory.php đã được require/autoload)
        $listCategories = [];
        try {
            // Lưu ý: Cần đảm bảo class TourCategory đã được định nghĩa và có hàm getAll()
            $model = new TourCategory(); 
            // getAll() là hàm lấy danh sách và đếm số tour (đã có trong file TourCategory bạn gửi)
            $listCategories = $model->getAll(); 
        } catch (Throwable $e) {
            $listCategories = [];
        }
        
        // 3. Tải Layout chính (Truyền $view, $title, $hideNavbar, và $listCategories)
        // Biến $listCategories PHẢI được truyền vì file view cần nó.
        require_once PATH_VIEW . 'main.php'; 
    }

    public function guideFeedbacks(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        $feedbackModel = new GuideFeedback();
        $feedbackTypes = $feedbackModel->getFeedbackTypes();

        $filters = [
            'guide_id' => isset($_GET['guide_id']) ? (int)$_GET['guide_id'] : 0,
            'feedback_type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'tour_id' => isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : 0,
        ];

        // Lấy danh sách phản hồi
        $feedbacks = $feedbackModel->getAll($filters);

        // Lấy danh sách HDV để filter
        $guides = [];
        try {
            $guideModel = new Guide();
            $guides = $guideModel->list([]);
        } catch (Throwable $e) {
            $guides = [];
        }

        // Lấy danh sách tour để filter
        $tours = [];
        try {
            $tourModel = new Tour();
            $tours = $tourModel->listWithCategory([]);
        } catch (Throwable $e) {
            $tours = [];
        }

        $view = 'admin/guide-feedbacks';
        $title = 'Phản hồi đánh giá từ HDV';
        $hideNavbar = true;

        require_once PATH_VIEW . 'main.php';
    }

    public function updateFeedbackStatus(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?action=guide-feedbacks');
            exit;
        }

        $feedbackId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = trim($_POST['status'] ?? '');

        if ($feedbackId <= 0 || empty($status)) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . BASE_URL . '?action=guide-feedbacks');
            exit;
        }

        $validStatuses = ['pending', 'reviewed', 'resolved'];
        if (!in_array($status, $validStatuses)) {
            $_SESSION['error'] = 'Trạng thái không hợp lệ';
            header('Location: ' . BASE_URL . '?action=guide-feedbacks');
            exit;
        }

        $feedbackModel = new GuideFeedback();
        $result = $feedbackModel->update($feedbackId, ['status' => $status]);

        if ($result) {
            $_SESSION['success'] = 'Đã cập nhật trạng thái phản hồi thành công.';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật trạng thái phản hồi.';
        }

        header('Location: ' . BASE_URL . '?action=guide-feedbacks');
        exit;
    }
    

}
