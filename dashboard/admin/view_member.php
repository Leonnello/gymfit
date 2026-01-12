<?php
include '../../db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("No member selected.");
}

$id = intval($_GET['id']);
$query = $conn->query("SELECT * FROM users WHERE id = $id");

if ($query->num_rows == 0) {
    die("Member not found.");
}

$user = $query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Member</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Modal -->
<div class="modal fade show" id="viewMemberModal" tabindex="-1" style="display:block; background: rgba(0,0,0,0.5);">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Member Details</h5>
        <a href="members.php" class="btn-close"></a>
      </div>

      <div class="modal-body">

        <div class="row mb-3">
          <div class="col-md-4 text-center">
            <img src="../../uploads/avatar/<?= htmlspecialchars($user['avatar']) ?>" 
                 alt="Avatar" class="rounded-circle mb-2" width="130" height="130"
                 onerror="this.src='../../uploads/default_avatar.png'">
            <p class="fw-bold">Profile Picture</p>
          </div>

          <div class="col-md-8">
            <table class="table table-bordered">
              <tr>
                <th>ID</th>
                <td><?= $user['id'] ?></td>
              </tr>
              <tr>
                <th>Full Name</th>
                <td><?= htmlspecialchars($user['firstName']." ".$user['middleName']." ".$user['lastName']) ?></td>
              </tr>
              <tr>
                <th>Username</th>
                <td><?= htmlspecialchars($user['username']) ?></td>
              </tr>
              <tr>
                <th>Email</th>
                <td><?= htmlspecialchars($user['email']) ?></td>
              </tr>
              <tr>
                <th>Contact</th>
                <td><?= htmlspecialchars($user['contact']) ?></td>
              </tr>
              <tr>
                <th>Role</th>
                <td><?= htmlspecialchars($user['role']) ?></td>
              </tr>
              <tr>
                <th>Status</th>
                <td>
                  <span class="badge <?= $user['status']=='active' ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= ucfirst($user['status']) ?>
                  </span>
                </td>
              </tr>
              <tr>
                <th>Joined</th>
                <td><?= date("M d, Y h:i A", strtotime($user['created_at'])) ?></td>
              </tr>
            </table>
          </div>
        </div>

        <hr>

        <h5 class="mb-2">ID Image</h5>
        <div class="text-center">
          <img src="../../uploads/id/<?= htmlspecialchars($user['idImage']) ?>" 
               alt="ID Image" class="img-fluid rounded"
               style="max-height:300px;"
               onerror="this.src='../../uploads/default_id.png'">
        </div>

      </div>

      <div class="modal-footer">
        <a href="members.php" class="btn btn-secondary">Close</a>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
