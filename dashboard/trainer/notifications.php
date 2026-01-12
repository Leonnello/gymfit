<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];

// Fetch pending appointments for this trainer
$query = "SELECT * FROM appointments WHERE trainer_id = ? AND status = 'Pending' ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
    }
    main {
      margin-left: 250px; /* sidebar width */
      margin-top: 70px;   /* navbar height */
      padding: 2rem;
    }
    .notification-card {
      border-left: 5px solid darkred;
      background-color: #ffffff;
      transition: 0.3s;
    }
    .notification-card:hover {
      transform: translateX(3px);
      background-color: #ffffff;
    }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Notifications Section -->
<main>
  <div class="container-fluid">
    <div class="card shadow-lg border-0 rounded-3">
      <div class="card-header text-white fw-bold" style="background-color: #f9fd0D;">
        🔔 Pending Notifications
      </div>
      <div class="card-body">

        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <div class="alert alert-light notification-card mb-3 p-3">
              <h6 class="fw-bold text-danger mb-1">
                <?= htmlspecialchars($row['training_regime']) ?> (<?= htmlspecialchars($row['type']) ?>)
              </h6>
              <p class="mb-1">
                <strong>Date:</strong> <?= date("F j, Y", strtotime($row['date'])) ?><br>
                <strong>Time:</strong> <?= date("g:i A", strtotime($row['start_time'])) ?> - <?= date("g:i A", strtotime($row['end_time'])) ?>
              </p>
              <small class="text-muted">
                Added on <?= date("M d, Y g:i A", strtotime($row['created_at'])) ?>
              </small>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="alert alert-success text-center mb-0">
            ✅ No new pending notifications.
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
