<?php
// Create admin account view - content only (layout handles HTML wrapper, data prepared by controller)
$message = $_SESSION['admin_message'] ?? '';
$messageType = $_SESSION['admin_message_type'] ?? '';
unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
?>
<!-- Main login form container for create admin -->
<div class="login-container">
    <div class="login-box">
        <h1>Create Admin User</h1>

        <?php if ($message): ?>
            <div class="<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="create_admin" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" required autofocus placeholder="Enter username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required placeholder="Enter password">
            </div>

            <button type="submit">Create Admin</button>
        </form>

        <p class="mt-1 font-sm text-muted text-center">
            Warning: remove this file after use to keep your site secure.
        </p>

        <a href="login">Back to Login</a>
    </div>
</div>