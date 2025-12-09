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
                <form method="post" action="<?= BASE_URL ?>?action=tour-guests-sync" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn đồng bộ khách từ booking? Các khách trùng tên sẽ bị bỏ qua.');">
                    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                    <button type="submit" class="btn btn-info text-white">🔄 Đồng bộ Booking</button>
                </form>
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
    <?php if (!empty($_SESSION['warning_special'])): ?>
        <div class="alert alert-warning mb-3"><?= htmlspecialchars($_SESSION['warning_special']) ?></div>
        <?php unset($_SESSION['warning_special']); ?>
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
                Booking: <strong>#<?= htmlspecialchars($group['booking_id'] ?? '?') ?></strong> ·
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

    <?php $specials = []; foreach ($guests as $__g) { if (trim($__g['special_requests'] ?? '') !== '') { $specials[] = $__g; } } ?>
    <?php if (!empty($specials)): ?>
        <div class="alert alert-warning mb-3">
            <div><strong>Cảnh báo yêu cầu đặc biệt</strong> · <?= count($specials) ?> khách cần lưu ý</div>
            <ul class="mb-0">
                <?php foreach ($specials as $sg): ?>
                    <li><?= htmlspecialchars($sg['full_name']) ?>: <?= htmlspecialchars($sg['special_requests']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

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
                                    <th>Ghi chú đặc biệt</th>
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
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty(trim($guest['special_requests'] ?? ''))): ?>
                                                    <span class="badge bg-warning text-dark">⚠️</span>
                                                    <span class="small"><?= htmlspecialchars($guest['special_requests']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSpecialRequests<?= $guest['id'] ?>" title="Cập nhật yêu cầu đặc biệt">
                                                    ✏️
                                                </button>
                                            </div>
                                            
                                            <!-- Modal cập nhật yêu cầu đặc biệt -->
                                            <div class="modal fade" id="modalSpecialRequests<?= $guest['id'] ?>" tabindex="-1" aria-labelledby="modalSpecialRequestsLabel<?= $guest['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="modalSpecialRequestsLabel<?= $guest['id'] ?>">
                                                                Cập nhật yêu cầu đặc biệt
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="post" action="<?= BASE_URL ?>?action=tour-guest-update-special-requests">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id" value="<?= $guest['id'] ?>">
                                                                <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Khách:</strong> <?= htmlspecialchars($guest['full_name']) ?></label>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="special_requests_<?= $guest['id'] ?>" class="form-label">
                                                                        Yêu cầu đặc biệt <small class="text-muted">(ăn chay, bệnh lý, dị ứng, v.v.)</small>
                                                                    </label>
                                                                    <textarea 
                                                                        class="form-control" 
                                                                        id="special_requests_<?= $guest['id'] ?>" 
                                                                        name="special_requests" 
                                                                        rows="4" 
                                                                        placeholder="Ví dụ: Ăn chay, Dị ứng hải sản, Bệnh tiểu đường, Không ăn thịt bò, v.v."
                                                                    ><?= htmlspecialchars($guest['special_requests'] ?? '') ?></textarea>
                                                                    <div class="form-text">
                                                                        Ghi nhận, cập nhật và nhắc lại các nhu cầu riêng biệt của khách để chuẩn bị phục vụ phù hợp suốt tour.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Lưu cập nhật</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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
