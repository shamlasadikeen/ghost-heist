
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

$difficulty = $_GET['difficulty'] ?? 'beginner';
$room = filter_var(
    $_GET['room'] ?? 1,
    FILTER_VALIDATE_INT
);

$validDifficulties = ['beginner', 'intermediate', 'hard'];

if (!in_array($difficulty, $validDifficulties, true)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid difficulty level.'
    ]);
}

if ($room === false || $room < 1 || $room > 3) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid room number.'
    ]);
}

require_once __DIR__ . '/../config.php';

$userId = (int) $_SESSION['user_id'];

try {
    // Get completed rooms for this player and difficulty.
    $stmt = $pdo->prepare(
        'SELECT room_number
         FROM room_progress_v2
         WHERE user_id = ? AND difficulty = ?
           AND room_number BETWEEN 1 AND 3'
    );

    $stmt->execute([$userId, $difficulty]);

    $completedRooms = array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    // Do not allow replaying a completed room.
    if (in_array($room, $completedRooms, true)) {
        respond(409, [
            'success' => false,
            'message' => $room === 3
                ? 'You have already opened the treasure chest!'
                : 'You have already completed this room and collected its key.'
        ]);
    }

    // Each room requires completion of the previous room.
    if ($room > 1 && !in_array($room - 1, $completedRooms, true)) {
        respond(403, [
            'success' => false,
            'message' => 'This room is locked. Complete the previous room first.'
        ]);
    }

    // Load a banana puzzle.
    $apiUrl = 'https://marcconrad.com/uob/banana/api.php?out=json';

    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'ignore_errors' => false
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        throw new RuntimeException('Puzzle service unavailable.');
    }

    $puzzle = json_decode($response, true);

    if (
        !is_array($puzzle) ||
        !isset($puzzle['question'], $puzzle['solution']) ||
        !filter_var($puzzle['question'], FILTER_VALIDATE_URL) ||
        parse_url($puzzle['question'], PHP_URL_SCHEME) !== 'https' ||
        parse_url($puzzle['question'], PHP_URL_HOST) !== 'www.sanfoh.com' ||
        !is_numeric($puzzle['solution'])
    ) {
        throw new RuntimeException('Invalid puzzle response.');
    }

    // Store the puzzle against the logged-in player and selected room.
    $_SESSION['ghost_heist_puzzle'] = [
        'question' => $puzzle['question'],
        'solution' => (int) $puzzle['solution'],
        'user_id' => $userId,
        'difficulty' => $difficulty,
        'room' => $room,
        'completed' => false
    ];

    respond(200, [
        'success' => true,
        'question' => $puzzle['question'],
        'difficulty' => $difficulty,
        'room' => $room,
        'totalRooms' => 3,
        'puzzleType' => $room === 3 ? 'final_treasure' : 'room_challenge'
    ]);

} catch (Throwable $e) {
    error_log('Ghost Heist puzzle API error: ' . $e->getMessage());

    respond(502, [
        'success' => false,
        'message' => 'Could not load a puzzle. Please try again.'
    ]);
}
