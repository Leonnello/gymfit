<?php
include 'db_connect.php';

if(isset($_GET['code'])){
    $code = $_GET['code'];

    $stmt = $conn->prepare("SELECT id, status FROM signup_requests WHERE verification_code=?");
    $stmt->bind_param("s",$code);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $user = $res->fetch_assoc();
        if($user['status']=='pending'){
            $update = $conn->prepare("UPDATE signup_requests SET status='active', verification_code=NULL WHERE id=?");
            $update->bind_param("i",$user['id']);
            $update->execute();
            echo "<script>alert('Email verified successfully! You can now log in.'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Email already verified.'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid verification code.'); window.location='login.php';</script>";
    }
} else {
    echo "<script>alert('No verification code provided.'); window.location='login.php';</script>";
}
?>
