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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body { font-family: "Poppins", sans-serif; background: #f8f9fa; overflow-x: hidden; }
    .main-content { 
        margin-left: 250px; 
        margin-top: 56px; 
        padding: 30px; 
        min-height: 100vh; 
    }
    .card-stats .card { 
        border-radius: 10px; 
        box-shadow: 0 3px 6px rgba(0,0,0,0.1); 
        text-align: center; 
        padding: 20px; 
    }
    .card-stats .card i { 
        font-size: 24px; 
        margin-bottom: 5px; 
        color: #0d6efd; 
    }
    .table-responsive { 
        max-height: 500px; 
        overflow-y: auto; 
    }
    table th, table td { 
        vertical-align: middle; 
    }
    .modal-header { 
        border-bottom: none; 
    }
    .modal-footer { 
        border-top: none; 
    }

    
</style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <h2 class="mb-4 fw-bold">Equipment Inventory</h2>

    <!-- Stats Cards -->
    <div class="row mb-4 card-stats g-3">
        <div class="col-md-3"><div class="card">
            <i class="fas fa-dumbbell"></i>
            <div class="fs-5 mt-2">Total Equipment</div>
            <div class="fw-bold fs-4"><?= count($equipment) ?></div>
        </div></div>
        <div class="col-md-3"><div class="card">
            <i class="fas fa-money-bill-wave"></i>
            <div class="fs-5 mt-2">Equipment Value</div>
            <div class="fw-bold fs-4">₱<?= number_format($totalValue, 2) ?></div>
        </div></div>
        <div class="col-md-3"><div class="card">
            <i class="fas fa-tools"></i>
            <div class="fs-5 mt-2">Maintenance</div>
            <div class="fw-bold fs-4"><?= $totalMaintenance ?></div>
        </div></div>
        <div class="col-md-3"><div class="card">
            <i class="fas fa-ban"></i>
            <div class="fs-5 mt-2">Out of Order</div>
            <div class="fw-bold fs-4"><?= $totalOutOfOrder ?></div>
        </div></div>
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
    <!-- ADD EQUIPMENT BUTTON -->
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
        <i class="fas fa-plus me-1"></i> Add Equipment
    </button>
</div>

<!-- ADD EQUIPMENT MODAL -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-sm border-0">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Equipment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="add_equipment.php" method="POST">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><strong>Name</strong></label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Category</strong></label>
              <select name="category_id" class="form-select" required>
                <option value="" disabled selected>Select Category</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Brand</strong></label>
              <input type="text" name="brand" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Model</strong></label>
              <input type="text" name="model" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Serial Number</strong></label>
              <input type="text" name="serial_number" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Purchase Date</strong></label>
              <input type="date" name="purchase_date" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Purchase Price</strong></label>
              <input type="number" step="0.01" name="purchase_price" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Status</strong></label>
              <select name="status" class="form-select" required>
                <option value="available">Available</option>
                <option value="maintenance">Maintenance</option>
                <option value="out_of_order">Out of Order</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Last Maintenance</strong></label>
              <input type="date" name="last_maintenance" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label"><strong>Notes</strong></label>
              <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Equipment</button>
        </div>
      </form>
    </div>
  </div>
</div>


    <!-- Equipment Table -->
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover table-bordered align-middle mb-0">
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
                                <!-- VIEW MODAL -->
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $eq['id'] ?>"><i class="fas fa-eye"></i></button>

                                <!-- EDIT MODAL -->
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $eq['id'] ?>"><i class="fas fa-edit"></i></button>

<!-- DELETE -->
<?php if ($_SESSION['user']['role'] === 'admin'): ?>
    <a href="delete_equipment.php?id=<?= $eq['id'] ?>" 
       class="btn btn-sm btn-outline-danger" 
       onclick="return confirm('Are you sure you want to delete this equipment?')">
       <i class="fas fa-trash"></i>
    </a>
<?php else: ?>
    <button class="btn btn-sm btn-outline-secondary" disabled title="Only admin can delete">
        <i class="fas fa-trash"></i>
    </button>
<?php endif; ?>

                            </td>
                        </tr>

                        <!-- VIEW MODAL -->
                        <div class="modal fade" id="viewModal<?= $eq['id'] ?>" tabindex="-1">
                          <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content shadow-sm border-0">
                              <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Equipment Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <div class="row g-3">
                                  <div class="col-md-6"><strong>Name:</strong> <?= htmlspecialchars($eq['name']) ?></div>
                                  <div class="col-md-6"><strong>Category:</strong> <?= htmlspecialchars(array_search($eq['category_id'], array_column($categories, 'id')) !== false ? $categories[array_search($eq['category_id'], array_column($categories, 'id'))]['name'] : 'Unknown') ?></div>
                                  <div class="col-md-6"><strong>Brand:</strong> <?= htmlspecialchars($eq['brand']) ?></div>
                                  <div class="col-md-6"><strong>Model:</strong> <?= htmlspecialchars($eq['model']) ?></div>
                                  <div class="col-md-6"><strong>Serial Number:</strong> <?= htmlspecialchars($eq['serial_number']) ?></div>
                                  <div class="col-md-6"><strong>Purchase Date:</strong> <?= date('M d, Y', strtotime($eq['purchase_date'])) ?></div>
                                  <div class="col-md-6"><strong>Purchase Price:</strong> ₱<?= number_format($eq['purchase_price'],2) ?></div>
                                  <div class="col-md-6"><strong>Status:</strong> 
                                    <?php
                                    switch ($eq['status']) {
                                        case 'available': echo '<span class="badge bg-success">Available</span>'; break;
                                        case 'maintenance': echo '<span class="badge bg-warning text-dark">Maintenance</span>'; break;
                                        case 'out_of_order': echo '<span class="badge bg-danger">Out of Order</span>'; break;
                                    }
                                    ?>
                                  </div>
                                  <div class="col-md-6"><strong>Last Maintenance:</strong> <?= $eq['last_maintenance'] ?></div>
                                  <div class="col-12"><strong>Notes:</strong> <?= htmlspecialchars($eq['notes']) ?></div>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="modal fade" id="editModal<?= $eq['id'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-sm border-0">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Equipment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="update_equipment.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $eq['id'] ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><strong>Name</strong></label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($eq['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Category</strong></label>
              <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= $eq['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Brand</strong></label>
              <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($eq['brand']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Model</strong></label>
              <input type="text" name="model" class="form-control" value="<?= htmlspecialchars($eq['model']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Serial Number</strong></label>
              <input type="text" name="serial_number" class="form-control" value="<?= htmlspecialchars($eq['serial_number']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Purchase Date</strong></label>
              <input type="date" name="purchase_date" class="form-control" value="<?= $eq['purchase_date'] ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Purchase Price</strong></label>
              <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= $eq['purchase_price'] ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Status</strong></label>
              <select name="status" class="form-select" required>
                <option value="available" <?= $eq['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                <option value="maintenance" <?= $eq['status'] == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                <option value="out_of_order" <?= $eq['status'] == 'out_of_order' ? 'selected' : '' ?>>Out of Order</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Last Maintenance</strong></label>
              <input type="date" name="last_maintenance" class="form-control" value="<?= $eq['last_maintenance'] ?>">
            </div>
            <div class="col-12">
              <label class="form-label"><strong>Notes</strong></label>
              <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($eq['notes']) ?></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>




                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No equipment found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const logoutBtn = document.querySelector(".logout-btn");
    if(logoutBtn){
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
            }).then((result) => { if (result.isConfirmed) window.location.href = "../../logout.php"; });
        });
    }
});
</script>

</body>
</html>