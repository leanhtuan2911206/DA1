
<?php 
$uploadsDir = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$bgFs  = $uploadsDir . 'img/login.jpg';
$bgUrl = BASE_ASSETS_UPLOADS . 'img/login.jpg';
if (!is_file($bgFs)) {
    if (is_file($uploadsDir . 'background.jpg'))      { $bgUrl = BASE_ASSETS_UPLOADS . 'background.jpg'; }
    elseif (is_file($uploadsDir . 'background.png'))  { $bgUrl = BASE_ASSETS_UPLOADS . 'background.png'; }
    elseif (is_file($uploadsDir . 'login-bg.jpg'))    { $bgUrl = BASE_ASSETS_UPLOADS . 'login-bg.jpg'; }
    elseif (is_file($uploadsDir . 'login-bg.png'))    { $bgUrl = BASE_ASSETS_UPLOADS . 'login-bg.png'; }
}
$logoFs = $uploadsDir . 'img/logo.png';
$logoUrl = BASE_ASSETS_UPLOADS . 'img/logo.png';
?>
<style>
/* Background image override - dynamic from PHP */
.login-page {
    background: url('<?= $bgUrl ?>') center/cover no-repeat fixed !important;
}
.login-page::before {
    background: url('<?= $bgUrl ?>') center/cover no-repeat !important;
}
</style>

<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            <?php if (is_file($logoFs)): ?>
                <img src="<?= $logoUrl ?>" alt="Logo" class="login-logo">
            <?php endif; ?>
            <h2 class="login-title">ĐĂNG NHẬP</h2>
        </div>
        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>?action=login" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email hoặc Số điện thoại:</label>
                <input type="text" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Nhập email hoặc số điện thoại" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary mt-2">Đăng nhập</button>
            <a>Chưa có tài khoản liên hệ Admin ngay?</a>
        </form>
    </div>
</div>