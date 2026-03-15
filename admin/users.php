<?php
define('BASE_URL', '../');
session_start();
require_once '../db.php';
require_once 'auth_check.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) $_POST['user_id'];
    $action  = $_POST['action'];

    if ($user_id <= 0) {
        $error = "Invalid user.";
    } else {

        switch ($action) {

            case 'ban':
                mysqli_query($conn, "UPDATE users SET status='banned' WHERE id=$user_id");
                $message = "Player banned successfully.";
                break;

            case 'unban':
                mysqli_query($conn, "UPDATE users SET status='active' WHERE id=$user_id");
                $message = "Player unbanned successfully.";
                break;

            case 'grant_vip':
                mysqli_query($conn, "UPDATE users SET vip_status=1 WHERE id=$user_id");
                $message = "VIP status granted.";
                break;

            case 'revoke_vip':
                mysqli_query($conn, "UPDATE users SET vip_status=0 WHERE id=$user_id");
                $message = "VIP status revoked.";
                break;

            case 'delete':
                mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
                $message = "Player deleted. All their scores have been removed.";
                break;

            default:
                $error = "Unknown action.";
        }
    }
}

$search = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim(mysqli_real_escape_string($conn, $_GET['search']));
    $users  = mysqli_query($conn, "
        SELECT * FROM users
        WHERE username LIKE '%$search%' OR email LIKE '%$search%'
        ORDER BY created_at DESC
    ");
} else {
    $users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Players — Wolf Strike Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<?php include 'partials/navbar.php'; ?>

<div class="container-fluid px-4 py-4">

    <div class="admin-page-title mb-4">
        Manage Players
        <span class="admin-page-sub">Ban, unban, grant or revoke VIP, delete accounts</span>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success mb-4" style="background:rgba(29,158,117,0.15);
            border-color:rgba(29,158,117,0.3); color:#5DCAA5; border-radius:8px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger mb-4" style="background:rgba(226,75,74,0.15);
            border-color:rgba(226,75,74,0.3); color:#f09595; border-radius:8px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">

        <form method="GET" action="users.php" class="d-flex gap-2 mb-4">
            <input type="text" name="search" class="form-control game-input"
                   placeholder="Search by username or email..."
                   value="<?php echo htmlspecialchars($search); ?>"
                   style="max-width:320px;">
            <button type="submit" class="btn btn-game px-4" style="padding:8px 20px;">
                Search
            </button>
            <?php if ($search): ?>
                <a href="users.php" class="btn btn-action btn-unban px-3 d-flex align-items-center">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <?php if (mysqli_num_rows($users) > 0): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>VIP</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td style="color:rgba(255,255,255,0.3); font-size:0.8rem;">
                            #<?php echo $user['id']; ?>
                        </td>
                        <td style="font-weight:500;">
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if ($user['vip_status']): ?>
                                <span class="vip-badge">VIP</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:rgba(255,255,255,0.5); font-size:0.85rem;">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['vip_status']): ?>
                                <span style="color:#EF9F27; font-size:0.8rem; font-weight:600;">
                                    Active
                                </span>
                            <?php else: ?>
                                <span style="color:rgba(255,255,255,0.25); font-size:0.8rem;">
                                    None
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="color:rgba(255,255,255,0.4); font-size:0.8rem;">
                            <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">

                                <?php if ($user['status'] === 'active'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="ban">
                                        <button type="submit" class="btn-action btn-ban"
                                            onclick="return confirm('Ban <?php echo htmlspecialchars($user['username']); ?>?')">
                                            Ban
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="unban">
                                        <button type="submit" class="btn-action btn-unban">
                                            Unban
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!$user['vip_status']): ?>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="grant_vip">
                                        <button type="submit" class="btn-action btn-vip">
                                            Grant VIP
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="revoke_vip">
                                        <button type="submit" class="btn-action btn-vip">
                                            Revoke VIP
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-action btn-delete"
                                        onclick="return confirm('Permanently delete <?php echo htmlspecialchars($user['username']); ?> and all their scores?')">
                                        Delete
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted text-center py-4">
                <?php echo $search ? "No players found matching \"$search\"." : "No players registered yet."; ?>
            </p>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>