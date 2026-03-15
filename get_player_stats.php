<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid player.']);
    exit();
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT username, vip_status, profile_photo, created_at
    FROM users WHERE id=$id AND status='active'
"));

if (!$user) {
    echo json_encode(['error' => 'Player not found.']);
    exit();
}

$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)             AS total_games,
        MAX(score)           AS best_score,
        SUM(kills)           AS total_kills,
        ROUND(AVG(score))    AS avg_score,
        MAX(rounds_survived) AS best_rounds,
        SUM(rounds_survived) AS total_rounds
    FROM scores WHERE user_id=$id
"));

$best_agent_row = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT agent, COUNT(*) AS cnt
    FROM scores WHERE user_id=$id
    GROUP BY agent ORDER BY cnt DESC LIMIT 1
"));

$has_photo = !empty($user['profile_photo']) &&
             file_exists(__DIR__ . '/assets/uploads/profiles/' . $user['profile_photo']);

echo json_encode([
    'username'     => $user['username'],
    'vip_status'   => (int)$user['vip_status'],
    'since'        => date('M Y', strtotime($user['created_at'])),
    'best_score'   => $stats['best_score']   ?? 0,
    'total_kills'  => $stats['total_kills']  ?? 0,
    'total_games'  => $stats['total_games']  ?? 0,
    'avg_score'    => $stats['avg_score']    ?? 0,
    'best_rounds'  => $stats['best_rounds']  ?? 0,
    'total_rounds' => $stats['total_rounds'] ?? 0,
    'best_agent'   => $best_agent_row['agent'] ?? null,
]);
?>