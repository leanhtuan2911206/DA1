<?php

class AdminController 
{
    public function index()
    {
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

            $tourCount = $tourModel->countAll();
            $bookingCount = $bookingModel->countAll();
            $userCount = $userModel->countAll();
            $guideCount = $guideModel->countAll();

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
        $view = 'admin/tour-categories'; // Tên view mới
        
        // Dữ liệu mẫu (tạm thời) cho 3 danh mục theo yêu cầu
        $categories = [
            ['id' => 1, 'name' => 'Tour trong nước', 'description' => 'Tour tham quan, du lịch các địa điểm trong nước.'],
            ['id' => 2, 'name' => 'Tour quốc tế', 'description' => 'Tour tham quan, du lịch các nước ngoài.'],
            ['id' => 3, 'name' => 'Tour theo yêu cầu', 'description' => 'Tour thiết kế riêng theo yêu cầu khách hàng.'],
        ];
        
        $hideNavbar = true; // Giữ nguyên sidebar/footer
        
        // Cần truyền biến $categories và $hideNavbar vào view.
        // Trong cấu trúc MVC đơn giản, có thể dùng extract($data) hoặc
        // require_once PATH_VIEW . 'main.php' (nếu main.php là layout chính)
        require_once PATH_VIEW . 'main.php'; 
    }
}
