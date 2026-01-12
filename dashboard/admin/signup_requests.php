<?php
session_start();
include '../../db_connect.php';

// ✅ Access Check
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// ✅ Approve Request
if (isset($_POST['approve_id'])) {
    $id = intval($_POST['approve_id']);
    $result = $conn->query("SELECT * FROM signup_requests WHERE id='$id'");
    $data = $result->fetch_assoc();

    if ($data) {
        $email = $data['email'];
        $username = $data['username'];

        // Check for duplicate email or username in users table
        $checkEmail = $conn->query("SELECT id FROM users WHERE email='$email'");
        $checkUser = $conn->query("SELECT id FROM users WHERE username='$username'");

        if ($checkEmail->num_rows > 0) {
            echo "<script>alert('A user with this email already exists');</script>";
        } elseif ($checkUser->num_rows > 0) {
            echo "<script>alert('A user with this username already exists');</script>";
        } else {
           $hashedPassword = $data['password']; // Already hashed


            // Insert user
          $stmt = $conn->prepare("INSERT INTO users (firstName, middleName, lastName, username, password, email, role, status, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())");

$stmt->bind_param(
    "sssssss",
    $data['firstName'],
    $data['middleName'],
    $data['lastName'],
    $data['username'],
    $data['password'], // ✅ USE DIRECT VALUE
    $data['email'],
    $data['role']
);


            if ($stmt->execute()) {
                // Delete request after approval
                $conn->query("DELETE FROM signup_requests WHERE id='$id'");
                echo "<script>alert('Request approved successfully'); window.location='signup_requests.php';</script>";
            } else {
                echo "<script>alert('Failed to approve request');</script>";
            }
        }
    }
}

// ✅ Reject Request
if (isset($_POST['reject_id'])) {
    $id = intval($_POST['reject_id']);
    $conn->query("DELETE FROM signup_requests WHERE id='$id'");
    echo "<script>alert('Request rejected successfully'); window.location='signup_requests.php';</script>";
}

// ✅ Fetch pending requests
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$query = "SELECT * FROM signup_requests WHERE 
          firstName LIKE '%$search%' OR 
          lastName LIKE '%$search%' OR 
          username LIKE '%$search%' OR 
          email LIKE '%$search%' 
          ORDER BY createdAt DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin | Sign-up Requests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; overflow-x: hidden; }
    .main-content { margin-left: 240px; margin-top: 60px; padding: 20px; background: #f8f9fa; height: 100vh; overflow-y: auto; }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="container-fluid">
    <h4 class="fw-bold mb-3">Sign-up Requests</h4>
    <p class="text-muted">Review and manage new account requests.</p>

    <!-- 🔍 Search -->
    <form method="GET" class="mb-3">
      <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search requests..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-danger" type="submit">Search</button>
      </div>
    </form>

    <!-- 🧾 Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-danger text-white fw-bold">Pending Requests</div>
      <div class="card-body">
        <table class="table table-striped align-middle text-center">
          <thead class="table-danger">
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Date</th>
              <th>ID Image</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['firstName'] . ' ' . ($row['middleName'] ? $row['middleName'].' ' : '') . $row['lastName'] ?></td>
                  <td><?= $row['username'] ?></td>
                  <td><?= $row['email'] ?></td>
                  <td><span class="badge bg-danger"><?= ucfirst($row['role']) ?></span></td>
                  <td><?= date('M d, Y', strtotime($row['createdAt'])) ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#viewImage<?= $row['id'] ?>">View ID</button>
                    <div class="modal fade" id="viewImage<?= $row['id'] ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">ID Image Preview</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body text-center">
                            <img src="<?= htmlspecialchars($row['idImage']) ?>" class="img-fluid rounded" style="max-height: 400px;">
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex justify-content-center gap-2">
                      <form method="POST">
                        <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                        <button class="btn btn-success btn-sm" onclick="return confirm('Approve this request?')">Approve</button>
                      </form>
                      <form method="POST">
                        <input type="hidden" name="reject_id" value="<?= $row['id'] ?>">
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Reject this request?')">Reject</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-muted py-4">No pending requests found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const logoutBtn = document.querySelector(".logout-btn");
  if(logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      Swal.fire({
        title: "Logout Confirmation",
        text: "Are you sure you want to logout?",
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
</body>
</html>
