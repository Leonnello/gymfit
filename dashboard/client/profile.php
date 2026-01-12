<?php
include '../../db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// ✅ Create upload directories if not exist
$avatarDir = "../../uploads/avatars/";
$idDir = "../../uploads/ids/";
if (!is_dir($avatarDir)) mkdir($avatarDir, 0777, true);
if (!is_dir($idDir)) mkdir($idDir, 0777, true);

// ✅ Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = trim($_POST['firstName']);
  $middleName = trim($_POST['middleName']);
  $lastName = trim($_POST['lastName']);
  $email = trim($_POST['email']);
  $username = trim($_POST['username']);
  $full_name = $firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName;

  // ✅ Avatar upload
  $avatar = $user['avatar'] ?? '';
  if (!empty($_FILES['avatar']['name'])) {
    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array(strtolower($ext), $allowed)) {
      $avatarName = time() . '_' . uniqid() . '.' . $ext;
      $targetPath = $avatarDir . $avatarName;
      if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
        $avatar = $avatarName;
      }
    }
  }

  // ✅ ID image upload
  $idImage = $user['idImage'] ?? '';
  if (!empty($_FILES['idImage']['name'])) {
    $ext = pathinfo($_FILES['idImage']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array(strtolower($ext), $allowed)) {
      $idImageName = time() . '_' . uniqid() . '.' . $ext;
      $targetPath = $idDir . $idImageName;
      if (move_uploaded_file($_FILES['idImage']['tmp_name'], $targetPath)) {
        $idImage = $idImageName;
      }
    }
  }

  // ✅ Update query
  $query = "UPDATE users 
            SET firstName=?, middleName=?, lastName=?, full_name=?, email=?, username=?, avatar=?, idImage=? 
            WHERE id=?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ssssssssi", $firstName, $middleName, $lastName, $full_name, $email, $username, $avatar, $idImage, $user_id);
  $stmt->execute();

  // ✅ Refresh session user data
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $_SESSION['user'] = $user;

  $success = "Profile updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile - GymFit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
      padding-left: 250px;
    }
    .profile-container {
      max-width: 900px;
      margin: 3rem auto;
      background: #fff;
      padding: 2.5rem;
      border-radius: 16px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }
    .profile-header {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    .profile-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #b71c1c;
    }
    .btn-save {
      background-color: #b71c1c;
      color: #fff;
      border: none;
    }
    .btn-save:hover {
      background-color: #a31515;
    }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>
  <?php include 'includes/sidebar.php'; ?>

  <div class="profile-container">
    <div class="profile-header">
      <?php if (!empty($user['avatar']) && file_exists($avatarDir . $user['avatar'])): ?>
        <img src="<?= $avatarDir . htmlspecialchars($user['avatar']) ?>" class="profile-avatar" alt="Profile">
      <?php else: ?>
        <div class="profile-avatar d-flex align-items-center justify-content-center bg-danger text-white">
          <i class="bi bi-person-fill" style="font-size:2rem;"></i>
        </div>
      <?php endif; ?>

      <div>
        <h3 class="fw-bold mb-1"><?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></h3>
        <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($user['role'] ?? 'User') ?></span>
        <p class="text-muted small mb-0">
          <i class="bi bi-calendar3 me-1"></i>
          Joined: <?= isset($user['created_at']) ? date("M d, Y", strtotime($user['created_at'])) : 'N/A' ?>
        </p>
      </div>
    </div>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold"><i class="bi bi-person text-danger me-2"></i>First Name</label>
          <input type="text" name="firstName" class="form-control" value="<?= htmlspecialchars($user['firstName'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold"><i class="bi bi-person-lines-fill text-danger me-2"></i>Middle Name</label>
          <input type="text" name="middleName" class="form-control" value="<?= htmlspecialchars($user['middleName'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold"><i class="bi bi-person text-danger me-2"></i>Last Name</label>
          <input type="text" name="lastName" class="form-control" value="<?= htmlspecialchars($user['lastName'] ?? '') ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-envelope text-danger me-2"></i>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-person-badge text-danger me-2"></i>Username</label>
          <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-image text-danger me-2"></i>Profile Picture</label>
          <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
          <?php if (!empty($user['avatar'])): ?>
            <small class="text-muted">Current: <?= htmlspecialchars($user['avatar']) ?></small>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-card-image text-danger me-2"></i>ID Image</label>
          <input type="file" name="idImage" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
          <?php if (!empty($user['idImage'])): ?>
            <small class="text-muted">Current: <?= htmlspecialchars($user['idImage']) ?></small>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-person-check text-danger me-2"></i>Status</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($user['status'] ?? 'Active')) ?>" disabled>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold"><i class="bi bi-lock text-danger me-2"></i>Role</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($user['role'] ?? 'User')) ?>" disabled>
        </div>
      </div>

      <div class="text-end mt-4">
        <button type="submit" class="btn btn-save px-4 py-2">
          <i class="bi bi-save me-2"></i>Save Changes
        </button>
      </div>
    </form>
  </div>
  <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Dropdown + Logout Confirmation -->
<script>
document.addEventListener("DOMContentLoaded", function () {

  const logoutBtn = document.querySelector(".logout-btn");

  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();

      Swal.fire({
        title: "Logout Confirmation",
        text: "Are you sure you want to log out?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, logout"
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = "../../logout.php";
        }
      });

    });
  }

});
</script>

<!-- Bootstrap 5 JS (Required for dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
