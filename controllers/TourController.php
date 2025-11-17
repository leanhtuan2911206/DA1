<?php

class TourController
{
    public function index(): void
    {
        $this->ensureAuthenticated();

        $view = 'admin/tours';
        $title = 'Quản lý danh sách tour';
        $hideNavbar = true;

        $tourModel = new Tour();
        $categoryModel = new TourCategory();

        $filters = [
            'keyword'      => trim($_GET['keyword'] ?? ''),
            'category_id'  => $_GET['category_id'] ?? '',
            'destination'  => trim($_GET['destination'] ?? ''),
            'price_order'  => $_GET['price_order'] ?? '',
        ];

        try {
            $tours = $tourModel->listWithCategory($filters);
        } catch (Throwable $e) {
            error_log('TourController::index list error: ' . $e->getMessage());
            $tours = [];
        }

        try {
            $categories = $categoryModel->getAll();
        } catch (Throwable $e) {
            error_log('TourController::index category error: ' . $e->getMessage());
            $categories = [];
        }

        require_once PATH_VIEW . 'main.php';
    }

    private function ensureAuthenticated(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '?action=login');
            exit;
        }
    }
}

