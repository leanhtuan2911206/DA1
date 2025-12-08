<?php
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
                            <option value="<?= (int)$t['id'] ?>">#<?= (int)$t['id'] ?> - <?= htmlspecialchars($t['name'] ?? 'Tour') ?></option>
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
                                    <td class="fw-semibold"><?= htmlspecialchars($t['name'] ?? 'Tour') ?></td>
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
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Quản lý</p>
            <h2 class="page-title mb-0">Nhật ký tour - <?= htmlspecialchars($tour['name'] ?? 'Tour') ?></h2>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form method="get" action="<?= BASE_URL ?>">
                <input type="hidden" name="action" value="tour-logs-list">
                <input type="hidden" name="tour_id" value="<?= (int)($tour['id'] ?? 0) ?>">
                <div class="d-flex align-items-center gap-2 flex-wrap">
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
            <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addLogModal">+ Thêm nhật ký</button>
        </div>
    </div>

    <?php $totalLogs = count($logs); $pendingLogs = 0; $inProgressLogs = 0; $resolvedLogs = 0; foreach ($logs as $l) { $s=$l['status']??''; if($s==='pending') $pendingLogs++; elseif($s==='in_progress') $inProgressLogs++; elseif($s==='resolved') $resolvedLogs++; } ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-primary">Tổng: <?= (int)$totalLogs ?></span>
        <span class="badge bg-warning text-dark">Chờ: <?= (int)$pendingLogs ?></span>
        <span class="badge bg-info text-dark">Đang xử lý: <?= (int)$inProgressLogs ?></span>
        <span class="badge bg-success">Đã giải quyết: <?= (int)$resolvedLogs ?></span>
    </div>

    <ul class="nav nav-pills mb-3" id="logsTabs">
        <li class="nav-item"><a href="#" class="nav-link active" onclick="showSection('table');return false;">Bảng</a></li>
        <li class="nav-item"><a href="#" class="nav-link" onclick="showSection('day');return false;">Theo ngày</a></li>
        <li class="nav-item"><a href="#" class="nav-link" onclick="showSection('guide');return false;">Theo HDV</a></li>
    </ul>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success mb-3"><?= htmlspecialchars($_SESSION['success']) ?></div><?php unset($_SESSION['success']); endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div><?php unset($_SESSION['error']); endif; ?>

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
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editLogModal" data-id="<?= htmlspecialchars((string)$log['id'], ENT_QUOTES, 'UTF-8') ?>" data-title="<?= htmlspecialchars($log['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-description="<?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-status="<?= htmlspecialchars($log['status'] ?? 'pending', ENT_QUOTES, 'UTF-8') ?>" data-rating="<?= htmlspecialchars($log['rating'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-guide-id="<?= htmlspecialchars($log['guide_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">✏️</button>
                                        <a href="<?= BASE_URL ?>?action=tour-logs-delete&id=<?= $log['id'] ?>&tour_id=<?= $tour['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa nhật ký này?')">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Thêm nhật ký tour</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="post" action="<?= BASE_URL ?>?action=tour-logs-store">
                <div class="modal-body">
                    <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">
                    <div class="mb-3"><label class="form-label">Loại nhật ký *</label>
                        <select class="form-select" name="log_type" required onchange="toggleSections(this.value, 'add')">
                            <option value="">-- Chọn loại --</option>
                            <option value="incident">Sự cố</option>
                            <option value="feedback">Phản hồi khách</option>
                            <option value="rating">Đánh giá HDV</option>
                            <option value="timeline">Lịch trình</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Hướng dẫn viên (HDV)</label>
                        <select class="form-select" name="guide_id">
                            <option value="">-- Chọn HDV --</option>
                            <?php foreach ($guides as $g): ?>
                                <option value="<?= (int)$g['HDV_ID'] ?>"><?= htmlspecialchars($g['HoTen'] ?? ('HDV #' . $g['HDV_ID'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Tiêu đề *</label><input type="text" class="form-control" name="title" required></div>
                    <div class="mb-3"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="4"></textarea></div>
                    <div id="add_sections_incident" style="display:none;">
                        <div class="mb-3"><label class="form-label">Thời tiết</label><input class="form-control" name="weather"></div>
                        <div class="mb-3"><label class="form-label">Sức khỏe khách</label><input class="form-control" name="health_status"></div>
                        <div class="mb-3"><label class="form-label">Hoạt động đặc biệt</label><input class="form-control" name="special_activities"></div>
                        <div class="mb-3"><label class="form-label">Cách xử lý</label><textarea class="form-control" name="handling_notes" rows="3"></textarea></div>
                    </div>
                    <div id="add_sections_feedback" style="display:none;">
                        <div class="mb-3"><label class="form-label">Phản hồi khách</label><textarea class="form-control" name="customer_feedback" rows="3"></textarea></div>
                        <div class="mb-3"><label class="form-label">Giai đoạn/Lịch trình</label>
                            <select class="form-select" name="itinerary_id">
                                <option value="">-- Chọn mục --</option>
                                <?php foreach ($itinerary as $item): ?>
                                    <option value="<?= (int)$item['id'] ?>">Ngày <?= htmlspecialchars((string)($item['day_number'] ?? '')) ?> - <?= htmlspecialchars($item['title'] ?? 'Mục') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status"><option value="pending">Chờ xử lý</option><option value="in_progress">Đang xử lý</option><option value="resolved">Đã giải quyết</option></select>
                    </div>
                    <div class="mb-3" id="add_rating_field" style="display:none;">
                        <label class="form-label">Đánh giá (sao) *</label>
                        <select class="form-select" name="rating">
                            <option value="">Không đánh giá</option>
                            <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option>
                        </select>
                        <div class="mt-2">
                            <div class="mb-2"><label class="form-label">Phối hợp</label>
                                <select class="form-select" name="rating_coordination"><option value="">—</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select>
                            </div>
                            <div><label class="form-label">Tinh thần làm việc</label>
                                <select class="form-select" name="rating_spirit"><option value="">—</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Thêm nhật ký</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="editLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Chỉnh sửa nhật ký tour</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="post" action="<?= BASE_URL ?>?action=tour-logs-update">
                <div class="modal-body">
                    <input type="hidden" id="edit_log_id" name="id">
                    <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">
                    <div class="mb-3"><label class="form-label">Hướng dẫn viên (HDV)</label>
                        <select class="form-select" id="edit_guide_id" name="guide_id">
                            <option value="">-- Chọn HDV --</option>
                            <?php foreach ($guides as $g): ?>
                                <option value="<?= (int)$g['HDV_ID'] ?>"><?= htmlspecialchars($g['HoTen'] ?? ('HDV #' . $g['HDV_ID'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Tiêu đề *</label><input type="text" class="form-control" id="edit_title" name="title" required></div>
                    <div class="mb-3"><label class="form-label">Mô tả</label><textarea class="form-control" id="edit_description" name="description" rows="4"></textarea></div>
                    <div class="mb-3"><label class="form-label">Trạng thái</label>
                        <select class="form-select" id="edit_status" name="status"><option value="pending">Chờ xử lý</option><option value="in_progress">Đang xử lý</option><option value="resolved">Đã giải quyết</option></select>
                    </div>
                    <div class="mb-3"><label class="form-label">Đánh giá (sao)</label>
                        <select class="form-select" id="edit_rating" name="rating"><option value="">Không đánh giá</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Cập nhật</button></div>
            </form>
        </div></div>
    </div>

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

    <div class="card-like mt-4" id="section_day" style="display:none;">
        <div class="p-3">
            <h5 class="mb-3">Nhật ký theo ngày</h5>
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

    <?php
    $logsByGuide = [];
    foreach ($logs as $log) {
        $g = trim((string)($log['guide_name'] ?? '—'));
        if (!isset($logsByGuide[$g])) { $logsByGuide[$g] = []; }
        $logsByGuide[$g][] = $log;
    }
    ?>
    <div class="card-like mt-4" id="section_guide" style="display:none;">
        <div class="p-3">
            <h5 class="mb-3">Nhật ký theo HDV</h5>
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
</main>

<script>
function toggleSections(logType, modalId) {
    const ratingField = document.getElementById(modalId + '_rating_field');
    const secIncident = document.getElementById(modalId + '_sections_incident');
    const secFeedback = document.getElementById(modalId + '_sections_feedback');
    if (ratingField) ratingField.style.display = (logType === 'rating') ? 'block' : 'none';
    if (secIncident) secIncident.style.display = (logType === 'incident') ? 'block' : 'none';
    if (secFeedback) secFeedback.style.display = (logType === 'feedback') ? 'block' : 'none';
}
</script>

<script>
function showSection(section){
    var t=document.getElementById('section_table');
    var d=document.getElementById('section_day');
    var g=document.getElementById('section_guide');
    if(t) t.style.display = (section==='table')?'block':'none';
    if(d) d.style.display = (section==='day')?'block':'none';
    if(g) g.style.display = (section==='guide')?'block':'none';
    var tabs=document.querySelectorAll('#logsTabs .nav-link');
    tabs.forEach(function(a){ a.classList.remove('active'); });
    var map={table:0,day:1,guide:2};
    var idx=map[section];
    if(typeof idx!=='undefined' && tabs[idx]) tabs[idx].classList.add('active');
}
const editModal = document.getElementById('editLogModal');
if (editModal) {
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; if (!button) return;
        const id = button.getAttribute('data-id') || '';
        const title = button.getAttribute('data-title') || '';
        const description = button.getAttribute('data-description') || '';
        const status = button.getAttribute('data-status') || 'pending';
        const rating = button.getAttribute('data-rating') || '';
        const gid = button.getAttribute('data-guide-id') || '';
        const idInput = document.getElementById('edit_log_id');
        const titleInput = document.getElementById('edit_title');
        const descInput = document.getElementById('edit_description');
        const statusInput = document.getElementById('edit_status');
        const ratingInput = document.getElementById('edit_rating');
        const guideInput = document.getElementById('edit_guide_id');
        if (idInput) idInput.value = id;
        if (titleInput) titleInput.value = title;
        if (descInput) descInput.value = description;
        if (statusInput) statusInput.value = status;
        if (ratingInput) ratingInput.value = rating;
        if (guideInput) guideInput.value = gid;
    });
}
</script>
