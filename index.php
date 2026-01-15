<?php
session_start();

// If user is logged in, redirect based on role
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];

    switch ($role) {
        case 'admin':
            header("Location: dashboard/admin/index.php");
            exit;

        case 'trainer':
            header("Location: dashboard/trainer/index.php");
            exit;

        case 'trainee':
            header("Location: dashboard/trainee/index.php");
            exit;

        default:
            // Unknown role, force logout
            session_destroy();
            header("Location: login.php");
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GymFit | Train Smart. Stay Fit.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-box {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 15px;
            text-align: center;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .hero-box h1 {
            font-weight: 700;
            margin-bottom: 15px;
        }

        .hero-box p {
            color: #cbd5f5;
            margin-bottom: 30px;
        }

        .btn-primary {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
        }

        .btn-outline-light {
            padding: 12px 30px;
            border-radius: 50px;
        }
    </style>
</head>
<body>

<div class="hero-box">
    <h1>GymFit</h1>
    <p>Train smarter. Manage better. Stay fit.</p>

    <div class="d-grid gap-3">
        <a href="login.php" class="btn btn-primary">Login</a>
        <a href="register.php" class="btn btn-outline-light">Create Account</a>
    </div>
</div>

</body>
</html>
