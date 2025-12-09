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

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <p class="text-uppercase text-muted small mb-1">Chi tiết</p>
            <h2 class="page-title mb-0">Lịch trình Tour</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=tours" class="btn btn-secondary rounded-pill px-4">
                &laquo; Quay lại danh sách
            </a>
        </div>
    </div>

    <div class="card-like mb-4">
        <div class="d-flex gap-4">
            <div style="width: 300px; flex-shrink: 0;">
                <?php 
                    $imgSrc = !empty($tour['image']) ? BASE_URL . ltrim($tour['image'], '/') : BASE_ASSETS_UPLOADS . 'img/1.jpg';
                ?>
                <img src="<?= $imgSrc ?>" class="img-fluid rounded shadow-sm w-100" style="object-fit: cover; aspect-ratio: 4/3;" alt="Ảnh tour">
            </div>
            
            <div class="flex-grow-1">
                <h3 class="text-primary mb-3"><?= htmlspecialchars(removeVNPrefix($tour['name'])) ?></h3>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted small">Mã Tour</p>
                        <p class="fw-semibold">#<?= $tour['id'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted small">Trạng thái</p>
                        <span class="badge bg-success"><?= $tour['tour_status'] ?? 'Hoạt động' ?></span>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted small">Giá tour</p>
                        <p class="fw-bold text-danger fs-5"><?= number_format($tour['price'], 0, ',', '.') ?> VNĐ</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted small">Địa điểm / Hành trình</p>
                        <p class="fw-semibold"><?= htmlspecialchars($tour['itinerary'] ?? 'Chưa cập nhật') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>