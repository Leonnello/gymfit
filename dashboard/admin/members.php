<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Fetch all trainees
$role = 'trainee';
$usersResult = $conn->query("SELECT * FROM users WHERE role='$role' ORDER BY created_at DESC");
$users = [];
while($row = $usersResult->fetch_assoc()){
    $users[] = $row;
}

// Handle search
$searchQuery = $_GET['search'] ?? '';
$membershipStatus = $_GET['status'] ?? 'all';
$filteredUsers = array_filter($users, function($user) use($searchQuery, $membershipStatus){
    $matchesSearch = empty($searchQuery) || 
        str_contains(strtolower($user['firstName']), strtolower($searchQuery)) ||
        str_contains(strtolower($user['email']), strtolower($searchQuery)) ||
        str_contains(strtolower($user['username']), strtolower($searchQuery));

    $matchesStatus = $membershipStatus === 'all' || $user['status'] === $membershipStatus;

    return $matchesSearch && $matchesStatus;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Owner Members | GymFit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background: #f8f9fa;
}

.card {
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

#sidebar {
    width: 250px;
    position: fixed;
    top: 56px; /* navbar height */
    bottom: 0;
    left: 0;
    overflow-y: auto;
    background: #fff;
    border-right: 1px solid #dee2e6;
    z-index: 1020;
}

main {
    margin-left: 250px; /* same as sidebar width */
    margin-top: 56px; /* navbar height */
    height: calc(100vh - 56px);
    overflow-y: auto;
    padding: 20px;
}

.avatar-img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%;
}

.id-img {
    width: 80px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}
</style>
</head>
<body>

<!-- Navbar -->
<div id="sidebar">
    <?php include 'includes/navbar.php'; ?>
</div>
<!-- Sidebar -->
<div id="sidebar">
    <?php include 'includes/sidebar.php'; ?>
</div>

