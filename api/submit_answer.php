
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Please log in to play.'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Use POST to submit an answer.'
    ]);
}

$puzzle = $_SESSION['ghost_heist_puzzle'] ?? null;

if (!is_array($puzzle) || !isset($puzzle['solution'])) {
    respond(400, [
        'success' => false,
        'message' => 'No active puzzle. Please load a puzzle first.'
    ]);
}

$answer = trim($_POST['answer'] ?? '');

if ($answer === '' || !preg_match('/^\d{1,3}$/', $answer)) {
    respond(400, [
        'success' => false,
        'message' => 'Enter a valid numeric answer.'
    ]);
}

if ((int) $answer !== (int) $puzzle['solution']) {
    respond(200, [
        'success' => true,
        'correct' => false,
        'message' => 'Not quite! Try again.'
    ]);
}

if (!empty($puzzle['completed'])) {
    respond(200, [
        'success' => true,
        'correct' => true,
        'coinsAwarded' => 0,
        'message' => 'You already solved this puzzle!'
    ]);
}

require_once __DIR__ . '/../config.php';

try {
    $stmt = $pdo->prepare(
        'UPDATE users SET coins = coins + 100 WHERE id = ?'
    );
    $stmt->execute([(int) $_SESSION['user_id']]);

    if ($stmt->rowCount() !== 1) {
        respond(500, [
            'success' => false,
            'message' => 'Could not save your reward. Please try again.'
        ]);
    }

    $_SESSION['ghost_heist_puzzle']['completed'] = true;

    respond(200, [
        'success' => true,
        'correct' => true,
        'coinsAwarded' => 100,
        'message' => 'Correct answer! You earned 100 coins!'
    ]);
} catch (PDOException $e) {
    error_log('Ghost Heist answer error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Could not save your reward. Please try again.'
    ]);
}
