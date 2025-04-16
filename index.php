<?php
require_once 'db.php';

// Fetch featured products
$featuredProducts = fetchAll("SELECT p.*, u.username as seller_name, c.name as category_name 
                             FROM products p 
                             JOIN users u ON p.seller_id = u.user_id 
                             JOIN categories c ON p.category_id = c.category_id 
                             WHERE p.featured = 1 AND p.status = 'active' 
                             ORDER BY p.created_at DESC 
                             LIMIT 8");

// Fetch latest products
$latestProducts = fetchAll("SELECT p.*, u.username as seller_name, c.name as category_name 
                           FROM products p 
                           JOIN users u ON p.seller_id = u.user_id 
                           JOIN categories c ON p.category_id = c.category_id 
                           WHERE p.status = 'active' 
                           ORDER BY p.created_at DESC 
                           LIMIT 12");

// Fetch categories
$categories = fetchAll("SELECT * FROM categories WHERE parent_id IS NULL");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daraz Clone - Online Shopping Mall</title>
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
            margin-bottom: 15px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .search-bar {
            flex-grow: 1;
            margin: 0 20px;
            position: relative;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-bar button {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            padding: 0 15px;
            background-color: #f85606;
            border: none;
            border-radius: 0 4px 4px 0;
            color: white;
            cursor: pointer;
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
        
        .cart-icon {
            position: relative;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #fff;
            color: #f85606;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Navigation */
        nav {
            background-color: white;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .nav-categories {
            display: flex;
            justify-content: space-between;
            list-style: none;
        }
        
        .nav-categories li {
            position: relative;
        }
        
        .nav-categories a {
            text-decoration: none;
            color: #333;
            font-size: 14px;
            padding: 10px 15px;
            display: block;
        }
        
        .nav-categories a:hover {
            color: #f85606;
        }
        
        /* Hero Banner */
        .hero-banner {
            height: 300px;
            background-image: linear-gradient(to right, #f85606, #ff9800);
            margin: 20px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }
        
        .hero-banner h1 {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .hero-banner p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .hero-banner .btn {
            background-color: white;
            color: #f85606;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .hero-banner .btn:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
        }
        
        /* Product Grid */
        .section-title {
            font-size: 24px;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f85606;
            color: #333;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .product-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
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
        }
        
        .add-to-cart {
            display: block;
            width: 100%;
            padding: 8px 0;
            background-color: #f85606;
            color: white;
            text-align: center;
            border: none;
            border-radius: 4px;
            margin-top: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .add-to-cart:hover {
            background-color: #e04e05;
        }
        
        /* Categories Section */
        .categories-section {
            margin: 40px 0;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
        }
        
        .category-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            padding: 20px;
            transition: transform 0.3s ease;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
        }
        
        .category-icon {
            font-size: 36px;
            margin-bottom: 10px;
            color: #f85606;
        }
        
        .category-name {
            font-size: 16px;
            color: #333;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 40px 0 20px;
            margin-top: 40px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #f85606;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .footer-column ul li a:hover {
            color: #f85606;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #444;
            font-size: 14px;
            color: #ccc;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .category-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .header-top {
                flex-direction: column;
            }
            
            .search-bar {
                margin: 15px 0;
                width: 100%;
            }
            
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .nav-categories {
                flex-wrap: wrap;
            }
            
            .nav-categories li {
                width: 50%;
            }
        }
        
        @media (max-width: 576px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .nav-categories li {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">Daraz</a>
                <div class="search-bar">
                    <form action="search.php" method="GET">
                        <input type="text" name="query" placeholder="Search in Daraz">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <div class="user-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="account.php">My Account</a>
                        <?php if (isSeller()): ?>
                            <a href="seller_dashboard.php">Seller Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="signup.php">Sign Up</a>
                    <?php endif; ?>
                    <a href="cart.php" class="cart-icon">
                        Cart
                        <?php
                        $cartCount = 0;
                        if (isLoggedIn()) {
                            $userId = $_SESSION['user_id'];
                            $cartResult = fetchOne("SELECT COUNT(ci.cart_item_id) as count 
                                                  FROM cart c 
                                                  JOIN cart_items ci ON c.cart_id = ci.cart_id 
                                                  WHERE c.user_id = ?", [$userId]);
                            if ($cartResult) {
                                $cartCount = $cartResult['count'];
                            }
                        }
                        ?>
                        <span class="cart-count"><?php echo $cartCount; ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <nav>
        <div class="container">
            <ul class="nav-categories">
                <?php foreach ($categories as $category): ?>
                <li>
                    <a href="category.php?id=<?php echo $category['category_id']; ?>">
                        <?php echo $category['name']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="hero-banner">
            <div>
                <h1>Welcome to Daraz Clone</h1>
                <p>Shop the best products at amazing prices!</p>
                <a href="#featured-products" class="btn">Shop Now</a>
            </div>
        </div>
        
        <section id="featured-products">
            <h2 class="section-title">Featured Products</h2>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): ?>
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
                            <span>By <?php echo $product['seller_name']; ?></span>
                        </div>
                        <button class="add-to-cart" onclick="addToCart(<?php echo $product['product_id']; ?>)">Add to Cart</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <section class="categories-section">
            <h2 class="section-title">Shop by Category</h2>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                <a href="category.php?id=<?php echo $category['category_id']; ?>" class="category-card">
                    <div class="category-icon">📦</div>
                    <div class="category-name"><?php echo $category['name']; ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <section>
            <h2 class="section-title">Latest Products</h2>
            <div class="product-grid">
                <?php foreach ($latestProducts as $product): ?>
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
                            <span>By <?php echo $product['seller_name']; ?></span>
                        </div>
                        <button class="add-to-cart" onclick="addToCart(<?php echo $product['product_id']; ?>)">Add to Cart</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">How to Buy</a></li>
                        <li><a href="#">Returns & Refunds</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>About Daraz</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Payment Methods</h3>
                    <ul>
                        <li><a href="#">Cash on Delivery</a></li>
                        <li><a href="#">Credit Card</a></li>
                        <li><a href="#">Bank Transfer</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Follow Us</h3>
                    <ul>
                        <li><a href="#">Facebook</a></li>
                        <li><a href="#">Twitter</a></li>
                        <li><a href="#">Instagram</a></li>
                        <li><a href="#">YouTube</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Daraz Clone. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        function addToCart(productId) {
            <?php if (isLoggedIn()): ?>
            // AJAX request to add product to cart
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                    // Update cart count
                    document.querySelector('.cart-count').textContent = data.cart_count;
                } else {
                    alert('Failed to add product to cart: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the product to cart.');
            });
            <?php else: ?>
            // Redirect to login page if not logged in
            window.location.href = 'login.php';
            <?php endif; ?>
        }
    </script>
</body>
</html>
