<?php
require_once 'db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    jsRedirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get user's cart
$cart = fetchOne("SELECT * FROM cart WHERE user_id = ?", [$userId]);

// If cart doesn't exist or is empty, redirect to cart page
if (!$cart) {
    jsRedirect('cart.php');
}

$cartId = $cart['cart_id'];

// Get cart items
$cartItems = fetchAll("SELECT ci.*, p.name, p.price, p.image1, p.discount_price, p.seller_id, u.username as seller_name 
                     FROM cart_items ci 
                     JOIN products p ON ci.product_id = p.product_id 
                     JOIN users u ON p.seller_id = u.user_id 
                     WHERE ci.cart_id = ?", [$cartId]);

if (count($cartItems) === 0) {
    jsRedirect('cart.php');
}

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
    $total += $price * $item['quantity'];
}

// Get user details
$user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);

// Process checkout
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = $_POST['shipping_address'] ?? '';
    $shipping_city = $_POST['shipping_city'] ?? '';
    $shipping_country = $_POST['shipping_country'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($shipping_address) || empty($shipping_city) || empty($shipping_country) || empty($contact_phone) || empty($payment_method)) {
        $error = 'Please fill in all required fields';
    } else {
        // Create order
        $orderData = [
            'user_id' => $userId,
            'total_amount' => $total,
            'shipping_address' => $shipping_address,
            'shipping_city' => $shipping_city,
            'shipping_country' => $shipping_country,
            'contact_phone' => $contact_phone,
            'payment_method' => $payment_method,
            'payment_status' => $payment_method === 'cash_on_delivery' ? 'pending' : 'pending',
            'order_status' => 'pending'
        ];
        
        $orderId = insert('orders', $orderData);
        
        if ($orderId) {
            // Add order items
            foreach ($cartItems as $item) {
                $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
                $orderItemData = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'seller_id' => $item['seller_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price
                ];
                
                insert('order_items', $orderItemData);
            }
            
            // Create payment record
            $paymentData = [
                'order_id' => $orderId,
                'amount' => $total,
                'payment_method' => $payment_method,
                'status' => $payment_method === 'cash_on_delivery' ? 'pending' : 'pending'
            ];
            
            insert('payments', $paymentData);
            
            // Clear cart
            delete('cart_items', 'cart_id = ?', [$cartId]);
            
            $success = 'Order placed successfully!';
            
            // Redirect to order confirmation page
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'order_confirmation.php?id=" . $orderId . "';
                }, 2000);
            </script>";
        } else {
            $error = 'Failed to place order. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Daraz Clone</title>
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
        
        /* Checkout Styles */
        .page-title {
            font-size: 28px;
            margin: 30px 0 20px;
            color: #333;
        }
        
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .checkout-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #f85606;
        }
        
        .radio-group {
            margin-bottom: 15px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .radio-option input {
            margin-right: 10px;
        }
        
        .order-summary {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            align-self: start;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }
        
        .summary-total .amount {
            color: #f85606;
        }
        
        .btn-primary {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #f85606;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
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
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                margin-top: 20px;
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
                    <a href="cart.php">Back to Cart</a>
                    <a href="account.php">My Account</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h1 class="page-title">Checkout</h1>
        
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
        
        <div class="checkout-container">
            <div class="checkout-form">
                <form action="checkout.php" method="POST">
                    <div class="form-section">
                        <h3 class="section-title">Shipping Information</h3>
                        
                        <div class="form-group">
                            <label for="shipping_address">Address *</label>
                            <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" required><?php echo $user['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_city">City *</label>
                            <input type="text" id="shipping_city" name="shipping_city" class="form-control" value="<?php echo $user['city'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_country">Country *</label>
                            <input type="text" id="shipping_country" name="shipping_country" class="form-control" value="<?php echo $user['country'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_phone">Contact Phone *</label>
                            <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?php echo $user['phone'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Payment Method</h3>
                        
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="cash_on_delivery" name="payment_method" value="cash_on_delivery" checked>
                                <label for="cash_on_delivery">Cash on Delivery</label>
                            </div>
                            
                            <div class="radio-option">
                                <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                                <label for="credit_card">Credit Card</label>
                            </div>
                            
                            <div class="radio-option">
                                <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                                <label for="bank_transfer">Bank Transfer</label>
                            </div>
                        </div>
                        
                        <div id="credit_card_details" style="display: none;">
                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input type="text" id="card_number" name="card_number" class="form-control">
                            </div>
                            
                            <div style="display: flex; gap: 10px;">
                                <div class="form-group" style="flex: 1;">
                                    <label for="expiry_date">Expiry Date</label>
                                    <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" class="form-control">
                                </div>
                                
                                <div class="form-group" style="flex: 1;">
                                    <label for="cvv">CVV</label>
                                    <input type="text" id="cvv" name="cvv" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div id="bank_transfer_details" style="display: none;">
                            <p style="margin-bottom: 15px; color: #555;">Please transfer the total amount to the following bank account:</p>
                            <p><strong>Bank Name:</strong> Example Bank</p>
                            <p><strong>Account Number:</strong> 1234567890</p>
                            <p><strong>Account Name:</strong> Daraz Clone</p>
                            <p><strong>Reference:</strong> Your Order ID will be provided after checkout</p>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">Place Order</button>
                </form>
            </div>
            
            <div class="order-summary">
                <h3 class="section-title">Order Summary</h3>
                
                <?php foreach ($cartItems as $item): ?>
                    <?php 
                    $price = !empty($item['discount_price']) ? $item['discount_price'] : $item['price'];
                    $itemTotal = $price * $item['quantity'];
                    ?>
                    <div class="summary-item">
                        <div>
                            <?php echo $item['name']; ?> × <?php echo $item['quantity']; ?>
                        </div>
                        <div>Rs. <?php echo number_format($itemTotal, 2); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-item">
                    <div>Subtotal</div>
                    <div>Rs. <?php echo number_format($total, 2); ?></div>
                </div>
                
                <div class="summary-item">
                    <div>Shipping</div>
                    <div>Free</div>
                </div>
                
                <div class="summary-total">
                    <div>Total</div>
                    <div class="amount">Rs. <?php echo number_format($total, 2); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Daraz Clone. All Rights Reserved.</p>
        </div>
    </footer>
    
    <script>
        // Show/hide payment method details
        document.getElementById('credit_card').addEventListener('change', function() {
            document.getElementById('credit_card_details').style.display = 'block';
            document.getElementById('bank_transfer_details').style.display = 'none';
        });
        
        document.getElementById('bank_transfer').addEventListener('change', function() {
            document.getElementById('credit_card_details').style.display = 'none';
            document.getElementById('bank_transfer_details').style.display = 'block';
        });
        
        document.getElementById('cash_on_delivery').addEventListener('change', function() {
            document.getElementById('credit_card_details').style.display = 'none';
            document.getElementById('bank_transfer_details').style.display = 'none';
        });
    </script>
</body>
</html>
