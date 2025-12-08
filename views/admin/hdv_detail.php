<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$guideId     = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
$trip_detail = isset($trip_detail) && is_array($trip_detail) ? $trip_detail : null;
$assignments = isset($assignments) && is_array($assignments) ? $assignments : [];

// Xác định tab hiện tại - mặc định là 'detail' nếu có trip_detail, ngược lại là 'assignments'
$__tab = isset($_GET['tab']) ? $_GET['tab'] : (!empty($trip_detail) ? 'detail' : 'assignments');
?>

<main class="main-content">
    <style>
    /* --- CSS GỐC (GIỮ NGUYÊN TỪ FILE CŨ CỦA BẠN) --- */
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

    /* --- CSS MỚI: DÀNH RIÊNG CHO TAB LỊCH TRÌNH (Tab Itinerary) --- */
    .timeline-container-new { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .timeline-header { padding: 20px; border-bottom: 1px solid #f3f4f6; background: #fff; display: flex; justify-content: space-between; align-items: center; }
    .timeline-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .timeline-table th { text-align: left; padding: 16px 24px; font-size: 13px; font-weight: 700; color: #111827; text-transform: uppercase; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .timeline-table td { padding: 20px 24px; vertical-align: top; border-bottom: 1px solid #f3f4f6; }
    .timeline-table tr:last-child td { border-bottom: none; }
    
    /* Cột Thời gian */
    .col-time .time-val { font-size: 16px; font-weight: 800; color: #111827; }
    .col-time .time-sub { font-size: 13px; font-weight: 500; color: #6b7280; margin-top: 4px; }
    
    /* Cột Hoạt động */
    .col-act .act-title { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px; }
    .col-act .act-loc { font-size: 13px; font-weight: 600; color: #4b5563; display: flex; align-items: center; gap: 4px; }
    
    /* Cột Trạng thái */
    .status-badge-tl { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em; }
    .st-done { background: #dcfce7; color: #166534; }
    .st-doing { background: #dbeafe; color: #1e40af; }
    .st-pending { background: #f3f4f6; color: #6b7280; }
    
    /* Nút bấm */
    .btn-tl { border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 8px; width: 100%; text-align: center; }
    .btn-tl-start { background: #6366f1; color: #fff; }
    .btn-tl-start:hover { background: #4f46e5; }
    .btn-tl-finish { background: #22c55e; color: #fff; }
    .btn-tl-finish:hover { background: #16a34a; }
    .btn-tl-undo { background: #fff; border: 1px solid #d1d5db; color: #4b5563; }
    .btn-tl-undo:hover { background: #f9fafb; }
    
    /* Dropdown trạng thái điểm danh */
    .status-select {
        transition: all 0.2s;
    }
    .status-select option {
        padding: 8px;
    }
    
    /* Nút chỉnh sửa */
    .btn-edit-itinerary:hover {
        background: #e5e7eb !important;
        border-color: #9ca3af !important;
    }
    
    /* Footer thống kê */
    .tl-footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
    .tl-stat-box { background: #eff6ff; padding: 10px 20px; border-radius: 8px; color: #1e40af; font-weight: 600; font-size: 14px; }
    .tl-stat-success { background: #f0fdf4; color: #15803d; }

    @media(max-width:768px){.grid-3{grid-template-columns:1fr}}
    </style>
    
    <div class="topbar">
        <div>
            <h2 class="page-title mb-0"><?= htmlspecialchars($trip_detail['tour_name'] ?? 'Thông tin tour đã được phân bổ') ?></h2>
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
                            <?= htmlspecialchars($ass['tour_name'] ?? 'Tour #' . $assBookingId) ?>
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
            <div class="hdv-hero">
                <h2 class="hero-title">Bảng Điều Khiển Hướng Dẫn Viên</h2>
                <div class="hero-sub">Quản lý thông tin tour và lịch trình hằng ngày</div>
                <?php
                $servicesList = isset($trip_detail['services']) && is_array($trip_detail['services']) ? $trip_detail['services'] : [];
                $transport = null; $transportStatus = '';$transportQty=0;
                foreach ($servicesList as $s){ $t=strtolower((string)($s['type']??'')); if(strpos($t,'vận chuyển')!==false||strpos($t,'transport')!==false||strpos($t,'xe')!==false){ $transport=$s; $transportStatus=$s['status']??''; $transportQty=(int)($s['qty']??0); break; }}
                $cc = isset($trip_detail['customer_list']) ? count($trip_detail['customer_list']) : 0;
                $ad = !empty($trip_detail['assigned_driver']) ? htmlspecialchars($trip_detail['assigned_driver']) : '—';
                $driverPhone = 'SDT: 0912 345 678';
                ?>
                <div class="grid grid-3 gap-10 mt-2">
                    <div class="mini-card">
                        <div class="icon-wrap">👥</div>
                        <div>
                            <div class="label">Số lượng khách</div>
                            <div class="value"><?= $cc ?> người</div>
                            <span class="status-pill status-ok">Đã xác nhận</span>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="icon-wrap">↔</div>
                        <div>
                            <div class="label">Nhà xe</div>
                            <div class="value"><?= htmlspecialchars($transport['name']??'—') ?></div>
                            <div class="text-muted small">SL: <?= $transportQty ?> <?= $transportQty? ' | TT:':'' ?><span class="status-pill status-run"><?= htmlspecialchars($transportStatus) ?></span></div>
                        </div>
                    </div>
                    <div class="mini-card">
                        <div class="icon-wrap">👤</div>
                        <div>
                            <div class="label">Tài xế</div>
                            <div class="value"><?= $ad ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($driverPhone) ?></div>
                            <div class="text-muted small">⭐ 4.8/5 (120 đánh giá)</div>
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
                                            <?php $note = $cus['special_requests'] ?? ''; $hasNote = !empty($note) && $note !== 'ko có' && trim($note) !== ''; ?>
                                            <?php if ($hasNote): ?>
                                                <div class="status-pill" style="background:#fff8db; color:#b45309; border:1px solid #fde68a;">⚠️ <?= htmlspecialchars($note) ?></div>
                                            <?php else: ?> 
                                                <span class="text-muted">—</span>
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
        <?php 
        // Debug info
        $debugInfo = [
            'guideId' => $guideId,
            'assignments_count' => count($assignments),
            'assignments' => $assignments
        ];
        error_log('hdv_detail.php assignments tab - Debug: ' . json_encode($debugInfo));
        ?>
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
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($row['tour_name'] ?? 'Chưa có tên tour') ?></div>
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
            
            // Debug: Log số lượng itinerary items
            error_log('hdv_detail.php - itinerary count: ' . count($itList));
            if (!empty($itList)) {
                $dayNums = [];
                foreach ($itList as $item) {
                    $dayNum = (int)($item['day_number'] ?? 0);
                    if ($dayNum > 0) {
                        $dayNums[$dayNum] = ($dayNums[$dayNum] ?? 0) + 1;
                    }
                }
                error_log('hdv_detail.php - day_numbers in itinerary: ' . json_encode($dayNums));
            } 
            
            // Lấy thông tin ngày phân công
            $assignDate = $trip_detail['assign_date'] ?? null;
            $endDate = $trip_detail['end_date'] ?? null;
            
            // Tính toán các ngày thực tế
            $actualDates = [];
            if ($assignDate) {
                $actualDates[1] = $assignDate; // Ngày 1 = ngày phân công
                if ($endDate && $endDate != $assignDate) {
                    // Tính số ngày giữa assign_date và end_date
                    $date1 = new DateTime($assignDate);
                    $date2 = new DateTime($endDate);
                    $diff = $date1->diff($date2);
                    $totalDays = $diff->days + 1; // +1 để bao gồm cả ngày đầu và ngày cuối
                    
                    // Tạo mảng các ngày
                    for ($i = 1; $i <= $totalDays; $i++) {
                        $date = clone $date1;
                        $date->modify('+' . ($i - 1) . ' days');
                        $actualDates[$i] = $date->format('Y-m-d');
                    }
                } else {
                    // Nếu không có end_date hoặc end_date = assign_date, chỉ có 1 ngày
                    $actualDates[1] = $assignDate;
                }
            }
            
            // --- CODE MỚI: Nhóm lịch trình và loại bỏ trùng lặp ---
            $groupedByDay = [];
            $seenItemIds = []; // Track các ID đã thấy để loại bỏ duplicate

            foreach ($itList as $item) {
                // Kiểm tra duplicate dựa trên ID (ưu tiên)
                $itemId = isset($item['id']) ? (int)$item['id'] : 0;
                if ($itemId > 0 && isset($seenItemIds[$itemId])) {
                    // Item này đã tồn tại (theo ID), bỏ qua
                    error_log('hdv_detail.php - Duplicate item ID skipped in view: id=' . $itemId);
                    continue;
                }
                
                // Ép kiểu sang số nguyên để chắc chắn là 1, 2, 3...
                $dayNum = (int)($item['day_number'] ?? 1);
                
                // Nếu chưa có mảng cho ngày này thì tạo mới
                if (!isset($groupedByDay[$dayNum])) {
                    $groupedByDay[$dayNum] = [];
                }
                
                // Thêm hoạt động vào ngày tương ứng
                $groupedByDay[$dayNum][] = $item;
                
                // Đánh dấu ID đã thấy
                if ($itemId > 0) {
                    $seenItemIds[$itemId] = true;
                }
            }
            
            // Log để debug
            error_log('hdv_detail.php - groupedByDay count by day: ' . json_encode(array_map('count', $groupedByDay)));

            // Sắp xếp lại theo thứ tự ngày tăng dần (1, 2, 3...)
            ksort($groupedByDay);
            // --- KẾT THÚC CODE MỚI ---
            
            // Lấy ngày được chọn từ GET parameter
            $selectedDayNum = isset($_GET['day']) ? (int)$_GET['day'] : 0;
            
            // Nếu không có day trong GET, chọn ngày đầu tiên có dữ liệu (hoặc ngày 1 nếu không có dữ liệu)
            if ($selectedDayNum <= 0) {
                if (!empty($groupedByDay)) {
                    // Chọn ngày đầu tiên có dữ liệu
                    $selectedDayNum = min(array_keys($groupedByDay));
                } else {
                    // Nếu không có dữ liệu nào, mặc định là ngày 1
                    $selectedDayNum = 1;
                }
            }
            
            // Đảm bảo selectedDayNum hợp lệ (phải có trong actualDates)
            if (!isset($actualDates[$selectedDayNum])) {
                // Nếu ngày được chọn không hợp lệ, chọn ngày đầu tiên trong actualDates
                if (!empty($actualDates)) {
                    $selectedDayNum = min(array_keys($actualDates));
                } else {
                    $selectedDayNum = 1;
                }
            }
            
            // Lấy danh sách các day_number có sẵn (đã có lịch trình)
            $availableDayNumbers = array_keys($groupedByDay);
            
            // Debug log
            error_log('hdv_detail.php - selectedDayNum: ' . $selectedDayNum . ', availableDays: ' . implode(',', $availableDayNumbers) . ', groupedByDay keys: ' . implode(',', array_keys($groupedByDay)));
        ?>
        <?php if (empty($trip_detail)): ?>
             <div class="hdv-container"><div class="schedule-card text-center py-4">Bạn cần chọn tour ở tab "Tour được phân công" trước.</div></div>
        <?php else: ?>
        <div class="hdv-container mt-3">
            <div class="timeline-container-new">
                <div class="timeline-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size:18px; font-weight:700; margin:0">🗓️Lịch trình</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <?php if (!empty($actualDates)): ?>
                            <label style="font-weight: 600; color: #1e40af; margin-right: 8px;">Chọn ngày:</label>
                            <select id="daySelector" onchange="changeDay(this.value)" style="padding: 6px 12px; border: 2px solid #3b82f6; border-radius: 6px; font-size: 14px; font-weight: 600; color: #1e40af; background: #fff; cursor: pointer;">
                                <?php 
                                    // Lặp qua $actualDates (tất cả các ngày từ ngày bắt đầu đến kết thúc)
                                    foreach ($actualDates as $dayNum => $dateStr): 
                                        $displayText = 'Ngày ' . $dayNum;
                                        
                                        // Format ngày tháng hiển thị
                                        if ($dateStr) {
                                            $dateObj = new DateTime($dateStr);
                                            $displayText .= ' (' . $dateObj->format('d/m/Y') . ')';
                                        }
                                        
                                        // Kiểm tra xem ngày này đã có dữ liệu chưa để đánh dấu
                                        $hasData = isset($groupedByDay[$dayNum]);
                                    ?>
                                        <option value="<?= $dayNum ?>" <?= $dayNum == $selectedDayNum ? 'selected' : '' ?>>
                                            <?= $displayText ?> <?= !$hasData ? '(Trống)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <div class="date-pill"><span>Hôm nay: <?= date('d/m/Y') ?></span></div>
                    </div>
                </div>

                <?php 
                    // Khởi tạo biến trước khi sử dụng
                    $total = 0; 
                    $done = 0;
                    
                    // Hiển thị lịch trình của ngày được chọn (ngay cả khi trống)
                    $dayItems = isset($groupedByDay[$selectedDayNum]) ? $groupedByDay[$selectedDayNum] : [];
                    
                    // Tạo tiêu đề ngày với ngày thực tế (luôn hiển thị)
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
                                    
                                    // Lấy thời gian
                                    preg_match('/(\d{1,2}:\d{2})/', $item['time_start'] . ($item['title']??''), $m);
                                    $time = $m[1] ?? substr($item['time_start']??'',0,5);
                                    
                                    // Tính thời lượng
                                    $duration = '';
                                    
                                    // Thử tìm trong description (ví dụ: "30 phút", "2 giờ", "1h30")
                                    if (!empty($item['description'])) {
                                        // Tìm pattern như "30 phút", "2 giờ", "1h30", "1 giờ 30 phút"
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
                                    
                                    // Nếu không tìm thấy trong description, tính từ khoảng cách với hoạt động tiếp theo
                                    if (empty($duration) && isset($dayItems[$idx + 1])) {
                                        $nextItem = $dayItems[$idx + 1];
                                        $nextTime = null;
                                        if (!empty($nextItem['time_start'])) {
                                            preg_match('/(\d{1,2}):(\d{2})/', $nextItem['time_start'], $nm);
                                            if (!empty($nm)) {
                                                $nextTime = (int)$nm[1] * 60 + (int)$nm[2]; // phút trong ngày
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
                                                <select class="status-select" 
                                                        data-itinerary-id="<?= $item['id'] ?>" 
                                                        onchange="updateStatus(<?= $item['id'] ?>, this.value)"
                                                        style="padding: 6px 12px; border: 2px solid; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; min-width: 150px;">
                                                    <option value="pending" <?= $stt === 'pending' ? 'selected' : '' ?> style="background: #f3f4f6; color: #6b7280;"> CHƯA BẮT ĐẦU</option>
                                                    <option value="doing" <?= $stt === 'doing' ? 'selected' : '' ?> style="background: #dbeafe; color: #1e40af;"> ĐANG THỰC HIỆN</option>
                                                    <option value="completed" <?= $stt === 'completed' ? 'selected' : '' ?> style="background: #dcfce7; color: #166534;">HOÀN THÀNH</option>
                                                </select>
                                                <!-- Nút chỉnh sửa -->
                                                <button class="btn-edit-itinerary" 
                                                        onclick="editItinerary(<?= $item['id'] ?>)"
                                                        data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>"
                                                        data-time="<?= htmlspecialchars($item['time_start'] ?? '', ENT_QUOTES) ?>"
                                                        data-description="<?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES) ?>"
                                                        data-location="<?= htmlspecialchars($item['location'] ?? '', ENT_QUOTES) ?>"
                                                        data-day="<?= $item['day_number'] ?>"
                                                        style="padding: 4px 10px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; font-size: 11px; cursor: pointer; color: #4b5563;">
                                                    ✏️ Chỉnh sửa
                                                </button>
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
                            <p class="small text-muted mt-2">Có lịch trình cho các ngày: <?= implode(', ', array_keys($groupedByDay)) ?></p>
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

        <!-- Modal chỉnh sửa lịch trình -->
        <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
            <div style="background: white; padding: 24px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700;">✏️ Chỉnh sửa lịch trình</h3>
                    <button onclick="closeEditModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
                </div>
                <form id="editForm" onsubmit="saveItinerary(event)">
                    <input type="hidden" id="edit_id" name="id">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Thời gian:</label>
                        <input type="text" id="edit_time_start" name="time_start" required
                               style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
                               placeholder="VD: 08:00">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Tiêu đề:</label>
                        <input type="text" id="edit_title" name="title" required
                               style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
                               placeholder="VD: Đón khách tại khách sạn">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Mô tả:</label>
                        <textarea id="edit_description" name="description" rows="4"
                                  style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; resize: vertical;"
                                  placeholder="Mô tả chi tiết hoạt động..."></textarea>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Địa điểm:</label>
                        <input type="text" id="edit_location" name="location"
                               style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;"
                               placeholder="VD: Khách sạn ABC, Hà Nội">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="closeEditModal()" 
                                style="padding: 10px 20px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; font-weight: 600; color: #4b5563;">
                            Hủy
                        </button>
                        <button type="submit" 
                                style="padding: 10px 20px; background: #3b82f6; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white;">
                            💾 Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function changeDay(day) {
            const url = new URL(window.location.href);
            // Giữ nguyên tất cả các parameters hiện tại
            url.searchParams.set('day', day);
            // Đảm bảo có booking_id và tab
            const bookingId = <?= $trip_detail['booking_code'] ?? 0 ?>;
            if (bookingId > 0) {
                url.searchParams.set('booking_id', bookingId);
            }
            // Đảm bảo tab là 'itinerary'
            url.searchParams.set('tab', 'itinerary');
            // Đảm bảo action là 'partner'
            url.searchParams.set('action', 'partner');
            window.location.href = url.toString();
        }
        
        function updateStatus(itineraryId, status) {
            const bookingId = <?= $trip_detail['booking_code'] ?? 0 ?>;
            if(bookingId == 0) return;
            
            // Cập nhật style của select ngay lập tức
            const select = event.target;
            const colors = {
                'pending': {bg: '#f3f4f6', border: '#9ca3af', text: '#6b7280'},
                'doing': {bg: '#dbeafe', border: '#3b82f6', text: '#1e40af'},
                'completed': {bg: '#dcfce7', border: '#22c55e', text: '#166534'}
            };
            const color = colors[status] || colors.pending;
            select.style.background = color.bg;
            select.style.borderColor = color.border;
            select.style.color = color.text;
            
            fetch('<?= BASE_URL ?>?action=partner-update-activity', {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({booking_id: bookingId, itinerary_id: itineraryId, status: status})
            }).then(r=>r.json()).then(d=>{ 
                if(d.success) {
                    // Cập nhật số hoạt động hoàn thành
                    setTimeout(() => location.reload(), 300);
                } else {
                    alert('Lỗi khi cập nhật trạng thái');
                    location.reload();
                }
            });
        }
        
        function editItinerary(id, title, timeStart, description, location, dayNumber) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_time_start').value = timeStart;
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_location').value = location || '';
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function saveItinerary(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = {
                id: formData.get('id'),
                time_start: formData.get('time_start'),
                title: formData.get('title'),
                description: formData.get('description'),
                location: formData.get('location')
            };
            
            fetch('<?= BASE_URL ?>?action=partner-update-itinerary', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            }).then(r=>r.json()).then(d=>{
                if(d.success) {
                    alert('Đã cập nhật lịch trình thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (d.message || 'Không thể cập nhật'));
                }
            });
        }
        
        // Cập nhật style cho các select khi load trang
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('.status-select');
            selects.forEach(select => {
                const status = select.value;
                const colors = {
                    'pending': {bg: '#f3f4f6', border: '#9ca3af', text: '#6b7280'},
                    'doing': {bg: '#dbeafe', border: '#3b82f6', text: '#1e40af'},
                    'completed': {bg: '#dcfce7', border: '#22c55e', text: '#166534'}
                };
                const color = colors[status] || colors.pending;
                select.style.background = color.bg;
                select.style.borderColor = color.border;
                select.style.color = color.text;
            });
        });
        </script>
        <?php endif; ?>
    <?php endif; ?>
</main>