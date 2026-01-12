<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];

// Fetch users
$usersResult = $conn->query("SELECT * FROM users");
$users = [];
while($row = $usersResult->fetch_assoc()){
    $users[] = $row;
}

// Fetch appointments
$appointmentsResult = $conn->query("SELECT * FROM appointments");
$appointments = [];
while($row = $appointmentsResult->fetch_assoc()){
    $appointments[] = $row;
}

// Fetch equipment
$equipmentResult = $conn->query("SELECT * FROM equipment");
$equipment = [];
while($row = $equipmentResult->fetch_assoc()){
    $equipment[] = $row;
}

// Stats calculations
$trainees = array_filter($users, fn($u) => $u['role'] === 'trainee');
$trainers = array_filter($users, fn($u) => $u['role'] === 'trainer');

$paidAppointments = array_filter($appointments, fn($a) => $a['is_paid']);
$totalRevenue = count($paidAppointments) * 50; // assuming 50 per session

// Equipment status
$equipmentStatus = [
    'available' => count(array_filter($equipment, fn($e) => $e['status'] === 'available')),
    'maintenance' => count(array_filter($equipment, fn($e) => $e['status'] === 'maintenance')),
    'out_of_order' => count(array_filter($equipment, fn($e) => $e['status'] === 'out_of_order')),
];

// Top 3 maintenance equipment
$maintenanceEquipment = array_filter($equipment, fn($e) => $e['status'] === 'maintenance' || $e['status'] === 'out_of_order');
usort($maintenanceEquipment, fn($a,$b) => strtotime($b['last_maintenance']) - strtotime($a['last_maintenance']));
$maintenanceEquipment = array_slice($maintenanceEquipment, 0, 3);

// Recent users (last 5)
usort($users, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$recentUsers = array_slice($users, 0, 5);

// Monthly stats
$firstDay = date("Y-m-01");
$lastDay = date("Y-m-t");

$newClientsThisMonth = count(array_filter($trainees, fn($t) => $t['created_at'] >= $firstDay && $t['created_at'] <= $lastDay));
$sessionsThisMonth = array_filter($appointments, fn($a) => $a['date'] >= $firstDay && $a['date'] <= $lastDay);
$completedSessions = count(array_filter($sessionsThisMonth, fn($a) => $a['status'] !== 'cancelled' && $a['status'] !== 'declined'));
$cancelledSessions = count(array_filter($sessionsThisMonth, fn($a) => $a['status'] === 'cancelled'));
$declinedSessions = count(array_filter($sessionsThisMonth, fn($a) => $a['status'] === 'declined'));
$monthlyRevenue = count(array_filter($sessionsThisMonth, fn($a) => $a['is_paid'])) * 50;

// Prepare data for graph (monthly appointments)
$monthLabels = [];
$monthlyAppointments = [];
for ($i=1; $i<=12; $i++) {
    $month = sprintf("%02d", $i);
    $monthLabels[] = date("F", strtotime("2025-$month-01"));
    $monthlyAppointments[] = count(array_filter($appointments, fn($a) => date("m", strtotime($a['date'])) == $month));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Owner Dashboard | GymFit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { 
  margin:0; 
  padding:0; 
  height:100vh; 
  overflow:hidden; 
  background:#f8f9fa;
}
main { margin-top:56px; margin-left:250px; height:calc(100vh - 56px); overflow-y:auto; padding:20px;}
.card { border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); cursor:pointer; transition:0.2s; }
.card:hover { transform:scale(1.02); }
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>
  <h2 class="mb-4">Owner Dashboard</h2>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card p-3" onclick="location.href='members.php';">
        <h6>Total Members</h6>
        <h3><?= count($trainees) ?></h3>
        <small><?= $newClientsThisMonth ?> new this month</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3" onclick="location.href='staff.php';">
        <h6>Total Trainers</h6>
        <h3><?= count($trainers) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3" onclick="location.href='appointments.php';">
        <h6>Total Appointments</h6>
        <h3><?= count($appointments) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3" onclick="location.href='payments.php';">
        <h6>Total Revenue</h6>
        <h3>₱<?= $totalRevenue ?></h3>
        <small>₱<?= $monthlyRevenue ?> this month</small>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-6">
      <div class="card p-3">
        <h6>Monthly Appointments</h6>
        <canvas id="appointmentsChart"></canvas>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card p-3">
        <h6>Equipment Alerts</h6>
        <?php if(!empty($maintenanceEquipment)): ?>
          <?php foreach($maintenanceEquipment as $eq): ?>
            <div class="border p-2 mb-2 rounded d-flex justify-content-between align-items-center">
              <div>
                <strong><?= htmlspecialchars($eq['name']) ?></strong><br>
                <small>Category: <?= htmlspecialchars($eq['category_id']) ?></small><br>
                <small>Last maintenance: <?= date("M d, Y", strtotime($eq['last_maintenance'])) ?></small>
              </div>
              <span class="badge <?= $eq['status'] === 'out_of_order' ? 'bg-danger' : 'bg-warning' ?>">
                <?= $eq['status'] === 'out_of_order' ? 'Out of Order' : 'Maintenance' ?>
              </span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted">No equipment in maintenance</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-12">
      <div class="card p-3">
        <h6>Recent Members</h6>
        <?php foreach($recentUsers as $u): ?>
          <div class="border p-2 mb-2 rounded d-flex justify-content-between align-items-center">
            <div>
              <strong><?= htmlspecialchars($u['username']) ?></strong><br>
              <small><?= htmlspecialchars($u['email']) ?></small><br>
              <small>Joined: <?= date("M d, Y", strtotime($u['created_at'])) ?></small>
            </div>
            <span class="badge bg-primary"><?= ucfirst($u['role']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</main>

<script>
const ctx = document.getElementById('appointmentsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($monthLabels) ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode($monthlyAppointments) ?>,
            backgroundColor: 'rgba(220, 53, 69, 0.7)',
            borderColor: 'rgba(220, 53, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive:true,
        plugins:{
            legend:{ display:false }
        },
        scales: {
            y: { beginAtZero:true }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
