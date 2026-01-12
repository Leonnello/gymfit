<?php
session_start();
include '../../db_connect.php';

// ✅ Access Check
if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
  header("Location: index.php");
  exit;
}

// ✅ Dashboard Data Initialization
$totalRevenue = 0;
$pendingPayments = 0;
$pendingSignUps = 0;
$recentUsers = [];

try {
  // ✅ Fetch appointments data
  $appointmentsQuery = $conn->query("SELECT * FROM appointments");
  $appointments = $appointmentsQuery->fetch_all(MYSQLI_ASSOC);

  // ✅ Compute total revenue and pending payments based on `amount`
  foreach ($appointments as $a) {
    if (!empty($a['is_paid']) && $a['is_paid'] == 1) {
      $totalRevenue += floatval($a['amount']);
    } else {
      $pendingPayments++;
    }
  }

  // ✅ Sign-up requests
  $signupQuery = $conn->query("SELECT * FROM signup_requests");
  $signupRequests = $signupQuery->fetch_all(MYSQLI_ASSOC);
  $pendingSignUps = count($signupRequests);

  // ✅ Recent Sign-ups (past 2 weeks)
  $twoWeeksAgo = date("Y-m-d H:i:s", strtotime("-14 days"));
  foreach ($signupRequests as $s) {
    if ($s['createdAt'] >= $twoWeeksAgo) {
      $recentUsers[] = $s;
    }
  }

  usort($recentUsers, fn($a, $b) => strtotime($b['createdAt']) - strtotime($a['createdAt']));
  $recentUsers = array_slice($recentUsers, 0, 5);
} catch (Exception $e) {
  echo "<p>Error loading dashboard: " . $e->getMessage() . "</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | GymFit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: #f8f9fa;
      overflow-x: hidden;
    }
    .main-content {
      margin-left: 250px;
      margin-top: 56px;
      padding: 30px;
      min-height: 100vh;
      background-color: #f8f9fa;
    }
    .card {
      border-radius: 16px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }
    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }
    .dashboard-header {
      display: flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #b11b1b, #8b0000);
      color: white;
      padding: 15px 25px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .dashboard-header i {
      font-size: 2rem;
    }
    .icon-box {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      color: white;
    }
    .icon-revenue { background: linear-gradient(135deg, #28a745, #218838); }
    .icon-signup { background: linear-gradient(135deg, #007bff, #0056b3); }
    .icon-report { background: linear-gradient(135deg, #6f42c1, #4b2885); }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- ✅ MAIN CONTENT -->
<div class="main-content">
  <div class="container-fluid">

    <!-- Header -->
    <div class="dashboard-header mb-4">
      <i class="bi bi-person-vcard-fill"></i>
      <div>
        <h3 class="mb-0 fw-bold">Admin Dashboard</h3>
        <small>Welcome back, <strong><?= htmlspecialchars($_SESSION['user']['firstName'] ?? 'Admin') ?></strong> 👋</small>
      </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="row g-4 mb-5">
      <!-- Total Revenue -->
      <div class="col-md-4">
        <div class="card p-4">
          <div class="d-flex align-items-center">
            <div class="icon-box icon-revenue me-3">
              <i class="bi bi-cash-stack"></i>
            </div>
            <div>
              <h6 class="text-muted">Total Revenue</h6>
              <h3 class="fw-bold text-success">₱<?= number_format($totalRevenue, 2) ?></h3>
              <small class="text-secondary"><?= $pendingPayments ?> pending payments</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Sign-up Requests -->
      <div class="col-md-4">
        <a href="signup_requests.php" class="text-decoration-none text-dark">
          <div class="card p-4">
            <div class="d-flex align-items-center">
              <div class="icon-box icon-signup me-3">
                <i class="bi bi-person-plus-fill"></i>
              </div>
              <div>
                <h6 class="text-muted">Sign-up Requests</h6>
                <h3 class="fw-bold"><?= $pendingSignUps ?></h3>
                <small class="text-secondary">Pending account approvals</small>
              </div>
            </div>
          </div>
        </a>
      </div>

      <!-- Reports -->
      <div class="col-md-4">
        <a href="admin_reports.php" class="text-decoration-none text-dark">
          <div class="card p-4">
            <div class="d-flex align-items-center">
              <div class="icon-box icon-report me-3">
                <i class="bi bi-bar-chart-line-fill"></i>
              </div>
              <div>
                <h6 class="text-muted">Reports Overview</h6>
                <h3 class="fw-bold">📊</h3>
                <small class="text-secondary">View performance reports</small>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>

<!-- Recent Sign-ups -->
<div class="card mb-5 p-4">
  <h5><i class="bi bi-people-fill text-danger me-2"></i> Recent Sign-ups</h5>
  <p class="text-muted mb-3">New account requests from the past 2 weeks</p>

  <?php if (empty($recentUsers)): ?>
    <div class="text-center py-4 text-secondary">
      <i class="bi bi-inbox fs-2 d-block mb-2"></i>
      <p>No recent sign-ups</p>
    </div>
  <?php else: ?>
    <div class="list-group">
      <?php foreach ($recentUsers as $user): ?>
        <a href="signup_requests.php?id=<?= htmlspecialchars($user['id']) ?>" 
           class="list-group-item list-group-item-action d-flex align-items-center justify-content-between border-0 border-bottom">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" 
                 style="width:45px; height:45px; font-size:1.2rem;">
              <i class="bi bi-person-fill"></i>
            </div>
            <div>
              <strong><?= htmlspecialchars(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')) ?></strong><br>
              <span class="badge bg-secondary"><?= htmlspecialchars($user['role'] ?? 'Pending') ?></span>
              <small class="text-muted ms-2"><?= date("M d, Y", strtotime($user['createdAt'])) ?></small><br>
              <small class="text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></small>
            </div>
          </div>
          <i class="bi bi-chevron-right text-muted"></i>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>


    <!-- Quick Actions -->
    <div class="card p-4 mb-5">
      <h5><i class="bi bi-lightning-charge-fill text-warning me-2"></i> Quick Actions</h5>
      <p class="text-muted">Manage your gym operations quickly</p>
      <div class="d-grid gap-3">
        <a href="admin_payments.php" class="btn btn-success"><i class="bi bi-cash-coin me-2"></i> View Payments</a>
        <a href="signup_requests.php" class="btn btn-primary"><i class="bi bi-person-lines-fill me-2"></i> View Sign-up Requests</a>
        <a href="admin_reports.php" class="btn btn-dark"><i class="bi bi-bar-chart-line-fill me-2"></i> View Reports</a>
      </div>
    </div>

  </div>
</div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
