<?php
session_start();
require_once 'db.php';
if (isset($_SESSION['user_id'])) {
    refresh_user_session($conn);
}

$top_scores = mysqli_query($conn, "
    SELECT u.id, u.username, u.vip_status, u.profile_photo,
           s.score, s.agent, s.kills, s.rounds_survived, s.played_at
    FROM scores s
    JOIN users u ON s.user_id = u.id
    INNER JOIN (
        SELECT user_id, MAX(score) AS best_score
        FROM scores
        GROUP BY user_id
    ) best ON s.user_id = best.user_id AND s.score = best.best_score
    GROUP BY s.user_id
    ORDER BY s.score DESC
    LIMIT 10
");

$user_best = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    $user_best = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT score, agent, kills, rounds_survived, played_at
        FROM scores WHERE user_id=$uid
        ORDER BY score DESC LIMIT 1
    "));
}

function get_letter_color($letter) {
    $colors = [
        'A' => '#E24B4A', 'B' => '#7F77DD', 'C' => '#1D9E75', 'D' => '#EF9F27',
        'E' => '#D4537E', 'F' => '#378ADD', 'G' => '#639922', 'H' => '#F0997B',
        'I' => '#534AB7', 'J' => '#5DCAA5', 'K' => '#BA7517', 'L' => '#AFA9EC',
        'M' => '#7F77DD', 'N' => '#E24B4A', 'O' => '#1D9E75', 'P' => '#EF9F27',
        'Q' => '#D4537E', 'R' => '#378ADD', 'S' => '#1D9E75', 'T' => '#F0997B',
        'U' => '#534AB7', 'V' => '#5DCAA5', 'W' => '#7F77DD', 'X' => '#E24B4A',
        'Y' => '#EF9F27', 'Z' => '#D4537E',
    ];
    return $colors[strtoupper($letter)] ?? '#7F77DD';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard — Wolf Strike</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--wolf-dark);
            font-family: 'Rajdhani', sans-serif;
            min-height: 100vh;
            color: var(--wolf-text);
            overflow-x: hidden;
        }

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

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 600px 400px at 15% 50%, rgba(127,119,221,0.08) 0%, transparent 70%),
                radial-gradient(ellipse 500px 300px at 85% 20%, rgba(29,158,117,0.06) 0%, transparent 70%),
                radial-gradient(ellipse 400px 400px at 50% 90%, rgba(83,74,183,0.05) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
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
            box-shadow: 0 0 8px rgba(127,119,221,0.6);
        }

        .rog-nav-link:hover         { color: #ffffff; }
        .rog-nav-link:hover::after  { transform: scaleX(1); }
        .rog-nav-link.active        { color: #ffffff; }
        .rog-nav-link.active::after { transform: scaleX(1); }
        .rog-nav-link.danger        { color: rgba(240,149,149,0.6); }
        .rog-nav-link.danger:hover  { color: #f09595; }
        .rog-nav-link.danger::after { background: var(--wolf-red); }

        /* ── page ── */
        .page-content {
            position: relative;
            z-index: 1;
            padding: 48px 24px 80px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-eyebrow {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 4px;
            color: var(--wolf-teal);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .page-heading {
            font-family: 'Orbitron', monospace;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 4px;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .page-sub {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem;
            color: var(--wolf-muted);
            letter-spacing: 0.5px;
            margin-bottom: 40px;
        }

        /* ── section title ── */
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .section-accent {
            width: 3px; height: 18px;
            background: linear-gradient(180deg, var(--wolf-purple), var(--wolf-teal));
            border-radius: 2px;
            box-shadow: 0 0 6px rgba(127,119,221,0.5);
        }

        .section-label {
            font-family: 'Orbitron', monospace;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 3px;
            color: rgba(255,255,255,0.6);
        }

        /* ── glass card ── */
        .glass-card {
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(127,119,221,0.12);
            border-radius: 16px;
            padding: 28px;
            backdrop-filter: blur(16px);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0; left: 20%; right: 20%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(127,119,221,0.4), transparent);
        }

        /* ── personal best card ── */
        .personal-best {
            background: rgba(127,119,221,0.04);
            border: 1px solid rgba(127,119,221,0.2);
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            animation: cardIn 0.5s 0.1s cubic-bezier(0.16,1,0.3,1) both;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .pb-left { display: flex; flex-direction: column; gap: 4px; }

        .pb-eyebrow {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 3px;
            color: rgba(127,119,221,0.7);
            text-transform: uppercase;
        }

        .pb-score {
            font-family: 'Orbitron', monospace;
            font-size: 2rem;
            font-weight: 900;
            color: var(--wolf-purple);
            text-shadow: 0 0 16px rgba(127,119,221,0.5);
            line-height: 1;
        }

        .pb-right {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .pb-stat {
            text-align: center;
        }

        .pb-stat-val {
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
        }

        .pb-stat-label {
            font-size: 0.68rem;
            color: var(--wolf-muted);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* ── leaderboard rows ── */
        .lb-wrap {
            border: 1px solid rgba(127,119,221,0.1);
            border-radius: 14px;
            overflow: hidden;
            animation: cardIn 0.5s 0.2s cubic-bezier(0.16,1,0.3,1) both;
        }

        .lb-header-row {
            display: grid;
            grid-template-columns: 64px 1fr 120px 80px 80px 100px;
            padding: 12px 20px;
            background: rgba(127,119,221,0.06);
            border-bottom: 1px solid rgba(127,119,221,0.1);
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
        }

        .lb-row {
            display: grid;
            grid-template-columns: 64px 1fr 120px 80px 80px 100px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            align-items: center;
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
            color: inherit;
        }

        .lb-row:last-child { border-bottom: none; }

        .lb-row:hover {
            background: rgba(127,119,221,0.06);
        }

        .lb-row.current-user {
            background: rgba(127,119,221,0.05);
            border-left: 2px solid var(--wolf-purple);
        }

        .lb-row.rank-1 { background: rgba(239,159,39,0.04); }
        .lb-row.rank-1:hover { background: rgba(239,159,39,0.08); }
        .lb-row.rank-2 { background: rgba(180,178,169,0.03); }
        .lb-row.rank-3 { background: rgba(216,90,48,0.03); }

        /* ── rank number ── */
        .rank-col {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rank-badge {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 1rem;
            line-height: 1;
        }

        .rank-1-badge {
            font-size: 1.1rem;
            color: var(--wolf-gold);
            text-shadow: 0 0 12px rgba(239,159,39,0.7);
        }

        .rank-2-badge {
            color: #C2C0B6;
            text-shadow: 0 0 8px rgba(194,192,182,0.5);
        }

        .rank-3-badge {
            color: #D85A30;
            text-shadow: 0 0 8px rgba(216,90,48,0.5);
        }

        .rank-other-badge {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.2);
        }

        /* ── player col ── */
        .player-col {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .player-avatar-sm {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            flex-shrink: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', monospace;
            font-size: 0.85rem;
            font-weight: 900;
            border: 1.5px solid;
        }

        .player-avatar-sm img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .player-name-wrap { min-width: 0; }

        .player-name {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .player-sub {
            font-size: 0.72rem;
            color: var(--wolf-muted);
            margin-top: 1px;
        }

        /* ── agent badge ── */
        .agent-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 3px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .agent-scout   { background: rgba(29,158,117,0.15);  color: #5DCAA5;  border: 1px solid rgba(29,158,117,0.3); }
        .agent-hunter  { background: rgba(127,119,221,0.15); color: #AFA9EC;  border: 1px solid rgba(127,119,221,0.3); }
        .agent-alpha   { background: rgba(216,90,48,0.15);   color: #F0997B;  border: 1px solid rgba(216,90,48,0.3); }
        .agent-phantom { background: rgba(239,159,39,0.15);  color: #FAC775;  border: 1px solid rgba(239,159,39,0.3); }

        .score-col {
            font-family: 'Orbitron', monospace;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--wolf-purple);
            text-shadow: 0 0 8px rgba(127,119,221,0.4);
        }

        .kills-col {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--wolf-text);
        }

        .rounds-col {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rounds-bar-track {
            width: 40px; height: 3px;
            background: rgba(255,255,255,0.08);
            border-radius: 2px;
            overflow: hidden;
        }

        .rounds-bar-fill {
            height: 100%;
            border-radius: 2px;
            background: var(--wolf-teal);
        }

        .rounds-text {
            font-size: 0.82rem;
            color: var(--wolf-muted);
        }

        .vip-badge {
            display: inline-block;
            background: linear-gradient(135deg, #EF9F27, #BA7517);
            color: #1A1A1A;
            font-size: 0.55rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: 5px;
            vertical-align: middle;
        }

        .you-tag {
            font-size: 0.65rem;
            color: rgba(127,119,221,0.6);
            letter-spacing: 1px;
            font-family: 'Rajdhani', sans-serif;
        }

        .empty-state {
            text-align: center;
            padding: 64px 20px;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.2;
        }

        .empty-text {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* ── player modal ── */
        .modal-backdrop-custom {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(6px);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-backdrop-custom.open {
            display: flex;
        }

        .player-modal {
            background: rgba(8,8,20,0.98);
            border: 1px solid rgba(127,119,221,0.2);
            border-radius: 20px;
            padding: 36px;
            width: 100%;
            max-width: 480px;
            position: relative;
            animation: modalIn 0.3s cubic-bezier(0.16,1,0.3,1) both;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(16px); }
            to   { opacity: 1; transform: scale(1)    translateY(0); }
        }

        .player-modal::before {
            content: '';
            position: absolute;
            top: 0; left: 20%; right: 20%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(127,119,221,0.5), transparent);
        }

        .modal-close {
            position: absolute;
            top: 16px; right: 16px;
            width: 32px; height: 32px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: rgba(226,75,74,0.15);
            border-color: rgba(226,75,74,0.3);
            color: #f09595;
        }

        .modal-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', monospace;
            font-size: 1.8rem;
            font-weight: 900;
            border: 2px solid;
            margin: 0 auto 16px;
        }

        .modal-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-username {
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 6px;
        }

        .modal-badges {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .modal-badge {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 3px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .mb-rank   { background: rgba(239,159,39,0.15);  color: var(--wolf-gold); border: 1px solid rgba(239,159,39,0.3); }
        .mb-vip    { background: linear-gradient(135deg, #EF9F27, #BA7517); color: #1A1A1A; font-weight:700; }
        .mb-since  { background: rgba(127,119,221,0.1);  color: rgba(127,119,221,0.8); border: 1px solid rgba(127,119,221,0.2); }

        .modal-divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 0 0 20px;
        }

        .modal-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .modal-stat {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            padding: 14px 10px;
            text-align: center;
        }

        .modal-stat-val {
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1;
            margin-bottom: 5px;
        }

        .modal-stat-label {
            font-size: 0.65rem;
            color: var(--wolf-muted);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: 'Rajdhani', sans-serif;
        }

        .modal-best-agent {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-best-label {
            font-size: 0.72rem;
            color: var(--wolf-muted);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: 'Rajdhani', sans-serif;
        }

        /* loading spinner */
        .modal-loading {
            text-align: center;
            padding: 40px 20px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.85rem;
            color: var(--wolf-muted);
            letter-spacing: 2px;
        }
    </style>
</head>
<body>

<nav class="rog-nav">
    <a href="index.php" class="rog-brand">
        <div class="rog-slash"></div>
        <div>
            <div class="rog-title">WOLF STRIKE</div>
            <div class="rog-subtitle">TACTICAL ARENA</div>
        </div>
    </a>
    <div class="rog-nav-links">
        <a href="index.php"        class="rog-nav-link">Home</a>
        <a href="select_agent.php" class="rog-nav-link">Play</a>
        <a href="leaderboard.php"  class="rog-nav-link active">Leaderboard</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php"  class="rog-nav-link">Profile</a>
            <a href="logout.php"   class="rog-nav-link danger">Logout</a>
        <?php else: ?>
            <a href="login.php"    class="rog-nav-link">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="page-content">

    <div class="page-eyebrow">// global rankings</div>
    <div class="page-heading">LEADERBOARD</div>
    <div class="page-sub">Top 10 wolf agents of all time — click any player to view their stats</div>

    <?php if ($user_best): ?>
    <div class="personal-best">
        <div class="pb-left">
            <div class="pb-eyebrow">Your Personal Best</div>
            <div class="pb-score"><?php echo number_format($user_best['score']); ?></div>
        </div>
        <div class="pb-right">
            <div class="pb-stat">
                <div class="pb-stat-val"><?php echo $user_best['kills']; ?></div>
                <div class="pb-stat-label">Kills</div>
            </div>
            <div class="pb-stat">
                <div class="pb-stat-val"><?php echo $user_best['rounds_survived']; ?>/5</div>
                <div class="pb-stat-label">Rounds</div>
            </div>
            <div class="pb-stat">
                <span class="agent-badge agent-<?php echo strtolower($user_best['agent']); ?>">
                    <?php echo strtoupper($user_best['agent']); ?>
                </span>
                <div class="pb-stat-label" style="margin-top:6px;">Agent</div>
            </div>
            <div class="pb-stat">
                <div class="pb-stat-val" style="font-size:0.85rem; color:var(--wolf-muted);">
                    <?php echo date('d M Y', strtotime($user_best['played_at'])); ?>
                </div>
                <div class="pb-stat-label">Date</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-title">
        <div class="section-accent"></div>
        <div class="section-label">Top Wolves</div>
    </div>

    <div class="lb-wrap">
        <div class="lb-header-row">
            <div style="text-align:center;">#</div>
            <div>Player</div>
            <div>Agent</div>
            <div>Score</div>
            <div>Kills</div>
            <div>Rounds</div>
        </div>

        <?php if (mysqli_num_rows($top_scores) > 0):
            $rank = 1;
            while ($row = mysqli_fetch_assoc($top_scores)):
                $is_current  = isset($_SESSION['username']) && $row['username'] === $_SESSION['username'];
                $has_photo   = !empty($row['profile_photo']) &&
                               file_exists(__DIR__ . '/assets/uploads/profiles/' . $row['profile_photo']);
                $photo_url   = $has_photo ? 'assets/uploads/profiles/' . $row['profile_photo'] : null;
                $letter      = strtoupper(substr($row['username'], 0, 1));
                $lcolor      = get_letter_color($letter);
                $row_class   = 'lb-row';
                if ($rank === 1) $row_class .= ' rank-1';
                elseif ($rank === 2) $row_class .= ' rank-2';
                elseif ($rank === 3) $row_class .= ' rank-3';
                if ($is_current) $row_class .= ' current-user';
        ?>
        <div class="<?php echo $row_class; ?>"
             onclick="openPlayerModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username'], ENT_QUOTES); ?>', '<?php echo $photo_url ? $photo_url : ''; ?>', '<?php echo $lcolor; ?>', <?php echo $rank; ?>)">

            <div class="rank-col">
                <?php if ($rank === 1): ?>
                    <span class="rank-badge rank-1-badge">01</span>
                <?php elseif ($rank === 2): ?>
                    <span class="rank-badge rank-2-badge">02</span>
                <?php elseif ($rank === 3): ?>
                    <span class="rank-badge rank-3-badge">03</span>
                <?php else: ?>
                    <span class="rank-badge rank-other-badge">
                        <?php echo str_pad($rank, 2, '0', STR_PAD_LEFT); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="player-col">
                <div class="player-avatar-sm"
                     style="border-color:<?php echo $lcolor; ?>;
                            background:<?php echo str_replace(')', ', 0.12)', str_replace('rgb', 'rgba', $lcolor)); ?>;">
                    <?php if ($has_photo): ?>
                        <img src="<?php echo $photo_url; ?>" alt="">
                    <?php else: ?>
                        <span style="color:<?php echo $lcolor; ?>;"><?php echo $letter; ?></span>
                    <?php endif; ?>
                </div>
                <div class="player-name-wrap">
                    <div class="player-name">
                        <?php echo htmlspecialchars($row['username']); ?>
                        <?php if ($row['vip_status']): ?>
                            <span class="vip-badge">VIP</span>
                        <?php endif; ?>
                        <?php if ($is_current): ?>
                            <span class="you-tag">YOU</span>
                        <?php endif; ?>
                    </div>
                    <div class="player-sub">
                        <?php echo date('d M Y', strtotime($row['played_at'])); ?>
                    </div>
                </div>
            </div>

            <div>
                <span class="agent-badge agent-<?php echo strtolower($row['agent']); ?>">
                    <?php echo strtoupper($row['agent']); ?>
                </span>
            </div>

            <div class="score-col">
                <?php echo number_format($row['score']); ?>
            </div>

            <div class="kills-col">
                <?php echo $row['kills']; ?>
            </div>

            <div class="rounds-col">
                <div class="rounds-bar-track">
                    <div class="rounds-bar-fill"
                         style="width:<?php echo ($row['rounds_survived']/5)*100; ?>%;">
                    </div>
                </div>
                <span class="rounds-text"><?php echo $row['rounds_survived']; ?>/5</span>
            </div>

        </div>
        <?php $rank++; endwhile;
        else: ?>
        <div class="empty-state">
            <div class="empty-icon">🏆</div>
            <div class="empty-text">No scores yet — be the first wolf to play</div>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- player profile modal -->
<div class="modal-backdrop-custom" id="playerModal" onclick="closeModalOnBackdrop(event)">
    <div class="player-modal" id="playerModalBox">
        <button class="modal-close" onclick="closePlayerModal()">✕</button>
        <div id="modalContent">
            <div class="modal-loading">LOADING AGENT FILE...</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openPlayerModal(userId, username, photoUrl, letterColor, rank) {
    document.getElementById('playerModal').classList.add('open');
    document.getElementById('modalContent').innerHTML =
        '<div class="modal-loading">LOADING AGENT FILE...</div>';

    fetch('get_player_stats.php?id=' + userId)
        .then(r => r.json())
        .then(data => {
            const letter = username.charAt(0).toUpperCase();
            const avatarHtml = photoUrl
                ? `<img src="${photoUrl}" alt="${username}">`
                : `<span style="color:${letterColor}; text-shadow: 0 0 12px ${letterColor};">${letter}</span>`;

            const rankLabel = rank === 1 ? '🥇 Rank #1' : rank === 2 ? '🥈 Rank #2' : rank === 3 ? '🥉 Rank #3' : `Rank #${rank}`;

            const vipBadge = data.vip_status
                ? `<span class="modal-badge mb-vip">VIP</span>` : '';

            const agentBadge = data.best_agent
                ? `<span class="agent-badge agent-${data.best_agent.toLowerCase()}">${data.best_agent.toUpperCase()}</span>`
                : '<span style="color:var(--wolf-muted); font-size:0.85rem;">No games</span>';

            document.getElementById('modalContent').innerHTML = `
                <div class="modal-avatar"
                     style="border-color:${letterColor};
                            background:${letterColor}22;">
                    ${avatarHtml}
                </div>

                <div class="modal-username">
                    ${username}
                </div>

                <div class="modal-badges">
                    <span class="modal-badge mb-rank">${rankLabel}</span>
                    ${vipBadge}
                    <span class="modal-badge mb-since">Since ${data.since}</span>
                </div>

                <div class="modal-divider"></div>

                <div class="modal-stats">
                    <div class="modal-stat">
                        <div class="modal-stat-val" style="color:var(--wolf-purple);">
                            ${Number(data.best_score || 0).toLocaleString()}
                        </div>
                        <div class="modal-stat-label">Best Score</div>
                    </div>
                    <div class="modal-stat">
                        <div class="modal-stat-val" style="color:var(--wolf-red);">
                            ${Number(data.total_kills || 0).toLocaleString()}
                        </div>
                        <div class="modal-stat-label">Total Kills</div>
                    </div>
                    <div class="modal-stat">
                        <div class="modal-stat-val" style="color:var(--wolf-teal);">
                            ${data.total_games || 0}
                        </div>
                        <div class="modal-stat-label">Games</div>
                    </div>
                    <div class="modal-stat">
                        <div class="modal-stat-val" style="color:var(--wolf-gold);">
                            ${Number(data.avg_score || 0).toLocaleString()}
                        </div>
                        <div class="modal-stat-label">Avg Score</div>
                    </div>
                    <div class="modal-stat">
                        <div class="modal-stat-val" style="color:#D4537E;">
                            ${data.best_rounds || 0}/5
                        </div>
                        <div class="modal-stat-label">Best Rounds</div>
                    </div>
                    <div class="modal-stat">
                        <div class="modal-stat-val" style="color:#378ADD;">
                            ${data.total_rounds || 0}
                        </div>
                        <div class="modal-stat-label">Total Rounds</div>
                    </div>
                </div>

                <div class="modal-best-agent">
                    <span class="modal-best-label">Favourite Agent</span>
                    ${agentBadge}
                </div>
            `;
        })
        .catch(() => {
            document.getElementById('modalContent').innerHTML =
                '<div class="modal-loading">Failed to load player data.</div>';
        });
}

function closePlayerModal() {
    document.getElementById('playerModal').classList.remove('open');
}

function closeModalOnBackdrop(e) {
    if (e.target === document.getElementById('playerModal')) {
        closePlayerModal();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePlayerModal();
});
</script>
</body>
</html>