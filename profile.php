<?php
session_start();
require_once 'db.php';
refresh_user_session($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid     = (int) $_SESSION['user_id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
            $file     = $_FILES['profile_photo'];
            $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $max_size = 3 * 1024 * 1024;

            if (!in_array($file['type'], $allowed)) {
                $error = "Only JPG, PNG, WEBP or GIF images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error = "Image must be under 3MB.";
            } else {
                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'wolf_' . $uid . '_' . time() . '.' . $ext;
                $dest     = __DIR__ . '/assets/uploads/profiles/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $old = mysqli_fetch_row(mysqli_query($conn, "SELECT profile_photo FROM users WHERE id=$uid"))[0];
                    if ($old && file_exists(__DIR__ . '/assets/uploads/profiles/' . $old)) {
                        unlink(__DIR__ . '/assets/uploads/profiles/' . $old);
                    }
                    mysqli_query($conn, "UPDATE users SET profile_photo='$filename' WHERE id=$uid");
                    $success = "Profile photo updated.";
                } else {
                    $error = "Upload failed. Check folder permissions.";
                }
            }
        } else {
            $error = "Please select a photo to upload.";
        }
    }

    if ($action === 'remove_photo') {
        $old = mysqli_fetch_row(mysqli_query($conn, "SELECT profile_photo FROM users WHERE id=$uid"))[0];
        if ($old && file_exists(__DIR__ . '/assets/uploads/profiles/' . $old)) {
            unlink(__DIR__ . '/assets/uploads/profiles/' . $old);
        }
        mysqli_query($conn, "UPDATE users SET profile_photo=NULL WHERE id=$uid");
        $success = "Profile photo removed.";
    }

    if ($action === 'update_username') {
        $new_username = trim(mysqli_real_escape_string($conn, $_POST['new_username']));
        if (empty($new_username)) {
            $error = "Username cannot be empty.";
        } elseif (strlen($new_username) < 3) {
            $error = "Username must be at least 3 characters.";
        } elseif (strlen($new_username) > 50) {
            $error = "Username cannot exceed 50 characters.";
        } else {
            $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$new_username' AND id != $uid");
            if (mysqli_num_rows($check) > 0) {
                $error = "That callsign is already taken by another wolf.";
            } else {
                mysqli_query($conn, "UPDATE users SET username='$new_username' WHERE id=$uid");
                $_SESSION['username'] = $new_username;
                $success = "Callsign updated successfully.";
            }
        }
    }
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT username, email, vip_status, profile_photo, created_at
    FROM users WHERE id=$uid
"));

$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)              AS total_games,
        MAX(score)            AS best_score,
        SUM(kills)            AS total_kills,
        ROUND(AVG(score))     AS avg_score,
        MAX(rounds_survived)  AS best_rounds,
        SUM(rounds_survived)  AS total_rounds
    FROM scores WHERE user_id=$uid
"));

$rank_row = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) + 1 AS rank
    FROM (
        SELECT user_id, MAX(score) AS best
        FROM scores GROUP BY user_id
    ) AS lb
    WHERE best > (
        SELECT COALESCE(MAX(score), 0) FROM scores WHERE user_id=$uid
    )
"));

$lb_rank    = (int)($rank_row['rank'] ?? 0);
$has_played = ($stats['total_games'] ?? 0) > 0;

