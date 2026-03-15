<?php
define('BASE_URL', '../');
session_start();
require_once '../db.php';
require_once 'auth_check.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score_id = (int) $_POST['score_id'];
    if ($score_id <= 0) {
        $error = "Invalid score record.";
    } else {
        mysqli_query($conn, "DELETE FROM scores WHERE id=$score_id");
        $message = "Score record deleted successfully.";
    }
}

$filter_user  = '';
$filter_agent = '';

if (isset($_GET['username']) && !empty(trim($_GET['username']))) {
    $filter_user = trim(mysqli_real_escape_string($conn, $_GET['username']));
}

if (isset($_GET['agent']) && !empty($_GET['agent'])) {
    $filter_agent = mysqli_real_escape_string($conn, $_GET['agent']);
}

$where = [];
if ($filter_user)  $where[] = "u.username LIKE '%$filter_user%'";
if ($filter_agent) $where[] = "s.agent = '$filter_agent'";
$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$scores = mysqli_query($conn, "
    SELECT s.*, u.username, u.vip_status, u.profile_photo
    FROM scores s
    JOIN users u ON s.user_id = u.id
    $where_sql
    ORDER BY s.score DESC
");

$total_filtered = mysqli_num_rows($scores);

$overall_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)          AS total,
        MAX(score)        AS highest,
        ROUND(AVG(score)) AS average,
        SUM(kills)        AS total_kills
    FROM scores
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

