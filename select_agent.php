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

$agents = [
    [
        'name'    => 'Scout',
        'type'    => 'Fast · Aggressive',
        'desc'    => 'Built for speed. Low HP but fastest movement and bullet velocity. High risk, high reward — strike before they see you.',
        'hp'      => 60,
        'speed'   => 9,
        'bullet'  => 8.5,
        'color'   => '#1D9E75',
        'glow'    => 'rgba(29,158,117,0.4)',
        'sel_img' => 'assets/images/player_scout_sel.png',
        'vip'     => false,
    ],
    [
        'name'    => 'Hunter',
        'type'    => 'Balanced · Reliable',
        'desc'    => 'The all-rounder. Equal parts speed, firepower and durability. Best choice for new wolves entering the arena.',
        'hp'      => 100,
        'speed'   => 6,
        'bullet'  => 6,
        'color'   => '#7F77DD',
        'glow'    => 'rgba(127,119,221,0.4)',
        'sel_img' => 'assets/images/player_hunter_sel.png',
        'vip'     => false,
    ],
    [
        'name'    => 'Alpha',
        'type'    => 'Tank · Unstoppable',
        'desc'    => 'Maximum HP, maximum presence. Slow but nearly impossible to kill. For wolves who prefer to stand their ground.',
        'hp'      => 150,
        'speed'   => 3.5,
        'bullet'  => 4,
        'color'   => '#D85A30',
        'glow'    => 'rgba(216,90,48,0.4)',
        'sel_img' => 'assets/images/player_alpha_sel.png',
        'vip'     => false,
    ],
    [
        'name'    => 'Phantom',
        'type'    => 'Elite · Exclusive',
        'desc'    => 'The apex predator. Near-Scout speed with dual energy pistols and maximum bullet power. Reserved for VIP wolves only.',
        'hp'      => 80,
        'speed'   => 9.5,
        'bullet'  => 10,
        'color'   => '#EF9F27',
        'glow'    => 'rgba(239,159,39,0.4)',
        'sel_img' => 'assets/images/player_phantom_sel.png',
        'vip'     => true,
    ],
];

