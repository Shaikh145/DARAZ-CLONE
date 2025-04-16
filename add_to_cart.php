<?php
require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get user ID and product ID
$userId = $_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Check if product exists and is active
$product = fetchOne("SELECT * FROM products WHERE product_id = ? AND status = 'active'", [$productId]);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found or unavailable']);
    exit;
}

// Get user's cart or create one if it doesn't exist
$cart = fetchOne("SELECT * FROM cart WHERE user_id = ?", [$userId]);

if (!$cart) {
    $cartId = insert('cart', ['user_id' => $userId]);
} else {
    $cartId = $cart['cart_id'];
}

// Check if product is already in cart
$cartItem = fetchOne("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?", [$cartId, $productId]);

if ($cartItem) {
    // Update quantity
    $newQuantity = $cartItem['quantity'] + 1;
    update('cart_items', ['quantity' => $newQuantity], 'cart_item_id = ?', ['cart_item_id' => $cartItem['cart_item_id']]);
} else {
    // Add new item to cart
    insert('cart_items', [
        'cart_id' => $cartId,
        'product_id' => $productId,
        'quantity' => 1
    ]);
}

// Get updated cart count
$cartCount = fetchOne("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = ?", [$cartId])['count'];

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Product added to cart successfully',
    'cart_count' => $cartCount
]);
exit;
?>
