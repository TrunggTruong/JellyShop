<?php
// Admin dashboard - displays products and orders (content only - layout handles HTML wrapper, data prepared by controller)
?>
  <div class="dashboard-buttons">
      <button class="dashboard-btn" onclick="showSection('products', this)">Manage Products</button>
      <button class="dashboard-btn" onclick="showSection('add-product', this)">Add Product</button>
      <button class="dashboard-btn" onclick="showSection('orders', this)">View Orders</button>
    </div>

    <!-- Products Section -->
    <div id="products-section" class="content-section">
      <div class="section-header">
        <h2>Products</h2>
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
            <?php while($r=$products_res->fetch_assoc()){ ?>
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
            <?php } ?>
          </tbody>
        </table>
      </div>

      <?php if($products_pages>1){ ?>
        <div class="pagination">
          <?php for($i=1;$i<=$products_pages;$i++){ ?>
            <?php if($i==$page){ ?>
              <strong><?= $i ?></strong>
            <?php } else { ?>
              <a href="?page=<?= $i ?>#products-section"><?= $i ?></a>
            <?php } ?>
          <?php } ?>
        </div>
      <?php } ?>
    </div>

    <!-- Add Product Section -->
    <div id="add-product-section" class="content-section">
      <div class="section-header">
        <h2>Add Product</h2>
      </div>

      <div class="form-container">
        <form method="post" action="add_product" enctype="multipart/form-data" onsubmit="return handleAddProduct(event)">
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
            <button type="button" class="btn btn-secondary" onclick="showSection('products', document.querySelectorAll('.dashboard-btn')[0])">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Orders Section -->
    <div id="orders-section" class="content-section">
      <div class="section-header">
        <h2>Orders</h2>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Order Code</th>
              <th>Customer</th>
              <th>Account</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r=$orders_res->fetch_assoc()){ ?>
              <tr>
                <td><?= $r['id'] ?></td>
                <td><strong class="text-primary"><?= htmlspecialchars($r['order_code'] ?? 'N/A') ?></strong></td>
                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                <td>
                  <?php if(!empty($r['customer_id'])): ?>
                    <strong><?= htmlspecialchars($r['registered_name'] ?? '') ?></strong><br>
                    <small><?= htmlspecialchars($r['customer_email'] ?? '') ?></small>
                  <?php else: ?>
                    <em>Guest</em>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['customer_phone'] ?? '') ?></td>
                <td><?= nl2br(htmlspecialchars($r['customer_address'] ?? '')) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                <td class="actions">
                  <a href="view_order?id=<?= $r['id'] ?>">View</a>
                  <?php if(empty($r['cancelled']) && empty($r['shipped'])): ?>
                  <a href="cancel_order?id=<?= $r['id'] ?>&orders_page=<?= $orders_page ?>" class="delete" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</a>
                  <?php endif; ?>
                </td>

              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <?php if($orders_pages>1){ ?>
        <div class="pagination">
          <?php for($i=1;$i<=$orders_pages;$i++){ ?>
            <?php if($i==$orders_page){ ?>
              <strong><?= $i ?></strong>
            <?php } else { ?>
              <a href="?orders_page=<?= $i ?>#orders-section"><?= $i ?></a>
            <?php } ?>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  </div>

  <script>
    // Show/hide dashboard sections and update active button state
    function showSection(sectionName, clickedButton) {
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
      });
      
      document.querySelectorAll('.dashboard-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      
      const section = document.getElementById(sectionName + '-section');
      if (section) {
        section.classList.add('active');
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      
      if (clickedButton) {
        clickedButton.classList.add('active');
      }
    }
    
    // Handle add product form submission
    function handleAddProduct(event) {
      return true;
    }
    
    // Initialize dashboard - show section based on URL hash or default to products
    window.addEventListener('DOMContentLoaded', function() {
      const hash = window.location.hash;
      if (hash) {
        const sectionName = hash.replace('#', '').replace('-section', '');
        let targetButton = null;
        document.querySelectorAll('.dashboard-btn').forEach(btn => {
          if (btn.textContent.includes('Products') && sectionName === 'products') {
            targetButton = btn;
          } else if (btn.textContent.includes('Add Product') && sectionName === 'add-product') {
            targetButton = btn;
          } else if (btn.textContent.includes('Orders') && sectionName === 'orders') {
            targetButton = btn;
          }
        });
        showSection(sectionName, targetButton);
      } else {
        const firstButton = document.querySelectorAll('.dashboard-btn')[0];
        showSection('products', firstButton);
      }
    });
  </script>