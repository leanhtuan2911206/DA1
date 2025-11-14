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
        } catch (Throwable $e) {
            // Nếu lỗi, fallback về 0 để trang vẫn hiển thị
            $tourCount = 0;
            $bookingCount = 0;
            $userCount = 0;
            $guideCount = 0;
        }

        require_once PATH_VIEW . 'main.php'; 
    }
}
