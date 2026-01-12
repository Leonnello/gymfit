<?php
session_start();
include '../../db_connect.php'; // your DB connection

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Fetch all staff (trainors/admins)
$roles = ["trainor", "admin"];
$placeholders = "'" . implode("','", $roles) . "'";
$staffResult = $conn->query("SELECT * FROM users WHERE role IN ($placeholders) ORDER BY created_at DESC");
$staff = [];
while($row = $staffResult->fetch_assoc()) {
    $staff[] = $row;
}

// Fetch appointments
$appointmentsResult = $conn->query("SELECT * FROM appointments");
$appointments = [];
while($row = $appointmentsResult->fetch_assoc()) {
    $appointments[] = $row;
}

// Handle search & filter
$searchQuery = strtolower($_GET['search'] ?? '');
$selectedRole = $_GET['role'] ?? 'all';

$filteredStaff = array_filter($staff, function($member) use($searchQuery, $selectedRole){
    $matchesRole = ($selectedRole === 'all') || ($member['role'] === $selectedRole);
    $matchesSearch = empty($searchQuery) ||
        str_contains(strtolower($member['full_name']), $searchQuery) ||
        str_contains(strtolower($member['email']), $searchQuery) ||
        str_contains(strtolower($member['username']), $searchQuery);
    return $matchesRole && $matchesSearch;
});

// Helper function: today's sessions
function getTodaySessions($appointments, $trainerId){
    $today = date("Y-m-d");
    $completed = 0;
    $scheduled = 0;
    foreach($appointments as $app){
        if($app['trainer_id'] != $trainerId) continue;
        if($app['date'] === $today){
            $appTime = strtotime($app['date'] . " " . $app['time']);
            $now = time();
            if($appTime < $now && $app['status'] !== 'cancelled' && $app['status'] !== 'declined'){
                $completed++;
            } elseif($appTime > $now && $app['status'] === 'scheduled'){
                $scheduled++;
            }
        }
    }
    return ['completed' => $completed, 'scheduled' => $scheduled];
}

function getStatusBadge($status){
    switch($status){
        case "active": return '<span class="badge bg-success">Active</span>';
        case "inactive": return '<span class="badge bg-danger">Inactive</span>';
        default: return '<span class="badge bg-secondary">Unknown</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Owner Staff Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background:#f8f9fa;
    margin:0;
    padding:0;
}


/* Fixed Sidebar */
#sidebar {
    position: fixed;
    top: 56px; /* height of navbar */
    left: 0;
    width: 250px;
    bottom: 0;
    background: #fff;
    border-right: 1px solid #dee2e6;
    overflow-y: auto;
    padding-top: 20px;
}

/* Main Content */
main {
    margin-left: 250px; /* sidebar width */
    margin-top: 56px;   /* navbar height */
    height: calc(100vh - 56px);
    overflow-y: auto;
    padding: 20px;
}

/* Card Table */
.card { border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Staff Management</h2>
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Search staff..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="all" <?= $selectedRole==='all' ? 'selected':'' ?>>All Staff</option>
                <option value="trainor" <?= $selectedRole==='trainor' ? 'selected':'' ?>>Trainers</option>
                <option value="admin" <?= $selectedRole==='admin' ? 'selected':'' ?>>Admins</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary w-100">Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Staff Members</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Today's Sessions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(!empty($filteredStaff)): ?>
                        <?php foreach($filteredStaff as $member): 
                            $sessions = getTodaySessions($appointments, $member['id']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($member['full_name']) ?></td>
                            <td><?= htmlspecialchars($member['email']) ?></td>
                            <td><?= ucfirst($member['role']) ?></td>
                            <td><?= getStatusBadge($member['status']) ?></td>
                            <td>Scheduled: <?= $sessions['scheduled'] ?> | Completed: <?= $sessions['completed'] ?></td>
                            <td>
                                <a href="view_staff.php?id=<?= $member['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <a href="delete_staff.php?id=<?= $member['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-3">No staff found</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
