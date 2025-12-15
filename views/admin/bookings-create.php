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
                    <input type="date" class="form-control" name="start_date" id="start_date" required>
                    <small class="text-muted"> Hệ thống sẽ tự động áp dụng phiên bản khuyến mãi/ đặc biệt nếu ngày khởi hành nằm trong thời gian áp dụng</small>
                </div>
                 <div class="col-12" id="version_auto_info" style="display: none;">
                    <div class="alert alert-info mb-0">
                        <strong>Phiên bản đang áp dụng:</strong> <span id="version_name_display"></span>
                        <br>
                        <small id="version_details_display"></small>
                    </div>
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
    
    
    // Lấy các phần tử HTML
    var startDateInput = document.getElementById('start_date');
    var versionInfoDiv = document.getElementById('version_auto_info');
    var versionNameDisplay = document.getElementById('version_name_display');
    var versionDetailsDisplay = document.getElementById('version_details_display');
    
    // Biến lưu giá tour
    var tourPrice = 0;
    var currentVersionId = null;
    
    // Hàm định dạng số tiền
    function formatMoney(amount) {
        return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + 'đ';
    }
    
    // Hàm kiểm tra và tải phiên bản tự động theo ngày
    function checkAutoVersion() {
        var tourId = tourSelect.value;
        var startDate = startDateInput.value;
        
        if (!tourId || !startDate) {
            versionInfoDiv.style.display = 'none';
            currentVersionId = null;
            // Dùng giá tour gốc
            var option = tourSelect.options[tourSelect.selectedIndex];
            tourPrice = parseFloat(option.getAttribute('data-price')) || 0;
            checkDeposit();
            return;
        }
        
        // Gọi API để kiểm tra phiên bản đang áp dụng
        fetch('<?= BASE_URL ?>?action=tours-get-active-version&tour_id=' + tourId + '&date=' + startDate)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.version) {
                    // Có phiên bản đang áp dụng
                    currentVersionId = data.version.id;
                    var versionPrice = data.version.price ? parseFloat(data.version.price) : null;
                    
                    if (versionPrice && versionPrice > 0) {
                        tourPrice = versionPrice;
                    } else {
                        // Dùng giá tour gốc
                        var option = tourSelect.options[tourSelect.selectedIndex];
                        tourPrice = parseFloat(option.getAttribute('data-price')) || 0;
                    }
                    
                    // Hiển thị thông tin phiên bản
                    var typeLabels = {
                        'seasonal': 'Theo mùa',
                        'promotional': 'Khuyến mãi',
                        'special': 'Đặc biệt'
                    };
                    var typeLabel = typeLabels[data.version.version_type] || data.version.version_type;
                    versionNameDisplay.textContent = data.version.name + ' (' + typeLabel + ')';
                    
                    var details = [];
                    if (versionPrice && versionPrice > 0) {
                        details.push('Giá: ' + formatMoney(versionPrice));
                    } else {
                        details.push('Giá: Dùng giá tour gốc');
                    }
                    if (data.version.start_date) {
                        details.push('Từ: ' + data.version.start_date);
                    }
                    if (data.version.end_date) {
                        details.push('Đến: ' + data.version.end_date);
                    }
                    versionDetailsDisplay.textContent = details.join(' | ');
                    versionInfoDiv.style.display = 'block';
                } else {
                    // Không có phiên bản nào, dùng tour gốc
                    currentVersionId = null;
                    var option = tourSelect.options[tourSelect.selectedIndex];
                    tourPrice = parseFloat(option.getAttribute('data-price')) || 0;
                    versionInfoDiv.style.display = 'none';
                }
                checkDeposit();
            })
            .catch(error => {
                console.error('Lỗi khi kiểm tra phiên bản:', error);
                // Nếu lỗi, dùng giá tour gốc
                currentVersionId = null;
                var option = tourSelect.options[tourSelect.selectedIndex];
                tourPrice = parseFloat(option.getAttribute('data-price')) || 0;
                versionInfoDiv.style.display = 'none';
                checkDeposit();
            });
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
        var tourId= this.value;
        var option = this.options[this.selectedIndex];
       if (tourId && tourId != '') {
            // Lấy giá tour từ thuộc tính data-price (giá gốc)
            tourPrice = parseFloat(option.getAttribute('data-price')) || 0;
            // Kiểm tra phiên bản tự động nếu đã có ngày
            if (startDateInput.value) {
                checkAutoVersion();
            } else {
                checkDeposit();
            }
        } else {
            tourPrice = 0;
            versionInfoDiv.style.display = 'none';
            checkDeposit();
        }
    });
    
    // Khi thay đổi ngày khởi hành
    startDateInput.addEventListener('change', function() {
        if (tourSelect.value) {
            checkAutoVersion();
        }
    });
    // Khi thay đổi số người
    totalPeopleInput.addEventListener('input', function() {
        checkDeposit();
    });
    
    // Khi thay đổi số tiền cọc
    depositInput.addEventListener('input', function() {
        checkDeposit();
    });
    
    //  thêm hidden input dể lưu tour_version_id
    var hiddenVersionInput= document.createElement('input');
    hiddenVersionInput.type='hidden';
    hiddenVersionInput.name='tour_version_id';
    hiddenVersionInput.id='tour_version_id';
    form.appendChild(hiddenVersionInput);
    // Kiểm tra trước khi submit form
    form.addEventListener('submit', function(e) {
        var people = parseInt(totalPeopleInput.value) || 0;
        var deposit = parseFloat(depositInput.value) || 0;
        
        // Cập nhật tour_version_id trước khi submit
        if(currentVersionId){
            hiddenVersionInput.value=currentVersionId;

        }else{
            hiddenVersionInput.value='';
        }
        
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

