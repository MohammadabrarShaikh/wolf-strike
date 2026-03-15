<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Both fields are required.";
    } else {
        $query  = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
        $user   = mysqli_fetch_assoc($query);

        if (!$user) {
            $error = "Invalid username or password.";

        } elseif ($user['status'] === 'banned') {
            $error = "Your account has been banned. Contact support.";

        } elseif (!password_verify($password, $user['password'])) {
            $error = "Invalid username or password.";

        } else {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['vip_status'] = $user['vip_status'];

            header("Location: index.php");
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
    <title>Login — Wolf Strike</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="auth-card">

        <div class="text-center mb-4">
            <h1 class="game-title">WOLF STRIKE</h1>
            <p class="text-muted">Login to play</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control game-input"
                       placeholder="Enter your username" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control game-input"
                       placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-game w-100">LOGIN</button>
        </form>

        <p class="text-center mt-3 text-muted">
            No account yet? <a href="register.php" class="game-link">Register here</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>