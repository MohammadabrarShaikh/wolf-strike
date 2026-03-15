<?php
define('BASE_URL', '../');
session_start();
require_once '../db.php';
require_once 'auth_check.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score_id = (int) $_POST['score_id'];

    if ($score_id <= 0) {
        $error = "Invalid score record.";
    } else {
        mysqli_query($conn, "DELETE FROM scores WHERE id=$score_id");
        $message = "Score record deleted successfully.";
    }
}

$filter_user  = '';
$filter_agent = '';

if (isset($_GET['username']) && !empty(trim($_GET['username']))) {
    $filter_user = trim(mysqli_real_escape_string($conn, $_GET['username']));
}

if (isset($_GET['agent']) && !empty($_GET['agent'])) {
    $filter_agent = mysqli_real_escape_string($conn, $_GET['agent']);
}

$where = [];
if ($filter_user)  $where[] = "u.username LIKE '%$filter_user%'";
if ($filter_agent) $where[] = "s.agent = '$filter_agent'";
$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$scores = mysqli_query($conn, "
    SELECT s.*, u.username, u.vip_status
    FROM scores s
    JOIN users u ON s.user_id = u.id
    $where_sql
    ORDER BY s.score DESC
");

$total_filtered = mysqli_num_rows($scores);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Scores — Wolf Strike Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<?php include 'partials/navbar.php'; ?>

<div class="container-fluid px-4 py-4">

    <div class="admin-page-title mb-4">
        Manage Scores
        <span class="admin-page-sub">View and delete game records — filter by player or agent</span>
    </div>

    <?php if ($message): ?>
        <div class="alert mb-4" style="background:rgba(29,158,117,0.15);
            border-color:rgba(29,158,117,0.3); color:#5DCAA5; border-radius:8px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert mb-4" style="background:rgba(226,75,74,0.15);
            border-color:rgba(226,75,74,0.3); color:#f09595; border-radius:8px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">

        <form method="GET" action="scores.php" class="d-flex gap-2 mb-4 flex-wrap">
            <input type="text" name="username" class="form-control game-input"
                   placeholder="Filter by username..."
                   value="<?php echo htmlspecialchars($filter_user); ?>"
                   style="max-width:220px;">
            <select name="agent" class="form-control game-input" style="max-width:160px;">
                <option value="">All agents</option>
                <?php foreach (['Scout','Hunter','Alpha','Phantom'] as $a): ?>
                    <option value="<?php echo $a; ?>"
                        <?php echo $filter_agent === $a ? 'selected' : ''; ?>>
                        <?php echo $a; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-game px-4" style="padding:8px 20px;">
                Filter
            </button>
            <?php if ($filter_user || $filter_agent): ?>
                <a href="scores.php" class="btn btn-action btn-unban px-3 d-flex align-items-center">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <div class="mb-3" style="font-size:0.8rem; color:rgba(255,255,255,0.3);">
            Showing <?php echo $total_filtered; ?> record<?php echo $total_filtered !== 1 ? 's' : ''; ?>
        </div>

        <?php if ($total_filtered > 0): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Player</th>
                        <th>Agent</th>
                        <th>Score</th>
                        <th>Kills</th>
                        <th>Rounds</th>
                        <th>Played</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($scores)): ?>
                    <tr>
                        <td style="color:rgba(255,255,255,0.3); font-size:0.8rem;">
                            #<?php echo $row['id']; ?>
                        </td>
                        <td style="font-weight:500;">
                            <?php echo htmlspecialchars($row['username']); ?>
                            <?php if ($row['vip_status']): ?>
                                <span class="vip-badge">VIP</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="agent-badge agent-<?php echo strtolower($row['agent']); ?>">
                                <?php echo $row['agent']; ?>
                            </span>
                        </td>
                        <td style="color:#7F77DD; font-weight:600;">
                            <?php echo number_format($row['score']); ?>
                        </td>
                        <td><?php echo $row['kills']; ?></td>
                        <td><?php echo $row['rounds_survived']; ?>/5</td>
                        <td style="color:rgba(255,255,255,0.4); font-size:0.8rem;">
                            <?php echo date('d M Y', strtotime($row['played_at'])); ?>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="score_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-action btn-delete"
                                    onclick="return confirm('Delete this score record permanently?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted text-center py-4">
                <?php echo ($filter_user || $filter_agent) ? "No records match your filter." : "No game records yet."; ?>
            </p>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>