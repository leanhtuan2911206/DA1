<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
            <div class="search-wrap">
                <input type="text" class="form-control" placeholder="Tìm kiếm"/>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary">VN</span>
            <div class="avatar rounded-circle bg-secondary-subtle"></div>
        </div>
    </div>

    <h2 class="page-title">Báo cáo - Thống Kê</h2>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary">👥</div>
                <div class="stat-content">
                    <div class="stat-label">Tổng khách hàng</div>
                    <div class="stat-value"><?= isset($customerCount) ? (int)$customerCount : 0 ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning">🧳</div>
                <div class="stat-content">
                    <div class="stat-label">Tour đang mở</div>
                    <div class="stat-value"><?= isset($tourOpenCount) ? (int)$tourOpenCount : (int)($tourCount ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success">💵</div>
                <div class="stat-content">
                    <div class="stat-label">Doanh thu</div>
                    <div class="stat-value"><?= isset($revenue) ? number_format((float)$revenue, 0, ',', '.') . 'đ' : '—' ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-info-subtle text-info">⏱️</div>
                <div class="stat-content">
                    <div class="stat-label">Tour chờ / xử lý</div>
                    <div class="stat-value"><?= (int)($pendingBookings ?? 0) ?> / <?= (int)($bookingCount ?? 0) ?></div>
                    <div class="stat-trend text-info"><a href="<?= BASE_URL ?>?action=bookings" class="text-info">Đi đến quản lý booking</a></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-like mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Số tour được đặt</div>
            <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>">
                <input type="hidden" name="action" value="admin" />
                <input type="hidden" name="year" value="<?= isset($selectedYear) ? (int)$selectedYear : (int)date('Y') ?>" />
                <select name="month" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <?php for ($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= isset($selectedMonth) && (int)$selectedMonth === $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
        <canvas id="bookingsChart" height="100"></canvas>
    </div>

    <div class="card-like mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Doanh thu theo thời gian</div>
            <div class="d-flex flex-wrap gap-2">
                <select name="tour_id" class="form-select form-select-sm w-auto" id="revTourId">
                    <option value="0">Tất cả tour</option>
                    <?php foreach (($toursList ?? []) as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (int)($revTourId ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars(removeVNPrefix($t['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="period" class="form-select form-select-sm w-auto" id="revPeriod">
                    <option value="month" <?= ($revPeriod ?? 'month') === 'month' ? 'selected' : '' ?>>Tháng</option>
                    <option value="quarter" <?= ($revPeriod ?? 'month') === 'quarter' ? 'selected' : '' ?>>Quý</option>
                    <option value="year" <?= ($revPeriod ?? 'month') === 'year' ? 'selected' : '' ?>>Năm</option>
                </select>
                <input type="number" class="form-control form-control-sm w-auto" name="rev_year" id="revYear" value="<?= (int)($revYear ?? date('Y')) ?>" min="2000" max="2100" />
                <select name="rev_month" class="form-select form-select-sm w-auto" id="revMonth">
                    <?php for ($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= (int)($revMonth ?? 1) === $m ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                    <?php endfor; ?>
                </select>
                <select name="rev_quarter" class="form-select form-select-sm w-auto" id="revQuarter">
                    <?php for ($q=1; $q<=4; $q++): ?>
                        <option value="<?= $q ?>" <?= (int)($revQuarter ?? 1) === $q ? 'selected' : '' ?>>Quý <?= $q ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="text-muted">Tổng doanh thu: <span class="fw-semibold text-success" id="revenueTotal"><?php echo number_format((float)($revenueSelectedTotal ?? 0), 0, ',', '.'); ?>đ</span></div>
        </div>
        <canvas id="revenueChart" height="100"></canvas>
    </div>

    <!-- Danh sách Tour đã hoàn thành -->
    <?php $completedTours = isset($completedTours) && is_array($completedTours) ? $completedTours : []; ?>
    <h3 class="section-title">Tour đã hoàn thành</h3>
    
    <?php if (empty($completedTours)): ?>
        <div class="card-like">
            <div class="text-center text-muted py-5">
                Chưa có tour nào hoàn thành.
            </div>
        </div>
    <?php else: ?>
        <div class="card-like mb-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Tên tour</th>
                            <th>Loại tour</th>
                            <th>Số booking</th>
                            <th>Doanh thu</th>
                            <th>Ngày tour cuối</th>
                            <th>Sự cố/Sự kiện</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedTours as $tour): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars(removeVNPrefix($tour['tour_name'] ?: '—')) ?></div>
                                </td>
                                <td><?= htmlspecialchars($tour['category_name'] ?: '—') ?></td>
                                <td>
                                    <span class="badge bg-info"><?= (int)($tour['total_bookings'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <span class="text-success fw-semibold">
                                        <?= number_format((float)($tour['total_revenue'] ?? 0), 0, ',', '.') ?>đ
                                    </span>
                                </td>
                                <td>
                                    <?= !empty($tour['last_tour_date']) ? date('d/m/Y', strtotime($tour['last_tour_date'])) : '—' ?>
                                </td>
                                <td>
                                    <?php 
                                    $issues = $tour['issues'] ?? [];
                                    $issueCount = count($issues);
                                    if ($issueCount > 0): 
                                        $openIssues = [];
                                        foreach ($issues as $i) {
                                            if (($i['status'] ?? 'open') === 'open') {
                                                $openIssues[] = $i;
                                            }
                                        }
                                        $openCount = count($openIssues);
                                    ?>
                                        <span class="badge <?= $openCount > 0 ? 'bg-danger' : 'bg-warning' ?>">
                                            <?= $openCount > 0 ? $openCount . ' đang mở' : 'Đã xử lý' ?> / <?= $issueCount ?> tổng
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Không có</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($issues)): ?>
                                        <button 
                                            class="btn btn-sm btn-outline-primary" 
                                            type="button"
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#tourDetails<?= (int)$tour['id'] ?>"
                                            aria-expanded="false"
                                        >
                                            Xem chi tiết
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Không có sự cố</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($issues)): ?>
                                <tr>
                                    <td colspan="7" class="p-0">
                                        <div class="collapse" id="tourDetails<?= (int)$tour['id'] ?>">
                                            <div class="card card-body bg-light">
                                                <h6 class="mb-3">Sự cố / Sự kiện liên quan:</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 150px;">Loại</th>
                                                                <th>Tiêu đề</th>
                                                                <th style="width: 100px;">Mức độ</th>
                                                                <th style="width: 100px;">Trạng thái</th>
                                                                <th style="width: 150px;">Ngày tạo</th>
                                                                <th>Mô tả</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($issues as $issue): ?>
                                                                <tr>
                                                                    <td>
                                                                        <?php 
                                                                        $issueType = $issue['issue_type'] ?? 'issue';
                                                                        $typeLabels = [
                                                                            'issue' => 'Sự cố',
                                                                            'event' => 'Sự kiện',
                                                                            'complaint' => 'Khiếu nại',
                                                                            'feedback' => 'Phản hồi'
                                                                        ];
                                                                        echo htmlspecialchars($typeLabels[$issueType] ?? $issueType);
                                                                        ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($issue['title'] ?? '—') ?></td>
                                                                    <td>
                                                                        <?php 
                                                                        $severity = strtolower($issue['severity'] ?? 'medium');
                                                                        $severityColors = [
                                                                            'low' => 'bg-info',
                                                                            'medium' => 'bg-warning',
                                                                            'high' => 'bg-danger'
                                                                        ];
                                                                        $severityLabels = [
                                                                            'low' => 'Thấp',
                                                                            'medium' => 'Trung bình',
                                                                            'high' => 'Cao'
                                                                        ];
                                                                        ?>
                                                                        <span class="badge <?= $severityColors[$severity] ?? 'bg-secondary' ?>">
                                                                            <?= $severityLabels[$severity] ?? $severity ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php 
                                                                        $status = strtolower($issue['status'] ?? 'open');
                                                                        $statusColors = [
                                                                            'open' => 'bg-danger',
                                                                            'in_progress' => 'bg-warning',
                                                                            'resolved' => 'bg-success',
                                                                            'closed' => 'bg-secondary'
                                                                        ];
                                                                        $statusLabels = [
                                                                            'open' => 'Mở',
                                                                            'in_progress' => 'Đang xử lý',
                                                                            'resolved' => 'Đã xử lý',
                                                                            'closed' => 'Đã đóng'
                                                                        ];
                                                                        ?>
                                                                        <span class="badge <?= $statusColors[$status] ?? 'bg-secondary' ?>">
                                                                            <?= $statusLabels[$status] ?? $status ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?= !empty($issue['created_at']) ? date('d/m/Y H:i', strtotime($issue['created_at'])) : '—' ?>
                                                                    </td>
                                                                    <td>
                                                                        <small class="text-muted">
                                                                            <?= !empty($issue['description']) ? htmlspecialchars(mb_substr($issue['description'], 0, 100)) . (mb_strlen($issue['description']) > 100 ? '...' : '') : '—' ?>
                                                                        </small>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php $tourRows = isset($tours) && is_array($tours) ? $tours : []; ?>

    <h3 class="section-title">Quản lý danh sách Tour</h3>

    <div class="card-like mb-3">
        <div class="filter-bar d-flex align-items-center">
            <div class="filter-inputs d-flex gap-2 flex-grow-1">
                <input class="form-control form-control-sm" placeholder="Nhập từ khóa tìm kiếm"/>
                <select class="form-select form-select-sm"><option>Chọn loại tour</option></select>
                <input class="form-control form-control-sm" placeholder="Nhập địa điểm tour"/>
                <select class="form-select form-select-sm"><option>Giá cao nhất</option></select>
            </div>
            <div class="filter-actions d-flex gap-2">
                <button class="btn btn-sm btn-warning">Tìm kiếm</button>
                <button class="btn btn-sm btn-success">+ Thêm tour</button>
            </div>
        </div>
    </div>

    <div class="card-like">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Tên tour</th>
                        <th>Loại Tour</th>
                        <th>Địa điểm</th>
                        <th>Giá tour</th>
                        <th>Doanh thu</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tourRows as $t): ?>
                    <tr>
                        <td>
                            <span class="flag me-2">🇻🇳</span>
                            <?= htmlspecialchars(removeVNPrefix($t['name'] ?: '—')) ?>
                        </td>
                        <td><?= htmlspecialchars($t['type'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($t['place'] ?: '—') ?></td>
                        <td>
                            <?php
                                $price = $t['price'];
                                echo is_numeric($price) ? number_format((float)$price, 0, ',', '.') . 'đ' : htmlspecialchars($price ?: '—');
                            ?>
                        </td>
                        <td>
                            <?php
                                $rev = $t['revenue'] ?? null;
                                echo is_numeric($rev) ? number_format((float)$rev, 0, ',', '.') . 'đ' : '—';
                            ?>
                        </td>
                        <td>
                            <?php $st = strtolower((string)$t['status']); ?>
                            <span class="badge rounded-pill <?= $st==='hoạt động' || $st==='active' ? 'bg-success' : ($st==='tạm dừng' || $st==='paused' ? 'bg-warning text-dark' : 'bg-secondary') ?>"><?= htmlspecialchars($t['status'] ?: '—') ?></span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary">✏️</button>
                            <button class="btn btn-sm btn-outline-danger">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('bookingsChart');
        const labels = <?= json_encode($chartLabels ?? []) ?>;
        const data = <?= json_encode($chartValues ?? []) ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Tour được đặt',
                    data,
                    tension: 0.35,
                    borderColor: '#4c6ef5',
                    backgroundColor: 'rgba(76,110,245,.1)',
                    pointBackgroundColor: '#4c6ef5',
                    pointRadius: 4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {legend: {display: false}},
                scales: {y: {beginAtZero: true}}
            }
        });

        const rctx = document.getElementById('revenueChart');
        const rlabels = <?= json_encode($revLabels ?? []) ?>;
        const rdata = <?= json_encode($revValues ?? []) ?>;
        let revenueChart = new Chart(rctx, {
            type: 'bar',
            data: {
                labels: rlabels,
                datasets: [{
                    label: 'Doanh thu',
                    data: rdata,
                    backgroundColor: 'rgba(25,135,84,.2)',
                    borderColor: '#198754'
                }]
            },
            options: {
                responsive: true,
                plugins: {legend: {display: false}},
                scales: {y: {beginAtZero: true}}
            }
        });

        const periodSel = document.getElementById('revPeriod');
        const monthSel = document.getElementById('revMonth');
        const quarterSel = document.getElementById('revQuarter');
        const tourSel = document.getElementById('revTourId');
        const yearInput = document.getElementById('revYear');
        
        function syncVisibility(){
            const p = periodSel.value;
            monthSel.style.display = p==='month' ? '' : 'none';
            quarterSel.style.display = p==='quarter' ? '' : 'none';
        }
        syncVisibility();
        periodSel.addEventListener('change', function() {
            syncVisibility();
            loadRevenueData();
        });

        function loadRevenueData() {
            const params = new URLSearchParams({
                action: 'admin-revenue-data',
                period: periodSel.value,
                rev_year: yearInput.value,
                rev_month: monthSel.value,
                rev_quarter: quarterSel.value,
                tour_id: tourSel.value
            });
            
            fetch('<?= BASE_URL ?>?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        return;
                    }
                    
                    // Cập nhật biểu đồ
                    revenueChart.data.labels = data.labels;
                    revenueChart.data.datasets[0].data = data.values;
                    revenueChart.update();
                    
                    // Cập nhật tổng doanh thu
                    const totalFormatted = new Intl.NumberFormat('vi-VN').format(data.total);
                    document.getElementById('revenueTotal').textContent = totalFormatted + 'đ';
                })
                .catch(error => {
                    console.error('Error loading revenue data:', error);
                });
        }

        // Thêm event listeners cho tất cả các filter
        tourSel.addEventListener('change', loadRevenueData);
        monthSel.addEventListener('change', loadRevenueData);
        quarterSel.addEventListener('change', loadRevenueData);
        yearInput.addEventListener('change', loadRevenueData);
    </script>
</main>

