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
            <h2 class="page-title mb-0">Chi tiết Booking</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=bookings" class="btn btn-secondary rounded-pill px-4">
                &laquo; Quay lại danh sách
            </a>
        </div>
    </div>

    <!-- Thông tin Booking -->
    <div class="card-like mb-4">
        <h4 class="mb-3">Thông tin Booking</h4>
        <div class="row g-3">
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Mã Booking</p>
                <p class="fw-semibold">#<?= $booking['id'] ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Trạng thái</p>
                <?php
                    $statusText = '';
                    $statusClass = '';
                    switch ($booking['status']) {
                        case 'pending': $statusText = 'Chờ xác nhận'; $statusClass = 'bg-warning text-dark'; break;
                        case 'confirmed': $statusText = 'Đã xác nhận'; $statusClass = 'bg-info'; break;
                        case 'deposit': $statusText = 'Đã đặt cọc'; $statusClass = 'bg-primary'; break;
                        case 'completed': $statusText = 'Hoàn thành'; $statusClass = 'bg-success'; break;
                        case 'cancelled': $statusText = 'Đã hủy'; $statusClass = 'bg-danger'; break;
                        default: $statusText = $booking['status']; $statusClass = 'bg-secondary';
                    }
                ?>
                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Khách hàng</p>
                <p class="fw-semibold"><?= htmlspecialchars($booking['customer_name']) ?></p>
                <?php if (!empty($booking['customer_phone'])): ?>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($booking['customer_phone']) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Ngày khởi hành</p>
                <p class="fw-semibold"><?= htmlspecialchars($booking['start_date']) ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Số người</p>
                <p class="fw-semibold"><?= $booking['total_people'] ?> người</p>
            </div>
            <div class="col-md-6">
                <p class="mb-1 text-muted small">Loại booking</p>
                <span class="badge <?= $booking['booking_type'] === 'group' ? 'bg-primary' : 'bg-secondary' ?>">
                    <?= $booking['booking_type'] === 'group' ? 'Đoàn' : 'Khách lẻ' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Thông tin Tour -->
    <?php if ($tour): ?>
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
    <?php endif; ?>

    <!-- Nhật ký tour (chỉ xem lại nhật ký HDV đã ghi) -->
    <?php if ($tour): ?>
    <div class="card-like mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <h4 class="mb-0">📝 Nhật ký tour</h4>
            <span class="text-muted small">Xem lại nhật ký HDV đã ghi và phản ánh/đánh giá của khách hàng</span>
        </div>
        
        <?php if (empty($tourLogs)): ?>
            <div class="alert alert-info text-center py-4">
                <p class="mb-0">HDV chưa ghi nhật ký nào cho booking này.</p>
                <p class="small text-muted mt-2">Nhật ký sẽ được hiển thị khi HDV ghi lại diễn biến tour, phản ánh sự cố hoặc phản hồi của khách hàng.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Ngày</th>
                            <th style="width: 150px;">Loại</th>
                            <th>Tiêu đề</th>
                            <th style="width: 150px;">HDV</th>
                            <th style="width: 120px;">Trạng thái</th>
                            <th style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tourLogs as $log): ?>
                            <?php
                                $logTypeText = [
                                    'incident' => 'Sự cố',
                                    'feedback' => 'Phản hồi',
                                    'rating' => 'Đánh giá HDV',
                                    'timeline' => 'Lịch trình',
                                    'daily' => 'Nhật ký ngày'
                                ][$log['log_type'] ?? ''] ?? $log['log_type'] ?? 'Khác';
                                
                                $statusText = [
                                    'pending' => 'Chờ xử lý',
                                    'in_progress' => 'Đang xử lý',
                                    'resolved' => 'Đã giải quyết'
                                ][$log['status'] ?? ''] ?? $log['status'] ?? 'pending';
                                
                                $statusClass = [
                                    'pending' => 'bg-warning text-dark',
                                    'in_progress' => 'bg-info',
                                    'resolved' => 'bg-success'
                                ][$log['status'] ?? ''] ?? 'bg-secondary';
                                
                                $logDate = $log['log_date'] ?? $log['created_at'] ?? '';
                                if ($logDate) {
                                    $dateObj = new DateTime($logDate);
                                    $logDate = $dateObj->format('d/m/Y');
                                }
                            ?>
                            <tr>
                                <td><?= $logDate ?></td>
                                <td><span class="badge bg-primary"><?= $logTypeText ?></span></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($log['title']) ?></div>
                                    <?php if (!empty($log['description'])): ?>
                                        <div class="text-muted small mt-1" style="max-height: 60px; overflow: hidden; text-overflow: ellipsis;">
                                            <?= nl2br(htmlspecialchars(mb_substr($log['description'], 0, 150))) ?><?= mb_strlen($log['description']) > 150 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($log['image_path'])): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-secondary">📷 Có ảnh</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($log['guide_name'] ?? '—') ?></td>
                                <td><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                <td>
                                    <a href="<?= BASE_URL ?>?action=tour-logs-list&tour_id=<?= $tour['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                        👁️ Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Lịch trình -->
    <div class="card-like">
       <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <h4 class="mb-0">Timeline Chi Tiết (Tour thực tế)</h4>
            <?php if ($tour): ?>
                <a href="<?= BASE_URL ?>?action=bookings-itinerary-create&booking_id=<?= $booking['id'] ?>" class="btn btn-primary btn-sm">
                    + Thêm hoạt động
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($itineraries)): ?>
            <div class="alert alert-warning text-center py-4">
                <i class="mb-2 d-block" style="font-size: 2rem;">📅</i>
                Chưa có dữ liệu lịch trình chi tiết cho tour này.<br>
                <span class="small text-muted">(Dữ liệu được lấy từ bảng <b>tour_itineraries</b>)</span>
            </div>
        <?php else: ?>
            <?php
                // Nhóm lịch trình theo ngày - xử lý cẩn thận để không bỏ sót ngày nào
                $groupedByDay = [];
                $seenItemIds = []; // Để tránh duplicate
                
                foreach ($itineraries as $item) {
                    $itemId = isset($item['id']) ? (int)$item['id'] : 0;
                    
                    // Loại bỏ duplicate dựa trên ID (chỉ nếu ID > 0)
                    if ($itemId > 0 && isset($seenItemIds[$itemId])) {
                        continue;
                    }
                    if ($itemId > 0) {
                        $seenItemIds[$itemId] = true;
                    }
                    
                    // Xử lý day_number - đảm bảo là integer và >= 1
                    $rawDayNum = $item['day_number'] ?? null;
                    $dayNum = 1; // Mặc định
                    
                    if ($rawDayNum !== null) {
                        if (is_numeric($rawDayNum)) {
                            $dayNum = (int)$rawDayNum;
                        } elseif (is_string($rawDayNum)) {
                            $dayNum = (int)trim($rawDayNum);
                        } else {
                            $dayNum = (int)$rawDayNum;
                        }
                    }
                    
                    // Đảm bảo day_number >= 1
                    $dayNum = max(1, $dayNum);
                    
                    // Nhóm vào mảng
                    if (!isset($groupedByDay[$dayNum])) {
                        $groupedByDay[$dayNum] = [];
                    }
                    $groupedByDay[$dayNum][] = $item;
                }
                
                // Sắp xếp theo ngày
                ksort($groupedByDay);
                
                // Lấy tất cả các ngày có dữ liệu
                $allDays = array_keys($groupedByDay);
                
                // Lấy ngày được chọn từ GET parameter
                $selectedDayNum = isset($_GET['day']) ? (int)$_GET['day'] : 0;
                
                // Nếu không có day trong GET hoặc day không hợp lệ, chọn ngày đầu tiên có dữ liệu
                if ($selectedDayNum <= 0 || !isset($groupedByDay[$selectedDayNum])) {
                    if (!empty($allDays)) {
                        $selectedDayNum = min($allDays);
                    } else {
                        $selectedDayNum = 1;
                    }
                }
            ?>
            
            <!-- Day Selector -->
            <?php 
                // Tính số ngày tối đa để hiển thị
                $maxDay = !empty($allDays) ? max($allDays) : 1;
                
                // Nếu có nhiều hơn 1 ngày hoặc có ngày > 1, hiển thị selector
                if ($maxDay > 1 || count($allDays) > 1): 
            ?>
                <div class="mb-4 d-flex align-items-center gap-3 flex-wrap">
                    <label class="fw-bold mb-0">Chọn ngày:</label>
                    <div class="btn-group" role="group">
                        <?php 
                            // Hiển thị tất cả các ngày từ 1 đến maxDay để người dùng thấy đầy đủ
                            for ($dayNum = 1; $dayNum <= $maxDay; $dayNum++): 
                                $hasData = isset($groupedByDay[$dayNum]);
                                $itemCount = $hasData ? count($groupedByDay[$dayNum]) : 0;
                        ?>
                            <a href="<?= BASE_URL ?>?action=bookings-detail&id=<?= $booking['id'] ?>&day=<?= $dayNum ?>" 
                               class="btn <?= $selectedDayNum == $dayNum ? 'btn-primary' : ($hasData ? 'btn-outline-primary' : 'btn-outline-secondary') ?>"
                               title="<?= $hasData ? 'Có ' . $itemCount . ' hoạt động' : 'Chưa có hoạt động' ?>"
                               style="<?= !$hasData ? 'opacity: 0.6;' : '' ?>">
                                Ngày <?= $dayNum ?> <?= !$hasData ? '<small>(Trống)</small>' : '<small>(' . $itemCount . ')</small>' ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="timeline-wrapper">
                <?php 
                    // Chỉ hiển thị lịch trình của ngày được chọn
                    $dayItems = isset($groupedByDay[$selectedDayNum]) ? $groupedByDay[$selectedDayNum] : [];
                    
                    if (empty($dayItems)): ?>
                        <div class="alert alert-info text-center py-4">
                            Không có lịch trình cho ngày <?= $selectedDayNum ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($dayItems as $item): ?>
                    <div class="d-flex align-items-start mb-3 ms-2">
                        <div class="text-center me-3 pt-1" style="min-width: 70px;">
                            <span class="fw-bold text-dark bg-light px-2 py-1 rounded border">
                                <?= htmlspecialchars($item['time_start'] ?? '--:--') ?>
                            </span>
                        </div>
                        <div class="flex-grow-1 p-3 bg-white rounded border border-start-0 border-top-0 border-end-0 shadow-sm position-relative" style="border-left: 4px solid #0d6efd !important;">
                            <div class="position-absolute top-0 end-0 mt-2 me-2">
                                <a href="<?= BASE_URL ?>?action=tours-itinerary-edit&id=<?= $item['id'] ?>&booking_id=<?= $booking['id'] ?>" class="btn btn-sm btn-link text-secondary p-0 me-2" title="Sửa">✏️</a>
                                <a href="<?= BASE_URL ?>?action=tours-itinerary-delete&id=<?= $item['id'] ?>&tour_id=<?= $tour['id'] ?>&booking_id=<?= $booking['id'] ?>" class="btn btn-sm btn-link text-danger p-0" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa lịch trình này?')">🗑️</a>
                            </div>
                            <h6 class="fw-bold text-dark mb-1 pe-5"><?= htmlspecialchars($item['title']) ?></h6>
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
                    <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

