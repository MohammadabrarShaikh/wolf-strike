<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wolf_strike_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

function refresh_user_session($conn) {
    if (isset($_SESSION['user_id'])) {
        $id    = (int) $_SESSION['user_id'];
        $query = mysqli_query($conn, "SELECT vip_status, status FROM users WHERE id=$id");
        $user  = mysqli_fetch_assoc($query);

        if (!$user || $user['status'] === 'banned') {
            session_unset();
            session_destroy();
            header("Location: login.php");
            exit();
        }

        $_SESSION['vip_status'] = $user['vip_status'];
    }
}
?>