<?php

session_start();
include '../../db_connect.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// === AJAX endpoint: fetch booked slots for trainer & date ===
if (isset($_GET['fetch_booked']) && !empty($_GET['trainer_id']) && !empty($_GET['date'])) {
    $trainer_id = intval($_GET['trainer_id']);
    $date = $_GET['date'];

    // Use prepared statement to avoid injection
    $stmt = $conn->prepare("SELECT start_time, end_time FROM appointments WHERE trainer_id = ? AND date = ? AND status NOT IN ('cancelled','declined')");
    $stmt->bind_param("is", $trainer_id, $date);
    $stmt->execute();
    $res = $stmt->get_result();

    $booked = [];
    while ($r = $res->fetch_assoc()) {
        // normalize times to "HH:MM:SS" expected format
        $booked[] = [
            'start_time' => $r['start_time'],
            'end_time' => $r['end_time']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($booked);
    exit;
}

// Fetch all appointments of the logged-in client
$appointments_query = "
    SELECT a.*, CONCAT(t.firstName, ' ', t.lastName) AS trainer_name
    FROM appointments a
    JOIN users t ON a.trainer_id = t.id
    WHERE a.trainee_id = ?
    ORDER BY a.date DESC, a.start_time ASC
";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments_result = $stmt->get_result();

// Fetch available trainers
$trainers_query = "SELECT id, CONCAT(firstName, ' ', lastName) AS name, role FROM users WHERE role IN ('trainer','trainor') ORDER BY firstName";
$trainers_result = $conn->query($trainers_query);

// Fetch equipment list (ASSUMPTION: equipment table)
$equipment_query = "SELECT id, name, model, status FROM equipment ORDER BY name ASC";
$equipment_result = $conn->query($equipment_query);

// If a specific trainer is selected via GET (e.g., from dashboard)
$selected_trainer_id = isset($_GET['trainer_id']) ? intval($_GET['trainer_id']) : null;
$selected_trainer = null;
if ($selected_trainer_id) {
    $trainer_query = "SELECT id, CONCAT(firstName, ' ', lastName) AS name, role FROM users WHERE id = ? AND role IN ('trainer','trainor')";
    $stmt2 = $conn->prepare($trainer_query);
    $stmt2->bind_param("i", $selected_trainer_id);
    $stmt2->execute();
    $selected_trainer_result = $stmt2->get_result();
    if ($selected_trainer_result->num_rows > 0) {
        $selected_trainer = $selected_trainer_result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Schedule | GymFit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        main { margin-left: 250px; padding: 2rem; min-height: 100vh; background-color: #f9f9f9; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #d32f2f, #8b0000); color: white; border-top-left-radius: 10px; border-top-right-radius: 10px; padding: 1rem 1.5rem; }
        .form-label { font-weight: 500; color: #444; }
        .table thead { background-color: #f5f5f5; color: #333; }
        .btn-danger { background-color: #b71c1c; border: none; }
        .btn-danger:hover { background-color: #9a0007; }
        .badge { font-size: 0.8rem; }
        .btn-icon { display: inline-flex; align-items: center; justify-content: center; }

        .selected-trainer-card { background: linear-gradient(135deg, #e3f2fd, #f3e5f5); border-left: 4px solid #b71c1c; border-radius: 8px; }

        /* Time selection styles */
        .time-section { background: #f8f9fa; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .time-section-header { border-bottom: 2px solid #e9ecef; padding-bottom: 1rem; margin-bottom: 1rem; }
        .time-slots-container { background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; max-height: 200px; overflow-y: auto; }
        .time-slots-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 8px; }
        .time-slot-btn { padding: 8px 12px; border: 2px solid #e9ecef; border-radius: 6px; background: white; color: #495057; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.2s ease; text-align: center; }
        .time-slot-btn:hover { border-color: #b71c1c; background: #fff5f5; color: #b71c1c; }
        .time-slot-btn.selected { background: #b71c1c; border-color: #b71c1c; color: white; }
        .time-slot-btn:disabled { background: #f8f9fa; color: #6c757d; cursor: not-allowed; opacity: 0.6; }
        .time-input-feedback { font-size: 0.875rem; margin-top: 0.5rem; }
        .time-display { font-weight: 600; color: #b71c1c; margin-top: 0.5rem; }

        /* Receipt modal styles */
        .receipt-modal { max-width: 500px; }
        .receipt-container { background: white; border-radius: 8px; padding: 1.5rem; }
        .receipt-header { border-bottom: 2px solid #e9ecef; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .receipt-header h5 { color: #b71c1c; }
        .receipt-details { font-size: 0.95rem; }
        .receipt-row { display: flex; justify-content: space-between; margin-bottom: 1rem; align-items: center; }
        .receipt-label { color: #6c757d; font-weight: 500; }
        .receipt-value { color: #333; text-align: right; word-break: break-word; }
        .receipt-total { background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-top: 1rem; border: 2px solid #e9ecef; }
        .receipt-price { color: #b71c1c; font-size: 1.2rem; }

        @media (max-width: 768px) {
            main { margin-left: 0; padding: 1rem; }
            .time-slots-grid { grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); }
            .time-section { padding: 1rem; }
            .receipt-modal { max-width: 90vw; }
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>
    <main style="flex: 1;">
        <div class="container-fluid">
            <h3 class="fw-bold mb-4 text-danger"><i class="bi bi-calendar-event"></i> Training Schedule</h3>

            <?php if ($selected_trainer): ?>
                <div class="alert alert-info selected-trainer-card mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-check-fill me-3 fs-4"></i>
                        <div>
                            <h5 class="mb-1">Booking session with: <strong><?= htmlspecialchars($selected_trainer['name']) ?></strong></h5>
                            <p class="mb-0">You've selected <?= htmlspecialchars($selected_trainer['name']) ?> as your trainer. Fill out the form below to schedule your session.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7">
                    <!-- Booking Form -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Book a Training Session</h5>
                        </div>
                        <div class="card-body">
                            <form action="book_session.php" method="POST" id="bookingForm">
                                <div class="row g-3">
                                    <!-- Trainer Selection -->
                                    <div class="col-md-6">
                                        <label class="form-label">Trainer</label>
                                        <?php if ($selected_trainer): ?>
                                            <div class="form-control bg-light">
                                                <strong><?= htmlspecialchars($selected_trainer['name']) ?> (<?= $selected_trainer['role'] ?>)</strong>
                                            </div>
                                            <input type="hidden" name="trainer_id" value="<?= $selected_trainer['id'] ?>">
                                        <?php else: ?>
                                            <select name="trainer_id" class="form-select" required>
                                                <option value="">Select Trainer</option>
                                                <?php
                                                $trainers_result->data_seek(0);
                                                while ($t = $trainers_result->fetch_assoc()):
                                                ?>
                                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= $t['role'] ?>)</option>
                                                <?php endwhile; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Date -->
                                    <div class="col-md-6">
                                        <label class="form-label">Date</label>
                                        <input type="date" name="date" id="sessionDate" class="form-control" required min="<?= date('Y-m-d') ?>">
                                    </div>

                                    <!-- Training Regime -->
                                    <div class="col-md-6">
                                        <label class="form-label">Training Regime</label>
                                        <select name="training_regime" class="form-select" required>
                                            <option value="">Select Regime</option>
                                            <option value="full_body">Full Body</option>
                                            <option value="upper_body">Upper Body</option>
                                            <option value="lower_body">Lower Body</option>
                                            <option value="cardio">Cardio</option>
                                            <option value="strength">Strength</option>
                                            <option value="flexibility">Flexibility</option>
                                            <option value="hiit">HIIT</option>
                                            <option value="recovery">Recovery</option>
                                        </select>
                                    </div>

                                    <!-- Equipment Dropdown (ASSUMPTION: equipment table exists) -->
                                    <div class="col-md-6">
                                        <label class="form-label">Equipment</label>
                                        <select name="equipment_id" class="form-select" required>
                                            <option value="">Select Equipment</option>
                                            <?php
                                            if ($equipment_result && $equipment_result->num_rows > 0) {
                                                $equipment_result->data_seek(0);
                                                while ($eq = $equipment_result->fetch_assoc()):
                                                    $status = strtolower($eq['status']);
                                                    $disabled = ($status !== 'available') ? 'disabled' : '';
                                                    ?>
                                                    <option value="<?= $eq['id'] ?>" <?= $disabled ?>><?= htmlspecialchars($eq['name']) ?> <?= $disabled ? " (Unavailable)" : "" ?></option>
                                                <?php endwhile;
                                            } else { ?>
                                                <option value="">No equipment found</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Time section -->
                                <div class="time-section mt-4">
                                    <div class="time-section-header">
                                        <h6 class="mb-0 text-danger"><i class="bi bi-clock"></i> Session Time</h6>
                                        <small class="text-muted">Select your preferred start and end times</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                                            <div class="time-slots-container">
                                                <div class="time-slots-grid" id="startTimeSlots">
                                                    <!-- Start time slots generated by JS -->
                                                </div>
                                            </div>
                                            <input type="hidden" name="start_time" id="selectedStartTime" required>
                                            <div class="time-input-feedback text-muted">
                                                <span id="startTimeDisplay">No time selected</span>
                                            </div>
                                            <small class="text-muted">Available: 7:00 AM - 8:00 PM (30-min intervals)</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                                            <div class="time-slots-container">
                                                <div class="time-slots-grid" id="endTimeSlots">
                                                    <div class="time-slot-btn" style="grid-column: 1 / -1; background: none; border: none; color: #6c757d;">
                                                        <i class="bi bi-info-circle"></i> Please select start time first
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="end_time" id="selectedEndTime" required>
                                            <div class="time-input-feedback text-muted">
                                                <span id="endTimeDisplay">No time selected</span>
                                            </div>
                                            <small class="text-muted" id="endTimeHelp">End time will be available after selecting start time</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Duration & Notes -->
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Session Duration (Days)</label>
                                        <select name="session_days" class="form-select" required>
                                            <option value="">Select Duration</option>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>"><?= $i ?> <?= $i > 1 ? 'Days' : 'Day' ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-9 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions or preferences..."></textarea>
                                    </div>
                                </div>

                                <!-- Price Display -->
                                <div class="alert alert-info mb-3" id="priceSection" style="display: none;">
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <small class="text-muted">Training Type:</small>
                                            <div class="h6 text-danger"><span id="regimeType">-</span></div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Base Rate:</small>
                                            <div class="h6 text-danger"><span id="basePrice">₱0.00</span></div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Duration:</small>
                                            <div class="h6 text-danger"><span id="duration">0</span> hrs</div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Days:</small>
                                            <div class="h6 text-danger"><span id="sessionDays">1</span></div>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Cost per Session:</small>
                                            <div class="h6 text-danger"><span id="pricePerSession">₱0.00</span></div>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <strong>Total Cost:</strong>
                                            <h5 class="text-danger mb-0"><span id="totalPrice">₱0.00</span></h5>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="amount" id="amountInput" value="0">

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-danger px-4">
                                        <i class="bi bi-save"></i> Book Session
                                    </button>
                                    <?php if ($selected_trainer): ?>
                                        <a href="client_schedule.php" class="btn btn-outline-secondary px-4">
                                            <i class="bi bi-people"></i> View All Trainers
                                        </a>
                                    <?php endif; ?>
                                    <button type="reset" class="btn btn-outline-danger px-4 ms-auto">
                                        <i class="bi bi-arrow-clockwise"></i> Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Appointments Table -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Your Training Sessions</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($appointments_result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle text-center">
                                        <thead class="table-light">
<tr>
    <th>Date</th>
    <th>Trainer</th>
    <th>Time</th>
    <th>Regime</th>
    <th>Duration</th>
    <th>Amount</th>
    <th>Status</th>
    <th>Payment</th>
    <th>Created At</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php
$appointments_result->data_seek(0);
while ($a = $appointments_result->fetch_assoc()):
    $status = strtolower($a['status']);
    $badgeClass = match ($status) {
        'pending', 'accepted' => 'primary', // Active
        'completed' => 'success',
        'cancelled', 'declined' => 'secondary',
        default => 'light'
    };
    $statusText = in_array($status, ['pending','accepted']) ? 'Active' : ucfirst($a['status']);
?>
<tr>
    <td><?= date("M d, Y", strtotime($a['date'])) ?></td>
    <td><?= htmlspecialchars($a['trainer_name']) ?></td>
    <td><?= date("h:i A", strtotime($a['start_time'])) ?> - <?= date("h:i A", strtotime($a['end_time'])) ?></td>
    <td><?= ucfirst(str_replace('_', ' ', $a['training_regime'])) ?></td>
    <td><?= $a['session_days'] ?? '1' ?> Day(s)</td>
    <td><strong>₱<?= number_format(floatval($a['amount']), 2) ?></strong></td>
    <td><span class="badge bg-<?= $badgeClass ?>"><?= $statusText ?></span></td>
    <td><span class="badge bg-<?= $a['is_paid'] ? 'success' : 'danger' ?>"><?= $a['is_paid'] ? 'Paid' : 'Unpaid' ?></span></td>
    <td><?= date("M d, Y h:i A", strtotime($a['created_at'] ?? $a['date'])) ?></td>
    <td>
        <?php 
        $canPay = !$a['is_paid'] && !in_array($status, ['completed', 'cancelled', 'declined']);
        ?>
        <button type="button" class="btn btn-sm btn-outline-success btn-icon" data-bs-toggle="modal" data-bs-target="#updatePaymentModal<?= $a['id'] ?>" <?= !$canPay ? 'disabled' : '' ?> title="<?= !$canPay ? 'Cannot pay for this session' : 'Pay for this session' ?>">
            <i class="bi bi-cash-coin"></i>
        </button>
        <?php if (!in_array($status, ['completed', 'cancelled', 'declined'])): ?>
            <a href="cancel_session.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon ms-2">
                <i class="bi bi-x-circle"></i>
            </a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</tbody>

                                    </table>

                                    <!-- Payment Modals for each appointment -->
                                    <?php
                                    $appointments_result->data_seek(0);
                                    while ($a = $appointments_result->fetch_assoc()):
                                    ?>
                                    <div class="modal fade" id="updatePaymentModal<?= $a['id'] ?>" tabindex="-1" aria-labelledby="updatePaymentLabel<?= $a['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="updatePaymentLabel<?= $a['id'] ?>">Payment for Session</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Trainer:</strong> <?= htmlspecialchars($a['trainer_name']) ?></p>
                                                    <p><strong>Date:</strong> <?= date("M d, Y", strtotime($a['date'])) ?></p>
                                                    <p><strong>Time:</strong> <?= date("h:i A", strtotime($a['start_time'])) ?> - <?= date("h:i A", strtotime($a['end_time'])) ?></p>
                                                    <p><strong>Training Type:</strong> <?= ucfirst(str_replace('_', ' ', $a['training_regime'])) ?></p>
                                                    <hr>
                                                    <form action="update_payment.php" method="POST">
                                                        <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                                                        <div class="mb-3">
                                                            <label for="paymentAmount<?= $a['id'] ?>" class="form-label">Amount to Pay</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" class="form-control" id="paymentAmount<?= $a['id'] ?>" name="amount" value="<?= floatval($a['amount']) ?>" step="0.01" readonly>
                                                            </div>
                                                            <small class="text-muted">Amount is fixed based on your booking</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <p class="text-muted"><strong>Status:</strong> <span class="badge bg-<?= $a['is_paid'] ? 'success' : 'danger' ?>"><?= $a['is_paid'] ? 'Already Paid' : 'Pending Payment' ?></span></p>
                                                        </div>
                                                        <button type="submit" class="btn btn-danger w-100" <?= ($a['is_paid'] || in_array(strtolower($a['status']), ['completed', 'cancelled', 'declined'])) ? 'disabled' : '' ?>>
                                                            <i class="bi bi-check-circle"></i> Mark as Paid
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-3 mb-0">No booked sessions yet.</p>
                                    <small class="text-muted">Book your first training session using the form above.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right column: Equipment list + Quick info -->
                <div class="col-lg-5">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-gear-fill"></i> Equipment List</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($equipment_result && $equipment_result->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php
                                    $equipment_result->data_seek(0);
                                    while ($eq = $equipment_result->fetch_assoc()):
                                        $st = strtolower($eq['status']);
                                        $badge = $st === 'available' ? 'success' : ($st === 'in_use' ? 'warning' : 'secondary');
                                        ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($eq['name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($eq['model']) ?></div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?= $badge ?>"><?= ucfirst($eq['status']) ?></span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="mb-0 text-muted">No equipment registered yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Notes</h6>
                        </div>
                        <div class="card-body">
                            <ul class="small">
                                <li>Select a trainer and date to see available time slots. Booked time slots will be disabled automatically.</li>
                                <li>Equipment availability is shown. Unavailable equipment cannot be selected.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Generate time slots from 7:00 to 20:00 (8:00 PM) in 30-minute intervals
    function generateTimeSlots() {
        const slots = [];
        for (let hour = 7; hour <= 20; hour++) {
            for (let minute = 0; minute < 60; minute += 30) {
                // stop at 20:00 (8:00 PM)
                if (hour === 20 && minute > 0) break;
                const hh = hour.toString().padStart(2, '0');
                const mm = minute.toString().padStart(2, '0');
                const value = `${hh}:${mm}:00`; // keep seconds for comparison with DB
                const display = formatTimeForDisplay(`${hh}:${mm}`);
                slots.push({ value, display });
            }
        }
        return slots;
    }

    function formatTimeForDisplay(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    }

    const timeSlots = generateTimeSlots();
    let bookedTimes = []; // from server

    // utility to get trainer id (either hidden input or select)
    function getSelectedTrainerId() {
        const hidden = document.querySelector('input[name="trainer_id"][type="hidden"]');
        if (hidden) return hidden.value;
        const sel = document.querySelector('select[name="trainer_id"]');
        return sel ? sel.value : '';
    }

    // Fetch booked slots via AJAX when trainer or date changes
    const dateInput = document.getElementById('sessionDate');
    const trainerSelect = document.querySelector('select[name="trainer_id"]');

    (dateInput) && dateInput.addEventListener('change', fetchBookedTimes);
    (trainerSelect) && trainerSelect.addEventListener('change', fetchBookedTimes);

    async function fetchBookedTimes() {
        const trainer_id = getSelectedTrainerId();
        const date = dateInput.value;
        if (!trainer_id || !date) {
            bookedTimes = [];
            renderTimeSlots('startTimeSlots', timeSlots);
            document.getElementById('endTimeSlots').innerHTML = '<div class="time-slot-btn" style="grid-column:1 / -1; background:none; border:none; color:#6c757d;"><i class="bi bi-info-circle"></i> Please select start time first</div>';
            return;
        }

        try {
            const resp = await fetch(`client_schedule.php?fetch_booked=1&trainer_id=${encodeURIComponent(trainer_id)}&date=${encodeURIComponent(date)}`);
            if (!resp.ok) throw new Error('Failed to fetch booked times');
            bookedTimes = await resp.json();
            renderTimeSlots('startTimeSlots', timeSlots);
            // reset end slots
            document.getElementById('endTimeSlots').innerHTML = '<div class="time-slot-btn" style="grid-column:1 / -1; background:none; border:none; color:#6c757d;"><i class="bi bi-info-circle"></i> Please select start time first</div>';
        } catch (err) {
            console.error(err);
            bookedTimes = [];
            renderTimeSlots('startTimeSlots', timeSlots);
        }
    }

    function isTimeBooked(slotValue) {
        // slotValue format "HH:MM:SS"
        // bookedTimes entries: {start_time: "HH:MM:SS", end_time: "HH:MM:SS"}
        // A slot is considered booked if slotValue >= start_time AND slotValue < end_time
        for (const b of bookedTimes) {
            if (slotValue >= b.start_time && slotValue < b.end_time) {
                return true;
            }
        }
        return false;
    }

    function renderTimeSlots(containerId, slots, selectedTime = '') {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        const grid = document.createDocumentFragment();

        slots.forEach(slot => {
            const isBooked = isTimeBooked(slot.value);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'time-slot-btn' + (selectedTime === slot.value ? ' selected' : '');
            btn.textContent = slot.display;
            btn.dataset.time = slot.value;
            btn.disabled = isBooked;

            if (isBooked) {
                btn.style.background = '#f1f1f1';
                btn.style.color = '#888';
                btn.style.borderColor = '#ddd';
                btn.title = 'Booked';
            }

            btn.addEventListener('click', function () {
                if (btn.disabled) return;
                // deselect siblings
                container.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');

                if (containerId === 'startTimeSlots') {
                    document.getElementById('selectedStartTime').value = slot.value;
                    document.getElementById('startTimeDisplay').textContent = 'Selected: ' + slot.display;
                    document.getElementById('startTimeDisplay').className = 'time-input-feedback text-success';
                    updateEndTimeSlots(slot.value);
                    // Clear end time and price when start time changes
                    document.getElementById('selectedEndTime').value = '';
                    document.getElementById('endTimeDisplay').textContent = 'No time selected';
                    document.getElementById('endTimeDisplay').className = 'time-input-feedback text-muted';
                    // Trigger price calculation after a small delay
                    setTimeout(calculatePrice, 100);
                } else {
                    document.getElementById('selectedEndTime').value = slot.value;
                    document.getElementById('endTimeDisplay').textContent = 'Selected: ' + slot.display;
                    document.getElementById('endTimeDisplay').className = 'time-input-feedback text-success';
                    // Trigger price calculation when end time is selected
                    calculatePrice();
                }
            });

            grid.appendChild(btn);
        });

        container.appendChild(grid);
    }

    function updateEndTimeSlots(startTimeValue) {
        // startTimeValue is "HH:MM:SS"
        // end slots are those strictly after startTimeValue
        const startIndex = timeSlots.findIndex(s => s.value === startTimeValue);
        let available = [];
        if (startIndex !== -1) {
            available = timeSlots.slice(startIndex + 1);
            // additionally filter out end slots that are within booked ranges, or if the range from start->end overlaps a booked period
            // Simpler approach: disable any end slot where any time between start and that end crosses a booked interval start
            // For accuracy server-side validation in book_session.php is required.
        }

        if (available.length === 0) {
            document.getElementById('endTimeSlots').innerHTML = '<div class="time-slot-btn" style="grid-column:1 / -1; background:none; border:none; color:#6c757d;"><i class="bi bi-exclamation-circle"></i> No available end times</div>';
            return;
        }

        // Render and disable individual end slots if they themselves are booked,
        // and additionally ensure end > start
        const container = document.getElementById('endTimeSlots');
        container.innerHTML = '';
        const frag = document.createDocumentFragment();

        available.forEach(slot => {
            // for an end slot to be allowed, the full interval [start, end) must not intersect any booked interval
            const isInvalid = bookedTimes.some(b => {
                // overlap if start < b.end_time AND end > b.start_time
                return (startTimeValue < b.end_time) && (slot.value > b.start_time);
            });

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'time-slot-btn';
            btn.textContent = slot.display;
            btn.dataset.time = slot.value;
            btn.disabled = isInvalid;

            if (isInvalid) {
                btn.style.background = '#f1f1f1';
                btn.style.color = '#888';
                btn.style.borderColor = '#ddd';
                btn.title = 'Conflicts with existing booking';
            }

            btn.addEventListener('click', function () {
                if (btn.disabled) return;
                // deselect siblings
                container.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');

                document.getElementById('selectedEndTime').value = slot.value;
                document.getElementById('endTimeDisplay').textContent = 'Selected: ' + slot.display;
                document.getElementById('endTimeDisplay').className = 'time-input-feedback text-success';
                
                // Calculate price when end time is selected
                calculatePrice();
            });

            frag.appendChild(btn);
        });

        container.appendChild(frag);
        document.getElementById('endTimeHelp').textContent = 'Available end times (must be after start)';
        document.getElementById('selectedEndTime').value = '';
        document.getElementById('endTimeDisplay').textContent = 'No time selected';
        document.getElementById('endTimeDisplay').className = 'time-input-feedback text-muted';
    }

    // Initialize start time slots (no trainer/date selected yet => all enabled visually)
    renderTimeSlots('startTimeSlots', timeSlots);

    // Calculate price when trainer, start time, or end time changes
    async function calculatePrice() {
        const trainer_id = getSelectedTrainerId();
        const start_time = document.getElementById('selectedStartTime').value;
        const end_time = document.getElementById('selectedEndTime').value;
        const training_regime = document.querySelector('select[name="training_regime"]').value;
        const session_days = document.querySelector('select[name="session_days"]').value;
        const priceSection = document.getElementById('priceSection');

        // Check if all required fields are filled
        if (!trainer_id || !start_time || !end_time || !training_regime || !session_days) {
            // Show "Form incomplete" message
            priceSection.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong>Form Incomplete</strong> - Please fill in all required fields to see pricing
                </div>
            `;
            priceSection.style.display = 'block';
            document.getElementById('amountInput').value = '0';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('training_regime', training_regime);
            formData.append('start_time', start_time);
            formData.append('end_time', end_time);
            formData.append('session_days', session_days);

            console.log('Sending to calculate_price.php:', {training_regime, start_time, end_time, session_days});

            const response = await fetch('ajax/calculate_price.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}: Failed to calculate price`);
            const data = await response.json();

            console.log('Response from calculate_price.php:', data);

            // Check if there was an error in the response
            if (data.error) {
                const errorMsg = data.error;
                priceSection.innerHTML = `
                    <div class="alert alert-danger mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-circle-fill" data-bs-toggle="tooltip" data-bs-title="${errorMsg}"></i>
                        <span>Error calculating price</span>
                    </div>
                `;
                priceSection.style.display = 'block';
                // Initialize tooltip for this new element
                const tooltipElement = priceSection.querySelector('[data-bs-toggle="tooltip"]');
                if (tooltipElement && typeof bootstrap !== 'undefined') {
                    new bootstrap.Tooltip(tooltipElement);
                }
                document.getElementById('amountInput').value = '0';
                return;
            }

            if (data.success) {
                const regimeNames = {
                    'full_body': 'Full Body',
                    'upper_body': 'Upper Body',
                    'lower_body': 'Lower Body',
                    'cardio': 'Cardio',
                    'strength': 'Strength',
                    'flexibility': 'Flexibility',
                    'hiit': 'HIIT',
                    'recovery': 'Recovery'
                };

                const priceHTML = `
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <small class="text-muted">Training Type:</small>
                            <div class="h6 text-danger"><span id="regimeType">${regimeNames[data.regime] || data.regime}</span></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Base Rate:</small>
                            <div class="h6 text-danger"><span id="basePrice">₱${parseFloat(data.base_price).toFixed(2)}</span></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Duration:</small>
                            <div class="h6 text-danger"><span id="duration">${data.duration.toFixed(1)}</span> hrs</div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Days:</small>
                            <div class="h6 text-danger"><span id="sessionDays">${data.session_days}</span></div>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Cost per Session:</small>
                            <div class="h6 text-danger"><span id="pricePerSession">₱${parseFloat(data.price_per_session).toFixed(2)}</span></div>
                        </div>
                        <div class="col-md-6 text-end">
                            <strong>Total Cost:</strong>
                            <h5 class="text-danger mb-0"><span id="totalPrice">${data.formatted_price}</span></h5>
                        </div>
                    </div>
                `;

                priceSection.innerHTML = priceHTML;
                document.getElementById('amountInput').value = data.total_price;
                priceSection.style.display = 'block';
            } else {
                throw new Error('Invalid response from server');
            }
        } catch (err) {
            console.error('Price calculation error:', err);
            const errorMsg = err.message;
            priceSection.innerHTML = `
                <div class="alert alert-danger mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-circle-fill" data-bs-toggle="tooltip" data-bs-title="${errorMsg}"></i>
                    <span>Error calculating price</span>
                </div>
            `;
            priceSection.style.display = 'block';
            // Initialize tooltip for this new element
            const tooltipElement = priceSection.querySelector('[data-bs-toggle="tooltip"]');
            if (tooltipElement && typeof bootstrap !== 'undefined') {
                new bootstrap.Tooltip(tooltipElement);
            }
            document.getElementById('amountInput').value = '0';
        }
    }

    // Listen to changes on start/end times and trainer
    document.addEventListener('DOMContentLoaded', function() {
        // This is already in DOMContentLoaded, so we can use event delegation
    });

    // Add event listeners for price calculation
    const originalRenderTimeSlots = renderTimeSlots;
    const startTimeContainer = document.getElementById('startTimeSlots');
    const endTimeContainer = document.getElementById('endTimeSlots');

    // Override the renderTimeSlots function to add event listeners
    window.calculatePriceOnChange = calculatePrice;

    // Listen for changes
    document.addEventListener('change', function(e) {
        if (e.target.name === 'trainer_id') {
            setTimeout(calculatePrice, 500);
        }
        if (e.target.name === 'training_regime' || e.target.name === 'session_days') {
            setTimeout(calculatePrice, 100);
        }
    });

    // Modify the start time click handler to recalculate
    const originalStartClick = function() {
        setTimeout(calculatePrice, 100);
    };

    // For end time selection
    const originalEndClick = function() {
        setTimeout(calculatePrice, 100);
    };

    // Form validation: ensure start and end are set and end > start
    // Show confirmation modal before submission
    const bookingFormElement = document.getElementById('bookingForm');
    if (bookingFormElement) {
        bookingFormElement.addEventListener('submit', function (e) {
            console.log('Book form submitted!');
            e.preventDefault();
            
            const start = document.getElementById('selectedStartTime').value;
            const end = document.getElementById('selectedEndTime').value;
            
            console.log('Start time:', start, 'End time:', end);
            
            if (!start) {
                Swal.fire({ icon: 'error', title: 'Missing Start Time', text: 'Please select a start time for your session', confirmButtonColor: '#b71c1c' });
                return;
            }
            if (!end) {
                Swal.fire({ icon: 'error', title: 'Missing End Time', text: 'Please select an end time for your session', confirmButtonColor: '#b71c1c' });
                return;
            }
            if (end <= start) {
                Swal.fire({ icon: 'error', title: 'Invalid Time Selection', text: 'End time must be after start time', confirmButtonColor: '#b71c1c' });
                return;
            }
            
            // Show confirmation modal
            showBookingConfirmation();
        });
    } else {
        console.error('Booking form not found!');
    }

    // Show booking confirmation modal
    function showBookingConfirmation() {
        console.log('showBookingConfirmation called');
        
        // Get trainer info (could be hidden input or select)
        const trainerHidden = document.querySelector('input[name="trainer_id"][type="hidden"]');
        const trainerSelect = document.querySelector('select[name="trainer_id"]');
        
        let trainerId = '';
        let trainerName = '';
        
        if (trainerHidden) {
            trainerId = trainerHidden.value;
            trainerName = trainerHidden.parentElement.querySelector('strong')?.textContent || 'Selected Trainer';
        } else if (trainerSelect) {
            trainerId = trainerSelect.value;
            trainerName = trainerSelect.options[trainerSelect.selectedIndex].text;
        }
        
        const date = document.querySelector('input[name="date"]').value;
        const startTime = document.getElementById('selectedStartTime').value;
        const endTime = document.getElementById('selectedEndTime').value;
        const regime = document.querySelector('select[name="training_regime"]').value;
        const sessionDays = document.querySelector('select[name="session_days"]').value;
        const totalPrice = document.getElementById('totalPrice').textContent;
        const notes = document.querySelector('textarea[name="notes"]').value;

        console.log({trainerId, trainerName, date, startTime, endTime, regime, sessionDays, totalPrice});
        
        // Calculate duration for display
        const start = new Date(`2000-01-01 ${startTime}`);
        const end = new Date(`2000-01-01 ${endTime}`);
        const durationMs = end - start;
        const durationHours = durationMs / (1000 * 60 * 60);
        const durationMin = (durationHours % 1) * 60;
        let durationText = '';
        if (durationHours >= 1) {
            durationText = `${Math.floor(durationHours)} hr${Math.floor(durationHours) > 1 ? 's' : ''}`;
            if (durationMin > 0) {
                durationText += ` ${Math.round(durationMin)} min`;
            }
        } else {
            durationText = `${Math.round(durationMin)} min`;
        }

        const regimeLabel = document.querySelector('select[name="training_regime"]').options[document.querySelector('select[name="training_regime"]').selectedIndex].text;
        const formattedDate = new Date(date).toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });

        const confirmHtml = `
            <div class="receipt-container">
                <div class="receipt-header text-center mb-4">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Booking Receipt</h5>
                    <small class="text-muted">Please review your booking details</small>
                </div>
                <div class="receipt-details">
                    <div class="receipt-row">
                        <span class="receipt-label">Trainer:</span>
                        <span class="receipt-value"><strong>${trainerName}</strong></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Date:</span>
                        <span class="receipt-value"><strong>${formattedDate}</strong></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Time:</span>
                        <span class="receipt-value"><strong>${startTime} - ${endTime}</strong></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Duration:</span>
                        <span class="receipt-value"><strong>${durationText}</strong></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Training Type:</span>
                        <span class="receipt-value"><strong>${regimeLabel}</strong></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Number of Days:</span>
                        <span class="receipt-value"><strong>${sessionDays} ${sessionDays > 1 ? 'Days' : 'Day'}</strong></span>
                    </div>
                    ${notes ? `<div class="receipt-row">
                        <span class="receipt-label">Notes:</span>
                        <span class="receipt-value"><em>${notes}</em></span>
                    </div>` : ''}
                    <hr class="my-3">
                    <div class="receipt-row receipt-total">
                        <span class="receipt-label"><strong>Total Cost:</strong></span>
                        <span class="receipt-value receipt-price"><strong>${totalPrice}</strong></span>
                    </div>
                </div>
            </div>
        `;

        Swal.fire({
            title: 'Confirm Booking',
            html: confirmHtml,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Confirm & Book',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#b71c1c',
            cancelButtonColor: '#6c757d',
            customClass: {
                popup: 'receipt-modal'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form
                document.getElementById('bookingForm').submit();
            }
        });
    };

    // Reset handler - clear selections and re-render
    document.getElementById('bookingForm').addEventListener('reset', function () {
        setTimeout(() => {
            document.getElementById('selectedStartTime').value = '';
            document.getElementById('selectedEndTime').value = '';
            document.getElementById('startTimeDisplay').textContent = 'No time selected';
            document.getElementById('startTimeDisplay').className = 'time-input-feedback text-muted';
            document.getElementById('endTimeDisplay').textContent = 'No time selected';
            document.getElementById('endTimeDisplay').className = 'time-input-feedback text-muted';
            renderTimeSlots('startTimeSlots', timeSlots);
            document.getElementById('endTimeSlots').innerHTML = '<div class="time-slot-btn" style="grid-column:1 / -1; background:none; border:none; color:#6c757d;"><i class="bi bi-info-circle"></i> Please select start time first</div>';
        }, 50);
    });

    // If page loaded with preselected trainer/date (e.g., selected trainer), fetch booked times
    if (dateInput && dateInput.value && getSelectedTrainerId()) {
        fetchBookedTimes();
    }
    
    // Trigger price calculation on page load if all fields are available
    setTimeout(calculatePrice, 500);
});
</script>

</body>
</html>
