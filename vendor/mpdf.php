<?php
require_once __DIR__ . '../../vendor/autoload.php';
include '../../db_connect.php';

$mpdf = new \Mpdf\Mpdf();
$html = "<h2>Feedback Report</h2><table border='1' cellpadding='5'>
<tr><th>User</th><th>Rating</th><th>Comments</th><th>Date</th></tr>";

$query = "SELECT users.name, feedback.rating, feedback.comments, feedback.created_at 
          FROM feedback 
          INNER JOIN users ON feedback.user_id = users.id";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $html .= "<tr>
                <td>{$row['name']}</td>
                <td>{$row['rating']}</td>
                <td>{$row['comments']}</td>
                <td>{$row['created_at']}</td>
              </tr>";
}
$html .= "</table>";

$mpdf->WriteHTML($html);
$mpdf->Output('feedback_report.pdf', 'D');
?>
