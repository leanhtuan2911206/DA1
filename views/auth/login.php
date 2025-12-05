
<?php 
$uploadsDir = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$bgFs  = $uploadsDir . '2083948.jpg';
$bgUrl = BASE_ASSETS_UPLOADS . '2083948.jpg';
if (!is_file($bgFs)) {
    if (is_file($uploadsDir . 'background.jpg'))      { $bgUrl = BASE_ASSETS_UPLOADS . 'background.jpg'; }
    elseif (is_file($uploadsDir . 'background.png'))  { $bgUrl = BASE_ASSETS_UPLOADS . 'background.png'; }
    elseif (is_file($uploadsDir . 'login-bg.jpg'))    { $bgUrl = BASE_ASSETS_UPLOADS . 'login-bg.jpg'; }
    elseif (is_file($uploadsDir . 'login-bg.png'))    { $bgUrl = BASE_ASSETS_UPLOADS . 'login-bg.png'; }
}
$logoFs = $uploadsDir . 'logo.png';
$logoUrl = BASE_ASSETS_UPLOADS . 'logo.png';
?>
<style>
.login-page{min-height:100vh;display:flex;align-items:center;justify-content:center;background:url('<?= $bgUrl ?>') center/cover no-repeat fixed}
.login-card{width:560px;max-width:95%;background:rgba(255,255,255,.58);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:22px 26px 26px;box-shadow:0 12px 40px rgba(0,0,0,.25)}
.login-header{position:relative;min-height:80px;margin-bottom:16px}
.login-logo{position:absolute;left:16px;top:50%;transform:translateY(-50%);width:68px;height:68px;border-radius:12px;object-fit:contain}
.login-title{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);margin:0;font-weight:700;font-size:24px;color:#333}
.form-label{font-weight:600;color:#0f172a;margin-bottom:6px}
.form-control{border-radius:14px;background:#e6e6e6;border:1px solid #d0d5dd;padding:12px 16px}
.btn-login{background:linear-gradient(180deg,#3b82f6,#5ea0ff);color:#fff;border:none;border-radius:12px;padding:10px 16px;font-weight:600;width:200px;display:block;margin:8px auto 0;box-shadow:0 2px 8px rgba(0,0,0,.15)}
a{display:block;text-align:center;color:black;text-decoration:none;margin-top:12px;font-weight:500}
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
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-login mt-2">Đăng nhập</button>
            <a>Chưa có tài khoản liên hệ Admin ngay?</a>
        </form>
    </div>
</div>