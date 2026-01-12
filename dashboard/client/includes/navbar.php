<?php

if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$user = $_SESSION['user'];
$client_id = $user['id'];
$client_name = ($user['firstName']);

// Fetch notifications where trainer accepted or declined
$query = "SELECT * FROM appointments 
          WHERE trainee_id = ? 
          AND (status = 'Accepted' OR status = 'Declined')
          ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);


$notif_count = count($notifications);
?>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap 5 JS (required for dropdowns) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="margin-left:250px; background: linear-gradient(178deg, #d42f2b, #bb2121, #0f0303);">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold">Gym Management System</span>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">

        <!-- Notifications Dropdown -->
        <li class="nav-item dropdown position-relative me-3">
          <a class="nav-link text-white position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            🔔
            <?php if ($notif_count > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $notif_count ?>
              </span>
            <?php endif; ?>
          </a>

          <ul class="dropdown-menu dropdown-menu-end p-2 shadow" aria-labelledby="notifDropdown" style="width: 300px; max-height: 300px; overflow-y: auto;">
            <?php if ($notif_count > 0): ?>
              <?php foreach ($notifications as $notif): ?>
                <li class="border-bottom small p-2">
                  <strong>Status:</strong> <?= htmlspecialchars($notif['status']) ?><br>
                  <small><?= htmlspecialchars($notif['training_regime']) ?> — <?= date("M d, Y", strtotime($notif['date'])) ?></small>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="text-center text-muted p-2">No new notifications</li>
            <?php endif; ?>
          </ul>
        </li>

        <!-- User Avatar + Name -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="rounded-circle bg-light text-dark d-flex align-items-center justify-content-center me-2" 
                 style="width: 35px; height: 35px; font-weight: bold;">
              <?= strtoupper(substr($client_name, 0, 1)) ?>
            </div>
            <span class="fw-semibold">Hi,<?= $client_name ?></span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="settingsDropdown">
            <li>
              <a class="dropdown-item" href="profile.php">👤 Profile</a>
            </li>
            <li>
              <a class="dropdown-item text-danger logout-btn" href="#">🚪 Logout</a>
            </li>
          </ul>
        </li>

      </ul>
    </div>
  </div>
</nav>
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
