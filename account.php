<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get user details
$user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);

// Get order count
$orderCount = fetchOne("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$userId])['count'];

// Get recent orders
$recentOrders = fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3", [$userId]);

// Process form submission for profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    
    if (empty($full_name)) {
        $error = 'Full name is required';
    } else {
        // Update user profile
        $userData = [
            'full_name' => $full_name,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'country' => $country
        ];
        
        $whereParams = ['user_id' => $userId];
        $updated = updateSimple('users', $userData, $userId);
        
        // Add debugging
        if (!$updated) {
            error_log("Failed to update user ID: $userId with data: " . print_r($userData, true));
            $error = 'Failed to update profile. Please check error logs.';
        } else {
            $success = 'Profile updated successfully';
            // Refresh user data
            $user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Daraz Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: #f85606;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
        }
        
        .user-actions a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-size: 14px;
        }
        
        /* Account Styles */
        .page-title {
            font-size: 28px;
            margin: 30px 0 20px;
            color: #333;
        }
        
        .account-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .account-sidebar {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .user-info {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #f85606;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            margin: 0 auto 15px;
        }
        
        .user-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 14px;
            color: #666;
        }
        
        .account-menu {
            list-style: none;
        }
        
        .account-menu li {
            margin-bottom: 10px;
        }
        
        .account-menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .account-menu a:hover, .account-menu a.active {
            background-color: #f5f5f5;
            color: #f85606;
        }
        
        .account-menu a.active {
            font-weight: bold;
        }
        
        .account-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .section-title {
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #f85606;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .recent-orders {
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-id {
            font-weight: bold;
        }
        
        .order-date {
            color: #666;
            font-size: 14px;
        }
        
        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .status-processing {
            background-color: #e1f5fe;
            color: #0288d1;
        }
        
        .status-shipped {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .status-delivered {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .order-amount {
            font-weight: bold;
            color: #f85606;
        }
        
        .view-all {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #f85606;
            text-decoration: none;
            font-weight: bold;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #f85606;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 10px 20px;
            background-color: #f85606;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #e04e05;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 20px 0;
            text-align: center;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .account-container {
                grid-template-columns: 1fr;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .profile-form {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">Daraz</a>
                <div class="user-actions">
                    <a href="index.php">Continue Shopping</a>
                    <a href="cart.php">My Cart</a>
                    <?php if (isSeller()): ?>
                        <a href="seller_dashboard.php">Seller Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h1 class="page-title">My Account</h1>
        
        <div class="account-container">
            <div class="account-sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-name"><?php echo $user['full_name']; ?></div>
                    <div class="user-email"><?php echo $user['email']; ?></div>
                </div>
                
                <ul class="account-menu">
                    <li><a href="#" class="active">Dashboard</a></li>
                    <li><a href="my_orders.php">My Orders</a></li>
                    <li><a href="#">Profile Settings</a></li>
                    <li><a href="#">Change Password</a></li>
                    <?php if (isSeller()): ?>
                        <li><a href="seller_dashboard.php">Seller Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="#">Become a Seller</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
            
            <div class="account-content">
                <h2 class="section-title">Dashboard</h2>
                
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $orderCount; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            $pendingOrders = fetchOne("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND order_status IN ('pending', 'processing')", [$userId])['count'];
                            echo $pendingOrders;
                            ?>
                        </div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php
                            $deliveredOrders = fetchOne("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND order_status = 'delivered'", [$userId])['count'];
                            echo $deliveredOrders;
                            ?>
                        </div>
                        <div class="stat-label">Delivered Orders</div>
                    </div>
                </div>
                
                <div class="recent-orders">
                    <h3 class="section-title">Recent Orders</h3>
                    
                    <?php if (count($recentOrders) > 0): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="order-item">
                                <div class="order-id">Order #<?php echo $order['order_id']; ?></div>
                                <div class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                <div>
                                    <span class="order-status status-<?php echo $order['order_status']; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </div>
                                <div class="order-amount">Rs. <?php echo number_format($order['total_amount'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <a href="my_orders.php" class="view-all">View All Orders</a>
                    <?php else: ?>
                        <p>You haven't placed any orders yet.</p>
                    <?php endif; ?>
                </div>
                
                <div class="profile-settings">
                    <h3 class="section-title">Profile Settings</h3>
                    
                    <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <?php echo $success; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="account.php" method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="3"><?php echo $user['address']; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" class="form-control" value="<?php echo $user['city']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" class="form-control" value="<?php echo $user['country']; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <button type="submit" class="btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Daraz Clone. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
