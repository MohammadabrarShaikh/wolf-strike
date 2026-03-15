<?php
session_start();
require_once 'db.php';
refresh_user_session($conn);

$top_scores = mysqli_query($conn, "
    SELECT u.username, u.vip_status, s.score, s.agent,
           s.kills, s.rounds_survived, s.played_at
    FROM scores s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.score DESC
    LIMIT 10
");

$user_best = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    $user_best = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT score, agent, kills, rounds_survived, played_at
        FROM scores
        WHERE user_id = $uid
        ORDER BY score DESC
        LIMIT 1
    "));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard — Wolf Strike</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

<nav class="navbar navbar-dark px-4 py-3"
     style="border-bottom:1px solid rgba(255,255,255,0.08);">
    <span class="game-title" style="font-size:1.2rem; letter-spacing:4px;">WOLF STRIKE</span>
    <div class="d-flex gap-3">
        <a href="index.php" class="game-link" style="font-size:0.85rem;">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="game-link" style="font-size:0.85rem;">Profile</a>
            <a href="logout.php" class="game-link"
               style="font-size:0.85rem; color:#f09595;">Logout</a>
        <?php else: ?>
            <a href="login.php" class="game-link" style="font-size:0.85rem;">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container py-5">

    <div class="text-center mb-5">
        <h2 style="color:#ffffff; letter-spacing:4px; font-weight:500;">LEADERBOARD</h2>
        <p class="text-muted" style="font-size:0.9rem;">Top 10 wolf agents of all time</p>
    </div>

    <?php if ($user_best): ?>
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="auth-card" style="border-color:rgba(127,119,221,0.3);
                background:rgba(127,119,221,0.05);">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div style="font-size:0.75rem; color:rgba(255,255,255,0.4);
                            letter-spacing:2px; margin-bottom:4px;">YOUR BEST</div>
                        <div style="font-size:1.5rem; font-weight:600; color:#7F77DD;">
                            <?php echo number_format($user_best['score']); ?>
                            <span style="font-size:0.8rem; color:rgba(255,255,255,0.4);
                                font-weight:400; margin-left:8px;">points</span>
                        </div>
                    </div>
                    <div class="d-flex gap-4">
                        <div class="text-center">
                            <div style="font-size:1rem; font-weight:600; color:#ffffff;">
                                <?php echo $user_best['kills']; ?>
                            </div>
                            <div style="font-size:0.7rem; color:rgba(255,255,255,0.4);">
                                KILLS
                            </div>
                        </div>
                        <div class="text-center">
                            <div style="font-size:1rem; font-weight:600; color:#ffffff;">
                                <?php echo $user_best['rounds_survived']; ?>/5
                            </div>
                            <div style="font-size:0.7rem; color:rgba(255,255,255,0.4);">
                                ROUNDS
                            </div>
                        </div>
                        <div class="text-center">
                            <span class="agent-badge agent-<?php echo strtolower($user_best['agent']); ?>">
                                <?php echo $user_best['agent']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="auth-card">
                <?php if (mysqli_num_rows($top_scores) > 0): ?>
                <table class="table table-borderless mb-0" style="color:#e0e0e0;">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.08);
                            font-size:0.75rem; color:rgba(255,255,255,0.3);
                            letter-spacing:1px;">
                            <th style="width:50px;">#</th>
                            <th>Player</th>
                            <th>Agent</th>
                            <th>Kills</th>
                            <th>Rounds</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        while ($row = mysqli_fetch_assoc($top_scores)):
                        $is_current = isset($_SESSION['username']) &&
                                      $row['username'] === $_SESSION['username'];
                        ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05);
                            font-size:0.9rem;
                            <?php echo $is_current ?
                                'background:rgba(127,119,221,0.06);' : ''; ?>">
                            <td>
                                <?php if ($rank === 1): ?>
                                    <span style="color:#EF9F27; font-weight:700;">1</span>
                                <?php elseif ($rank === 2): ?>
                                    <span style="color:#B4B2A9; font-weight:700;">2</span>
                                <?php elseif ($rank === 3): ?>
                                    <span style="color:#D85A30; font-weight:700;">3</span>
                                <?php else: ?>
                                    <span style="color:rgba(255,255,255,0.3);">
                                        <?php echo $rank; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:500;">
                                <?php echo htmlspecialchars($row['username']); ?>
                                <?php if ($row['vip_status']): ?>
                                    <span class="vip-badge">VIP</span>
                                <?php endif; ?>
                                <?php if ($is_current): ?>
                                    <span style="font-size:0.7rem;
                                        color:rgba(127,119,221,0.7);
                                        margin-left:4px;">you</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="agent-badge
                                    agent-<?php echo strtolower($row['agent']); ?>">
                                    <?php echo $row['agent']; ?>
                                </span>
                            </td>
                            <td style="color:rgba(255,255,255,0.6);">
                                <?php echo $row['kills']; ?>
                            </td>
                            <td style="color:rgba(255,255,255,0.6);">
                                <?php echo $row['rounds_survived']; ?>/5
                            </td>
                            <td style="color:#7F77DD; font-weight:700;">
                                <?php echo number_format($row['score']); ?>
                            </td>
                        </tr>
                        <?php $rank++; endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted text-center py-4">
                        No scores yet. Be the first wolf to play.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>