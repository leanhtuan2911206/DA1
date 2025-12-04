<?php
$allGroups = isset($allGroups) && is_array($allGroups) ? $allGroups : [];
$tours = isset($tours) && is_array($tours) ? $tours : [];
$filters = [
    'tour_id' => $_GET['tour_id'] ?? '',
    'status' => $_GET['status'] ?? '',
];
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary">VN</span>
            <div class="avatar rounded-circle bg-secondary-subtle"></div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Quản lý hoạt động</p>
            <h2 class="page-title mb-0">Danh sách đoàn tour</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>?action=tour-group-create" class="btn btn-success">+ Tạo đoàn khách</a>
        </div>
    </div>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success mb-3"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card-like mb-3">
        <h4 class="mb-3">Bộ lọc</h4>
        <form method="get" action="<?= BASE_URL ?>" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="action" value="tour-management">
            <select class="form-select" name="tour_id" style="max-width: 250px;">
                <option value="">-- Tất cả tour --</option>
                <?php foreach ($tours as $tour): ?>
                    <option value="<?= $tour['id'] ?>" <?= (string)$filters['tour_id'] === (string)$tour['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tour['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="status" style="max-width: 200px;">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Chờ khởi hành</option>
                <option value="in_progress" <?= $filters['status'] === 'in_progress' ? 'selected' : '' ?>>Đang diễn hành</option>
                <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
            </select>
            <button class="btn btn-primary">Lọc</button>
            <a href="<?= BASE_URL ?>?action=tour-management" class="btn btn-outline-secondary">Xóa lọc</a>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card-like">
                <h4 class="mb-3">Danh sách đoàn khách</h4>
                <?php if (empty($allGroups)): ?>
                    <div class="py-5 text-center text-muted">
                        Chưa có đoàn khách nào. <a href="<?= BASE_URL ?>?action=tour-group-create">Tạo đoàn khách mới</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên đoàn</th>
                                    <th>Tour</th>
                                    <th>Số khách</th>
                                    <th>Khởi hành</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allGroups as $group): ?>
                                    <tr>
                                        <td class="text-muted small">
                                            #<?= htmlspecialchars((string)($group['id'] ?? '')) ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($group['group_name'] ?? '—') ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($group['tour_name'] ?? '—') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= (int)($group['actual_guests'] ?? 0) ?> / <?= (int)($group['total_guests'] ?? 0) ?></span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($group['start_date'] ?? '—') ?>
                                        </td>
                                        <td>
                                            <?php
                                                $statusMap = [
                                                    'pending' => 'Chờ khởi hành',
                                                    'in_progress' => 'Đang diễn hành',
                                                    'completed' => 'Đã hoàn thành',
                                                ];
                                                $statusClass = [
                                                    'pending' => 'bg-warning text-dark',
                                                    'in_progress' => 'bg-info text-dark',
                                                    'completed' => 'bg-success',
                                                ];
                                                $status = $group['status'] ?? 'pending';
                                            ?>
                                            <span class="badge <?= $statusClass[$status] ?? 'bg-secondary' ?>">
                                                <?= $statusMap[$status] ?? $status ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 align-items-center">
                                                <a href="<?= BASE_URL ?>?action=tour-guests&group_id=<?= $group['id'] ?>" class="btn btn-sm btn-outline-primary" title="Danh sách khách">👥</a>
                                                <a href="<?= BASE_URL ?>?action=tour-guests-print&group_id=<?= $group['id'] ?>" class="btn btn-sm btn-outline-secondary" title="In danh sách">🖨️</a>

                                                <form method="post" action="<?= BASE_URL ?>?action=tour-group-update-status" style="margin:0;">
                                                    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                                    <select name="status" class="form-select form-select-sm" style="display:inline-block;width:auto;min-width:140px;">
                                                        <option value="pending" <?= ($group['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Chờ khởi hành</option>
                                                        <option value="in_progress" <?= ($group['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>Đang diễn hành</option>
                                                        <option value="completed" <?= ($group['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                                                        <option value="cancelled" <?= ($group['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Hủy</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary" style="margin-left:6px;">Cập nhật</button>
                                                </form>
                                                                                                <!-- Delete button triggers modal -->
                                                                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteGroupModal-<?= $group['id'] ?>" style="margin-left:6px;">🗑️ Xóa</button>

                                                                                                <!-- Modal -->
                                                                                                <div class="modal fade" id="deleteGroupModal-<?= $group['id'] ?>" tabindex="-1" aria-labelledby="deleteGroupModalLabel-<?= $group['id'] ?>" aria-hidden="true">
                                                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                                                        <div class="modal-content">
                                                                                                            <div class="modal-header">
                                                                                                                <h5 class="modal-title" id="deleteGroupModalLabel-<?= $group['id'] ?>">Xác nhận xóa đoàn</h5>
                                                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                            </div>
                                                                                                            <div class="modal-body">
                                                                                                                Bạn có chắc muốn xóa đoàn "<strong><?= htmlspecialchars($group['group_name'] ?? '') ?></strong>"? Hành động này không thể hoàn tác.
                                                                                                            </div>
                                                                                                            <div class="modal-footer">
                                                                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                                                                                                                <form method="post" action="<?= BASE_URL ?>?action=tour-group-delete" style="display:inline-block;">
                                                                                                                    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                                                                                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                                                                                                </form>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
