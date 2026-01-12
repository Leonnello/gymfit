<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$roleFilter = isset($_GET['role']) && $_GET['role'] !== 'all' ? $_GET['role'] : 'all';

try {

    if ($roleFilter === 'all') {
        $stmt = $conn->prepare("SELECT * FROM users ORDER BY role, lastName ASC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role = ? ORDER BY lastName ASC");
        $stmt->bind_param("s", $roleFilter);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | GymFit Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body { font-family: "Poppins", sans-serif; background-color: #f8f9fa; overflow-x: hidden; }
    .main-content { margin-left: 250px; margin-top: 56px; padding: 30px; min-height: 100vh; }
    .card { border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.05); }
    .role-badge { text-transform: capitalize; font-size: 0.85rem; }
    .role-badge.admin { background-color: #212529; }
    .role-badge.trainer { background-color: #0d6efd; }
    .role-badge.trainee { background-color: #dc3545; }
    .role-badge.client { background-color: #198754; }
    img.avatar-img { width:50px; height:50px; object-fit:cover; border-radius:50%; }
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold">👥 Manage Users</h2>

      <form method="GET" class="d-flex align-items-center">
        <label class="me-2 fw-semibold">Filter by Role:</label>
        <select name="role" class="form-select" style="width:180px;" onchange="this.form.submit()">
          <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>All</option>
          <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
          <option value="trainer" <?= $roleFilter === 'trainer' ? 'selected' : '' ?>>Trainer</option>
          <option value="trainee" <?= $roleFilter === 'trainee' ? 'selected' : '' ?>>Trainee</option>
          <option value="client" <?= $roleFilter === 'client' ? 'selected' : '' ?>>Client</option>
        </select>
      </form>
    </div>

    <div class="card p-4">

      <?php if (empty($users)): ?>
        <p class="text-center text-muted py-5 mb-0">No users found.</p>
      <?php else: ?>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>

            <?php $i=1; foreach ($users as $u): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($u['firstName'] . " " . $u['lastName']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="badge role-badge <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
              <td><?= date("M d, Y", strtotime($u['created_at'])) ?></td>
              <td>

                <!-- EDIT BTN -->
                <button class="btn btn-sm btn-outline-success editBtn"
                  data-bs-toggle="modal" 
                  data-bs-target="#editUserModal"
                  data-id="<?= $u['id'] ?>"
                  data-first="<?= $u['firstName'] ?>"
                  data-middle="<?= $u['middleName'] ?>"
                  data-last="<?= $u['lastName'] ?>"
                  data-username="<?= $u['username'] ?>"
                  data-email="<?= $u['email'] ?>"
                  data-contact="<?= $u['contact'] ?>"
                  data-role="<?= $u['role'] ?>"
                  data-status="<?= $u['status'] ?>"
                ><i class="bi bi-pencil"></i></button>

                <!-- DELETE BTN -->
                <a href="delete_user.php?id=<?= $u['id'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Delete this user? This cannot be undone.')">
                   <i class="bi bi-trash"></i>
                </a>

              </td>
            </tr>
            <?php endforeach; ?>

          </tbody>
        </table>
      </div>

      <?php endif; ?>

    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editUserModal">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form method="POST" action="update_user_action.php" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body row">
        <input type="hidden" name="id" id="editId">

        <div class="col-md-6 mb-3">
          <label>First Name</label>
          <input type="text" class="form-control" id="editFirst" name="firstName" required>
        </div>

        <div class="col-md-6 mb-3">
          <label>Middle Name</label>
          <input type="text" class="form-control" id="editMiddle" name="middleName">
        </div>

        <div class="col-md-6 mb-3">
          <label>Last Name</label>
          <input type="text" class="form-control" id="editLast" name="lastName" required>
        </div>

        <div class="col-md-6 mb-3">
          <label>Username</label>
          <input type="text" class="form-control" id="editUsername" name="username" required>
        </div>

        <div class="col-md-6 mb-3">
          <label>Email</label>
          <input type="email" class="form-control" id="editEmail" name="email" required>
        </div>

        <div class="col-md-6 mb-3">
          <label>Contact</label>
          <input type="text" class="form-control" id="editContact" name="contact">
        </div>

        <div class="col-md-6 mb-3">
          <label>Role</label>
          <select name="role" id="editRole" class="form-select">
            <option value="admin">Admin</option>
            <option value="trainer">Trainer</option>
            <option value="trainee">Trainee</option>
            <option value="client">Client</option>
          </select>
        </div>

        <div class="col-md-6 mb-3">
          <label>Status</label>
          <select name="status" id="editStatus" class="form-select">
            <option value="active">Active</option>
            <option value="pending">Pending</option>
          </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Update</button>
      </div>

    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){

  document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", function(){
      document.getElementById("editId").value = this.dataset.id;
      document.getElementById("editFirst").value = this.dataset.first;
      document.getElementById("editMiddle").value = this.dataset.middle;
      document.getElementById("editLast").value = this.dataset.last;
      document.getElementById("editUsername").value = this.dataset.username;
      document.getElementById("editEmail").value = this.dataset.email;
      document.getElementById("editContact").value = this.dataset.contact;
      document.getElementById("editRole").value = this.dataset.role;
      document.getElementById("editStatus").value = this.dataset.status;
    });
  });

});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
