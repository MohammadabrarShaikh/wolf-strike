<?php
define('BASE_URL', '../');
session_start();
require_once '../db.php';
require_once 'auth_check.php';

$total_users  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0];
$total_games  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM scores"))[0];
$total_kills  = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(kills) FROM scores"))[0] ?? 0;
$highest_score = mysqli_fetch_row(mysqli_query($conn, "SELECT MAX(score) FROM scores"))[0] ?? 0;
$total_banned = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE status='banned'"))[0];
$total_vip    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE vip_status=1"))[0];

$recent_scores = mysqli_query($conn, "
    SELECT u.username, u.vip_status, s.score, s.agent, s.kills, s.rounds_survived, s.played_at
    FROM scores s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.played_at DESC
    LIMIT 8
");

$recent_users = mysqli_query($conn, "
    SELECT username, email, status, vip_status, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Wolf Strike</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<?php include 'partials/navbar.php'; ?>

<div class="container-fluid px-4 py-4">

    <div class="admin-page-title mb-4">
        Dashboard
        <span class="admin-page-sub">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(127,119,221,0.15); color:#7F77DD;">P</div>
                <div class="stat-val"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Total Players</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(29,158,117,0.15); color:#1D9E75;">G</div>
                <div class="stat-val"><?php echo number_format($total_games); ?></div>
                <div class="stat-label">Games Played</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(226,75,74,0.15); color:#E24B4A;">K</div>
                <div class="stat-val"><?php echo number_format($total_kills); ?></div>
                <div class="stat-label">Total Kills</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(239,159,39,0.15); color:#EF9F27;">S</div>
                <div class="stat-val"><?php echo number_format($highest_score); ?></div>
                <div class="stat-label">Highest Score</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="border-color:rgba(226,75,74,0.2);">
                <div class="stat-val" style="color:#E24B4A;"><?php echo $total_banned; ?></div>
                <div class="stat-label">Banned Players</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card" style="border-color:rgba(239,159,39,0.2);">
                <div class="stat-val" style="color:#EF9F27;"><?php echo $total_vip; ?></div>
                <div class="stat-label">VIP Players</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-lg-7">
            <div class="admin-card">
                <div class="admin-card-title">Recent games</div>
                <?php if (mysqli_num_rows($recent_scores) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Agent</th>
                            <th>Score</th>
                            <th>Kills</th>
                            <th>Rounds</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_scores)): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['username']); ?>
                                <?php if ($row['vip_status']): ?>
                                    <span class="vip-badge">VIP</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="agent-badge agent-<?php echo strtolower($row['agent']); ?>"><?php echo $row['agent']; ?></span></td>
                            <td style="color:#7F77DD; font-weight:600;"><?php echo number_format($row['score']); ?></td>
                            <td><?php echo $row['kills']; ?></td>
                            <td><?php echo $row['rounds_survived']; ?>/5</td>
                            <td style="color:rgba(255,255,255,0.4); font-size:0.8rem;"><?php echo date('d M y', strtotime($row['played_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted text-center py-3" style="font-size:0.9rem;">No games played yet.</p>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="scores.php" class="game-link" style="font-size:0.85rem;">View all scores →</a>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="admin-card">
                <div class="admin-card-title">Recent registrations</div>
                <?php if (mysqli_num_rows($recent_users) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_users)): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['username']); ?>
                                <?php if ($row['vip_status']): ?>
                                    <span class="vip-badge">VIP</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td style="color:rgba(255,255,255,0.4); font-size:0.8rem;"><?php echo date('d M y', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted text-center py-3" style="font-size:0.9rem;">No players yet.</p>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="users.php" class="game-link" style="font-size:0.85rem;">Manage players →</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>