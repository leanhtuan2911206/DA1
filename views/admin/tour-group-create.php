<?php
$tours = isset($tours) && is_array($tours) ? $tours : [];
$bookings = isset($bookings) && is_array($bookings) ? $bookings : [];
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>?action=tour-management" class="btn btn-outline-secondary">← Quay lại</a>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Tạo mới</p>
            <h2 class="page-title mb-0">Đoàn khách mới</h2>
        </div>
    </div>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card-like">
        <h4 class="mb-3">Tạo đoàn khách mới</h4>
        <form method="post" action="<?= BASE_URL ?>?action=tour-group-store">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Tour <span class="text-danger">*</span></label>
                    <select id="tour_select" class="form-select" name="tour_id" required>
                        <option value="">-- Chọn tour --</option>
                        <?php foreach ($tours as $tour): ?>
                            <option value="<?= $tour['id'] ?>"><?= htmlspecialchars($tour['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Booking</label>
                    <select id="booking_select" class="form-select" name="booking_id">
                        <option value="">-- Chọn booking --</option>
                        <?php foreach ($bookings as $booking): ?>
                            <option value="<?= $booking['id'] ?>"
                                data-tour-id="<?= $booking['tour_id'] ?? '' ?>"
                                data-tour-name="<?= htmlspecialchars($booking['tour_name'] ?? '') ?>"
                                data-customer-name="<?= htmlspecialchars($booking['customer_name'] ?? '') ?>"
                                data-total="<?= (int)($booking['total_people'] ?? 0) ?>">
                                #<?= $booking['id'] ?> - <?= htmlspecialchars($booking['customer_name']) ?><?php if (!empty($booking['tour_name'])): ?> · <?= htmlspecialchars($booking['tour_name']) ?><?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="status">
                        <option value="pending">Chờ xử lý</option>
                        <option value="in_progress">Đang thực hiện</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="cancelled">Đã huỷ</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Tên đoàn <span class="text-danger">*</span></label>
                <input id="group_name" type="text" class="form-control" name="group_name" placeholder="Ví dụ: Đoàn Hà Nội - Hạ Long" required>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-md-6">
                    <label class="form-label">Ngày khởi hành</label>
                    <input type="date" class="form-control" name="start_date">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Ngày kết thúc</label>
                    <input type="date" class="form-control" name="end_date">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Số khách dự kiến <span class="text-danger">*</span></label>
                <input id="total_guests" type="number" class="form-control" name="total_guests" min="1" required>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">Tạo đoàn khách</button>
                <a href="<?= BASE_URL ?>?action=tour-management" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</main>
<script>
    (function(){
        var bookingSelect = document.getElementById('booking_select');
        var tourSelect = document.getElementById('tour_select');
        var groupName = document.getElementById('group_name');
        var totalGuests = document.getElementById('total_guests');

        if (!bookingSelect) return;

        bookingSelect.addEventListener('change', function(){
            var opt = bookingSelect.options[bookingSelect.selectedIndex];
            if (!opt || !opt.value) return;
            var tourId = opt.getAttribute('data-tour-id');
            var tourName = opt.getAttribute('data-tour-name') || '';
            var customer = opt.getAttribute('data-customer-name') || '';
            var total = opt.getAttribute('data-total') || '';

            // Set tour select if tourId available
            if (tourId && tourSelect) {
                tourSelect.value = tourId;
            }

            // If group name empty, prefill with customer + tour name
            if (groupName && groupName.value.trim() === '') {
                var generated = customer;
                if (tourName) generated += ' · ' + tourName;
                groupName.value = generated;
            }

            // Prefill total guests if empty or zero
            if (totalGuests && (totalGuests.value === '' || parseInt(totalGuests.value,10) === 0)) {
                if (total) {
                    totalGuests.value = parseInt(total,10);
                }
            }
        });
    })();
</script>
