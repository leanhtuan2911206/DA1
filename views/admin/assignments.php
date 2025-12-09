<?php
// Trang danh sách phân bổ nhân sự (đơn giản)
?>
<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="page-title mb-0">Quản lý khởi hành và phân bổ nhân sự</div>
        <a class="btn btn-success" href="<?= BASE_URL ?>?action=assignments-create">+ Tạo phân bổ</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form class="filter-bar d-flex mb-3" method="get" action="<?= BASE_URL ?>">
        <input type="hidden" name="action" value="assignments">
        <div class="filter-inputs d-flex">
            <select class="form-select" name="booking_id">
                <option value="">Tất cả Tour</option>
                <?php $curBid = $_GET['booking_id'] ?? ''; foreach (($bookings ?? []) as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ((string)$curBid === (string)$b['id'])?'selected':'' ?>>
                        #<?= $b['id'] ?> - <?= htmlspecialchars(removeVNPrefix($b['tour_name'] ?? ($b['customer_name'] ?? ''))) ?> (<?= htmlspecialchars($b['start_date'] ?? '') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="HDV_ID">
                <option value="">Tất cả HDV</option>
                <?php $curGid = $_GET['HDV_ID'] ?? ''; foreach (($guides ?? []) as $g): ?>
                    <option value="<?= $g['HDV_ID'] ?>" <?= ((string)$curGid === (string)$g['HDV_ID'])?'selected':'' ?>>
                        <?= htmlspecialchars($g['HoTen'] ?? ('#'.$g['HDV_ID'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>
        <div class="filter-actions d-flex">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=assignments">Đặt lại</a>
        </div>
    </form>

    <div class="card-like">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width:70px">ID</th>
                        <th>Tour</th>
                        <th>HDV</th>
                        <th>Điểm tập trung</th>
                        <th>Giờ xuất phát</th>
                        <th>Giờ kết thúc</th>
                        <th>Ngày phân công</th>
                        <th>Ngày kết thúc</th>
                        <th>Ghi chú</th>
                        <th style="width:160px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list ?? [])): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Chưa có phân bổ nào.</td></tr>
                    <?php endif; ?>
                    <?php foreach (($list ?? []) as $row): ?>
                        <?php $id = $row['id'] ?? null; $bid = $row['booking_id'] ?? null; $gid = $row['HDV_ID'] ?? null; ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars((string)$id) ?></span></td>
                            <td>
                                <?php $label = $bid ? ('#'.$bid) : '-';
                                foreach (($bookings ?? []) as $b) { if ((string)$b['id'] === (string)$bid) { $label = '#'.$b['id'].' - '.removeVNPrefix($b['tour_name'] ?? ($b['customer_name'] ?? '')); break; } }
                                ?>
                                <div class="fw-semibold"><?= htmlspecialchars($label) ?></div>
                            </td>
                            <td>
                                <?php $gName = $gid ? ('#'.$gid) : '-';
                                foreach (($guides ?? []) as $g) { if ((string)$g['HDV_ID'] === (string)$gid) { $gName = $g['HoTen'] ?? $gName; break; } }
                                ?>
                                <div class="fw-semibold"><?= htmlspecialchars($gName) ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['meeting_point'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['start_time'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['end_time'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['assign_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['end_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if ($id): ?>
                                        <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>?action=assignments-edit&id=<?= urlencode((string)$id) ?>">✏️</a>
                                        <a class="btn btn-outline-danger btn-sm" href="<?= BASE_URL ?>?action=assignments-delete&id=<?= urlencode((string)$id) ?>" onclick="return confirm('Xóa phân bổ này?')">🗑️</a>
                                    <?php endif; ?>
                                    <?php if ($bid): ?>
                                        <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>?action=services&booking_id=<?= urlencode((string)$bid) ?>">Dịch vụ</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
