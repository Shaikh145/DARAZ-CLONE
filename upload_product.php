<?php
require_once 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is a seller
if (!isLoggedIn() || !isSeller()) {
    jsRedirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get all categories
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

$error = '';
$success = '';
$uploadErrors = [];

// Create uploads directory if it doesn't exist
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

if (!is_dir('uploads/products')) {
    mkdir('uploads/products', 0777, true);
}

// Make sure directories are writable
if (!is_writable('uploads')) {
    chmod('uploads', 0777);
}

if (!is_writable('uploads/products')) {
    chmod('uploads/products', 0777);
}

// Simplified image upload function
function simple_upload_image($file) {
    // Generate a unique filename
    $filename = 'uploads/products/' . uniqid() . '_' . basename($file['name']);
    
    // Try to upload the file
    if (move_uploaded_file($file['tmp_name'], $filename)) {
        return $filename;
    }
    
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $discount_price = $_POST['discount_price'] ?? null;
    $quantity = $_POST['quantity'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name) || empty($description) || empty($price) || empty($quantity) || empty($category_id)) {
        $error = 'Please fill in all required fields';
    } else {
        // Upload images
        $image1 = null;
        $image2 = null;
        $image3 = null;
        $image4 = null;
        
        // Process main image
        if (isset($_FILES['image1']) && $_FILES['image1']['error'] === 0) {
            $image1 = simple_upload_image($_FILES['image1']);
            if (!$image1) {
                $uploadErrors[] = 'Failed to upload main image';
            }
        } else if ($_FILES['image1']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadErrors[] = 'Main image upload error: ' . $_FILES['image1']['error'];
        }
        
        // Process additional images
        if (isset($_FILES['image2']) && $_FILES['image2']['error'] === 0) {
            $image2 = simple_upload_image($_FILES['image2']);
        }
        
        if (isset($_FILES['image3']) && $_FILES['image3']['error'] === 0) {
            $image3 = simple_upload_image($_FILES['image3']);
        }
        
        if (isset($_FILES['image4']) && $_FILES['image4']['error'] === 0) {
            $image4 = simple_upload_image($_FILES['image4']);
        }
        
        // If there are no upload errors, proceed with product creation
        if (empty($uploadErrors)) {
            // Insert product
            $productData = [
                'seller_id' => $userId,
                'category_id' => $category_id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'discount_price' => !empty($discount_price) ? $discount_price : null,
                'quantity' => $quantity,
                'image1' => $image1,
                'image2' => $image2,
                'image3' => $image3,
                'image4' => $image4,
                'featured' => $featured,
                'status' => $status
            ];
            
            $productId = insert('products', $productData);
            
            if ($productId) {
                $success = 'Product added successfully!';
                
                // Redirect to seller dashboard after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'seller_dashboard.php';
                    }, 2000);
                </script>";
            } else {
                $error = 'Failed to add product. Please try again.';
            }
        } else {
            $error = 'Image upload failed: ' . implode(', ', $uploadErrors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Daraz Clone</title>
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
        
        /* Form Styles */
        .page-title {
            font-size: 28px;
            margin: 30px 0 20px;
            color: #333;
        }
        
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 40px;
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
            margin-bottom: 20px;
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
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input {
            margin-right: 10px;
        }
        
        .image-preview {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .preview-box {
            width: 100px;
            height: 100px;
            border: 1px dashed #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .preview-box img {
            max-width: 100%;
            max-height: 100%;
            display: none;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 12px 25px;
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
        
        .btn-secondary {
            display: inline-block;
            padding: 12px 25px;
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
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
        @media (max-width: 768px) {
            .image-preview {
                flex-wrap: wrap;
            }
            
            .preview-box {
                width: calc(50% - 10px);
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
                    <a href="seller_dashboard.php">Back to Dashboard</a>
                    <a href="index.php">Back to Store</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <h1 class="page-title">Add New Product</h1>
        
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
        
        <div class="form-container">
            <form action="upload_product.php" method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">Pricing & Inventory</h3>
                    
                    <div class="form-group">
                        <label for="price">Price (Rs.) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_price">Discount Price (Rs.)</label>
                        <input type="number" id="discount_price" name="discount_price" step="0.01" min="0" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="0" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="featured" name="featured">
                        <label for="featured">Mark as Featured Product</label>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">Product Images</h3>
                    
                    <div class="form-group">
                        <label for="image1">Main Image *</label>
                        <input type="file" id="image1" name="image1" class="form-control" accept="image/*" required>
                        <div class="preview-box" id="preview1">
                            <img id="preview-img1">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image2">Additional Image 1</label>
                        <input type="file" id="image2" name="image2" class="form-control" accept="image/*">
                        <div class="preview-box" id="preview2">
                            <img id="preview-img2">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image3">Additional Image 2</label>
                        <input type="file" id="image3" name="image3" class="form-control" accept="image/*">
                        <div class="preview-box" id="preview3">
                            <img id="preview-img3">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image4">Additional Image 3</label>
                        <input type="file" id="image4" name="image4" class="form-control" accept="image/*">
                        <div class="preview-box" id="preview4">
                            <img id="preview-img4">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="seller_dashboard.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Daraz Clone. All Rights Reserved.</p>
        </div>
    </footer>
    
    <script>
        // Image preview functionality
        function previewImage(input, imgId, previewId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.getElementById(imgId);
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            }
        }
        
        document.getElementById('image1').addEventListener('change', function() {
            previewImage(this, 'preview-img1', 'preview1');
        });
        
        document.getElementById('image2').addEventListener('change', function() {
            previewImage(this, 'preview-img2', 'preview2');
        });
        
        document.getElementById('image3').addEventListener('change', function() {
            previewImage(this, 'preview-img3', 'preview3');
        });
        
        document.getElementById('image4').addEventListener('change', function() {
            previewImage(this, 'preview-img4', 'preview4');
        });
    </script>
</body>
</html>
