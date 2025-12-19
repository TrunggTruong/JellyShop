<?php
// View order view - displays order details (content only - layout handles HTML wrapper)
?>
<div class="page-header">
    <h1>Order #<?= $order['id'] ?></h1>
</div>

    <div class="order-details">
      <h2>Order Information</h2>
      <div class="order-info">
        <div>
          <strong>Order Code</strong>
          <p class="order-code"><?= htmlspecialchars($order['order_code'] ?? 'N/A') ?></p>
        </div>
        <div>
          <strong>Customer Name</strong>
          <p><?= htmlspecialchars($order['customer_name']) ?></p>
        </div>
        <div>
          <strong>Account</strong>
          <p>
            <?php if(!empty($order['customer_id'])): ?>
              <?= htmlspecialchars($order['registered_name'] ?? '') ?><br>
              <small><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
            <?php else: ?>
              <em>Guest</em>
            <?php endif; ?>
          </p>
        </div>
        <div>
          <strong>Phone</strong>
          <p><?= htmlspecialchars($order['customer_phone'] ?? '') ?></p>
        </div>
        <div>
          <strong>Created At</strong>
          <p><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
        </div>
        <div>
          <strong>Status</strong>
          <p>
            <?php
            if (!empty($order['cancelled'])) {
              echo '<span class="text-error font-bold">Canceled</span>';
            } else if (isset($order['shipped']) && $order['shipped']) {
              echo '✅ Shipped';
            } else {
              echo '⏳ Pending';
            }
            ?>
          </p>
        </div>
      </div>
      <div>
        <strong>Delivery Address</strong>
        <p><?= nl2br(htmlspecialchars($order['customer_address'] ?? '')) ?></p>
      </div>
      <?php
      // Calculate total order value from items
      $grand_total = 0;
      $items->data_seek(0);
      while($it = $items->fetch_assoc()){ 
        $grand_total += $it['quantity'] * $it['price'];
      }
      $items->data_seek(0);
      ?>
      <div class="divider">
        <strong>Total price</strong>
        <p class="order-total mt-half"><?= number_format($grand_total) ?> VND</p>
      </div>
    </div>

    <div class="table-container">
      <h2 class="table-section-header">Order Items</h2>
      <table>
        <thead>
          <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          while($it = $items->fetch_assoc()){ 
          ?>
            <tr>
              <td><?= htmlspecialchars($it['name']) ?></td>
              <td><?= $it['quantity'] ?></td>
              <td><?= number_format($it['price']) ?> VND</td>
            </tr>
          <?php } ?>
          <tr class="bg-hover font-bold">
            <td class="text-right" colspan="2">Total:</td>
            <td><?= number_format($grand_total) ?> VND</td>
          </tr>
        </tbody>
      </table>
    </div>

    <?php if((!isset($order['shipped']) || !$order['shipped']) && empty($order['cancelled'])){ ?>
      <form method="post" action="mark_order_shipped?id=<?= $id ?>" class="mt-2">
        <button type="submit" class="btn">Mark as Shipped</button>
      </form>
    <?php } ?>

    <a href="orders" class="back-link">← Back to View Orders</a>