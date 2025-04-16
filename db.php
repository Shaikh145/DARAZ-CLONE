<?php
// Database connection parameters
$host = "localhost";
$dbname = "dbqk1vcxwwgjyz";
$username = "uklz9ew3hrop3";
$password = "zyrbspyjlzjb";

// Create connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set character set to utf8mb4
    $conn->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions for database operations
function executeQuery($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

function insert($table, $data) {
    global $conn;
    
    $columns = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        return $conn->lastInsertId();
    } catch(PDOException $e) {
        die("Insert failed: " . $e->getMessage());
    }
}

function update($table, $data, $where, $whereParams = []) {
    global $conn;
    
    $setParts = [];
    $params = [];
    
    // Build SET clause and parameters
    foreach($data as $column => $value) {
        $setParts[] = "$column = :set_$column";
        $params["set_$column"] = $value;
    }
    $setClause = implode(", ", $setParts);
    
    // Add WHERE parameters with different prefixes to avoid conflicts
    foreach($whereParams as $key => $value) {
        $params["where_$key"] = $value;
    }
    
    // Replace ? placeholders with named parameters
    $whereClause = $where;
    if (strpos($where, '?') !== false) {
        $whereValues = array_values($whereParams);
        $whereCount = 0;
        $whereClause = preg_replace_callback('/\?/', function($matches) use (&$whereCount, $whereValues) {
            $param = ":where_" . array_keys($whereValues)[$whereCount];
            $whereCount++;
            return $param;
        }, $where);
    }
    
    $sql = "UPDATE $table SET $setClause WHERE $whereClause";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch(PDOException $e) {
        error_log("Update failed: " . $e->getMessage());
        return false;
    }
}

function updateSimple($table, $data, $userId) {
    global $conn;
    
    $setParts = [];
    foreach(array_keys($data) as $column) {
        $setParts[] = "$column = :$column";
    }
    $setClause = implode(", ", $setParts);
    
    $sql = "UPDATE $table SET $setClause WHERE user_id = :user_id";
    
    try {
        $stmt = $conn->prepare($sql);
        // Add user_id to data array
        $data['user_id'] = $userId;
        $stmt->execute($data);
        return $stmt->rowCount();
    } catch(PDOException $e) {
        error_log("Update failed: " . $e->getMessage());
        return false;
    }
}

function delete($table, $where, $params = []) {
    global $conn;
    
    $sql = "DELETE FROM $table WHERE $where";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch(PDOException $e) {
        die("Delete failed: " . $e->getMessage());
    }
}

// Session management
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is a seller
function isSeller() {
    return isLoggedIn() && isset($_SESSION['is_seller']) && $_SESSION['is_seller'] == true;
}

// Function to redirect with JavaScript
function jsRedirect($url) {
    echo "<script>window.location.href = '$url';</script>";
    exit;
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to generate a random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Function to upload image
function uploadImage($file, $directory = 'uploads/') {
    // Check if directory exists, if not create it
    if (!file_exists($directory)) {
        if (!mkdir($directory, 0777, true)) {
            return false; // Failed to create directory
        }
    }
    
    // Make sure the directory is writable
    if (!is_writable($directory)) {
        chmod($directory, 0777);
        if (!is_writable($directory)) {
            return false; // Directory is not writable
        }
    }
    
    $targetDir = $directory;
    $fileName = basename($file["name"]);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Generate a unique file name
    $uniqueName = generateRandomString() . '_' . time() . '.' . $fileType;
    $targetFile = $targetDir . $uniqueName;
    
    // Check if file is an actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if($fileType != "jpg" && $fileType != "png" && $fileType != "jpeg" && $fileType != "gif" ) {
        return false;
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $targetFile;
    } else {
        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                error_log('The uploaded file exceeds the upload_max_filesize directive in php.ini');
                break;
            case UPLOAD_ERR_FORM_SIZE:
                error_log('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
                break;
            case UPLOAD_ERR_PARTIAL:
                error_log('The uploaded file was only partially uploaded');
                break;
            case UPLOAD_ERR_NO_FILE:
                error_log('No file was uploaded');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                error_log('Missing a temporary folder');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                error_log('Failed to write file to disk');
                break;
            case UPLOAD_ERR_EXTENSION:
                error_log('A PHP extension stopped the file upload');
                break;
            default:
                error_log('Unknown upload error');
        }
        return false;
    }
}
?>
