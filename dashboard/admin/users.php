<?php
// ADD THESE LINES AT THE VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include '../../db_connect.php';

$roleFilter = isset($_GET['role']) && $_GET['role'] !== 'all' ? $_GET['role'] : 'all';

try {
    if ($roleFilter === 'all') {
        $stmt = $conn->query("SELECT * FROM users ORDER BY role, lastName ASC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE role = ? ORDER BY lastName ASC");
        $stmt->bind_param("s", $roleFilter);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    $users = $roleFilter === 'all' ? $stmt->fetch_all(MYSQLI_ASSOC) : $result->fetch_all(MYSQLI_ASSOC);

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
    table { vertical-align: middle; }
    .role-badge { text-transform: capitalize; font-size: 0.85rem; }
    .role-badge.admin { background-color: #212529; }
    .role-badge.trainer { background-color: #0d6efd; }
    .role-badge.trainee { background-color: #dc3545; }
    .role-badge.client { background-color: #198754; }
    img.avatar-img { width:50px; height:50px; object-fit:cover; border-radius:50%; }
    img.id-img { width:80px; height:50px; object-fit:cover; }
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold">👥 Manage Users</h2>
      <div class="d-flex align-items-center gap-3">
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
        <!-- ADD CREATE ACCOUNT BUTTON -->
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
          <i class="bi bi-plus-circle"></i> Create Account
        </button>
      </div>
    </div>

    <div class="card p-4">
      <?php if (empty($users)): ?>
        <p class="text-center text-muted py-5 mb-0">No users found for this role.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Created At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; foreach ($users as $user): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars(trim($user['firstName'].' '.$user['middleName'].' '.$user['lastName'])) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><span class="badge role-badge <?= htmlspecialchars($user['role']) ?>"><?= htmlspecialchars($user['role']) ?></span></td>
              <td><?= date("M d, Y", strtotime($user['created_at'] ?? 'now')) ?></td>
              <td>
                <!-- VIEW MODAL BUTTON -->
                <button class="btn btn-sm btn-outline-primary viewBtn" 
                  data-bs-toggle="modal" data-bs-target="#viewUserModal"
                  data-id="<?= $user['id'] ?>"
                  data-firstname="<?= htmlspecialchars($user['firstName']) ?>"
                  data-middlename="<?= htmlspecialchars($user['middleName']) ?>"
                  data-lastname="<?= htmlspecialchars($user['lastName']) ?>"
                  data-username="<?= htmlspecialchars($user['username']) ?>"
                  data-email="<?= htmlspecialchars($user['email']) ?>"
                  data-contact="<?= htmlspecialchars($user['contact']) ?>"
                  data-role="<?= htmlspecialchars($user['role']) ?>"
                  data-status="<?= htmlspecialchars($user['status']) ?>"
                  data-avatar="<?= htmlspecialchars($user['avatar']) ?>"
                  data-idimage="<?= htmlspecialchars($user['idImage']) ?>"
                  data-created="<?= $user['created_at'] ?>"
                >
                  <i class="bi bi-eye"></i>
                </button>

                <!-- EDIT MODAL BUTTON -->
                <button class="btn btn-sm btn-outline-success editBtn" 
                  data-bs-toggle="modal" data-bs-target="#editUserModal"
                  data-id="<?= $user['id'] ?>"
                  data-firstname="<?= htmlspecialchars($user['firstName']) ?>"
                  data-middlename="<?= htmlspecialchars($user['middleName']) ?>"
                  data-lastname="<?= htmlspecialchars($user['lastName']) ?>"
                  data-username="<?= htmlspecialchars($user['username']) ?>"
                  data-email="<?= htmlspecialchars($user['email']) ?>"
                  data-contact="<?= htmlspecialchars($user['contact']) ?>"
                  data-role="<?= htmlspecialchars($user['role']) ?>"
                  data-status="<?= htmlspecialchars($user['status']) ?>"
                  data-avatar="<?= htmlspecialchars($user['avatar']) ?>"
                  data-idimage="<?= htmlspecialchars($user['idImage']) ?>"
                >
                  <i class="bi bi-pencil"></i>
                </button>
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
    <a href="delete_acc.php?id=<?= $user['id'] ?>" 
       class="btn btn-sm btn-outline-danger" 
       onclick="return confirm('Are you sure you want to delete this account?')">
       <i class="bi bi-trash"></i>
    </a>
<?php else: ?>
    <button class="btn btn-sm btn-outline-secondary" disabled title="Only admin can delete">
        <i class="bi bi-trash"></i>
    </button>
<?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

</div>

<!-- CREATE USER MODAL -->
<div class="modal fade" id="createUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form id="createUserForm" method="POST" action="create_user_action.php" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create New User Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row">
        <div class="col-md-6 mb-3">
          <label class="form-label">First Name <span class="text-danger">*</span></label>
          <input type="text" name="firstName" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Middle Name</label>
          <input type="text" name="middleName" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Last Name <span class="text-danger">*</span></label>
          <input type="text" name="lastName" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Username <span class="text-danger">*</span></label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Contact Number</label>
          <input type="text" name="contact" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Password <span class="text-danger">*</span></label>
          <input type="password" name="password" class="form-control" required minlength="6">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
          <input type="password" name="confirm_password" class="form-control" required minlength="6">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Role <span class="text-danger">*</span></label>
          <select name="role" class="form-select" required>
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="trainer">Trainer</option>
            <option value="trainee">Trainee</option>
            <option value="client" selected>Client</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Status <span class="text-danger">*</span></label>
          <select name="status" class="form-select" required>
            <option value="active" selected>Active</option>
            <option value="pending">Pending</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Avatar Image</label>
          <input type="file" name="avatar" class="form-control" accept="image/*">
          <small class="text-muted">Optional: Upload a profile picture</small>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">ID Image <span class="text-danger">*</span></label>
          <input type="file" name="idImage" class="form-control" accept="image/*" required>
          <small class="text-muted">Required: Upload a valid ID image</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Account</button>
      </div>
    </form>
  </div>
</div>

<!-- VIEW USER MODAL -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">View User Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row">
        <div class="col-md-4 text-center">
          <img id="viewAvatar" class="avatar-img mb-2" src="" alt="Avatar">
          <p>ID Image:</p>
          <img id="viewIdImage" class="id-img" src="" alt="ID Image">
        </div>
        <div class="col-md-8">
          <ul class="list-group list-group-flush">
            <li class="list-group-item"><strong>ID:</strong> <span id="viewId"></span></li>
            <li class="list-group-item"><strong>Full Name:</strong> <span id="viewFullName"></span></li>
            <li class="list-group-item"><strong>Username:</strong> <span id="viewUsername"></span></li>
            <li class="list-group-item"><strong>Email:</strong> <span id="viewEmail"></span></li>
            <li class="list-group-item"><strong>Contact:</strong> <span id="viewContact"></span></li>
            <li class="list-group-item"><strong>Role:</strong> <span id="viewRole"></span></li>
            <li class="list-group-item"><strong>Status:</strong> <span id="viewStatus"></span></li>
            <li class="list-group-item"><strong>Created At:</strong> <span id="viewCreated"></span></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form id="editUserForm" method="POST" action="update_user_action.php" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row">
        <input type="hidden" name="id" id="editId">
        <div class="col-md-6 mb-3">
          <label class="form-label">First Name</label>
          <input type="text" name="firstName" id="editFirstName" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Middle Name</label>
          <input type="text" name="middleName" id="editMiddleName" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Last Name</label>
          <input type="text" name="lastName" id="editLastName" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" id="editUsername" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" id="editEmail" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Contact</label>
          <input type="text" name="contact" id="editContact" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Role</label>
          <select name="role" id="editRole" class="form-select" required>
            <option value="admin">Admin</option>
            <option value="trainer">Trainer</option>
            <option value="trainee">Trainee</option>
            <option value="client">Client</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Status</label>
          <select name="status" id="editStatus" class="form-select" required>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Avatar</label>
          <input type="file" name="avatar" id="editAvatar" class="form-control">
          <img id="editAvatarPreview" class="avatar-img mt-2" src="" alt="Avatar">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">ID Image</label>
          <input type="file" name="idImage" id="editIdImage" class="form-control">
          <img id="editIdImagePreview" class="id-img mt-2" src="" alt="ID Image">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Update User</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function(){

  // Password confirmation validation for create form
  const createForm = document.getElementById('createUserForm');
  createForm.addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirmPassword) {
      e.preventDefault();
      alert('Passwords do not match!');
      return false;
    }
    
    if (password.length < 6) {
      e.preventDefault();
      alert('Password must be at least 6 characters long!');
      return false;
    }
  });

  // VIEW MODAL
  const viewModal = document.getElementById('viewUserModal');
  viewModal.addEventListener('show.bs.modal', function(event){
    const button = event.relatedTarget;
    document.getElementById('viewId').innerText = button.getAttribute('data-id');
    document.getElementById('viewFullName').innerText = button.getAttribute('data-firstname') + ' ' + button.getAttribute('data-middlename') + ' ' + button.getAttribute('data-lastname');
    document.getElementById('viewUsername').innerText = button.getAttribute('data-username');
    document.getElementById('viewEmail').innerText = button.getAttribute('data-email');
    document.getElementById('viewContact').innerText = button.getAttribute('data-contact');
    document.getElementById('viewRole').innerText = button.getAttribute('data-role');
    document.getElementById('viewStatus').innerText = button.getAttribute('data-status');
    document.getElementById('viewCreated').innerText = button.getAttribute('data-created');
    document.getElementById('viewAvatar').src = button.getAttribute('data-avatar');
    document.getElementById('viewIdImage').src = button.getAttribute('data-idimage');
  });

  // EDIT MODAL
  const editModal = document.getElementById('editUserModal');
  editModal.addEventListener('show.bs.modal', function(event){
    const button = event.relatedTarget;
    document.getElementById('editId').value = button.getAttribute('data-id');
    document.getElementById('editFirstName').value = button.getAttribute('data-firstname');
    document.getElementById('editMiddleName').value = button.getAttribute('data-middlename');
    document.getElementById('editLastName').value = button.getAttribute('data-lastname');
    document.getElementById('editUsername').value = button.getAttribute('data-username');
    document.getElementById('editEmail').value = button.getAttribute('data-email');
    document.getElementById('editContact').value = button.getAttribute('data-contact');
    document.getElementById('editRole').value = button.getAttribute('data-role');
    document.getElementById('editStatus').value = button.getAttribute('data-status');
    document.getElementById('editAvatarPreview').src = button.getAttribute('data-avatar');
    document.getElementById('editIdImagePreview').src = button.getAttribute('data-idimage');
  });

});
</script>
</body>
</html>