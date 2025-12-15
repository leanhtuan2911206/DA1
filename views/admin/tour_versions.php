<?php
 $tour= isset($tour) ? $tour : [];
 $versions= isset($versions) && is_array($versions) ? $versions : [];
 $tourId = isset($tour['id']) ? (int)$tour['id'] : 0;
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
            <p class="text-uppercase text-muted small mb-1">Quản lý phiên bản</p>
            <h2 class="page-title mb-0">Quản lý phiên bản tour: <?= htmlspecialchars($tour['name'] ?? '') ?></h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=tours" class="btn btn-secondary rounded-pill px-4 me-2">← Quay lại</a>
            <a href="<?= BASE_URL ?>?action=tours-version-create&tour_id=<?= $tourId ?>" class="btn btn-success rounded-pill px-4">+ Thêm phiên bản</a>
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

    <div class="card-like mb-4">
        <h4 class="mb-3">Thông tin tour gốc</h4>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Tên tour:</strong> <?= htmlspecialchars($tour['name'] ?? '') ?></p>
                <p><strong>Loại tour:</strong> <?= htmlspecialchars($tour['category_name'] ?? 'Chưa phân loại') ?></p>
                <p><strong>Giá gốc:</strong> 
                    <?php 
                        $price = $tour['price'] ?? 0;
                        echo is_numeric($price) ? number_format((float)$price, 0, ',', '.') . 'đ' : '—';
                    ?>
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Trạng thái:</strong> 
                    <span class="badge bg-success"><?= htmlspecialchars($tour['status'] ?? 'Hoạt động') ?></span>
                </p>
                <p><strong>Mô tả:</strong> <?= htmlspecialchars(mb_substr($tour['description'] ?? '', 0, 100)) ?></p>
            </div>
        </div>
    </div>

    <div class="card-like">
        <h4 class="mb-3">Danh sách phiên bản tour</h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Loại phiên bản</th>
                        <th>Tên phiên bản</th>
                        <th>Giá</th>
                        <th>Thời gian áp dụng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($versions)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                Chưa có phiên bản nào. <a href="<?= BASE_URL ?>?action=tours-version-create&tour_id=<?= $tourId ?>">Tạo phiên bản đầu tiên</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($versions as $version): ?>
                            <?php
                                $typeLabels = [
                                    'seasonal' => 'Theo mùa',
                                    'promotional' => 'Khuyến mãi',
                                    'special' => 'Đặc biệt'
                                ];
                                $typeColors = [
                                    'seasonal' => 'bg-info',
                                    'promotional' => 'bg-warning text-dark',
                                    'special' => 'bg-primary'
                                ];
                                $typeLabel = $typeLabels[$version['version_type']] ?? $version['version_type'];
                                $typeColor = $typeColors[$version['version_type']] ?? 'bg-secondary';
                                
                                $statusText = $version['status'] ?? 'active';
                                $statusClass = 'bg-success';
                                if ($statusText === 'inactive') {
                                    $statusClass = 'bg-secondary';
                                }
                            ?>
                            <tr>
                                <td class="text-muted"><?= htmlspecialchars((string)($version['id'] ?? '')) ?></td>
                                <td>
                                    <span class="badge <?= $typeColor ?>"><?= htmlspecialchars($typeLabel) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($version['name'] ?? '') ?></div>
                                    <?php if (!empty($version['description'])): ?>
                                        <div class="text-muted small"><?= htmlspecialchars(mb_substr($version['description'], 0, 50)) ?>...</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $price = $version['price'] ?? null;
                                        if ($price !== null && is_numeric($price)) {
                                            echo number_format((float)$price, 0, ',', '.') . 'đ';
                                        } else {
                                            echo '<span class="text-muted">Giá tour gốc</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($version['start_date']) || !empty($version['end_date'])): ?>
                                        <?php if (!empty($version['start_date'])): ?>
                                            <div><small>Từ:</small> <?= htmlspecialchars($version['start_date']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($version['end_date'])): ?>
                                            <div><small>Đến:</small> <?= htmlspecialchars($version['end_date']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Không giới hạn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= $statusClass ?>">
                                        <?= $statusText === 'active' ? 'Hoạt động' : 'Không hoạt động' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="<?= BASE_URL ?>?action=tours-version-edit&id=<?= $version['id'] ?>" 
                                           class="btn btn-sm btn-outline-secondary" title="Sửa">
                                            ✏️
                                        </a>
                                        <a href="<?= BASE_URL ?>?action=tours-version-delete&id=<?= $version['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Bạn có chắc muốn xóa phiên bản này không?')" 
                                           title="Xóa">
                                            🗑️
                                        </a>
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
