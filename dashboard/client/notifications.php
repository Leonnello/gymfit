<?php
include '../../db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$user = $_SESSION['user'];
$client_id = $user['id'];

// Fetch accepted or declined appointments
$query = "SELECT training_regime, date, start_time, end_time, status, created_at 
          FROM appointments 
          WHERE trainee_id = ? 
          AND (status = 'Accepted' OR status = 'Declined') 
          ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<style>
  main {
    margin-left: 250px;
    margin-top: 56px;
    height: calc(100vh - 56px);
    overflow-y: auto;
    padding: 1.5rem;
  }

  .notification-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  }

  .notification-card:hover {
    transform: scale(1.02);
    box-shadow: 0px 4px 15px rgba(0,0,0,0.1);
  }
</style>

<body>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>
  <div class="container" style="margin-top: 80px;">
    <div class="card shadow border-0">
      <div class="card-header text-white fw-bold" style="background-color: darkred;">
        🔔 Appointment Notifications
      </div>
      <div class="card-body">
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <div class="alert notification-card 
              <?= $row['status'] === 'Accepted' ? 'alert-success border-start border-4 border-success' : 'alert-danger border-start border-4 border-danger' ?> 
              mb-3">
              
              <h6 class="fw-bold mb-1"><?= htmlspecialchars($row['training_regime']) ?></h6>
              <p class="mb-1">
                <strong>Status:</strong> <?= htmlspecialchars($row['status']) ?><br>
                <strong>Date:</strong> <?= date("F j, Y", strtotime($row['date'])) ?><br>
                <strong>Time:</strong> <?= date("g:i A", strtotime($row['start_time'])) ?> - <?= date("g:i A", strtotime($row['end_time'])) ?>
              </p>
              <small class="text-muted">Updated on <?= date("M d, Y g:i A", strtotime($row['created_at'])) ?></small>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="alert alert-secondary text-center">
            ✅ No new notifications.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
