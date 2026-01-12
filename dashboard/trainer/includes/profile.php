<?php
session_start();
include '../../../db_connect.php';

if (!isset($_SESSION['user'])) {
  header("Location: ../../../login.php");
  exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = $_POST['firstName'];
  $middleName = $_POST['middleName'];
  $lastName = $_POST['lastName'];
  $email = $_POST['email'];
  $contact = $_POST['contact'];

  $avatarPath = $user['avatar'];

  // Handle avatar upload
  if (!empty($_FILES['avatar']['name'])) {
    $targetDir = "../../../uploads/avatars/";
    $fileName = uniqid() . "_" . basename($_FILES["avatar"]["name"]);
    $targetFile = $targetDir . $fileName;
    move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile);
    $avatarPath = "uploads/avatars/" . $fileName;
  }

  // Update user info
  $stmt = $conn->prepare("UPDATE users SET firstName=?, middleName=?, lastName=?, email=?, contact=?, avatar=? WHERE id=?");
  $stmt->bind_param("ssssssi", $firstName, $middleName, $lastName, $email, $contact, $avatarPath, $user_id);
  $stmt->execute();

  // Refresh session
  $_SESSION['user']['firstName'] = $firstName;
  $_SESSION['user']['middleName'] = $middleName;
  $_SESSION['user']['lastName'] = $lastName;
  $_SESSION['user']['email'] = $email;
  $_SESSION['user']['contact'] = $contact;
  $_SESSION['user']['avatar'] = $avatarPath;

  echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
  exit;
}

// Fetch latest user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
?>

<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="content p-4" style="margin-left:250px;">
  <div class="container mt-4">
    <div class="card shadow-lg border-0 rounded-4">
      <div class="card-header bg-dark text-white d-flex align-items-center">
        <i class="bi bi-person-circle me-2 fs-4"></i>
        <h5 class="mb-0">My Profile</h5>
      </div>
      <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-4 text-center mb-3">
              <img src="../../../<?= htmlspecialchars($userData['avatar'] ?? 'assets/default-avatar.png') ?>" 
                   alt="Avatar" 
                   class="rounded-circle border" 
                   style="width:150px;height:150px;object-fit:cover;">
              <div class="mt-3">
                <label class="btn btn-outline-dark btn-sm">
                  <i class="bi bi-upload me-1"></i> Upload Avatar
                  <input type="file" name="avatar" accept="image/*" hidden>
                </label>
              </div>
            </div>

            <div class="col-md-8">
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label fw-semibold"><i class="bi bi-person"></i> First Name</label>
                  <input type="text" name="firstName" class="form-control" value="<?= htmlspecialchars($userData['firstName']) ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label fw-semibold"><i class="bi bi-person"></i> Middle Name</label>
                  <input type="text" name="middleName" class="form-control" value="<?= htmlspecialchars($userData['middleName']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label fw-semibold"><i class="bi bi-person"></i> Last Name</label>
                  <input type="text" name="lastName" class="form-control" value="<?= htmlspecialchars($userData['lastName']) ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold"><i class="bi bi-envelope"></i> Email</label>
                  <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold"><i class="bi bi-telephone"></i> Contact Number</label>
                  <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($userData['contact']) ?>">
                </div>
              </div>

              <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-dark rounded-3 px-4">
                  <i class="bi bi-save me-2"></i> Save Changes
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
