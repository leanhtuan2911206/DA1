<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $title ?? 'Home' ?></title>
    

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">

   
</head>

<body>
    <?php 
    // Dùng session_status() để kiểm tra nếu session chưa hoạt động, thì mới bắt đầu
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $isLoggedIn = isset($_SESSION['user']);
    ?>
    <?php 
    $currentAction = $_GET['action'] ?? '/';
    $isLoginPage = ($currentAction === 'login');
    ?>
    
    <?php if (empty($hideNavbar)) : ?>
        <nav class="navbar navbar-expand-xxl bg-light justify-content-center">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-uppercase" href="<?= BASE_URL ?>?action=admin"><b>Admin</b></a>
                </li>
            </ul>
        </nav>

        <div class="container">
            <div class="row">
                <?php
                if (isset($view)) {
                    require_once PATH_VIEW . $view . '.php';
                }
                ?>
            </div>
        </div>
    <?php else: ?>
        <?php if (!$isLoginPage && $isLoggedIn) : ?>
            <?php require_once PATH_VIEW . 'layouts/sidebar.php'; ?>
        <?php endif; ?>
        <?php
        if (isset($view)) {
            require_once PATH_VIEW . $view . '.php';
        }
        ?>
    <?php endif; ?>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>