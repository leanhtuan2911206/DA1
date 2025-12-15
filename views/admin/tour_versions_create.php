<?php
$tour = isset($tour) ? $tour : [];
$tourId = isset($tour['id']) ? (int)$tour['id'] : 0;
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
            <p class="text-uppercase text-muted small mb-1">Tạo phiên bản</p>
            <h2 class="page-title mb-0">Tạo phiên bản tour: <?= htmlspecialchars($tour['name'] ?? '') ?></h2>
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
        <form method="POST" action="<?= BASE_URL ?>?action=tours-version-store">
            <input type="hidden" name="tour_id" value="<?= $tourId ?>">

            <div class="mb-3">
                <label class="form-label">Loại phiên bản <span class="text-danger">*</span></label>
                <select class="form-select" name="version_type" required>
                    <option value="seasonal">Theo mùa</option>
                    <option value="promotional">Khuyến mãi</option>
                    <option value="special">Đặc biệt</option>
                </select>
                <small class="text-muted">Chọn loại phiên bản: Theo mùa (cao điểm/thấp điểm), Khuyến mãi (giá ưu đãi), Đặc biệt (sự kiện, VIP, lễ)</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Tên phiên bản <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" required placeholder="Ví dụ: Tour mùa cao điểm, Tour khuyến mãi Tết...">
            </div>

            <div class="mb-3">
                <label class="form-label">Giá (VND)</label>
                <input type="number" class="form-control" name="price" placeholder="Để trống nếu giữ giá tour gốc">
                <small class="text-muted">Nếu không nhập, sẽ dùng giá tour gốc: <?= number_format((float)($tour['price'] ?? 0), 0, ',', '.') ?>đ</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Lịch trình</label>
                <textarea class="form-control" name="itinerary" rows="5" placeholder="Nhập lịch trình riêng cho phiên bản này (nếu khác với tour gốc)"><?= htmlspecialchars($tour['itinerary'] ?? '') ?></textarea>
                <small class="text-muted">Lịch trình khác với tour gốc (theo mùa, sự kiện đặc biệt...)</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Dịch vụ bổ sung</label>
                <textarea class="form-control" name="services" rows="3" placeholder="Ví dụ: Bữa tối đặc biệt, Xe đưa đón sân bay, Quà tặng..."></textarea>
                <small class="text-muted">Các dịch vụ bổ sung chỉ có trong phiên bản này</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea class="form-control" name="description" rows="4" placeholder="Mô tả chi tiết về phiên bản này"></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày bắt đầu áp dụng</label>
                    <input type="date" class="form-control" name="start_date">
                    <small class="text-muted">Để trống nếu không giới hạn</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày kết thúc</label>
                    <input type="date" class="form-control" name="end_date">
                    <small class="text-muted">Để trống nếu không giới hạn</small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" name="status">
                    <option value="active" selected>Hoạt động</option>
                    <option value="inactive">Không hoạt động</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Lưu phiên bản</button>
                <a href="<?= BASE_URL ?>?action=tours-versions&tour_id=<?= $tourId ?>" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</main>

