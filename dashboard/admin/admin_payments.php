<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$search = $_GET['search'] ?? '';
$payment_status = $_GET['status'] ?? 'all';
$date_range = $_GET['range'] ?? 'all';

$query = "
  SELECT 
    a.*, 
    CONCAT(t1.firstName, ' ', t1.lastName) AS trainee_name,
    CONCAT(t2.firstName, ' ', t2.lastName) AS trainor_name
  FROM appointments a
  JOIN users t1 ON a.trainee_id = t1.id
  JOIN users t2 ON a.trainer_id = t2.id
  WHERE a.status NOT IN ('cancelled','declined')
";

if (!empty($search)) {
  $query .= " AND (
    t1.firstName LIKE '%$search%' 
    OR t1.lastName LIKE '%$search%' 
    OR t2.firstName LIKE '%$search%' 
    OR t2.lastName LIKE '%$search%'
  )";
}

if ($payment_status == 'paid') $query .= " AND a.is_paid = 1";
elseif ($payment_status == 'unpaid') $query .= " AND a.is_paid = 0";

if ($date_range == 'today') $query .= " AND a.date = CURDATE()";
elseif ($date_range == 'week') $query .= " AND a.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
elseif ($date_range == 'month') $query .= " AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

$result = $conn->query($query);

$total = $paid = $unpaid = 0;
$appointments = [];
while ($row = $result->fetch_assoc()) {
  $appointments[] = $row;
  $total++;
  if ($row['is_paid']) $paid++; else $unpaid++;
}
$revenueQuery = "
  SELECT SUM(amount) AS total_revenue
  FROM appointments
  WHERE is_paid = 1
  AND status NOT IN ('cancelled','declined')
";

$revenueResult = $conn->query($revenueQuery);
$revenueRow = $revenueResult->fetch_assoc();
$total_revenue = $revenueRow['total_revenue'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Payments | GymFit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: #f8f9fa;
      margin: 0;
      height: 100vh;
      overflow: hidden;
    }

    .main-content {
      margin-left: 250px;
      margin-top: 60px;
      padding: 30px;
      height: calc(100vh - 60px);
      overflow-y: auto;
    }
  </style>
</head>
<body>

  <?php include 'includes/navbar.php'; ?>
  <?php include 'includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="container-fluid">
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'paid_success'): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    ✅ Payment marked as <strong>Paid</strong> successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

      <h2 class="fw-bold mb-4">💰 Payment Management</h2>

      <!-- Stats -->
      <div class="row text-center mb-4">
        <div class="col-md-3">
          <div class="card p-3 shadow-sm">
            <h6>Total Revenue</h6>
            <h4>₱<?= number_format($total_revenue, 2) ?></h4>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card p-3 shadow-sm">
            <h6>Paid Sessions</h6>
            <h4><?= $paid ?></h4>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card p-3 shadow-sm">
            <h6>Total Sessions</h6>
            <h4><?= $total ?></h4>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card p-4 shadow-sm">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-dark">
            <tr>
              <th>Trainee</th>
              <th>Trainer</th>
              <th>Date</th>
              <th>Time</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($appointments as $a): ?>
           <tr>
  <td><?= htmlspecialchars($a['trainee_name']) ?></td>
  <td><?= htmlspecialchars($a['trainor_name']) ?></td>
  <td><?= htmlspecialchars($a['date']) ?></td>
  <td><?= htmlspecialchars($a['start_time'] . ' ' . $a['end_time']) ?></td>
  <td>₱<?= number_format($a['amount'], 2) ?></td>
  <td>
    <?php if ($a['is_paid']): ?>
      <span class="badge bg-success">Paid</span>
    <?php else: ?>
      <span class="badge bg-warning text-dark">Unpaid</span>
    <?php endif; ?>
  </td>
</tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
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
