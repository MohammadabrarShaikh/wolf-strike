<?php
session_start();
require_once 'db.php';
refresh_user_session($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int) $_SESSION['user_id'];

$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)            AS total_games,
        MAX(score)          AS best_score,
        SUM(kills)          AS total_kills,
        AVG(score)          AS avg_score,
        MAX(rounds_survived) AS best_rounds
    FROM scores
    WHERE user_id = $uid
"));

$history = mysqli_query($conn, "
    SELECT score, agent, kills, rounds_survived, played_at
    FROM scores
    WHERE user_id = $uid
    ORDER BY played_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — Wolf Strike</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

<nav class="navbar navbar-dark px-4 py-3"
     style="border-bottom:1px solid rgba(255,255,255,0.08);">
    <span class="game-title" style="font-size:1.2rem; letter-spacing:4px;">WOLF STRIKE</span>
    <div class="d-flex gap-3">
        <a href="index.php" class="game-link" style="font-size:0.85rem;">Home</a>
        <a href="leaderboard.php" class="game-link" style="font-size:0.85rem;">Leaderboard</a>
        <a href="logout.php" class="game-link"
           style="font-size:0.85rem; color:#f09595;">Logout</a>
    </div>
</nav>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="auth-card mb-4">
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div style="width:64px; height:64px; border-radius:50%;
                        background:rgba(127,119,221,0.2);
                        border:2px solid rgba(127,119,221,0.4);
                        display:flex; align-items:center; justify-content:center;
                        font-size:1.5rem; font-weight:700; color:#7F77DD; flex-shrink:0;">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-size:1.2rem; font-weight:600; color:#ffffff;">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if ($_SESSION['vip_status']): ?>
                                <span class="vip-badge">VIP</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.8rem; color:rgba(255,255,255,0.4); margin-top:3px;">
                            Wolf Agent
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="auth-card text-center">
                        <div style="font-size:1.6rem; font-weight:600; color:#7F77DD;">
                            <?php echo (int)($stats['total_games'] ?? 0); ?>
                        </div>
                        <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);
                            letter-spacing:1px; margin-top:4px;">GAMES</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="auth-card text-center">
                        <div style="font-size:1.6rem; font-weight:600; color:#1D9E75;">
                            <?php echo number_format($stats['best_score'] ?? 0); ?>
                        </div>
                        <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);
                            letter-spacing:1px; margin-top:4px;">BEST SCORE</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="auth-card text-center">
                        <div style="font-size:1.6rem; font-weight:600; color:#E24B4A;">
                            <?php echo (int)($stats['total_kills'] ?? 0); ?>
                        </div>
                        <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);
                            letter-spacing:1px; margin-top:4px;">TOTAL KILLS</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="auth-card text-center">
                        <div style="font-size:1.6rem; font-weight:600; color:#EF9F27;">
                            <?php echo number_format($stats['avg_score'] ?? 0); ?>
                        </div>
                        <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);
                            letter-spacing:1px; margin-top:4px;">AVG SCORE</div>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <h6 style="color:rgba(255,255,255,0.4); letter-spacing:2px;
                    font-size:0.75rem; margin-bottom:16px;">GAME HISTORY</h6>

                <?php if (mysqli_num_rows($history) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-borderless mb-0" style="color:#e0e0e0;">
                        <thead>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.08);
                                font-size:0.75rem; color:rgba(255,255,255,0.3);
                                letter-spacing:1px;">
                                <th>Date</th>
                                <th>Agent</th>
                                <th>Score</th>
                                <th>Kills</th>
                                <th>Rounds</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($history)): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.05);
                                font-size:0.875rem;">
                                <td style="color:rgba(255,255,255,0.4); font-size:0.8rem;">
                                    <?php echo date('d M Y · H:i', strtotime($row['played_at'])); ?>
                                </td>
                                <td>
                                    <span class="agent-badge
                                        agent-<?php echo strtolower($row['agent']); ?>">
                                        <?php echo $row['agent']; ?>
                                    </span>
                                </td>
                                <td style="color:#7F77DD; font-weight:600;">
                                    <?php echo number_format($row['score']); ?>
                                </td>
                                <td style="color:rgba(255,255,255,0.7);">
                                    <?php echo $row['kills']; ?>
                                </td>
                                <td style="color:rgba(255,255,255,0.7);">
                                    <?php echo $row['rounds_survived']; ?>/5
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4" style="font-size:0.9rem;">
                        No games played yet. Enter the arena and make your mark.
                    </p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>