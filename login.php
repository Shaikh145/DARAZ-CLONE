<?php
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check if user exists
        $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_seller'] = $user['is_seller'];
            
            // Redirect to home page
            jsRedirect('index.php');
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Daraz Clone</title>
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
        
        /* Login Form */
        .login-container {
            max-width: 500px;
            margin: 50px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .form-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
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
        }
        
        .btn-primary:hover {
            background-color: #e04e05;
        }
        
        .form-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .form-footer a {
            color: #f85606;
            text-decoration: none;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
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
            margin-top: 40px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">Daraz</a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="login-container">
            <h2 class="form-title">Login to Your Account</h2>
            
            <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn-primary">Login</button>
                
                <div class="form-footer">
                    Don't have an account? <a href="signup.php">Sign Up</a>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Daraz Clone. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
