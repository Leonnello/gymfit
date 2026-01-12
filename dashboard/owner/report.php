<?php
session_start();
include '../../db_connect.php'; // MySQLi connection

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'owner') {
    header("Location: ../../login.php");
    exit;
}

// Time range
$timeRange = $_GET['timeRange'] ?? '6m';
$months = $timeRange === '3m' ? 3 : ($timeRange === '12m' ? 12 : 6);

function getMonthArray($months) {
    $arr = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $arr[] = date('Y-m', strtotime("-$i months"));
    }
    return $arr;
}
$lastMonths = getMonthArray($months);

// Fetch data
$users = $conn->query("SELECT * FROM users")->fetch_all(MYSQLI_ASSOC);
$appointments = $conn->query("SELECT * FROM appointments")->fetch_all(MYSQLI_ASSOC);
$equipment = $conn->query("SELECT * FROM equipment")->fetch_all(MYSQLI_ASSOC);

// Paid appointments
$paidAppointments = array_filter($appointments, fn($app) => $app['is_paid']);

// Monthly revenue
$monthlyRevenue = [];
foreach ($lastMonths as $month) {
    $monthlyRevenue[$month] = array_reduce($paidAppointments, function($sum, $app) use ($month) {
        $appMonth = date('Y-m', strtotime($app['date']));
        return $appMonth === $month ? $sum + ($app['paymentAmount'] ?? 50) : $sum;
    }, 0);
}

// Revenue growth
$monthsKeys = array_keys($monthlyRevenue);
$currentMonthRev = end($monthlyRevenue);
$previousMonthRev = count($monthlyRevenue) > 1 ? prev($monthlyRevenue) : 0;
$revenueGrowth = $previousMonthRev ? (($currentMonthRev - $previousMonthRev) / $previousMonthRev) * 100 : 0;

// Revenue by trainer
$trainers = array_filter($users, fn($u) => $u['role'] === 'trainor');
$revenueByTrainer = [];
foreach ($trainers as $trainer) {
    $trainerApps = array_filter($paidAppointments, fn($a) => $a['trainerId'] == $trainer['id']);
    $totalRev = array_reduce($trainerApps, fn($sum, $a) => $sum + ($a['paymentAmount'] ?? 50), 0);
    $revenueByTrainer[] = [
        'trainerName' => $trainer['name'],
        'sessionCount' => count($trainerApps),
        'totalRevenue' => $totalRev
    ];
}

// Trainee growth
$trainees = array_filter($users, fn($u) => in_array($u['role'], ['trainee', 'client']));
$userGrowth = [];
foreach ($lastMonths as $month) {
    $count = count(array_filter($trainees, fn($u) => date('Y-m', strtotime($u['created_at'])) === $month));
    $userGrowth[] = ['month' => $month, 'count' => $count];
}

// Equipment status
$equipmentStatus = [];
foreach ($equipment as $eq) {
    $equipmentStatus[$eq['status']] = ($equipmentStatus[$eq['status']] ?? 0) + 1;
}

// Maintenance logs
$maintenanceLogs = [];
foreach ($equipment as $eq) {
    if (!empty($eq['maintenanceLogs'])) {
        $logs = json_decode($eq['maintenanceLogs'], true);
        foreach ($logs as $log) {
            $log['equipmentName'] = $eq['name'];
            $log['equipmentStatus'] = $eq['status'];
            $maintenanceLogs[] = $log;
        }
    }
}
usort($maintenanceLogs, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));
$maintenanceLogs = array_slice($maintenanceLogs, 0, 10);

function getStatusBadge($status) {
    switch ($status) {
        case 'available': return '<span class="badge bg-success">Available</span>';
        case 'maintenance': return '<span class="badge bg-warning text-dark">Maintenance</span>';
        case 'out_of_order': return '<span class="badge bg-danger">Out of Order</span>';
        default: return '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>';
    }
}

$avgSessionValue = array_sum(array_column($paidAppointments,'paymentAmount')) / max(array_sum(array_column($revenueByTrainer,'sessionCount')),1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Owner Reports | GymFit</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background:#f8f9fa;
    margin:0;
}

