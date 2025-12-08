<?php
$bookingsGrouped = isset($bookingsGrouped) && is_array($bookingsGrouped) ? $bookingsGrouped : [];
$tours = isset($tours) && is_array($tours) ? $tours : [];
$filters = [
    'tour_id' => $_GET['tour_id'] ?? '',
    'status' => $_GET['status'] ?? '',
    'booking_type' => $_GET['booking_type'] ?? '',
    'customer_name' => $_GET['customer_name'] ?? '',
    'start_date_from' => $_GET['start_date_from'] ?? '',
    'start_date_to' => $_GET['start_date_to'] ?? '',
];
?>

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

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Danh sách</p>
            <h2 class="page-title mb-0">Quản lý Booking</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=bookings-create" class="btn btn-success rounded-pill px-4">+ Tạo booking mới</a>
        </div>
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

    <!-- Bộ lọc -->
    <div class="card-like mb-4">
        <form class="filter-bar" method="get" action="<?= BASE_URL ?>">
            <input type="hidden" name="action" value="bookings">

            <div class="filter-inputs row g-3 flex-grow-1 w-100 align-items-center">
                <div class="col-12 col-lg-3 col-xl-2">
                    <select class="form-select form-select-sm" name="tour_id">
                        <option value="">Tất cả tour</option>
                        <?php foreach ($tours as $tour): ?>
                            <option value="<?= $tour['id'] ?>" <?= (string)$filters['tour_id'] === (string)$tour['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tour['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <select class="form-select form-select-sm" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                        <option value="confirmed" <?= $filters['status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                        <option value="deposit" <?= $filters['status'] === 'deposit' ? 'selected' : '' ?>>Đã đặt cọc</option>
                        <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <select class="form-select form-select-sm" name="booking_type">
                        <option value="">Tất cả loại</option>
                        <option value="individual" <?= $filters['booking_type'] === 'individual' ? 'selected' : '' ?>>Khách lẻ</option>
                        <option value="group" <?= $filters['booking_type'] === 'group' ? 'selected' : '' ?>>Đoàn</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <input
                        class="form-control form-control-sm"
                        name="customer_name"
                        value="<?= htmlspecialchars($filters['customer_name']) ?>"
                        placeholder="Tên khách hàng"
                    />
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <input
                        type="date"
                        class="form-control form-control-sm"
                        name="start_date_from"
                        value="<?= htmlspecialchars($filters['start_date_from']) ?>"
                        placeholder="Từ ngày"
                    />
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <input
                        type="date"
                        class="form-control form-control-sm"
                        name="start_date_to"
                        value="<?= htmlspecialchars($filters['start_date_to']) ?>"
                        placeholder="Đến ngày"
                    />
                </div>
                <div class="col-12 col-lg-auto ms-lg-auto">
                    <div class="filter-actions d-flex align-items-center gap-2 justify-content-lg-end">
                        <button class="btn btn-sm btn-warning px-3 py-1 d-inline-flex align-items-center rounded-pill" type="submit">Tìm kiếm</button>
                        <a class="btn btn-sm btn-light text-secondary px-3 py-1 d-inline-flex align-items-center rounded-pill" href="<?= BASE_URL ?>?action=bookings">Đặt lại</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Danh sách booking theo tour -->
    <?php if (empty($bookingsGrouped)): ?>
        <div class="card-like">
            <div class="text-center text-muted py-5">
                Không có booking nào phù hợp với bộ lọc.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($bookingsGrouped as $tourGroup): ?>
            <div class="card-like mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div>
                        <h4 class="mb-1"><?= htmlspecialchars($tourGroup['tour_name']) ?></h4>
                        <p class="text-muted small mb-0">
                            Loại: <?= htmlspecialchars($tourGroup['category_name']) ?> | 
                            Giá: <?= number_format((float)$tourGroup['tour_price'], 0, ',', '.') ?>đ | 
                            Số booking: <?= count($tourGroup['bookings']) ?>
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th>Khách hàng</th>
                                <th>Ngày khởi hành</th>
                                <th>Số người</th>
                                <th>Loại</th>
                                <th>Đặt cọc</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                                <th>Chi tiết</th>
                                <th>Danh sách khách</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tourGroup['bookings'] as $booking): ?>
                                <?php
                                    $statusText = '';
                                    $statusClass = '';
                                    switch ($booking['status']) {
                                        case 'pending':
                                            $statusText = 'Chờ xác nhận';
                                            $statusClass = 'bg-warning text-dark';
                                            break;
                                        case 'confirmed':
                                            $statusText = 'Đã xác nhận';
                                            $statusClass = 'bg-info';
                                            break;
                                        case 'deposit':
                                            $statusText = 'Đã đặt cọc';
                                            $statusClass = 'bg-primary';
                                            break;
                                        case 'completed':
                                            $statusText = 'Hoàn thành';
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'cancelled':
                                            $statusText = 'Đã hủy';
                                            $statusClass = 'bg-danger';
                                            break;
                                        default:
                                            $statusText = $booking['status'];
                                            $statusClass = 'bg-secondary';
                                    }

                                    $bookingTypeText = $booking['booking_type'] === 'group' ? 'Đoàn' : 'Khách lẻ';
                                    $bookingTypeClass = $booking['booking_type'] === 'group' ? 'bg-primary' : 'bg-secondary';
                                ?>
                                <tr>
                                    <td class="text-muted"><?= htmlspecialchars((string)$booking['id']) ?></td>
                                    <td>
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($booking['customer_name']) ?></div>
                                            <?php if (!empty($booking['customer_phone'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($booking['customer_phone']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($booking['customer_email'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($booking['customer_email']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($booking['start_date']) ?></td>
                                    <td><?= htmlspecialchars((string)$booking['total_people']) ?> người</td>
                                    <td>
                                        <span class="badge rounded-pill <?= $bookingTypeClass ?>">
                                            <?= $bookingTypeText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= number_format((float)$booking['deposit_amount'], 0, ',', '.') ?>đ
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-2">
                                            <!-- Form cập nhật trạng thái nhanh -->
                                            <form method="post" action="<?= BASE_URL ?>?action=bookings-update-status" style="display: inline;">
                                                <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; display: inline-block;">
                                                    <option value="pending" <?= $booking['status'] === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                                    <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                                                    <option value="deposit" <?= $booking['status'] === 'deposit' ? 'selected' : '' ?>>Đã đặt cọc</option>
                                                    <option value="completed" <?= $booking['status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                                    <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                                </select>
                                            </form>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <a href="<?= BASE_URL ?>?action=bookings-itinerary-create&booking_id=<?= $booking['id'] ?>" 
                                                   class="btn btn-sm btn-outline-success" 
                                                   title="Thêm lịch trình">
                                                    📅 Thêm lịch trình
                                                </a>
                                                <a href="<?= BASE_URL ?>?action=bookings-edit&id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                                <a href="<?= BASE_URL ?>?action=bookings-delete&id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa booking này không?')">🗑️</a>
                                                <?php if (!empty($booking['has_assignment'])): ?>
                                                    <a href="<?= BASE_URL ?>?action=assignments&booking_id=<?= $booking['id'] ?>" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="Đã phân công: <?= htmlspecialchars($booking['assignment']['guide_name'] ?? 'HDV') ?>">
                                                        👤 Đã phân công
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= BASE_URL ?>?action=assignments-create&booking_id=<?= $booking['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        ➕ Phân công
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>?action=bookings-detail&id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-info">
                                            👁️ Xem chi tiết
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>?action=customers&booking_id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-primary">Xem danh sách</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

