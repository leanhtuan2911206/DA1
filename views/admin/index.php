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
                    <div class="stat-value"><?= isset($userCount) ? $userCount : 0 ?></div>
                    <div class="stat-trend text-success">+17%</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning-subtle text-warning">🧳</div>
                <div class="stat-content">
                    <div class="stat-label">Tour đang mở</div>
                    <div class="stat-value"><?= isset($tourCount) ? $tourCount : 0 ?></div>
                    <div class="stat-trend text-success">+10%</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success">💵</div>
                <div class="stat-content">
                    <div class="stat-label">Doanh thu</div>
                    <div class="stat-value">1.9 tỷ</div>
                    <div class="stat-trend text-danger">-4.3%</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon bg-info-subtle text-info">⏱️</div>
                <div class="stat-content">
                    <div class="stat-label">Tour chờ / xử lý</div>
                    <div class="stat-value">15 / 40</div>
                    <div class="stat-trend text-info">Đi đến quản lý booking</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-like mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Số tour được đặt</div>
            <select class="form-select form-select-sm w-auto"><option>Tháng 11</option></select>
        </div>
        <canvas id="bookingsChart" height="100"></canvas>
    </div>

    <?php
        $topTours = [
            ['name' => 'Du lịch Hội An', 'type' => 'Trong nước', 'place' => 'Phố cổ Hội An - Đà Nẵng - VN', 'price' => '1.200.000đ', 'revenue' => '480.000.000đ', 'status' => 'Hoạt động'],
            ['name' => 'Du lịch Cao Bằng', 'type' => 'Trong nước', 'place' => 'Danh lam Cao Bằng', 'price' => '1.900.000đ', 'revenue' => '10.000.000đ', 'status' => 'Tạm dừng'],
            ['name' => 'Du lịch Miền tây', 'type' => 'Trong nước', 'place' => 'Chợ nổi Cần Thơ', 'price' => '1.000.000đ', 'revenue' => '40.000.000đ', 'status' => 'Hoạt động'],
            ['name' => 'Du lịch Thái Lan', 'type' => 'Quốc tế', 'place' => 'Thủ đô Băng Cốc', 'price' => '5.200.000đ', 'revenue' => '70.000.000đ', 'status' => 'Hoạt động'],
            ['name' => 'Du lịch Lai Châu', 'type' => 'Trong nước', 'place' => 'Núi Tam Đường - Lai Châu', 'price' => '700.000đ', 'revenue' => '30.000.000đ', 'status' => 'Hoạt động'],
        ];
    ?>

    <div class="card-like">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Quản lý danh sách Tour</div>
            <div class="d-flex gap-2">
                <input class="form-control form-control-sm" placeholder="Nhập từ khóa tìm kiếm"/>
                <select class="form-select form-select-sm"><option>Chọn loại tour</option></select>
                <input class="form-control form-control-sm" placeholder="Nhập địa điểm tour"/>
                <select class="form-select form-select-sm"><option>Giá cao nhất</option></select>
                <button class="btn btn-sm btn-warning">Tìm kiếm</button>
                <button class="btn btn-sm btn-success">+ Thêm tour</button>
            </div>
        </div>

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
                    <?php foreach ($topTours as $t): ?>
                    <tr>
                        <td>
                            <span class="flag me-2">🇻🇳</span>
                            <?= $t['name'] ?>
                        </td>
                        <td><?= $t['type'] ?></td>
                        <td><?= $t['place'] ?></td>
                        <td><?= $t['price'] ?></td>
                        <td><?= $t['revenue'] ?></td>
                        <td>
                            <span class="badge rounded-pill <?= $t['status']==='Hoạt động' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $t['status'] ?></span>
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
        const labels = Array.from({length: 30}, (_, i)=> String(i+1).padStart(2,'0'));
        const data = [20,25,30,45,50,40,55,84,38,42,51,60,74,68,48,70,82,66,55,64,40,52,58,46,62,59,49,54,57,60];
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

