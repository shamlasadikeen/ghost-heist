
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): void
{
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
$userId = (int) $_SESSION['user_id'];

if (
    !is_array($puzzle) ||
    !isset(
        $puzzle['solution'],
        $puzzle['user_id'],
        $puzzle['difficulty'],
        $puzzle['room']
    ) ||
    (int) $puzzle['user_id'] !== $userId
) {
    respond(400, [
        'success' => false,
        'message' => 'No valid active puzzle. Please load your room again.'
    ]);
}

$difficulty = $puzzle['difficulty'];
$room = (int) $puzzle['room'];

if (
    !in_array($difficulty, ['beginner', 'intermediate', 'hard'], true) ||
    $room < 1 ||
    $room > 5
) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid puzzle details.'
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
        'message' => 'This room has already been completed.'
    ]);
}

require_once __DIR__ . '/../config.php';

try {
    $pdo->beginTransaction();

    // Lock this player's account row while awarding the reward.
    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$userId]);

    if (!$stmt->fetch()) {
        $pdo->rollBack();

        respond(401, [
            'success' => false,
            'message' => 'Your account could not be verified.'
        ]);
    }

    // Check progress again inside the transaction.
    $stmt = $pdo->prepare(
        'SELECT room_number
         FROM room_progress
         WHERE user_id = ? AND difficulty = ?'
    );
    $stmt->execute([$userId, $difficulty]);

    $completedRooms = array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    if (in_array($room, $completedRooms, true)) {
        $pdo->rollBack();

        $_SESSION['ghost_heist_puzzle']['completed'] = true;

        respond(200, [
            'success' => true,
            'correct' => true,
            'coinsAwarded' => 0,
            'message' => 'This room has already been completed.'
        ]);
    }

    if ($room > 1 && !in_array($room - 1, $completedRooms, true)) {
        $pdo->rollBack();

        respond(403, [
            'success' => false,
            'message' => 'Complete the previous room first.'
        ]);
    }

    // The unique primary key prevents duplicate room rewards.
    $stmt = $pdo->prepare(
        'INSERT INTO room_progress (user_id, difficulty, room_number)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $difficulty, $room]);

    $stmt = $pdo->prepare(
        'UPDATE users SET coins = coins + 100 WHERE id = ?'
    );
    $stmt->execute([$userId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Could not save the coin reward.');
    }

    $pdo->commit();

    $_SESSION['ghost_heist_puzzle']['completed'] = true;

    respond(200, [
        'success' => true,
        'correct' => true,
        'coinsAwarded' => 100,
        'room' => $room,
        'difficulty' => $difficulty,
        'message' => 'Room completed! You earned 100 coins!'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Ghost Heist answer error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Could not save your progress. Please try again.'
    ]);
}
