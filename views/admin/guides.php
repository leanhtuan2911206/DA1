<?php
$guidesPage = $guidesPage ?? ['guides' => [], 'stats' => []];
$guides = $guidesPage['guides'] ?? [];
$stats = array_merge([
    'total' => 0,
    'male' => 0,
    'female' => 0,
    'avgExperience' => 0,
], $guidesPage['stats'] ?? []);
?>

<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
            <div>
                <p class="text-uppercase text-muted small mb-0">Quản lý nhân sự</p>
                <h5 class="mb-0 fw-semibold">Hướng dẫn viên</h5>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary">VN</span>
            <div class="avatar rounded-circle bg-secondary-subtle"></div>
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

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="text-uppercase text-muted small mb-1">Danh sách</p>
            <h2 class="page-title mb-0">Quản lý Hướng dẫn viên</h2>
            <p class="text-muted mb-0 small">Theo dõi tình trạng nhân sự HDV trong hệ thống</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>?action=guides-create" class="btn btn-success rounded-pill px-4 d-flex align-items-center gap-2">
                <span>＋</span> Thêm HDV mới
            </a>
        </div>
    </div>

    <!-- Quick stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="info-tile bg-primary-subtle text-primary">
                <div class="fs-1 fw-bold"><?= number_format($stats['total']) ?></div>
                <div class="small text-uppercase text-muted">Tổng số HDV</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="info-tile bg-info-subtle text-info">
                <div class="fs-1 fw-bold"><?= number_format($stats['male']) ?></div>
                <div class="small text-uppercase text-muted">HDV Nam</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="info-tile bg-pink-subtle text-danger">
                <div class="fs-1 fw-bold"><?= number_format($stats['female']) ?></div>
                <div class="small text-uppercase text-muted">HDV Nữ</div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="info-tile bg-warning-subtle text-warning">
                <div class="fs-2 fw-bold"><?= number_format($stats['avgExperience'], 1) ?> năm</div>
                <div class="small text-uppercase text-muted">Kinh nghiệm TB</div>
            </div>
        </div>
    </div>

    <div class="card-like">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Họ tên</th>
                        <th>Ngày sinh</th>
                        <th>Giới tính</th>
                        <th>Liên hệ</th>
                        <th>Ngôn ngữ</th>
                        <th>Địa chỉ</th>
                        <th>Chứng chỉ </th>
                        <th>Kinh nghiệm</th>
                        <th>Điểm đánh giá</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($guides)): ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                Hiện chưa có hướng dẫn viên nào trong hệ thống.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($guides as $guide): ?>
                            <?php
                                $gender = trim($guide['GioiTinh'] ?? 'N/A');
                                $genderLower = strtolower($gender);
                                $genderBadge = 'bg-secondary';
                                if (in_array($genderLower, ['nam', 'male'], true)) {
                                    $genderBadge = 'bg-primary';
                                } elseif (in_array($genderLower, ['nữ', 'nu', 'female'], true)) {
                                    $genderBadge = 'bg-danger';
                                }
                            ?>
                            <tr>
                                <td class="text-muted fw-semibold"><?= htmlspecialchars($guide['HDV_ID']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($guide['HoTen'] ?? 'N/A') ?></div>
                                    <div class="text-muted small">ID nội bộ: HDV<?= str_pad((string)$guide['HDV_ID'], 3, '0', STR_PAD_LEFT) ?></div>
                                </td>
                                <td><?= isset($guide['NgaySinh']) ? date('d/m/Y', strtotime($guide['NgaySinh'])) : 'N/A' ?></td>
                                <td>
                                    <span class="badge rounded-pill <?= $genderBadge ?>">
                                        <?= $gender ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($guide['LienHe'] ?? 'N/A') ?></div>
                                    <?php if (!empty($guide['Email'])): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($guide['Email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($guide['NgonNgu'] ?? 'Đang cập nhật') ?></td>
                                <td><?= htmlspecialchars($guide['DiaChi'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($guide['ChungChiHDV'] ?? 'N/A') ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($guide['KinhNghiem'] ?? 0) ?> năm</div>
                                </td>
                                <td><?= htmlspecialchars($guide['DiemDanhGia'] ?? 'Chưa có') ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="<?= BASE_URL ?>?action=guides-edit&id=<?= $guide['HDV_ID'] ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                        <a href="<?= BASE_URL ?>?action=guides-delete&id=<?= $guide['HDV_ID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa HDV <?= htmlspecialchars($guide['HoTen'] ?? '') ?>?')">🗑️</a>
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