
<div class="login-page">
    <div class="login-card">
        <h2 class="card-title text-center mb-4">Đăng Nhập Quản Trị</h2>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>?action=login" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-3">Đăng Nhập</button>
        </form>
        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>">Về trang chủ</a>
        </div>
    </div>
</div>