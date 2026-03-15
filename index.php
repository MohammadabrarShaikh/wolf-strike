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
    SELECT u.username, u.vip_status, u.profile_photo, s.score, s.agent, s.played_at
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

$user_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS games, MAX(score) AS best, SUM(kills) AS kills
    FROM scores WHERE user_id = {$_SESSION['user_id']}
"));

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wolf Strike — Home</title>
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
            --wolf-card:     rgba(255,255,255,0.03);
            --wolf-border:   rgba(127,119,221,0.15);
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

        /* ── wolf face background ── */
        #wolf-bg {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: min(700px, 90vw);
            height: auto;
            z-index: 0;
            pointer-events: none;
            animation: wolfPulse 4s ease-in-out infinite;
        }

        @keyframes wolfPulse {
            0%, 100% {
                opacity: 0.04;
                filter: drop-shadow(0 0 8px rgba(127,119,221,0.4));
            }
            50% {
                opacity: 0.09;
                filter: drop-shadow(0 0 24px rgba(127,119,221,0.8))
                        drop-shadow(0 0 48px rgba(127,119,221,0.3));
            }
        }

        /* ── ROG navbar ── */
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
            animation: navIn 0.5s ease both;
        }

        @keyframes navIn {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .rog-nav::before {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg,
                var(--wolf-purple) 0%,
                var(--wolf-teal) 40%,
                transparent 100%);
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
            text-shadow: 0 0 8px rgba(127,119,221,0.8),
                         0 0 20px rgba(127,119,221,0.4);
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
            white-space: nowrap;
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
        .rog-nav-link.danger        { color: rgba(240,149,149,0.6); }
        .rog-nav-link.danger:hover  { color: #f09595; }
        .rog-nav-link.danger::after { background: var(--wolf-red); }

        .rog-user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(127,119,221,0.08);
            border: 1px solid rgba(127,119,221,0.2);
            border-radius: 6px;
            margin: auto 16px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--wolf-purple);
            letter-spacing: 1px;
        }

        .rog-user-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--wolf-teal);
            box-shadow: 0 0 6px var(--wolf-teal);
            animation: dotPulse 2s ease-in-out infinite;
        }

        @keyframes dotPulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }

        /* ── page ── */
        .page-content {
            position: relative;
            z-index: 1;
            padding: 60px 24px 80px;
        }

        /* ── hero ── */
        .hero-section {
            text-align: center;
            margin-bottom: 64px;
            animation: heroIn 0.7s 0.2s cubic-bezier(0.16,1,0.3,1) both;
        }

        @keyframes heroIn {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .hero-eyebrow {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 6px;
            color: var(--wolf-teal);
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .hero-title {
            font-family: 'Orbitron', monospace;
            font-size: clamp(2.5rem, 6vw, 5rem);
            font-weight: 900;
            letter-spacing: 12px;
            color: #ffffff;
            line-height: 1;
            text-shadow:
                0 0 20px rgba(127,119,221,0.9),
                0 0 60px rgba(127,119,221,0.4),
                0 0 120px rgba(127,119,221,0.15);
            animation: titlePulse 4s ease-in-out infinite;
            margin-bottom: 16px;
        }

        @keyframes titlePulse {
            0%, 100% {
                text-shadow:
                    0 0 20px rgba(127,119,221,0.9),
                    0 0 60px rgba(127,119,221,0.4),
                    0 0 120px rgba(127,119,221,0.15);
            }
            50% {
                text-shadow:
                    0 0 30px rgba(127,119,221,1),
                    0 0 80px rgba(127,119,221,0.6),
                    0 0 160px rgba(127,119,221,0.25);
            }
        }

        .hero-desc {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.1rem;
            font-weight: 400;
            color: var(--wolf-muted);
            letter-spacing: 1px;
            max-width: 480px;
            margin: 0 auto 40px;
            line-height: 1.7;
        }

        /* ── play button ── */
        .play-btn-wrap {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .play-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 14px;
            padding: 18px 48px;
            background: transparent;
            border: 2px solid var(--wolf-purple);
            border-radius: 4px;
            font-family: 'Orbitron', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 6px;
            color: #ffffff;
            text-decoration: none;
            overflow: hidden;
            transition: all 0.3s;
            clip-path: polygon(12px 0%, 100% 0%, calc(100% - 12px) 100%, 0% 100%);
        }

        .play-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                rgba(127,119,221,0.2) 0%,
                rgba(127,119,221,0.05) 100%);
            transition: opacity 0.3s;
        }

        .play-btn::after {
            content: '';
            position: absolute;
            top: -100%; left: 0; right: 0;
            height: 100%;
            background: linear-gradient(transparent,
                rgba(127,119,221,0.15), transparent);
            animation: btnScan 2.5s linear infinite;
        }

        @keyframes btnScan {
            0%   { top: -100%; }
            100% { top: 200%; }
        }

        .play-btn:hover {
            background: rgba(127,119,221,0.15);
            border-color: #9088e8;
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow:
                0 0 30px rgba(127,119,221,0.4),
                0 8px 32px rgba(127,119,221,0.2);
        }

        .play-btn-arrow {
            font-size: 1.2rem;
            transition: transform 0.3s;
        }

        .play-btn:hover .play-btn-arrow { transform: translateX(6px); }

        .play-btn-hint {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.25);
            letter-spacing: 2px;
        }

        /* ── stat chips ── */
        .stat-chips {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 64px;
            animation: heroIn 0.7s 0.35s cubic-bezier(0.16,1,0.3,1) both;
        }

        .stat-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 6px;
            backdrop-filter: blur(10px);
        }

        .stat-chip-val {
            font-family: 'Orbitron', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
        }

        .stat-chip-label {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--wolf-muted);
            text-transform: uppercase;
        }

        .stat-chip-divider {
            width: 1px;
            height: 24px;
            background: rgba(255,255,255,0.1);
        }

        /* ── leaderboard ── */
        .lb-section {
            max-width: 860px;
            margin: 0 auto;
            animation: heroIn 0.7s 0.45s cubic-bezier(0.16,1,0.3,1) both;
        }

        .lb-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .lb-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lb-title-accent {
            width: 4px; height: 22px;
            background: linear-gradient(180deg, var(--wolf-purple), var(--wolf-teal));
            border-radius: 2px;
            box-shadow: 0 0 8px rgba(127,119,221,0.6);
        }

        .lb-title-text {
            font-family: 'Orbitron', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 4px;
            color: #ffffff;
        }

        .lb-view-all {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--wolf-purple);
            text-decoration: none;
            transition: all 0.2s;
        }

        .lb-view-all:hover {
            color: #9088e8;
            text-shadow: 0 0 8px rgba(127,119,221,0.5);
        }

        /* ── leaderboard table ── */
        .lb-table-wrap {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(127,119,221,0.12);
            border-radius: 12px;
            overflow: hidden;
            backdrop-filter: blur(16px);
        }

        .lb-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lb-table thead tr {
            background: rgba(127,119,221,0.06);
            border-bottom: 1px solid rgba(127,119,221,0.12);
        }

        .lb-table th {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
            padding: 14px 20px;
            text-align: left;
        }

        .lb-table td {
            padding: 14px 20px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--wolf-text);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
        }

        .lb-table tbody tr:last-child td { border-bottom: none; }

        .lb-table tbody tr {
            transition: background 0.15s;
            cursor: pointer;
        }

        .lb-table tbody tr:hover td {
            background: rgba(127,119,221,0.04);
        }

        /* rank badges */
        .rank-1 { font-family:'Orbitron',monospace; font-weight:900; color:var(--wolf-gold); text-shadow:0 0 10px rgba(239,159,39,0.7); }
        .rank-2 { font-family:'Orbitron',monospace; font-weight:900; color:#B4B2A9; text-shadow:0 0 8px rgba(180,178,169,0.5); }
        .rank-3 { font-family:'Orbitron',monospace; font-weight:900; color:#D85A30; text-shadow:0 0 8px rgba(216,90,48,0.5); }
        .rank-other { font-family:'Orbitron',monospace; font-size:0.75rem; color:rgba(255,255,255,0.25); }

        /* agent badges */
        .agent-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 3px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .agent-scout   { background:rgba(29,158,117,0.15);  color:#5DCAA5;  border:1px solid rgba(29,158,117,0.3); }
        .agent-hunter  { background:rgba(127,119,221,0.15); color:#AFA9EC;  border:1px solid rgba(127,119,221,0.3); }
        .agent-alpha   { background:rgba(216,90,48,0.15);   color:#F0997B;  border:1px solid rgba(216,90,48,0.3); }
        .agent-phantom { background:rgba(239,159,39,0.15);  color:#FAC775;  border:1px solid rgba(239,159,39,0.3); }

        .score-val {
            font-family: 'Orbitron', monospace;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--wolf-purple);
            text-shadow: 0 0 8px rgba(127,119,221,0.4);
        }

        .vip-badge {
            display: inline-block;
            background: linear-gradient(135deg, #EF9F27, #BA7517);
            color: #1A1A1A;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 5px;
            vertical-align: middle;
        }

        /* mini avatar */
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

        .mini-av img { width:100%; height:100%; object-fit:cover; }

        .player-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lb-empty {
            text-align: center;
            padding: 48px 20px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 1px;
        }

        #bgCanvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>

<!-- geometric low-poly howling wolf background -->
<svg id="wolf-bg" viewBox="0 0 300 420" fill="none" xmlns="http://www.w3.org/2000/svg">
    <polygon points="148,8 158,8 153,2" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.05)"/>
    <polygon points="135,22 148,8 153,2 158,8 168,22 153,28" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="120,38 135,22 153,28 148,42" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.06)"/>
    <polygon points="168,22 180,38 158,42 153,28" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.03)"/>
    <polygon points="110,55 120,38 148,42 140,58" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.05)"/>
    <polygon points="180,38 192,55 162,58 158,42" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="120,38 148,42 140,58 125,62" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="148,42 158,42 162,58 150,65 140,58" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.06)"/>
    <polygon points="96,72 110,55 125,62 118,78" stroke="#7F77DD" stroke-width="1.2" fill="rgba(127,119,221,0.08)"/>
    <polygon points="192,55 206,72 184,78 178,62" stroke="#7F77DD" stroke-width="1.2" fill="rgba(127,119,221,0.08)"/>
    <polygon points="100,70 112,64 120,72 108,78" stroke="#7F77DD" stroke-width="1.5" fill="rgba(127,119,221,0.25)"/>
    <polygon points="182,64 194,70 194,78 180,72" stroke="#7F77DD" stroke-width="1.5" fill="rgba(127,119,221,0.25)"/>
    <polygon points="110,55 96,72 80,65 95,45" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="206,72 192,55 207,45 222,65" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="95,45 80,65 68,85 88,90" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.05)"/>
    <polygon points="207,45 222,65 234,85 214,90" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.05)"/>
    <polygon points="95,45 110,55 125,62 118,78 96,72 80,65" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.03)"/>
    <polygon points="125,62 150,65 162,58 178,62 184,78 150,82 118,78" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="178,62 192,55 206,72 222,65 214,90 184,78" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.03)"/>
    <polygon points="68,85 80,65 95,45 75,30 55,55" stroke="#7F77DD" stroke-width="1.2" fill="rgba(127,119,221,0.05)"/>
    <polygon points="234,85 222,65 207,45 227,30 247,55" stroke="#7F77DD" stroke-width="1.2" fill="rgba(127,119,221,0.05)"/>
    <polygon points="75,30 95,45 80,65 55,55 50,35" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="227,30 207,45 222,65 247,55 252,35" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="50,35 75,30 68,85 40,80 25,50" stroke="#7F77DD" stroke-width="1.2" fill="rgba(127,119,221,0.04)"/>
    <polygon points="25,50 40,80 20,90 8,60" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.05)"/>
    <polygon points="50,35 75,30 60,12 38,18" stroke="#7F77DD" stroke-width="1.5" fill="rgba(127,119,221,0.06)"/>
    <polygon points="252,35 227,30 234,85 262,80 277,50" stroke="#7F77DD" stroke-width="1.2" fill="rgba(127,119,221,0.04)"/>
    <polygon points="277,50 262,80 282,90 294,60" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.05)"/>
    <polygon points="252,35 227,30 242,12 264,18" stroke="#7F77DD" stroke-width="1.5" fill="rgba(127,119,221,0.06)"/>
    <polygon points="68,85 88,90 96,72 80,65" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.05)"/>
    <polygon points="214,90 206,72 222,65 234,85" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.05)"/>
    <polygon points="88,90 96,72 118,78 110,100 90,105" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="214,90 206,72 184,78 192,100 212,105" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="90,105 110,100 118,78 150,82 184,78 192,100 212,105 200,125 150,132 102,125" stroke="#7F77DD" stroke-width="1.2" fill="rgba(127,119,221,0.04)"/>
    <polygon points="102,125 150,132 200,125 188,148 150,155 114,148" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.05)"/>
    <polygon points="68,85 90,105 102,125 80,135 58,110" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="234,85 212,105 200,125 222,135 244,110" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="58,110 80,135 65,155 42,138" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="42,138 65,155 50,178 28,160" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.05)"/>
    <polygon points="28,160 50,178 38,202 15,182" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="244,110 222,135 237,155 260,138" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="260,138 237,155 252,178 274,160" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.05)"/>
    <polygon points="274,160 252,178 264,202 287,182" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="80,135 102,125 114,148 95,165 72,155" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.05)"/>
    <polygon points="222,135 200,125 188,148 207,165 230,155" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.05)"/>
    <polygon points="95,165 114,148 150,155 188,148 207,165 190,188 150,195 112,188" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="65,155 95,165 72,185 48,172" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="48,172 72,185 55,210 30,195" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.05)"/>
    <polygon points="30,195 55,210 42,238 18,220" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="237,155 207,165 230,185 254,172" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="254,172 230,185 247,210 272,195" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.05)"/>
    <polygon points="272,195 247,210 260,238 284,220" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="127,218 150,225 175,218 162,250 150,258 140,250" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="112,188 90,212 100,245 127,218" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="190,188 212,212 202,245 175,218" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="78,240 100,245 88,278 62,265" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="62,265 88,278 78,312 48,295" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.03)"/>
    <polygon points="224,240 202,245 214,278 240,265" stroke="#7F77DD" stroke-width="1" fill="rgba(83,74,183,0.04)"/>
    <polygon points="240,265 214,278 224,312 254,295" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.03)"/>
    <polygon points="120,272 150,258 182,272 168,305 150,315 134,305" stroke="#7F77DD" stroke-width="1" fill="rgba(127,119,221,0.04)"/>
    <polygon points="134,305 150,315 168,305 158,342 150,350 144,342" stroke="#7F77DD" stroke-width="0.8" fill="rgba(83,74,183,0.03)"/>
