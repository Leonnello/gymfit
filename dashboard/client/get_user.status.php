<?php
include '../../db_connect.php';
$trainer_id=intval($_GET['trainer_id']??0);
if($trainer_id){
    $status=$conn->query("SELECT active_status FROM users WHERE id=$trainer_id")->fetch_assoc()['active_status']??'offline';
    echo json_encode(['status'=>$status]);
}
?>
