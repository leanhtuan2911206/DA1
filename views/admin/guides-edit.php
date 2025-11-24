<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$guideData = $guide ?? [];
if (!empty($_SESSION['guide_form_old'])) {
    $guideData = array_merge($guideData, $_SESSION['guide_form_old']);
    unset($_SESSION['guide_form_old']);
}
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between mb-4">
        <div>
            <p class="text-uppercase text-muted small mb-1">Chỉnh sửa</p>
            <h2 class="page-title mb-0"><?= htmlspecialchars($guideData['HoTen'] ?? 'Nhân sự') ?></h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=guides" class="btn btn-outline-secondary">← Quay lại danh sách</a>
        </div>
    </div>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>?action=guides-update" class="card-like">
        <input type="hidden" name="id" value="<?= htmlspecialchars($guideData['HDV_ID'] ?? '') ?>">
        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <label class="form-label">Họ và tên *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($guideData['HoTen'] ?? $guideData['name'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Ngày sinh</label>
                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($guideData['NgaySinh'] ?? $guideData['dob'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Giới tính</label>
                <?php $gender = $guideData['GioiTinh'] ?? $guideData['gender'] ?? ''; ?>
                <select name="gender" class="form-select">
                    <option value="">Chọn</option>
                    <option value="Nam" <?= $gender === 'Nam' ? 'selected' : '' ?>>Nam</option>
                    <option value="Nữ" <?= $gender === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                    <option value="Khác" <?= $gender === 'Khác' ? 'selected' : '' ?>>Khác</option>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Số điện thoại / Email *</label>
                <input type="text" name="contact" class="form-control" required value="<?= htmlspecialchars($guideData['LienHe'] ?? $guideData['contact'] ?? '') ?>">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Ngôn ngữ</label>
                <input type="text" name="languages" class="form-control" value="<?= htmlspecialchars($guideData['NgonNgu'] ?? $guideData['languages'] ?? '') ?>">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Địa chỉ</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($guideData['DiaChi'] ?? $guideData['address'] ?? '') ?>">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Chứng chỉ HDV</label>
                <input type="text" name="certificate" class="form-control" value="<?= htmlspecialchars($guideData['ChungChiHDV'] ?? $guideData['certificate'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Kinh nghiệm (năm)</label>
                <input type="number" name="experience" class="form-control" min="0" value="<?= htmlspecialchars($guideData['KinhNghiem'] ?? $guideData['experience'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Ngày bắt đầu làm</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($guideData['NgayBatDauLam'] ?? $guideData['start_date'] ?? '') ?>">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Trạng thái</label>
                <?php $statusVal = $guideData['status'] ?? 'active'; ?>
                <select name="status" class="form-select">
                    <option value="active" <?= $statusVal === 'active' ? 'selected' : '' ?>>Đang làm việc</option>
                    <option value="inactive" <?= $statusVal === 'inactive' ? 'selected' : '' ?>>Tạm dừng</option>
                    <option value="on_leave" <?= $statusVal === 'on_leave' ? 'selected' : '' ?>>Nghỉ phép</option>
                </select>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Tình trạng sức khỏe</label>
                <textarea name="health_status" class="form-control" rows="2"><?= htmlspecialchars($guideData['TrangThaiSucKhoe'] ?? $guideData['health_status'] ?? '') ?></textarea>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Ghi chú nội bộ</label>
                <textarea name="internal_note" class="form-control" rows="2"><?= htmlspecialchars($guideData['GhiChuNoiBo'] ?? $guideData['internal_note'] ?? '') ?></textarea>
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Điểm đánh giá</label>
                <input type="number" name="rating" step="0.1" min="0" max="10" class="form-control" value="<?= htmlspecialchars($guideData['DiemDanhGia'] ?? $guideData['rating'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Nhóm phụ trách</label>
                <input type="number" name="group_id" class="form-control" min="0" value="<?= htmlspecialchars($guideData['hdv_group_id'] ?? $guideData['group_id'] ?? 0) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Nhận xét đánh giá</label>
                <textarea name="review_note" class="form-control" rows="2"><?= htmlspecialchars($guideData['NhanXetDanhGia'] ?? $guideData['review_note'] ?? '') ?></textarea>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Cập nhật mật khẩu</label>
                <input type="text" name="password" class="form-control" placeholder="Để trống nếu không đổi">
                <small class="text-muted">Nhập mật khẩu mới nếu cần thay đổi thông tin đăng nhập.</small>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= BASE_URL ?>?action=guides" class="btn btn-light">Hủy</a>
            <button type="submit" class="btn btn-primary px-4">Lưu thay đổi</button>
        </div>
    </form>
</main>

