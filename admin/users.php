<?php
define('BASE_URL', '../');
session_start();
require_once '../db.php';
require_once 'auth_check.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int) $_POST['user_id'];
    $action  = $_POST['action'] ?? '';

    if ($user_id <= 0) {
        $error = "Invalid user.";
    } else {
        switch ($action) {
            case 'ban':
                mysqli_query($conn, "UPDATE users SET status='banned' WHERE id=$user_id");
                $message = "Player banned successfully.";
                break;
            case 'unban':
                mysqli_query($conn, "UPDATE users SET status='active' WHERE id=$user_id");
                $message = "Player unbanned successfully.";
                break;
            case 'grant_vip':
                mysqli_query($conn, "UPDATE users SET vip_status=1 WHERE id=$user_id");
                $message = "VIP status granted.";
                break;
            case 'revoke_vip':
                mysqli_query($conn, "UPDATE users SET vip_status=0 WHERE id=$user_id");
                $message = "VIP status revoked.";
                break;
            case 'delete':
                mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
                $message = "Player deleted. All their scores have been removed.";
                break;
            default:
                $error = "Unknown action.";
        }
    }
}

$search       = '';
$filter       = $_GET['filter'] ?? '';
$search_input = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search       = trim(mysqli_real_escape_string($conn, $_GET['search']));
    $search_input = $search;
    $users        = mysqli_query($conn, "
        SELECT * FROM users
        WHERE username LIKE '%$search%' OR email LIKE '%$search%'
        ORDER BY created_at DESC
    ");
} elseif ($filter === 'banned') {
    $users = mysqli_query($conn, "SELECT * FROM users WHERE status='banned' ORDER BY created_at DESC");
} elseif ($filter === 'vip') {
    $users = mysqli_query($conn, "SELECT * FROM users WHERE vip_status=1 ORDER BY created_at DESC");
} else {
    $users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
}

