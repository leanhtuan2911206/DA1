<main class="main-content">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button">☰</button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary">VN</span>
            <div class="avatar rounded-circle bg-secondary-subtle"></div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Tạo mới</p>
            <h2 class="page-title mb-0">Tạo tài khoản mới</h2>
        </div>
        <div>
            <a href="<?= BASE_URL ?>?action=users" class="btn btn-light rounded-pill px-4">← Quay lại</a>
        </div>
    </div>

    <?php if (session_status() === PHP_SESSION_NONE) {
        session_start();
    } ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card-like">
        <form method="post" action="<?= BASE_URL ?>?action=users-store">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">Tạo tài khoản</button>
                        <a href="<?= BASE_URL ?>?action=users" class="btn btn-light">Hủy</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>