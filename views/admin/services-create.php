<?php
?>
<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="page-title mb-0">Đặt dịch vụ</div>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=services">← Danh sách</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card-like">
        <form method="post" action="<?= BASE_URL ?>?action=services-store">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Booking</label>
                    <select class="form-select" name="booking_id" required>
                        <option value="">-- Chọn booking --</option>
                        <?php foreach (($bookings ?? []) as $b): ?>
                            <option value="<?= $b['id'] ?>">#<?= $b['id'] ?> - <?= htmlspecialchars($b['tour_name'] ?? ($b['customer_name'] ?? '')) ?> (<?= htmlspecialchars($b['start_date'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Loại dịch vụ</label>
                    <select class="form-select" name="service_type" required>
                        <?php foreach ([['vehicle','Xe'],['hotel','Khách sạn'],['flight','Vé máy bay'],['restaurant','Nhà hàng'],['activity','Tham quan']] as [$v,$t]): ?>
                            <option value="<?= $v ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Nhà cung cấp</label>
                    <input type="text" class="form-control" name="supplier_name" required>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Số lượng</label>
                    <input type="number" class="form-control" name="quantity" min="1" value="1">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="status">
                        <?php foreach ([["pending","Chờ"],["confirmed","Xác nhận"],["completed","Hoàn tất"],["canceled","Hủy"],["active","Đang hoạt động"],["inactive","Tạm nghỉ"]] as [$v,$t]): ?>
                            <option value="<?= $v ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Thời gian bắt đầu</label>
                    <input type="datetime-local" class="form-control" name="start_time">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Thời gian kết thúc</label>
                    <input type="datetime-local" class="form-control" name="end_time">
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="notes" rows="3"></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=services">Hủy</a>
            </div>
        </form>
    </div>
</div>
