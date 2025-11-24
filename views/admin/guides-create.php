<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$formData = $_SESSION['guide_form_old'] ?? [];
unset($_SESSION['guide_form_old']);
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between mb-4">
        <div>
            <p class="text-uppercase text-muted small mb-1">Thêm mới</p>
            <h2 class="page-title mb-0">Nhân sự / Hướng dẫn viên</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=guides" class="btn btn-outline-secondary">← Quay lại danh sách</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>?action=guides-store" class="card-like">
        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <label class="form-label">Họ và tên *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($formData['name'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Ngày sinh</label>
                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($formData['dob'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Giới tính</label>
                <select name="gender" class="form-select">
                    <option value="">Chọn</option>
                    <option value="Nam" <?= ($formData['gender'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                    <option value="Nữ" <?= ($formData['gender'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                    <option value="Khác" <?= ($formData['gender'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Số điện thoại / Email *</label>
                <input type="text" name="contact" class="form-control" required value="<?= htmlspecialchars($formData['contact'] ?? '') ?>">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Ngôn ngữ</label>
                <input type="text" name="languages" class="form-control" placeholder="VD: Việt, Anh, Hàn" value="<?= htmlspecialchars($formData['languages'] ?? '') ?>">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Địa chỉ</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($formData['address'] ?? '') ?>">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label">Chứng chỉ HDV</label>
                <input type="text" name="certificate" class="form-control" value="<?= htmlspecialchars($formData['certificate'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Kinh nghiệm (năm)</label>
                <input type="number" name="experience" class="form-control" min="0" value="<?= htmlspecialchars($formData['experience'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Ngày bắt đầu làm</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($formData['start_date'] ?? '') ?>">
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="active" <?= ($formData['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang làm việc</option>
                    <option value="inactive" <?= ($formData['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Tạm dừng</option>
                    <option value="on_leave" <?= ($formData['status'] ?? '') === 'on_leave' ? 'selected' : '' ?>>Nghỉ phép</option>
                </select>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Tình trạng sức khỏe</label>
                <textarea name="health_status" class="form-control" rows="2"><?= htmlspecialchars($formData['health_status'] ?? '') ?></textarea>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Ghi chú nội bộ</label>
                <textarea name="internal_note" class="form-control" rows="2"><?= htmlspecialchars($formData['internal_note'] ?? '') ?></textarea>
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Điểm đánh giá</label>
                <input type="number" name="rating" step="0.1" min="0" max="10" class="form-control" value="<?= htmlspecialchars($formData['rating'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label">Nhóm phụ trách</label>
                <input type="number" name="group_id" class="form-control" min="0" value="<?= htmlspecialchars($formData['group_id'] ?? 0) ?>">
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Nhận xét đánh giá</label>
                <textarea name="review_note" class="form-control" rows="2"><?= htmlspecialchars($formData['review_note'] ?? '') ?></textarea>
            </div>
            <div class="col-12 col-lg-6">
                <label class="form-label">Mật khẩu đăng nhập *</label>
                <input type="text" name="password" class="form-control" required placeholder="Nhập mật khẩu tạm thời" value="<?= htmlspecialchars($formData['password'] ?? '') ?>">
                <small class="text-muted">Hệ thống sẽ tự mã hóa mật khẩu khi lưu.</small>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= BASE_URL ?>?action=guides" class="btn btn-light">Hủy</a>
            <button type="submit" class="btn btn-primary px-4">Lưu nhân sự</button>
        </div>
    </form>
</main>

