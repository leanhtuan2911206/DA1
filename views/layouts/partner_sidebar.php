<?php
$currentAction = $_GET['action'] ?? '/';
$currentTab    = $_GET['tab'] ?? 'detail';


$detailHref      = BASE_URL . '?action=partner&tab=detail';
$assignmentsHref = BASE_URL . '?action=partner&tab=assignments';
$itineraryHref   = BASE_URL . '?action=partner&tab=itinerary';

$activeDetail      = ($currentAction === 'partner' && $currentTab === 'detail') ? 'active' : '';
$activeAssignments = ($currentAction === 'partner' && $currentTab === 'assignments') ? 'active' : '';
$activeItinerary   = ($currentAction === 'partner' && $currentTab === 'itinerary') ? 'active' : '';

$uploadsDir = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$logoFs     = $uploadsDir . 'logo.png';
$logoUrl    = BASE_ASSETS_UPLOADS . 'logo.png';
?>
<aside class="sidebar">
    <div class="mb-4 text-center">
        <?php if (is_file($logoFs)): ?>
            <img src="<?= $logoUrl ?>" alt="Logo" style="max-height:120px">
        <?php else: ?>
            <h4 class="mb-0">HDV</h4>
        <?php endif; ?>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a class="nav-link <?= $activeDetail ?>" href="<?= $detailHref ?>">Thông tin tour</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $activeAssignments ?>" href="<?= $assignmentsHref ?>">Tour được phân công</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link <?= $activeItinerary ?>" href="<?= $itineraryHref ?>">Lịch trình tour</a>
        </li>
        <li class="nav-item mt-4 px-3">
            <a class="btn btn-sm btn-danger w-100" href="<?= BASE_URL ?>?action=logout">Đăng xuất</a>
        </li>
    </ul>
</aside>