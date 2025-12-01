<?php
?>
<div class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="page-title mb-0">Quản lý dịch vụ đoàn</div>
        <a class="btn btn-success" href="<?= BASE_URL ?>?action=services-create">+ Đặt dịch vụ</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form class="filter-bar d-flex mb-3" method="get" action="<?= BASE_URL ?>">
        <input type="hidden" name="action" value="services">
        <div class="filter-inputs d-flex">
            <select class="form-select" name="booking_id">
                <option value="">Tất cả Tour</option>
                <?php $curBid = $_GET['booking_id'] ?? ''; foreach (($bookings ?? []) as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ((string)$curBid === (string)$b['id'])?'selected':'' ?>>
                        #<?= $b['id'] ?> - <?= htmlspecialchars($b['tour_name'] ?? ($b['customer_name'] ?? '')) ?> (<?= htmlspecialchars($b['start_date'] ?? '') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" name="type">
                <option value="">Tất cả loại</option>
                <?php 
                    $selectedType = $_GET['type'] ?? ''; 
                    $typeLabels = [
                        'vehicle' => 'Xe',
                        'hotel' => 'Khách sạn',
                        'flight' => 'Vé máy bay',
                        'restaurant' => 'Nhà hàng',
                        'activity' => 'Tham quan'
                    ];
                    $sourceTypes = !empty($types) ? $types : array_keys($typeLabels);
                    foreach ($sourceTypes as $v):
                        $label = $typeLabels[$v] ?? ucfirst($v);
                ?>
                    <option value="<?= htmlspecialchars($v) ?>" <?= ($selectedType===$v?'selected':'') ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
           <select class="form-select" name="status">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ([["chờ","Chờ"],["xác nhận","Xác nhận"],["hoàn tất","Hoàn tất"],["hủy","Hủy"],["tạm ngưng","Tạm ngưng"],["hoạt động","Hoạt động"]] as [$v,$t]): ?>
                    <option value="<?= $v ?>" <?= (($_GET['status'] ?? '')===$v?'selected':'') ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions d-flex">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?action=services">Đặt lại</a>
        </div>
    </form>

    <div class="card-like">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tour</th>
                        <th>Loại</th>
                        <th>Nhà cung cấp</th>
                        <th>Số lượng</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Ghi chú</th>
                        <th style="width:160px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list ?? [])): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Chưa có dịch vụ nào.</td></tr>
                    <?php endif; ?>
                    <?php foreach (($list ?? []) as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                            <td>
                                <?php $bid = $row['booking_id'] ?? null; $label='#'.$bid; foreach (($bookings ?? []) as $b) { if ((string)$b['id'] === (string)$bid) { $label = '#'.$b['id'].' - '.($b['tour_name'] ?? ($b['customer_name'] ?? '')); break; } } ?>
                                <?= htmlspecialchars($label) ?>
                            </td>
                            <td>
                                <?php 
                                    $typeLabels = [
                                        'vehicle' => 'Xe',
                                        'hotel' => 'Khách sạn',
                                        'flight' => 'Vé máy bay',
                                        'restaurant' => 'Nhà hàng',
                                        'activity' => 'Tham quan'
                                    ];
                                    $stp = strtolower(trim($row['service_type'] ?? ''));
                                    $label = $typeLabels[$stp] ?? ($row['service_type'] ?? '');
                                ?>
                                <?= htmlspecialchars($label) ?>
                            </td>
                            <td><?= htmlspecialchars($row['supplier_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['quantity'] ?? '') ?></td>
                            <td>
                                <?php 
                                    $st = strtolower(trim($row['status'] ?? ''));
                                    $labelMap = [
                                        'chờ' => 'Chờ',
                                        'xác nhận' => 'Xác nhận',
                                        'hoàn tất' => 'Hoàn tất',
                                        'hủy' => 'Đã hủy',
                                        'tạm ngưng' => 'Tạm ngưng',
                                        'hoạt động' => 'Hoạt động',
                                    ];
                                    $classMap = [
                                        'chờ' => 'bg-warning text-dark',
                                        'xác nhận' => 'bg-info',
                                        'hoàn tất' => 'bg-success',
                                        'hủy' => 'bg-danger',
                                        'tạm ngưng' => 'bg-secondary',
                                        'hoạt động' => 'bg-success',
                                    ];
                                    $badgeClass = 'badge rounded-pill ' . ($classMap[$st] ?? 'bg-secondary');
                                    $displayText = $labelMap[$st] ?? ($row['status'] ?? '');
                                ?>
                                <span class="<?= $badgeClass ?>"><?= htmlspecialchars($displayText) ?></span>
                            </td>
                            <td><?= htmlspecialchars(($row['start_time'] ?? '') . ' - ' . ($row['end_time'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>?action=services-edit&id=<?= urlencode((string)($row['id'] ?? '')) ?>">✏️</a>
                                    <a class="btn btn-outline-danger btn-sm" href="<?= BASE_URL ?>?action=services-delete&id=<?= urlencode((string)($row['id'] ?? '')) ?>" onclick="return confirm('Xóa dịch vụ này?')">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