$history = mysqli_query($conn, "
    SELECT score, agent, kills, rounds_survived, played_at
    FROM scores WHERE user_id=$uid
    ORDER BY played_at DESC
");

$first_letter = strtoupper(substr($user['username'], 0, 1));
$has_photo    = !empty($user['profile_photo']) &&
                file_exists(__DIR__ . '/assets/uploads/profiles/' . $user['profile_photo']);
$photo_url    = $has_photo ? 'assets/uploads/profiles/' . $user['profile_photo'] : null;

$letter_colors = [
    'A' => '#E24B4A', 'B' => '#7F77DD', 'C' => '#1D9E75', 'D' => '#EF9F27',
    'E' => '#D4537E', 'F' => '#378ADD', 'G' => '#639922', 'H' => '#F0997B',
    'I' => '#534AB7', 'J' => '#5DCAA5', 'K' => '#BA7517', 'L' => '#AFA9EC',
    'M' => '#7F77DD', 'N' => '#E24B4A', 'O' => '#1D9E75', 'P' => '#EF9F27',
    'Q' => '#D4537E', 'R' => '#378ADD', 'S' => '#1D9E75', 'T' => '#F0997B',
    'U' => '#534AB7', 'V' => '#5DCAA5', 'W' => '#7F77DD', 'X' => '#E24B4A',
    'Y' => '#EF9F27', 'Z' => '#D4537E',
];
$letter_color = $letter_colors[$first_letter] ?? '#7F77DD';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — Wolf Strike</title>
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
            --letter-color:  <?php echo $letter_color; ?>;
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
                radial-gradient(ellipse 600px 400px at 10% 40%, rgba(127,119,221,0.07) 0%, transparent 70%),
                radial-gradient(ellipse 500px 300px at 90% 60%, rgba(29,158,117,0.05) 0%, transparent 70%);
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
            max-width: 1100px;
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
            margin-bottom: 40px;
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
            text-transform: uppercase;
        }

        /* ── profile header ── */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* ── avatar container ── */
        .avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .avatar-circle {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--letter-color);
            box-shadow: 0 0 16px color-mix(in srgb, var(--letter-color) 40%, transparent);
            position: relative;
            cursor: pointer;
            transition: all 0.25s;
        }

        .avatar-circle:hover {
            transform: scale(1.05);
            box-shadow: 0 0 28px color-mix(in srgb, var(--letter-color) 60%, transparent);
        }

        /* photo avatar */
        .avatar-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* letter avatar */
        .avatar-letter {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--letter-color) 12%, transparent);
            font-family: 'Orbitron', monospace;
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--letter-color);
            text-shadow:
                0 0 10px var(--letter-color),
                0 0 30px color-mix(in srgb, var(--letter-color) 60%, transparent),
                0 0 60px color-mix(in srgb, var(--letter-color) 30%, transparent);
            animation: letterPulse 3s ease-in-out infinite;
            position: relative;
        }

        @keyframes letterPulse {
            0%, 100% {
                text-shadow:
                    0 0 10px var(--letter-color),
                    0 0 30px color-mix(in srgb, var(--letter-color) 60%, transparent),
                    0 0 60px color-mix(in srgb, var(--letter-color) 30%, transparent);
            }
            50% {
                text-shadow:
                    0 0 16px var(--letter-color),
                    0 0 48px color-mix(in srgb, var(--letter-color) 80%, transparent),
                    0 0 90px color-mix(in srgb, var(--letter-color) 40%, transparent);
            }
        }

        /* camera overlay on hover */
        .avatar-overlay {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: rgba(0,0,0,0.55);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            gap: 2px;
        }

        .avatar-circle:hover .avatar-overlay { opacity: 1; }

        .avatar-overlay-icon {
            font-size: 1.2rem;
            line-height: 1;
        }

        .avatar-overlay-text {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.55rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: #ffffff;
            text-transform: uppercase;
        }

        /* camera badge */
        .avatar-badge {
            position: absolute;
            bottom: 0; right: 0;
            width: 24px; height: 24px;
            border-radius: 50%;
            background: var(--wolf-dark);
            border: 1.5px solid rgba(127,119,221,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: var(--wolf-purple);
        }

        /* ── profile info ── */
        .profile-info { flex: 1; min-width: 0; }

        .profile-username {
            font-family: 'Orbitron', monospace;
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 2px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-meta {
            font-size: 0.82rem;
            color: var(--wolf-muted);
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .profile-badge {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 3px 9px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-rank   { background: rgba(239,159,39,0.15);  color: var(--wolf-gold);   border: 1px solid rgba(239,159,39,0.3); }
        .badge-vip    { background: linear-gradient(135deg, #EF9F27, #BA7517); color: #1A1A1A; font-weight: 700; }
        .badge-member { background: rgba(127,119,221,0.1);  color: rgba(127,119,221,0.8); border: 1px solid rgba(127,119,221,0.2); }

        /* ── upload panel ── */
        .upload-panel {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .upload-panel.open { display: block; }

        .upload-drop-zone {
            border: 2px dashed rgba(127,119,221,0.3);
            border-radius: 12px;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
        }

        .upload-drop-zone:hover,
        .upload-drop-zone.dragover {
            border-color: var(--wolf-purple);
            background: rgba(127,119,221,0.06);
        }

        .upload-drop-icon {
            font-size: 2rem;
            margin-bottom: 8px;
            opacity: 0.5;
        }

        .upload-drop-text {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--wolf-muted);
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .upload-drop-hint {
            font-size: 0.72rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 0.5px;
        }

        #photoInput { display: none; }

        /* preview before upload */
        .photo-preview-wrap {
            display: none;
            text-align: center;
            margin-bottom: 12px;
        }

        .photo-preview-wrap.show { display: block; }

        .photo-preview-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--wolf-purple);
            box-shadow: 0 0 16px rgba(127,119,221,0.4);
            margin-bottom: 8px;
        }

        .upload-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        /* ── form elements ── */
        .wolf-input {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(127,119,221,0.2);
            border-radius: 8px;
            padding: 10px 14px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            color: #ffffff;
            outline: none;
            transition: all 0.25s;
            width: 100%;
        }

        .wolf-input:focus {
            border-color: var(--wolf-purple);
            box-shadow: 0 0 0 3px rgba(127,119,221,0.12);
            background: rgba(127,119,221,0.05);
        }

        .wolf-input::placeholder { color: rgba(255,255,255,0.2); }

        .wolf-btn-sm {
            padding: 9px 20px;
            background: linear-gradient(135deg, var(--wolf-purple), var(--wolf-purple-2));
            border: none;
            border-radius: 7px;
            font-family: 'Orbitron', monospace;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .wolf-btn-sm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(127,119,221,0.4);
        }

        .wolf-btn-outline {
            padding: 8px 18px;
            background: transparent;
            border: 1px solid rgba(127,119,221,0.3);
            border-radius: 7px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--wolf-purple);
            cursor: pointer;
            transition: all 0.2s;
        }

        .wolf-btn-outline:hover {
            background: rgba(127,119,221,0.1);
            border-color: var(--wolf-purple);
        }

        .wolf-btn-danger {
            padding: 8px 16px;
            background: rgba(226,75,74,0.1);
            border: 1px solid rgba(226,75,74,0.3);
            border-radius: 7px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: #f09595;
            cursor: pointer;
            transition: all 0.2s;
        }

        .wolf-btn-danger:hover {
            background: rgba(226,75,74,0.2);
        }

        /* ── alerts ── */
        .wolf-alert {
            border-radius: 8px;
            padding: 11px 16px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-bottom: 24px;
            border-left: 3px solid;
        }

        .wolf-alert-error   { background: rgba(226,75,74,0.1);  border-color: #E24B4A; color: #f09595; }
        .wolf-alert-success { background: rgba(29,158,117,0.1); border-color: #1D9E75; color: #5DCAA5; }

        /* ── rank card ── */
        .rank-card {
            background: rgba(239,159,39,0.04);
            border: 1px solid rgba(239,159,39,0.15);
            border-radius: 12px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .rank-num {
            font-family: 'Orbitron', monospace;
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--wolf-gold);
            text-shadow: 0 0 20px rgba(239,159,39,0.6);
            line-height: 1;
            flex-shrink: 0;
        }

        .rank-label {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: rgba(239,159,39,0.6);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .rank-desc {
            font-size: 0.9rem;
            color: var(--wolf-muted);
            line-height: 1.6;
        }

        /* ── stats grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 12px;
        }

        .stat-block {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            padding: 20px 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .stat-block:hover { transform: translateY(-2px); }

        .stat-block::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
        }

        .stat-block.purple { border-color: rgba(127,119,221,0.15); }
        .stat-block.purple::after { background: var(--wolf-purple); }
        .stat-block.teal   { border-color: rgba(29,158,117,0.15);  }
        .stat-block.teal::after   { background: var(--wolf-teal); }
        .stat-block.red    { border-color: rgba(226,75,74,0.15);   }
        .stat-block.red::after    { background: var(--wolf-red); }
        .stat-block.gold   { border-color: rgba(239,159,39,0.15);  }
        .stat-block.gold::after   { background: var(--wolf-gold); }
        .stat-block.pink   { border-color: rgba(212,83,126,0.15);  }
        .stat-block.pink::after   { background: #D4537E; }
        .stat-block.blue   { border-color: rgba(55,138,221,0.15);  }
        .stat-block.blue::after   { background: #378ADD; }

        .stat-icon-label {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 2px;
            color: var(--wolf-muted);
            text-transform: uppercase;
            margin-bottom: 10px;
            font-family: 'Rajdhani', sans-serif;
        }

        .stat-big-val {
            font-family: 'Orbitron', monospace;
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-sub {
            font-size: 0.7rem;
            color: var(--wolf-muted);
            letter-spacing: 1px;
            font-family: 'Rajdhani', sans-serif;
        }

        /* ── history ── */
        .history-wrap {
            border: 1px solid rgba(127,119,221,0.1);
            border-radius: 12px;
            overflow: hidden;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table thead tr {
            background: rgba(127,119,221,0.06);
            border-bottom: 1px solid rgba(127,119,221,0.12);
        }

        .history-table th {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            padding: 14px 18px;
            text-align: left;
        }

        .history-table td {
            padding: 14px 18px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--wolf-text);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            vertical-align: middle;
        }

        .history-table tbody tr:last-child td { border-bottom: none; }
        .history-table tbody tr:hover td { background: rgba(127,119,221,0.03); }

        .score-val {
            font-family: 'Orbitron', monospace;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--wolf-purple);
            text-shadow: 0 0 8px rgba(127,119,221,0.4);
        }

        .agent-badge {
            display: inline-block;
            font-size: 0.68rem;
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

        .rounds-bar-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rounds-bar-track {
            width: 55px;
            height: 4px;
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
            font-size: 0.85rem;
            color: var(--wolf-text);
            font-weight: 600;
        }

        /* ── edit form ── */
        .edit-form-wrap {
            display: none;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .edit-form-wrap.open { display: block; }

        /* ── empty state ── */
        .empty-state {
            text-align: center;
            padding: 56px 20px;
        }

        .empty-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
            opacity: 0.25;
        }

        .empty-text {
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.2);
            letter-spacing: 2px;
            text-transform: uppercase;
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
            margin-left: 6px;
            vertical-align: middle;
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
        <a href="leaderboard.php"  class="rog-nav-link">Leaderboard</a>
        <a href="profile.php"      class="rog-nav-link active">Profile</a>
        <a href="logout.php"       class="rog-nav-link danger">Logout</a>
    </div>
</nav>

<div class="page-content">

    <div class="page-eyebrow">// player profile</div>
    <div class="page-heading">AGENT FILE</div>

    <?php if ($error): ?>
        <div class="wolf-alert wolf-alert-error">⚠ <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="wolf-alert wolf-alert-success">✓ <?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- left column -->
        <div class="col-lg-4">

            <!-- identity card -->
            <div class="glass-card mb-4">
                <div class="section-title" style="margin-bottom:20px;">
                    <div class="section-accent"></div>
                    <div class="section-label">Identity</div>
                </div>

                <div class="profile-header">

                    <!-- avatar -->
                    <div class="avatar-wrap">
                        <div class="avatar-circle" onclick="toggleUploadPanel()">
                            <?php if ($has_photo): ?>
                                <img src="<?php echo $photo_url; ?>"
                                     alt="Profile" class="avatar-photo">
                            <?php else: ?>
                                <div class="avatar-letter">
                                    <?php echo $first_letter; ?>
                                </div>
                            <?php endif; ?>
                            <div class="avatar-overlay">
                                <div class="avatar-overlay-icon">📷</div>
                                <div class="avatar-overlay-text">Change Photo</div>
                            </div>
                        </div>
                        <div class="avatar-badge">✎</div>
                    </div>

                    <div class="profile-info">
                        <div class="profile-username">
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if ($user['vip_status']): ?>
                                <span class="vip-badge">VIP</span>
                            <?php endif; ?>
                        </div>
                        <div class="profile-meta">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                        <div class="profile-badges">
                            <?php if ($has_played): ?>
                                <span class="profile-badge badge-rank">
                                    RANK #<?php echo $lb_rank; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($user['vip_status']): ?>
                                <span class="profile-badge badge-vip">VIP</span>
                            <?php endif; ?>
                            <span class="profile-badge badge-member">
                                SINCE <?php echo date('M Y', strtotime($user['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- photo upload panel -->
                <div class="upload-panel" id="uploadPanel">

                    <form method="POST" enctype="multipart/form-data" id="photoForm">
                        <input type="hidden" name="action" value="update_photo">
                        <input type="file" name="profile_photo" id="photoInput"
                               accept="image/jpeg,image/png,image/webp,image/gif">

                        <div class="photo-preview-wrap" id="previewWrap">
                            <img id="previewImg" src="" alt="Preview"
                                 class="photo-preview-img">
                            <div style="font-size:0.75rem; color:var(--wolf-muted);
                                 letter-spacing:0.5px;">Preview</div>
                        </div>

                        <div class="upload-drop-zone" id="dropZone"
                             onclick="document.getElementById('photoInput').click()">
                            <div class="upload-drop-icon">📷</div>
                            <div class="upload-drop-text">
                                Click to choose a photo
                            </div>
                            <div class="upload-drop-hint">
                                JPG · PNG · WEBP · GIF · Max 3MB
                            </div>
                        </div>

                        <div class="upload-actions">
                            <button type="submit" class="wolf-btn-sm"
                                    id="uploadSubmitBtn" style="flex:1; display:none;">
                                UPLOAD PHOTO
                            </button>
                            <button type="button" class="wolf-btn-outline"
                                    onclick="toggleUploadPanel()">
                                Cancel
                            </button>
                            <?php if ($has_photo): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="remove_photo">
                                <button type="submit" class="wolf-btn-danger"
                                        onclick="return confirm('Remove profile photo?')">
                                    Remove
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- change username -->
                <div style="margin-top:20px; padding-top:20px;
                     border-top:1px solid rgba(255,255,255,0.06);">
                    <div style="display:flex; justify-content:space-between;
                         align-items:center;">
                        <div style="font-family:'Rajdhani',sans-serif;
                             font-size:0.72rem; font-weight:600;
                             letter-spacing:2px; color:var(--wolf-muted);
                             text-transform:uppercase;">
                            Change Callsign
                        </div>
                        <button class="wolf-btn-outline"
                                style="font-size:0.7rem; padding:5px 12px;"
                                onclick="toggleUsernameEdit()">
                            Edit
                        </button>
                    </div>

                    <div class="edit-form-wrap" id="usernameEditForm">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_username">
                            <div style="display:flex; gap:8px; margin-top:12px;">
                                <input type="text" name="new_username"
                                       class="wolf-input"
                                       placeholder="New callsign..."
                                       value="<?php echo htmlspecialchars($user['username']); ?>"
                                       minlength="3" maxlength="50" required>
                                <button type="submit" class="wolf-btn-sm">
                                    SAVE
                                </button>
                            </div>
                            <div style="font-size:0.72rem; color:rgba(255,255,255,0.25);
                                 margin-top:6px; letter-spacing:0.5px;">
                                Must be unique · 3–50 characters
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- rank card -->
            <?php if ($has_played): ?>
            <div class="glass-card mb-4">
                <div class="section-title" style="margin-bottom:16px;">
                    <div class="section-accent"></div>
                    <div class="section-label">Global Rank</div>
                </div>
                <div class="rank-card">
                    <div class="rank-num">#<?php echo $lb_rank; ?></div>
                    <div>
                        <div class="rank-label">Leaderboard Position</div>
                        <div class="rank-desc">
                            Best score:
                            <span style="color:#ffffff; font-weight:600;">
                                <?php echo number_format($stats['best_score'] ?? 0); ?>
                            </span><br>
                            <span style="font-size:0.82rem;">
                                Across <?php echo number_format($stats['total_games']); ?> games
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- right column -->
        <div class="col-lg-8">

            <!-- stats -->
            <div class="glass-card mb-4">
                <div class="section-title" style="margin-bottom:20px;">
                    <div class="section-accent"></div>
                    <div class="section-label">Combat Stats</div>
                </div>

                <?php if ($has_played): ?>
                <div class="stats-grid">
                    <div class="stat-block purple">
                        <div class="stat-icon-label">Games Played</div>
                        <div class="stat-big-val" style="color:var(--wolf-purple);">
                            <?php echo number_format($stats['total_games']); ?>
                        </div>
                        <div class="stat-sub">Total matches</div>
                    </div>
                    <div class="stat-block teal">
                        <div class="stat-icon-label">Best Score</div>
                        <div class="stat-big-val" style="color:var(--wolf-teal);">
                            <?php echo number_format($stats['best_score'] ?? 0); ?>
                        </div>
                        <div class="stat-sub">Personal record</div>
                    </div>
                    <div class="stat-block red">
                        <div class="stat-icon-label">Total Kills</div>
                        <div class="stat-big-val" style="color:var(--wolf-red);">
                            <?php echo number_format($stats['total_kills'] ?? 0); ?>
                        </div>
                        <div class="stat-sub">Bots eliminated</div>
                    </div>
                    <div class="stat-block gold">
                        <div class="stat-icon-label">Avg Score</div>
                        <div class="stat-big-val" style="color:var(--wolf-gold);">
                            <?php echo number_format($stats['avg_score'] ?? 0); ?>
                        </div>
                        <div class="stat-sub">Per game</div>
                    </div>
                    <div class="stat-block pink">
                        <div class="stat-icon-label">Best Rounds</div>
                        <div class="stat-big-val" style="color:#D4537E;">
                            <?php echo ($stats['best_rounds'] ?? 0); ?>/5
                        </div>
                        <div class="stat-sub">Most survived</div>
                    </div>
                    <div class="stat-block blue">
                        <div class="stat-icon-label">Total Rounds</div>
                        <div class="stat-big-val" style="color:#378ADD;">
                            <?php echo number_format($stats['total_rounds'] ?? 0); ?>
                        </div>
                        <div class="stat-sub">Across all games</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">⚔</div>
                    <div class="empty-text">No combat data — enter the arena first</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- match history -->
            <div class="glass-card">
                <div class="section-title" style="margin-bottom:20px;">
                    <div class="section-accent"></div>
                    <div class="section-label">Match History</div>
                </div>

                <?php if (mysqli_num_rows($history) > 0): ?>
                <div class="history-wrap">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Agent</th>
                                <th>Score</th>
                                <th>Kills</th>
                                <th>Rounds</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($history)): ?>
                            <tr>
                                <td>
                                    <span style="color:var(--wolf-text); font-weight:600;">
                                        <?php echo date('d M Y', strtotime($row['played_at'])); ?>
                                    </span>
                                    <span style="display:block; font-size:0.78rem;
                                        color:var(--wolf-muted);">
                                        <?php echo date('H:i', strtotime($row['played_at'])); ?>
                                    </span>
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
                                <td>
                                    <span style="color:#ffffff; font-weight:600;">
                                        <?php echo $row['kills']; ?>
                                    </span>
                                    <span style="color:var(--wolf-muted);
                                        font-size:0.8rem;"> kills</span>
                                </td>
                                <td>
                                    <div class="rounds-bar-wrap">
                                        <div class="rounds-bar-track">
                                            <div class="rounds-bar-fill"
                                                 style="width:<?php echo ($row['rounds_survived']/5)*100; ?>%;">
                                            </div>
                                        </div>
                                        <span class="rounds-text">
                                            <?php echo $row['rounds_survived']; ?>/5
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <div class="empty-text">No matches played yet — enter the arena</div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleUploadPanel() {
    document.getElementById('uploadPanel').classList.toggle('open');
}

function toggleUsernameEdit() {
    document.getElementById('usernameEditForm').classList.toggle('open');
}

document.getElementById('photoInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewWrap').classList.add('show');
        document.getElementById('dropZone').style.display = 'none';
        document.getElementById('uploadSubmitBtn').style.display = 'block';
    };
    reader.readAsDataURL(file);
});

const dropZone = document.getElementById('dropZone');

dropZone.addEventListener('dragover', function (e) {
    e.preventDefault();
    this.classList.add('dragover');
});

dropZone.addEventListener('dragleave', function () {
    this.classList.remove('dragover');
});

dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    this.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const input = document.getElementById('photoInput');
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    input.dispatchEvent(new Event('change'));
});
</script>
</body>
</html>