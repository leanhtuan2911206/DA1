<?php
$tour = isset($tour) && is_array($tour) ? $tour : null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tour - <?= htmlspecialchars($tour['name'] ?? 'Tour') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 1150px;
            width: 98%;
            margin: 0 auto;
            padding: 50px;
            justify-content: center;
            align-items: center;
        }
        .tour-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
        }
        .booking-body {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        .tour-column {
            flex: 1 1 45%;
            min-width: 0;
        }
        .form-column {
            flex: 1 1 55%;
            min-width: 0;
        }
        .tour-image-wrapper {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 20px;
            background: #f0f0f0;
        }
        .tour-image {
            width: 100%;
            height: auto;
            object-fit: contain;
            display: block;
            border-radius: 10px;
        }
        .tour-details {
            display: flex;
            flex-direction: column;
        }
        .price {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
            margin-top: 10px;
        }
        .tour-name {
            font-size: 26px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .tour-itinerary {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            padding: 12px;
            font-size: 16px;
        }
        @media (max-width: 768px) {
            .booking-card {
                padding: 30px 20px;
                width: 100%;
            }
            .booking-body {
                flex-direction: column;
            }
            .tour-image {
                width: 100%;
                height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="booking-card">
        <h2 class="text-center mb-4">📱 Đăng ký tour nhanh</h2>
        
        <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if ($tour): ?>
            <?php
                // Lấy ảnh tour
                $tourImage = !empty($tour['image']) 
                    ? BASE_URL . ltrim($tour['image'], '/')
                    : BASE_ASSETS_UPLOADS . 'img/1.jpg';
            ?>
            <div class="booking-body">
                <div class="tour-column">
                    <div class="tour-info">
                        <?php if (!empty($tourImage)): ?>
                            <div class="tour-image-wrapper">
                                <img src="<?= htmlspecialchars($tourImage) ?>" alt="<?= htmlspecialchars($tour['name'] ?? 'Tour') ?>" class="tour-image">
                            </div>
                        <?php endif; ?>
                        <div class="tour-details">
                            <h3 class="tour-name"><?= htmlspecialchars(removeVNPrefix($tour['name'] ?? '')) ?></h3>
                            <p class="tour-itinerary mb-3"><?= htmlspecialchars($tour['itinerary'] ?? '') ?></p>
                            <div class="price" id="tour-display-price" data-base-price="<?= (float)($tour['price'] ?? 0) ?>"><?= number_format((float)($tour['price'] ?? 0), 0, ',', '.') ?>đ</div>
                        </div>
                    </div>
                </div>

                <div class="form-column">
                    <form method="post" action="<?= BASE_URL ?>?action=tour-qr-booking-store">
                        <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="customer_phone" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="customer_email">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số người <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="total_people" id="total_people" min="1" value="1" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày khởi hành <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                        </div>

                        <div class="mb-3" id="version_auto_info" style="display: none;">
                            <div class="alert alert-info">
                                <strong>Phiên bản đang áp dụng:</strong> <span id="version_name_display"></span>
                                <br>
                                <small id="version_details_display"></small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Loại booking <span class="text-danger">*</span></label>
                                <select class="form-select" name="booking_type" required>
                                    <option value="individual">Khách lẻ</option>
                                    <option value="group">Đoàn (nhiều người, công ty, tổ chức)</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số tiền đặt cọc</label>
                                <input type="number" class="form-control" name="deposit_amount" id="deposit_amount" min="0" step="0.01" value="0">
                                <div id="deposit_error" class="text-danger small mt-1" style="display: none;"></div>
                                <div id="deposit_info" class="form-text text-muted mt-1" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Chờ xác nhận</option>
                                <option value="confirmed">Đã xác nhận</option>
                                <option value="deposit">Đã đặt cọc</option>
                                <option value="completed">Hoàn thành</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Yêu cầu đặc biệt</label>
                            <textarea class="form-control" name="special_requests" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg">Đăng ký ngay</button>
                    </form>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const els = {
                    start: document.querySelector('input[name="start_date"]'),
                    tourId: document.querySelector('input[name="tour_id"]'),
                    people: document.getElementById('total_people'),
                    deposit: document.getElementById('deposit_amount'),
                    price: document.getElementById('tour-display-price'),
                    vInfo: document.getElementById('version_auto_info'),
                    vName: document.getElementById('version_name_display'),
                    vDetails: document.getElementById('version_details_display'),
                    err: document.getElementById('deposit_error'),
                    info: document.getElementById('deposit_info')
                };
                const basePrice = parseFloat(els.price.dataset.basePrice) || 0;
                let currentPrice = basePrice;
                const formatMoney = n => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + 'đ';

                const checkDeposit = () => {
                    const people = parseInt(els.people.value) || 0;
                    const deposit = parseFloat(els.deposit.value) || 0;
                    if (currentPrice <= 0 || people <= 0) {
                        els.info.style.display = els.err.style.display = 'none';
                        return;
                    }
                    const total = currentPrice * people;
                    els.info.style.display = 'block';
                    els.info.textContent = `Tổng giá tour: ${formatMoney(total)}`;
                    const isOver = deposit > total;
                    els.err.style.display = isOver ? 'block' : 'none';
                    els.err.textContent = `Số tiền cọc (${formatMoney(deposit)}) vượt quá tổng giá tour (${formatMoney(total)})!`;
                    els.deposit.classList.toggle('is-invalid', isOver);
                };

                const updateUI = (price, version = null) => {
                    currentPrice = price;
                    els.price.textContent = formatMoney(price);
                    const isSpecialPrice = version && parseFloat(version.price) > 0;
                    els.price.style.color = isSpecialPrice ? '#d63384' : '#28a745';
                    if (version) {
                        els.vInfo.style.display = 'block';
                        const types = { seasonal: 'Theo mùa', promotional: 'Khuyến mãi', special: 'Đặc biệt' };
                        els.vName.textContent = `${version.name} (${types[version.version_type] || version.version_type})`;
                        const details = [
                            `Giá: ${isSpecialPrice ? formatMoney(price) : 'Dùng giá tour gốc'}`,
                            version.start_date ? `Từ: ${version.start_date}` : '',
                            version.end_date ? `Đến: ${version.end_date}` : ''
                        ].filter(Boolean).join(' | ');
                        els.vDetails.innerHTML = details;
                    } else {
                        els.vInfo.style.display = 'none';
                    }
                    checkDeposit();
                };

                els.start.addEventListener('change', function() {
                    const date = this.value;
                    if (!date) return updateUI(basePrice);
                    fetch(`<?= BASE_URL ?>?action=tours-get-active-version&tour_id=${els.tourId.value}&date=${date}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && data.version) {
                                const vPrice = parseFloat(data.version.price);
                                updateUI(vPrice > 0 ? vPrice : basePrice, data.version);
                            } else {
                                updateUI(basePrice);
                            }
                        })
                        .catch(() => updateUI(basePrice));
                });

                [els.people, els.deposit].forEach(el => el.addEventListener('input', checkDeposit));
                checkDeposit();
            });
            </script>
        <?php else: ?>
            <div class="alert alert-danger">Tour không tồn tại!</div>
        <?php endif; ?>
    </div>
    

</body>
</html>