main {
    margin-left:250px;
    margin-top:56px;
    padding:20px;
    height:calc(100vh - 56px);
    overflow-y:auto;
}
.card {
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
.table-responsive {
    max-height:400px;
    overflow-y:auto;
}
.stats-card {
    background:white;
    border-radius:10px;
    padding:1rem;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
}
.stats-card h6 {
    color:#6c757d;
    font-size:0.9rem;
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold">📊 Reports Dashboard</h1>
    <form method="GET" class="d-flex gap-2">
        <select name="timeRange" class="form-select">
            <option value="3m" <?= $timeRange=='3m'?'selected':'' ?>>Last 3 Months</option>
            <option value="6m" <?= $timeRange=='6m'?'selected':'' ?>>Last 6 Months</option>
            <option value="12m" <?= $timeRange=='12m'?'selected':'' ?>>Last 12 Months</option>
        </select>
        <button class="btn btn-danger">Refresh</button>
    </form>
</div>

<!-- Revenue Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stats-card text-center"><h6>Total Revenue</h6><h4>₱<?= number_format(array_sum(array_column($paidAppointments,'paymentAmount') ?: [0])) ?></h4><small><?= $revenueGrowth>0?'+':'' ?><?= number_format($revenueGrowth,1) ?>% from last month</small></div></div>
    <div class="col-md-3"><div class="stats-card text-center"><h6>Monthly Revenue</h6><h4>₱<?= number_format($currentMonthRev) ?></h4><small>Current month</small></div></div>
    <div class="col-md-3"><div class="stats-card text-center"><h6>Total Sessions</h6><h4><?= array_sum(array_column($revenueByTrainer,'sessionCount')) ?></h4><small>Completed sessions</small></div></div>
    <div class="col-md-3"><div class="stats-card text-center"><h6>Avg Session Value</h6><h4>₱<?= number_format($avgSessionValue,2) ?></h4><small>Per session</small></div></div>
</div>

<!-- Tables and Charts -->
<div class="card mb-4">
    <div class="card-header fw-bold">Revenue by Trainer</div>
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead><tr><th>Trainer</th><th class="text-end">Sessions</th><th class="text-end">Revenue</th></tr></thead>
            <tbody>
                <?php if(empty($revenueByTrainer)): ?>
                <tr><td colspan="3" class="text-center">No trainer data</td></tr>
                <?php else: foreach($revenueByTrainer as $t): ?>
                <tr><td><?= htmlspecialchars($t['trainerName']) ?></td><td class="text-end"><?= $t['sessionCount'] ?></td><td class="text-end">₱<?= number_format($t['totalRevenue']) ?></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header fw-bold">Trainee Growth</div>
    <div class="card-body table-responsive">
        <table class="table table-striped mb-0">
            <thead><tr><th>Month</th><th class="text-end">New Trainees</th><th class="text-end">Total Trainees</th></tr></thead>
            <tbody><?php $total=0; foreach($userGrowth as $g): $total+=$g['count']; ?>
            <tr><td><?= date('F Y', strtotime($g['month'].'-01')) ?></td><td class="text-end"><?= $g['count'] ?></td><td class="text-end"><?= $total ?></td></tr>
            <?php endforeach; ?></tbody>
        </table>
    </div>
</div>

<!-- Charts -->
<div class="row mb-5">
    <div class="col-md-6"><div class="card p-3"><canvas id="revenueChart"></canvas></div></div>
    <div class="col-md-6"><div class="card p-3"><canvas id="traineeChart"></canvas></div></div>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const revenueChart = new Chart(document.getElementById('revenueChart'), {
    type:'line',
    data:{
        labels:<?= json_encode(array_map(fn($m)=>date('M Y',strtotime($m.'-01')),$lastMonths)) ?>,
        datasets:[{label:'Monthly Revenue (₱)',data:<?= json_encode(array_values($monthlyRevenue)) ?>,borderColor:'rgba(220,53,69,0.9)',backgroundColor:'rgba(220,53,69,0.2)',fill:true,tension:0.4}]
    },
    options:{responsive:true,plugins:{legend:{display:true}}}
});

const traineeChart = new Chart(document.getElementById('traineeChart'), {
    type:'bar',
    data:{
        labels:<?= json_encode(array_map(fn($m)=>date('M Y',strtotime($m.'-01')),$lastMonths)) ?>,
        datasets:[{label:'New Trainees',data:<?= json_encode(array_column($userGrowth,'count')) ?>,backgroundColor:'rgba(0,123,255,0.6)'}]
    },
    options:{responsive:true,scales:{y:{beginAtZero:true}}}
});
</script>
</body>
</html>
