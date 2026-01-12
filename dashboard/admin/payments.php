<?php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Fetch categories
$categoriesResult = $conn->query("SELECT * FROM categories");
$categories = [];
if ($categoriesResult) while($row = $categoriesResult->fetch_assoc()) $categories[] = $row;

// Fetch equipment
$equipmentResult = $conn->query("
    SELECT e.*, c.name AS category_name
    FROM equipment e
    LEFT JOIN categories c ON e.category_id = c.id
");
$equipment = [];
if ($equipmentResult) while($row = $equipmentResult->fetch_assoc()) $equipment[] = $row;

// Filters
$search = $_GET['search'] ?? '';
$filterCategory = $_GET['category'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';

$filteredEquipment = array_filter($equipment, function($eq) use ($search, $filterCategory, $filterStatus) {
    if ($filterCategory != 'all' && $eq['category_id'] != $filterCategory) return false;
    if ($filterStatus != 'all' && $eq['status'] != $filterStatus) return false;
    if ($search) {
        $q = strtolower($search);
        return str_contains(strtolower($eq['name']), $q) ||
               str_contains(strtolower($eq['brand']), $q) ||
               str_contains(strtolower($eq['model']), $q) ||
               str_contains(strtolower($eq['serial_number']), $q);
    }
    return true;
});

function getStatusBadge($status) {
    switch($status){
        case 'available': return '<span class="badge bg-success">Available</span>';
        case 'maintenance': return '<span class="badge bg-warning">Maintenance</span>';
        case 'out_of_order': return '<span class="badge bg-danger">Out of Order</span>';
        default: return '<span class="badge bg-secondary">'.$status.'</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Owner Inventory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { margin:0; padding:0; background:#f8f9fa; }



/* Fixed sidebar */
#sidebar {
    position: fixed;
    top: 56px; /* navbar height */
    left: 0;
    bottom: 0;
    width: 250px;
    background: #fff;
    border-right: 1px solid #dee2e6;
    overflow-y: auto;
    padding-top: 20px;
}

/* Main content */
main {
    margin-top: 56px; /* navbar height */
    margin-left: 250px; /* sidebar width */
    height: calc(100vh - 56px);
    overflow-y: auto;
    padding: 20px;
}

/* Card hover effect */
.card { border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition:0.2s; cursor:pointer; }
.card:hover { transform: scale(1.02); }

/* Table scroll */
.table-responsive { max-height: 500px; overflow-y: auto; }
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main>
    <h2>Equipment Inventory</h2>
    <p>Manage gym equipment, maintenance, and categories.</p>

    <!-- Filters -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search equipment...">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="all">All Categories</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="all">All Status</option>
                <option value="available" <?= $filterStatus=='available'?'selected':'' ?>>Available</option>
                <option value="maintenance" <?= $filterStatus=='maintenance'?'selected':'' ?>>Maintenance</option>
                <option value="out_of_order" <?= $filterStatus=='out_of_order'?'selected':'' ?>>Out of Order</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- Equipment Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Serial</th>
                            <th>Purchase Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(count($filteredEquipment) > 0): ?>
                        <?php foreach($filteredEquipment as $eq): ?>
                        <tr>
                            <td><?= htmlspecialchars($eq['name']) ?></td>
                            <td><?= htmlspecialchars($eq['category_name']) ?></td>
                            <td><?= htmlspecialchars($eq['brand']) ?></td>
                            <td><?= htmlspecialchars($eq['model']) ?></td>
                            <td><?= htmlspecialchars($eq['serial_number']) ?></td>
                            <td><?= date('M d, Y', strtotime($eq['purchase_date'])) ?></td>
                            <td><?= getStatusBadge($eq['status']) ?></td>
                            <td>
    <a href="view_equipment.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-info" title="View">
        <i class="bi bi-eye"></i>
    </a>
    <a href="edit_equipment.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
        <i class="bi bi-pencil-square"></i>
    </a>
    <a href="delete_equipment.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
        <i class="bi bi-trash"></i>
    </a>
</td>

                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No equipment found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const logoutBtn = document.querySelector(".logout-btn");

  logoutBtn.addEventListener("click", function (e) {
    e.preventDefault();

    Swal.fire({
      title: "Logout Confirmation",
      text: "Are you sure you want to logout?",
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
});
</script>

</body>
</html>