$total_count = mysqli_num_rows($users);

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
    <title>Manage Players — Wolf Strike Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --wolf-purple:   #7F77DD;
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

        /* ── navbar ── */
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

        /* ── alerts ── */
        .wolf-alert {
            border-radius: 10px;
            padding: 11px 16px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
            border-left: 3px solid;
            animation: alertIn 0.3s ease both;
        }

        @keyframes alertIn {
            from { opacity: 0; transform: translateX(-8px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .wolf-alert-error   { background: rgba(226,75,74,0.1);  border-color: #E24B4A; color: #f09595; }
        .wolf-alert-success { background: rgba(29,158,117,0.1); border-color: #1D9E75; color: #5DCAA5; }

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
            background: linear-gradient(90deg, transparent, rgba(226,75,74,0.25), transparent);
        }

        /* ── search bar ── */
        .search-wrap {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input-wrap {
            position: relative;
            flex: 1;
            min-width: 220px;
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--wolf-muted);
            font-size: 14px;
            pointer-events: none;
        }

        .wolf-input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(226,75,74,0.2);
            border-radius: 10px;
            padding: 11px 14px 11px 40px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            color: #ffffff;
            outline: none;
            transition: all 0.25s;
            letter-spacing: 0.5px;
        }

        .wolf-input::placeholder { color: rgba(255,255,255,0.2); }

        .wolf-input:focus {
            border-color: var(--wolf-red);
            background: rgba(226,75,74,0.05);
            box-shadow: 0 0 0 3px rgba(226,75,74,0.1);
        }

        .search-btn {
            padding: 10px 22px;
            background: linear-gradient(135deg, var(--wolf-red), #A32D2D);
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', monospace;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(226,75,74,0.4);
        }

        .search-btn:active { transform: translateY(0); }

        .filter-btn {
            padding: 10px 18px;
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--wolf-muted);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            white-space: nowrap;
        }

        .filter-btn:hover, .filter-btn.active-filter {
            border-color: var(--wolf-red);
            color: #ffffff;
            background: rgba(226,75,74,0.08);
        }

        .clear-btn {
            padding: 10px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--wolf-muted);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .clear-btn:hover {
            color: #ffffff;
            border-color: rgba(255,255,255,0.2);
        }

        /* ── results count ── */
        .results-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .results-count {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--wolf-muted);
        }

        .results-count span {
            color: #ffffff;
            font-family: 'Orbitron', monospace;
            font-size: 0.85rem;
        }

        /* ── SEARCH SCANNING ANIMATION ── */
        .search-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(5,5,15,0.85);
            backdrop-filter: blur(8px);
            z-index: 100;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 32px;
        }

        .search-overlay.active { display: flex; }

        .scan-box {
            position: relative;
            width: 220px;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* outer rotating ring */
        .scan-ring-outer {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: var(--wolf-red);
            border-right-color: rgba(226,75,74,0.3);
            animation: spinOuter 1.2s linear infinite;
        }

        /* inner rotating ring opposite direction */
        .scan-ring-inner {
            position: absolute;
            inset: 20px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-bottom-color: rgba(226,75,74,0.6);
            border-left-color: rgba(226,75,74,0.2);
            animation: spinInner 0.8s linear infinite;
        }

        /* middle pulsing circle */
        .scan-ring-mid {
            position: absolute;
            inset: 40px;
            border-radius: 50%;
            border: 1px solid rgba(226,75,74,0.2);
            animation: pulseMid 1.5s ease-in-out infinite;
        }

        @keyframes spinOuter {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes spinInner {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }

        @keyframes pulseMid {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50%       { opacity: 0.8; transform: scale(1.05); }
        }

        /* radar sweep line */
        .scan-radar {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            overflow: hidden;
            animation: spinOuter 2s linear infinite;
        }

        .scan-radar::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 50%; height: 2px;
            background: linear-gradient(90deg, rgba(226,75,74,0.8), transparent);
            transform-origin: left center;
        }

        /* center icon */
        .scan-center {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .scan-icon {
            font-size: 2.2rem;
            animation: scanIconPulse 1s ease-in-out infinite;
        }

        @keyframes scanIconPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%       { transform: scale(1.1); opacity: 0.8; }
        }

        .scan-label {
            font-family: 'Orbitron', monospace;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 3px;
            color: var(--wolf-red);
            text-transform: uppercase;
        }

        /* scanning text below box */
        .scan-status {
            font-family: 'Orbitron', monospace;
            font-size: 0.72rem;
            letter-spacing: 3px;
            color: rgba(226,75,74,0.7);
            text-align: center;
        }

        .scan-dots::after {
            content: '';
            animation: dots 1.5s steps(4) infinite;
        }

        @keyframes dots {
            0%  { content: ''; }
            25% { content: '.'; }
            50% { content: '..'; }
            75% { content: '...'; }
        }

        /* horizontal scan line across screen */
        .scan-line {
            position: fixed;
            left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg,
                transparent 0%,
                rgba(226,75,74,0.6) 30%,
                rgba(226,75,74,0.9) 50%,
                rgba(226,75,74,0.6) 70%,
                transparent 100%);
            animation: scanLine 1.8s ease-in-out infinite;
            pointer-events: none;
            z-index: 101;
        }

        @keyframes scanLine {
            0%   { top: -2px; opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { top: 100vh; opacity: 0; }
        }

        /* ── table ── */
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
        .admin-table tbody tr:hover td { background: rgba(226,75,74,0.03); }

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

        .player-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .player-name {
            font-weight: 600;
            color: #ffffff;
            font-size: 0.9rem;
        }

        .player-id {
            font-size: 0.7rem;
            color: var(--wolf-muted);
            font-family: 'Orbitron', monospace;
            margin-top: 1px;
        }

        /* ── status badges ── */
        .status-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 99px;
            letter-spacing: 0.5px;
        }

        .status-active { background: rgba(29,158,117,0.15);  color: #5DCAA5;  border: 1px solid rgba(29,158,117,0.3); }
        .status-banned { background: rgba(226,75,74,0.15);   color: #f09595;  border: 1px solid rgba(226,75,74,0.3); }

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

        /* ── action buttons ── */
        .actions-cell {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-action {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.5px;
        }

        .btn-ban    { background: rgba(226,75,74,0.12);   color: #f09595; border: 1px solid rgba(226,75,74,0.25); }
        .btn-unban  { background: rgba(29,158,117,0.12);  color: #5DCAA5; border: 1px solid rgba(29,158,117,0.25); }
        .btn-vip    { background: rgba(239,159,39,0.12);  color: #FAC775; border: 1px solid rgba(239,159,39,0.25); }
        .btn-delete { background: rgba(226,75,74,0.08);   color: #f09595; border: 1px solid rgba(226,75,74,0.15); }

        .btn-ban:hover    { background: rgba(226,75,74,0.22);  color: #f09595; }
        .btn-unban:hover  { background: rgba(29,158,117,0.22); color: #5DCAA5; }
        .btn-vip:hover    { background: rgba(239,159,39,0.22); color: #FAC775; }
        .btn-delete:hover { background: rgba(226,75,74,0.18);  color: #f09595; }

        /* ── empty state ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 3rem;
            opacity: 0.2;
            margin-bottom: 12px;
        }

        .empty-text {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* row entry animation */
        .admin-table tbody tr {
            animation: rowIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
        }

        <?php for ($i = 1; $i <= 20; $i++): ?>
        .admin-table tbody tr:nth-child(<?php echo $i; ?>) {
            animation-delay: <?php echo ($i * 0.04); ?>s;
        }
        <?php endfor; ?>

        @keyframes rowIn {
            from { opacity: 0; transform: translateX(-12px); }
            to   { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>

<!-- search scanning overlay -->
<div class="search-overlay" id="searchOverlay">
    <div class="scan-line"></div>
    <div class="scan-box">
        <div class="scan-ring-outer"></div>
        <div class="scan-ring-inner"></div>
        <div class="scan-ring-mid"></div>
        <div class="scan-radar"></div>
        <div class="scan-center">
            <div class="scan-icon">🔍</div>
            <div class="scan-label">Scanning</div>
        </div>
    </div>
    <div class="scan-status">
        SEARCHING WOLF DATABASE<span class="scan-dots"></span>
    </div>
</div>

<nav class="admin-nav">
    <a href="dashboard.php" class="admin-brand">
        <div class="admin-slash"></div>
        <div>
            <div class="admin-title">WOLF STRIKE</div>
            <div class="admin-subtitle">CONTROL PANEL</div>
        </div>
    </a>
    <div class="admin-nav-links">
        <div class="admin-user-chip">
            <div class="admin-dot"></div>
            <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
        </div>
        <a href="dashboard.php" class="admin-nav-link">Dashboard</a>
        <a href="users.php"     class="admin-nav-link active">Players</a>
        <a href="scores.php"    class="admin-nav-link">Scores</a>
        <a href="logout.php"    class="admin-nav-link logout">Logout</a>
    </div>
</nav>

<div class="page-content">

    <div class="page-eyebrow">// player management</div>
    <div class="page-heading">MANAGE PLAYERS</div>

    <?php if ($message): ?>
        <div class="wolf-alert wolf-alert-success">✓ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="wolf-alert wolf-alert-error">⚠ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="glass-card">

        <!-- search & filter bar -->
        <form method="GET" action="users.php" id="searchForm" onsubmit="triggerSearch(event)">
            <div class="search-wrap">
                <div class="search-input-wrap">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" class="wolf-input"
                           id="searchInput"
                           placeholder="Search by username or email..."
                           value="<?php echo htmlspecialchars($search_input); ?>"
                           autocomplete="off">
                </div>
                <button type="submit" class="search-btn">SEARCH</button>
                <a href="users.php?filter=banned"
                   class="filter-btn <?php echo $filter === 'banned' ? 'active-filter' : ''; ?>">
                    Banned
                </a>
                <a href="users.php?filter=vip"
                   class="filter-btn <?php echo $filter === 'vip' ? 'active-filter' : ''; ?>">
                    VIP Only
                </a>
                <?php if ($search_input || $filter): ?>
                <a href="users.php" class="clear-btn">✕ Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- results count -->
        <div class="results-bar">
            <div class="results-count">
                Showing <span><?php echo $total_count; ?></span>
                <?php
                if ($search_input)         echo " result" . ($total_count !== 1 ? "s" : "") . " for &quot;" . htmlspecialchars($search_input) . "&quot;";
                elseif ($filter === 'banned') echo " banned player" . ($total_count !== 1 ? "s" : "");
                elseif ($filter === 'vip')    echo " VIP player" . ($total_count !== 1 ? "s" : "");
                else                          echo " total player" . ($total_count !== 1 ? "s" : "");
                ?>
            </div>
        </div>

        <!-- table -->
        <?php if ($total_count > 0): ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th>Player</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>VIP</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td>
                            <span style="font-family:'Orbitron',monospace;
                                font-size:0.7rem; color:rgba(255,255,255,0.25);">
                                #<?php echo $user['id']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="player-cell">
                                <?php echo mini_avatar($user['username'], $user['profile_photo'], 34); ?>
                                <div>
                                    <div class="player-name">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if ($user['vip_status']): ?>
                                            <span class="vip-badge">VIP</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="player-id">ID:<?php echo $user['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--wolf-muted); font-size:0.82rem;">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['vip_status']): ?>
                                <span style="color:var(--wolf-gold); font-size:0.8rem;
                                    font-weight:600; font-family:'Rajdhani',sans-serif;">
                                    Active
                                </span>
                            <?php else: ?>
                                <span style="color:rgba(255,255,255,0.2);
                                    font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--wolf-muted); font-size:0.8rem;">
                            <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td>
                            <div class="actions-cell">

                                <?php if ($user['status'] === 'active'): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action"  value="ban">
                                    <button type="submit" class="btn-action btn-ban"
                                        onclick="return confirm('Ban <?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>?')">
                                        🚫 Ban
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action"  value="unban">
                                    <button type="submit" class="btn-action btn-unban">
                                        ✓ Unban
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php if (!$user['vip_status']): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action"  value="grant_vip">
                                    <button type="submit" class="btn-action btn-vip">
                                        ⭐ VIP
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action"  value="revoke_vip">
                                    <button type="submit" class="btn-action btn-vip"
                                            style="opacity:0.7;">
                                        ✕ VIP
                                    </button>
                                </form>
                                <?php endif; ?>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action"  value="delete">
                                    <button type="submit" class="btn-action btn-delete"
                                        onclick="return confirm('Permanently delete <?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?> and all their data?')">
                                        🗑
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <?php echo $search_input ? '🔍' : '👥'; ?>
            </div>
            <div class="empty-text">
                <?php
                if ($search_input)            echo "No players found matching \"" . htmlspecialchars($search_input) . "\"";
                elseif ($filter === 'banned') echo "No banned players";
                elseif ($filter === 'vip')    echo "No VIP players yet";
                else                           echo "No players registered yet";
                ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function triggerSearch(e) {
    const input = document.getElementById('searchInput').value.trim();
    if (!input) return;

    e.preventDefault();

    const overlay = document.getElementById('searchOverlay');
    overlay.classList.add('active');

    setTimeout(() => {
        e.target.submit();
    }, 1800);
}

// also show animation when filter links are clicked
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.href;
        const overlay = document.getElementById('searchOverlay');
        overlay.classList.add('active');
        setTimeout(() => {
            window.location.href = href;
        }, 1200);
    });
});


</script>
</body>
</html>