<?php
// create_user_action.php
session_start();
include '../../db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $firstName = trim($_POST['firstName']);
        $middleName = trim($_POST['middleName']);
        $lastName = trim($_POST['lastName']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $contact = trim($_POST['contact']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $status = $_POST['status'];
        
        // Calculate full_name
        $full_name = trim("$firstName $middleName $lastName");
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = "Please fill in all required fields!";
            header("Location: manage_users.php");
            exit();
        }
        
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $_SESSION['error'] = "Username already exists! Please choose a different username.";
            header("Location: manage_users.php");
            exit();
        }
        $checkStmt->close();
        
        // Check if email already exists (if email is required to be unique)
        if (!empty($email)) {
            $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmailStmt->bind_param("s", $email);
            $checkEmailStmt->execute();
            $checkEmailStmt->store_result();
            
            if ($checkEmailStmt->num_rows > 0) {
                $_SESSION['error'] = "Email already exists! Please use a different email address.";
                header("Location: manage_users.php");
                exit();
            }
            $checkEmailStmt->close();
        }
        
        // Handle file uploads
        $avatarPath = null;
        $idImagePath = null;
        $uploadDir = "../../uploads/";
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Upload avatar if provided
        if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarName = time() . '_avatar_' . basename($_FILES['avatar']['name']);
            $avatarTarget = $uploadDir . $avatarName;
            
            // Validate image file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            $avatarExt = strtolower(pathinfo($avatarName, PATHINFO_EXTENSION));
            
            if (in_array($avatarExt, $allowedTypes)) {
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarTarget)) {
                    $avatarPath = "uploads/" . $avatarName;
                } else {
                    $_SESSION['error'] = "Failed to upload avatar image.";
                    header("Location: manage_users.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Invalid avatar image type. Only JPG, JPEG, PNG, GIF are allowed.";
                header("Location: manage_users.php");
                exit();
            }
        }
        
        // Upload ID image (required)
        if (!empty($_FILES['idImage']['name']) && $_FILES['idImage']['error'] === UPLOAD_ERR_OK) {
            $idImageName = time() . '_id_' . basename($_FILES['idImage']['name']);
            $idImageTarget = $uploadDir . $idImageName;
            
            // Validate image file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $idImageExt = strtolower(pathinfo($idImageName, PATHINFO_EXTENSION));
            
            if (in_array($idImageExt, $allowedTypes)) {
                if (move_uploaded_file($_FILES['idImage']['tmp_name'], $idImageTarget)) {
                    $idImagePath = "uploads/" . $idImageName;
                } else {
                    $_SESSION['error'] = "Failed to upload ID image.";
                    header("Location: manage_users.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Invalid ID image type. Only JPG, JPEG, PNG, GIF, PDF are allowed.";
                header("Location: manage_users.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "ID Image is required!";
            header("Location: manage_users.php");
            exit();
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (firstName, middleName, lastName, username, email, contact, password, role, full_name, avatar, idImage, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssssssssssss", $firstName, $middleName, $lastName, $username, $email, $contact, $hashedPassword, $role, $full_name, $avatarPath, $idImagePath, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User account created successfully!";
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        
        header("Location: manage_users.php");
        exit();
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Error in create_user_action.php: " . $e->getMessage());
        
        $_SESSION['error'] = "Error creating user: " . $e->getMessage();
        header("Location: manage_users.php");
        exit();
    }
} else {
    // If not POST request, redirect back
    $_SESSION['error'] = "Invalid request method!";
    header("Location: manage_users.php");
    exit();
}
?>