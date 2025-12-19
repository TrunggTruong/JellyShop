<?php
// Logout controller - destroys session and redirects to login
require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: /JellyShop/public/admin/login');
exit;

