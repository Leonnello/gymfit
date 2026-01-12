<!-- sidebar.php -->
<div class="sidebar">
  <div class="sidebar-content">
    <!-- Header -->
    <div class="sidebar-header">
      <img src="../../assets/_logo.png" alt="GymFit Logo">
    </div>

    <div class="p-2">
      <!-- MAIN -->
      <div class="nav-section">Main</div>
      <ul class="p-0">
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>">
          <a href="admin.php"><i class="bi bi-grid"></i> Dashboard</a>
        </li>
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'signup_requests.php' ? 'active' : '' ?>">
          <a href="signup_requests.php"><i class="bi bi-person-plus"></i> Sign-up Requests</a>
        </li>
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : '' ?>">
          <a href="members.php"><i class="bi bi-people-fill"></i> Members</a>
        </li>
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
          <a href="users.php"><i class="bi bi-people-fill"></i> Users</a>
        </li>
        
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'staff.php' ? 'active' : '' ?>">
          <a href="staff.php"><i class="bi bi-person-badge-fill"></i> Staff</a>
        </li>
      </ul>

      <!-- BUSINESS -->
      <div class="nav-section">Business</div>
      <ul class="p-0">
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin_payments.php' || basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : '' ?>">
          <a href="admin_payments.php"><i class="bi bi-credit-card"></i> Payments</a>
        </li>
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : '' ?>">
          <a href="inventory.php"><i class="bi bi-box-seam"></i> Inventory</a>
        </li>
      </ul>

      <!-- REPORTS -->
      <div class="nav-section">Reports</div>
      <ul class="p-0">
        <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'report.php' || basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'active' : '' ?>">
          <a href="admin_reports.php"><i class="bi bi-graph-up"></i> Analytics</a>
        </li>
      </ul>
    </div>
  </div>

  <!-- FOOTER -->
  <div class="sidebar-footer">
    <a href="../../logout.php" class="logout-btn">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
    <div class="footer-text">
      <div>© 2025</div>
      <div>GymFit v0.1</div>
    </div>
  </div>
</div>

<style>
.sidebar {
  height: 100vh;
  width: 250px;
  background: #fff;
  border-right: 1px solid #ddd;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.3s;
  z-index: 1000;
}

.sidebar-header {
  background: #d42f2b;
  padding: 0.3rem;
  text-align: center;
}

.sidebar-header img {
  width: 55px;
  border-radius: 50%;
}

.nav-section {
  padding: 0.5rem 1rem;
  color: #999;
  font-size: 0.75rem;
  text-transform: uppercase;
}

.nav-item {
  list-style: none;
  margin-bottom: 5px;
}

.nav-item a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 15px;
  border-radius: 6px;
  color: #333;
  text-decoration: none;
  transition: 0.3s;
}

.nav-item a i {
  font-size: 1rem;
  color: #b71c1c;
}

.nav-item a:hover,
.nav-item.active a {
  background-color: #f4f4f4;
  color: #b71c1c;
}

.sidebar-footer {
  text-align: center;
  padding: 1rem;
  border-top: 1px solid #eee;
}

.logout-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #b71c1c;
  text-decoration: none;
  font-weight: 500;
  margin-bottom: 0.5rem;
  transition: 0.3s;
}

.logout-btn:hover {
  color: #fff;
  background: #b71c1c;
  border-radius: 6px;
  padding: 6px 12px;
}

.footer-text {
  font-size: 0.8rem;
  color: #999;
}
</style>
