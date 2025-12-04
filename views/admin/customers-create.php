<?php
$bookings = isset($bookings) && is_array($bookings) ? $bookings : [];
$selectedBooking = $selectedBooking ?? null;
$bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : ($selectedBooking['id'] ?? 0);
$genderOptions = isset($genderOptions) && is_array($genderOptions)
    ? $genderOptions
    : ['Male' => 'Nam', 'Female' => 'Nữ', 'Other' => 'Khác'];
$paymentStatuses = isset($paymentStatuses) && is_array($paymentStatuses)
    ? $paymentStatuses
    : ['unpaid', 'deposit', 'paid'];
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <?php if ($selectedBooking): ?>
                <a href="<?= BASE_URL ?>?action=customers&booking_id=<?= $selectedBooking['id'] ?>" class="btn btn-outline-secondary">← Danh sách khách</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>?action=customers" class="btn btn-outline-secondary">← Danh sách khách</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Thêm mới</p>
            <h2 class="page-title mb-0">Khách hàng trong booking</h2>
        </div>
        <div>
            <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>">
                <input type="hidden" name="action" value="customers-create">
                <select class="form-select" name="booking_id" onchange="this.form.submit()" style="min-width:260px">
                    <?php if (empty($bookings)): ?>
                        <option value="">Chưa có booking nào</option>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <option value="<?= $booking['id'] ?>" <?= (string) $bookingId === (string) $booking['id'] ? 'selected' : '' ?>>
                                #<?= $booking['id'] ?> · <?= htmlspecialchars($booking['customer_name']) ?> · <?= htmlspecialchars($booking['tour_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <noscript><button class="btn btn-primary">Chọn</button></noscript>
            </form>
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

    <?php if (!$selectedBooking): ?>
        <div class="card-like">
            <div class="py-5 text-center text-muted">
                Vui lòng chọn booking để thêm khách.
            </div>
        </div>
    <?php else: ?>
        <div class="mb-3 p-3 rounded-3 border bg-white d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
            <div>
                <h4 class="mb-1">Booking #<?= htmlspecialchars((string) $selectedBooking['id']) ?> · <?= htmlspecialchars($selectedBooking['customer_name']) ?></h4>
                <p class="text-muted mb-0 small">
                    Tour: <?= htmlspecialchars($selectedBooking['tour_name'] ?? 'Chưa xác định') ?> ·
                    Khởi hành: <?= htmlspecialchars($selectedBooking['start_date']) ?> ·
                    Số khách đăng ký: <?= htmlspecialchars((string) $selectedBooking['total_people']) ?> ·
                    Trạng thái: <?= htmlspecialchars($selectedBooking['status']) ?>
                </p>
            </div>
        </div>

        <div class="card-like">
            <h4 class="mb-3">Thêm khách mới vào Tour</h4>
            <form method="post" action="<?= BASE_URL ?>?action=customers-store">
                <input type="hidden" name="booking_id" value="<?= $selectedBooking['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="full_name" required>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Giới tính</label>
                        <select class="form-select" name="gender">
                            <option value="">-- Chọn --</option>
                            <?php foreach ($genderOptions as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Ngày sinh</label>
                        <input type="date" class="form-control" name="date_of_birth">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Loại giấy tờ</label>
                        <input type="text" class="form-control" name="id_type" placeholder="CCCD, Passport...">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Số giấy tờ</label>
                        <input type="text" class="form-control" name="id_number">
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Điện thoại</label>
                        <input type="text" class="form-control" name="contact_phone">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" class="form-control" name="address">
                </div>
                <div class="mt-3">
                    <label class="form-label">Tình trạng thanh toán</label>
                    <select class="form-select" name="payment_status">
                        <?php $labels = ['unpaid' => 'Chưa thanh toán', 'deposit' => 'Đã đặt cọc', 'paid' => 'Đã thanh toán']; ?>
                        <?php foreach ($paymentStatuses as $status): ?>
                            <option value="<?= $status ?>"><?= $labels[$status] ?? $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mt-3">
                    <label class="form-label">Yêu cầu cá nhân</label>
                    <textarea class="form-control" rows="3" name="special_requests"></textarea>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">Thêm khách</button>
                    <button type="reset" class="btn btn-outline-secondary">Nhập lại</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</main>
