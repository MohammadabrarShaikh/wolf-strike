<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$user_id        = (int) $_SESSION['user_id'];
$agent          = mysqli_real_escape_string($conn, $_POST['agent'] ?? '');
$score          = (int) ($_POST['score'] ?? 0);
$kills          = (int) ($_POST['kills'] ?? 0);
$rounds_survived = (int) ($_POST['rounds_survived'] ?? 0);

$valid_agents = ['Scout', 'Hunter', 'Alpha', 'Phantom'];
if (!in_array($agent, $valid_agents)) {
    echo json_encode(['success' => false, 'message' => 'Invalid agent.']);
    exit();
}

if ($score < 0 || $kills < 0 || $rounds_survived < 0 || $rounds_survived > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid score data.']);
    exit();
}

$insert = mysqli_query($conn, "
    INSERT INTO scores (user_id, score, agent, rounds_survived, kills)
    VALUES ($user_id, $score, '$agent', $rounds_survived, $kills)
");

if ($insert) {
    echo json_encode(['success' => true, 'message' => 'Score saved.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>