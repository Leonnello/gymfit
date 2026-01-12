<?php
session_start();
include '../../db_connect.php';

// ✅ Access Check
if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

// --- Filter Type ---
$filter = $_GET['filter'] ?? 'monthly';
switch ($filter) {
  case 'daily':
    $labelFormat = "%Y-%m-%d";
    $intervalLabel = "Daily";
    $dateFormat = "DATE(date)";
    break;
  case 'weekly':
    $labelFormat = "%x-W%v";
    $intervalLabel = "Weekly";
    $dateFormat = "YEARWEEK(date)";
    break;
  case 'yearly':
    $labelFormat = "%Y";
    $intervalLabel = "Yearly";
    $dateFormat = "YEAR(date)";
    break;
  default:
    $labelFormat = "%Y-%m";
    $intervalLabel = "Monthly";
    $dateFormat = "DATE_FORMAT(date, '%Y-%m')";
    break;
}

// --- Revenue Query - FIXED ---
$revenueQuery = "
  SELECT DATE_FORMAT(date, '$labelFormat') AS period,
         SUM(amount) AS total_revenue
  FROM appointments
  WHERE is_paid = 1
  GROUP BY period
  ORDER BY date ASC
";
$revenueResult = $conn->query($revenueQuery);

$labels = [];
$revenues = [];
$totalRevenue = 0;

if ($revenueResult && $revenueResult->num_rows > 0) {
  while ($row = $revenueResult->fetch_assoc()) {
    $labels[] = $row['period'];
    $revenues[] = (float)$row['total_revenue'];
    $totalRevenue += (float)$row['total_revenue'];
  }
} else {
  // If no data, show some default values to prevent chart errors
  $labels[] = 'No Data';
  $revenues[] = 0;
}

// --- Trainer Performance ---
$trainerQuery = "
  SELECT 
    CONCAT(u.firstName, ' ', u.lastName) AS trainer_name,
    COUNT(a.id) AS sessions,
    SUM(a.amount) AS revenue
  FROM appointments a
  JOIN users u ON a.trainer_id = u.id
  WHERE a.is_paid = 1
  GROUP BY a.trainer_id
  ORDER BY revenue DESC
";
$trainerResult = $conn->query($trainerQuery);

// --- Users by Role ---
$userQuery = "SELECT role, COUNT(*) AS total FROM users GROUP BY role";
$userResult = $conn->query($userQuery);

// --- Equipment Status ---
$equipmentQuery = "SELECT status, COUNT(*) AS total FROM equipment GROUP BY status";
$equipmentResult = $conn->query($equipmentQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Reports | GymFit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
      overflow-x: hidden;
    }
    .main-content {
      margin-left: 250px;
      padding: 20px;
    }
    .chart-container {
      position: relative;
      height: 400px;
    }
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid">
    <h2 class="mb-4">📊 Admin Reports Dashboard</h2>

    <!-- Revenue Summary -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card shadow-sm text-center p-3">
          <h6 class="text-muted">Total Revenue</h6>
          <h3 class="fw-bold text-success">₱<?= number_format($totalRevenue, 2) ?></h3>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card shadow-sm text-center p-3">
          <h6 class="text-muted">Top Trainer</h6>
          <?php
          if ($trainerResult && $trainerResult->num_rows > 0) {
            $topTrainer = $trainerResult->fetch_assoc();
            echo "<h4 class='fw-bold'>{$topTrainer['trainer_name']}</h4><p>₱" . number_format($topTrainer['revenue'], 2) . "</p>";
            $trainerResult->data_seek(0);
          } else {
            echo "<p>No Data</p>";
          }
          ?>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card shadow-sm text-center p-3">
          <h6 class="text-muted">Active Trainers</h6>
          <h3 class="fw-bold"><?= $trainerResult ? $trainerResult->num_rows : 0 ?></h3>
        </div>
      </div>
    </div>

    <!-- Revenue Chart -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <strong>📈 <?= $intervalLabel ?> Revenue Trend</strong>
        <form method="GET" class="d-flex align-items-center">
          <label class="me-2 fw-bold">View:</label>
          <select name="filter" class="form-select form-select-sm w-auto me-2" onchange="this.form.submit()">
            <option value="daily" <?= $filter == 'daily' ? 'selected' : '' ?>>Daily</option>
            <option value="weekly" <?= $filter == 'weekly' ? 'selected' : '' ?>>Weekly</option>
            <option value="monthly" <?= $filter == 'monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="yearly" <?= $filter == 'yearly' ? 'selected' : '' ?>>Yearly</option>
          </select>
        </form>
      </div>
      <div class="card-body">
        <div class="chart-container">
          <canvas id="revenueChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Trainer Performance -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-dark text-white">
        <strong>🏋️ Trainer Performance</strong>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-secondary">
            <tr>
              <th>Trainer</th>
              <th>Sessions</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($trainerResult && $trainerResult->num_rows > 0) {
              while ($row = $trainerResult->fetch_assoc()): 
            ?>
            <tr>
              <td><?= htmlspecialchars($row['trainer_name']) ?></td>
              <td><?= $row['sessions'] ?></td>
              <td>₱<?= number_format($row['revenue'], 2) ?></td>
            </tr>
            <?php 
              endwhile; 
            } else {
              echo '<tr><td colspan="3" class="text-center">No trainer data available</td></tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Users and Equipment -->
    <div class="row">
      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <strong>👥 Users by Role</strong>
          </div>
          <div class="card-body">
            <table class="table table-bordered text-center">
              <thead class="table-light">
                <tr>
                  <th>Role</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                if ($userResult && $userResult->num_rows > 0) {
                  while ($row = $userResult->fetch_assoc()): 
                ?>
                <tr>
                  <td><?= ucfirst($row['role']) ?></td>
                  <td><?= $row['total'] ?></td>
                </tr>
                <?php 
                  endwhile; 
                } else {
                  echo '<tr><td colspan="2" class="text-center">No user data available</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-success text-white">
            <strong>🔧 Equipment Status</strong>
          </div>
          <div class="card-body">
            <table class="table table-bordered text-center">
              <thead class="table-light">
                <tr>
                  <th>Status</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                if ($equipmentResult && $equipmentResult->num_rows > 0) {
                  while ($row = $equipmentResult->fetch_assoc()): 
                ?>
                <tr>
                  <td><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></td>
                  <td><?= $row['total'] ?></td>
                </tr>
                <?php 
                  endwhile; 
                } else {
                  echo '<tr><td colspan="2" class="text-center">No equipment data available</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ChartJS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const ctx = document.getElementById('revenueChart').getContext('2d');
  const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: '<?= $intervalLabel ?> Revenue (₱)',
        data: <?= json_encode($revenues) ?>,
        borderColor: '#dc3545',
        backgroundColor: 'rgba(220, 53, 69, 0.1)',
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointBackgroundColor: '#dc3545',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          display: true, 
          position: 'top',
          labels: {
            font: {
              size: 14
            }
          }
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function(context) {
              return `₱${context.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            maxRotation: 45,
            minRotation: 45
          }
        },
        y: { 
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '₱' + value.toLocaleString('en-PH');
            }
          }
        }
      },
      interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
      }
    }
  });
});
</script>

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

</body>
</html> 