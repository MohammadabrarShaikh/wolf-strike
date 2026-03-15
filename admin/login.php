<?php
define('BASE_URL', '../');
session_start();
require_once '../db.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Both fields are required.";
    } else {
        $query = mysqli_query($conn, "SELECT * FROM admins WHERE username='$username'");
        $admin = mysqli_fetch_assoc($query);

        if (!$admin || !password_verify($password, $admin['password'])) {
            $error = "Invalid admin credentials.";
        } else {
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: dashboard.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Wolf Strike</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="auth-card">

        <div class="text-center mb-4">
            <h1 class="game-title" style="font-size:1.5rem;">WOLF STRIKE</h1>
            <p class="text-muted mt-1" style="font-size:0.85rem; letter-spacing:2px;">
                ADMIN PANEL
            </p>
            <div style="width:40px; height:2px; background:#E24B4A;
                margin:10px auto 0; border-radius:2px;"></div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label">Admin Username</label>
                <input type="text" name="username" class="form-control game-input"
                       placeholder="Enter admin username" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control game-input"
                       placeholder="Enter admin password" required>
            </div>
            <button type="submit" class="btn btn-game w-100"
                    style="background:linear-gradient(135deg, #E24B4A, #A32D2D);">
                ACCESS PANEL
            </button>
        </form>

        <p class="text-center mt-3">
            <a href="../index.php" class="game-link"
               style="font-size:0.8rem; color:rgba(255,255,255,0.3);">
                ← Back to game
            </a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>