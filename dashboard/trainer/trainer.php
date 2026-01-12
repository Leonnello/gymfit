<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// ✅ Handle Accept / Decline actions directly
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'] === 'accepted' ? 'accepted' : 'cancelled';

    $update = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $update->bind_param("si", $status, $id);
    $update->execute();

    $_SESSION['message'] = ($status === 'accepted') ? "Session Accepted & Scheduled." : "Session Cancelled.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch appointments where this user is the trainer
$appointments_query = "
    SELECT a.*, CONCAT(t.firstName,' ',t.lastName) AS trainee_name
    FROM appointments a
    JOIN users t ON a.trainee_id = t.id
    WHERE a.trainer_id = '$user_id'
    ORDER BY a.date ASC, a.start_time ASC
";
$appointments_result = $conn->query($appointments_query);
$appointments = [];
while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
}

// Fetch trainees
$trainees_query = "SELECT id, CONCAT(firstName,' ',lastName) AS name, avatar FROM users WHERE role='trainee'";
$trainees_result = $conn->query($trainees_query);
$trainees = [];
while ($t = $trainees_result->fetch_assoc()) {
    $trainees[$t['id']] = $t;
}

// Stats
$total = count($appointments);
$accepted = $completed = $cancelled = 0;
$now = date('Y-m-d H:i:s');

foreach ($appointments as $a) {
    if ($a['status'] === 'cancelled' || $a['status'] === 'declined') $cancelled++;
    elseif ($a['status'] === 'accepted') $accepted++;
    elseif ($a['status'] === 'completed') $completed++;
}

function formatTimeRange($start, $end) {
    if (!$start || !$end) return "Time not set";
    return date("h:i A", strtotime($start)) . " - " . date("h:i A", strtotime($end));
}

function getRegimeLabel($regime) {
    $regimes = [
        "full_body" => "Full Body Workout",
        "upper_body" => "Upper Body Focus",
        "lower_body" => "Lower Body Focus",
        "cardio" => "Cardio Training",
        "strength" => "Strength Training",
        "flexibility" => "Flexibility & Mobility",
        "hiit" => "HIIT Training",
        "recovery" => "Recovery Session"
    ];
    return $regimes[$regime] ?? ucfirst($regime) ?? "N/A";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Trainer Dashboard | GymFit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
body {
    background: #f8f9fa;
    margin: 0;
    height: 100vh;
    overflow: hidden;
}
main {
    margin-left: 250px;
    margin-top: 56px;
    height: calc(100vh - 56px);
    overflow-y: auto;
    padding: 1.5rem;
}
.stats-card {
    border-radius: 12px;
    padding: 1.2rem;
    text-align: center;
    background: #fff;
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
}
.stats-card:hover {
    transform: translateY(-3px);
}
.stats-icon {
    font-size: 2rem;
    color: #b71c1c;
    margin-bottom: 0.5rem;
}
.stats-card h6 { color: #777; font-size: 0.9rem; }
.stats-card h4 { color: #b71c1c; font-weight: 700; }

.session-card {
    border-radius: 10px;
    padding: 1rem;
    border: 1px solid #ddd;
    background: #fff;
}
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    display: inline-block;
}
.avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <h2 class="fw-bold mb-3"><i class="bi bi-person-badge-fill text-danger"></i> Welcome back, <?= htmlspecialchars($user['firstName'] ?? $user['name']) ?>!</h2>
    <p class="text-muted">Here's an overview of your training sessions and trainees.</p>

    <!-- Stats -->
    <div class="row mb-4 text-center">
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-calendar-check stats-icon"></i>
                <h6>Total Sessions</h6>
                <h4><?= $total ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-hand-thumbs-up-fill stats-icon"></i>
                <h6>Accepted</h6>
                <h4><?= $accepted ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-trophy-fill stats-icon"></i>
                <h6>Completed</h6>
                <h4><?= $completed ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="bi bi-x-circle-fill stats-icon"></i>
                <h6>Cancelled</h6>
                <h4><?= $cancelled ?></h4>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Today's Schedule -->
        <div class="col-md-6">
            <div class="card p-3 shadow-sm">
                <h5><i class="bi bi-clock-history text-danger"></i> Today's Schedule</h5>
                <?php
                $today = date('Y-m-d');
                $todaySessions = array_filter($appointments, fn($a) => $a['date'] === $today && in_array($a['status'], ['pending','accepted']));
                if (count($todaySessions) === 0) echo "<p class='text-muted'>No sessions today.</p>";
                foreach ($todaySessions as $session):
                    $trainee = $trainees[$session['trainee_id']] ?? ['name'=>'Unknown','avatar'=>null];
                ?>
                <div class="session-card mb-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><i class="bi bi-person-circle text-danger"></i> <?= htmlspecialchars($trainee['name']) ?></strong><br>
                            <small><i class="bi bi-calendar-event"></i> <?= date("F d, Y", strtotime($session['date'])) ?> | <?= formatTimeRange($session['start_time'], $session['end_time']) ?></small><br>
                            <small><i class="bi bi-dumbbell"></i> <?= getRegimeLabel($session['training_regime']) ?></small>
                        </div>
                        <span class="badge bg-<?= $session['is_paid'] ? 'success' : 'warning text-dark' ?>">
                            <?= $session['is_paid'] ? 'Paid' : 'Unpaid' ?>
                        </span>
                    </div>

                    <?php if ($session['status'] === 'pending'): ?>
                    <div class="mt-2">
                        <a href="?id=<?= $session['id'] ?>&status=accepted" class="btn btn-outline-success btn-sm"><i class="bi bi-check-circle"></i> Accept</a>
                        <a href="?id=<?= $session['id'] ?>&status=declined" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle"></i> Cancel</a>
                    </div>
                    <?php else: ?>
                    <div class="mt-2 text-muted"><strong>Status:</strong> <?= ucfirst($session['status']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Trainees -->
        <div class="col-md-6">
            <div class="card p-3 shadow-sm">
                <h5><i class="bi bi-people-fill text-danger"></i> Your Trainees</h5>
                <?php
                $activeTrainees = [];
                foreach ($appointments as $app) {
                    if (!in_array($app['status'], ['cancelled', 'declined'])) {
                        $activeTrainees[$app['trainee_id']] = $trainees[$app['trainee_id']] ?? ['name'=>'Unknown','avatar'=>null];
                    }
                }
                if (empty($activeTrainees)) echo "<p class='text-muted'>No active trainees.</p>";
                foreach ($activeTrainees as $tId => $trainee):
                ?>
                <div class="d-flex align-items-center mb-3 border p-2 rounded">
                    <div class="avatar-circle me-3">
                        <?php if($trainee['avatar']): ?>
                            <img src="<?= $trainee['avatar'] ?>" alt="<?= $trainee['name'] ?>">
                        <?php else: ?>
                            <div class="bg-secondary text-white d-flex justify-content-center align-items-center" style="width:40px;height:40px;border-radius:50%;">
                                <?= strtoupper(substr($trainee['name'],0,1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <strong><?= htmlspecialchars($trainee['name']) ?></strong><br>
                        <small class="text-muted"><i class="bi bi-person"></i> Trainee</small>
                    </div>
                    <a href="trainer_chat.php?traineeId=<?= $tId ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-chat-dots"></i> Chat</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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

</body>
</html>
