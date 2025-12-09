<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$guideId     = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
$trip_detail = isset($trip_detail) && is_array($trip_detail) ? $trip_detail : null;
$assignments = isset($assignments) && is_array($assignments) ? $assignments : [];

$__tab = isset($_GET['tab']) ? $_GET['tab'] : (!empty($trip_detail) ? 'detail' : 'assignments');
?>

<main class="main-content">
    <style>
    .main-content{background:transparent;padding:0}
    .hdv-container{max-width:1200px;margin:0 auto}
    .hdv-hero{background:#fff;border:2px solid #d9ccff;border-radius:18px;padding:14px;margin-bottom:14px;color:#111;box-shadow:0 8px 24px rgba(0,0,0,.08)}
    .hero-title{font-weight:700;margin:0 0 6px;font-size:20px;color:#1f2937}
    .hero-sub{opacity:.8;font-size:12px;color:#64748b}
    .mini-card{background:#fff;color:#111;border-radius:14px;padding:12px;box-shadow:0 6px 20px rgba(0,0,0,.08);display:flex;gap:10px;align-items:flex-start;border:2px solid #d9ccff;min-height:92px}
    .icon-wrap{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#6f6ee9;background:#eef1ff;flex:0 0 32px}
    .mini-card .label{font-size:11px;color:#64748b}
    .mini-card .value{font-size:20px;font-weight:700;margin-top:2px}
    .status-pill{display:inline-block;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600}
    .status-ok{background:#e9f7ef;color:#107d3e}
    .status-run{background:#eef6ff;color:#1e66f5}
    .status-wait{background:#f6f7fb;color:#475569}
    .schedule-card{background:#fff;border:2px solid #d9ccff;border-radius:14px;padding:10px;box-shadow:0 6px 20px rgba(0,0,0,.06)}
    .schedule-card .table thead{border-bottom:1px solid #eef1ff}
    .schedule-card .table tbody tr td{border-bottom:1px solid #f5f7ff}
    .date-pill{background:#fff;color:#6f6ee9;border:1px solid #dfe3ff;border-radius:999px;padding:4px 10px;font-size:12px;display:inline-flex;align-items:center;gap:6px}
    .summary{display:flex;gap:10px;margin-top:10px}
    .summary .box{flex:1;border-radius:12px;padding:10px}
    .summary-blue{background:#eaf2ff;color:#1e66f5}
    .summary-green{background:#e9f7ef;color:#107d3e}
    .summary-purple{background:#f1ecff;color:#6f6ee9}
    .summary .value{font-size:18px;font-weight:700}
    .services-card{background:#fff;border:2px solid #d9ccff;border-radius:14px;padding:10px;box-shadow:0 6px 20px rgba(0,0,0,.06)}
    .service-item{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed #eef1ff}
    .service-item:last-child{border-bottom:none}
    .customer-card{background:#eef6ff;border:2px solid #d9ccff;border-radius:12px}
    .customer-card .name{font-weight:600;color:#1f2937}
    .customer-card .meta{color:#64748b}
    .stat-card{background:#fff;border:2px solid #e8e8ff;border-radius:12px;padding:10px; min-width: 100px;}
    .stat-card .title{font-size:12px;color:#64748b}
    .stat-card .value{font-size:18px;font-weight:700;color:#111}
    .stat-orange{background:#fff0f0;border-color:#ffd9d9}
    .stat-green{background:#effaf3;border-color:#d7f2df}
    .stat-pink{background:#fff0f7;border-color:#ffd9ea}
    .stat-violet{background:#f3f0ff;border-color:#e3dcff}
    .notes-card{background:#fff8db;border:2px solid #fde68a;border-radius:12px;padding:10px;color:#7c6f46}
    .topbar{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:8px;margin-bottom:12px}
    .grid{display:grid}
    .grid-3{grid-template-columns:repeat(3,1fr)}
    .grid-2{grid-template-columns:repeat(2,1fr)}
    .grid-2-1{grid-template-columns:2fr 1fr}
    .gap-10{gap:10px}
    .flex-between{display:flex;justify-content:space-between;align-items:center}
    .table-basic{width:100%;border-collapse:collapse}
    .table-basic thead tr{border-bottom:1px solid #eef1ff; background: #f8fafc;}
    .table-basic td,.table-basic th{padding:12px 10px; text-align: left;}
    .info-alert{background:#eaf2ff;color:#1e66f5;border:1px solid #dfe7ff;border-radius:8px;padding:10px}
    .simple-card{background:#fff;border:2px solid #d9ccff;border-radius:12px;padding:10px}
    .text-muted{color:#64748b}
    .small{font-size:12px}
    .fw-semibold{font-weight:600}
    .page-title{font-weight:700;color:#1f2937}
    .mb-0{margin-bottom:0}
    .mt-2{margin-top:8px}
    .mt-3{margin-top:12px}
    .mb-2{margin-bottom:8px}
    .mb-3{margin-bottom:12px}

    .timeline-container-new { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .timeline-header { padding: 20px; border-bottom: 1px solid #f3f4f6; background: #fff; display: flex; justify-content: space-between; align-items: center; }
    .timeline-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .timeline-table th { text-align: left; padding: 16px 24px; font-size: 13px; font-weight: 700; color: #111827; text-transform: uppercase; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .timeline-table td { padding: 20px 24px; vertical-align: top; border-bottom: 1px solid #f3f4f6; }
    .timeline-table tr:last-child td { border-bottom: none; }
    
    .col-time .time-val { font-size: 16px; font-weight: 800; color: #111827; }
    .col-time .time-sub { font-size: 13px; font-weight: 500; color: #6b7280; margin-top: 4px; }
    .col-act .act-title { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px; }
    .col-act .act-loc { font-size: 13px; font-weight: 600; color: #4b5563; display: flex; align-items: center; gap: 4px; }
    .status-badge-tl { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em; }
    .st-done { background: #dcfce7; color: #166534; }
    .st-doing { background: #dbeafe; color: #1e40af; }
    .st-pending { background: #f3f4f6; color: #6b7280; }
    .btn-tl { border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 8px; width: 100%; text-align: center; }
    .btn-tl-start { background: #6366f1; color: #fff; }
    .btn-tl-start:hover { background: #4f46e5; }
    .btn-tl-finish { background: #22c55e; color: #fff; }
    .btn-tl-finish:hover { background: #16a34a; }
    .btn-tl-undo { background: #fff; border: 1px solid #d1d5db; color: #4b5563; }
    .btn-tl-undo:hover { background: #f9fafb; }
    .status-select {
        transition: all 0.2s;
    }
    .status-select option {
        padding: 8px;
    }
    .btn-edit-itinerary:hover {
        background: #e5e7eb !important;
        border-color: #9ca3af !important;
    }
    .tl-footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
    .tl-stat-box { background: #eff6ff; padding: 10px 20px; border-radius: 8px; color: #1e40af; font-weight: 600; font-size: 14px; }
    .tl-stat-success { background: #f0fdf4; color: #15803d; }

    @media(max-width:768px){.grid-3{grid-template-columns:1fr}}
    </style>
    
    <div class="topbar">
        <div>
            <h2 class="page-title mb-0"><?= htmlspecialchars(removeVNPrefix($trip_detail['tour_name'] ?? 'Thông tin tour đã được phân bổ')) ?></h2>
        </div>
        <div>
            <a class="btn btn-light rounded-pill px-4" href="<?= BASE_URL ?>?action=logout">Đăng xuất</a>
        </div>
    </div>

    <?php if (!empty($assignments) && count($assignments) > 1): ?>
        <!-- Danh sách tour nếu có nhiều tour -->
        <div class="hdv-container mb-3">
            <div class="simple-card">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-semibold">Tour được phân công:</span>
                    <?php foreach ($assignments as $idx => $ass): ?>
                        <?php 
                        $assBookingId = isset($ass['booking_id']) ? (int)$ass['booking_id'] : 0;
                        $currentBookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
                        $isActive = ($assBookingId > 0 && $assBookingId === $currentBookingId) || ($idx === 0 && $currentBookingId === 0);
                        ?>
                        <a href="<?= BASE_URL ?>?action=partner&tab=<?= $__tab ?>&booking_id=<?= $assBookingId ?>" 
                           class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <?= htmlspecialchars(removeVNPrefix($ass['tour_name'] ?? 'Tour #' . $assBookingId)) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div class="hdv-container mb-3">
        <div class="d-flex gap-2 border-bottom">
            <a href="<?= BASE_URL ?>?action=partner&tab=assignments<?= isset($_GET['booking_id']) ? '&booking_id=' . (int)$_GET['booking_id'] : '' ?>" 
               class="px-4 py-2 text-decoration-none <?= $__tab === 'assignments' ? 'border-bottom border-primary border-2 text-primary fw-bold' : 'text-muted' ?>">
                📋 Danh sách tour
            </a>
            <?php if (!empty($trip_detail)): ?>
                <a href="<?= BASE_URL ?>?action=partner&tab=detail&booking_id=<?= $trip_detail['booking_code'] ?? ($_GET['booking_id'] ?? '') ?>" 
                   class="px-4 py-2 text-decoration-none <?= $__tab === 'detail' ? 'border-bottom border-primary border-2 text-primary fw-bold' : 'text-muted' ?>">
                    📊 Chi tiết tour
                </a>
                <a href="<?= BASE_URL ?>?action=partner&tab=itinerary&booking_id=<?= $trip_detail['booking_code'] ?? ($_GET['booking_id'] ?? '') ?>" 
                   class="px-4 py-2 text-decoration-none <?= $__tab === 'itinerary' ? 'border-bottom border-primary border-2 text-primary fw-bold' : 'text-muted' ?>">
                    🗓️ Lịch trình
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($__tab === 'detail'): ?>
        <?php if (empty($trip_detail)): ?>
            <div class="simple-card">
                <h3 class="section-title mb-2">Thông tin tour</h3>
                <div class="info-alert">Bạn chưa chọn tour nào hoặc chưa được phân công tour.</div>
                <div class="text-muted small mt-2">Vui lòng chọn một tour từ danh sách "Tour được phân công".</div>
            </div>
        <?php else: ?>
        <div class="hdv-container">
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 12px;">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 12px;">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="hdv-hero">
                <h2 class="hero-title">Bảng Điều Khiển Hướng Dẫn Viên</h2>
                <div class="hero-sub">Quản lý thông tin tour và lịch trình hằng ngày</div>
                <?php
                $vehicleInfo = $trip_detail['vehicle_info'] ?? null;
                $transportService = $trip_detail['transport_service'] ?? null;
                $supplierName = '—';
                $vehicleQuantity = 0;
                $vehicleStatus = '';
                if ($vehicleInfo) {
                    $supplierName = $vehicleInfo['supplier_name'] ?? ($vehicleInfo['name'] ?? '—');
                    $vehicleQuantity = (int)($vehicleInfo['quantity'] ?? 0);
                    $vehicleStatus = $vehicleInfo['status'] ?? '';
                } elseif ($transportService) {
                    $supplierName = $transportService['name'] ?? '—';
                    $vehicleQuantity = (int)($transportService['qty'] ?? 0);
                    $vehicleStatus = $transportService['status'] ?? '';
                }
                
                $driverName = '—';
                $driverPhone = '—';
                if ($vehicleInfo && !empty($vehicleInfo['driver_name'])) {
                    $driverName = htmlspecialchars($vehicleInfo['driver_name']);
                    $driverPhone = !empty($vehicleInfo['driver_phone']) ? htmlspecialchars($vehicleInfo['driver_phone']) : '—';
                } elseif (!empty($trip_detail['assigned_driver'])) {
                    $driverName = htmlspecialchars($trip_detail['assigned_driver']);
                }
                
                $licensePlate = $vehicleInfo['license_plate'] ?? '—';
                $vehicleCapacity = $vehicleInfo['capacity'] ?? null;
                
                $cc = isset($trip_detail['customer_list']) ? count($trip_detail['customer_list']) : 0;
                
                // Tính toán trạng thái check-in dựa trên danh sách khách
                $checkinStatus = 'not_arrived'; // Mặc định: Chưa đến
                $checkinCount = 0;
                $checkedInCount = 0;
                
                if (!empty($trip_detail['customer_list'])) {
                    foreach ($trip_detail['customer_list'] as $customer) {
                        $status = $customer['checkin_status'] ?? 'not_arrived';
                        if ($status === 'checked_in') {
                            $checkedInCount++;
                        } elseif ($status === 'arrived') {
                            $checkinCount++;
                        }
                    }
                    
                    // Xác định trạng thái tổng thể
                    if ($checkedInCount === $cc && $cc > 0) {
                        $checkinStatus = 'all_checked_in';
                    } elseif ($checkedInCount > 0 || $checkinCount > 0) {
                        $checkinStatus = 'in_progress';
                    }
                }
                
                // Hiển thị badge check-in
                $checkinLabels = [
                    'all_checked_in' => 'Đã check-in',
                    'in_progress' => 'Đang check-in',
                    'not_arrived' => 'Chưa check-in'
                ];
                $checkinText = $checkinLabels[$checkinStatus] ?? 'Chưa check-in';
                $checkinClass = $checkinStatus === 'all_checked_in' ? 'status-ok' : ($checkinStatus === 'in_progress' ? 'status-run' : 'status-wait');
                ?>
                <div class="grid grid-3 gap-10 mt-2">
                    <div class="mini-card">
                        <div class="icon-wrap">👥</div>
                        <div>
                            <div class="label">Số lượng khách</div>
                            <div class="value"><?= $cc ?> người</div>
                            <span class="status-pill <?= $checkinClass ?>"><?= $checkinText ?></span>
                            <?php if ($cc > 0): ?>
                                <div class="text-muted small mt-1"><?= $checkedInCount ?>/<?= $cc ?> đã check-in</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="icon-wrap">↔</div>
                        <div>
                            <div class="label">Nhà xe</div>
                            <div class="value"><?= htmlspecialchars($supplierName) ?></div>
                            <?php if ($vehicleQuantity > 0 || !empty($vehicleStatus)): ?>
                                <div class="text-muted small">
                                    <?php if ($vehicleQuantity > 0): ?>
                                        SL: <?= $vehicleQuantity ?>
                                    <?php endif; ?>
                                    <?php if (!empty($vehicleStatus)): ?>
                                        <?= $vehicleQuantity > 0 ? ' | ' : '' ?>TT: <span class="status-pill status-run"><?= htmlspecialchars($vehicleStatus) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($licensePlate !== '—'): ?>
                                <div class="text-muted small">Biển số: <?= htmlspecialchars($licensePlate) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="icon-wrap">👤</div>
                        <div>
                            <div class="label">Tài xế</div>
                            <div class="value"><?= $driverName ?></div>
                            <?php if ($driverPhone !== '—'): ?>
                                <div class="text-muted small">SDT: <?= $driverPhone ?></div>
                            <?php endif; ?>
                            <?php if ($vehicleCapacity): ?>
                                <div class="text-muted small">Sức chứa: <?= $vehicleCapacity ?> chỗ</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="schedule-card mt-3">
                <div class="flex-between mb-3">
                    <h2 class="section-title mb-0">
                        <span class="me-2">📋</span>Danh sách đoàn khách 
                        <span class="status-pill status-run ms-2"><?= count($trip_detail['customer_list'] ?? []) ?> khách</span>
                    </h2>
                </div>
                <div class="table-responsive">
                    <table class="table-basic align-middle">
                        <thead>
                            <tr>
                                <th width="50">STT</th>
                                <th>Họ và tên</th>
                                <th>Thông tin</th>
                                <th>Check-in</th>
                                <th>Ghi chú đặc biệt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($trip_detail['customer_list'])): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có khách hàng.</td></tr>
                            <?php else: ?>
                                <?php foreach ($trip_detail['customer_list'] as $index => $cus): ?>
                                    <tr>
                                        <td class="text-center fw-bold text-muted"><?= $index + 1 ?></td>
                                        <td>
                                            <div class="fw-bold text-dark" style="font-size:15px"><?= htmlspecialchars($cus['full_name']) ?></div>
                                            <div class="small text-muted mt-1">
                                                <?= $cus['gender'] === 'Male' ? 'Nam' : ($cus['gender'] === 'Female' ? 'Nữ' : htmlspecialchars($cus['gender'] ?? '')) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($cus['contact_phone'])): ?>
                                                <div style="margin-bottom: 6px;">
                                                    <span style="color: #dc2626;">📞</span> 
                                                    <span class="fw-semibold text-primary">
                                                        <?= htmlspecialchars($cus['contact_phone']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($cus['email'])): ?>
                                                <div style="margin-bottom: 6px;">
                                                    <span style="color: #ec4899;">✉️</span> 
                                                    <a href="mailto:<?= htmlspecialchars($cus['email']) ?>" class="text-decoration-none text-dark">
                                                        <?= htmlspecialchars($cus['email']) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($cus['id_number'])): ?>
                                                <div style="margin-bottom: 6px;">
                                                    <span style="color: #2563eb;">🪪</span> 
                                                    <span class="text-dark">
                                                        <?= htmlspecialchars($cus['id_type'] ?? '') ?><?= !empty($cus['id_type']) ? ' · ' : '' ?><?= htmlspecialchars($cus['id_number']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($cus['date_of_birth'])): ?>
                                                <div style="margin-bottom: 6px;" class="small text-muted">
                                                    <span>📅</span> Ngày sinh: <?= htmlspecialchars($cus['date_of_birth']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($cus['id']) && $cus['id'] > 0): ?>
                                                <form method="POST" action="<?= BASE_URL ?>?action=partner-guest-checkin">
                                                    <input type="hidden" name="guest_id" value="<?= $cus['id'] ?>">
                                                    <input type="hidden" name="booking_id" value="<?= $trip_detail['booking_code'] ?? 0 ?>">
                                                    <select name="checkin_status" class="form-select form-select-sm status-select" 
                                                            onchange="this.form.submit()"
                                                            style="width: 140px; font-weight: 600; 
                                                            <?= ($cus['checkin_status'] ?? '') === 'checked_in' ? 'color: #166534; background-color: #dcfce7; border-color: #bbf7d0;' : 
                                                               (($cus['checkin_status'] ?? '') === 'arrived' ? 'color: #1e40af; background-color: #dbeafe; border-color: #bfdbfe;' : 'color: #4b5563;') ?>">
                                                        <option value="not_arrived" <?= ($cus['checkin_status'] ?? '') === 'not_arrived' ? 'selected' : '' ?>>Chưa đến</option>
                                                        <option value="arrived" <?= ($cus['checkin_status'] ?? '') === 'arrived' ? 'selected' : '' ?>>Đã đến</option>
                                                        <option value="checked_in" <?= ($cus['checkin_status'] ?? '') === 'checked_in' ? 'selected' : '' ?>>Check-in</option>
                                                    </select>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Chưa đồng bộ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php $note = $cus['special_requests'] ?? ''; $hasNote = !empty($note) && $note !== 'ko có' && trim($note) !== ''; ?>
                                                <?php if ($hasNote): ?>
                                                    <div class="status-pill" style="background:#fff8db; color:#b45309; border:1px solid #fde68a;">⚠️ <?= htmlspecialchars($note) ?></div>
                                                <?php else: ?> 
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                                <?php if (isset($cus['id']) && $cus['id'] > 0): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 12px;" data-bs-toggle="modal" data-bs-target="#modalSpecialRequests<?= $cus['id'] ?>" title="Cập nhật yêu cầu đặc biệt">
                                                        ✏️
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (isset($cus['id']) && $cus['id'] > 0): ?>
                                            <!-- Modal cập nhật yêu cầu đặc biệt -->
                                            <div class="modal fade" id="modalSpecialRequests<?= $cus['id'] ?>" tabindex="-1" aria-labelledby="modalSpecialRequestsLabel<?= $cus['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="modalSpecialRequestsLabel<?= $cus['id'] ?>">
                                                                Cập nhật yêu cầu đặc biệt
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="post" action="<?= BASE_URL ?>?action=partner-guest-update-special-requests">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="guest_id" value="<?= $cus['id'] ?>">
                                                                <input type="hidden" name="booking_id" value="<?= $trip_detail['booking_code'] ?? 0 ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Khách:</strong> <?= htmlspecialchars($cus['full_name']) ?></label>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="special_requests_<?= $cus['id'] ?>" class="form-label">
                                                                        Yêu cầu đặc biệt <small class="text-muted">(ăn chay, bệnh lý, dị ứng, v.v.)</small>
                                                                    </label>
                                                                    <textarea 
                                                                        class="form-control" 
                                                                        id="special_requests_<?= $cus['id'] ?>" 
                                                                        name="special_requests" 
                                                                        rows="4" 
                                                                        placeholder="Ví dụ: Ăn chay, Dị ứng hải sản, Bệnh tiểu đường, Không ăn thịt bò, v.v."
                                                                    ><?= htmlspecialchars($note !== 'ko có' ? $note : '') ?></textarea>
                                                                    <div class="form-text">
                                                                        Ghi nhận, cập nhật và nhắc lại các nhu cầu riêng biệt của khách để chuẩn bị phục vụ phù hợp suốt tour.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Lưu cập nhật</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>


        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($__tab === 'assignments'): ?>
        <?php if (!empty($assignments)): ?>
        <div class="hdv-container">
            <div class="schedule-card mt-3">
                <h2 class="section-title"><span class="me-2">🗂️</span>Tour được phân công (<?= count($assignments) ?> tour)</h2>
                <div class="table-responsive">
                    <table class="table-basic">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Tên Tour</th>
                                <th>Khách hàng</th>
                                <th>Điểm hẹn</th>
                                <th>Giờ bắt đầu</th>
                                <th>Giờ kết thúc</th>
                                <th>Ngày phân công</th>
                                <th>Ngày kết thúc</th>
                                <th>Ghi chú</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $row): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= isset($row['booking_id']) ? '#' . htmlspecialchars((string)$row['booking_id']) : '—' ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars(removeVNPrefix($row['tour_name'] ?? 'Chưa có tên tour')) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['customer_name'])): ?>
                                            <div><?= htmlspecialchars($row['customer_name']) ?></div>
                                            <?php if (!empty($row['total_people'])): ?>
                                                <div class="small text-muted"><?= $row['total_people'] ?> người</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['meeting_point'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['start_time'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['end_time'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['assign_date'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['end_date'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['notes'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($row['booking_id'])): ?>
                                            <a href="<?= BASE_URL ?>?action=partner&tab=detail&booking_id=<?= $row['booking_id'] ?>" class="btn btn-sm btn-primary">Xem chi tiết</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="simple-card">
            <div class="info-alert">
                <strong>Chưa có chuyến đi nào được phân bổ cho bạn.</strong>
                <div class="mt-2 small text-muted">
                    <?php if ($guideId > 0): ?>
                        Guide ID của bạn: <?= $guideId ?><br>
                    <?php endif; ?>
                    Vui lòng liên hệ admin để được phân bổ tour.
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($__tab === 'itinerary'): ?>
        <?php 
            $itList = isset($trip_detail['itinerary']) && is_array($trip_detail['itinerary']) ? $trip_detail['itinerary'] : [];
            $assignDate = $trip_detail['assign_date'] ?? null;
            $endDate = $trip_detail['end_date'] ?? null;
            $groupedByDay = [];
            $seenItemIds = [];

            foreach ($itList as $item) {
                $itemId = isset($item['id']) ? (int)$item['id'] : 0;
                if ($itemId > 0 && isset($seenItemIds[$itemId])) {
                    continue;
                }
                if ($itemId > 0) {
                    $seenItemIds[$itemId] = true;
                }
                
                $rawDayNum = $item['day_number'] ?? null;
                $dayNum = 1;
                if ($rawDayNum !== null && $rawDayNum !== '') {
                    if (is_numeric($rawDayNum)) {
                        $dayNum = (int)floatval($rawDayNum);
                    } elseif (is_string($rawDayNum)) {
                        $trimmed = trim($rawDayNum);
                        if (is_numeric($trimmed)) {
                            $dayNum = (int)floatval($trimmed);
                        }
                    } else {
                        $dayNum = (int)$rawDayNum;
                    }
                }
                $dayNum = max(1, $dayNum);
                if (!isset($groupedByDay[$dayNum])) {
                    $groupedByDay[$dayNum] = [];
                }
                $groupedByDay[$dayNum][] = $item;
            }
            ksort($groupedByDay);
            
            $actualDates = [];
            $allDays = array_keys($groupedByDay);
            $maxDayFromData = !empty($allDays) ? max($allDays) : 1;
            
            if ($assignDate) {
                $date1 = new DateTime($assignDate);
                $maxDayFromEndDate = 1;
                if ($endDate && $endDate != $assignDate) {
                    $date2 = new DateTime($endDate);
                    $diff = $date1->diff($date2);
                    $maxDayFromEndDate = $diff->days + 1;
                }
                $maxDay = max($maxDayFromData, $maxDayFromEndDate);
                for ($i = 1; $i <= $maxDay; $i++) {
                    $date = clone $date1;
                    $date->modify('+' . ($i - 1) . ' days');
                    $actualDates[$i] = $date->format('Y-m-d');
                }
            } else {
                $maxDay = $maxDayFromData;
                for ($i = 1; $i <= $maxDay; $i++) {
                    $actualDates[$i] = date('Y-m-d', strtotime('+' . ($i - 1) . ' days'));
                }
            }
            
            if ($assignDate && !empty($allDays)) {
                $date1 = new DateTime($assignDate);
                foreach ($allDays as $dayNum) {
                    $dayKey = (int)$dayNum;
                    if (!isset($actualDates[$dayKey])) {
                        $date = clone $date1;
                        $date->modify('+' . ($dayKey - 1) . ' days');
                        $actualDates[$dayKey] = $date->format('Y-m-d');
                    }
                }
                if (!empty($allDays)) {
                    $maxDayFromData = max($allDays);
                    if ($maxDayFromData > $maxDay) {
                        $maxDay = $maxDayFromData;
                        for ($i = $maxDay + 1; $i <= $maxDayFromData; $i++) {
                            if (!isset($actualDates[$i])) {
                                $date = clone $date1;
                                $date->modify('+' . ($i - 1) . ' days');
                                $actualDates[$i] = $date->format('Y-m-d');
                            }
                        }
                    }
                }
                ksort($actualDates);
            }
            
            $selectedDayNum = isset($_GET['day']) ? (int)$_GET['day'] : 0;
            if ($selectedDayNum <= 0) {
                $selectedDayNum = !empty($groupedByDay) ? min(array_keys($groupedByDay)) : 1;
            }
            if (!isset($actualDates[$selectedDayNum]) && !empty($actualDates)) {
                $selectedDayNum = min(array_keys($actualDates));
            }
            
        ?>
        <?php if (empty($trip_detail)): ?>
             <div class="hdv-container"><div class="schedule-card text-center py-4">Bạn cần chọn tour ở tab "Tour được phân công" trước.</div></div>
        <?php else: ?>
        <div class="hdv-container mt-3">
            
            <div class="timeline-container-new">
                <div class="timeline-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size:18px; font-weight:700; margin:0">🗓️Lịch trình</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <?php if (!empty($actualDates) || !empty($groupedByDay)): ?>
                            <label style="font-weight: 600; color: #1e40af; margin-right: 8px;">Chọn ngày:</label>
                            <form method="get" action="<?= BASE_URL ?>" style="display: inline;">
                                <input type="hidden" name="action" value="partner">
                                <input type="hidden" name="tab" value="itinerary">
                                <input type="hidden" name="booking_id" value="<?= $trip_detail['booking_code'] ?? 0 ?>">
                                <select name="day" onchange="this.form.submit()" style="padding: 6px 12px; border: 2px solid #3b82f6; border-radius: 6px; font-size: 14px; font-weight: 600; color: #1e40af; background: #fff; cursor: pointer;">
                                <?php foreach ($actualDates as $dayNum => $dateStr): 
                                    $displayText = 'Ngày ' . $dayNum;
                                    if ($dateStr) {
                                        $dateObj = new DateTime($dateStr);
                                        $displayText .= ' (' . $dateObj->format('d/m/Y') . ')';
                                    }
                                    $hasData = isset($groupedByDay[$dayNum]);
                                ?>
                                    <option value="<?= $dayNum ?>" <?= $dayNum == $selectedDayNum ? 'selected' : '' ?>>
                                        <?= $displayText ?> <?= !$hasData ? '(Trống)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            </form>
                        <?php endif; ?>
                        <div class="date-pill"><span>Hôm nay: <?= date('d/m/Y') ?></span></div>
                    </div>
                </div>

                <?php 
                    $total = 0; 
                    $done = 0;
                    $dayKey = (int)$selectedDayNum;
                    $dayItems = [];
                    if (isset($groupedByDay[$dayKey]) && is_array($groupedByDay[$dayKey])) {
                        $dayItems = $groupedByDay[$dayKey];
                    } elseif (isset($groupedByDay[(string)$dayKey]) && is_array($groupedByDay[(string)$dayKey])) {
                        $dayItems = $groupedByDay[(string)$dayKey];
                    }
                    $dayTitle = 'Ngày ' . $selectedDayNum;
                    $actualDate = $actualDates[$selectedDayNum] ?? null;
                    if ($actualDate) {
                        $dateObj = new DateTime($actualDate);
                        $dayTitle .= ' - ' . $dateObj->format('d/m/Y');
                    }
                ?>
                    <div style="margin-bottom: 30px;">
                        <div style="padding: 12px 20px; background: #eff6ff; border-bottom: 2px solid #3b82f6; margin-bottom: 0;">
                            <span style="font-size: 16px; font-weight: 700; color: #1e40af;"><?= $dayTitle ?></span>
                        </div>
                        
                        <?php if (!empty($dayItems)): ?>
                        <div class="table-responsive">
                            <table class="timeline-table">
                                <thead>
                                    <tr>
                                        <th style="width: 150px;">Thời gian</th>
                                        <th>Hoạt động</th>
                                        <th style="width: 200px;">Địa điểm</th>
                                        <th style="width: 160px; text-align: right;">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                    $prevTime = null;
                                    foreach ($dayItems as $idx => $item): 
                                    $total++;
                                    $stt = $item['current_status'] ?? 'pending'; 
                                    if($stt==='completed') $done++;
                                    preg_match('/(\d{1,2}:\d{2})/', $item['time_start'] . ($item['title']??''), $m);
                                    $time = $m[1] ?? substr($item['time_start']??'',0,5);
                                    $duration = '';
                                    if (!empty($item['description'])) {
                                        if (preg_match('/(\d+)\s*(?:giờ|h|hour)(?:\s*(\d+)\s*(?:phút|ph|minute))?/i', $item['description'], $dm)) {
                                            $hours = (int)$dm[1];
                                            $minutes = isset($dm[2]) ? (int)$dm[2] : 0;
                                            if ($hours > 0 && $minutes > 0) {
                                                $duration = $hours . ' giờ ' . $minutes . ' phút';
                                            } elseif ($hours > 0) {
                                                $duration = $hours . ' giờ';
                                            } elseif ($minutes > 0) {
                                                $duration = $minutes . ' phút';
                                            }
                                        } elseif (preg_match('/(\d+)\s*(?:phút|ph|minute)/i', $item['description'], $dm)) {
                                            $duration = $dm[1] . ' phút';
                                        }
                                    }
                                    if (empty($duration) && isset($dayItems[$idx + 1])) {
                                        $nextItem = $dayItems[$idx + 1];
                                        $nextTime = null;
                                        if (!empty($nextItem['time_start'])) {
                                            preg_match('/(\d{1,2}):(\d{2})/', $nextItem['time_start'], $nm);
                                            if (!empty($nm)) {
                                                $nextTime = (int)$nm[1] * 60 + (int)$nm[2];
                                            }
                                        }
                                        if ($nextTime !== null) {
                                            preg_match('/(\d{1,2}):(\d{2})/', $time, $tm);
                                            if (!empty($tm)) {
                                                $currentTime = (int)$tm[1] * 60 + (int)$tm[2];
                                                $diff = $nextTime - $currentTime;
                                                if ($diff > 0) {
                                                    $hours = floor($diff / 60);
                                                    $minutes = $diff % 60;
                                                    if ($hours > 0 && $minutes > 0) {
                                                        $duration = $hours . ' giờ ' . $minutes . ' phút';
                                                    } elseif ($hours > 0) {
                                                        $duration = $hours . ' giờ';
                                                    } elseif ($minutes > 0) {
                                                        $duration = $minutes . ' phút';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                ?>
                                    <tr style="<?= $stt==='completed' ? 'background:#f9fafb; opacity:0.75' : '' ?>">
                                        <td class="col-time">
                                            <div class="time-val">
                                                <?= $time ?><?= !empty($duration) ? ' (' . $duration . ')' : '' ?>
                                            </div>
                                        </td>
                                        <td class="col-act">
                                            <div class="act-title"><?= htmlspecialchars($item['title']) ?></div>
                                            <div style="font-size:13px; color:#6b7280; line-height:1.4"><?= nl2br(htmlspecialchars($item['description']??'')) ?></div>
                                        </td>
                                        <td class="col-act">
                                            <div class="act-loc">📍 <?= htmlspecialchars($item['location']??'—') ?></div>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                                <!-- Dropdown trạng thái điểm danh -->
                                                <form method="post" action="<?= BASE_URL ?>?action=partner-update-activity" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?= $trip_detail['booking_code'] ?? 0 ?>">
                                                    <input type="hidden" name="itinerary_id" value="<?= $item['id'] ?>">
                                                    <select name="status" onchange="this.form.submit()" 
                                                            style="padding: 6px 12px; border: 2px solid; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; min-width: 150px; <?= $stt === 'pending' ? 'background: #f3f4f6; border-color: #9ca3af; color: #6b7280;' : ($stt === 'doing' ? 'background: #dbeafe; border-color: #3b82f6; color: #1e40af;' : 'background: #dcfce7; border-color: #22c55e; color: #166534;') ?>">
                                                    <option value="pending" <?= $stt === 'pending' ? 'selected' : '' ?> style="background: #f3f4f6; color: #6b7280;"> CHƯA BẮT ĐẦU</option>
                                                    <option value="doing" <?= $stt === 'doing' ? 'selected' : '' ?> style="background: #dbeafe; color: #1e40af;"> ĐANG THỰC HIỆN</option>
                                                    <option value="completed" <?= $stt === 'completed' ? 'selected' : '' ?> style="background: #dcfce7; color: #166534;">HOÀN THÀNH</option>
                                                </select>
                                                </form>
                                                <!-- Nút chỉnh sửa -->
                                                <a href="<?= BASE_URL ?>?action=partner-edit-itinerary&id=<?= $item['id'] ?>&booking_id=<?= $trip_detail['booking_code'] ?? 0 ?>&day=<?= $selectedDayNum ?>" 
                                                   class="btn-edit-itinerary" 
                                                   style="padding: 4px 10px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; font-size: 11px; cursor: pointer; color: #4b5563; text-decoration: none; display: inline-block;">
                                                    ✏️ Chỉnh sửa
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div style="padding: 40px; text-align: center; color: #6b7280;">
                                <p>Không có lịch trình cho ngày <?= $selectedDayNum ?></p>
                        <?php if (!empty($actualDates[$selectedDayNum])): ?>
                            <?php 
                            $dateObj = new DateTime($actualDates[$selectedDayNum]);
                            ?>
                            <p class="small text-muted">(<?= $dateObj->format('d/m/Y') ?>)</p>
                        <?php endif; ?>
                        <?php if (!empty($groupedByDay)): ?>
                            <?php if (!empty($groupedByDay)): ?>
                                <p class="small text-muted mt-2">Có lịch trình cho các ngày: <?= implode(', ', array_keys($groupedByDay)) ?></p>
                            <?php else: ?>
                                <p class="small text-muted mt-2">Chưa có lịch trình cho tour này.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <div class="tl-footer">
                    <div class="tl-stat-box">Tổng thời gian: 12 giờ</div>
                    <div class="tl-stat-box tl-stat-success">Hoàn thành: <?= $done ?>/<?= $total ?> hoạt động</div>
                    <div class="tl-stat-box" style="background:#f3e8ff; color:#6b21a8">Dự kiến kết thúc: 20:00</div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    <?php endif; ?>
</main>