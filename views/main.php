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
        <?php 
        $role = isset($_SESSION['user']['role']) ? strtolower((string)$_SESSION['user']['role']) : '';
        if (!$isLoginPage && $isLoggedIn) {
            if ($role === 'hdv' && file_exists(PATH_VIEW . 'layouts/partner_sidebar.php')) {
                require_once PATH_VIEW . 'layouts/partner_sidebar.php';
            } else {
                require_once PATH_VIEW . 'layouts/sidebar.php';
            }
        }
        ?>
        <div id="main-content-wrapper">
            <?php if (isset($view)) {
                require_once PATH_VIEW . $view . '.php';
            } ?>
        </div>
    <?php endif; ?>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
 <script>
    // Ngăn reload khi click sidebar links
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        
        const mainContentWrapper = document.getElementById('main-content-wrapper');
        if (!mainContentWrapper) return;
        
        // Intercept tất cả clicks trên sidebar links
        sidebar.addEventListener('click', function(e) {
            const link = e.target.closest('a.nav-link');
            if (!link) return;
            
            // Bỏ qua nút đăng xuất và các link đặc biệt
            if (link.href.includes('logout') || link.href === '' || !link.href.includes('action=')) {
                return;
            }
            
            e.preventDefault();
            
            const url = link.getAttribute('href');
            if (!url) return;
            
            // Update active state
            sidebar.querySelectorAll('.nav-link').forEach(navLink => {
                navLink.classList.remove('active');
            });
            link.classList.add('active');
            
            // Load nội dung bằng AJAX
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    // Parse HTML response
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Tìm main-content trong response
                    const newMainContent = doc.querySelector('#main-content-wrapper main.main-content, #main-content-wrapper .main-content, #main-content-wrapper > main, #main-content-wrapper > *');
                    
                    if (newMainContent) {
                        mainContentWrapper.innerHTML = newMainContent.outerHTML;
                    } else {
                        // Fallback: load toàn bộ wrapper
                        const wrapper = doc.querySelector('#main-content-wrapper');
                        if (wrapper) {
                            mainContentWrapper.innerHTML = wrapper.innerHTML;
                        }
                    }
                    
                    // Update URL mà không reload
                    window.history.pushState({}, '', url);
                    
                    // Reinitialize Bootstrap components nếu cần
                    if (typeof bootstrap !== 'undefined') {
                        // Reinitialize tooltips, modals, etc.
                        const tooltipTriggerList = [].slice.call(mainContentWrapper.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading content:', error);
                    // Fallback: reload page
                    window.location.href = url;
                });
        });
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(e) {
            window.location.reload();
        });
    });
 </script>
</body>

</html>