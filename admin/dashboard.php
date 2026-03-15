<?php
define('BASE_URL', '../');
session_start();
require_once '../db.php';
require_once 'auth_check.php';

$total_users   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0];
$total_games   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM scores"))[0];
$total_kills   = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(kills) FROM scores"))[0] ?? 0;
$highest_score = mysqli_fetch_row(mysqli_query($conn, "SELECT MAX(score) FROM scores"))[0] ?? 0;
$total_banned  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE status='banned'"))[0];
$total_vip     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE vip_status=1"))[0];
$avg_score     = mysqli_fetch_row(mysqli_query($conn, "SELECT ROUND(AVG(score)) FROM scores"))[0] ?? 0;
$total_rounds  = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(rounds_survived) FROM scores"))[0] ?? 0;

$recent_scores = mysqli_query($conn, "
    SELECT u.id, u.username, u.vip_status, u.profile_photo,
           s.score, s.agent, s.kills, s.rounds_survived, s.played_at
    FROM scores s JOIN users u ON s.user_id = u.id
    ORDER BY s.played_at DESC LIMIT 8
");

$recent_users = mysqli_query($conn, "
    SELECT id, username, email, status, vip_status, profile_photo, created_at
    FROM users ORDER BY created_at DESC LIMIT 6
");

$agent_stats = mysqli_query($conn, "
    SELECT agent, COUNT(*) as cnt
    FROM scores GROUP BY agent ORDER BY cnt DESC
");

function get_letter_color($letter) {
    $colors = [
        'A'=>'#E24B4A','B'=>'#7F77DD','C'=>'#1D9E75','D'=>'#EF9F27',
        'E'=>'#D4537E','F'=>'#378ADD','G'=>'#639922','H'=>'#F0997B',
        'I'=>'#534AB7','J'=>'#5DCAA5','K'=>'#BA7517','L'=>'#AFA9EC',
        'M'=>'#7F77DD','N'=>'#E24B4A','O'=>'#1D9E75','P'=>'#EF9F27',
        'Q'=>'#D4537E','R'=>'#378ADD','S'=>'#1D9E75','T'=>'#F0997B',
        'U'=>'#534AB7','V'=>'#5DCAA5','W'=>'#7F77DD','X'=>'#E24B4A',
        'Y'=>'#EF9F27','Z'=>'#D4537E',
    ];
    return $colors[strtoupper($letter)] ?? '#7F77DD';
}

function mini_avatar($username, $profile_photo, $size = 36) {
    $letter = strtoupper(substr($username, 0, 1));
    $color  = get_letter_color($letter);
    $has_photo = !empty($profile_photo) &&
                 file_exists(__DIR__ . '/../assets/uploads/profiles/' . $profile_photo);
    if ($has_photo) {
        $src = '../assets/uploads/profiles/' . $profile_photo;
        return "<div class='mini-av' style='width:{$size}px;height:{$size}px;border-color:{$color};'>
                    <img src='{$src}' alt='{$username}'>
                </div>";
    }
    return "<div class='mini-av' style='width:{$size}px;height:{$size}px;
                border-color:{$color};background:rgba(0,0,0,0.3);
                color:{$color};font-size:" . ($size * 0.38) . "px;'>
                {$letter}
            </div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Wolf Strike</title>
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
            --admin-accent:  #E24B4A;
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
                linear-gradient(rgba(226,75,74,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(226,75,74,0.025) 1px, transparent 1px);
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
                radial-gradient(ellipse 700px 400px at 0% 50%, rgba(226,75,74,0.05) 0%, transparent 70%),
                radial-gradient(ellipse 500px 400px at 100% 20%, rgba(127,119,221,0.04) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── admin navbar ── */
        .admin-nav {
            position: relative;
            z-index: 10;
            background: rgba(5,5,15,0.9);
            border-bottom: 1px solid rgba(226,75,74,0.2);
            backdrop-filter: blur(20px);
            padding: 0 32px;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            height: 64px;
        }

        .admin-nav::before {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--wolf-red) 0%, rgba(226,75,74,0.3) 50%, transparent 100%);
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .admin-slash {
            width: 3px; height: 28px;
            background: linear-gradient(180deg, var(--wolf-red), rgba(226,75,74,0.3));
            transform: skewX(-15deg);
            border-radius: 1px;
            box-shadow: 0 0 8px rgba(226,75,74,0.6);
        }

        .admin-title-wrap { display: flex; flex-direction: column; }

        .admin-title {
            font-family: 'Orbitron', monospace;
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: 5px;
            color: #ffffff;
            text-shadow: 0 0 8px rgba(226,75,74,0.6);
            line-height: 1.1;
        }

        .admin-subtitle {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.58rem;
            font-weight: 600;
            letter-spacing: 3px;
            color: rgba(226,75,74,0.7);
        }

        .admin-nav-links {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .admin-nav-link {
            position: relative;
            padding: 0 16px;
            height: 64px;
            display: flex;
            align-items: center;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--wolf-muted);
            text-decoration: none;
            text-transform: uppercase;
            transition: color 0.2s;
        }

        .admin-nav-link::after {
            content: '';
            position: absolute;
            bottom: 0; left: 16px; right: 16px;
            height: 2px;
            background: var(--wolf-red);
            transform: scaleX(0);
            transition: transform 0.2s;
            box-shadow: 0 0 6px rgba(226,75,74,0.5);
        }

        .admin-nav-link:hover         { color: #ffffff; }
        .admin-nav-link:hover::after  { transform: scaleX(1); }
        .admin-nav-link.active        { color: #ffffff; }
        .admin-nav-link.active::after { transform: scaleX(1); }
        .admin-nav-link.logout        { color: rgba(240,149,149,0.5); }
        .admin-nav-link.logout:hover  { color: #f09595; }

        .admin-user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            background: rgba(226,75,74,0.08);
            border: 1px solid rgba(226,75,74,0.2);
            border-radius: 6px;
            margin: auto 12px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(226,75,74,0.9);
            letter-spacing: 1px;
        }

        .admin-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--wolf-red);
            box-shadow: 0 0 6px var(--wolf-red);
            animation: dotPulse 2s ease-in-out infinite;
        }

        @keyframes dotPulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        /* ── page ── */
        .page-content {
            position: relative;
            z-index: 1;
            padding: 40px 28px 80px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-eyebrow {
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 4px;
            color: rgba(226,75,74,0.7);
            margin-bottom: 4px;
            text-transform: uppercase;
            font-family: 'Rajdhani', sans-serif;
        }

        .page-heading {
            font-family: 'Orbitron', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 4px;
            color: #ffffff;
            margin-bottom: 36px;
        }

        /* ── section title ── */
        .sec-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .sec-accent {
            width: 3px; height: 16px;
            background: linear-gradient(180deg, var(--wolf-red), rgba(226,75,74,0.3));
            border-radius: 2px;
            box-shadow: 0 0 6px rgba(226,75,74,0.4);
        }

        .sec-label {
            font-family: 'Orbitron', monospace;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 3px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
        }

        /* ── glass card ── */
        .glass-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0; left: 15%; right: 15%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(226,75,74,0.3), transparent);
        }

        /* ── STAT CARDS with filling animation ── */
        .stat-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 14px;
            margin-bottom: 14px;
        }

        .stat-cards-grid-2 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 14px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 20px 18px;
            position: relative;
            overflow: hidden;
            animation: statCardIn 0.6s cubic-bezier(0.16,1,0.3,1) both;
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.10s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.20s; }

        @keyframes statCardIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* filling bar at bottom of card */
        .stat-card-fill {
            position: absolute;
            bottom: 0; left: 0;
            height: 3px;
            width: 0%;
            border-radius: 0 0 14px 14px;
            animation: fillBar 1.4s cubic-bezier(0.34,1.56,0.64,1) forwards;
        }

        @keyframes fillBar {
            from { width: 0%; }
            to   { width: 100%; }
        }

        .stat-card:nth-child(1) .stat-card-fill { animation-delay: 0.4s; }
        .stat-card:nth-child(2) .stat-card-fill { animation-delay: 0.55s; }
        .stat-card:nth-child(3) .stat-card-fill { animation-delay: 0.7s; }
        .stat-card:nth-child(4) .stat-card-fill { animation-delay: 0.85s; }

        /* corner accent */
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 40px; height: 40px;
            border-top: 1px solid;
            border-right: 1px solid;
            border-radius: 0 14px 0 0;
            opacity: 0.3;
        }

        .stat-card-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin-bottom: 14px;
            flex-shrink: 0;
        }

        .stat-card-label {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--wolf-muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .stat-card-val {
            font-family: 'Orbitron', monospace;
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
            /* count-up animation */
            animation: countUp 0.1s ease both;
        }

        .stat-card-sub {
            font-size: 0.72rem;
            color: var(--wolf-muted);
            letter-spacing: 0.5px;
        }

        /* ── circular progress ── */
        .circle-stats {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 0;
        }

        .circle-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .circle-wrap {
            position: relative;
            width: 72px;
            height: 72px;
        }

        .circle-svg {
            width: 72px;
            height: 72px;
            transform: rotate(-90deg);
        }

        .circle-bg {
            fill: none;
            stroke: rgba(255,255,255,0.06);
            stroke-width: 4;
        }

        .circle-progress {
            fill: none;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-dasharray: 188;
            stroke-dashoffset: 188;
            transition: stroke-dashoffset 1.2s cubic-bezier(0.34,1.56,0.64,1);
        }

        .circle-val {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            color: #ffffff;
        }

        .circle-label {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--wolf-muted);
            text-transform: uppercase;
            text-align: center;
        }

        /* ── mini avatar ── */
        .mini-av {
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', monospace;
            font-weight: 700;
            border: 1.5px solid;
            flex-shrink: 0;
        }

        .mini-av img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ── admin table ── */
        .admin-table-wrap {
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            overflow: hidden;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table thead tr {
            background: rgba(226,75,74,0.04);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .admin-table th {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
            padding: 12px 16px;
            text-align: left;
        }

        .admin-table td {
            padding: 12px 16px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--wolf-text);
            border-bottom: 1px solid rgba(255,255,255,0.03);
            vertical-align: middle;
        }

        .admin-table tbody tr:last-child td { border-bottom: none; }
        .admin-table tbody tr { transition: background 0.15s; }
        .admin-table tbody tr:hover td { background: rgba(226,75,74,0.03); }

        /* ── player row ── */
        .player-row-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .player-row-name {
            font-weight: 600;
            color: #ffffff;
            font-size: 0.9rem;
        }

        .player-row-sub {
            font-size: 0.72rem;
            color: var(--wolf-muted);
            margin-top: 1px;
        }

        /* ── badges ── */
        .status-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 99px;
            letter-spacing: 0.5px;
            font-family: 'Rajdhani', sans-serif;
        }

        .status-active {
            background: rgba(29,158,117,0.15);
            color: #5DCAA5;
            border: 1px solid rgba(29,158,117,0.3);
        }

        .status-banned {
            background: rgba(226,75,74,0.15);
            color: #f09595;
            border: 1px solid rgba(226,75,74,0.3);
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
            margin-left: 4px;
            vertical-align: middle;
        }

        .agent-badge {
            display: inline-block;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .agent-scout   { background: rgba(29,158,117,0.15);  color: #5DCAA5;  border: 1px solid rgba(29,158,117,0.3); }
        .agent-hunter  { background: rgba(127,119,221,0.15); color: #AFA9EC;  border: 1px solid rgba(127,119,221,0.3); }
        .agent-alpha   { background: rgba(216,90,48,0.15);   color: #F0997B;  border: 1px solid rgba(216,90,48,0.3); }
        .agent-phantom { background: rgba(239,159,39,0.15);  color: #FAC775;  border: 1px solid rgba(239,159,39,0.3); }

        .score-val {
            font-family: 'Orbitron', monospace;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--wolf-purple);
        }

        /* ── view all link ── */
        .view-all {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: rgba(226,75,74,0.7);
            text-decoration: none;
            transition: color 0.2s;
            margin-top: 14px;
            display: inline-block;
        }

        .view-all:hover { color: #f09595; }

        /* ── agent usage bars ── */
        .agent-usage-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .agent-usage-item:last-child { margin-bottom: 0; }

        .agent-usage-name {
            width: 60px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .agent-usage-track {
            flex: 1;
            height: 6px;
            background: rgba(255,255,255,0.06);
            border-radius: 3px;
            overflow: hidden;
        }

        .agent-usage-fill {
            height: 100%;
            border-radius: 3px;
            width: 0%;
            animation: fillBar 1.2s cubic-bezier(0.34,1.56,0.64,1) forwards;
        }

        .agent-usage-cnt {
            font-family: 'Orbitron', monospace;
            font-size: 0.7rem;
            color: var(--wolf-muted);
            min-width: 28px;
            text-align: right;
        }

        /* ── quick action buttons ── */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .qa-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            text-decoration: none;
            color: var(--wolf-text);
            transition: all 0.2s;
            font-family: 'Rajdhani', sans-serif;
        }

        .qa-btn:hover {
            background: rgba(226,75,74,0.06);
            border-color: rgba(226,75,74,0.2);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .qa-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .qa-label {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .qa-sub {
            font-size: 0.7rem;
            color: var(--wolf-muted);
            margin-top: 1px;
        }

        /* ── empty ── */
        .table-empty {
            text-align: center;
            padding: 32px;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 1px;
            font-family: 'Rajdhani', sans-serif;
        }
    </style>
</head>
<body>

<nav class="admin-nav">
    <a href="dashboard.php" class="admin-brand">
        <div class="admin-slash"></div>
        <div class="admin-title-wrap">
            <div class="admin-title">WOLF STRIKE</div>
            <div class="admin-subtitle">CONTROL PANEL</div>
        </div>
    </a>
    <div class="admin-nav-links">
        <div class="admin-user-chip">
            <div class="admin-dot"></div>
            <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
        </div>
        <a href="dashboard.php" class="admin-nav-link active">Dashboard</a>
        <a href="users.php"     class="admin-nav-link">Players</a>
        <a href="scores.php"    class="admin-nav-link">Scores</a>
        <a href="logout.php"    class="admin-nav-link logout">Logout</a>
    </div>
</nav>

<div class="page-content">

    <div class="page-eyebrow">// command centre</div>
    <div class="page-heading">DASHBOARD</div>

    <!-- primary stat cards -->
    <div class="stat-cards-grid">

        <div class="stat-card" style="border-color:rgba(127,119,221,0.15);">
            <div class="stat-card-fill" style="background:var(--wolf-purple); animation-delay:0.4s;"></div>
            <div class="stat-card::after" style="border-color:var(--wolf-purple);"></div>
            <div class="stat-card-icon"
                 style="background:rgba(127,119,221,0.15); color:var(--wolf-purple);">
                👥
            </div>
            <div class="stat-card-label">Total Players</div>
            <div class="stat-card-val" style="color:var(--wolf-purple);"
                 data-target="<?php echo $total_users; ?>">0</div>
            <div class="stat-card-sub">Registered accounts</div>
        </div>

        <div class="stat-card" style="border-color:rgba(29,158,117,0.15);">
            <div class="stat-card-fill" style="background:var(--wolf-teal); animation-delay:0.55s;"></div>
            <div class="stat-card-icon"
                 style="background:rgba(29,158,117,0.15); color:var(--wolf-teal);">
                🎮
            </div>
            <div class="stat-card-label">Games Played</div>
            <div class="stat-card-val" style="color:var(--wolf-teal);"
                 data-target="<?php echo $total_games; ?>">0</div>
            <div class="stat-card-sub">Total matches completed</div>
        </div>

        <div class="stat-card" style="border-color:rgba(226,75,74,0.15);">
            <div class="stat-card-fill" style="background:var(--wolf-red); animation-delay:0.7s;"></div>
            <div class="stat-card-icon"
                 style="background:rgba(226,75,74,0.15); color:var(--wolf-red);">
                💀
            </div>
            <div class="stat-card-label">Total Kills</div>
            <div class="stat-card-val" style="color:var(--wolf-red);"
                 data-target="<?php echo $total_kills; ?>">0</div>
            <div class="stat-card-sub">Bots eliminated globally</div>
        </div>

        <div class="stat-card" style="border-color:rgba(239,159,39,0.15);">
            <div class="stat-card-fill" style="background:var(--wolf-gold); animation-delay:0.85s;"></div>
            <div class="stat-card-icon"
                 style="background:rgba(239,159,39,0.15); color:var(--wolf-gold);">
                🏆
            </div>
            <div class="stat-card-label">Highest Score</div>
            <div class="stat-card-val" style="color:var(--wolf-gold);"
                 data-target="<?php echo $highest_score; ?>">0</div>
            <div class="stat-card-sub">All-time record</div>
        </div>

    </div>

    <!-- secondary stat cards -->
    <div class="stat-cards-grid-2">

        <div class="stat-card" style="border-color:rgba(226,75,74,0.12);">
            <div class="stat-card-fill" style="background:var(--wolf-red); animation-delay:1s;"></div>
            <div class="stat-card-label">Banned Players</div>
            <div class="stat-card-val" style="color:#f09595; font-size:1.4rem;"
                 data-target="<?php echo $total_banned; ?>">0</div>
            <div class="stat-card-sub">Active bans</div>
        </div>

        <div class="stat-card" style="border-color:rgba(239,159,39,0.12);">
            <div class="stat-card-fill" style="background:var(--wolf-gold); animation-delay:1.1s;"></div>
            <div class="stat-card-label">VIP Players</div>
            <div class="stat-card-val" style="color:var(--wolf-gold); font-size:1.4rem;"
                 data-target="<?php echo $total_vip; ?>">0</div>
            <div class="stat-card-sub">Elite access granted</div>
        </div>

        <div class="stat-card" style="border-color:rgba(127,119,221,0.12);">
            <div class="stat-card-fill" style="background:var(--wolf-purple); animation-delay:1.2s;"></div>
            <div class="stat-card-label">Avg Score</div>
            <div class="stat-card-val" style="color:var(--wolf-purple); font-size:1.4rem;"
                 data-target="<?php echo $avg_score; ?>">0</div>
            <div class="stat-card-sub">Per game average</div>
        </div>

        <div class="stat-card" style="border-color:rgba(29,158,117,0.12);">
            <div class="stat-card-fill" style="background:var(--wolf-teal); animation-delay:1.3s;"></div>
            <div class="stat-card-label">Total Rounds</div>
            <div class="stat-card-val" style="color:var(--wolf-teal); font-size:1.4rem;"
                 data-target="<?php echo $total_rounds; ?>">0</div>
            <div class="stat-card-sub">Rounds survived globally</div>
        </div>

    </div>

    <div class="row g-4">

        <!-- recent games -->
        <div class="col-lg-7">
            <div class="glass-card">
                <div class="sec-title">
                    <div class="sec-accent"></div>
                    <div class="sec-label">Recent Games</div>
                </div>

                <?php if (mysqli_num_rows($recent_scores) > 0): ?>
                <div class="admin-table-wrap">
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
                                    <div class="player-row-cell">
                                        <?php echo mini_avatar($row['username'], $row['profile_photo'], 32); ?>
                                        <div>
                                            <div class="player-row-name">
                                                <?php echo htmlspecialchars($row['username']); ?>
                                                <?php if ($row['vip_status']): ?>
                                                    <span class="vip-badge">VIP</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="agent-badge agent-<?php echo strtolower($row['agent']); ?>">
                                        <?php echo $row['agent']; ?>
                                    </span>
                                </td>
                                <td><span class="score-val"><?php echo number_format($row['score']); ?></span></td>
                                <td style="color:rgba(255,255,255,0.7);"><?php echo $row['kills']; ?></td>
                                <td style="color:rgba(255,255,255,0.7);"><?php echo $row['rounds_survived']; ?>/5</td>
                                <td style="color:var(--wolf-muted); font-size:0.8rem;">
                                    <?php echo date('d M y', strtotime($row['played_at'])); ?>
                                    <span style="display:block; font-size:0.72rem;">
                                        <?php echo date('H:i', strtotime($row['played_at'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="table-empty">No games played yet.</div>
                <?php endif; ?>

                <a href="scores.php" class="view-all">View all scores →</a>
            </div>
        </div>

        <!-- right column -->
        <div class="col-lg-5">

            <!-- agent usage -->
            <div class="glass-card mb-4">
                <div class="sec-title">
                    <div class="sec-accent"></div>
                    <div class="sec-label">Agent Usage</div>
                </div>

                <?php
                $agent_data = [];
                $max_cnt    = 1;
                while ($row = mysqli_fetch_assoc($agent_stats)) {
                    $agent_data[] = $row;
                    if ($row['cnt'] > $max_cnt) $max_cnt = $row['cnt'];
                }

                $agent_colors = [
                    'Scout'   => '#1D9E75',
                    'Hunter'  => '#7F77DD',
                    'Alpha'   => '#D85A30',
                    'Phantom' => '#EF9F27',
                ];

                if (count($agent_data) > 0):
                    foreach ($agent_data as $i => $ag):
                        $pct   = round(($ag['cnt'] / $max_cnt) * 100);
                        $color = $agent_colors[$ag['agent']] ?? '#7F77DD';
                        $delay = 0.8 + ($i * 0.15);
                ?>
                <div class="agent-usage-item">
                    <span class="agent-usage-name"
                          style="color:<?php echo $color; ?>;">
                        <?php echo $ag['agent']; ?>
                    </span>
                    <div class="agent-usage-track">
                        <div class="agent-usage-fill"
                             style="background:<?php echo $color; ?>;
                                    animation-delay:<?php echo $delay; ?>s;"
                             data-width="<?php echo $pct; ?>">
                        </div>
                    </div>
                    <div class="agent-usage-cnt"><?php echo $ag['cnt']; ?></div>
                </div>
                <?php endforeach;
                else: ?>
                    <div class="table-empty" style="padding:20px;">No data yet.</div>
                <?php endif; ?>
            </div>

            <!-- quick actions -->
            <div class="glass-card mb-4">
                <div class="sec-title">
                    <div class="sec-accent"></div>
                    <div class="sec-label">Quick Actions</div>
                </div>
                <div class="quick-actions">
                    <a href="users.php" class="qa-btn">
                        <div class="qa-icon"
                             style="background:rgba(127,119,221,0.15); color:var(--wolf-purple);">
                            👥
                        </div>
                        <div>
                            <div class="qa-label">Players</div>
                            <div class="qa-sub">Manage accounts</div>
                        </div>
                    </a>
                    <a href="scores.php" class="qa-btn">
                        <div class="qa-icon"
                             style="background:rgba(29,158,117,0.15); color:var(--wolf-teal);">
                            📊
                        </div>
                        <div>
                            <div class="qa-label">Scores</div>
                            <div class="qa-sub">View records</div>
                        </div>
                    </a>
                    <a href="users.php?filter=banned" class="qa-btn">
                        <div class="qa-icon"
                             style="background:rgba(226,75,74,0.15); color:var(--wolf-red);">
                            🚫
                        </div>
                        <div>
                            <div class="qa-label">Banned</div>
                            <div class="qa-sub"><?php echo $total_banned; ?> active bans</div>
                        </div>
                    </a>
                    <a href="users.php?filter=vip" class="qa-btn">
                        <div class="qa-icon"
                             style="background:rgba(239,159,39,0.15); color:var(--wolf-gold);">
                            ⭐
                        </div>
                        <div>
                            <div class="qa-label">VIP</div>
                            <div class="qa-sub"><?php echo $total_vip; ?> members</div>
                        </div>
                    </a>
                </div>
            </div>

        </div>

    </div>

    <!-- recent registrations -->
    <div class="glass-card mt-4">
        <div class="sec-title">
            <div class="sec-accent"></div>
            <div class="sec-label">Recent Registrations</div>
        </div>

        <?php if (mysqli_num_rows($recent_users) > 0): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>VIP</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($recent_users)): ?>
                    <tr>
                        <td>
                            <div class="player-row-cell">
                                <?php echo mini_avatar($row['username'], $row['profile_photo'], 34); ?>
                                <div class="player-row-name">
                                    <?php echo htmlspecialchars($row['username']); ?>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--wolf-muted); font-size:0.82rem;">
                            <?php echo htmlspecialchars($row['email']); ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['vip_status']): ?>
                                <span class="vip-badge" style="font-size:0.65rem; padding:3px 8px;">VIP</span>
                            <?php else: ?>
                                <span style="color:rgba(255,255,255,0.2); font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--wolf-muted); font-size:0.8rem;">
                            <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="table-empty">No players registered yet.</div>
        <?php endif; ?>

        <a href="users.php" class="view-all">Manage all players →</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── count-up animation for stat values ──
function countUp(el, target, duration) {
    const start     = performance.now();
    const startVal  = 0;
    const isLarge   = target > 999;

    function update(time) {
        const elapsed  = time - start;
        const progress = Math.min(elapsed / duration, 1);
        const ease     = 1 - Math.pow(1 - progress, 3);
        const current  = Math.floor(startVal + (target - startVal) * ease);
        el.textContent = current.toLocaleString();
        if (progress < 1) requestAnimationFrame(update);
        else el.textContent = target.toLocaleString();
    }

    requestAnimationFrame(update);
}

// ── trigger count-up on load ──
window.addEventListener('load', function () {
    document.querySelectorAll('.stat-card-val[data-target]').forEach((el, i) => {
        const target  = parseInt(el.getAttribute('data-target')) || 0;
        const delay   = 300 + (i * 120);
        setTimeout(() => countUp(el, target, 1200), delay);
    });

    // ── circular progress fill ──
    document.querySelectorAll('.circle-progress').forEach(el => {
        const pct    = parseFloat(el.getAttribute('data-pct')) || 0;
        const offset = 188 - (188 * pct / 100);
        setTimeout(() => {
            el.style.strokeDashoffset = offset;
        }, 600);
    });

    // ── agent usage bar fill (correct width from data attr) ──
    document.querySelectorAll('.agent-usage-fill').forEach(el => {
        const w = el.getAttribute('data-width') || '0';
        el.style.setProperty('--target-width', w + '%');
    });
});
</script>
</body>
</html>