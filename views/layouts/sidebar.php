<?php
$currentAction = $currentAction ?? ($_GET['action'] ?? '/');

$activeMap = [
    'admin'            => ['/', 'admin'],
    'tour-categories'  => ['tour-categories', 'tour-categories-create', 'tour-categories-store', 'tour-categories-edit', 'tour-categories-update'],
    'tours'            => ['tours'],
];

$isActive = function(string $key) use ($currentAction, $activeMap) {
    $actions = $activeMap[$key] ?? [$key];
    return in_array($currentAction, $actions, true) ? 'active' : '';
};
?>

<aside class="sidebar">
    <h4 class="mb-4 text-center">Quản trị</h4>

    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('admin'); ?>" href="<?= BASE_URL ?>?action=admin">Báo cáo</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('tour-categories'); ?>" href="<?= BASE_URL ?>?action=tour-categories">Danh mục tour</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('tours'); ?>" href="<?= BASE_URL ?>?action=tours">Danh sách tour</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link" href="#">Quản lý booking</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link" href="#">Quản lý tài khoản</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link" href="#">Cài đặt</a>
        </li>
        <li class="nav-item mt-4 px-3">
            <a class="btn btn-sm btn-danger w-100" href="<?= BASE_URL ?>?action=logout">Đăng xuất</a>
        </li>
    </ul>
</aside>
