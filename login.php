<?php
session_start();
include 'db_connect.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Handle login
    if (isset($_POST["username"]) && isset($_POST["password"])) {
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);

        // Prepare and execute query
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // 🔐 Use password_verify if passwords are hashed properly
            // If still using md5 in your DB, you can revert to: md5($password) === $user['password']
            if (password_verify($password, $user["password"]) || md5($password) === $user["password"]) {

                // Set session data
                $_SESSION["user"] = [
                    "id" => $user["id"],
                    "username" => $user["username"],
                    "role" => strtolower($user["role"]),
                    "firstName" => $user["firstName"] ?? '',
                    "lastName" => $user["lastName"] ?? ''
                ];

                // Define redirects for each role
                $redirects = [
                    "admin" => "dashboard/admin/admin.php",
                    "trainer" => "dashboard/trainer/trainer.php",
                    "trainor" => "dashboard/trainer/trainer.php", // alternate spelling support
                    "client" => "dashboard/client/client.php",
                    "trainee" => "dashboard/client/client.php",
                    "owner" => "dashboard/owner/owner.php",
                ];

                // Redirect based on role
                $role = $_SESSION["user"]["role"];
                $redirectPath = $redirects[$role] ?? "index.php";

                header("Location: $redirectPath");
                exit;

            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "User not found!";
        }

        $stmt->close();
    }
    
    // Handle forgot password request
    if (isset($_POST["forgot_username"])) {
        $username = trim($_POST["forgot_username"]);
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT id, username, firstName, email FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate a simple reset code (6 digits)
            $reset_code = sprintf("%06d", mt_rand(1, 999999));
            $expires = date("Y-m-d H:i:s", strtotime("+30 minutes")); // Code expires in 30 minutes
            
            // Store reset code in database
            $updateStmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_expires = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $reset_code, $expires, $user['id']);
            
            if ($updateStmt->execute()) {
                // For demo purposes, we'll show the reset code on screen
                // In production, you would send this via email/SMS
                $success = "Password reset code has been generated!<br><br>
                           <strong>Your Reset Code:</strong> <span style='font-size: 1.5em; font-weight: bold; color: #d32f2f;'>$reset_code</span><br><br>
                           <small><a href='reset_password.php' class='btn btn-sm btn-outline-danger'>Go to Password Reset</a></small><br><br>
                           <em>Note: This code expires in 30 minutes. In production, this would be sent via email/SMS.</em>";
            } else {
                $error = "Error generating reset code. Please try again.";
            }
            
            $updateStmt->close();
        } else {
            $error = "No account found with that username.";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GymFit | Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
* { font-family: 'Poppins', sans-serif; }
body { margin: 0; display: flex; min-height: 100vh; }
.left {
  flex: 1;
  background: linear-gradient(135deg, #c62828, #b71c1c);
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: 50px;
}
.left img {
  width: 180px; height: 180px;
  border-radius: 50%; margin-bottom: 20px;
  background: #fff; padding: 10px;
}
.left h1 { font-weight: 700; }
.left p { color: #f8f9fa; font-size: 15px; margin-top: 10px; line-height: 1.6; }
.right {
  flex: 1;
  background: #fff;
  display: flex;
  justify-content: center;
  align-items: center;
}
.login-card {
  width: 100%;
  max-width: 400px;
  border-radius: 15px;
  box-shadow: 0 0 25px rgba(0,0,0,0.1);
  padding: 35px 40px;
  animation: fadeIn 0.6s ease-in-out;
}
@keyframes fadeIn { from {opacity: 0; transform: translateY(10px);} to {opacity: 1; transform: translateY(0);} }
.login-card h2 { font-weight: 700; margin-bottom: 10px; }
.login-card p { color: #6c757d; margin-bottom: 25px; }
.form-control { padding-left: 40px; }
.input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; }
.btn-login {
  background: #d32f2f;
  color: white;
  font-weight: 600;
  border: none;
  transition: 0.3s;
}
.btn-login:hover { background: #b71c1c; }
small a { color: #d32f2f; font-weight: 500; text-decoration: none; }
small a:hover { text-decoration: underline; }
.forgot-link { 
    color: #d32f2f; 
    text-decoration: none; 
    font-size: 14px;
    cursor: pointer;
}
.forgot-link:hover { 
    text-decoration: underline; 
    color: #b71c1c;
}
.modal-content {
    border-radius: 15px;
    border: none;
}
.modal-header {
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 1.5rem;
}
.modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1rem 1.5rem;
}
@media (max-width: 900px) {
  body { flex-direction: column; }
  .left { padding: 30px 20px; }
}
</style>
</head>
<body>

<div class="left">
  <img src="assets/_logo.png" alt="GymFit Logo">
  <h1>Join GymFit Today</h1>
  <p>"Start your fitness journey. Every signup<br>is a step closer to your goals!"</p>
  <img src="assets/favicon.svg" alt="Barbell Icon" style="width: 180px; height: auto; margin-top: 10px; background: transparent;">
</div>

<div class="right">
  <div class="login-card">
    <h2 class="text-center">Account Login</h2>
    <p class="text-center">Enter your credentials to access your account</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
      <div class="alert alert-success text-center"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return handleSubmit()">
      <div class="mb-3 position-relative">
        <i class="fa fa-user input-icon"></i>
        <input type="text" class="form-control" name="username" placeholder="Username" required>
      </div>
      <div class="mb-3 position-relative">
        <i class="fa fa-lock input-icon"></i>
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
      </div>
      
      <div class="mb-3 text-end">
        <a href="#" class="forgot-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
          <i class="fa fa-key me-1"></i>Forgot Password?
        </a>
      </div>
      
      <button type="submit" id="loginBtn" class="btn btn-login w-100 py-2">
        <i class="fa fa-sign-in-alt me-1"></i> Sign In
      </button>
      
      <div class="text-center mt-3">
        <small>Don't have an account? <a href="register.php">Sign up</a></small>
      </div>
    </form>
  </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="forgotPasswordModalLabel">
          <i class="fa fa-key me-2"></i>Reset Your Password
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="forgotPasswordForm">
        <div class="modal-body">
          <p class="text-muted">Enter your username and we'll generate a reset code for you.</p>
          <div class="mb-3">
            <label for="forgot_username" class="form-label">Username</label>
            <input type="text" class="form-control" id="forgot_username" name="forgot_username" placeholder="Enter your username" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-login" id="resetBtn">
            <i class="fa fa-paper-plane me-1"></i> Get Reset Code
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function handleSubmit() {
  const btn = document.getElementById("loginBtn");
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Signing in...';
  return true;
}

// Handle forgot password form submission
document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
  const btn = document.getElementById('resetBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';
});

// Clear form when modal is hidden
document.getElementById('forgotPasswordModal').addEventListener('hidden.bs.modal', function () {
  document.getElementById('forgotPasswordForm').reset();
  const btn = document.getElementById('resetBtn');
  btn.disabled = false;
  btn.innerHTML = '<i class="fa fa-paper-plane me-1"></i> Get Reset Code';
});
</script>

</body>
</html>