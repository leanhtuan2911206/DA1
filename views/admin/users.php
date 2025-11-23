<?php
$users = isset($users) && is_array($users) ? $users : [];
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
            <h2 class="page-title mb-0">Quản lý tài khoản</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=users-create" class="btn btn-success rounded-pill px-4">+ Tạo tài khoản mới</a>
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

    <div class="card-like">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="5%">ID</th>
                    <th>Tên</th>
                    <th>Email</th>
                    <th width="15%">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>?action=users-edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                    <a href="<?= BASE_URL ?>?action=users-delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn chắc chắn muốn xóa tài khoản này?')">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Chưa có tài khoản</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>