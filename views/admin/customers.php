<?php
$bookings = isset($bookings) && is_array($bookings) ? $bookings : [];
$selectedBooking = $selectedBooking ?? null;
$customers = isset($customers) && is_array($customers) ? $customers : [];
$editingCustomer = $editingCustomer ?? null;
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
            <span class="badge bg-primary-subtle text-primary">VN</span>
            <div class="avatar rounded-circle bg-secondary-subtle"></div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Khách theo booking</p>
            <h2 class="page-title mb-0">Quản lý khách hàng</h2>
        </div>
        <div class="d-flex gap-2">
            <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>">
                <input type="hidden" name="action" value="customers">
                <select class="form-select" name="booking_id" onchange="this.form.submit()" style="min-width:260px">
                    <?php if (empty($bookings)): ?>
                        <option value="">Chưa có booking nào</option>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <option value="<?= $booking['id'] ?>" <?= (string) $bookingId === (string) $booking['id'] ? 'selected' : '' ?>>
                                #<?= $booking['id'] ?> · <?= htmlspecialchars($booking['customer_name']) ?> · <?= htmlspecialchars(removeVNPrefix($booking['tour_name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <noscript><button class="btn btn-primary">Chọn</button></noscript>
            </form>
            <?php if ($selectedBooking): ?>
                <a href="<?= BASE_URL ?>?action=customers-create&booking_id=<?= $selectedBooking['id'] ?>" class="btn btn-success">+ Thêm khách</a>
            <?php endif; ?>
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
                <?php if (empty($bookings)): ?>
                    Hiện chưa có booking nào để quản lý khách hàng.
                <?php else: ?>
                    Vui lòng chọn booking ở góc phải phía trên để xem danh sách khách trong đoàn.
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>

    <div class="mb-3 p-3 rounded-3 border bg-white d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
        <div>
            <h4 class="mb-1">Booking #<?= htmlspecialchars((string) $selectedBooking['id']) ?> · <?= htmlspecialchars($selectedBooking['customer_name']) ?></h4>
            <p class="text-muted mb-0 small">
                Tour: <?= htmlspecialchars(removeVNPrefix($selectedBooking['tour_name'] ?? 'Chưa xác định')) ?> ·
                Khởi hành: <?= htmlspecialchars($selectedBooking['start_date']) ?> ·
                Số khách đăng ký: <?= htmlspecialchars((string) $selectedBooking['total_people']) ?> ·
                Trạng thái: <?= htmlspecialchars($selectedBooking['status']) ?>
            </p>
        </div>
        <div class="text-center">
            <span class="badge bg-primary-subtle text-primary fs-6 px-4 py-2">
                <?= count($customers) ?> / <?= (int) $selectedBooking['total_people'] ?> khách đã nhập
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card-like h-100">
                <h4 class="mb-3">Danh sách khách trong đoàn</h4>
                <?php if (empty($customers)): ?>
                    <div class="py-5 text-center text-muted">
                        Chưa có khách nào được thêm cho booking này.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Họ tên</th>
                                    <th>Thông tin</th>
                                    <th>Địa chỉ</th>
                                    <th>Thanh toán</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $index => $customer): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($customer['full_name']) ?></div>
                                            <?php if (!empty($customer['gender'])): ?>
                                                <small class="text-muted"><?= $genderOptions[$customer['gender']] ?? $customer['gender'] ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($customer['date_of_birth'])): ?>
                                                <div>🎂 <?= htmlspecialchars($customer['date_of_birth']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($customer['contact_phone'])): ?>
                                                <div>📞 <?= htmlspecialchars($customer['contact_phone']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($customer['email'])): ?>
                                                <div>✉️ <?= htmlspecialchars($customer['email']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($customer['id_number'])): ?>
                                                <div>🪪 <?= htmlspecialchars(($customer['id_type'] ?? '') . ' · ' . $customer['id_number']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($customer['special_requests'])): ?>
                                                <div class="text-muted small">Yêu cầu: <?= htmlspecialchars($customer['special_requests']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($customer['address'])): ?>
                                                <?= htmlspecialchars($customer['address']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                                $labelMap = [
                                                    'unpaid' => 'Chưa thanh toán',
                                                    'deposit' => 'Đã đặt cọc',
                                                    'paid' => 'Đã thanh toán',
                                                ];
                                                $classMap = [
                                                    'unpaid' => 'bg-warning text-dark',
                                                    'deposit' => 'bg-info text-dark',
                                                    'paid' => 'bg-success',
                                                ];
                                                $ps = $customer['payment_status'] ?? 'unpaid';
                                            ?>
                                            <span class="badge rounded-pill <?= $classMap[$ps] ?? 'bg-secondary' ?>">
                                                <?= $labelMap[$ps] ?? $ps ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="<?= BASE_URL ?>?action=customers-edit&booking_id=<?= $selectedBooking['id'] ?>&id=<?= $customer['id'] ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                                <a href="<?= BASE_URL ?>?action=customers-delete&booking_id=<?= $selectedBooking['id'] ?>&id=<?= $customer['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa khách <?= htmlspecialchars($customer['full_name']) ?> khỏi booking?');">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

