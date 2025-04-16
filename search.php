<?php
require_once 'db.php';

// Get search query
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Get all categories for navigation
$categories = fetchAll("SELECT * FROM categories WHERE parent_id IS NULL");

// Search products if query is not empty
$products = [];
if (!empty($query)) {
    $searchTerm = "%$query%";
    $products = fetchAll("SELECT p.*, c.name as category_name, u.username as seller_name 
                        FROM products p 
                        JOIN categories c ON p.category_id = c.category_id 
                        JOIN users u ON p.seller_id = u.user_id 
                        WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.status = 'active' 
                        ORDER BY p.created_at DESC", [$searchTerm, $searchTerm]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Daraz Clone</title>
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
        
        /* Search Results Styles */
        .search-header {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .search-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .search-count {
            color: #666;
        }
        
        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1
