<?php
$guideList = isset($guides) && is_array($guides) ? $guides : [];
$filters = array_merge([
    'keyword'  => '',
    'language' => '',
    'status'   => '',
], $filters ?? []);
$summary = array_merge([
    'total'    => 0,
    'active'   => 0,
    'inactive' => 0,
    'on_leave' => 0,
], $summary ?? []);

$statusBadges = [
    'active'   => ['label' => 'Đang làm việc', 'class' => 'bg-success'],
    'inactive' => ['label' => 'Tạm ngưng', 'class' => 'bg-secondary'],
    'on_leave' => ['label' => 'Đang nghỉ phép', 'class' => 'bg-warning text-dark'],
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
            <p class="text-uppercase text-muted small mb-1">Nguồn lực</p>
            <h2 class="page-title mb-0">Quản lý nhân sự</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=guides-create" class="btn btn-success rounded-pill px-4">+ Thêm nhân sự</a>
        </div>
    </div>

    <?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success mb-3"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Tổng nhân sự</p>
                    <h4 id="summary-total" class="mb-0"><?= (int)$summary['total'] ?></h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Đang làm việc</p>
                    <h4 id="summary-active" class="text-success mb-0"><?= (int)$summary['active'] ?></h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Tạm dừng</p>
                    <h4 id="summary-inactive" class="text-secondary mb-0"><?= (int)$summary['inactive'] ?></h4>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Nghỉ phép</p>
                    <h4 id="summary-on-leave" class="text-warning mb-0"><?= (int)$summary['on_leave'] ?></h4>
                </div>
            </div>
        </div>
    </div>

    <?php
        // Kiểm tra xem data trả về có cột `status` hay không
        $statusColumnExists = false;
        if (!empty($guideList) && is_array($guideList[0])) {
            $statusColumnExists = array_key_exists('status', $guideList[0]);
        }
    ?>
    <div class="card-like mb-4">
        <form class="filter-bar" method="get" action="<?= BASE_URL ?>">
            <input type="hidden" name="action" value="guides">
            <div class="filter-inputs row g-3 flex-grow-1 w-100 align-items-center">
                <div class="col-12 col-lg-3 col-xl-2">
                    <input
                        class="form-control form-control-sm"
                        name="keyword"
                        value="<?= htmlspecialchars($filters['keyword']) ?>"
                        placeholder="Tên, số điện thoại, ngôn ngữ"
                    />
                </div>
                <div class="col-12 col-lg-3 col-xl-2">
                    <input
                        class="form-control form-control-sm"
                        name="language"
                        value="<?= htmlspecialchars($filters['language']) ?>"
                        placeholder="Lọc theo ngôn ngữ"
                    />
                </div>
                <div class="col-12 col-lg-2 col-xl-2">
                    <select class="form-select form-select-sm" name="gender">
                        <option value="">Giới tính</option>
                        <option value="Nam" <?= $filters['gender'] === 'Nam' ? 'selected': '' ?>>Nam</option>
                        <option value="Nữ" <?= $filters['gender'] === 'Nữ' ? 'selected': '' ?>>Nữ</option>
                    </select>
                </div>
                <?php if ($statusColumnExists): ?>
                <div class="col-12 col-lg-2 col-xl-2">
                    <select class="form-select form-select-sm" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Đang làm việc</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Tạm dừng</option>
                        <option value="on_leave" <?= $filters['status'] === 'on_leave' ? 'selected' : '' ?>>Nghỉ phép</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12 col-lg-auto ms-lg-auto">
                    <div class="filter-actions d-flex align-items-center gap-2 justify-content-lg-end">
                        <button class="btn btn-sm btn-warning px-3 py-1 d-inline-flex align-items-center rounded-pill" type="submit">Lọc</button>
                        <a class="btn btn-sm btn-light text-secondary px-3 py-1 d-inline-flex align-items-center rounded-pill" href="<?= BASE_URL ?>?action=guides">Đặt lại</a>
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
                        <th width="70">Mã số</th>
                        <th>Họ tên</th>
                        <th>Liên hệ</th>
                        <th>Ngôn ngữ</th>
                        <th>Chứng chỉ</th>
                        <th class="text-center">Kinh nghiệm</th>
                        <th>Sức khỏe</th>
                        <th class="text-center">Điểm</th>
                        <th>Trạng thái</th>
                        <?php if ($statusColumnExists): ?>
                        <th width="160">Thao tác</th>
                        <?php endif; ?>
                        <th width="130">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($guideList)): ?>
                        <tr>
                            <td colspan="<?= $statusColumnExists ? 11 : 10 ?>" class="text-center text-muted py-5">Chưa có nhân sự nào khớp bộ lọc.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($guideList as $guide): ?>
                            <?php
                                // Lấy trạng thái an toàn: nếu không có cột `status` hoặc giá trị rỗng, mặc định là 'active'
                                $rawStatus = $guide['status'] ?? null;
                                if ($rawStatus === null || trim((string)$rawStatus) === '') {
                                    $statusKey = 'active';
                                } else {
                                    $statusKey = strtolower((string)$rawStatus);
                                }
                                $badge = $statusBadges[$statusKey] ?? ['label' => ucfirst($statusKey ?: 'khác'), 'class' => 'bg-secondary'];
                                $isNew = isset($_GET['new']) && (int)$_GET['new'] === ((int)($guide['HDV_ID'] ?? 0));
                            ?>
                            <tr <?= $isNew ? 'class="table-success"' : '' ?>>
                                <td class="text-muted"><?= htmlspecialchars((string)($guide['ma_so'] ?? $guide['HDV_ID'] ?? '')) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($guide['HoTen'] ?? '—') ?></div>
                                    <div class="small text-muted">
                                        <?php if (!empty($guide['NgaySinh'])): ?>
                                            SN: <?= htmlspecialchars($guide['NgaySinh']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($guide['GioiTinh'])): ?>
                                            · <?= htmlspecialchars($guide['GioiTinh']) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($guide['LienHe'] ?? '—') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($guide['DiaChi'] ?? '') ?></div>
                                </td>
                                <td><?= htmlspecialchars($guide['NgonNgu'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($guide['ChungChiHDV'] ?? '—') ?></td>
                                <td class="text-center"><?= htmlspecialchars($guide['KinhNghiem'] ?? '0') ?> năm</td>
                                <td><?= htmlspecialchars($guide['TrangThaiSucKhoe'] ?? '—') ?></td>
                                <td class="text-center"><?= htmlspecialchars($guide['DiemDanhGia'] ?? '—') ?></td>
                                <td>
                                    <span id="badge-<?= $guide['HDV_ID'] ?>" class="badge rounded-pill <?= $badge['class'] ?>">
                                        <?= htmlspecialchars($badge['label']) ?>
                                    </span>
                                </td>
                                <?php if ($statusColumnExists): ?>
                                <td>
                                   <form method="post" action="<?= BASE_URL ?>?action=guides-update-status" style="display: inline;">
                                        <input type="hidden" name="id" value="<?= $guide['HDV_ID'] ?>">
                                        <select
                                            name="status"
                                            class="form-select form-select-sm"
                                            onchange="this.form.submit()"
                                            aria-label="Cập nhật trạng thái">
                                            <option value="active" <?= $statusKey === 'active' ? 'selected' : '' ?>>Đang làm việc</option>
                                            <option value="inactive" <?= $statusKey === 'inactive' ? 'selected' : '' ?>>Tạm dừng</option>
                                            <option value="on_leave" <?= $statusKey === 'on_leave' ? 'selected' : '' ?>>Nghỉ phép</option>
                                        </select>
                                    </form>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="<?= BASE_URL ?>?action=guides-edit&id=<?= $guide['HDV_ID'] ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                        <a
                                            href="<?= BASE_URL ?>?action=guides-delete&id=<?= $guide['HDV_ID'] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Bạn có chắc muốn xóa nhân sự này?');"
                                        >🗑️</a>
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
