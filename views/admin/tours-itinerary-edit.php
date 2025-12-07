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

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Sửa hoạt động: <span class="text-primary"><?= htmlspecialchars($tour['name']) ?></span></h2>
        </div>
        <a href="<?= BASE_URL ?>?action=tours-detail&id=<?= $tour['id'] ?>" class="btn btn-secondary">
            &laquo; Quay lại chi tiết
        </a>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Cập nhật thông tin</h6>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    
                    <form action="<?= BASE_URL ?>?action=tours-itinerary-update" method="POST">
                        <input type="hidden" name="id" value="<?= $itinerary['id'] ?>">
                        <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ngày thứ (Day)</label>
                                <input type="number" name="day_number" class="form-control" value="<?= $itinerary['day_number'] ?>" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Giờ bắt đầu</label>
                                <input type="text" name="time_start" class="form-control" value="<?= htmlspecialchars($itinerary['time_start']) ?>" placeholder="VD: 08:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Địa điểm</label>
                                <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($itinerary['location']) ?>" placeholder="VD: Sảnh khách sạn">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên hoạt động <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($itinerary['title']) ?>" required placeholder="VD: Ăn sáng, Tham quan...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả chi tiết</label>
                            <textarea name="description" rows="4" class="form-control"><?= htmlspecialchars($itinerary['description']) ?></textarea>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                            <a href="<?= BASE_URL ?>?action=tours-detail&id=<?= $tour['id'] ?>" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
