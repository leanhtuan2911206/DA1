<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$userRole = isset($_SESSION['user']['role']) ? strtolower($_SESSION['user']['role']) : '';
$isAdmin = ($userRole === 'admin');

$tour = isset($tour) && is_array($tour) ? $tour : null;
$logs = isset($logs) && is_array($logs) ? $logs : [];
$guides = isset($guides) && is_array($guides) ? $guides : [];
$itinerary = isset($itinerary) && is_array($itinerary) ? $itinerary : [];
try {
    $gModel = new Guide();
    $assigned = $gModel->listAssignedByTour((int)($tour['id'] ?? 0));
    if (!empty($assigned)) { $guides = $assigned; }
} catch (Throwable $e) {}
function extractLogParts(string $desc): array {
    $lines = preg_split('/\r?\n/', trim($desc));
    $out = [
        'weather' => '',
        'health' => '',
        'activities' => '',
        'handling' => '',
        'feedback' => '',
        'coordination' => '',
        'spirit' => ''
    ];
    foreach ($lines as $ln) {
        $l = trim($ln);
        if ($l === '') continue;
        if (stripos($l, 'Thời tiết:') === 0) { $out['weather'] = trim(substr($l, strlen('Thời tiết:'))); }
        elseif (stripos($l, 'Sức khỏe khách:') === 0) { $out['health'] = trim(substr($l, strlen('Sức khỏe khách:'))); }
        elseif (stripos($l, 'Hoạt động đặc biệt:') === 0) { $out['activities'] = trim(substr($l, strlen('Hoạt động đặc biệt:'))); }
        elseif (stripos($l, 'Cách xử lý:') === 0) { $out['handling'] = trim(substr($l, strlen('Cách xử lý:'))); }
        elseif (stripos($l, 'Phản hồi khách:') === 0) { $out['feedback'] = trim(substr($l, strlen('Phản hồi khách:'))); }
        elseif (stripos($l, 'Đánh giá phối hợp:') === 0) { $out['coordination'] = trim(substr($l, strlen('Đánh giá phối hợp:'))); }
        elseif (stripos($l, 'Tinh thần làm việc:') === 0) { $out['spirit'] = trim(substr($l, strlen('Tinh thần làm việc:'))); }
    }
    return $out;
}
?>

<main class="main-content">
    <?php if (!$tour): ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <p class="text-uppercase text-muted small mb-1">Danh mục</p>
                <h2 class="page-title mb-0">Nhật ký tour</h2>
            </div>
        </div>
        <div class="card-like p-3">
            <div class="mb-2">Chọn tour để xem nhật ký theo HDV và theo ngày:</div>
            <form method="get" action="<?= BASE_URL ?>">
                <input type="hidden" name="action" value="tour-logs-list">
                <div class="d-flex flex-wrap gap-2">
                    <select name="tour_id" class="form-select" style="min-width:260px;" required>
                        <option value="">-- Chọn tour --</option>
                        <?php foreach (($tours ?? []) as $t): ?>
                            <option value="<?= (int)$t['id'] ?>">#<?= (int)$t['id'] ?> - <?= htmlspecialchars(removeVNPrefix($t['name'] ?? 'Tour')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="keyword" class="form-control" placeholder="Tìm theo tên/mô tả" style="min-width:260px;" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary">Xem nhật ký</button>
                </div>
            </form>
            <div class="mt-3">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width:70px;">ID</th>
                                <th>Tên tour</th>
                                <th style="width:180px;">Danh mục</th>
                                <th style="width:140px;">Trạng thái</th>
                                <th style="width:140px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tours)): ?>
                                <tr><td colspan="5" class="text-muted text-center">Chưa có tour hoặc không tìm thấy theo từ khóa.</td></tr>
                            <?php else: foreach ($tours as $t): ?>
                                <?php $st = $t['status'] ?? 'Hoạt động'; $cls = 'bg-secondary'; if ($st === 'Hoạt động') $cls='bg-success'; ?>
                                <tr>
                                    <td class="text-muted">#<?= (int)$t['id'] ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars(removeVNPrefix($t['name'] ?? 'Tour')) ?></td>
                                    <td><?= htmlspecialchars($t['category_name'] ?? '—') ?></td>
                                    <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($st) ?></span></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= (int)$t['id'] ?>">Xem nhật ký</a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php return; ?>