<!-- Main Content -->
<main>
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Member Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
            <i class="bi bi-person-plus"></i> Add Member
        </button>
    </div>

    <!-- Search & Filter -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Search members..." value="<?= htmlspecialchars($searchQuery) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="all" <?= $membershipStatus==='all' ? 'selected' : '' ?>>All Members</option>
                <option value="active" <?= $membershipStatus==='active' ? 'selected' : '' ?>>Active</option>
                <option value="pending" <?= $membershipStatus==='pending' ? 'selected' : '' ?>>Pending</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary w-100">Filter</button>
        </div>
    </form>

    <!-- Members Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Members</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($filteredUsers)): ?>
                            <?php foreach($filteredUsers as $user): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $avatarSrc = (!empty($user['avatar']) && $user['avatar'] !== 'null') 
                                        ? '../../' . htmlspecialchars($user['avatar']) 
                                        : 'https://via.placeholder.com/50/007bff/ffffff?text=AV';
                                    ?>
                                    <img src="<?= $avatarSrc ?>" class="avatar-img" alt="Avatar" onerror="this.src='https://via.placeholder.com/50/6c757d/ffffff?text=AV'">
                                </td>
                                <td><?= htmlspecialchars($user['firstName'] . ' ' . $user['middleName'] . ' ' . $user['lastName']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= date("M d, Y", strtotime($user['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $user['status']==='active' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_member.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    <button 
                                        class="btn btn-sm btn-outline-secondary editBtn"
                                        data-id="<?= $user['id'] ?>"
                                        data-first="<?= htmlspecialchars($user['firstName']) ?>"
                                        data-middle="<?= htmlspecialchars($user['middleName']) ?>"
                                        data-last="<?= htmlspecialchars($user['lastName']) ?>"
                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                        data-contact="<?= htmlspecialchars($user['contact']) ?>"
                                        data-role="<?= htmlspecialchars($user['role']) ?>"
                                        data-avatar="<?= htmlspecialchars($user['avatar']) ?>"
                                        data-idimage="<?= htmlspecialchars($user['idImage']) ?>"
                                        data-status="<?= htmlspecialchars($user['status']) ?>"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="delete_member.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">No members found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="add_member_action.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active">Active</option>
            <option value="pending">Pending</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Member</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="edit_member_action.php" enctype="multipart/form-data" class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Edit Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" name="id" id="edit_id">

        <div class="row g-3">

          <!-- Avatar -->
          <div class="col-md-4 text-center">
            <img id="edit_avatar_preview" src="" 
                 class="rounded-circle mb-2" width="140" height="140"
                 onerror="this.src='https://via.placeholder.com/140/007bff/ffffff?text=AVATAR'">
            <p class="fw-bold">Avatar</p>
            <input type="file" name="avatar" class="form-control" accept="image/*">
          </div>

          <!-- Member Details -->
          <div class="col-md-8">

            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">First Name</label>
                <input type="text" name="firstName" id="edit_firstName" class="form-control" required>
              </div>

              <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middleName" id="edit_middleName" class="form-control">
              </div>

              <div class="col-md-4">
                <label class="form-label">Last Name</label>
                <input type="text" name="lastName" id="edit_lastName" class="form-control" required>
              </div>
            </div>

            <div class="mt-2">
              <label class="form-label">Username</label>
              <input type="text" name="username" id="edit_username" class="form-control" required>
            </div>

            <div class="mt-2">
              <label class="form-label">Email</label>
              <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>

            <div class="mt-2">
              <label class="form-label">Contact</label>
              <input type="text" name="contact" id="edit_contact" class="form-control">
            </div>

            <div class="mt-2">
              <label class="form-label">Role</label>
              <select name="role" id="edit_role" class="form-select">
                <option value="trainee">Trainee</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>

            <div class="mt-2">
              <label class="form-label">Status</label>
              <select name="status" id="edit_status" class="form-select">
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

          </div>
        </div>

        <hr>

        <!-- ID Image -->
        <h6>ID Image</h6>
        <img id="edit_idImage_preview" src="" 
             class="img-fluid rounded mb-2" style="max-height: 250px;"
             onerror="this.src='https://via.placeholder.com/300x150/28a745/ffffff?text=ID+IMAGE'">
        <input type="file" name="idImage" class="form-control" accept="image/*">

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Member</button>
      </div>

    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const logoutBtn = document.querySelector(".logout-btn");

  if (logoutBtn) {
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
<script>
document.addEventListener("DOMContentLoaded", () => {
    const editButtons = document.querySelectorAll(".editBtn");

    editButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            // Set form values
            document.getElementById("edit_id").value = btn.dataset.id;
            document.getElementById("edit_firstName").value = btn.dataset.first;
            document.getElementById("edit_middleName").value = btn.dataset.middle;
            document.getElementById("edit_lastName").value = btn.dataset.last;
            document.getElementById("edit_username").value = btn.dataset.username;
            document.getElementById("edit_email").value = btn.dataset.email;
            document.getElementById("edit_contact").value = btn.dataset.contact;
            document.getElementById("edit_role").value = btn.dataset.role;
            document.getElementById("edit_status").value = btn.dataset.status;

            // FIXED: Handle image paths correctly
            const avatarSrc = btn.dataset.avatar && btn.dataset.avatar !== 'null' && btn.dataset.avatar !== '' 
                ? '../../' + btn.dataset.avatar 
                : 'https://via.placeholder.com/140/007bff/ffffff?text=AVATAR';
            
            const idImageSrc = btn.dataset.idimage && btn.dataset.idimage !== 'null' && btn.dataset.idimage !== '' 
                ? '../../' + btn.dataset.idimage 
                : 'https://via.placeholder.com/300x150/28a745/ffffff?text=ID+IMAGE';

            console.log('Avatar Source:', avatarSrc);
            console.log('ID Image Source:', idImageSrc);

            document.getElementById("edit_avatar_preview").src = avatarSrc;
            document.getElementById("edit_idImage_preview").src = idImageSrc;

            new bootstrap.Modal(document.getElementById("editMemberModal")).show();
        });
    });
});
</script>

</body>
</html>