<?php
// Admin login view - displays login form (content only - layout handles HTML wrapper)
$errorMessage = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!-- Main login form container -->
<div class="login-container">
    <div class="login-box">
        <!-- Page title -->
        <h1>Admin Login</h1>
        
        <!-- Show error message if there is one -->
        <?php if ($errorMessage): ?>
            <div class="error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- Login form -->
        <form method="post" action="login" autocomplete="off">
            <!-- Username field -->
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    id="username"
                    name="username" 
                    type="text"
                    required 
                    autofocus
                    placeholder="Enter your username"
                >
            </div>
            
            <!-- Password field -->
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    id="password"
                    name="password" 
                    type="password" 
                    required
                    placeholder="Enter your password"
                >
            </div>
            
            <!-- Submit button -->
            <button type="submit">Login to Admin Panel</button>
        </form>
        
        <!-- Link to create new admin account -->
        <div class="create-account">
            <a href="create_admin">Create New Admin Account</a>
        </div>
    </div>
</div>
