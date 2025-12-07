<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
            <div class="search-wrap">
                <input type="text" class="form-control" placeholder="Tìm kiếm nhanh" readonly/>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary">VN</span>
            <div class="avatar rounded-circle bg-secondary-subtle"></div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Sửa hoạt động cho: <span class="text-primary"><?= htmlspecialchars($tour['name'] ?? '') ?></span></h2>
        <a href="<?= BASE_URL ?>?action=tours-detail&id=<?= $tour['id'] ?? 0 ?>" class="btn btn-secondary">
            &laquo; Quay lại chi tiết
        </a>
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

    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form chỉnh sửa thông tin</h6>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>?action=tours-itinerary-update" method="POST">
                        <input type="hidden" name="id" value="<?= $itinerary['id'] ?>">
                        <input type="hidden" name="tour_id" value="<?= $itinerary['tour_id'] ?>">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ngày thứ (Day)</label>
                                <input type="number" name="day_number" class="form-control" value="<?= $itinerary['day_number'] ?>" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Giờ bắt đầu</label>
                                <input type="text" name="time_start" class="form-control" value="<?= htmlspecialchars($itinerary['time_start'] ?? '') ?>" placeholder="VD: 08:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Địa điểm</label>
                                <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($itinerary['location'] ?? '') ?>" placeholder="VD: Sảnh khách sạn">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên hoạt động <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($itinerary['title']) ?>" required placeholder="VD: Ăn sáng, Tham quan...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả chi tiết</label>
                            <textarea name="description" rows="6" class="form-control"><?= htmlspecialchars($itinerary['description'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?= BASE_URL ?>?action=tours-detail&id=<?= $tour['id'] ?? 0 ?>" class="btn btn-secondary">Hủy bỏ</a>
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
