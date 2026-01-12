<?php
session_start();
include '../../db_connect.php';

// Ensure trainer is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];
$trainer_id = $user['id'];

// Helper functions
function formatTimeRange($start, $end) {
    return date("h:i A", strtotime($start)) . " - " . date("h:i A", strtotime($end));
}

function getTrainingRegimeLabel($regime) {
    $labels = [
        'strength' => 'Strength Training',
        'cardio' => 'Cardio Training',
        'flexibility' => 'Flexibility Training'
    ];
    return $labels[$regime] ?? 'General Training';
}

// Sorting logic
$allowedSort = [
    "date_asc" => "a.date ASC",
    "date_desc" => "a.date DESC",
    "trainee_asc" => "u.firstName ASC",
    "trainee_desc" => "u.firstName DESC",
    "status_asc" => "a.status ASC",
    "status_desc" => "a.status DESC"
];

$sort = $_GET["sort"] ?? "date_asc";
$orderBy = $allowedSort[$sort] ?? "a.date ASC";

// Update appointment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $status = ($_POST['status'] === 'accepted') ? 'accepted' : 'cancelled';

    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $appointment_id);
    $stmt->execute();

    header("Location: trainer_sessions.php?updated=1");
    exit;
}

// Success message
$statusMessage = isset($_GET['updated']) ? "Session status updated successfully!" : "";

// Fetch appointments
$stmt = $conn->prepare("
    SELECT a.*, u.firstName, u.lastName
    FROM appointments a
    JOIN users u ON a.trainee_id = u.id
    WHERE a.trainer_id = ?
    ORDER BY 
        CASE 
            WHEN LOWER(a.status) = 'pending' THEN 1
            WHEN LOWER(a.status) = 'accepted' THEN 2
            WHEN LOWER(a.status) = 'cancelled' THEN 3
            ELSE 4
        END,
        $orderBy
");

$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $row['status'] = strtolower($row['status']); // normalize
    $appointments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainor Sessions | GymFit</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        main { margin-left: 250px; padding: 30px; }
        .appointment-card {
            border: none; border-radius: 15px; background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: 0.2s ease;
        }
        .appointment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .card-header {
            background: #dc3545;
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .status-badge { font-weight: 600; }
        .badge-paid { background-color: #28a745; }
        .badge-unpaid { background-color: #6c757d; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>

    <h3 class="fw-bold mb-4">
        <i class="bi bi-calendar-check text-danger"></i> Trainor Sessions
    </h3>

    <!-- Success message -->
    <?php if ($statusMessage): ?>
        <div class="alert alert-success"><?= $statusMessage ?></div>
    <?php endif; ?>

    <!-- Sorting -->
    <div class="d-flex justify-content-end mb-4">
        <form method="GET" class="d-flex gap-2">
            <select name="sort" class="form-select">
                <option value="date_asc" <?= $sort == "date_asc" ? "selected" : "" ?>>Date (Earliest)</option>
                <option value="date_desc" <?= $sort == "date_desc" ? "selected" : "" ?>>Date (Latest)</option>
                <option value="trainee_asc" <?= $sort == "trainee_asc" ? "selected" : "" ?>>Trainee A → Z</option>
                <option value="trainee_desc" <?= $sort == "trainee_desc" ? "selected" : "" ?>>Trainee Z → A</option>
                <option value="status_asc" <?= $sort == "status_asc" ? "selected" : "" ?>>Status A → Z</option>
                <option value="status_desc" <?= $sort == "status_desc" ? "selected" : "" ?>>Status Z → A</option>
            </select>
            <button class="btn btn-danger">Sort</button>
        </form>
    </div>

    <?php
    // Grouping
    $groups = ["pending" => [], "accepted" => [], "cancelled" => []];

    foreach ($appointments as $appt) {
        $groups[strtolower($appt["status"])][] = $appt;
    }

    function renderGroup($title, $color, $list) {
        if (empty($list)) return;

        echo "<h4 class='fw-bold mt-4 mb-3 text-$color'>
                <i class='bi bi-circle-fill'></i> $title
              </h4>";

        echo "<div class='row'>";
        foreach ($list as $a) { ?>

            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card appointment-card">
                    <div class="card-header d-flex justify-content-between">
                        <span><i class="bi bi-person-circle"></i> <?= $a['firstName'] . " " . $a['lastName'] ?></span>
                        <span class="badge <?= $a['is_paid'] ? 'badge-paid' : 'badge-unpaid' ?>">
                            <?= $a['is_paid'] ? "Paid" : "Unpaid" ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <p><i class="bi bi-calendar3 text-danger"></i> <?= date("F j, Y", strtotime($a['date'])) ?></p>
                        <p><i class="bi bi-clock text-danger"></i> <?= formatTimeRange($a['start_time'], $a['end_time']) ?></p>
                        <p><i class="bi bi-heart-pulse text-danger"></i> <?= getTrainingRegimeLabel($a['training_regime']) ?></p>
                        <p><i class="bi bi-cash text-danger"></i> ₱<?= number_format($a['amount'], 2) ?></p>

                        <?php if (!empty($a['notes'])): ?>
                            <p><i class="bi bi-journal-text text-danger"></i> <?= $a['notes'] ?></p>
                        <?php endif; ?>

                        <!-- Status Buttons -->
                        <?php if (strtolower($a['status']) === 'pending'): ?>
                            <form method="POST" class="d-flex gap-2 mt-3">
                                <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                                <button name="status" value="accepted" class="btn btn-outline-success w-50">
                                    <i class="bi bi-check-circle"></i> Accept
                                </button>
                                <button name="status" value="cancelled" class="btn btn-outline-danger w-50">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center mt-3">
                                <span class="status-badge text-<?= strtolower($a['status']) === 'accepted' ? 'success' : 'danger' ?>">
                                    <i class="bi <?= strtolower($a['status']) === 'accepted' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i>
                                    <?= ucfirst($a['status']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php }
        echo "</div>";
    }

    // Render grouped sections
    renderGroup("Pending Sessions", "warning", $groups["pending"]);
    renderGroup("Accepted Sessions", "success", $groups["accepted"]);
    renderGroup("Cancelled Sessions", "danger", $groups["cancelled"]);
    ?>

</main>
</body>
</html>
