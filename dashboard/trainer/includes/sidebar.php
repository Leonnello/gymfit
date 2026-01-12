<!-- sidebar.php -->
<div class="sidebar">
  <div class="sidebar-content">
    <!-- Header -->
    <div class="sidebar-header">
      <img src="../../assets/_logo.png" alt="GymFit Logo">
      <h5 class="mt-2 text-white fw-bold">GymFit Trainor</h5>
    </div>

    <div class="p-2">
      <!-- Main Section -->
      <div class="nav-section">Main</div>
      <ul class="p-0">
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'trainer.php' ? 'active' : '' ?>">
          <a href="trainer.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'trainer_schedule.php' ? 'active' : '' ?>">
          <a href="trainer_schedule.php"><i class="bi bi-calendar-event"></i> Schedule</a>
        </li>
      </ul>

      <!-- Reports Section -->
      <div class="nav-section">Sessions & Reports</div>
      <ul class="p-0">
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'trainer_chat.php' ? 'active' : '' ?>">
          <a href="trainer_chat.php"><i class="bi bi-chat-dots"></i> Chat</a>
        </li>
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'trainer_sessions.php' ? 'active' : '' ?>">
          <a href="trainer_sessions.php"><i class="bi bi-clock-history"></i> Sessions</a>
        </li>
      </ul>
    </div>
  </div>

  <!-- Footer -->
  <div class="sidebar-footer">
    <div class="footer-text">
      <div>© 2025 GymFit</div>
      <div class="small">Version 0.1</div>
    </div>
  </div>
</div>

<!-- ✅ Styles -->
<style>
.sidebar {
  height: 100vh;
  width: 250px;
  background: #ffffff;
  border-right: 1px solid #ddd;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.3s ease;
  z-index: 1000;
}

.sidebar-header {
  background: linear-gradient(135deg, #b71c1c, #dc3545);
  padding: 1rem 0.5rem;
  text-align: center;
}

.sidebar-header img {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 2px solid #fff;
}

.nav-section {
  padding: 0.5rem 1.25rem;
  color: #888;
  font-size: 0.75rem;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.nav-item {
  list-style: none;
  margin-bottom: 6px;
}

.nav-item a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  border-radius: 8px;
  color: #333;
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-item a i {
  font-size: 1.1rem;
  color: #b71c1c;
  transition: 0.3s ease;
}

/* Hover and Active States */
.nav-item a:hover,
.nav-item.active a {
  background: #fff3f3;
  color: #b71c1c;
  font-weight: 600;
  box-shadow: inset 3px 0 0 #b71c1c;
}

.nav-item a:hover i,
.nav-item.active a i {
  color: #b71c1c;
}

.sidebar-footer {
  text-align: center;
  padding: 1rem;
  border-top: 1px solid #eee;
  background: #fafafa;
}

.logout-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #b71c1c;
  text-decoration: none;
  font-weight: 600;
  padding: 8px 16px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  background: #b71c1c;
  color: #fff;
}

.footer-text {
  margin-top: 10px;
  font-size: 0.8rem;
  color: #777;
}
</style>