<?php endif; ?>
    <?php 
    $bookings = isset($bookings) && is_array($bookings) ? $bookings : [];
    $selectedBookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    ?>
    
    <!-- Danh sách bookings -->
    <?php if (!empty($bookings)): ?>
        <div class="card-like mb-3">
            <h5 class="mb-3">📅 Danh sách Booking của Tour</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th style="width:80px;">ID</th>
                            <th>Khách hàng</th>
                            <th style="width:120px;">Ngày khởi hành</th>
                            <th style="width:100px;">Số người</th>
                            <th style="width:140px;">Trạng thái</th>
                            <th style="width:140px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <?php 
                            $statusLabels = [
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'deposit' => 'Đã đặt cọc',
                                'paid' => 'Đã thanh toán',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Đã hủy'
                            ];
                            $statusClasses = [
                                'pending' => 'bg-warning text-dark',
                                'confirmed' => 'bg-info',
                                'deposit' => 'bg-primary',
                                'paid' => 'bg-success',
                                'completed' => 'bg-success',
                                'cancelled' => 'bg-danger'
                            ];
                            $bookingStatus = $booking['status'] ?? 'pending';
                            $statusText = $statusLabels[$bookingStatus] ?? $bookingStatus;
                            $statusClass = $statusClasses[$bookingStatus] ?? 'bg-secondary';
                            $isSelected = $selectedBookingId === (int)$booking['id'];
                            ?>
                            <tr class="<?= $isSelected ? 'table-primary' : '' ?>">
                                <td class="text-muted">#<?= (int)$booking['id'] ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($booking['customer_name'] ?? '—') ?></td>
                                <td><?= !empty($booking['start_date']) ? date('d/m/Y', strtotime($booking['start_date'])) : '—' ?></td>
                                <td><?= (int)($booking['total_people'] ?? 0) ?> người</td>
                                <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                                <td>
                                    <a class="btn btn-sm <?= $isSelected ? 'btn-primary' : 'btn-outline-primary' ?>" 
                                       href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= (int)$tour['id'] ?>&booking_id=<?= (int)$booking['id'] ?>">
                                        <?= $isSelected ? '✓ Đang xem' : 'Xem nhật ký' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($selectedBookingId > 0): ?>
                <div class="alert alert-info mt-2 mb-0">
                    <strong>📌 Đang xem nhật ký của Booking #<?= $selectedBookingId ?></strong> - 
                    <a href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= (int)$tour['id'] ?>" class="alert-link">Xem tất cả nhật ký của tour</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Quản lý</p>
            <h2 class="page-title mb-0">
                Nhật ký tour - <?= htmlspecialchars(removeVNPrefix($tour['name'] ?? 'Tour')) ?>
                <?php if ($selectedBookingId > 0): ?>
                    <small class="text-muted">(Booking #<?= $selectedBookingId ?>)</small>
                <?php endif; ?>
            </h2>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="get" action="<?= BASE_URL ?>">
                <input type="hidden" name="action" value="tour-logs-list">
                <input type="hidden" name="tour_id" value="<?= (int)($tour['id'] ?? 0) ?>">
                <?php if ($selectedBookingId > 0): ?>
                    <input type="hidden" name="booking_id" value="<?= $selectedBookingId ?>">
                <?php endif; ?>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if (!empty($bookings)): ?>
                        <select name="booking_id" class="form-select" style="min-width:200px;">
                            <option value="">Tất cả Booking</option>
                            <?php foreach ($bookings as $b): ?>
                                <option value="<?= (int)$b['id'] ?>" <?= $selectedBookingId === (int)$b['id'] ? 'selected' : '' ?>>
                                    #<?= (int)$b['id'] ?> - <?= htmlspecialchars($b['customer_name'] ?? '') ?> (<?= !empty($b['start_date']) ? date('d/m/Y', strtotime($b['start_date'])) : '' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <select name="guide_id" class="form-select" style="min-width:200px;">
                        <option value="">HDV</option>
                        <?php foreach ($guides as $g): ?>
                            <option value="<?= (int)$g['HDV_ID'] ?>" <?= (!empty($_GET['guide_id']) && (int)$_GET['guide_id'] === (int)$g['HDV_ID'])?'selected':'' ?>><?= htmlspecialchars($g['HoTen'] ?? ('HDV #' . $g['HDV_ID'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="day" class="form-select" style="min-width:140px;">
                        <option value="">Ngày</option>
                        <?php $days = []; foreach ($itinerary as $it) { $d=(int)($it['day_number']??0); if($d>0) $days[$d]=true; } ksort($days); foreach (array_keys($days) as $d): ?>
                            <option value="<?= $d ?>" <?= (!empty($_GET['day']) && (int)$_GET['day'] === $d)?'selected':'' ?>>Ngày <?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="log_type" class="form-select" style="min-width:160px;">
                        <?php $lt = $_GET['log_type'] ?? ''; ?>
                        <option value="" <?= $lt===''?'selected':'' ?>>Loại</option>
                        <option value="incident" <?= $lt==='incident'?'selected':'' ?>>Sự cố</option>
                        <option value="feedback" <?= $lt==='feedback'?'selected':'' ?>>Phản hồi</option>
                        <option value="rating" <?= $lt==='rating'?'selected':'' ?>>Đánh giá</option>
                        <option value="timeline" <?= $lt==='timeline'?'selected':'' ?>>Lịch trình</option>
                    </select>
                    <select name="status" class="form-select" style="min-width:160px;">
                        <?php $stf = $_GET['status'] ?? ''; ?>
                        <option value="" <?= $stf===''?'selected':'' ?>>Trạng thái</option>
                        <option value="pending" <?= $stf==='pending'?'selected':'' ?>>Chờ xử lý</option>
                        <option value="in_progress" <?= $stf==='in_progress'?'selected':'' ?>>Đang xử lý</option>
                        <option value="resolved" <?= $stf==='resolved'?'selected':'' ?>>Đã giải quyết</option>
                    </select>
                    <button class="btn btn-outline-primary" type="submit">Lọc</button>
                </div>
            </form>
            <?php if (!$isAdmin): ?>
                <a href="<?= BASE_URL ?>?action=tour-logs-create&tour_id=<?= (int)$tour['id'] ?><?= $selectedBookingId > 0 ? '&booking_id=' . $selectedBookingId : '' ?>" class="btn btn-success rounded-pill px-4">+ Thêm nhật ký</a>
            <?php endif; ?>
        </div>
    </div>

    <?php $totalLogs = count($logs); $pendingLogs = 0; $inProgressLogs = 0; $resolvedLogs = 0; foreach ($logs as $l) { $s=$l['status']??''; if($s==='pending') $pendingLogs++; elseif($s==='in_progress') $inProgressLogs++; elseif($s==='resolved') $resolvedLogs++; } ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-primary">Tổng: <?= (int)$totalLogs ?></span>
        <span class="badge bg-warning text-dark">Chờ: <?= (int)$pendingLogs ?></span>
        <span class="badge bg-info text-dark">Đang xử lý: <?= (int)$inProgressLogs ?></span>
        <span class="badge bg-success">Đã giải quyết: <?= (int)$resolvedLogs ?></span>
    </div>

    <?php 
    $currentView = $_GET['view'] ?? 'table';
    $viewParam = $selectedBookingId > 0 ? '&booking_id=' . $selectedBookingId : '';
    $editingLog = isset($editingLog) && is_array($editingLog) ? $editingLog : null;
    ?>
    
    <?php if ($editingLog && !$isAdmin): 
    ?>
    <div class="card-like mb-3">
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">✏️ Chỉnh sửa nhật ký</h5>
                <a href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= (int)$tour['id'] ?><?= $viewParam ?>" class="btn btn-sm btn-secondary">Hủy</a>
            </div>
            <form method="post" action="<?= BASE_URL ?>?action=tour-logs-update" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int)$editingLog['id'] ?>">
                <input type="hidden" name="tour_id" value="<?= (int)$tour['id'] ?>">
                <?php if ($selectedBookingId > 0): ?>
                    <input type="hidden" name="booking_id" value="<?= $selectedBookingId ?>">
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Ngày nhật ký</label>
                        <input type="date" class="form-control" name="log_date" value="<?= htmlspecialchars($editingLog['log_date'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="pending" <?= ($editingLog['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                            <option value="in_progress" <?= ($editingLog['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>Đang xử lý</option>
                            <option value="resolved" <?= ($editingLog['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>Đã giải quyết</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Đánh giá (1-5)</label>
                        <select class="form-select" name="rating">
                            <option value="">-- Không đánh giá --</option>
                            <option value="1" <?= (isset($editingLog['rating']) && (int)$editingLog['rating'] === 1) ? 'selected' : '' ?>>1 - Rất kém</option>
                            <option value="2" <?= (isset($editingLog['rating']) && (int)$editingLog['rating'] === 2) ? 'selected' : '' ?>>2 - Kém</option>
                            <option value="3" <?= (isset($editingLog['rating']) && (int)$editingLog['rating'] === 3) ? 'selected' : '' ?>>3 - Trung bình</option>
                            <option value="4" <?= (isset($editingLog['rating']) && (int)$editingLog['rating'] === 4) ? 'selected' : '' ?>>4 - Tốt</option>
                            <option value="5" <?= (isset($editingLog['rating']) && (int)$editingLog['rating'] === 5) ? 'selected' : '' ?>>5 - Rất tốt</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tiêu đề *</label>
                        <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($editingLog['title'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mô tả / Nội dung *</label>
                        <textarea class="form-control" name="description" rows="6" required><?= htmlspecialchars($editingLog['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Hình ảnh (tải mới để thay thế)</label>
                        <?php if (!empty($editingLog['image_path'])): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL . $editingLog['image_path'] ?>" alt="Ảnh hiện tại" style="max-width:200px;max-height:200px;object-fit:cover;border-radius:4px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="image" accept="image/*">
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">💾 Cập nhật nhật ký</button>
                            <a href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= (int)$tour['id'] ?><?= $viewParam ?>" class="btn btn-secondary">Hủy</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <ul class="nav nav-pills mb-3" id="logsTabs">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= (int)$tour['id'] ?><?= $viewParam ?>&view=table" 
               class="nav-link <?= $currentView === 'table' ? 'active' : '' ?>">Bảng</a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= (int)$tour['id'] ?><?= $viewParam ?>&view=day" 
               class="nav-link <?= $currentView === 'day' ? 'active' : '' ?>">Theo ngày</a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= (int)$tour['id'] ?><?= $viewParam ?>&view=guide" 
               class="nav-link <?= $currentView === 'guide' ? 'active' : '' ?>">Theo HDV</a>
        </li>
    </ul>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success mb-3"><?= htmlspecialchars($_SESSION['success']) ?></div><?php unset($_SESSION['success']); endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div><?php unset($_SESSION['error']); endif; ?>

    <?php if ($currentView === 'table'): ?>
    <div class="card-like" id="section_table">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width:80px;">ID</th>
                        <th style="width:120px;">HDV</th>
                        <th style="width:100px;">Loại</th>
                        <th>Tiêu đề</th>
                        <th style="width:80px;">Đánh giá</th>
                        <th style="width:100px;">Trạng thái</th>
                        <th style="width:150px;">Ngày tạo</th>
                        <th style="width:120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">Chưa có nhật ký nào cho tour này.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                                $typeMap = [
                                    'incident' => ['badge' => 'bg-danger', 'text' => 'Sự cố'],
                                    'feedback' => ['badge' => 'bg-info', 'text' => 'Phản hồi khách'],
                                    'rating' => ['badge' => 'bg-warning', 'text' => 'Đánh giá HDV'],
                                    'timeline' => ['badge' => 'bg-primary', 'text' => 'Lịch trình'],
                                ];
                                $typeInfo = $typeMap[$log['log_type']] ?? ['badge' => 'bg-secondary', 'text' => 'Khác'];
                                $statusMap = [
                                    'pending' => ['badge' => 'bg-warning', 'text' => 'Chờ xử lý'],
                                    'in_progress' => ['badge' => 'bg-info', 'text' => 'Đang xử lý'],
                                    'resolved' => ['badge' => 'bg-success', 'text' => 'Đã giải quyết'],
                                ];
                                $statusInfo = $statusMap[$log['status']] ?? ['badge' => 'bg-secondary', 'text' => 'Không rõ'];
                            ?>
                            <tr>
                                <td class="text-muted"><?= htmlspecialchars((string)$log['id']) ?></td>
                                <td><?= htmlspecialchars($log['guide_name'] ?? '') ?></td>
                                <td><span class="badge <?= $typeInfo['badge'] ?>"><?= $typeInfo['text'] ?></span></td>
                                <td><?= htmlspecialchars($log['title']) ?></td>
                                <td class="text-center"><?php if (!empty($log['rating'])): ?><div class="text-warning">★ <?= (int)$log['rating'] ?>/5</div><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                                <td><span class="badge <?= $statusInfo['badge'] ?>"><?= $statusInfo['text'] ?></span></td>
                                <td class="text-muted"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <?php if ($isAdmin): 
                                            // Admin chỉ có thể đánh giá
                                        ?>
                                            <form method="post" action="<?= BASE_URL ?>?action=tour-logs-update" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $log['id'] ?>">
                                                <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">
                                                <?php if ($selectedBookingId > 0): ?>
                                                    <input type="hidden" name="booking_id" value="<?= $selectedBookingId ?>">
                                                <?php endif; ?>
                                                <select name="rating" class="form-select form-select-sm" style="display:inline-block;width:auto;min-width:100px;" onchange="this.form.submit()">
                                                    <option value="">-- Đánh giá --</option>
                                                    <option value="1" <?= (isset($log['rating']) && (int)$log['rating'] === 1) ? 'selected' : '' ?>>1 - Rất kém</option>
                                                    <option value="2" <?= (isset($log['rating']) && (int)$log['rating'] === 2) ? 'selected' : '' ?>>2 - Kém</option>
                                                    <option value="3" <?= (isset($log['rating']) && (int)$log['rating'] === 3) ? 'selected' : '' ?>>3 - Trung bình</option>
                                                    <option value="4" <?= (isset($log['rating']) && (int)$log['rating'] === 4) ? 'selected' : '' ?>>4 - Tốt</option>
                                                    <option value="5" <?= (isset($log['rating']) && (int)$log['rating'] === 5) ? 'selected' : '' ?>>5 - Rất tốt</option>
                                                </select>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>?action=tour-logs-edit&id=<?= $log['id'] ?>&tour_id=<?= $tour['id'] ?><?= $selectedBookingId > 0 ? '&booking_id=' . $selectedBookingId : '' ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                            <a href="<?= BASE_URL ?>?action=tour-logs-delete&id=<?= $log['id'] ?>&tour_id=<?= $tour['id'] ?><?= $selectedBookingId > 0 ? '&booking_id=' . $selectedBookingId : '' ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa nhật ký này?')">🗑️</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($currentView === 'day'): ?>
    <div class="card-like" id="section_day">
        <div class="p-3">
            <h5 class="mb-3">Nhật ký theo ngày</h5>
            <?php
            $logsByDay = [];
            $dayTitles = [];
            foreach ($itinerary as $item) {
                $day = (int)($item['day_number'] ?? 0);
                if (!isset($logsByDay[$day])) { $logsByDay[$day] = []; }
                $dayTitles[$day] = ($item['title'] ?? 'Mục') . (isset($item['time_start']) ? (' (' . $item['time_start'] . ')') : '');
            }
            foreach ($logs as $log) {
                $day = 0;
                if (!empty($log['itinerary_id'])) {
                    foreach ($itinerary as $item) { if ((int)$item['id'] === (int)$log['itinerary_id']) { $day = (int)($item['day_number'] ?? 0); break; } }
                }
                if (!isset($logsByDay[$day])) { $logsByDay[$day] = []; }
                $logsByDay[$day][] = $log;
            }
            ?>
            <?php if (empty($logsByDay)): ?>
                <div class="text-muted">Chưa có nhật ký theo ngày.</div>
            <?php else: ?>
                <?php ksort($logsByDay); foreach ($logsByDay as $day => $items): ?>
                    <div class="mb-3">
                        <h6 class="mb-2">Ngày <?= $day > 0 ? (int)$day : '—' ?><?= isset($dayTitles[$day]) ? (': ' . htmlspecialchars($dayTitles[$day])) : '' ?></h6>
                        <ul class="list-group">
                            <?php if (empty($items)): ?>
                                <li class="list-group-item small text-muted">Không có nhật ký</li>
                            <?php else: foreach ($items as $log): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?= htmlspecialchars($log['title'] ?? '') ?></strong>
                                            <div class="small text-muted">HDV: <?= htmlspecialchars($log['guide_name'] ?? '') ?> · <?= htmlspecialchars($log['log_type'] ?? '') ?> · <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></div>
                                            <?php $parts = extractLogParts((string)($log['description'] ?? '')); ?>
                                            <div class="mt-2">
                                                <?php if ($parts['weather'] !== ''): ?><div class="small">Thời tiết: <span class="fw-semibold"><?= htmlspecialchars($parts['weather']) ?></span></div><?php endif; ?>
                                                <?php if ($parts['health'] !== ''): ?><div class="small">Sức khỏe khách: <span class="fw-semibold"><?= htmlspecialchars($parts['health']) ?></span></div><?php endif; ?>
                                                <?php if ($parts['activities'] !== ''): ?><div class="small">Hoạt động đặc biệt: <span class="fw-semibold"><?= htmlspecialchars($parts['activities']) ?></span></div><?php endif; ?>
                                                <?php if ($parts['handling'] !== ''): ?><div class="small">Cách xử lý: <span class="fw-semibold"><?= htmlspecialchars($parts['handling']) ?></span></div><?php endif; ?>
                                                <?php if ($parts['feedback'] !== ''): ?><div class="small">Phản hồi khách: <span class="fw-semibold"><?= htmlspecialchars($parts['feedback']) ?></span></div><?php endif; ?>
                                                <?php if ($parts['coordination'] !== '' || $parts['spirit'] !== ''): ?>
                                                    <div class="small">Đánh giá HDV: <?php if ($parts['coordination'] !== ''): ?><span>Phối hợp <?= htmlspecialchars($parts['coordination']) ?></span><?php endif; ?><?php if ($parts['spirit'] !== ''): ?><span class="ms-2">Tinh thần <?= htmlspecialchars($parts['spirit']) ?></span><?php endif; ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($log['description'])): ?><div class="mt-1 small text-muted" style="white-space:pre-line;"><?= htmlspecialchars($log['description']) ?></div><?php endif; ?>
                                        </div>
                                        <div class="text-nowrap"><?php if (!empty($log['rating'])): ?><span class="text-warning">★ <?= (int)$log['rating'] ?>/5</span><?php endif; ?></div>
                                    </div>
                                </li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($currentView === 'guide'): ?>
    <div class="card-like" id="section_guide" style="margin-bottom:0">
        <div class="p-3">
            <h5 class="mb-3">Nhật ký theo HDV</h5>
            <?php
            $logsByGuide = [];
            foreach ($logs as $log) {
                $g = trim((string)($log['guide_name'] ?? '—'));
                if (!isset($logsByGuide[$g])) { $logsByGuide[$g] = []; }
                $logsByGuide[$g][] = $log;
            }
            ?>
            <?php if (empty($logsByGuide)): ?>
                <div class="text-muted">Chưa có nhật ký theo HDV.</div>
            <?php else: foreach ($logsByGuide as $guideName => $items): ?>
                <div class="mb-3">
                    <h6 class="mb-2">HDV: <?= htmlspecialchars($guideName) ?> (<?= count($items) ?> mục)</h6>
                    <ul class="list-group">
                        <?php foreach ($items as $log): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?= htmlspecialchars($log['title'] ?? '') ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars($log['log_type'] ?? '') ?> · <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></div>
                                        <?php $parts = extractLogParts((string)($log['description'] ?? '')); ?>
                                        <div class="mt-2">
                                            <?php if ($parts['weather'] !== ''): ?><div class="small">Thời tiết: <span class="fw-semibold"><?= htmlspecialchars($parts['weather']) ?></span></div><?php endif; ?>
                                            <?php if ($parts['health'] !== ''): ?><div class="small">Sức khỏe khách: <span class="fw-semibold"><?= htmlspecialchars($parts['health']) ?></span></div><?php endif; ?>
                                            <?php if ($parts['activities'] !== ''): ?><div class="small">Hoạt động đặc biệt: <span class="fw-semibold"><?= htmlspecialchars($parts['activities']) ?></span></div><?php endif; ?>
                                            <?php if ($parts['handling'] !== ''): ?><div class="small">Cách xử lý: <span class="fw-semibold"><?= htmlspecialchars($parts['handling']) ?></span></div><?php endif; ?>
                                            <?php if ($parts['feedback'] !== ''): ?><div class="small">Phản hồi khách: <span class="fw-semibold"><?= htmlspecialchars($parts['feedback']) ?></span></div><?php endif; ?>
                                            <?php if ($parts['coordination'] !== '' || $parts['spirit'] !== ''): ?>
                                                <div class="small">Đánh giá HDV: <?php if ($parts['coordination'] !== ''): ?><span>Phối hợp <?= htmlspecialchars($parts['coordination']) ?></span><?php endif; ?><?php if ($parts['spirit'] !== ''): ?><span class="ms-2">Tinh thần <?= htmlspecialchars($parts['spirit']) ?></span><?php endif; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($log['description'])): ?><div class="mt-1 small text-muted" style="white-space:pre-line;">
                                            <?= htmlspecialchars($log['description']) ?>
                                        </div><?php endif; ?>
                                    </div>
                                    <div class="text-nowrap"><?php if (!empty($log['rating'])): ?><span class="text-warning">★ <?= (int)$log['rating'] ?>/5</span><?php endif; ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <?php endif; ?>
</main>