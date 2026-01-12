<?php
include '../../db_connect.php';
session_start();

// ✅ Check if trainer/admin is logged in
if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$user = $_SESSION['user'];

// ✅ Fetch pending signup requests (MySQLi version)
$query = "SELECT id, firstName, email, username, createdAt, status 
          FROM signup_requests 
          ORDER BY createdAt DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signup Requests - GymFit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding-left: 250px;
      background: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }

    .content {
      padding: 2rem;
    }

    .page-title {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      color: #b71c1c;
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .table-container {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 1.5rem;
    }

    .table th {
      background-color: #b71c1c;
      color: #fff;
    }

    .btn-approve {
      background: #28a745;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
    }

    .btn-decline {
      background: #dc3545;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
    }

    .btn-approve:hover {
      background: #218838;
    }

    .btn-decline:hover {
      background: #c82333;
    }
  </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

  <div class="content">
    <div class="page-title">
      <i class="bi bi-person-badge-fill"></i> Signup Requests
    </div>

    <div class="table-container">
      <?php if (count($requests) > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center">
            <thead>
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Username</th>
                <th>Status</th>
                <th>Date Requested</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($requests as $index => $req): ?>
                <tr>
                  <td><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars($req['firstName']) ?></td>
                  <td><?= htmlspecialchars($req['email']) ?></td>
                  <td><?= htmlspecialchars($req['username']) ?></td>
                  <td>
                    <?php if ($req['status'] == 'Pending'): ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                    <?php elseif ($req['status'] == 'Approved'): ?>
                      <span class="badge bg-success">Approved</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Declined</span>
                    <?php endif; ?>
                  </td>
                  <td><?= date('M d, Y h:i A', strtotime($req['createdAt'])) ?></td>
                  <td>
                    <form action="signup_request_action.php" method="POST" class="d-inline">
                      <input type="hidden" name="id" value="<?= $req['id'] ?>">
                      <button type="submit" name="approve" class="btn-approve">
                        <i class="bi bi-check-circle"></i>
                      </button>
                    </form>
                    <form action="signup_request_action.php" method="POST" class="d-inline">
                      <input type="hidden" name="id" value="<?= $req['id'] ?>">
                      <button type="submit" name="decline" class="btn-decline">
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-secondary text-center">No signup requests found.</div>
      <?php endif; ?>
    </div>
  </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const logoutBtn = document.querySelector(".logout-btn");

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
});
</script>

</html>
