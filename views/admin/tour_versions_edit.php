<?php
$version = isset($version) ? $version : [];
$tour = isset($tour) ? $tour : [];
$versionId = isset($version['id']) ? (int)$version['id'] : 0;
$tourId = isset($version['tour_id']) ? (int)$version['tour_id'] : 0;
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
            <p class="text-uppercase text-muted small mb-1">Sửa phiên bản</p>
            <h2 class="page-title mb-0">Sửa phiên bản tour: <?= htmlspecialchars($version['name'] ?? '') ?></h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=tours-versions&tour_id=<?= $tourId ?>" class="btn btn-secondary rounded-pill px-4">← Quay lại</a>
        </div>
    </div>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card-like">
        <form method="POST" action="<?= BASE_URL ?>?action=tours-version-update">
            <input type="hidden" name="id" value="<?= $versionId ?>">

            <div class="mb-3">
                <label class="form-label">Loại phiên bản <span class="text-danger">*</span></label>
                <select class="form-select" name="version_type" required>
                    <option value="seasonal" <?= ($version['version_type'] ?? '') === 'seasonal' ? 'selected' : '' ?>>Theo mùa</option>
                    <option value="promotional" <?= ($version['version_type'] ?? '') === 'promotional' ? 'selected' : '' ?>>Khuyến mãi</option>
                    <option value="special" <?= ($version['version_type'] ?? '') === 'special' ? 'selected' : '' ?>>Đặc biệt</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Tên phiên bản <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($version['name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Giá (VND)</label>
                <input type="number" class="form-control" name="price" value="<?= htmlspecialchars($version['price'] ?? '') ?>" placeholder="Để trống nếu giữ giá tour gốc">
                <small class="text-muted">Nếu không nhập, sẽ dùng giá tour gốc</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Lịch trình</label>
                <textarea class="form-control" name="itinerary" rows="5"><?= htmlspecialchars($version['itinerary'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Dịch vụ bổ sung</label>
                <textarea class="form-control" name="services" rows="3"><?= htmlspecialchars($version['services'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($version['description'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày bắt đầu áp dụng</label>
                    <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($version['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày kết thúc</label>
                    <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($version['end_date'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" name="status">
                    <option value="active" <?= ($version['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="inactive" <?= ($version['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Không hoạt động</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Cập nhật</button>
                <a href="<?= BASE_URL ?>?action=tours-versions&tour_id=<?= $tourId ?>" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</main>

