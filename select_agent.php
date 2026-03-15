<?php
session_start();
require_once 'db.php';
refresh_user_session($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent = $_POST['agent'];
    $valid_agents = ['Scout', 'Hunter', 'Alpha'];

    if ($_SESSION['vip_status']) {
        $valid_agents[] = 'Phantom';
    }

    if (in_array($agent, $valid_agents)) {
        $_SESSION['selected_agent'] = $agent;
        header("Location: game.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Agent — Wolf Strike</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/agent.css" rel="stylesheet">
</head>
<body class="auth-body">

<nav class="navbar navbar-dark px-4 py-3"
     style="border-bottom:1px solid rgba(255,255,255,0.08);">
    <span class="game-title" style="font-size:1.2rem; letter-spacing:4px;">
        WOLF STRIKE
    </span>
    <a href="index.php" class="game-link" style="font-size:0.85rem;">
        ← Back
    </a>
</nav>

<div class="container py-5">

    <div class="text-center mb-5">
        <h2 style="color:#ffffff; letter-spacing:4px; font-weight:500;">
            SELECT YOUR AGENT
        </h2>
        <p class="text-muted" style="font-size:0.9rem;">
            Choose wisely — your agent determines your HP, speed, and bullet power
        </p>
    </div>

    <form method="POST" action="select_agent.php">
        <div class="row justify-content-center g-4">

            <div class="col-6 col-lg-3">
                <label class="agent-card-label">
                    <input type="radio" name="agent" value="Scout" required hidden>
                    <div class="agent-card agent-scout-card">
                        <div class="agent-img-wrap">
                            <img src="assets/images/player_scout.png"
                                 alt="Scout" class="agent-img">
                        </div>
                        <div class="agent-name">Scout</div>
                        <div class="agent-type">Fast · Aggressive</div>
                        <div class="agent-stats">
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">HP</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:40%; background:#E24B4A;">
                                    </div>
                                </div>
                                <span class="stat-num">60</span>
                            </div>
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">Speed</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:90%; background:#1D9E75;">
                                    </div>
                                </div>
                                <span class="stat-num">9</span>
                            </div>
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">Bullet</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:85%; background:#7F77DD;">
                                    </div>
                                </div>
                                <span class="stat-num">8.5</span>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            <div class="col-6 col-lg-3">
                <label class="agent-card-label">
                    <input type="radio" name="agent" value="Hunter" required hidden>
                    <div class="agent-card agent-hunter-card">
                        <div class="agent-img-wrap">
                            <img src="assets/images/player_hunter.png"
                                 alt="Hunter" class="agent-img">
                        </div>
                        <div class="agent-name">Hunter</div>
                        <div class="agent-type">Balanced · Reliable</div>
                        <div class="agent-stats">
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">HP</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:65%; background:#E24B4A;">
                                    </div>
                                </div>
                                <span class="stat-num">100</span>
                            </div>
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">Speed</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:60%; background:#1D9E75;">
                                    </div>
                                </div>
                                <span class="stat-num">6</span>
                            </div>
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">Bullet</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:60%; background:#7F77DD;">
                                    </div>
                                </div>
                                <span class="stat-num">6</span>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            <div class="col-6 col-lg-3">
                <label class="agent-card-label">
                    <input type="radio" name="agent" value="Alpha" required hidden>
                    <div class="agent-card agent-alpha-card">
                        <div class="agent-img-wrap">
                            <img src="assets/images/player_alpha.png"
                                 alt="Alpha" class="agent-img">
                        </div>
                        <div class="agent-name">Alpha</div>
                        <div class="agent-type">Tank · Unstoppable</div>
                        <div class="agent-stats">
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">HP</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:100%; background:#E24B4A;">
                                    </div>
                                </div>
                                <span class="stat-num">150</span>
                            </div>
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">Speed</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:35%; background:#1D9E75;">
                                    </div>
                                </div>
                                <span class="stat-num">3.5</span>
                            </div>
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">Bullet</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:40%; background:#7F77DD;">
                                    </div>
                                </div>
                                <span class="stat-num">4</span>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            <?php if ($_SESSION['vip_status']): ?>
            <div class="col-6 col-lg-3">
                <label class="agent-card-label">
                    <input type="radio" name="agent" value="Phantom" required hidden>
                    <div class="agent-card agent-phantom-card">
                        <div class="vip-crown">VIP</div>
                        <div class="agent-img-wrap">
                            <img src="assets/images/player_phantom.png"
                                 alt="Phantom" class="agent-img">
                        </div>
                        <div class="agent-name" style="color:#EF9F27;">Phantom</div>
                        <div class="agent-type" style="color:rgba(239,159,39,0.6);">
                            Elite · Exclusive
                        </div>
                        <div class="agent-stats">
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">HP</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:55%; background:#E24B4A;">
                                    </div>
                                </div>
                                <span class="stat-num">80</span>
                            </div>
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">Speed</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:95%; background:#EF9F27;">
                                    </div>
                                </div>
                                <span class="stat-num">9.5</span>
                            </div>
                            <div class="stat-row-agent">
                                <span class="stat-name-agent">Bullet</span>
                                <div class="stat-bar-track">
                                    <div class="stat-bar-fill"
                                         style="width:100%; background:#EF9F27;">
                                    </div>
                                </div>
                                <span class="stat-num">10</span>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
            <?php endif; ?>

        </div>

        <div class="text-center mt-5">
            <button type="submit" class="btn btn-game px-5 py-3"
                    style="font-size:1rem; letter-spacing:3px;">
                ENTER ARENA
            </button>
        </div>
    </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('.agent-card-label').forEach(label => {
        label.addEventListener('click', function () {
            document.querySelectorAll('.agent-card').forEach(card => {
                card.classList.remove('selected');
            });
            this.querySelector('.agent-card').classList.add('selected');
        });
    });
</script>
</body>
</html>