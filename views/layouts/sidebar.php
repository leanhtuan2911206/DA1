<?php
$currentAction = $currentAction ?? ($_GET['action'] ?? '/');

$activeMap = [
    'admin'            => ['/', 'admin'],
    'tour-categories'  => ['tour-categories', 'tour-categories-create', 'tour-categories-store', 'tour-categories-edit', 'tour-categories-update'],
    'tours'            => ['tours', 'tours-create', 'tours-store', 'tours-edit', 'tours-update'],
    'bookings'         => ['bookings', 'bookings-create', 'bookings-store', 'bookings-edit', 'bookings-update', 'bookings-update-status'],
    'customers'        => ['customers', 'customers-store', 'customers-update', 'customers-delete'],
    'tour-management'  => ['tour-management', 'tour-group-create', 'tour-group-store', 'tour-guests', 'tour-guest-add', 'tour-guest-store', 'tour-guest-edit', 'tour-guest-update', 'tour-guest-checkin', 'tour-guest-assign-room', 'tour-guest-delete', 'tour-guests-print'],
    'users'            => ['users', 'users-create', 'users-store', 'users-edit', 'users-update', 'users-delete'],
    'guides'           => ['guides', 'guides-create', 'guides-store', 'guides-edit', 'guides-update', 'guides-delete'],
    'assignments'      => ['assignments', 'assignments-create', 'assignments-store', 'assignments-delete'],
    'services'         => ['services', 'services-create', 'services-store', 'services-edit', 'services-update', 'services-delete'],
];

$isActive = function (string $key) use ($currentAction, $activeMap) {
    $actions = $activeMap[$key] ?? [$key];
    return in_array($currentAction, $actions, true) ? 'active' : '';
};
?>

<aside class="sidebar">
    <?php
        $__logoUrl = BASE_ASSETS_UPLOADS . 'img/logo.png';
        $__logoFs = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo.png';
    ?>
    <div class="mb-4 text-center">
        <?php if (file_exists($__logoFs)): ?>
            <img src="<?= $__logoUrl ?>" alt="Travel Company" style="max-height:120px">
        <?php else: ?>
            <h4 class="mb-0">Quản trị</h4>
        <?php endif; ?>
    </div>

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
            <a class="nav-link <?= $isActive('bookings'); ?>" href="<?= BASE_URL ?>?action=bookings">Quản lý booking</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('tour-management'); ?>" href="<?= BASE_URL ?>?action=tour-management">Diễn hành tour</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('users'); ?>" href="<?= BASE_URL ?>?action=users">Quản lý tài khoản</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('guides'); ?>" href="<?= BASE_URL ?>?action=guides">Quản lý nhân sự</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('assignments'); ?>" href="<?= BASE_URL ?>?action=assignments">Quản lý khởi hành và phân bổ nhân sự</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('services'); ?>" href="<?= BASE_URL ?>?action=services">Quản lý dịch vụ</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $isActive('customers'); ?>" href="<?= BASE_URL ?>?action=customers">Quản lý khách hàng</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link" href="">Cài đặt</a>
        </li>
        <li class="nav-item mt-4 px-3">
            <a class="btn btn-sm btn-danger w-100" href="<?= BASE_URL ?>?action=logout">Đăng xuất</a>
        </li>
    </ul>
</aside>
