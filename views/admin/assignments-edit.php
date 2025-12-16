<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="page-title mb-0">Sửa phân bổ nhân sự</div>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=assignments">← Danh sách</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card-like">
        <form method="post" action="<?= BASE_URL ?>?action=assignments-update">
            <input type="hidden" name="id" value="<?= htmlspecialchars($assignment['id'] ?? '') ?>">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Tour</label>
                    <?php $curBid = $assignment['booking_id'] ?? ''; ?>
                    <select class="form-select" name="booking_id" required>
                        <?php foreach (($bookings ?? []) as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ((string)$curBid === (string)$b['id']) ? 'selected' : '' ?>>#<?= $b['id'] ?> - <?= htmlspecialchars(removeVNPrefix($b['tour_name'] ?? ($b['customer_name'] ?? ''))) ?> (<?= htmlspecialchars($b['start_date'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Hướng dẫn viên</label>
                    <?php $curGid = $assignment['HDV_ID'] ?? ''; ?>
                    <select class="form-select" name="HDV_ID" required>
                        <?php 
                            $busyGuideIds = isset($busyGuideIds) && is_array($busyGuideIds) ? $busyGuideIds : [];
                            foreach (($guides ?? []) as $g): 
                                $gid = (int)($g['HDV_ID'] ?? 0);
                                $label = ($g['HoTen'] ?? ('#'.$gid));
                                $suffix = in_array($gid, $busyGuideIds, true) ? ' (đang bận)' : '';
                        ?>
                            <option value="<?= $gid ?>" <?= ((string)$curGid === (string)$gid) ? 'selected' : '' ?>><?= htmlspecialchars($label . $suffix) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Ngày phân công</label>
                    <input type="date" class="form-control" name="assign_date" value="<?= htmlspecialchars($assignment['assign_date'] ?? '') ?>">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Ngày kết thúc</label>
                    <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($assignment['end_date'] ?? '') ?>">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label"></label>Điểm tập trung</label>
                    <input type="text" class="form-control" name="meeting_point" value="<?= htmlspecialchars($assignment['meeting_point'] ?? '') ?>">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Giờ xuất phát</label>
                    <input type="time" class="form-control" name="start_time" value="<?= htmlspecialchars(substr($assignment['start_time'] ?? '',0,5)) ?>">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Giờ kết thúc</label>
                    <input type="time" class="form-control" name="end_time" value="<?= htmlspecialchars(substr($assignment['end_time'] ?? '',0,5)) ?>">
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">Nhân sự hậu cần</label>
                    <?php $curSupport = $assignment['support_id'] ?? ''; ?>
                    <select class="form-select" name="support_id">
                        <option value="">-- Chọn nhân sự --</option>
                        <?php foreach (($guides ?? []) as $g): ?>
                            <option value="<?= $g['HDV_ID'] ?>" <?= ((string)$curSupport === (string)$g['HDV_ID']) ? 'selected' : '' ?>><?= htmlspecialchars($g['HoTen'] ?? ('#'.$g['HDV_ID'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="notes" rows="3"><?= htmlspecialchars($assignment['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Lưu</button>
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=assignments">Hủy</a>
            </div>
        </form>
    </div>
</main>

