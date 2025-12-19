<?php
// Router for admin and API requests
// Routes requests to appropriate controllers/views in app/

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove base path if exists
$basePath = dirname($scriptName);
if ($basePath !== '/' && $basePath !== '\\') {
    $path = str_replace($basePath, '', $path);
}

// Remove leading slash
$path = ltrim($path, '/');

// Normalize path: remove 'JellyShop' and 'public' prefixes if present
// This handles cases where root .htaccess redirects /JellyShop/admin/ to /JellyShop/public/admin/
$pathParts = explode('/', $path);
$filteredParts = [];
$skipNext = false;
foreach ($pathParts as $i => $part) {
    if ($skipNext) {
        $skipNext = false;
        continue;
    }
    if ($part === 'JellyShop' || $part === 'public') {
        // Skip these parts
        continue;
    }
    $filteredParts[] = $part;
}
$path = implode('/', $filteredParts);

// Split path into segments
$segments = explode('/', $path);

// Route admin requests
if (isset($segments[0]) && $segments[0] === 'admin') {
    $adminFile = isset($segments[1]) && $segments[1] !== '' ? $segments[1] : 'index';
    
    // Remove .php extension if present
    $adminFile = str_replace('.php', '', $adminFile);
    
    // Controllers (handle POST requests or actions, or prepare data for views)
    $adminControllers = [
        'logout', 'delete_product', 'add_product', 'edit_product',
        'mark_order_shipped', 'cancel_order', 'login', 'create_admin',
        'customers', 'customer', 'revenue', 'view_order', 'index', 'products', 'orders'
    ];
    
    // Views (display pages - only login/create_admin for standalone views)
    $adminViews = [
        'login', 'create_admin'
    ];
    
    // Special case: controllers that render views via layout
    $adminControllerViews = [
        'customers', 'customer', 'revenue', 'view_order', 'login', 'create_admin', 'index', 'products', 'orders'
    ];
    
    // If POST request, try controller first
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($adminFile, $adminControllers)) {
        $controllerPath = __DIR__ . '/../app/controllers/admin/' . $adminFile . '.php';
        if (file_exists($controllerPath)) {
            $_SERVER['SCRIPT_NAME'] = '/JellyShop/public/admin/' . $adminFile;
            require_once $controllerPath;
            exit;
        }
    }
    
    // Try controller for GET requests to action endpoints (like logout)
    if (in_array($adminFile, $adminControllers) && !in_array($adminFile, $adminControllerViews)) {
        $controllerPath = __DIR__ . '/../app/controllers/admin/' . $adminFile . '.php';
        if (file_exists($controllerPath)) {
            $_SERVER['SCRIPT_NAME'] = '/JellyShop/public/admin/' . $adminFile;
            require_once $controllerPath;
            exit;
        }
    }
    
    // Try controller-view (controllers that render views)
    if (in_array($adminFile, $adminControllerViews)) {
        $controllerPath = __DIR__ . '/../app/controllers/admin/' . $adminFile . '.php';
        if (file_exists($controllerPath)) {
            $_SERVER['SCRIPT_NAME'] = '/JellyShop/public/admin/' . $adminFile;
            require_once $controllerPath;
            exit;
        }
    }
    
    // Try view
    if (in_array($adminFile, $adminViews)) {
        $viewPath = __DIR__ . '/../app/views/admin/' . $adminFile . '.php';
        if (file_exists($viewPath)) {
            $_SERVER['SCRIPT_NAME'] = '/JellyShop/public/admin/' . $adminFile;
            require_once $viewPath;
            exit;
        }
    }
    
    // 404 for admin
    http_response_code(404);
    echo 'Admin page not found: ' . htmlspecialchars($adminFile) . ' (Path: ' . htmlspecialchars($path) . ', Segments: ' . print_r($segments, true) . ')';
    exit;
}

// Route API requests
if (isset($segments[0]) && $segments[0] === 'api') {
    $apiFile = isset($segments[1]) && $segments[1] !== '' ? $segments[1] : '';
    
    // Remove .php extension if present
    $apiFile = str_replace('.php', '', $apiFile);
    
    // Map API files
    $apiFiles = ['auth', 'customer', 'orders', 'products'];
    
    if (in_array($apiFile, $apiFiles)) {
        $controllerPath = __DIR__ . '/../app/controllers/api/' . $apiFile . '.php';
        if (file_exists($controllerPath)) {
            $_SERVER['SCRIPT_NAME'] = '/JellyShop/public/api/' . $apiFile;
            require_once $controllerPath;
            exit;
        }
    }
    
    // 404 for API
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Default: serve public index
if ($path === '' || $path === 'index.php' || $path === 'public' || $path === 'public/') {
    readfile(__DIR__ . '/../app/views/public/index.html');
    exit;
}

// 404
http_response_code(404);
echo 'Page not found. Path: ' . htmlspecialchars($path) . ' (Original: ' . htmlspecialchars($requestUri) . ')';
