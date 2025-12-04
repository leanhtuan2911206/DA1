<?php
$group = isset($group) && is_array($group) ? $group : null;
$guests = isset($guests) && is_array($guests) ? $guests : [];
$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>?action=tour-management" class="btn btn-outline-secondary">← Quay lại</a>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Chi tiết đoàn</p>
            <h2 class="page-title mb-0">Danh sách khách đoàn</h2>
        </div>
        <?php if ($group): ?>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>?action=tour-guest-add&group_id=<?= $group['id'] ?>" class="btn btn-success">+ Thêm khách</a>
                <a href="<?= BASE_URL ?>?action=tour-guests-print&group_id=<?= $group['id'] ?>" class="btn btn-outline-secondary">🖨️ In danh sách</a>
            </div>
        <?php endif; ?>
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

    <?php if (!$group): ?>
        <div class="card-like">
            <div class="py-5 text-center text-muted">
                Không tìm thấy đoàn khách
            </div>
        </div>
    <?php else: ?>

    <div class="mb-3 p-3 rounded-3 border bg-white d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
        <div>
            <h4 class="mb-1"><?= htmlspecialchars($group['group_name']) ?></h4>
            <p class="text-muted mb-0 small">
                Tour: <?= htmlspecialchars($group['tour_name'] ?? '—') ?> ·
                Khởi hành: <?= htmlspecialchars($group['start_date'] ?? '—') ?> ·
                Số khách: <?= (int)$group['total_guests'] ?> · 
                Trạng thái: <?= htmlspecialchars($group['status'] ?? '—') ?>
            </p>
        </div>
        <div class="text-center">
            <span class="badge bg-primary-subtle text-primary fs-6 px-4 py-2">
                <?= count($guests) ?> / <?= (int)$group['total_guests'] ?> khách
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card-like">
                <h4 class="mb-3">Danh sách khách trong đoàn</h4>
                <?php if (empty($guests)): ?>
                    <div class="py-5 text-center text-muted">
                        Chưa có khách nào. <a href="<?= BASE_URL ?>?action=tour-guest-add&group_id=<?= $group['id'] ?>">Thêm khách</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Họ tên</th>
                                    <th>Thông tin</th>
                                    <th>Thanh toán</th>
                                    <th>Check-in</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guests as $index => $guest): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($guest['full_name']) ?></strong>
                                            <?php if (!empty($guest['gender'])): ?>
                                                <br><small class="text-muted">
                                                    <?= $guest['gender'] === 'Male' ? 'Nam' : ($guest['gender'] === 'Female' ? 'Nữ' : 'Khác') ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($guest['phone'])): ?>
                                                <div>📞 <?= htmlspecialchars($guest['phone']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($guest['email'])): ?>
                                                <div>✉️ <?= htmlspecialchars($guest['email']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($guest['id_number'])): ?>
                                                <div>🪪 <?= htmlspecialchars($guest['id_type'] ?? '') ?> · <?= htmlspecialchars($guest['id_number']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                                $payMap = [
                                                    'unpaid' => 'Chưa thanh toán',
                                                    'deposit' => 'Đã đặt cọc',
                                                    'paid' => 'Đã thanh toán',
                                                ];
                                                $payClass = [
                                                    'unpaid' => 'bg-warning text-dark',
                                                    'deposit' => 'bg-info text-dark',
                                                    'paid' => 'bg-success',
                                                ];
                                                $ps = $guest['payment_status'] ?? 'unpaid';
                                            ?>
                                            <form method="post" action="<?= BASE_URL ?>?action=tour-guest-checkin" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $guest['id'] ?>">
                                                <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                                <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
                                                    <option value="unpaid" <?= $ps === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
                                                    <option value="deposit" <?= $ps === 'deposit' ? 'selected' : '' ?>>Đã đặt cọc</option>
                                                    <option value="paid" <?= $ps === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <?php
                                                $checkInMap = [
                                                    'not_arrived' => 'Chưa đến',
                                                    'arrived' => 'Đã đến',
                                                    'checked_in' => 'Check-in',
                                                ];
                                                $checkInClass = [
                                                    'not_arrived' => 'bg-secondary',
                                                    'arrived' => 'bg-warning text-dark',
                                                    'checked_in' => 'bg-success',
                                                ];
                                                $ci = $guest['checkin_status'] ?? 'not_arrived';
                                            ?>
                                            <form method="post" action="<?= BASE_URL ?>?action=tour-guest-checkin" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $guest['id'] ?>">
                                                <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                                <select name="checkin_status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 130px;">
                                                    <option value="not_arrived" <?= $ci === 'not_arrived' ? 'selected' : '' ?>>Chưa đến</option>
                                                    <option value="arrived" <?= $ci === 'arrived' ? 'selected' : '' ?>>Đã đến</option>
                                                    <option value="checked_in" <?= $ci === 'checked_in' ? 'selected' : '' ?>>Check-in</option>
                                                </select>
                                            </form>
                                        </td>
                                        
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="<?= BASE_URL ?>?action=tour-guest-edit&id=<?= $guest['id'] ?>&group_id=<?= $group['id'] ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                                <a href="<?= BASE_URL ?>?action=tour-guest-delete&id=<?= $guest['id'] ?>&group_id=<?= $group['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa khách <?= htmlspecialchars($guest['full_name']) ?>?')">🗑️</a>
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
    <?php endif; ?>
</main>
