<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get user's cart
$cart = fetchOne("SELECT * FROM cart WHERE user_id = ?", [$userId]);

// If cart doesn't exist, create one
if (!$cart) {
    $cartId = insert('cart', ['user_id' => $userId]);
    $cart = ['cart_id' => $cartId];
} else {
    $cartId = $cart['cart_id'];
}

// Get cart items
$cartItems = fetchAll("SELECT ci.*, p.name, p.price, p.image1, p.discount_price, u.username as seller_name 
                     FROM cart_items ci 
                     JOIN products p ON ci.product_id = p.product_id 
                     JOIN users u ON p.seller_id = u.user_id 
                     WHERE ci.cart_id = ?", [$cartId]);

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
    $total += $price * $item['quantity'];
}

// Handle remove item
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $cartItemId = $_GET['remove'];
    delete('cart_items', 'cart_item_id = ?', [$cartItemId]);
    jsRedirect('cart.php');
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cartItemId => $quantity) {
        if ($quantity > 0) {
            update('cart_items', ['quantity' => $quantity], 'cart_item_id = ?', ['cart_item_id' => $cartItemId]);
        } else {
            delete('cart_items', 'cart_item_id = ?', [$cartItemId]);
        }
    }
    jsRedirect('cart.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Daraz Clone</title>
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
        
        /* Cart Styles */
        .page-title {
            font-size: 28px;
            margin: 30px 0 20px;
            color: #333;
        }
        
        .cart-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .cart-header {
            display: grid;
            grid-template-columns: 100px 2fr 1fr 1fr 1fr 50px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            color: #555;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 100px 2fr 1fr 1fr 1fr 50px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .product-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .product-seller {
            font-size: 12px;
            color: #777;
        }
        
        .product-price {
            font-weight: bold;
            color: #f85606;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .item-total {
            font-weight: bold;
        }
        
        .remove-btn {
            color: #e53935;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
        }
        
        .cart-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .update-cart {
            padding: 10px 20px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .update-cart:hover {
            background-color: #e0e0e0;
        }
        
        .cart-total {
            font-size: 18px;
            font-weight: bold;
        }
        
        .cart-total span {
            color: #f85606;
        }
        
        .empty-cart {
            text-align: center;
            padding: 50px 0;
            color: #777;
        }
        
        .empty-cart p {
            margin-bottom: 20px;
            font-size: 18px;
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
        
        .checkout-btn {
            padding: 12px 30px;
            font-size: 16px;
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
            .cart-header {
                display: none;
            }
            
            .cart-item {
                grid-template-columns: 80px 1fr;
                grid-template-rows: auto auto auto auto;
                gap: 10px;
                padding: 15px 0;
            }
            
            .product-image {
                grid-row: span 4;
            }
            
            .product-price, .item-total {
                font-size: 14px;
            }
            
            .remove-btn {
                position: absolute;
                right: 20px;
                top: 15px;
            }
            
            .cart-item {
                position: relative;
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
        <h1 class="page-title">Shopping Cart</h1>
        
        <div class="cart-container">
            <?php if (count($cartItems) > 0): ?>
                <form action="cart.php" method="POST">
                    <div class="cart-header">
                        <div>Image</div>
                        <div>Product</div>
                        <div>Price</div>
                        <div>Quantity</div>
                        <div>Total</div>
                        <div></div>
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <?php 
                        $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
                        $itemTotal = $price * $item['quantity'];
                        ?>
                        <div class="cart-item">
                            <div>
                                <img src="<?php echo !empty($item['image1']) ? $item['image1'] : 'uploads/placeholder.jpg'; ?>" alt="<?php echo $item['name']; ?>" class="product-image">
                            </div>
                            <div>
                                <div class="product-name"><?php echo $item['name']; ?></div>
                                <div class="product-seller">Sold by: <?php echo $item['seller_name']; ?></div>
                            </div>
                            <div class="product-price">Rs. <?php echo number_format($price, 2); ?></div>
                            <div>
                                <input type="number" name="quantity[<?php echo $item['cart_item_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input">
                            </div>
                            <div class="item-total">Rs. <?php echo number_format($itemTotal, 2); ?></div>
                            <div>
                                <a href="cart.php?remove=<?php echo $item['cart_item_id']; ?>" class="remove-btn" onclick="return confirm('Are you sure you want to remove this item?')">×</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-footer">
                        <button type="submit" name="update_cart" class="
                    
                    <div class="cart-footer">
                        <button type="submit" name="update_cart" class="update-cart">Update Cart</button>
                        <div class="cart-total">Total: <span>Rs. <?php echo number_format($total, 2); ?></span></div>
                    </div>
                </form>
                
                <div style="text-align: right; margin-top: 20px;">
                    <a href="checkout.php" class="btn checkout-btn">Proceed to Checkout</a>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <p>Your cart is empty</p>
                    <a href="index.php" class="btn">Continue Shopping</a>
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
