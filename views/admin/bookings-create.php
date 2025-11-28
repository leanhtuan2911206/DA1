<?php
$tours = isset($tours) && is_array($tours) ? $tours : [];
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
            <p class="text-uppercase text-muted small mb-1">Tạo mới</p>
            <h2 class="page-title mb-0">Tạo Booking Mới</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=bookings" class="btn btn-light rounded-pill px-4">← Quay lại</a>
        </div>
    </div>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card-like">
        <form method="post" action="<?= BASE_URL ?>?action=bookings-store">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Tour <span class="text-danger">*</span></label>
                    <select class="form-select" name="tour_id" required>
                        <option value="">-- Chọn tour --</option>
                        <?php foreach ($tours as $tour): ?>
                            <option value="<?= $tour['id'] ?>">
                                <?= htmlspecialchars($tour['name']) ?> - <?= number_format((float)$tour['price'], 0, ',', '.') ?>đ
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Ngày khởi hành <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="start_date" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Tên khách hàng <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="customer_name" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" class="form-control" name="customer_phone">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="customer_email">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Số người <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="total_people" min="1" value="1" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Loại booking <span class="text-danger">*</span></label>
                    <select class="form-select" name="booking_type" required>
                        <option value="individual">Khách lẻ</option>
                        <option value="group">Đoàn (nhiều người, công ty, tổ chức)</option>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Số tiền đặt cọc</label>
                    <input type="number" class="form-control" name="deposit_amount" min="0" step="0.01" value="0">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="pending">Chờ xác nhận</option>
                        <option value="confirmed">Đã xác nhận</option>
                        <option value="deposit">Đã đặt cọc</option>
                        <option value="completed">Hoàn thành</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Yêu cầu đặc biệt</label>
                    <textarea class="form-control" name="special_requests" rows="3"></textarea>
                </div>

                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">Tạo booking</button>
                        <a href="<?= BASE_URL ?>?action=bookings" class="btn btn-light">Hủy</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