$available = array_filter($agents, function($a) {
    return !$a['vip'] || $_SESSION['vip_status'];
});
$available = array_values($available);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Agent — Wolf Strike</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --wolf-purple:   #7F77DD;
            --wolf-purple-2: #534AB7;
            --wolf-teal:     #1D9E75;
            --wolf-red:      #E24B4A;
            --wolf-gold:     #EF9F27;
            --wolf-dark:     #05050f;
            --wolf-text:     #e8e8f0;
            --wolf-muted:    rgba(232,232,240,0.45);
            --agent-color:   #7F77DD;
            --agent-glow:    rgba(127,119,221,0.4);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--wolf-dark);
            font-family: 'Rajdhani', sans-serif;
            min-height: 100vh;
            color: var(--wolf-text);
            overflow: hidden;
        }

        /* ── animated grid ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(127,119,221,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(127,119,221,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: gridMove 20s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes gridMove {
            0%   { transform: translateY(0); }
            100% { transform: translateY(40px); }
        }

        /* ── dynamic colour glow orbs ── */
        #bgGlow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            transition: background 0.6s ease;
        }

        /* ── navbar ── */
        .rog-nav {
            position: relative;
            z-index: 10;
            background: rgba(5,5,15,0.85);
            border-bottom: 1px solid rgba(127,119,221,0.2);
            backdrop-filter: blur(20px);
            padding: 0 32px;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            height: 64px;
        }

        .rog-nav::before {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--wolf-purple) 0%, var(--wolf-teal) 40%, transparent 100%);
        }

        .rog-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .rog-slash {
            width: 3px; height: 28px;
            background: linear-gradient(180deg, var(--wolf-purple), var(--wolf-teal));
            transform: skewX(-15deg);
            border-radius: 1px;
            box-shadow: 0 0 8px rgba(127,119,221,0.6);
        }

        .rog-title {
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            font-weight: 900;
            letter-spacing: 6px;
            color: #ffffff;
            text-shadow: 0 0 8px rgba(127,119,221,0.8), 0 0 20px rgba(127,119,221,0.4);
        }

        .rog-subtitle {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.6rem;
            font-weight: 600;
            letter-spacing: 3px;
            color: var(--wolf-teal);
            margin-top: -2px;
        }

        .rog-nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .rog-nav-link {
            position: relative;
            padding: 0 16px;
            height: 64px;
            display: flex;
            align-items: center;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--wolf-muted);
            text-decoration: none;
            text-transform: uppercase;
            transition: color 0.2s;
        }

        .rog-nav-link::after {
            content: '';
            position: absolute;
            bottom: 0; left: 16px; right: 16px;
            height: 2px;
            background: var(--wolf-purple);
            transform: scaleX(0);
            transition: transform 0.2s;
        }

        .rog-nav-link:hover         { color: #ffffff; }
        .rog-nav-link:hover::after  { transform: scaleX(1); }
        .rog-nav-link.danger        { color: rgba(240,149,149,0.6); }
        .rog-nav-link.danger:hover  { color: #f09595; }
        .rog-nav-link.danger::after { background: var(--wolf-red); }

        /* ── page layout ── */
        .select-page {
            position: relative;
            z-index: 1;
            height: calc(100vh - 64px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        /* ── eyebrow + heading ── */
        .page-eyebrow {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 6px;
            color: var(--wolf-teal);
            text-align: center;
            margin-bottom: 6px;
            text-transform: uppercase;
            animation: fadeUp 0.5s ease both;
        }

        .page-heading {
            font-family: 'Orbitron', monospace;
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: 900;
            letter-spacing: 8px;
            color: #ffffff;
            text-align: center;
            margin-bottom: 40px;
            text-shadow: 0 0 20px rgba(127,119,221,0.5);
            animation: fadeUp 0.5s 0.1s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── carousel wrapper ── */
        .carousel-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            width: 100%;
            max-width: 1000px;
            position: relative;
            margin-bottom: 40px;
        }

        /* ── arrow buttons ── */
        .arrow-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
            z-index: 5;
            position: relative;
        }

        .arrow-btn:hover {
            background: rgba(127,119,221,0.15);
            border-color: var(--wolf-purple);
            color: #ffffff;
            box-shadow: 0 0 16px rgba(127,119,221,0.3);
            transform: scale(1.05);
        }

        .arrow-btn:active { transform: scale(0.97); }

        /* ── stage ── */
        .stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            overflow: hidden;
            position: relative;
            height: 420px;
        }

        /* ── agent slot ── */
        .agent-slot {
            position: absolute;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
        }

        /* center agent */
        .agent-slot.pos-center {
            transform: translateX(0) scale(1);
            opacity: 1;
            z-index: 3;
        }

        /* left agent */
        .agent-slot.pos-left {
            transform: translateX(-260px) scale(0.65);
            opacity: 0.35;
            z-index: 2;
        }

        /* right agent */
        .agent-slot.pos-right {
            transform: translateX(260px) scale(0.65);
            opacity: 0.35;
            z-index: 2;
        }

        /* far left — hidden */
        .agent-slot.pos-far-left {
            transform: translateX(-420px) scale(0.4);
            opacity: 0;
            z-index: 1;
        }

        /* far right — hidden */
        .agent-slot.pos-far-right {
            transform: translateX(420px) scale(0.4);
            opacity: 0;
            z-index: 1;
        }

        /* ── agent image container ── */
        .agent-img-wrap {
            width: 280px;
            height: 320px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            position: relative;
        }

        .agent-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 0px transparent);
            transition: filter 0.5s;
        }

        .agent-slot.pos-center .agent-img {
            filter: drop-shadow(0 0 32px var(--agent-color-dynamic))
                    drop-shadow(0 8px 24px rgba(0,0,0,0.5));
        }

        /* ground glow under center agent */
        .agent-ground-glow {
            position: absolute;
            bottom: -10px;
            width: 200px;
            height: 20px;
            border-radius: 50%;
            background: var(--agent-color-dynamic, #7F77DD);
            filter: blur(20px);
            opacity: 0;
            transition: opacity 0.5s;
        }

        .agent-slot.pos-center .agent-ground-glow { opacity: 0.4; }

        /* ── agent info (only shown for center) ── */
        .agent-info {
            text-align: center;
            max-width: 280px;
            transition: opacity 0.3s;
        }

        .agent-slot:not(.pos-center) .agent-info {
            opacity: 0;
            pointer-events: none;
        }

        .agent-name-display {
            font-family: 'Orbitron', monospace;
            font-size: 1.4rem;
            font-weight: 900;
            letter-spacing: 4px;
            color: #ffffff;
            margin-bottom: 2px;
        }

        .agent-type-display {
            font-size: 0.78rem;
            letter-spacing: 2px;
            color: var(--wolf-muted);
            margin-bottom: 0;
        }

        /* ── info panel below carousel ── */
        .info-panel {
            width: 100%;
            max-width: 700px;
            text-align: center;
            animation: fadeUp 0.4s ease both;
        }

        .info-desc {
            font-size: 0.95rem;
            color: var(--wolf-muted);
            line-height: 1.7;
            letter-spacing: 0.3px;
            margin-bottom: 24px;
            min-height: 50px;
            transition: opacity 0.3s;
        }

        /* ── stat bars ── */
        .stat-bars {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .stat-bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            min-width: 100px;
        }

        .stat-bar-label {
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--wolf-muted);
            text-transform: uppercase;
        }

        .stat-bar-track {
            width: 100px;
            height: 4px;
            background: rgba(255,255,255,0.08);
            border-radius: 2px;
            overflow: hidden;
        }

        .stat-bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.5s cubic-bezier(0.34,1.56,0.64,1),
                        background 0.5s;
        }

        .stat-bar-val {
            font-family: 'Orbitron', monospace;
            font-size: 0.72rem;
            font-weight: 700;
            color: #ffffff;
        }

        /* ── VIP lock overlay ── */
        .vip-lock {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(5,5,15,0.6);
            border-radius: 8px;
            backdrop-filter: blur(2px);
        }

        .vip-lock-icon {
            font-size: 2rem;
            opacity: 0.5;
        }

        .vip-lock-text {
            font-family: 'Orbitron', monospace;
            font-size: 0.65rem;
            letter-spacing: 2px;
            color: var(--wolf-gold);
            opacity: 0.8;
        }

        /* ── confirm button ── */
        .confirm-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 14px;
            padding: 16px 52px;
            background: transparent;
            border: 2px solid var(--wolf-purple);
            border-radius: 4px;
            font-family: 'Orbitron', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 5px;
            color: #ffffff;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s;
            clip-path: polygon(12px 0%, 100% 0%, calc(100% - 12px) 100%, 0% 100%);
        }

        .confirm-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                rgba(127,119,221,0.15) 0%,
                rgba(127,119,221,0.03) 100%);
            transition: opacity 0.3s;
        }

        .confirm-btn::after {
            content: '';
            position: absolute;
            top: -100%; left: 0; right: 0;
            height: 100%;
            background: linear-gradient(transparent, rgba(255,255,255,0.08), transparent);
            animation: btnScan 2.5s linear infinite;
        }

        @keyframes btnScan {
            0%   { top: -100%; }
            100% { top: 200%; }
        }

        .confirm-btn:hover {
            background: rgba(127,119,221,0.12);
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(127,119,221,0.3),
                        0 0 0 1px rgba(127,119,221,0.4);
        }

        .confirm-btn:active { transform: translateY(0); }

        .confirm-btn.locked {
            border-color: rgba(239,159,39,0.4);
            opacity: 0.6;
            cursor: not-allowed;
        }

        .confirm-btn.locked:hover {
            transform: none;
            box-shadow: none;
        }

        /* ── dot indicators ── */
        .dot-indicators {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            transition: all 0.3s;
        }

        .dot.active {
            width: 20px;
            border-radius: 3px;
            background: var(--wolf-purple);
            box-shadow: 0 0 8px rgba(127,119,221,0.6);
        }

        /* ── keyboard hint ── */
        .key-hint {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 2px;
            text-align: center;
            margin-top: 16px;
        }

        .key-pill {
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 0.65rem;
            margin: 0 2px;
        }

        .vip-badge-inline {
            display: inline-block;
            background: linear-gradient(135deg, #EF9F27, #BA7517);
            color: #1A1A1A;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 6px;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div id="bgGlow"></div>

<nav class="rog-nav">
    <a href="index.php" class="rog-brand">
        <div class="rog-slash"></div>
        <div>
            <div class="rog-title">WOLF STRIKE</div>
            <div class="rog-subtitle">TACTICAL ARENA</div>
        </div>
    </a>
    <div class="rog-nav-links">
        <a href="index.php"    class="rog-nav-link">Home</a>
        <a href="profile.php"  class="rog-nav-link">Profile</a>
        <a href="logout.php"   class="rog-nav-link danger">Logout</a>
    </div>
</nav>

<div class="select-page">

    <div class="page-eyebrow">// choose your wolf</div>
    <div class="page-heading">SELECT AGENT</div>

    <div class="carousel-wrap">

        <button class="arrow-btn" id="btnLeft" onclick="slide(-1)">&#8592;</button>

        <div class="stage" id="stage">
            <?php foreach ($available as $i => $agent): ?>
            <div class="agent-slot"
                 id="slot-<?php echo $i; ?>"
                 data-index="<?php echo $i; ?>"
                 data-name="<?php echo $agent['name']; ?>"
                 data-color="<?php echo $agent['color']; ?>"
                 data-glow="<?php echo $agent['glow']; ?>"
                 data-vip="<?php echo $agent['vip'] ? '1' : '0'; ?>"
                 onclick="if(<?php echo $i; ?> !== currentIndex) goTo(<?php echo $i; ?>)">

                <div class="agent-img-wrap">
                    <div class="agent-ground-glow"
                         style="background:<?php echo $agent['color']; ?>;"></div>
                    <img src="<?php echo $agent['sel_img']; ?>"
                         alt="<?php echo $agent['name']; ?>"
                         class="agent-img">
                    <?php if ($agent['vip'] && !$_SESSION['vip_status']): ?>
                    <div class="vip-lock">
                        <div class="vip-lock-icon">🔒</div>
                        <div class="vip-lock-text">VIP ONLY</div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="agent-info">
                    <div class="agent-name-display">
                        <?php echo strtoupper($agent['name']); ?>
                        <?php if ($agent['vip']): ?>
                            <span class="vip-badge-inline">VIP</span>
                        <?php endif; ?>
                    </div>
                    <div class="agent-type-display">
                        <?php echo $agent['type']; ?>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <button class="arrow-btn" id="btnRight" onclick="slide(1)">&#8594;</button>

    </div>

    <!-- dot indicators -->
    <div class="dot-indicators" id="dotIndicators">
        <?php foreach ($available as $i => $agent): ?>
        <div class="dot <?php echo $i === 1 ? 'active' : ''; ?>"
             id="dot-<?php echo $i; ?>"
             onclick="goTo(<?php echo $i; ?>)">
        </div>
        <?php endforeach; ?>
    </div>

    <!-- info panel -->
    <div class="info-panel">

        <div class="info-desc" id="infoDesc">
            <!-- filled by JS -->
        </div>

        <div class="stat-bars" id="statBars">
            <div class="stat-bar-item">
                <div class="stat-bar-label">HP</div>
                <div class="stat-bar-track">
                    <div class="stat-bar-fill" id="barHp"
                         style="width:0%; background:#E24B4A;"></div>
                </div>
                <div class="stat-bar-val" id="valHp">—</div>
            </div>
            <div class="stat-bar-item">
                <div class="stat-bar-label">Speed</div>
                <div class="stat-bar-track">
                    <div class="stat-bar-fill" id="barSpeed"
                         style="width:0%; background:#1D9E75;"></div>
                </div>
                <div class="stat-bar-val" id="valSpeed">—</div>
            </div>
            <div class="stat-bar-item">
                <div class="stat-bar-label">Bullet</div>
                <div class="stat-bar-track">
                    <div class="stat-bar-fill" id="barBullet"
                         style="width:0%;"></div>
                </div>
                <div class="stat-bar-val" id="valBullet">—</div>
            </div>
        </div>

        <form method="POST" action="select_agent.php" id="selectForm">
            <input type="hidden" name="agent" id="selectedAgentInput" value="">
            <button type="submit" class="confirm-btn" id="confirmBtn">
                ENTER ARENA &#9654;
            </button>
        </form>

        <div class="key-hint">
            <span class="key-pill">←</span> <span class="key-pill">→</span>
            navigate &nbsp;·&nbsp;
            <span class="key-pill">Enter</span> confirm
        </div>

    </div>

</div>

<script>
const agents = <?php echo json_encode(array_values($available)); ?>;

// start on middle agent
let currentIndex = Math.floor(agents.length / 2);

const positions = ['pos-far-left', 'pos-left', 'pos-center', 'pos-right', 'pos-far-right'];

function getPosition(slotIndex, centerIndex, total) {
    const diff = slotIndex - centerIndex;
    if (diff === 0)       return 'pos-center';
    if (diff === -1)      return 'pos-left';
    if (diff === 1)       return 'pos-right';
    if (diff <= -2)       return 'pos-far-left';
    if (diff >= 2)        return 'pos-far-right';
    return 'pos-far-right';
}

function updateCarousel() {
    const agent = agents[currentIndex];
    const color = agent.color;
    const glow  = agent.glow;

    // update bg glow
    document.getElementById('bgGlow').style.background = `
        radial-gradient(ellipse 800px 600px at 50% 60%, ${glow.replace('0.4', '0.08')} 0%, transparent 70%)
    `;

    // update slot positions
    agents.forEach((a, i) => {
        const slot = document.getElementById('slot-' + i);
        positions.forEach(p => slot.classList.remove(p));
        slot.classList.add(getPosition(i, currentIndex, agents.length));

        // update ground glow color dynamically
        if (i === currentIndex) {
            slot.querySelector('.agent-ground-glow').style.background = color;
        }
    });

    // update dots
    agents.forEach((a, i) => {
        const dot = document.getElementById('dot-' + i);
        dot.classList.toggle('active', i === currentIndex);
    });

    // update info
    document.getElementById('infoDesc').textContent = agent.desc;

    // update stat bars
    const hpPct     = (agent.hp     / 150) * 100;
    const speedPct  = (agent.speed  / 10)  * 100;
    const bulletPct = (agent.bullet / 10)  * 100;

    document.getElementById('barHp').style.width     = hpPct + '%';
    document.getElementById('barSpeed').style.width  = speedPct + '%';
    document.getElementById('barBullet').style.width = bulletPct + '%';
    document.getElementById('barBullet').style.background = color;

    document.getElementById('valHp').textContent     = agent.hp;
    document.getElementById('valSpeed').textContent  = agent.speed;
    document.getElementById('valBullet').textContent = agent.bullet;

    // update confirm button
    const isLocked = agent.vip && !<?php echo $_SESSION['vip_status'] ? 'true' : 'false'; ?>;
    const btn = document.getElementById('confirmBtn');
    btn.classList.toggle('locked', isLocked);
    btn.textContent = isLocked ? '🔒  VIP REQUIRED' : 'ENTER ARENA ▶';
    btn.style.borderColor = isLocked ? 'rgba(239,159,39,0.4)' : color;
    btn.style.boxShadowColor = color;

    // update hidden input
    document.getElementById('selectedAgentInput').value = isLocked ? '' : agent.name;

    // dynamic CSS variable for drop shadow
    document.documentElement.style.setProperty('--agent-color-dynamic', color);
}

function slide(dir) {
    currentIndex = (currentIndex + dir + agents.length) % agents.length;
    updateCarousel();
}

function goTo(index) {
    currentIndex = index;
    updateCarousel();
}

// keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft')  slide(-1);
    if (e.key === 'ArrowRight') slide(1);
    if (e.key === 'Enter') {
        const btn = document.getElementById('confirmBtn');
        if (!btn.classList.contains('locked')) {
            document.getElementById('selectForm').submit();
        }
    }
});

// prevent locked form submit
document.getElementById('selectForm').addEventListener('submit', function(e) {
    const agent = agents[currentIndex];
    const isLocked = agent.vip && !<?php echo $_SESSION['vip_status'] ? 'true' : 'false'; ?>;
    if (isLocked || !agent.name) e.preventDefault();
});

// init
updateCarousel();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>