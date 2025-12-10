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
        <form method="post" action="<?= BASE_URL ?>?action=bookings-store" id="booking_form">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Tour <span class="text-danger">*</span></label>
                    <select class="form-select" name="tour_id" id="tour_id"  required>
                        <option value="">-- Chọn tour --</option>
                        <?php foreach ($tours as $tour): ?>
                            <option value="<?= $tour['id'] ?>" data-price="<?= (float)$tour['price'] ?>">
                                <?= htmlspecialchars(removeVNPrefix($tour['name'])) ?> - <?= number_format((float)$tour['price'], 0, ',', '.') ?>đ
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="tour_price_info" class="form-text text-muted" style="display: none"></div>
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
                    <input type="number" class="form-control" name="total_people" id="total_people" min="1" value="1" required>
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
                    <input type="number" class="form-control" name="deposit_amount" id="deposit_amount" min="0" step="0.01" value="0">
                    <div id="deposit_error" class="text-danger small mt-1" style="display: none;"></div>
                    <div id="deposit_info" class="form-text text-muted mt-1" style="display: none;"></div>
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
<script>
// Chờ trang load xong
document.addEventListener('DOMContentLoaded', function() {
    
    // Lấy các phần tử HTML
    var tourSelect = document.getElementById('tour_id');
    var totalPeopleInput = document.getElementById('total_people');
    var depositInput = document.getElementById('deposit_amount');
    var errorDiv = document.getElementById('deposit_error');
    var infoDiv = document.getElementById('deposit_info');
    var form = document.getElementById('booking_form');
    
    // Biến lưu giá tour
    var tourPrice = 0;
    
    // Hàm định dạng số tiền
    function formatMoney(amount) {
        return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + 'đ';
    }
    
    // Hàm kiểm tra số tiền cọc
    function checkDeposit() {
        // Lấy giá trị
        var people = parseInt(totalPeopleInput.value) || 0;
        var deposit = parseFloat(depositInput.value) || 0;
        
        // Nếu chưa chọn tour hoặc chưa nhập số người thì không kiểm tra
        if (tourPrice == 0 || people == 0) {
            errorDiv.style.display = 'none';
            infoDiv.style.display = 'none';
            depositInput.classList.remove('is-invalid');
            return;
        }
        
        // Tính tổng tiền
        var totalPrice = tourPrice * people;
        
        // Hiển thị tổng tiền
        infoDiv.textContent = 'Tổng giá tour: ' + formatMoney(totalPrice);
        infoDiv.style.display = 'block';
        
        // Kiểm tra số tiền cọc
        if (deposit > totalPrice) {
            // Hiển thị lỗi
            errorDiv.textContent = 'Số tiền cọc (' + formatMoney(deposit) + ') vượt quá tổng giá tour (' + formatMoney(totalPrice) + ')!';
            errorDiv.style.display = 'block';
            depositInput.classList.add('is-invalid');
        } else {
            // Ẩn lỗi
            errorDiv.style.display = 'none';
            depositInput.classList.remove('is-invalid');
        }
    }
    
    // Khi chọn tour
    tourSelect.addEventListener('change', function() {
        var option = this.options[this.selectedIndex];
        if (option.value) {
            // Lấy giá tour từ thuộc tính data-price
            tourPrice = parseFloat(option.getAttribute('data-price')) || 0;
        } else {
            tourPrice = 0;
        }
        // Kiểm tra lại
        checkDeposit();
    });
    
    // Khi thay đổi số người
    totalPeopleInput.addEventListener('input', function() {
        checkDeposit();
    });
    
    // Khi thay đổi số tiền cọc
    depositInput.addEventListener('input', function() {
        checkDeposit();
    });
    
    // Kiểm tra trước khi submit form
    form.addEventListener('submit', function(e) {
        var people = parseInt(totalPeopleInput.value) || 0;
        var deposit = parseFloat(depositInput.value) || 0;
        
        if (tourPrice > 0 && people > 0) {
            var totalPrice = tourPrice * people;
            
            // Nếu số tiền cọc vượt quá thì chặn submit
            if (deposit > totalPrice) {
                e.preventDefault(); // Chặn submit
                errorDiv.textContent = 'Số tiền cọc (' + formatMoney(deposit) + ') vượt quá tổng giá tour (' + formatMoney(totalPrice) + ')!';
                errorDiv.style.display = 'block';
                depositInput.classList.add('is-invalid');
                depositInput.focus(); // Focus vào ô input
                return false;
            }
        }
    });
    
});
</script>

