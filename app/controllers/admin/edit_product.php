<?php
// Edit product controller - handles product update form submission and display
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index#products-section');
    exit;
}

$categoryOptions = get_categories();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    $desc = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? $categoryOptions[0];
    
    if (!in_array($category, $categoryOptions, true)) {
        $category = $categoryOptions[0];
    }
    
    // Keep existing image unless new one is uploaded
    $image_path = $_POST['old_image'] ?? '';
    
    // Handle new image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $new_image = upload_product_image();
        if ($new_image) {
            $image_path = $new_image;
        }
    }
    
    // Update product in database
    if ($db) {
      $stmt = $db->prepare('UPDATE products SET name=?,description=?,price=?,image=?,category=? WHERE id=?');
      if ($stmt) {
        $stmt->bind_param('ssissi', $name, $desc, $price, $image_path, $category, $id);
        if ($stmt->execute()) {
          if ($stmt->affected_rows > 0) {
                    $_SESSION['admin_message'] = 'Product updated successfully.';
                    $_SESSION['admin_message_type'] = 'success';
                    $_SESSION['flash_message'] = 'Product updated successfully.';
                    $_SESSION['flash_type'] = 'success';
          } else {
                    $_SESSION['admin_message'] = 'No changes were made to the product.';
                    $_SESSION['admin_message_type'] = 'info';
                    $_SESSION['flash_message'] = 'No changes were made to the product.';
                    $_SESSION['flash_type'] = 'info';
          }
        } else {
                $_SESSION['admin_message'] = 'Failed to update product.';
                $_SESSION['admin_message_type'] = 'error';
                $_SESSION['flash_message'] = 'Failed to update product.';
                $_SESSION['flash_type'] = 'error';
        }
        $stmt->close();
      } else {
        $_SESSION['admin_message'] = 'Database error when preparing statement.';
        $_SESSION['admin_message_type'] = 'error';
        $_SESSION['flash_message'] = 'Database error when preparing statement.';
        $_SESSION['flash_type'] = 'error';
      }
    } else {
      $_SESSION['admin_message'] = 'Database connection failed.';
      $_SESSION['admin_message_type'] = 'error';
      $_SESSION['flash_message'] = 'Database connection failed.';
      $_SESSION['flash_type'] = 'error';
    }

    header('Location: products');
    exit;
}

// Fetch product data for form
$stmt = $db->prepare('SELECT * FROM products WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$r = $result->fetch_assoc();
$stmt->close();

if (!$r) {
    header('Location: index#products-section');
    exit;
}

$selectedCategory = $r['category'] ?? $categoryOptions[0];
if (!in_array($selectedCategory, $categoryOptions, true) && $selectedCategory !== '') {
    $categoryOptions[] = $selectedCategory;
}

// Render view (we'll create a simple inline view since add_product was deleted)
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset='utf-8'>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Product - Admin</title>
  <link rel="icon" href="../assets/images/Gengar.png" type="image/png">
  <link rel="shortcut icon" href="../assets/images/Gengar.png" type="image/png">
  <link rel="stylesheet" href="../admin/assets/css/admin.css">
</head>
<body>
  <header class="admin-header">
    <h1>Rau Câu Shop - Admin <img src="../assets/images/Gengar.png" alt="Gengar Logo" class="header-logo"></h1>
    <nav class="admin-nav">
      <a href="index">Dashboard</a>
      <a href="logout" class="logout">Logout</a>
    </nav>
  </header>

  <div class="admin-container">
    <div class="page-header">
      <h1>Edit Product</h1>
    </div>

    <div class="form-container">
      <form method="post" action="edit_product?id=<?= $id ?>" enctype="multipart/form-data">
        <input type="hidden" name="old_image" value="<?= htmlspecialchars($r['image']) ?>">
        <div class="form-group">
          <label>Name</label>
          <input name="name" value="<?= htmlspecialchars($r['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category">
            <?php foreach($categoryOptions as $option): ?>
              <option value="<?= htmlspecialchars($option) ?>" <?= $selectedCategory === $option ? 'selected' : '' ?>>
                <?= htmlspecialchars($option) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Price (VND)</label>
          <input name="price" type="number" value="<?= htmlspecialchars($r['price']) ?>" required min="0">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="4"><?= htmlspecialchars($r['description']) ?></textarea>
        </div>
        <div class="form-group">
          <label>Current Image</label>
          <?php if($r['image']) echo '<img src="'.ASSET_PATH.'/'.htmlspecialchars($r['image']).'" width="200">'; else echo '<p>No image</p>'; ?>
        </div>
        <div class="form-group">
          <label>Replace Image (jpg/png/svg)</label>
          <input name="image" type="file" accept="image/*">
        </div>
        <div class="form-actions">
          <button type="submit" class="btn">Save Changes</button>
          <a href="index#products-section" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
