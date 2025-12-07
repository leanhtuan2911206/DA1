<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$guideId     = isset($_SESSION['user']['guide_id']) ? (int)$_SESSION['user']['guide_id'] : 0;
$trip_detail = isset($trip_detail) && is_array($trip_detail) ? $trip_detail : null;
$assignments = isset($assignments) && is_array($assignments) ? $assignments : [];
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
    @media (max-width: 768px){.grid-3{grid-template-columns:1fr}.grid-2{grid-template-columns:1fr}.grid-2-1{grid-template-columns:1fr}}
    </style>
    
    <div class="topbar">
        <div>
            <h2 class="page-title mb-0"><?= htmlspecialchars($trip_detail['tour_name'] ?? 'Thông tin tour đã được phân bổ') ?></h2>
        </div>
        <div>
            <a class="btn btn-light rounded-pill px-4" href="<?= BASE_URL ?>?action=logout">Đăng xuất</a>
        </div>
    </div>

    <?php $__tab = isset($currentTab) ? $currentTab : (isset($_GET['tab']) && $_GET['tab']==='assignments' ? 'assignments' : 'detail'); ?>
    
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
                            <th>Liên hệ</th>
                            <th>Ghi chú đặc biệt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trip_detail['customer_list'])): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Chưa có khách hàng nào trong đoàn này.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trip_detail['customer_list'] as $index => $cus): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= $index + 1 ?></td>
                                    
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size:15px">
                                            <?= htmlspecialchars($cus['full_name']) ?>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?php 
                                                // Giới tính
                                                $gender = $cus['gender'] ?? '';
                                                if(in_array(strtolower($gender), ['male','nam'])) echo 'Nam';
                                                elseif(in_array(strtolower($gender), ['female','nữ'])) echo 'Nữ';
                                                else echo $gender;

                                                // Tuổi
                                                if (!empty($cus['date_of_birth'])) {
                                                    $dob = strtotime($cus['date_of_birth']);
                                                    echo ' · ' . date('d/m/Y', $dob);
                                                    echo ' (' . (date('Y') - date('Y', $dob)) . ' tuổi)';
                                                }
                                            ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if (!empty($cus['contact_phone'])): ?>
                                            <a href="tel:<?= htmlspecialchars($cus['contact_phone']) ?>" class="text-decoration-none fw-semibold text-primary">
                                                📞 <?= htmlspecialchars($cus['contact_phone']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php 
                                            $note = $cus['special_requests'] ?? '';
                                            $hasNote = !empty($note) && trim(strtolower($note)) !== 'ko có' && trim(strtolower($note)) !== 'không';
                                        ?>
                                        <?php if ($hasNote): ?>
                                            <div class="status-pill" style="background:#fff8db; color:#b45309; border:1px solid #fde68a; white-space: normal; text-align: left;">
                                                ⚠️ <?= htmlspecialchars($note) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($trip_detail['customer_list'])): ?>
            <div class="mt-3 pt-3 border-top d-flex gap-3 overflow-auto">
                <div class="stat-card stat-green px-3 py-2">
                    <div class="small text-muted">Tổng thành viên</div>
                    <div class="fw-bold fs-5 text-dark"><?= count($trip_detail['customer_list']) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($__tab === 'assignments'): ?>
        <?php if (!empty($assignments)): ?>
        <div class="schedule-card mt-3">
            <h2 class="section-title"><span class="me-2">🗂️</span>Phân bổ của tôi</h2>
            <table class="table-basic">
                <thead><tr><th>Booking</th><th>Điểm hẹn</th><th>Bắt đầu</th><th>Kết thúc</th><th>Ngày</th><th>Ngày kết thúc</th><th>Ghi chú</th></tr></thead>
                <tbody>
                    <?php foreach ($assignments as $row): ?>
                        <tr>
                            <td>
                                <div><?= isset($row['booking_id']) ? '#' . htmlspecialchars((string)$row['booking_id']) : '—' ?></div>
                                <div class="small fw-bold text-primary"><?= htmlspecialchars($row['tour_name'] ?? ($row['customer_name'] ?? '')) ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['meeting_point'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['start_time'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['end_time'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['assign_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['end_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="simple-card">
            <h3 class="section-title mb-2">Thông tin tour đã được phân bổ</h3>
            <div class="info-alert">Chưa có chuyến đi nào được phân bổ cho bạn.</div>
            <div class="text-muted small">Liên hệ quản trị viên để được phân công vào chuyến phù hợp.</div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php $itList = isset($trip_detail['itinerary']) && is_array($trip_detail['itinerary']) ? $trip_detail['itinerary'] : []; $dep = isset($trip_detail['departure_date']) ? $trip_detail['departure_date'] : ''; ?>
    <?php if ($__tab === 'detail' && !empty($itList)): ?>
    <div class="hdv-container">
    <div class="schedule-card mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="section-title mb-0"><span class="me-2">📅</span>Lịch Trình Hằng Ngày</h2>
            <?php 
                $depText = ' ';
                if ($dep) {
                    $w = (int)date('N', strtotime($dep));
                    $days = [1=>'Thứ Hai',2=>'Thứ Ba',3=>'Thứ Tư',4=>'Thứ Năm',5=>'Thứ Sáu',6=>'Thứ Bảy',7=>'Chủ Nhật'];
                    $depText = $days[$w] . ', ' . date('d/m/Y', strtotime($dep));
                }
            ?>
            <div class="date-pill"><span>📅</span><span><?= $depText ?></span></div>
        </div>
        <div>
            <table class="table-basic">
                <thead><tr><th>Thời gian</th><th>Hoạt động</th><th>Địa điểm</th><th>Trạng thái</th></tr></thead>
                <tbody>
                <?php 
                $completed=0; $total=0; $lastTime=null;
                foreach ($itList as $item):
                    $total++;
                    $act = trim((string)($item['activities'] ?? ($item['title'] ?? '')));
                    preg_match('/(\d{1,2}:\d{2})/', $act, $m);
                    $time = $m[1] ?? (substr($item['time_start'] ?? '',0,5)); 
                    $desc = $item['description'] ?? '';
                    
                    $status = ($time==='07:00' ? 'Hoàn thành' : ($time==='08:00' ? 'Đang thực hiện' : 'Chưa bắt đầu'));
                    if($status==='Hoàn thành') $completed++;
                ?>
                    <tr>
                        <td><span class="fw-bold"><?= htmlspecialchars($time) ?></span></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($act) ?></div>
                            <?php if ($desc): ?><div class="text-muted small mt-1"><?= nl2br(htmlspecialchars($desc)) ?></div><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['location'] ?? '—') ?></td>
                        <td><span class="status-pill <?= $status==='Hoàn thành'?'status-ok':($status==='Đang thực hiện'?'status-run':'status-wait') ?>"><?= $status ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($trip_detail['services'])): ?>
    <div class="hdv-container">
        <div class="services-card mt-3">
            <h2 class="section-title"><span class="me-2">🧩</span>Dịch vụ đã phân bổ</h2>
            <?php foreach ($trip_detail['services'] as $service): ?>
            <div class="service-item">
                <div class="fw-semibold">Vận chuyển: <?= htmlspecialchars($service['name'] ?? '') ?></div>
                <div class="text-muted small">SL: <?= (int)($service['qty'] ?? 0) ?> | TT: <span class="text-success fw-semibold"><?= htmlspecialchars($service['status'] ?? '') ?></span></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>