<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <header class="my-4">
        <?php $__logoUrl = BASE_ASSETS_UPLOADS . 'logo.png'; $__logoFs = rtrim(PATH_ASSETS_UPLOADS, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logo.png'; ?>
        <div class="text-center">
            <?php if (file_exists($__logoFs)): ?>
                <img src="<?= $__logoUrl ?>" alt="Travel Company" style="max-height:80px">
            <?php else: ?>
                <h1 class="text-center">Trang quản trị</h1>
            <?php endif; ?>
        </div>
    </header>
</div>