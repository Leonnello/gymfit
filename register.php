<?php
session_start();
include 'db_connect.php';

// Fetch barangays
$barangays = $conn->query("SELECT * FROM barangays ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect and sanitize form data
    $firstName = trim($_POST['firstName']);
    $middleName = trim($_POST['middleName']);
    $lastName = trim($_POST['lastName']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $birthday = $_POST['birthday'];
    $mobileNumber = trim($_POST['mobileNumber']);
    $street = trim($_POST['street']);
    $barangay = $_POST['barangay'];
    $region_id = trim($_POST['region']);
    // $zip_code = trim($_POST['zip_code']);
    $role = "trainee";
    $status = "pending";

    // Password validation
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        echo "<script>alert('Passwords do not match!');</script>";
        exit;
    }
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // File upload validation
    if (!isset($_FILES['idImage']) || $_FILES['idImage']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Error uploading ID image!');</script>";
        exit;
    }

    $targetDir = "uploads/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = basename($_FILES["idImage"]["name"]);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png"];

    if (!in_array($fileType, $allowed)) {
        echo "<script>alert('Only JPG, JPEG, PNG files allowed!');</script>";
        exit;
    } elseif ($_FILES["idImage"]["size"] > 5 * 1024 * 1024) {
        echo "<script>alert('File must be less than 5MB!');</script>";
        exit;
    }

    $targetFile = $targetDir . time() . "_" . $fileName;
    if (!move_uploaded_file($_FILES["idImage"]["tmp_name"], $targetFile)) {
        echo "<script>alert('Failed to move uploaded file.');</script>";
        exit;
    }

    // Check for duplicate username or email
    $check = $conn->prepare("SELECT id FROM signup_requests WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        echo "<script>alert('Email or Username already exists.');</script>";
        exit;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO signup_requests 
        (firstName, middleName, lastName, username, email, password, role, birthday, mobileNumber, street, barangay, province, zipCode, idImage, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssssssssssssss",
        $firstName, $middleName, $lastName, $username, $email, $password,
        $role, $birthday, $mobileNumber, $street, $barangay, 
        $region_id, $zip_code, $targetFile, $status
    );

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! Please wait for admin approval.'); window.location='login.php';</script>";
        exit;
    } else {
        echo "<script>alert('Registration failed. Try again.');</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymFit | Register</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  margin: 0;
  display: flex;
  min-height: 100vh;
  font-family: 'Poppins', sans-serif;
}

/* ✅ Lessen left side width */
.left {
  width: 35%;
  min-width: 320px;
  background: linear-gradient(135deg, #c62828, #b71c1c);
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: 20px;
}

/* ✅ Expand right side width */
.right {
  width: 70%;
  background: #fff;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 40px 15px;
  overflow-y: auto;
}

/* ✅ Make form wider — more space for inputs */
.card-form {
  width: 100%;
  max-width: 750px;
  border-radius: 15px;
  box-shadow: 0 0 25px rgba(0,0,0,0.1);
  padding: 30px 40px;
}

.left img {
  width: 130px;
  height: 130px;
  border-radius: 50%;
  margin-bottom: 15px;
}

/* ✅ Mobile responsive stays good */
@media(max-width: 900px) { 
  body { 
    flex-direction: column; 
  } 
  
  .left, .right {
    width: 100%;
  }

  .card-form {
    max-width: 100%;
  }
}

@keyframes fadeIn { from {opacity:0; transform:translateY(10px);} to {opacity:1; transform:translateY(0);} }
.btn-danger { background-color: #d32f2f; border: none; }
.btn-danger:hover { background-color: #b71c1c; }
a { color: #d32f2f; text-decoration: none; }
a:hover { text-decoration: underline; }
@media(max-width: 900px) { body { flex-direction: column; } .left { padding: 20px; } }
</style>
</head>
<body>

<div class="left">
  <img src="assets/_logo.png" alt="GymFit Logo">
  <h1>Join GymFit Today</h1>
  <p>"Start your fitness journey. Every signup<br>is a step closer to your goals!"</p>
</div>

<div class="right">
  <div class="card-form">
    <h3 class="text-center fw-bold">Create an Account</h3>
    <!-- <p class="text-center text-muted mb-4">Enter your information to create your GymFit account</p> -->

    <form method="POST" enctype="multipart/form-data">

   <div class="row">
  <div class="col-md-4 mb-3">
    <label class="form-label">First Name</label>
    <input type="text" name="firstName" class="form-control" placeholder="First Name" required>
  </div>
  <div class="col-md-4 mb-3">
    <label class="form-label">Middle Name</label>
    <input type="text" name="middleName" class="form-control" placeholder="Middle Name">
  </div>
  <div class="col-md-4 mb-3">
    <label class="form-label">Last Name</label>
    <input type="text" name="lastName" class="form-control" placeholder="Last Name" required>
  </div>
</div>

<div class="row">
  <div class="col-md-4 mb-3">
    <label class="form-label">Mobile Number</label>
    <input type="text" name="mobileNumber" class="form-control" placeholder="09XXXXXXXXX" required>
  </div>
  <div class="col-md-4 mb-3">
    <label class="form-label">Username</label>
    <input type="text" name="username" class="form-control" placeholder="Username" required>
  </div>
  <div class="col-md-4 mb-3">
    <label class="form-label">Email Address</label>
    <input type="email" name="email" class="form-control" placeholder="Email" required>
  </div>
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label">Birthday</label>
    <input type="date" name="birthday" class="form-control" required>
  </div>
   <div class="col-md-6 mb-3">
        <label class="form-label fw-semibold">Upload ID Card Image</label>
        <input type="file" name="idImage" class="form-control" accept="image/*" required>
        <small class="text-muted">Accepted: JPG, JPEG, PNG (Max 5MB)</small>
      </div>
</div>

<div class="row">
   <div class="col-md-4 mb-3">
    <label class="form-label">Street</label>
    <input type="text" name="street" class="form-control" placeholder="Street" required>
  </div>
  <div class="col-md-4 mb-3">
    <label class="form-label">City</label>
    <input type="text" name="region" class="form-control" placeholder="City" required>
  </div>
  <div class="col-md-4 mb-3">
      <label class="form-label">Barangay</label>
    <input type="text" name="barangay" class="form-control" placeholder="Barangay" required>
    </select>
  </div>
  <!-- <div class="col-md-4 mb-3">
    <label class="form-label">Zip Code</label>
    <input type="text" name="zip_code" id="zip_code" class="form-control" readonly required>
  </div>
</div> -->

<div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" placeholder="Password" required>
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label">Confirm Password</label>
    <input type="password" name="confirmPassword" class="form-control" placeholder="Confirm Password" required>
  </div>
</div>


      <div class="d-grid">
        <button type="submit" class="btn btn-danger py-2 fw-semibold">Create Account</button>
      </div>

      <div class="text-center mt-3">
        <small>Already have an account? <a href="login.php">Sign in</a></small>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('barangay').addEventListener('change', function() {
    const selected = this.selectedOptions[0];
    document.getElementById('zip_code').value = selected.getAttribute('data-zip') || '';
});
</script>

</body>
</html>