</svg>

<canvas id="bgCanvas"></canvas>

<!-- ROG navbar -->
<nav class="rog-nav">
    <a href="index.php" class="rog-brand">
        <div class="rog-slash"></div>
        <div>
            <div class="rog-title">WOLF STRIKE</div>
            <div class="rog-subtitle">TACTICAL ARENA</div>
        </div>
    </a>
    <div class="rog-nav-links">
        <div class="rog-user-chip">
            <div class="rog-user-dot"></div>
            <?php echo htmlspecialchars($username); ?>
            <?php if ($vip_status): ?>
                <span class="vip-badge">VIP</span>
            <?php endif; ?>
        </div>
        <a href="profile.php"      class="rog-nav-link">Profile</a>
        <a href="leaderboard.php"  class="rog-nav-link">Leaderboard</a>
        <a href="select_agent.php" class="rog-nav-link">Play</a>
        <a href="logout.php"       class="rog-nav-link danger">Logout</a>
    </div>
</nav>

<div class="page-content">

    <!-- hero -->
    <div class="hero-section">
        <div class="hero-eyebrow">// tactical browser shooter</div>
        <h1 class="hero-title">WOLF STRIKE</h1>
        <p class="hero-desc">
            5 rounds. Escalating danger. Only the sharpest wolf survives.<br>
            Choose your agent and enter the arena.
        </p>
        <div class="play-btn-wrap">
            <a href="select_agent.php" class="play-btn">
                SELECT AGENT
                <span class="play-btn-arrow">▶</span>
            </a>
            <span class="play-btn-hint">WASD · MOUSE AIM · CLICK TO SHOOT</span>
        </div>
    </div>

    <!-- player stat chips -->
    <div class="stat-chips">
        <div class="stat-chip">
            <div>
                <div class="stat-chip-val">
                    <?php echo number_format($user_stats['games'] ?? 0); ?>
                </div>
                <div class="stat-chip-label">Games Played</div>
            </div>
        </div>
        <div class="stat-chip-divider"></div>
        <div class="stat-chip">
            <div>
                <div class="stat-chip-val" style="color:var(--wolf-purple);">
                    <?php echo number_format($user_stats['best'] ?? 0); ?>
                </div>
                <div class="stat-chip-label">Best Score</div>
            </div>
        </div>
        <div class="stat-chip-divider"></div>
        <div class="stat-chip">
            <div>
                <div class="stat-chip-val" style="color:var(--wolf-red);">
                    <?php echo number_format($user_stats['kills'] ?? 0); ?>
                </div>
                <div class="stat-chip-label">Total Kills</div>
            </div>
        </div>
    </div>

    <!-- leaderboard -->
    <div class="lb-section">
        <div class="lb-header">
            <div class="lb-title">
                <div class="lb-title-accent"></div>
                <div class="lb-title-text">TOP WOLVES</div>
            </div>
            <a href="leaderboard.php" class="lb-view-all">
                View Full Leaderboard →
            </a>
        </div>

        <div class="lb-table-wrap">
            <?php if (mysqli_num_rows($top_scores) > 0): ?>
            <table class="lb-table">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Player</th>
                        <th>Agent</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    while ($row = mysqli_fetch_assoc($top_scores)):
                        $is_current = $row['username'] === $username;
                        $letter     = strtoupper(substr($row['username'], 0, 1));
                        $lcolor     = get_letter_color($letter);
                        $has_photo  = !empty($row['profile_photo']) &&
                                      file_exists(__DIR__ . '/assets/uploads/profiles/' . $row['profile_photo']);
                        $photo_url  = $has_photo ? 'assets/uploads/profiles/' . $row['profile_photo'] : null;
                    ?>
                    <tr <?php echo $is_current ? 'style="background:rgba(127,119,221,0.06);"' : ''; ?>>
                        <td>
                            <?php if ($rank === 1): ?>
                                <span class="rank-1">01</span>
                            <?php elseif ($rank === 2): ?>
                                <span class="rank-2">02</span>
                            <?php elseif ($rank === 3): ?>
                                <span class="rank-3">03</span>
                            <?php else: ?>
                                <span class="rank-other">
                                    <?php echo str_pad($rank, 2, '0', STR_PAD_LEFT); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="player-cell">
                                <div class="mini-av"
                                     style="width:32px;height:32px;
                                            border-color:<?php echo $lcolor; ?>;
                                            background:<?php echo $lcolor; ?>22;
                                            color:<?php echo $lcolor; ?>;
                                            font-size:12px;">
                                    <?php if ($has_photo): ?>
                                        <img src="<?php echo $photo_url; ?>" alt="">
                                    <?php else: ?>
                                        <?php echo $letter; ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span style="font-weight:600; color:#ffffff;">
                                        <?php echo htmlspecialchars($row['username']); ?>
                                    </span>
                                    <?php if ($row['vip_status']): ?>
                                        <span class="vip-badge">VIP</span>
                                    <?php endif; ?>
                                    <?php if ($is_current): ?>
                                        <span style="font-size:0.7rem;
                                            color:rgba(127,119,221,0.6);
                                            margin-left:6px; letter-spacing:1px;">YOU</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="agent-badge
                                agent-<?php echo strtolower($row['agent']); ?>">
                                <?php echo strtoupper($row['agent']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="score-val">
                                <?php echo number_format($row['score']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php $rank++; endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="lb-empty">
                    NO SCORES YET — BE THE FIRST WOLF TO PLAY
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const bgCanvas = document.getElementById('bgCanvas');
const bgCtx    = bgCanvas.getContext('2d');

bgCanvas.width  = window.innerWidth;
bgCanvas.height = window.innerHeight;

window.addEventListener('resize', () => {
    bgCanvas.width  = window.innerWidth;
    bgCanvas.height = window.innerHeight;
});

const SHAPES = [
    { type:'circle',   symbol:'●', color:'#E24B4A' },
    { type:'circle',   symbol:'■', color:'#7F77DD' },
    { type:'circle',   symbol:'▲', color:'#1D9E75' },
    { type:'circle',   symbol:'✕', color:'#EF9F27' },
    { type:'triangle', symbol:null, color:'#7F77DD' },
    { type:'hexagon',  symbol:null, color:'#1D9E75' },
    { type:'diamond',  symbol:null, color:'#534AB7' },
    { type:'triangle', symbol:null, color:'#EF9F27' },
    { type:'hexagon',  symbol:null, color:'#E24B4A' },
    { type:'diamond',  symbol:null, color:'#7F77DD' },
    { type:'circle',   symbol:'●', color:'#1D9E75' },
    { type:'circle',   symbol:'■', color:'#EF9F27' },
    { type:'triangle', symbol:null, color:'#534AB7' },
    { type:'hexagon',  symbol:null, color:'#7F77DD' },
    { type:'diamond',  symbol:null, color:'#E24B4A' },
];

function randomBetween(a, b) { return Math.random() * (b - a) + a; }

const particles = SHAPES.map(s => ({
    ...s,
    x:          randomBetween(0, window.innerWidth),
    y:          randomBetween(0, window.innerHeight),
    size:       randomBetween(14, 32),
    vx:         randomBetween(-0.3, 0.3),
    vy:         randomBetween(-0.3, 0.3),
    angle:      randomBetween(0, Math.PI * 2),
    spin:       randomBetween(-0.006, 0.006),
    alpha:      randomBetween(0.04, 0.12),
    pulse:      randomBetween(0, Math.PI * 2),
    pulseSpeed: randomBetween(0.012, 0.025),
}));

function drawTriangle(ctx, x, y, size, angle) {
    ctx.save(); ctx.translate(x, y); ctx.rotate(angle);
    ctx.beginPath();
    ctx.moveTo(0, -size);
    ctx.lineTo(size * 0.866, size * 0.5);
    ctx.lineTo(-size * 0.866, size * 0.5);
    ctx.closePath();
    ctx.restore();
}

function drawHexagon(ctx, x, y, size, angle) {
    ctx.save(); ctx.translate(x, y); ctx.rotate(angle);
    ctx.beginPath();
    for (let i = 0; i < 6; i++) {
        const a = (Math.PI / 3) * i;
        i === 0
            ? ctx.moveTo(Math.cos(a) * size, Math.sin(a) * size)
            : ctx.lineTo(Math.cos(a) * size, Math.sin(a) * size);
    }
    ctx.closePath();
    ctx.restore();
}

function drawDiamond(ctx, x, y, size, angle) {
    ctx.save(); ctx.translate(x, y); ctx.rotate(angle);
    ctx.beginPath();
    ctx.moveTo(0, -size);
    ctx.lineTo(size * 0.6, 0);
    ctx.lineTo(0, size);
    ctx.lineTo(-size * 0.6, 0);
    ctx.closePath();
    ctx.restore();
}

function drawCircleBtn(ctx, x, y, size, symbol, color, alpha) {
    ctx.save(); ctx.translate(x, y);
    ctx.strokeStyle = color; ctx.lineWidth = 1.5;
    ctx.globalAlpha = alpha;
    ctx.shadowColor = color; ctx.shadowBlur = 12;
    ctx.beginPath();
    ctx.arc(0, 0, size, 0, Math.PI * 2);
    ctx.stroke();
    ctx.font = `${size * 0.9}px Arial`;
    ctx.fillStyle = color;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(symbol, 0, 1);
    ctx.restore();
}

function bgLoop() {
    bgCtx.clearRect(0, 0, bgCanvas.width, bgCanvas.height);
    particles.forEach(p => {
        p.x += p.vx; p.y += p.vy;
        p.angle += p.spin; p.pulse += p.pulseSpeed;
        const a  = p.alpha + Math.sin(p.pulse) * 0.03;
        const sz = p.size  + Math.sin(p.pulse) * 2;
        if (p.x < -80) p.x = bgCanvas.width  + 80;
        if (p.x > bgCanvas.width  + 80) p.x = -80;
        if (p.y < -80) p.y = bgCanvas.height + 80;
        if (p.y > bgCanvas.height + 80) p.y = -80;
        bgCtx.globalAlpha = a;
        bgCtx.strokeStyle = p.color;
        bgCtx.lineWidth   = 1.2;
        bgCtx.shadowColor = p.color;
        bgCtx.shadowBlur  = 12;
        if (p.type === 'circle' && p.symbol) {
            drawCircleBtn(bgCtx, p.x, p.y, sz, p.symbol, p.color, a);
        } else if (p.type === 'triangle') {
            drawTriangle(bgCtx, p.x, p.y, sz, p.angle);
            bgCtx.stroke();
        } else if (p.type === 'hexagon') {
            drawHexagon(bgCtx, p.x, p.y, sz, p.angle);
            bgCtx.stroke();
        } else if (p.type === 'diamond') {
            drawDiamond(bgCtx, p.x, p.y, sz, p.angle);
            bgCtx.stroke();
        }
        bgCtx.shadowBlur  = 0;
        bgCtx.globalAlpha = 1;
    });
    requestAnimationFrame(bgLoop);
}

bgLoop();
</script>
</body>
</html>