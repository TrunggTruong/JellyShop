<?php
// Customer detail view - displays single customer (content only - layout handles HTML wrapper)
?>
<div class="page-header">
    <h1><?= htmlspecialchars($customer['full_name']) ?></h1>
    <a href="customers" class="btn btn-secondary">← Account List</a>
</div>

        <?php if ($flashMessage): ?>
            <div class="flash-message <?= htmlspecialchars($flashType ?? '') ?>">
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <div class="customer-details">
            <div class="detail-section">
                <h2>Account Information</h2>
                <div class="detail-grid">
                    <div>
                        <strong>Full Name</strong>
                        <p><?= htmlspecialchars($customer['full_name']) ?></p>
                    </div>
                    <div>
                        <strong>Email</strong>
                        <p><?= htmlspecialchars($customer['email']) ?></p>
                    </div>
                    <div>
                        <strong>Account Created</strong>
                        <p><?= date('d/m/Y H:i', strtotime($customer['created_at'])) ?></p>
                    </div>
                    <div>
                        <strong>Status</strong>
                        <p>
                            <span class="status-badge <?= !empty($customer['is_locked']) ? 'locked' : 'active' ?>">
                                <?= !empty($customer['is_locked']) ? 'Locked' : 'Active' ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h2>Order Statistics</h2>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <h3>Total Orders</h3>
                        <span class="metric-value"><?= number_format($customerStats['total_orders']) ?></span>
                    </div>
                    <div class="metric-card">
                        <h3>Total Spent</h3>
                        <span class="metric-value"><?= number_format($customerStats['total_spent']) ?> VND</span>
                    </div>
                    <div class="metric-card">
                        <h3>Average Order</h3>
                        <span class="metric-value"><?= number_format($customerStats['avg_order']) ?> VND</span>
                    </div>
                    <div class="metric-card">
                        <h3>Last Order</h3>
                        <span class="metric-value">
                            <?= $customerStats['last_order'] ? date('d/m/Y', strtotime($customerStats['last_order'])) : 'N/A' ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h2>Order History</h2>
                <?php if (empty($orders)): ?>
                    <p class="text-center pd-1 text-muted">No orders found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order Code</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_code'] ?? 'N/A') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                        <td><?= number_format($order['total_price']) ?> VND</td>
                                        <td>
                                            <?php
                                            if (!empty($order['cancelled'])) {
                                                echo '<span class="text-error">Cancelled</span>';
                                            } else if (!empty($order['shipped'])) {
                                                echo '<span class="text-success">Shipped</span>';
                                            } else {
                                                echo '<span class="text-warning">Pending</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="view_order?id=<?= (int)$order['id'] ?>">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-section">
                <h2>Account Actions</h2>
                <div class="account-actions">
                    <form method="post">
                        <input type="hidden" name="customer_id" value="<?= (int)$customer['id'] ?>">
                        <input type="hidden" name="redirect" value="customers">
                        <?php if (!empty($customer['is_locked'])): ?>
                            <input type="hidden" name="action" value="unlock">
                            <button type="submit" class="btn btn-secondary">Unlock Account</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="lock">
                            <button type="submit" class="btn btn-secondary">Lock Account</button>
                        <?php endif; ?>
                    </form>
                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this account? All data will be permanently deleted.');">
                        <input type="hidden" name="customer_id" value="<?= (int)$customer['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="redirect" value="customers">
                        <button type="submit" class="btn btn-danger">Delete Account</button>
                    </form>
                </div>
            </div>
        </div>