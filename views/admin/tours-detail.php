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
                <h3 class="text-primary mb-3"><?= htmlspecialchars($tour['name']) ?></h3>
                
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

    <div class="card-like">
        <h4 class="mb-4 border-bottom pb-2">Timeline Chi Tiết (Tour thực tế)</h4>
        
        <?php if (empty($itineraries)): ?>
            <div class="alert alert-warning text-center py-4">
                <i class="mb-2 d-block" style="font-size: 2rem;">📅</i>
                Chưa có dữ liệu lịch trình chi tiết cho tour này.<br>
                <span class="small text-muted">(Dữ liệu được lấy từ bảng <b>tour_itineraries</b>)</span>
            </div>
        <?php else: ?>
            <div class="timeline-wrapper">
                <?php 
                    $currentDay = 0; 
                    foreach ($itineraries as $item): 
                ?>
                    <?php if ($item['day_number'] != $currentDay): $currentDay = $item['day_number']; ?>
                        <div class="mt-4 mb-3">
                            <span class="badge bg-primary px-3 py-2 rounded-pill fs-6">Ngày <?= $currentDay ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex align-items-start mb-3 ms-2">
                        <div class="text-center me-3 pt-1" style="min-width: 70px;">
                            <span class="fw-bold text-dark bg-light px-2 py-1 rounded border">
                                <?= htmlspecialchars($item['time_start'] ?? '--:--') ?>
                            </span>
                        </div>
                        
                        <div class="flex-grow-1 p-3 bg-white rounded border border-start-0 border-top-0 border-end-0 shadow-sm" style="border-left: 4px solid #0d6efd !important;">
                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($item['title']) ?></h6>
                            <div class="text-secondary small mb-2" style="white-space: pre-line;">
                                <?= htmlspecialchars($item['description']) ?>
                            </div>
                            <?php if (!empty($item['location'])): ?>
                                <div class="small text-muted border-top pt-2 mt-2">
                                    📍 <?= htmlspecialchars($item['location']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>