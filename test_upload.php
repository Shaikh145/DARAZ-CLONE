<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simple function to upload an image
function simple_upload_image($file) {
    // Create uploads directory if it doesn't exist
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
        echo "Created uploads directory<br>";
    }
    
    // Check if directory is writable
    if (!is_writable('uploads')) {
        echo "Directory is not writable. Attempting to set permissions...<br>";
        chmod('uploads', 0777);
        if (!is_writable('uploads')) {
            echo "Failed to make directory writable<br>";
            return false;
        }
    }
    
    // Generate a unique filename
    $filename = 'uploads/' . uniqid() . '_' . basename($file['name']);
    
    // Try to upload the file
    if (move_uploaded_file($file['tmp_name'], $filename)) {
        echo "File uploaded successfully to: " . $filename . "<br>";
        return $filename;
    } else {
        echo "Upload failed. Error code: " . $file['error'] . "<br>";
        
        // Display detailed error message
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                echo "The uploaded file exceeds the upload_max_filesize directive in php.ini<br>";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                echo "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form<br>";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "The uploaded file was only partially uploaded<br>";
                break;
            case UPLOAD_ERR_NO_FILE:
                echo "No file was uploaded<br>";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                echo "Missing a temporary folder<br>";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                echo "Failed to write file to disk<br>";
                break;
            case UPLOAD_ERR_EXTENSION:
                echo "A PHP extension stopped the file upload<br>";
                break;
            default:
                echo "Unknown upload error<br>";
        }
        return false;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Upload Information:</h2>";
    
    // Check if file was uploaded
    if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        echo "File received: " . $_FILES['test_image']['name'] . "<br>";
        echo "File size: " . $_FILES['test_image']['size'] . " bytes<br>";
        echo "File type: " . $_FILES['test_image']['type'] . "<br>";
        
        // Try to upload the file
        $result = simple_upload_image($_FILES['test_image']);
        
        if ($result) {
            echo "<div style='color: green; font-weight: bold;'>Upload successful!</div>";
            echo "<img src='" . $result . "' style='max-width: 300px; margin-top: 20px;'>";
        } else {
            echo "<div style='color: red; font-weight: bold;'>Upload failed!</div>";
        }
    } else {
        echo "No file was uploaded or there was an error.<br>";
    }
    
    // Display PHP configuration
    echo "<h2>PHP Configuration:</h2>";
    echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
    echo "post_max_size: " . ini_get('post_max_size') . "<br>";
    echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
    echo "file_uploads enabled: " . (ini_get('file_uploads') ? 'Yes' : 'No') . "<br>";
    echo "max_execution_time: " . ini_get('max_execution_time') . " seconds<br>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Image Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .btn {
            background-color: #f85606;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Test Image Upload</h1>
    <p>This page will help diagnose image upload issues.</p>
    
    <form action="test_upload.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="test_image">Select an image to upload:</label>
            <input type="file" id="test_image" name="test_image" accept="image/*">
        </div>
        
        <button type="submit" class="btn">Upload Image</button>
    </form>
    
    <div style="margin-top: 30px;">
        <h2>Troubleshooting Steps:</h2>
        <ol>
            <li>Try uploading a small image (less than 1MB)</li>
            <li>Make sure the image is a JPG, PNG, or GIF</li>
            <li>Check if the uploads directory exists and is writable</li>
            <li>If this test works but the product upload doesn't, there might be an issue with the product upload form</li>
        </ol>
    </div>
</body>
</html>
