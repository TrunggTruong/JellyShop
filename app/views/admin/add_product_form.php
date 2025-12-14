<?php
// Add product form page - displays form for adding new products
?>
<div class="page-header">
  <h1>Add New Product</h1>
  <a href="products" class="btn btn-secondary">Back to Products</a>
</div>

<div class="form-container">
  <form method="post" action="add_product" enctype="multipart/form-data">
    <div class="form-group">
      <label>Name</label>
      <input name="name" required>
    </div>
    <div class="form-group">
      <label>Category</label>
      <select name="category">
        <?php foreach($categoryOptions as $option): ?>
          <option value="<?= htmlspecialchars($option) ?>">
            <?= htmlspecialchars($option) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Price (VND)</label>
      <input name="price" type="number" required min="0">
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="4"></textarea>
    </div>
    <div class="form-group">
      <label>Image (jpg/png/svg)</label>
      <input name="image" type="file" accept="image/*">
    </div>
    <div class="form-actions">
      <button type="submit" class="btn">Add Product</button>
      <a href="products" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
