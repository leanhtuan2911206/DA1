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
                            <?= htmlspecialchars($t['name'] ?: '—') ?>
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
    </script>
</main>

