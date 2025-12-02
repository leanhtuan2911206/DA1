<?php
$guest = isset($guest) && is_array($guest) ? $guest : null;
$rooms = isset($rooms) && is_array($rooms) ? $rooms : [];
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <?php if ($guest): ?>
                <a href="<?= BASE_URL ?>?action=tour-guests&group_id=<?= $guest['group_id'] ?>" class="btn btn-outline-secondary">← Danh sách khách</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>?action=tour-management" class="btn btn-outline-secondary">← Quay lại</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Chỉnh sửa</p>
            <h2 class="page-title mb-0">Thông tin khách hàng</h2>
        </div>
    </div>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!$guest): ?>
        <div class="card-like">
            <div class="py-5 text-center text-muted">
                Không tìm thấy khách hàng
            </div>
        </div>
    <?php else: ?>

    <div class="card-like">
        <h4 class="mb-3">Cập nhật: <?= htmlspecialchars($guest['full_name']) ?></h4>
        <form method="post" action="<?= BASE_URL ?>?action=tour-guest-update">
            <input type="hidden" name="id" value="<?= $guest['id'] ?>">
            <input type="hidden" name="group_id" value="<?= $guest['group_id'] ?>">
            
            <div class="mb-3">
                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($guest['full_name']) ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Giới tính</label>
                    <select class="form-select" name="gender">
                        <option value="">-- Chọn --</option>
                        <option value="Male" <?= ($guest['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Nam</option>
                        <option value="Female" <?= ($guest['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Nữ</option>
                        <option value="Other" <?= ($guest['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Khác</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ngày sinh</label>
                    <input type="date" class="form-control" name="date_of_birth" value="<?= htmlspecialchars($guest['date_of_birth'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-md-6">
                    <label class="form-label">Loại giấy tờ</label>
                    <input type="text" class="form-control" name="id_type" value="<?= htmlspecialchars($guest['id_type'] ?? '') ?>" placeholder="CCCD, Passport, ...">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Số giấy tờ</label>
                    <input type="text" class="form-control" name="id_number" value="<?= htmlspecialchars($guest['id_number'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-md-6">
                    <label class="form-label">Điện thoại</label>
                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($guest['phone'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($guest['email'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Địa chỉ</label>
                <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($guest['address'] ?? '') ?>">
            </div>

            <div class="mt-3">
                <label class="form-label">Tình trạng thanh toán</label>
                <select class="form-select" name="payment_status">
                    <option value="unpaid" <?= ($guest['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
                    <option value="deposit" <?= ($guest['payment_status'] ?? '') === 'deposit' ? 'selected' : '' ?>>Đã đặt cọc</option>
                    <option value="paid" <?= ($guest['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                </select>
            </div>

            <div class="mt-3">
                <label class="form-label">Yêu cầu cá nhân</label>
                <textarea class="form-control" rows="3" name="special_requests"><?= htmlspecialchars($guest['special_requests'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">Cập nhật thông tin</button>
                <a href="<?= BASE_URL ?>?action=tour-guests&group_id=<?= $guest['group_id'] ?>" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</main>
