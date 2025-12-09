<?php
// Customers view - displays customer list (content only - layout handles HTML wrapper)
?>
<div class="page-header">
    <h1>Account Management</h1>
    <a href="index" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<?php if ($flashMessage): ?>
            <div class="flash-message <?= htmlspecialchars($flashType ?? '') ?>">
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <div class="metrics-grid">
            <div class="metric-card primary">
                <h3>Total Accounts</h3>
                <span class="metric-value"><?= number_format($accountStats['total_accounts']) ?></span>
                <small>Active: <?= number_format($accountStats['total_accounts'] - $accountStats['locked_accounts']) ?></small>
            </div>
            <div class="metric-card">
                <h3>Locked Accounts</h3>
                <span class="metric-value"><?= number_format($accountStats['locked_accounts']) ?></span>
                <small>Restricted from login</small>
            </div>
        </div>

        <form class="search-bar" method="get" action="customers">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email">
            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if ($search !== ''): ?>
                <a class="btn" href="customers">Clear Filter</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Created Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="5" class="text-center pd-1">No accounts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($customer['full_name']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($customer['email']) ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($customer['created_at'])) ?></td>
                                <td>
                                    <span class="status-badge <?= !empty($customer['is_locked']) ? 'locked' : 'active' ?>">
                                        <?= !empty($customer['is_locked']) ? 'Locked' : 'Active' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="account-actions">
                                        <a class="btn btn-sm" href="customer?id=<?= (int)$customer['id'] ?>">View</a>
                                        <?php if (!empty($customer['is_locked'])): ?>
                                            <form method="post">
                                                <input type="hidden" name="customer_id" value="<?= (int)$customer['id'] ?>">
                                                <input type="hidden" name="action" value="unlock">
                                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentUrl) ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm">Unlock</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post">
                                                <input type="hidden" name="customer_id" value="<?= (int)$customer['id'] ?>">
                                                <input type="hidden" name="action" value="lock">
                                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentUrl) ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm">Lock</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this account? Login data will be permanently deleted.');">
                                            <input type="hidden" name="customer_id" value="<?= (int)$customer['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentUrl) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
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
                        $link = 'customers?' . http_build_query($params);
                    ?>
                    <?php if ($i === $page): ?>
                        <strong><?= $i ?></strong>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($link) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>