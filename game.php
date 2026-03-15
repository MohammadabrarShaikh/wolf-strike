<?php
session_start();
require_once 'db.php';
refresh_user_session($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['selected_agent'])) {
    header("Location: select_agent.php");
    exit();
}

$agent      = $_SESSION['selected_agent'];
$username   = $_SESSION['username'];
$vip_status = $_SESSION['vip_status'];

$agent_stats = [
    'Scout'   => ['hp' => 60,  'speed' => 4.5, 'bullet_speed' => 9,  'damage' => 25, 'gravity' => 0.25, 'jump_force' => -13],
    'Hunter'  => ['hp' => 100, 'speed' => 3.0, 'bullet_speed' => 7,  'damage' => 20, 'gravity' => 0.45, 'jump_force' => -11],
    'Alpha'   => ['hp' => 150, 'speed' => 1.8, 'bullet_speed' => 5,  'damage' => 35, 'gravity' => 0.75, 'jump_force' => -9 ],
    'Phantom' => ['hp' => 80,  'speed' => 4.8, 'bullet_speed' => 10, 'damage' => 30, 'gravity' => 0.12, 'jump_force' => -15],
];

$stats = $agent_stats[$agent];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wolf Strike — Playing as <?php echo $agent; ?></title>
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/game.css" rel="stylesheet">
</head>
<body class="game-body">

<div id="game-wrapper">

    <div id="hud">
        <div id="hud-left">
            <div id="hp-label">HP</div>
            <div id="hp-bar-track">
                <div id="hp-bar-fill"></div>
            </div>
            <div id="hp-text"></div>
        </div>
        <div id="hud-center">
            <div id="round-label">ROUND</div>
            <div id="round-num">1 / 5</div>
        </div>
        <div id="hud-right">
            <div id="score-label">SCORE</div>
            <div id="score-num">0</div>
        </div>
        <div class="hud-item">
    <div class="hud-label">TARGETS</div>
    <div class="hud-val" id="bots-remaining">0</div>
</div>
    </div>

    <div id="timer-bar-wrap">
        <div id="timer-bar"></div>
    </div>

    <canvas id="gameCanvas"></canvas>

    <div id="overlay" class="hidden">
        <div id="overlay-box">
            <div id="overlay-title"></div>
            <div id="overlay-body"></div>
            <button id="overlay-btn"></button>
        </div>
    </div>

</div>

<script>
    const PLAYER_AGENT        = "<?php echo $agent; ?>";
    const PLAYER_MAX_HP       = <?php echo $stats['hp']; ?>;
    const PLAYER_SPEED        = <?php echo $stats['speed']; ?>;
    const PLAYER_BULLET_SPEED = <?php echo $stats['bullet_speed']; ?>;
    const PLAYER_DAMAGE       = <?php echo $stats['damage']; ?>;
    const PLAYER_GRAVITY      = <?php echo $stats['gravity']; ?>;
    const PLAYER_JUMP_FORCE   = <?php echo $stats['jump_force']; ?>;
    const PLAYER_USERNAME     = "<?php echo $username; ?>";
</script>

<script src="assets/js/utils.js" defer></script>
<script src="assets/js/bot.js" defer></script>
<script src="assets/js/game.js" defer></script>

</body>
</html>