function mini_avatar($username, $profile_photo, $size = 34) {
    $letter    = strtoupper(substr($username, 0, 1));
    $color     = get_letter_color($letter);
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
    <title>Manage Scores — Wolf Strike Admin</title>
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
                radial-gradient(ellipse 700px 400px at 0% 50%,   rgba(226,75,74,0.05) 0%, transparent 70%),
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

        /* ── stat cards ── */
        .stat-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 14px;
            margin-bottom: 32px;
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

        .stat-card:nth-child(1) { animation-delay: 0.05s; border-color: rgba(127,119,221,0.15); }
        .stat-card:nth-child(2) { animation-delay: 0.10s; border-color: rgba(239,159,39,0.15); }
        .stat-card:nth-child(3) { animation-delay: 0.15s; border-color: rgba(29,158,117,0.15); }
        .stat-card:nth-child(4) { animation-delay: 0.20s; border-color: rgba(226,75,74,0.15); }

        @keyframes statCardIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

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

        .stat-card-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin-bottom: 14px;
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
        }

        .stat-card-sub {
            font-size: 0.72rem;
            color: var(--wolf-muted);
            letter-spacing: 0.5px;
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

        /* ── filter bar ── */
        .filter-wrap {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input-wrap {
            position: relative;
            flex: 1;
            min-width: 180px;
        }

        .search-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--wolf-muted);
            font-size: 13px;
            pointer-events: none;
        }

        .wolf-input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(226,75,74,0.2);
            border-radius: 10px;
            padding: 10px 14px 10px 38px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.92rem;
            font-weight: 500;
            color: #ffffff;
            outline: none;
            transition: all 0.25s;
        }

        .wolf-input::placeholder { color: rgba(255,255,255,0.2); }

        .wolf-input:focus {
            border-color: var(--wolf-red);
            background: rgba(226,75,74,0.05);
            box-shadow: 0 0 0 3px rgba(226,75,74,0.1);
        }

        .wolf-select {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(226,75,74,0.2);
            border-radius: 10px;
            padding: 10px 14px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.92rem;
            font-weight: 500;
            color: #ffffff;
            outline: none;
            cursor: pointer;
            transition: all 0.25s;
            min-width: 140px;
        }

        .wolf-select option { background: #0d0d1a; color: #ffffff; }

        .wolf-select:focus {
            border-color: var(--wolf-red);
            box-shadow: 0 0 0 3px rgba(226,75,74,0.1);
        }

        .filter-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--wolf-red), #A32D2D);
            border: none;
            border-radius: 10px;
            font-family: 'Orbitron', monospace;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(226,75,74,0.4);
        }

        .clear-btn {
            padding: 10px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--wolf-muted);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .clear-btn:hover { color: #ffffff; border-color: rgba(255,255,255,0.2); }

        /* ── results bar ── */
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

        .scan-ring-outer {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: var(--wolf-red);
            border-right-color: rgba(226,75,74,0.3);
            animation: spinOuter 1.2s linear infinite;
        }

        .scan-ring-inner {
            position: absolute;
            inset: 20px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-bottom-color: rgba(226,75,74,0.6);
            border-left-color: rgba(226,75,74,0.2);
            animation: spinInner 0.8s linear infinite;
        }

        .scan-ring-mid {
            position: absolute;
            inset: 40px;
            border-radius: 50%;
            border: 1px solid rgba(226,75,74,0.2);
            animation: pulseMid 1.5s ease-in-out infinite;
        }

        @keyframes spinOuter  { to { transform: rotate(360deg); } }
        @keyframes spinInner  { to { transform: rotate(-360deg); } }
        @keyframes pulseMid   {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50%       { opacity: 0.8; transform: scale(1.05); }
        }

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
        }

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
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;
        }

        .admin-table th:hover { color: rgba(255,255,255,0.7); }

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

        .admin-table tbody tr {
            animation: rowIn 0.4s cubic-bezier(0.16,1,0.3,1) both;
        }

        <?php for ($i = 1; $i <= 30; $i++): ?>
        .admin-table tbody tr:nth-child(<?php echo $i; ?>) {
            animation-delay: <?php echo ($i * 0.03); ?>s;
        }
        <?php endfor; ?>

        @keyframes rowIn {
            from { opacity: 0; transform: translateX(-12px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ── cells ── */
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

        .mini-av img { width: 100%; height: 100%; object-fit: cover; }

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

        .score-val {
            font-family: 'Orbitron', monospace;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--wolf-purple);
            text-shadow: 0 0 8px rgba(127,119,221,0.3);
        }

        .agent-badge {
            display: inline-block;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 3px 9px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .agent-scout   { background: rgba(29,158,117,0.15);  color: #5DCAA5;  border: 1px solid rgba(29,158,117,0.3); }
        .agent-hunter  { background: rgba(127,119,221,0.15); color: #AFA9EC;  border: 1px solid rgba(127,119,221,0.3); }
        .agent-alpha   { background: rgba(216,90,48,0.15);   color: #F0997B;  border: 1px solid rgba(216,90,48,0.3); }
        .agent-phantom { background: rgba(239,159,39,0.15);  color: #FAC775;  border: 1px solid rgba(239,159,39,0.3); }

        .rounds-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rounds-track {
            width: 44px; height: 4px;
            background: rgba(255,255,255,0.07);
            border-radius: 2px;
            overflow: hidden;
        }

        .rounds-fill {
            height: 100%;
            border-radius: 2px;
            background: var(--wolf-teal);
        }

        .btn-delete {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 6px;
            background: rgba(226,75,74,0.1);
            color: #f09595;
            border: 1px solid rgba(226,75,74,0.2);
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-delete:hover {
            background: rgba(226,75,74,0.22);
        }

        /* ── rank col ── */
        .rank-num {
            font-family: 'Orbitron', monospace;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .rank-gold   { color: var(--wolf-gold);  text-shadow: 0 0 8px rgba(239,159,39,0.5); }
        .rank-silver { color: #C2C0B6; }
        .rank-bronze { color: #D85A30; }
        .rank-other  { color: rgba(255,255,255,0.25); font-size: 0.72rem; }

        /* ── empty ── */
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
    </style>
</head>
<body>

<!-- search overlay -->
<div class="search-overlay" id="searchOverlay">
    <div class="scan-line"></div>
    <div class="scan-box">
        <div class="scan-ring-outer"></div>
        <div class="scan-ring-inner"></div>
        <div class="scan-ring-mid"></div>
        <div class="scan-radar"></div>
        <div class="scan-center">
            <div class="scan-icon">📊</div>
            <div class="scan-label">Filtering</div>
        </div>
    </div>
    <div class="scan-status">
        SCANNING SCORE RECORDS<span class="scan-dots"></span>
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
        <a href="users.php"     class="admin-nav-link">Players</a>
        <a href="scores.php"    class="admin-nav-link active">Scores</a>
        <a href="logout.php"    class="admin-nav-link logout">Logout</a>
    </div>
</nav>

<div class="page-content">

    <div class="page-eyebrow">// score management</div>
    <div class="page-heading">MANAGE SCORES</div>

    <!-- stat cards -->
    <div class="stat-cards-grid">
        <div class="stat-card">
            <div class="stat-card-fill" style="background:var(--wolf-purple);"></div>
            <div class="stat-card-icon"
                 style="background:rgba(127,119,221,0.15); color:var(--wolf-purple);">
                📋
            </div>
            <div class="stat-card-label">Total Records</div>
            <div class="stat-card-val" style="color:var(--wolf-purple);"
                 data-target="<?php echo $overall_stats['total']; ?>">0</div>
            <div class="stat-card-sub">All score entries</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-fill" style="background:var(--wolf-gold);"></div>
            <div class="stat-card-icon"
                 style="background:rgba(239,159,39,0.15); color:var(--wolf-gold);">
                🏆
            </div>
            <div class="stat-card-label">Highest Score</div>
            <div class="stat-card-val" style="color:var(--wolf-gold);"
                 data-target="<?php echo $overall_stats['highest']; ?>">0</div>
            <div class="stat-card-sub">All-time record</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-fill" style="background:var(--wolf-teal);"></div>
            <div class="stat-card-icon"
                 style="background:rgba(29,158,117,0.15); color:var(--wolf-teal);">
                📈
            </div>
            <div class="stat-card-label">Average Score</div>
            <div class="stat-card-val" style="color:var(--wolf-teal);"
                 data-target="<?php echo $overall_stats['average']; ?>">0</div>
            <div class="stat-card-sub">Per game average</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-fill" style="background:var(--wolf-red);"></div>
            <div class="stat-card-icon"
                 style="background:rgba(226,75,74,0.15); color:var(--wolf-red);">
                💀
            </div>
            <div class="stat-card-label">Total Kills</div>
            <div class="stat-card-val" style="color:var(--wolf-red);"
                 data-target="<?php echo $overall_stats['total_kills']; ?>">0</div>
            <div class="stat-card-sub">Globally eliminated</div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="wolf-alert wolf-alert-success">✓ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="wolf-alert wolf-alert-error">⚠ <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="glass-card">

        <!-- filter form -->
        <form method="GET" action="scores.php" id="filterForm" onsubmit="triggerSearch(event)">
            <div class="filter-wrap">
                <div class="search-input-wrap">
                    <span class="search-icon">👤</span>
                    <input type="text" name="username" class="wolf-input"
                           placeholder="Filter by username..."
                           value="<?php echo htmlspecialchars($filter_user); ?>"
                           autocomplete="off">
                </div>
                <select name="agent" class="wolf-select" id="agentSelect">
                    <option value="">All agents</option>
                    <?php foreach (['Scout','Hunter','Alpha','Phantom'] as $a): ?>
                    <option value="<?php echo $a; ?>"
                        <?php echo $filter_agent === $a ? 'selected' : ''; ?>>
                        <?php echo $a; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="filter-btn">FILTER</button>
                <?php if ($filter_user || $filter_agent): ?>
                <a href="scores.php" class="clear-btn">✕ Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- results count -->
        <div class="results-bar">
            <div class="results-count">
                Showing <span><?php echo $total_filtered; ?></span>
                record<?php echo $total_filtered !== 1 ? 's' : ''; ?>
                <?php if ($filter_user || $filter_agent): ?>
                    <?php if ($filter_user): ?>
                        — player: <span><?php echo htmlspecialchars($filter_user); ?></span>
                    <?php endif; ?>
                    <?php if ($filter_agent): ?>
                        — agent: <span><?php echo $filter_agent; ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- table -->
        <?php if ($total_filtered > 0): ?>
        <div class="admin-table-wrap">
            <table class="admin-table" id="scoresTable">
                <thead>
                    <tr>
                        <th style="width:52px;">Rank</th>
                        <th>Player</th>
                        <th>Agent</th>
                        <th>Score</th>
                        <th>Kills</th>
                        <th>Rounds</th>
                        <th>Date</th>
                        <th style="width:80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    while ($row = mysqli_fetch_assoc($scores)):
                    ?>
                    <tr>
                        <td>
                            <?php if ($rank === 1): ?>
                                <span class="rank-num rank-gold">01</span>
                            <?php elseif ($rank === 2): ?>
                                <span class="rank-num rank-silver">02</span>
                            <?php elseif ($rank === 3): ?>
                                <span class="rank-num rank-bronze">03</span>
                            <?php else: ?>
                                <span class="rank-num rank-other">
                                    <?php echo str_pad($rank, 2, '0', STR_PAD_LEFT); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="player-cell">
                                <?php echo mini_avatar($row['username'], $row['profile_photo'], 32); ?>
                                <div>
                                    <div class="player-name">
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
                        <td>
                            <span class="score-val">
                                <?php echo number_format($row['score']); ?>
                            </span>
                        </td>
                        <td style="color:rgba(255,255,255,0.8); font-weight:600;">
                            <?php echo $row['kills']; ?>
                        </td>
                        <td>
                            <div class="rounds-wrap">
                                <div class="rounds-track">
                                    <div class="rounds-fill"
                                         style="width:<?php echo ($row['rounds_survived']/5)*100; ?>%;">
                                    </div>
                                </div>
                                <span style="font-size:0.82rem; color:var(--wolf-muted);">
                                    <?php echo $row['rounds_survived']; ?>/5
                                </span>
                            </div>
                        </td>
                        <td style="color:var(--wolf-muted); font-size:0.8rem;">
                            <?php echo date('d M Y', strtotime($row['played_at'])); ?>
                            <span style="display:block; font-size:0.72rem;">
                                <?php echo date('H:i', strtotime($row['played_at'])); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="score_id"
                                       value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-delete"
                                    onclick="return confirm('Delete this score record permanently?')">
                                    🗑 Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php $rank++; endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📊</div>
            <div class="empty-text">
                <?php echo ($filter_user || $filter_agent)
                    ? "No records match your filter"
                    : "No game records yet"; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function triggerSearch(e) {
    e.preventDefault();
    const overlay = document.getElementById('searchOverlay');
    overlay.classList.add('active');
    setTimeout(() => { e.target.submit(); }, 1600);
}

document.getElementById('agentSelect').addEventListener('change', function() {
    const overlay = document.getElementById('searchOverlay');
    overlay.classList.add('active');
    setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 1200);
});

window.addEventListener('load', function() {
    document.querySelectorAll('.stat-card-val[data-target]').forEach((el, i) => {
        const target = parseInt(el.getAttribute('data-target')) || 0;
        const delay  = 300 + (i * 120);
        setTimeout(() => countUp(el, target, 1200), delay);
    });
});

function countUp(el, target, duration) {
    const start = performance.now();
    function update(time) {
        const progress = Math.min((time - start) / duration, 1);
        const ease     = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.floor(target * ease).toLocaleString();
        if (progress < 1) requestAnimationFrame(update);
        else el.textContent = target.toLocaleString();
    }
    requestAnimationFrame(update);
}
</script>
</body>
</html>