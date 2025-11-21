<?php
$tourList = isset($tours) && is_array($tours) ? $tours : [];
$categoryOptions = isset($categories) && is_array($categories) ? $categories : [];
$filters = isset($filters) && is_array($filters) ? $filters : [
    'keyword' => '',
    'category_id' => '',
    'destination' => '',
    'price_order' => '',
];
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
            <div class="search-wrap">
                <input type="text" class="form-control" placeholder="Tìm kiếm nhanh" readonly/>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary">VN</span>
            <div class="avatar rounded-circle bg-secondary-subtle"></div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Danh sách</p>
            <h2 class="page-title mb-0">Quản lý danh sách Tour</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=tours-create" class="btn btn-success rounded-pill px-4">+ Thêm tour</a>
        </div>
    </div>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success mb-3"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['new_tour_debug'])): $dbg = $_SESSION['new_tour_debug']; ?>
        <div class="mb-3">
            <?php if ($dbg['foundInList']): ?>
                <div class="alert alert-success"><?= htmlspecialchars($dbg['message']) ?></div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <?= htmlspecialchars($dbg['message']) ?>
                    <?php if (!empty($dbg['direct'])): ?>
                        <div class="small mt-2">Dữ liệu lưu trực tiếp: <pre style="white-space:pre-wrap"><?= htmlspecialchars(json_encode($dbg['direct'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) ?></pre></div>
                    <?php else: ?>
                        <div class="small mt-2 text-muted">Không tìm thấy bản ghi trong CSDL.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php unset($_SESSION['new_tour_debug']); endif; ?>

    <div class="card-like mb-4">
        <form class="filter-bar" method="get" action="<?= BASE_URL ?>">
            <input type="hidden" name="action" value="tours">

            <div class="filter-inputs row g-3 flex-grow-1 w-100 align-items-center">
                <div class="col-12 col-lg-3 col-xl-2">
                    <input
                        class="form-control form-control-sm"
                        name="keyword"
                        value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>"
                        placeholder="Nhập từ khóa tìm kiếm"
                    />
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <select class="form-select form-select-sm" name="category_id">
                        <option value="">Chọn loại tour</option>
                        <?php foreach ($categoryOptions as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= (string)($filters['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <input
                        class="form-control form-control-sm"
                        name="destination"
                        value="<?= htmlspecialchars($filters['destination'] ?? '') ?>"
                        placeholder="Nhập địa điểm tour"
                    />
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <select class="form-select form-select-sm" name="price_order">
                        <option value="">Giá mặc định</option>
                        <option value="desc" <?= ($filters['price_order'] ?? '') === 'desc' ? 'selected' : '' ?>>Giá cao nhất</option>
                        <option value="asc" <?= ($filters['price_order'] ?? '') === 'asc' ? 'selected' : '' ?>>Giá thấp nhất</option>
                    </select>
                </div>
                <div class="col-12 col-lg-auto ms-lg-auto">
                    <div class="filter-actions d-flex align-items-center gap-2 justify-content-lg-end">
                        <button class="btn btn-warning" type="submit">Tìm kiếm</button>
                        <a class="btn btn-light text-secondary" href="<?= BASE_URL ?>?action=tours">Đặt lại</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card-like">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Tên tour</th>
                        <th>Loại Tour</th>
                        <th>Hành trình / Địa điểm</th>
                        <th>Giá tour</th>
                        <th>Doanh thu ước tính</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tourList)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Hiện chưa có tour nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tourList as $tour): ?>
                            <?php
                                $thumb = !empty($tour['image']) 
                                    ? BASE_URL . ltrim($tour['image'], '/')
                                    : BASE_ASSETS_UPLOADS . 'img/1.jpg';
                                $price = $tour['price'] ?? null;
                                $estRevenue = is_numeric($price) ? (float)$price * 0.4 : null;
                                $statusText = $tour['status'] ?? 'Hoạt động';
                                $statusClass = 'bg-secondary';
                                if (in_array(strtolower($statusText), ['hoạt động', 'active'])) {
                                    $statusClass = 'bg-success';
                                } elseif (in_array(strtolower($statusText), ['tạm dừng', 'paused'])) {
                                    $statusClass = 'bg-warning text-dark';
                                }
                            ?>
                            <tr>
                                <td class="text-muted"><?= htmlspecialchars((string)($tour['id'] ?? '')) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= htmlspecialchars($thumb) ?>" alt="" class="tour-thumb rounded">
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($tour['name'] ?? '—') ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($tour['policy'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($tour['category_name'] ?? 'Chưa phân loại') ?></td>
                                <td><?= htmlspecialchars($tour['itinerary'] ?? 'Đang cập nhật') ?></td>
                                <td>
                                    <?php 
                                        echo is_numeric($price) 
                                            ? number_format((float)$price, 0, ',', '.') . 'đ'
                                            : '—';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        echo is_numeric($estRevenue) 
                                            ? number_format($estRevenue, 0, ',', '.') . 'đ'
                                            : '—';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= $statusClass ?>">
                                        <?= htmlspecialchars($statusText) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="<?= BASE_URL ?>?action=tours-edit&id=<?= $tour['id'] ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                        <a href="<?= BASE_URL ?>?action=tours-delete&id=<?= $tour['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa tour này không?')">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

