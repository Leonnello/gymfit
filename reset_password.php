<?php
session_start();
include 'db_connect.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $reset_code = trim($_POST["reset_code"]);
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    
    // Validate inputs
    if (empty($username) || empty($reset_code) || empty($new_password)) {
        $error = "All fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        // Check if reset code is valid and not expired
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND reset_code = ? AND reset_expires > NOW()");
        $stmt->bind_param("ss", $username, $reset_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password and clear reset code
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires = NULL WHERE id = ?");
            $updateStmt->bind_param("si", $hashed_password, $user['id']);
            
            if ($updateStmt->execute()) {
                $success = "Password reset successfully! You can now <a href='login.php' class='alert-link'>login</a> with your new password.";
            } else {
                $error = "Error resetting password. Please try again.";
            }
            
            $updateStmt->close();
        } else {
            $error = "Invalid reset code, username, or code has expired. Please request a new code.";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | GymFit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #c62828, #b71c1c);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .btn-reset {
            background: #d32f2f;
            color: white;
            font-weight: 600;
            border: none;
        }
        .btn-reset:hover {
            background: #b71c1c;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <h2 class="text-center mb-4">
            <i class="fa fa-lock me-2"></i>Reset Password
        </h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success text-center"><?= $success ?></div>
        <?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                </div>
                
                <div class="mb-3">
                    <label for="reset_code" class="form-label">Reset Code</label>
                    <input type="text" class="form-control" id="reset_code" name="reset_code" placeholder="Enter the 6-digit reset code" required maxlength="6">
                </div>
                
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" required minlength="6">
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-reset w-100 py-2">
                    <i class="fa fa-sync-alt me-1"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">
                <i class="fa fa-arrow-left me-1"></i>Back to Login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>