<?php
// Products management page - displays all products in a table
?>
<div class="page-header">
  <h1>Manage Products</h1>
  <div>
    <a href="add_product_form" class="btn">Add New Product</a>
    <a href="index" class="btn btn-secondary">Back to Dashboard</a>
  </div>
</div>

<?php if (!empty($GLOBALS['flashMessage'] ?? '')): ?>
  <div class="admin-flash <?= htmlspecialchars($GLOBALS['flashType'] ?? '') ?>" role="alert">
    <?= htmlspecialchars($GLOBALS['flashMessage']) ?>
  </div>
<?php endif; ?>

<div class="table-container">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Image</th>
        <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($products_res && $products_res->num_rows > 0): ?>
        <?php while($r = $products_res->fetch_assoc()): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?php if($r['image']) echo '<img src="'.ASSET_PATH.'/'.htmlspecialchars($r['image']).'" width="80" height="80" class="img-cover">'; else echo '-'; ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><?= htmlspecialchars($r['category']) ?></td>
            <td><?= number_format($r['price']) ?> VND</td>
            <td class="actions">
              <a href="edit_product?id=<?= $r['id'] ?>">Edit</a>
              <a href="delete_product?id=<?= $r['id'] ?>" class="delete" onclick="return confirm('Delete this product?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: 20px;">No products found</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if($products_pages > 1): ?>
  <div class="pagination">
    <?php for($i = 1; $i <= $products_pages; $i++): ?>
      <?php if($i == $page): ?>
        <strong><?= $i ?></strong>
      <?php else: ?>
        <a href="products?page=<?= $i ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>
