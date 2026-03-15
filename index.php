<?php
session_start();
require_once 'db.php';
refresh_user_session($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username   = $_SESSION['username'];
$vip_status = $_SESSION['vip_status'];

$top_scores = mysqli_query($conn, "
    SELECT u.username, u.vip_status, s.score, s.agent, s.played_at
    FROM scores s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.score DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wolf Strike</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

<nav class="navbar navbar-dark px-4 py-3" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
    <span class="game-title" style="font-size:1.2rem; letter-spacing:4px;">WOLF STRIKE</span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:0.85rem;">
            Welcome, <span style="color:#7F77DD; font-weight:600;">
                <?php echo htmlspecialchars($username); ?>
            </span>
            <?php if ($vip_status): ?>
                <span class="vip-badge">VIP</span>
            <?php endif; ?>
        </span>
        <a href="profile.php" class="game-link" style="font-size:0.85rem;">Profile</a>
        <a href="leaderboard.php" class="game-link" style="font-size:0.85rem;">Leaderboard</a>
        <a href="logout.php" class="game-link" style="font-size:0.85rem; color:#f09595;">Logout</a>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">

        <div class="col-lg-6 text-center mb-5">
            <h1 class="game-title mb-3" style="font-size:3rem;">WOLF STRIKE</h1>
            <p class="text-muted mb-4" style="font-size:1rem; line-height:1.7;">
                5 rounds. Increasing danger. Only the sharpest wolf survives.<br>
                Choose your agent and enter the arena.
            </p>
            <a href="select_agent.php" class="btn btn-game px-5 py-3"
               style="font-size:1rem; letter-spacing:3px;">
                PLAY NOW
            </a>
        </div>

        <div class="col-lg-8">
            <div class="auth-card">
                <h5 class="mb-3" style="color:rgba(255,255,255,0.7);
                    letter-spacing:2px; font-size:0.85rem;">
                    TOP SCORES
                </h5>
                <?php if (mysqli_num_rows($top_scores) > 0): ?>
                <table class="table table-borderless mb-0" style="color:#e0e0e0;">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.08);
                            font-size:0.8rem; color:rgba(255,255,255,0.4);">
                            <th>#</th>
                            <th>Player</th>
                            <th>Agent</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; while ($row = mysqli_fetch_assoc($top_scores)): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05);
                            font-size:0.9rem;">
                            <td style="color:rgba(255,255,255,0.4);"><?php echo $rank++; ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['username']); ?>
                                <?php if ($row['vip_status']): ?>
                                    <span class="vip-badge">VIP</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:rgba(255,255,255,0.5);">
                                <?php echo $row['agent']; ?>
                            </td>
                            <td style="color:#7F77DD; font-weight:600;">
                                <?php echo number_format($row['score']); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted text-center py-3" style="font-size:0.9rem;">
                        No scores yet. Be the first to play.
                    </p>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="leaderboard.php" class="game-link" style="font-size:0.85rem;">
                        View full leaderboard →
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>