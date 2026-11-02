
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
    $room > 3
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

// Check the submitted answer.
if ((int) $answer !== (int) $puzzle['solution']) {
    respond(200, [
        'success' => true,
        'correct' => false,
        'message' => 'Not quite! Try again.'
    ]);
}

// Prevent duplicate rewards.
if (!empty($puzzle['completed'])) {
    respond(200, [
        'success' => true,
        'correct' => true,
        'coinsAwarded' => 0,
        'message' => 'This challenge has already been completed.'
    ]);
}

require_once __DIR__ . '/../config.php';

try {
    $pdo->beginTransaction();

    // Lock the player's account while saving progress.
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

    // Check saved progress again inside the transaction.
    $stmt = $pdo->prepare(
        'SELECT room_number
         FROM room_progress
         WHERE user_id = ? AND difficulty = ?
           AND room_number BETWEEN 1 AND 3'
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
            'message' => 'This challenge has already been completed.'
        ]);
    }

    // Enforce the room unlocking sequence.
    if ($room > 1 && !in_array($room - 1, $completedRooms, true)) {
        $pdo->rollBack();

        respond(403, [
            'success' => false,
            'message' => 'Complete the previous room first.'
        ]);
    }

    // Save the completed room.
    // Room 1 = Wooden Key 1
    // Room 2 = Wooden Key 2
    // Room 3 = Final treasure chest opened
    $stmt = $pdo->prepare(
        'INSERT INTO room_progress (user_id, difficulty, room_number)
         VALUES (?, ?, ?)'
    );

    $stmt->execute([$userId, $difficulty, $room]);

    $pdo->commit();

    $_SESSION['ghost_heist_puzzle']['completed'] = true;

    if ($room === 1) {
        respond(200, [
            'success' => true,
            'correct' => true,
            'coinsAwarded' => 0,
            'rewardType' => 'wooden_key',
            'keyNumber' => 1,
            'keysEarned' => 1,
            'gameCompleted' => false,
            'room' => 1,
            'difficulty' => $difficulty,
            'message' => 'Amazing! You solved the puzzle and collected Wooden Key 1!'
        ]);
    }

    if ($room === 2) {
        respond(200, [
            'success' => true,
            'correct' => true,
            'coinsAwarded' => 0,
            'rewardType' => 'wooden_key',
            'keyNumber' => 2,
            'keysEarned' => 2,
            'gameCompleted' => false,
            'room' => 2,
            'difficulty' => $difficulty,
            'message' => 'Excellent! You collected Wooden Key 2. You now have both keys!'
        ]);
    }

    // Final Room: treasure chest successfully opened.
    respond(200, [
        'success' => true,
        'correct' => true,
        'coinsAwarded' => 0,
        'rewardType' => 'treasure',
        'keysEarned' => 2,
        'gameCompleted' => true,
        'room' => 3,
        'difficulty' => $difficulty,
        'message' => 'Congratulations! You used both wooden keys, solved the final puzzle, and unlocked the treasure chest. You completed Ghost Heist!'
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
