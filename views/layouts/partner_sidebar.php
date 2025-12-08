<?php
$currentAction = $_GET['action'] ?? '/';
$currentTab    = $_GET['tab'] ?? 'detail';


$detailHref      = BASE_URL . '?action=partner&tab=detail';
$assignmentsHref = BASE_URL . '?action=partner&tab=assignments';
<<<<<<< HEAD
$logsHref        = BASE_URL . '?action=partner-logs';

$activeDetail      = ($currentAction === 'partner' && $currentTab === 'detail') ? 'active' : '';
$activeAssignments = ($currentAction === 'partner' && $currentTab === 'assignments') ? 'active' : '';
$activeLogs        = ($currentAction === 'partner-logs') ? 'active' : '';
=======
$itineraryHref   = BASE_URL . '?action=partner&tab=itinerary';

$activeDetail      = ($currentAction === 'partner' && $currentTab === 'detail') ? 'active' : '';
$activeAssignments = ($currentAction === 'partner' && $currentTab === 'assignments') ? 'active' : '';
$activeItinerary   = ($currentAction === 'partner' && $currentTab === 'itinerary') ? 'active' : '';
>>>>>>> 28845ff407d32c5a40ac483cf443d3e1139ad627

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
<<<<<<< HEAD
            <a class="nav-link <?= $activeLogs ?>" href="<?= $logsHref ?>">Nhật ký tour</a>
=======
            <a class="nav-link <?= $activeItinerary ?>" href="<?= $itineraryHref ?>">Lịch trình tour</a>
>>>>>>> 28845ff407d32c5a40ac483cf443d3e1139ad627
        </li>
        <li class="nav-item mt-4 px-3">
            <a class="btn btn-sm btn-danger w-100" href="<?= BASE_URL ?>?action=logout">Đăng xuất</a>
        </li>
    </ul>
</aside>
