<?php

class GuideController
{
    public function index(): void
    {
        $this->ensureAuthenticated();

        $view = 'admin/guides';
        $title = 'Quản lý Hướng dẫn viên';
        $hideNavbar = true;

        // Lấy dữ liệu theo phong cách PHP thuần để dễ đọc hơn
        $guidesPage = $this->loadGuidesPageData();

        require_once PATH_VIEW . 'main.php';
    }

    private function ensureAuthenticated(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            header('Location:' . BASE_URL . '?action=login');
            exit;
        }
    }

    private function loadGuidesPageData(): array
    {
        $guides = [];

        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query('SELECT * FROM hdv ORDER BY HoTen ASC');
            $guides = $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log('GuideController::loadGuidesPageData => ' . $e->getMessage());
            $_SESSION['error'] = 'Không thể tải dữ liệu hướng dẫn viên. Vui lòng thử lại.';
        }

        return [
            'guides' => $guides,
            'stats'  => $this->buildStats($guides),
        ];
    }

    private function getConnection(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);

        return $pdo;
    }

    private function buildStats(array $guides): array
    {
        $totalGuides = count($guides);
        $male = 0;
        $female = 0;
        $experienceSum = 0;

        foreach ($guides as $guide) {
            $gender = strtolower(trim($guide['GioiTinh'] ?? ''));
            if (in_array($gender, ['nam', 'male'], true)) {
                $male++;
            } elseif (in_array($gender, ['nữ', 'nu', 'female'], true)) {
                $female++;
            }

            $experienceSum += (int) ($guide['KinhNghiem'] ?? 0);
        }

        $avgExperience = $totalGuides > 0
            ? round($experienceSum / $totalGuides, 1)
            : 0;

        return [
            'total'          => $totalGuides,
            'male'           => $male,
            'female'         => $female,
            'avgExperience'  => $avgExperience,
        ];
    }
}