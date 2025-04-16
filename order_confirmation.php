<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

$userId = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    jsRedirect('index.php');
}

$orderId = $_GET['id'];

// Get order details
$order = fetchOne("SELECT * FROM orders WHERE order_id = ? AND user_id = ?", [$orderId, $userId]);

if (!$order) {
    jsRedirect('index.php');
}

// Get order items
$orderItems = fetchAll("SELECT oi.*, p.name, p.image1, u.username as seller_name 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.product_id 
                      JOIN users u ON oi.seller_id = u.user_id 
                      WHERE oi.order_id = ?", [$orderId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Daraz Clone</title>
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
        
        /* Confirmation Styles */
        .page-title {
            font-size: 28px;
            margin: 30px 0 20px;
            color: #333;
        }
        
        .confirmation-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .confirmation-icon {
            font-size: 60px;
            color: #4caf50;
            margin-bottom: 20px;
        }
        
        .confirmation-message {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .confirmation-details {
            font-size: 16px;
            margin-bottom: 30px;
            color: #666;
        }
        
        .order-number {
            font-weight: bold;
            color: #f85606;
        }
        
        .order-details {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-box {
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        .info-box h4 {
            margin-bottom: 10px;
            color: #555;
            font-size: 16px;
        }
        
        .info-box p {
            margin-bottom: 5px;
            color: #666;
            font-size: 14px;
        }
        
        .order-items {
            margin-bottom: 30px;
        }
        
        .item-grid {
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .item-grid.header {
            font-weight: bold;
            color: #555;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .product-name {
            font-weight: bold;
        }
        
        .product-seller {
            font-size: 12px;
            color: #777;
        }
        
        .order-total {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
        }
        
        .order-total span {
            color: #f85606;
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
        
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
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
        @media (max-width: 768px) {
            .order-info {
                grid-template-columns: 1fr;
            }
            
            .item-grid {
                grid-template-columns: 60px 1fr;
                grid-template-rows: auto auto;
                gap: 10px;
            }
            
            .item-grid.header {
                display: none;
            }
            
            .product-image {
                grid-row: span 2;
            }
            
            .btn-group {
                flex-direction: column;
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
        <div class="confirmation-container">
            <div class="confirmation-icon">✓</div>
            <h1 class="confirmation-message">Thank You for Your Order!</h1>
            <p class="confirmation-details">Your order has been placed successfully. Your order number is <span class="order-number">#<?php echo $orderId; ?></span>.</p>
            <p class="confirmation-details">We'll send you an email confirmation shortly.</p>
            
            <div class="btn-group">
                <a href="index.php" class="btn">Continue Shopping</a>
                <a href="account.php" class="btn">View My Orders</a>
            </div>
        </div>
        
        <div class="order-details">
            <h2 class="section-title">Order Details</h2>
            
            <div class="order-info">
                <div class="info-box">
                    <h4>Order Information</h4>
                    <p><strong>Order Number:</strong> #<?php echo $orderId; ?></p>
                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                    <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                    <p><strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?></p>
                    <p><strong>Order Status:</strong> <?php echo ucfirst($order['order_status']); ?></p>
                </div>
                
                <div class="info-box">
                    <h4>Shipping Address</h4>
                    <p><?php echo $order['shipping_address']; ?></p>
                    <p><?php echo $order['shipping_city']; ?>, <?php echo $order['shipping_country']; ?></p>
                    <p><strong>Contact:</strong> <?php echo $order['contact_phone']; ?></p>
                </div>
                
                <div class="info-box">
                    <h4>Payment Details</h4>
                    <?php if ($order['payment_method'] === 'cash_on_delivery'): ?>
                        <p>You will pay when your order is delivered.</p>
                        <p><strong>Amount:</strong> Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                    <?php elseif ($order['payment_method'] === 'credit_card'): ?>
                        <p>Paid via Credit Card</p>
                        <p><strong>Amount:</strong> Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                    <?php elseif ($order['payment_method'] === 'bank_transfer'): ?>
                        <p>Please transfer the amount to our bank account:</p>
                        <p><strong>Bank:</strong> Example Bank</p>
                        <p><strong>Account:</strong> 1234567890</p>
                        <p><strong>Reference:</strong> Order #<?php echo $orderId; ?></p>
                        <p><strong>Amount:</strong> Rs. <?php echo number_format($order['total_amount'], 2); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="order-items">
                <h3 class="section-title">Order Items</h3>
                
                <div class="item-grid header">
                    <div>Image</div>
                    <div>Product</div>
                    <div>Price</div>
                    <div>Quantity</div>
                </div>
                
                <?php foreach ($orderItems as $item): ?>
                    <div class="item-grid">
                        <div>
                            <img src="<?php echo !empty($item['image1']) ? $item['image1'] : 'uploads/placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>" class="product-image">
                        </div>
                        <div>
                            <div class="product-name"><?php echo $item['name']; ?></div>
                            <div class="product-seller">Sold by: <?php echo $item['seller_name']; ?></div>
                        </div>
                        <div>Rs. <?php echo number_format($item['price'], 2); ?></div>
                        <div><?php echo $item['quantity']; ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-total">
                    Total: <span>Rs. <?php echo number_format($order['total_amount'], 2); ?></span>
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
