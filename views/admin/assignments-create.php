<?php
?>
<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="page-title mb-0">Tạo phân bổ nhân sự</div>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=assignments">← Danh sách</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card-like">
        <form method="post" action="<?= BASE_URL ?>?action=assignments-store">
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
                    <label class="form-label">Hướng dẫn viên</label>
                    <select class="form-select" name="HDV_ID" required>
                        <option value="">-- Chọn HDV --</option>
                        <?php foreach (($guides ?? []) as $g): ?>
                            <option value="<?= $g['HDV_ID'] ?>"><?= htmlspecialchars($g['HoTen'] ?? ('#'.$g['HDV_ID'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Ngày phân công</label>
                    <input type="date" class="form-control" name="assign_date">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Điểm tập trung</label>
                    <input type="text" class="form-control" name="meeting_point" placeholder="Ví dụ: 123 Lý Thường Kiệt">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Giờ xuất phát</label>
                    <input type="time" class="form-control" name="start_time">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Giờ kết thúc</label>
                    <input type="time" class="form-control" name="end_time">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Tài xế (tuỳ chọn)</label>
                    <select class="form-select" name="driver_id">
                        <option value="">-- Chọn tài xế --</option>
                        <?php foreach (($guides ?? []) as $g): ?>
                            <option value="<?= $g['HDV_ID'] ?>"><?= htmlspecialchars($g['HoTen'] ?? ('#'.$g['HDV_ID'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Nhân sự hậu cần (tuỳ chọn)</label>
                    <select class="form-select" name="support_id">
                        <option value="">-- Chọn nhân sự --</option>
                        <?php foreach (($guides ?? []) as $g): ?>
                            <option value="<?= $g['HDV_ID'] ?>"><?= htmlspecialchars($g['HoTen'] ?? ('#'.$g['HDV_ID'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Thông tin bổ sung..."></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Lưu phân bổ</button>
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=assignments">Hủy</a>
            </div>
        </form>
    </div>
</div>

