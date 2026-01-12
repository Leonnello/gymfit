<?php
session_start();
include '../../db_connect.php';

if (empty($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = (int)$user['id'];

/* ======================================================
   FETCH APPOINTMENTS FOR TRAINER
====================================================== */
$sql = "
    SELECT a.*, CONCAT(u.firstName,' ',u.lastName) AS trainee_name
    FROM appointments a
    JOIN users u ON a.trainee_id = u.id
    WHERE a.trainer_id = ?
    ORDER BY a.date ASC, a.start_time ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$listAppointments = $appointments;

usort($listAppointments, function ($a, $b) {
    $dateA = strtotime($a['date'].' '.$a['start_time']);
    $dateB = strtotime($b['date'].' '.$b['start_time']);
    return $dateB <=> $dateA; // latest → oldest
});


/* ======================================================
   HELPERS
====================================================== */
function formatTimeRange($start, $end) {
    return (!$start || !$end)
        ? "Time not set"
        : date("h:i A", strtotime($start)) . " - " . date("h:i A", strtotime($end));
}

function getTrainingRegimeLabel($regime) {
    return [
        "full_body" => "Full Body Workout",
        "upper_body" => "Upper Body Focus",
        "lower_body" => "Lower Body Focus",
        "cardio" => "Cardio Training",
        "strength" => "Strength Training",
        "flexibility" => "Flexibility & Mobility",
        "hiit" => "HIIT Training",
        "recovery" => "Recovery Session"
    ][$regime] ?? ucfirst($regime);
}

function getAppointmentStatus($a) {
    $dt = strtotime($a['date'].' '.$a['start_time']);
    return ($dt < time() && $a['status'] === 'scheduled')
        ? 'completed'
        : $a['status'];
}

function getStatusBadge($status) {
    switch ($status) {
        case "completed":
            return '<span class="status-text status-completed">
                        <i class="bi bi-check-circle"></i> Completed
                    </span>';
        case "cancelled":
            return '<span class="status-text status-cancelled">
                        <i class="bi bi-x-circle"></i> Cancelled
                    </span>';
        case "scheduled":
            return '<span class="status-text status-scheduled">
                        <i class="bi bi-calendar-event"></i> Scheduled
                    </span>';
        default:
            return '<span class="status-text text-muted">
                        '.ucfirst($status).'
                    </span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Trainer Schedule | GymFit</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f8f9fa; }
main {
    margin-left:250px;
    margin-top:56px;
    padding:1.5rem;
    height:calc(100vh - 56px);
    overflow:auto;
}
.card { border-radius:12px; }
.status-text {
    font-weight: 600;
    font-size: 0.9rem;
}

.status-completed {
    color: #28a745; /* green */
}

.status-cancelled {
    color: #dc3545; /* red */
}

.status-scheduled {
    color: #0d6efd; /* blue */
}

</style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>

<div class="mb-4">
    <h2><i class="bi bi-calendar-week text-danger"></i> Training Schedule</h2>
    <p class="text-muted">View and manage your training sessions</p>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#calendar">
            <i class="bi bi-calendar3"></i> Calendar View
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#list">
            <i class="bi bi-list-ul"></i> List View
        </button>
    </li>
</ul>

<div class="tab-content">

<!-- ================= CALENDAR VIEW ================= -->
<div class="tab-pane fade show active" id="calendar">

<div class="row g-4">
<div class="col-md-3">
    <div class="card p-3">
        <h6>Select Date</h6>
        <input type="date" class="form-control" id="selectedDate" value="<?= date('Y-m-d') ?>">
    </div>
</div>

<div class="col-md-9">
<div class="card p-3">
<h6>Sessions on <span id="displayDate"><?= date('F d, Y') ?></span></h6>
<div id="appointmentsList"></div>
</div>
</div>
</div>

</div>

<!-- ================= LIST VIEW ================= -->
<div class="tab-pane fade" id="list">
<div class="card p-3">
<h6>All Sessions</h6>

<?php if (!$appointments): ?>
<p class="text-muted">No sessions found.</p>
<?php endif; ?>

<?php foreach ($listAppointments as $a): ?>
<div class="card mb-3 p-3 shadow-sm">
    <strong><?= htmlspecialchars($a['trainee_name']) ?></strong><br>
    <small><?= date("F d, Y", strtotime($a['date'])) ?></small><br>
    <small><?= formatTimeRange($a['start_time'], $a['end_time']) ?></small><br>
    <?= getStatusBadge(getAppointmentStatus($a)) ?>
</div>
<?php endforeach; ?>

</div>
</div>

</div>
</main>

<script>
const appointments = <?= json_encode($appointments) ?>;
const list = document.getElementById('appointmentsList');
const dateInput = document.getElementById('selectedDate');
const displayDate = document.getElementById('displayDate');

function renderAppointments(date) {
    list.innerHTML = '';
    let found = false;

    appointments.forEach(a => {
        const apptDate = new Date(a.date).toISOString().split('T')[0];
        if (apptDate === date) {
            found = true;
            list.innerHTML += `
            <div class="card mb-3 p-3 shadow-sm">
                <strong>${a.trainee_name}</strong><br>
                <small>${a.start_time} - ${a.end_time}</small><br>
                <small>${a.training_regime}</small>
            </div>`;
        }
    });

    if (!found) {
        list.innerHTML = '<p class="text-muted">No sessions for this date.</p>';
    }
}

renderAppointments(dateInput.value);

dateInput.addEventListener('change', () => {
    displayDate.textContent = new Date(dateInput.value).toLocaleDateString('en-US',{
        month:'long', day:'numeric', year:'numeric'
    });
    renderAppointments(dateInput.value);
});


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</script>
</body>
</html>
