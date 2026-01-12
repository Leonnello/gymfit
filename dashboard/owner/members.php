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
        str_contains(strtolower($user['full_name']), strtolower($searchQuery)) ||
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
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
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
                                    <a href="edit_member.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <a href="delete_member.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-3">No members found</td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
