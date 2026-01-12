<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}


// Fetch equipment and categories
$equipmentQuery = $conn->query("SELECT * FROM equipment ORDER BY id DESC");
$equipment = $equipmentQuery->fetch_all(MYSQLI_ASSOC);

$categoriesQuery = $conn->query("SELECT * FROM categories");
$categories = $categoriesQuery->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$totalValue = 0;
$totalAvailable = 0;
$totalMaintenance = 0;
$totalOutOfOrder = 0;

foreach ($equipment as $eq) {
    $totalValue += floatval($eq['purchase_price']);
    switch ($eq['status']) {
        case 'available': $totalAvailable++; break;
        case 'maintenance': $totalMaintenance++; break;
        case 'out_of_order': $totalOutOfOrder++; break;
    }
}

// Filter handling
$searchQuery = $_GET['search'] ?? '';
$selectedCategory = $_GET['category'] ?? 'all';
$selectedStatus = $_GET['status'] ?? 'all';

$filteredEquipment = array_filter($equipment, function($eq) use ($searchQuery, $selectedCategory, $selectedStatus) {
    $matchSearch = empty($searchQuery) ||
        stripos($eq['name'], $searchQuery) !== false ||
        stripos($eq['brand'], $searchQuery) !== false ||
        stripos($eq['model'], $searchQuery) !== false ||
        stripos($eq['serial_number'], $searchQuery) !== false;
    $matchCategory = $selectedCategory === 'all' || $eq['category_id'] == $selectedCategory;
    $matchStatus = $selectedStatus === 'all' || $eq['status'] === $selectedStatus;
    return $matchSearch && $matchCategory && $matchStatus;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Inventory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body {
        overflow-x: hidden;
    }

    .main-content {
        margin-left: 220px;
        margin-top: 56px;
        padding: 1rem;
        height: calc(100vh - 56px);
        overflow-y: auto;
    }
    .card-stats .card-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-responsive {
        max-height: 500px;
        overflow-y: auto;
    }
</style>
</head>
<body>

<!-- Navbar -->
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <h2 class="mb-4">Equipment Inventory</h2>

    <!-- Stats Cards -->
    <div class="row mb-4 card-stats">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div>Total Equipment</div>
                    <div><i class="fas fa-dumbbell"></i> <?= count($equipment) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div>Equipment Value</div>
                    <div>₱<?= number_format($totalValue, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div>Maintenance</div>
                    <div><?= $totalMaintenance ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div>Out of Order</div>
                    <div><?= $totalOutOfOrder ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search equipment..." value="<?= htmlspecialchars($searchQuery) ?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="all">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $selectedCategory == $cat['id'] ? 'selected' : '' ?>><?= $cat['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="all">All Status</option>
                <option value="available" <?= $selectedStatus == 'available' ? 'selected' : '' ?>>Available</option>
                <option value="maintenance" <?= $selectedStatus == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                <option value="out_of_order" <?= $selectedStatus == 'out_of_order' ? 'selected' : '' ?>>Out of Order</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
        </div>
    </form>

    <!-- Equipment Table -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Serial Number</th>
                    <th>Purchase Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($filteredEquipment) > 0): ?>
                    <?php foreach ($filteredEquipment as $eq): ?>
                        <tr>
                            <td><?= htmlspecialchars($eq['name']) ?></td>
                            <td><?= htmlspecialchars(array_search($eq['category_id'], array_column($categories, 'id')) !== false ? $categories[array_search($eq['category_id'], array_column($categories, 'id'))]['name'] : 'Unknown') ?></td>
                            <td><?= htmlspecialchars($eq['brand']) ?></td>
                            <td><?= htmlspecialchars($eq['model']) ?></td>
                            <td><?= htmlspecialchars($eq['serial_number']) ?></td>
                            <td><?= date('M d, Y', strtotime($eq['purchase_date'])) ?></td>
                            <td>
                                <?php
                                switch ($eq['status']) {
                                    case 'available': echo '<span class="badge bg-success">Available</span>'; break;
                                    case 'maintenance': echo '<span class="badge bg-warning text-dark">Maintenance</span>'; break;
                                    case 'out_of_order': echo '<span class="badge bg-danger">Out of Order</span>'; break;
                                }
                                ?>
                            </td>
                            <td class="text-end">
                                <a href="view.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                <a href="edit.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                <a href="delete.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No equipment found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
