<?php
// Admin layout - main wrapper for all admin pages
// Controllers should set $admin_view (e.g., 'customers', 'revenue') 
// and $admin_page_title before requiring this layout

require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default page title
$admin_page_title = $admin_page_title ?? 'Admin Panel';
$admin_view = $admin_view ?? 'index';

// Initialize flash message if not set
$flashMessage = $_SESSION['flash_message'] ?? '';
$flashType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Make flash message available to included views
$GLOBALS['flashMessage'] = $flashMessage;
$GLOBALS['flashType'] = $flashType;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($admin_page_title) ?> - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../assets/images/Gengar.png" type="image/png">
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
</head>
<body>
    <!-- Admin Header Navigation (except for login/create_admin pages) -->
    <?php if ($admin_view !== 'login' && $admin_view !== 'create_admin'): ?>
        <header class="admin-header">
            <h1>Jelly Gengar - Admin <img src="../assets/images/Gengar.png" alt="Gengar Logo" class="header-logo"></h1>
            <nav class="admin-nav">
                <a href="logout" class="logout">Logout</a>
            </nav>
        </header>
        <div class="admin-container">
    <?php endif; ?>

    <!-- Load the specific page content -->
    <?php
    $viewPath = __DIR__ . '/' . $admin_view . '.php';
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        echo '<div class="error">View not found: ' . htmlspecialchars($admin_view) . '</div>';
    }
    ?>

    <!-- Close admin container (if not login/create_admin) -->
    <?php if ($admin_view !== 'login' && $admin_view !== 'create_admin'): ?>
        </div>
    <?php endif; ?>

</body>
</html>
