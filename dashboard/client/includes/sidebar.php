<!-- sidebar.php -->
<div class="sidebar">
  <div class="sidebar-content">
    <div class="sidebar-header">
      <img src="../../assets/_logo.png" alt="GymFit Logo">
    </div>

    <div class="p-2">
      <div class="nav-section">Main</div>
      <ul class="p-0">
        <li class="nav-item active">
          <a href="client.php"><i class="bi bi-grid"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a href="client_schedule.php"><i class="bi bi-calendar-event"></i> Schedule</a>
        </li>
      </ul>

      <div class="nav-section">Reports</div>
      <ul class="p-0">
        <li class="nav-item">
          <a href="client_chat.php"><i class="bi bi-chat-dots"></i> Chat</a>
        </li>
      </ul>
    </div>
  </div>

  <!-- ✅ Sidebar Footer -->
  <div class="sidebar-footer">
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
}

.sidebar-header {
  background: #d42f2b;
  padding: 0.7rem;
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

.nav-item a:hover,
.nav-item.active a {
  background-color: #f4f4f4;
  color: #b71c1c;
}

.nav-item a i {
  font-size: 1rem;
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
