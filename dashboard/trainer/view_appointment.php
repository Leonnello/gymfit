<?php
include '../../db_connect.php';
session_start();

// ✅ Check if trainer is logged in
if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$user = $_SESSION['user'];
$trainer_id = $user['id'];

// ✅ Check if appointment ID is provided
if (!isset($_GET['id'])) {
  header("Location: trainer_sessions.php");
  exit;
}

$appointment_id = intval($_GET['id']);

// ✅ Fetch appointment details
$query = "SELECT a.*, 
                 CONCAT(t.firstName, ' ', t.lastName) AS trainee_name, 
                 t.email AS trainee_email
          FROM appointments a
          JOIN users t ON a.trainee_id = t.id
          WHERE a.id = ? AND a.trainer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $appointment_id, $trainer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "<script>alert('Appointment not found or unauthorized access!'); window.location='trainer_sessions.php';</script>";
  exit;
}

$appointment = $result->fetch_assoc();

// ✅ Handle Accept / Decline action
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $new_status = $_POST['status'];

  $update = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
  $update->bind_param("si", $new_status, $appointment_id);
  $update->execute();

  echo "<script>alert('Appointment has been updated to $new_status.'); window.location='trainer_sessions.php';</script>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Appointment | Trainer</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    body {
      background-color: #f5f6fa;
      font-family: "Poppins", sans-serif;
    }
    main {
      margin-left: 250px;
      padding: 2rem;
      min-height: 100vh;
    }
    .card-header {
      background: linear-gradient(135deg, #d42f2b, #bb2121, #0f0303);
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.5rem;
    }
    .card-header h4 {
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .info-label {
      font-weight: 600;
      color: #333;
    }
    .action-btn {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      transition: 0.3s;
      color: white;
    }
    .action-btn:hover {
      transform: scale(1.1);
    }
    .btn-success {
      background-color: #28a745 !important;
      border: none;
    }
    .btn-danger {
      background-color: #dc3545 !important;
      border: none;
    }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<div class="d-flex">
  <?php include 'includes/sidebar.php'; ?>

  <main>
    <div class="container">
      <div class="card shadow-lg border-0">
        <div class="card-header">
          <h4><i class="bi bi-person-vcard-fill"></i> View Appointment</h4>
          <span><i class="bi bi-calendar2-week"></i></span>
        </div>

        <div class="card-body p-4">
          <div class="row mb-4">
            <div class="col-md-6 mb-3">
              <p><span class="info-label"><i class="bi bi-person-fill"></i> Trainee Name:</span><br> <?= htmlspecialchars($appointment['trainee_name']) ?></p>
              <p><span class="info-label"><i class="bi bi-envelope-fill"></i> Email:</span><br> <?= htmlspecialchars($appointment['trainee_email']) ?></p>
              <p><span class="info-label"><i class="bi bi-calendar-event-fill"></i> Date:</span><br> <?= date("F d, Y", strtotime($appointment['date'])) ?></p>
              <p><span class="info-label"><i class="bi bi-clock-fill"></i> Time:</span><br> 
                <?= date("h:i A", strtotime($appointment['start_time'])) ?> - <?= date("h:i A", strtotime($appointment['end_time'])) ?>
              </p>
            </div>

            <div class="col-md-6 mb-3">
              <p><span class="info-label"><i class="bi bi-activity"></i> Training Regime:</span><br> <?= ucfirst(str_replace('_', ' ', $appointment['training_regime'])) ?></p>
              <p><span class="info-label"><i class="bi bi-list-task"></i> Session Type:</span><br> <?= htmlspecialchars($appointment['type'] ?? 'N/A') ?></p>
              <p><span class="info-label"><i class="bi bi-chat-left-text-fill"></i> Notes:</span><br> <?= htmlspecialchars($appointment['notes'] ?? 'No notes provided') ?></p>
              <p><span class="info-label"><i class="bi bi-credit-card"></i> Session Cost:</span><br> 
                <strong class="text-danger">₱<?= number_format($appointment['amount'] ?? 0, 2) ?></strong>
              </p>
              <p><span class="info-label"><i class="bi bi-check2-circle"></i> Status:</span><br>
                <span class="badge bg-<?= 
                  $appointment['status'] == 'Pending' ? 'warning text-dark' : 
                  ($appointment['status'] == 'Accepted' ? 'success' : 'danger') 
                ?> fs-6 px-3 py-2">
                  <?= ucfirst($appointment['status']) ?>
                </span>
              </p>
            </div>
          </div>

          <?php if ($appointment['status'] == 'Pending'): ?>
          <form method="POST" class="d-flex justify-content-center gap-4 mt-4">
            <button type="submit" name="status" value="Accepted" 
                    class="btn btn-success action-btn" title="Accept Appointment">
              <i class="bi bi-check-lg"></i>
            </button>

            <button type="submit" name="status" value="Declined" 
                    class="btn btn-danger action-btn" title="Decline Appointment">
              <i class="bi bi-x-lg"></i>
            </button>
          </form>
          <?php else: ?>
            <div class="text-center mt-3">
              <p class="text-muted fs-5">This appointment has already been <strong><?= ucfirst($appointment['status']) ?></strong>.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
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
