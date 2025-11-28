<?php
?>
<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="page-title mb-0">Sửa dịch vụ</div>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=services">← Danh sách</a>
    </div>

    <div class="card-like">
        <?php if (!$service): ?>
            <div class="alert alert-warning">Không tìm thấy dịch vụ.</div>
        <?php else: ?>
            <form method="post" action="<?= BASE_URL ?>?action=services-update">
                <input type="hidden" name="id" value="<?= htmlspecialchars($service['id'] ?? '') ?>">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Booking</label>
                        <input type="number" class="form-control" name="booking_id" value="<?= htmlspecialchars($service['booking_id'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Loại dịch vụ</label>
                        <?php $curType = $service['service_type'] ?? ''; ?>
                        <select class="form-select" name="service_type">
                            <?php foreach ([["vehicle","Xe"],["hotel","Khách sạn"],["flight","Vé máy bay"],["restaurant","Nhà hàng"],["activity","Tham quan"]] as [$v,$t]): ?>
                                <option value="<?= $v ?>" <?= ($curType === $v ? 'selected' : '') ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Nhà cung cấp</label>
                        <input type="text" class="form-control" name="supplier_name" value="<?= htmlspecialchars($service['supplier_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Số lượng</label>
                        <input type="number" class="form-control" name="quantity" value="<?= htmlspecialchars($service['quantity'] ?? 1) ?>">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Trạng thái</label>
                        <?php $curStatus = $service['status'] ?? ''; ?>
                        <select class="form-select" name="status">
                            <?php foreach ([["pending","Chờ"],["confirmed","Xác nhận"],["completed","Hoàn tất"],["canceled","Hủy"],["active","Đang hoạt động"],["inactive","Tạm nghỉ"]] as [$v,$t]): ?>
                                <option value="<?= $v ?>" <?= ($curStatus === $v ? 'selected' : '') ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Thời gian bắt đầu</label>
                        <input type="text" class="form-control" name="start_time" value="<?= htmlspecialchars($service['start_time'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Thời gian kết thúc</label>
                        <input type="text" class="form-control" name="end_time" value="<?= htmlspecialchars($service['end_time'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($service['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Lưu</button>
                    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=services">Hủy</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
