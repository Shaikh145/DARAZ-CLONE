<?php
require_once 'db.php';

// Check if user is logged in and is a seller
if (!isLoggedIn() || !isSeller()) {
    jsRedirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get seller's products
$products = fetchAll("SELECT p.*, c.name as category_name 
                    FROM products p 
                    JOIN categories c ON p.category_id = c.category_id 
                    WHERE p.seller_id = ? 
                    ORDER BY p.created_at DESC", [$userId]);

// Get seller's orders
$orders = fetchAll("SELECT o.*, oi.product_id, oi.quantity, oi.price, p.name as product_name, u.username as buyer_name 
                  FROM orders o 
                  JOIN order_items oi ON o.order_id = oi.order_id 
                  JOIN products p ON oi.product_id = p.product_id 
                  JOIN users u ON o.user_id = u.user_id 
                  WHERE oi.seller_id = ? 
                  ORDER BY o.created_at DESC", [$userId]);

// Group orders by order_id
$groupedOrders = [];
foreach ($orders as $order) {
    $orderId = $order['order_id'];
    if (!isset($groupedOrders[$orderId])) {
        $groupedOrders[$orderId] = [
            'order_id' => $orderId,
            'buyer_name' => $order['buyer_name'],
            'total_amount' => 0,
            'created_at' => $order['created_at'],
            'order_status' => $order['order_status'],
            'payment_status' => $order['payment_status'],
            'items' => []
        ];
    }
    
    $groupedOrders[$orderId]['items'][] = [
        'product_id' => $order['product_id'],
        'product_name' => $order['product_name'],
        'quantity' => $order['quantity'],
        'price' => $order['price']
    ];
    
    $groupedOrders[$orderId]['total_amount'] += $order['quantity'] * $order['price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Daraz Clone</title>
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
        
        /* Dashboard Styles */
        .page-title {
            font-size: 28px;
            margin: 30px 0 20px;
            color: #333;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
        
        .dashboard-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 16px;
            font-weight: bold;
            color: #777;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-btn.active {
            color: #f85606;
            border-bottom-color: #f85606;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Products Tab */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .product-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 180px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-title {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
            height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #f85606;
            margin-bottom: 10px;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #777;
            margin-bottom: 10px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
            flex: 1;
            text-align: center;
        }
        
        .btn-edit {
            background-color: #2196f3;
        }
        
        .btn-edit:hover {
            background-color: #1976d2;
        }
        
        .btn-delete {
            background-color: #f44336;
        }
        
        .btn-delete:hover {
            background-color: #d32f2f;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 0;
            color: #777;
        }
        
        .empty-state p {
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        /* Orders Tab */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .orders-table th, .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background-color: #f9f9f9;
            font-weight: bold;
            color: #555;
        }
        
        .orders-table tr:last-child td {
            border-bottom: none;
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
        
        .order-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
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
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-tabs {
                overflow-x: auto;
                white-space: nowrap;
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
                    <a href="index.php">Back to Store</a>
                    <a href="account.php">My Account</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="dashboard-header">
            <h1 class="page-title">Seller Dashboard</h1>
            <a href="upload_product.php" class="btn">+ Add New Product</a>
        </div>
        
        <div class="dashboard-tabs">
            <button class="tab-btn active" data-tab="products">My Products</button>
            <button class="tab-btn" data-tab="orders">My Orders</button>
        </div>
        
        <div id="products" class="tab-content active">
            <?php if (count($products) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo !empty($product['image1']) ? $product['image1'] : 'uploads/placeholder.jpg'; ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?php echo $product['name']; ?></h3>
                                <div class="product-price">
                                    Rs. <?php echo number_format($product['price'], 2); ?>
                                    <?php if (!empty($product['discount_price'])): ?>
                                    <span style="text-decoration: line-through; color: #999; font-size: 14px; margin-left: 5px;">
                                        Rs. <?php echo number_format($product['discount_price'], 2); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-meta">
                                    <span><?php echo $product['category_name']; ?></span>
                                    <span>Stock: <?php echo $product['quantity']; ?></span>
                                </div>
                                <div class="product-meta">
                                    <span>Status: <?php echo ucfirst($product['status']); ?></span>
                                    <span><?php echo $product['featured'] ? 'Featured' : ''; ?></span>
                                </div>
                                <div class="product-actions">
                                    <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                    <a href="delete_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't added any products yet.</p>
                    <a href="upload_product.php" class="btn">Add Your First Product</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="orders" class="tab-content">
            <?php if (count($groupedOrders) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo $order['buyer_name']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                <td>Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="order-status status-<?php echo $order['order_status']; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="order-status status-<?php echo $order['payment_status']; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                    
                                    <div class="order-details">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <div class="order-item">
                                                <div><?php echo $item['product_name']; ?> × <?php echo $item['quantity']; ?></div>
                                                <div>Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>You don't have any orders yet.</p>
                    <a href="index.php" class="btn">Go to Store</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Daraz Clone. All Rights Reserved.</p>
        </div>
    </footer>
    
    <script>
        // Tab functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                
                // Remove active class from all buttons and contents
                tabBtns.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked button and corresponding content
                btn.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>
