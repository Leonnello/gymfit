<?php
// add_member_action.php
session_start();
include '../../db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $status = $_POST['status'];
        
        // Validate required fields
        if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = "Please fill in all required fields!";
            header("Location: members.php");
            exit();
        }
        
        // Split full name into first, middle, last
        $name_parts = explode(' ', $full_name);
        $firstName = $name_parts[0];
        $lastName = end($name_parts);
        $middleName = '';
        
        // If there are more than 2 name parts, everything in between is middle name
        if (count($name_parts) > 2) {
            $middle_parts = array_slice($name_parts, 1, -1);
            $middleName = implode(' ', $middle_parts);
        }
        
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
            $_SESSION['error'] = "Username already exists! Please choose a different username.";
            header("Location: members.php");
            exit();
        }
        $checkStmt->close();
        
        // Check if email already exists
        if (!empty($email)) {
            $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmailStmt->bind_param("s", $email);
            $checkEmailStmt->execute();
            $checkEmailStmt->store_result();
            
            if ($checkEmailStmt->num_rows > 0) {
                $_SESSION['error'] = "Email already exists! Please use a different email address.";
                header("Location: members.php");
                exit();
            }
            $checkEmailStmt->close();
        }
        
        // Handle file uploads - create default paths since they're required in database
        $avatarPath = "uploads/default_avatar.png";
        $idImagePath = "uploads/default_id.png";
        $uploadDir = "../../uploads/";
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Set default contact number
        $contact = "0000000000";
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Set role to trainee for members
        $role = 'trainee';
        
        // Insert new member
        $stmt = $conn->prepare("INSERT INTO users (firstName, middleName, lastName, username, email, contact, password, role, full_name, avatar, idImage, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssssssssssss", $firstName, $middleName, $lastName, $username, $email, $contact, $hashedPassword, $role, $full_name, $avatarPath, $idImagePath, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Member account created successfully!";
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        
        header("Location: members.php");
        exit();
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Error in add_member_action.php: " . $e->getMessage());
        
        $_SESSION['error'] = "Error creating member: " . $e->getMessage();
        header("Location: members.php");
        exit();
    }
} else {
    // If not POST request, redirect back
    $_SESSION['error'] = "Invalid request method!";
    header("Location: members.php");
    exit();
}
?>