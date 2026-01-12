<?php
session_start();
include '../../db_connect.php';
require_once '../../vendor/autoload.php'; // ✅ For TCPDF (PDF generation)

// --- Access check ---
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
  header("Location: ../../login.php");
  exit;
}

$type = $_GET['type'] ?? 'csv';
$filter = $_GET['filter'] ?? 'monthly';

// --- Filter format ---
switch ($filter) {
  case 'daily':
    $labelFormat = "%Y-%m-%d";
    $title = "Daily";
    break;
  case 'weekly':
    $labelFormat = "%x-W%v";
    $title = "Weekly";
    break;
  case 'yearly':
    $labelFormat = "%Y";
    $title = "Yearly";
    break;
  default:
    $labelFormat = "%Y-%m";
    $title = "Monthly";
    break;
}

$query = "
  SELECT DATE_FORMAT(date, '$labelFormat') AS period,
         SUM(amount) AS total_revenue
  FROM appointments
  WHERE is_paid = 1
  GROUP BY period
  ORDER BY period ASC
";
$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

if ($type == 'csv') {
  header("Content-Type: text/csv");
  header("Content-Disposition: attachment; filename=revenue_report_$filter.csv");
  $output = fopen("php://output", "w");
  fputcsv($output, ['Period', 'Total Revenue']);
  foreach ($data as $row) {
    fputcsv($output, [$row['period'], $row['total_revenue']]);
  }
  fclose($output);
  exit;
}

// --- PDF Export ---
if ($type == 'pdf') {
  $pdf = new TCPDF();
  $pdf->AddPage();
  $pdf->SetFont('helvetica', '', 12);
  $pdf->Cell(0, 10, "GymFit $title Revenue Report", 0, 1, 'C');
  $pdf->Ln(5);

  $html = '<table border="1" cellpadding="6">
    <tr><th><b>Period</b></th><th><b>Total Revenue (₱)</b></th></tr>';
  foreach ($data as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['period']) . '</td><td>₱' . number_format($row['total_revenue'], 2) . '</td></tr>';
  }
  $html .= '</table>';

  $pdf->writeHTML($html);
  $pdf->Output("revenue_report_$filter.pdf", "D");
  exit;
}
?>
