<?php


if (!isset($_SESSION['user'])) {
  header("Location: ../../login.php");
  exit;
}

$user = $_SESSION['user'];
$trainer_id = $user['id'];
$trainer_name = $user['firstName'];

// Fetch latest pending appointments
$query = "SELECT id, trainee_id, date, start_time, training_regime 
          FROM appointments 
          WHERE trainer_id = ? AND status = 'Pending'
          ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$pending_count = count($notifications);
?>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<nav class="navbar navbar-expand-lg navbar-dark fixed-top"
     style="margin-left:250px; background: linear-gradient(178deg, #d42f2b, #bb2121, #0f0303);">
  <div class="container-fluid">

    <!-- BRAND -->
    <span class="navbar-brand fw-bold">Gym Management System</span>

    <!-- TOGGLER -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- NAV ITEMS -->
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">

        <!-- NOTIFICATION DROPDOWN -->
        <li class="nav-item dropdown me-3">
          <a class="nav-link text-white position-relative dropdown-toggle" href="#" id="notifDropdown"
             role="button" data-bs-toggle="dropdown" aria-expanded="false">

            <i class="bi bi-bell-fill fs-5"></i>

            <?php if ($pending_count > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $pending_count ?>
              </span>
            <?php endif; ?>
          </a>

          <ul class="dropdown-menu dropdown-menu-end shadow p-2"
              aria-labelledby="notifDropdown"
              style="width: 320px; max-height: 300px; overflow-y: auto;">

            <?php if ($pending_count > 0): ?>
              <?php foreach ($notifications as $n): ?>
                <li>
                  <a href="view_appointment.php?id=<?= $n['id'] ?>"
                     class="dropdown-item small border-bottom p-2 text-wrap">
                    <strong>Pending Session</strong><br>
                    <?= htmlspecialchars(ucfirst($n['training_regime'])) ?><br>
                    <small class="text-muted">
                      <?= date("M d, Y", strtotime($n['date'])) ?> —
                      <?= date("h:i A", strtotime($n['start_time'])) ?>
                    </small>
                  </a>
                </li>
              <?php endforeach; ?>

              <li><hr class="dropdown-divider"></li>
              <li><a href="trainer_sessions.php" class="dropdown-item text-center text-primary small">
                View all notifications
              </a></li>

            <?php else: ?>
              <li class="text-center text-muted p-2">No new notifications</li>
            <?php endif; ?>

          </ul>
        </li>

        <!-- USER DROPDOWN -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" id="userDropdown"
             role="button" data-bs-toggle="dropdown" aria-expanded="false">

            <div class="rounded-circle bg-light text-dark d-flex align-items-center justify-content-center me-2"
                 style="width: 35px; height: 35px; font-weight: bold;">
              <?= strtoupper(substr($trainer_name, 0, 1)) ?>
            </div>

            <span class="fw-semibold">Hi, <?= strtok($trainer_name, ' ') ?>!</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="profile.php">👤 Profile</a></li>
            <li><a class="dropdown-item text-danger logout-btn" href="#">🚪 Logout</a></li>
          </ul>
        </li>

      </ul>
    </div>
  </div>
</nav>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
