<?php
// Revenue view - displays revenue statistics (content only - layout handles HTML wrapper)
?>
<div class="page-header">
            <h1>Revenue Management</h1>
            <a href="index" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if ($flashMessage): ?>
            <div class="flash-message <?= htmlspecialchars($flashType ?? '') ?>">
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <div class="metrics-grid">
            <div class="metric-card primary">
                <h3>Total Revenue</h3>
                <span class="metric-value"><?= number_format($revenueStats['total_revenue']) ?> VND</span>
                <small>From all completed orders</small>
            </div>
            <div class="metric-card">
                <h3>Total Orders</h3>
                <span class="metric-value"><?= number_format($revenueStats['total_orders']) ?></span>
                <small>Completed orders only</small>
            </div>
        </div>

        <div class="detail-grid mt-2">
            <div class="detail-card">
                <h3>Daily Revenue (Last 30 Days)</h3>
                <?php if (empty($dailyRevenue)): ?>
                    <p><em>No revenue data available</em></p>
                <?php else: ?>
                    <table class="mt-1 w-full">
                        <thead>
                            <tr>
                                <th class="text-left">Date</th>
                                <th class="text-right">Orders</th>
                                <th class="text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dailyRevenue as $day): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($day['order_date'])) ?></td>
                                    <td class="text-right"><?= number_format((int)$day['order_count']) ?></td>
                                    <td class="text-right"><?= number_format((int)$day['daily_revenue']) ?> VND</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <form class="search-bar" method="get" action="revenue">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by order code, customer name or email">
            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if ($search !== ''): ?>
                <a class="btn" href="revenue">Clear Filter</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order Code</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center pd-1">No orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['order_code'] ?: ('Order #' . $order['id'])) ?></strong></td>
                                <td>
                                    <?php if (!empty($order['customer_id'])): ?>
                                        <strong><?= htmlspecialchars($order['customer_name'] ?? '') ?></strong><br>
                                        <small><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
                                    <?php else: ?>
                                        <em>Guest</em>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td><strong><?= number_format((int)$order['total_price']) ?> VND</strong></td>
                                <td>
                                    <?php
                                        $status = 'Processing';
                                        $statusClass = '';
                                        if (!empty($order['cancelled'])) {
                                            $status = 'Cancelled';
                                            $statusClass = 'locked';
                                        } elseif (!empty($order['shipped'])) {
                                            $status = 'Shipped';
                                            $statusClass = 'active';
                                        }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= $status ?></span>
                                </td>
                                <td>
                                    <a class="btn btn-sm" href="view_order?id=<?= (int)$order['id'] ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                        $params = ['page' => $i];
                        if ($search !== '') {
                            $params['q'] = $search;
                        }
                        $link = 'revenue?' . http_build_query($params);
                    ?>
                    <?php if ($i === $page): ?>
                        <strong><?= $i ?></strong>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($link) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>