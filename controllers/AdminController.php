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
            try { $guideCount = (int)($pdo->query('SELECT COUNT(*) FROM hdv')->fetchColumn() ?: 0); } catch (Throwable $_) { $guideCount = 0; }

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
        }

        require_once PATH_VIEW . 'main.php'; 
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
            // Ghi log lỗi nếu database/model có vấn đề
            error_log("Database error in AdminController::tourCategories: " . $e->getMessage());
            $listCategories = []; // Trả về mảng rỗng để trang không bị crash
        }
        
        // 3. Tải Layout chính (Truyền $view, $title, $hideNavbar, và $listCategories)
        // Biến $listCategories PHẢI được truyền vì file view cần nó.
        require_once PATH_VIEW . 'main.php'; 
    }
    

}
