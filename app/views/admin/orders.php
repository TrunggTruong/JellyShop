<?php
// View orders page - displays all orders
?>
<div class="page-header">
  <h1>Orders</h1>
  <a href="index" class="btn btn-secondary">Back to Dashboard</a>
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
      <?php if ($orders_res && $orders_res->num_rows > 0): ?>
        <?php while($r = $orders_res->fetch_assoc()): ?>
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
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="8" style="text-align: center; padding: 20px;">No orders found</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if($orders_pages > 1): ?>
  <div class="pagination">
    <?php for($i = 1; $i <= $orders_pages; $i++): ?>
      <?php if($i == $orders_page): ?>
        <strong><?= $i ?></strong>
      <?php else: ?>
        <a href="orders?orders_page=<?= $i ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>
