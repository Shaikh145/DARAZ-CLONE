<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get user's orders
$orders = fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC", [$userId]);

// Get order details for each order
$orderDetails = [];
foreach ($orders as $order) {
    $orderId = $order['order_id'];
    $items = fetchAll("SELECT oi.*, p.name, p.image1, u.username as seller_name 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.product_id 
                      JOIN users u ON oi.seller_id = u.user_id 
                      WHERE oi.order_id = ?", [$orderId]);
    
    $orderDetails[$orderId] = $items;
}

// Get user details
$user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Daraz Clone</title>
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
        
        /* Orders Styles */
        .page-title {
            font-size: 28px;
            margin: 30px 0 20px;
            color: #333;
        }
        
        .orders-container {
            margin-bottom: 40px;
        }
        
        .order-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        
        .order-id {
            font-weight: bold;
            font-size: 16px;
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
        
        .order-body {
            padding: 20px;
        }
        
        .order-items {
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            flex-grow: 1;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-seller {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .item-price {
            font-size: 14px;
            color: #f85606;
        }
        
        .item-quantity {
            font-size: 14px;
            color: #666;
        }
        
        .order-summary {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .order-address {
            flex: 1;
        }
        
        .order-address h4 {
            font-size: 14px;
            margin-bottom: 5px;
            color: #666;
        }
        
        .order-address p {
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .order-total {
            text-align: right;
        }
        
        .order-total h4 {
            font-size: 14px;
            margin-bottom: 5px;
            color: #666;
        }
        
        .order-total .total-amount {
            font-size: 18px;
            font-weight: bold;
            color: #f85606;
        }
        
        .order-total .payment-method {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-state p {
            margin-bottom: 20px;
            font-size: 18px;
            color: #666;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #f85606;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: #e04e05;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 20px 0;
            margin-top: 40px;
            text-align: center;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-date {
                margin-top: 5px;
            }
            
            .order-summary {
                flex-direction: column;
            }
            
            .order-address {
                margin-bottom: 20px;
            }
            
            .order-total {
                text-align: left;
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
                    <a href="account.php">My Account</a>
                    <?php if (isSeller()): ?>
                        <a href="seller_dashboard.php">Seller Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h1 class="page-title">My Orders</h1>
        
        <div class="orders-container">
            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['order_id']; ?></div>
                            <div class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                            <div>
                                <span class="order-status status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-items">
                                <?php foreach ($orderDetails[$order['order_id']] as $item): ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <img src="<?php echo !empty($item['image1']) ? $item['image1'] : 'uploads/placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>">
                                        </div>
                                        <div class="item-details">
                                            <div class="item-name"><?php echo $item['name']; ?></div>
                                            <div class="item-seller">Sold by: <?php echo $item['seller_name']; ?></div>
                                            <div class="item-price">Rs. <?php echo number_format($item['price'], 2); ?></div>
                                            <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-summary">
                                <div class="order-address">
                                    <h4>Shipping Address</h4>
                                    <p><?php echo $order['shipping_address']; ?></p>
                                    <p><?php echo $order['shipping_city']; ?>, <?php echo $order['shipping_country']; ?></p>
                                    <p>Contact: <?php echo $order['contact_phone']; ?></p>
                                </div>
                                
                                <div class="order-total">
                                    <h4>Order Total</h4>
                                    <div class="total-amount">Rs. <?php echo number_format($order['total_amount'], 2); ?></div>
                                    <div class="payment-method">
                                        Payment Method: <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?>
                                    </div>
                                    <div class="payment-status">
                                        Payment Status: <?php echo ucfirst($order['payment_status']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't placed any orders yet.</p>
                    <a href="index.php" class="btn">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Daraz Clone. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
