<?php
session_start();
include '../../db_connect.php';

// ✅ Access check
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// ✅ Fetch all appointments for this user
$appointments_query = "
    SELECT a.*, 
           CONCAT(t.firstName, ' ', t.lastName) AS trainer_name
    FROM appointments a
    JOIN users t ON a.trainer_id = t.id
    WHERE a.trainee_id = ?
    ORDER BY a.date ASC, a.start_time ASC
";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments_result = $stmt->get_result();

$appointments = [];
while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
}

// ✅ Fetch trainers
$trainers_query = "SELECT id, CONCAT(firstName, ' ', lastName) AS name, role 
                   FROM users 
                   WHERE role IN ('trainer', 'trainor')";
$trainers_result = $conn->query($trainers_query);
$trainers = [];
while ($t = $trainers_result->fetch_assoc()) {
    $trainers[] = $t;
}

// ✅ Stats Calculation
$total = count($appointments);
$now = date('Y-m-d H:i:s');
$pending = $accepted = $completed = $cancelled = $declined = 0;

foreach ($appointments as $a) {
    $appt_datetime = $a['date'] . ' ' . $a['start_time'];
    switch (strtolower($a['status'])) {
        case 'pending': $pending++; break;
        case 'accepted':
            $accepted++;
            if ($appt_datetime <= $now) $completed++;
            break;
        case 'cancelled': $cancelled++; break;
        case 'declined': $declined++; break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Dashboard | GymFit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
body {
  background: #f8f9fa;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
}
main {
  margin-left: 250px;
  margin-top: 60px;
  padding: 2rem;
  height: calc(100vh - 60px);
  overflow-y: auto;
}
.card {
  border: none;
  border-radius: 12px;
}
.stats-card {
  border-radius: 10px;
  background: white;
  padding: 1.2rem;
  text-align: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  transition: transform 0.2s;
}
.stats-card:hover {
  transform: translateY(-3px);
}
.stats-card i {
  font-size: 1.8rem;
  margin-bottom: 8px;
}
.stats-card h6 {
  font-size: 0.9rem;
  color: #666;
}
.stats-card h4 {
  font-weight: 700;
  color: #b71c1c;
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>
<div class="container-fluid">
  <h2 class="fw-bold mb-3">Welcome back, <?= htmlspecialchars($user['firstName'] ?? $user['name']) ?> 👋</h2>
  <p class="text-muted mb-4">Here’s an overview of your sessions and upcoming activities.</p>

  <!-- ✅ Stats Overview -->
  <div class="row g-3 text-center mb-4">
    <div class="col-md-2 col-6">
      <div class="stats-card">
        <i class="bi bi-list-task text-primary"></i>
        <h6>Total</h6>
        <h4><?= $total ?></h4>
      </div>
    </div>
    <div class="col-md-2 col-6">
      <div class="stats-card">
        <i class="bi bi-hourglass-split text-warning"></i>
        <h6>Pending</h6>
        <h4><?= $pending ?></h4>
      </div>
    </div>
    <div class="col-md-2 col-6">
      <div class="stats-card">
        <i class="bi bi-check2-circle text-success"></i>
        <h6>Accepted</h6>
        <h4><?= $accepted ?></h4>
      </div>
    </div>
    <div class="col-md-2 col-6">
      <div class="stats-card">
        <i class="bi bi-flag text-info"></i>
        <h6>Completed</h6>
        <h4><?= $completed ?></h4>
      </div>
    </div>
    <div class="col-md-2 col-6">
      <div class="stats-card">
        <i class="bi bi-x-circle text-danger"></i>
        <h6>Cancelled</h6>
        <h4><?= $cancelled ?></h4>
      </div>
    </div>
    <div class="col-md-2 col-6">
      <div class="stats-card">
        <i class="bi bi-hand-thumbs-down text-secondary"></i>
        <h6>Declined</h6>
        <h4><?= $declined ?></h4>
      </div>
    </div>
  </div>

  <!-- ✅ Accepted Sessions -->
  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0">Accepted Sessions</h5>
          <small>Upcoming gym appointments</small>
        </div>
        <div class="card-body">
          <?php
          $has_upcoming = false;
          foreach ($appointments as $a):
            $appt_time = $a['date'] . ' ' . $a['start_time'];
            if (strtolower($a['status']) === 'accepted' && $appt_time > $now):
              $has_upcoming = true; ?>
              <div class="border rounded p-3 mb-3 bg-light">
                <strong>Session with <?= htmlspecialchars($a['trainer_name']) ?></strong><br>
                <small><?= date("M d, Y", strtotime($a['date'])) ?> — <?= date("h:i A", strtotime($a['start_time'])) ?> to <?= date("h:i A", strtotime($a['end_time'])) ?></small><br>
                <span class="badge bg-<?= $a['is_paid'] ? 'success' : 'warning text-dark' ?>"><?= $a['is_paid'] ? 'Paid' : 'Unpaid' ?></span>
                <span class="badge bg-dark"><?= ucfirst($a['status']) ?></span>
              </div>
          <?php endif; endforeach; ?>

          <?php if (!$has_upcoming): ?>
            <div class="text-center">
              <p class="text-muted">No upcoming sessions yet.</p>
              <a href="client_schedule.php" class="btn btn-primary btn-sm">Book a Session</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

   <!-- ✅ Trainers Section -->
<div class="col-md-6 mb-4">
  <div class="card shadow-sm">
    <div class="card-header bg-danger text-white">
      <h5 class="mb-0">Available Trainers</h5>
      <small>Book your preferred trainer</small>
    </div>
    <div class="card-body">
      <?php if (count($trainers) > 0): ?>
        <?php foreach (array_slice($trainers, 0, 3) as $trainer): ?>
          <div class="border rounded p-3 mb-3 d-flex justify-content-between align-items-center">
            <div>
              <strong><?= htmlspecialchars($trainer['name']) ?></strong><br>
              <small class="text-muted"><?= ucfirst($trainer['role']) ?></small>
            </div>
            <!-- Updated Book button with trainer_id parameter -->
            <a href="client_schedule.php?trainer_id=<?= $trainer['id'] ?>" 
               class="btn btn-outline-danger btn-sm">
              Book
            </a>
          </div>
        <?php endforeach; ?>
        <?php if (count($trainers) > 3): ?>
          <a href="client_schedule.php" class="btn btn-outline-dark w-100">View All Trainers</a>
        <?php endif; ?>
      <?php else: ?>
        <p class="text-center text-muted">No trainers available at the moment.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Dropdown + Logout Confirmation -->
<script>
document.addEventListener("DOMContentLoaded", function () {

  const logoutBtn = document.querySelector(".logout-btn");

  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();

      Swal.fire({
        title: "Logout Confirmation",
        text: "Are you sure you want to log out?",
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

<!-- Bootstrap 5 JS (Required for dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
