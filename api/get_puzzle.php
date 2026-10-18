
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to play.'
    ]);
    exit;
}

$difficulty = $_GET['difficulty'] ?? 'beginner';

$difficulties = [
    'beginner' => ['rooms' => 5, 'coins' => 100],
    'intermediate' => ['rooms' => 5, 'coins' => 100],
    'hard' => ['rooms' => 5, 'coins' => 100]
];

if (!isset($difficulties[$difficulty])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid difficulty level.'
    ]);
    exit;
}

$apiUrl = 'https://marcconrad.com/uob/banana/api.php?out=json';

try {
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

    $_SESSION['ghost_heist_puzzle'] = [
        'question' => $puzzle['question'],
        'solution' => (int) $puzzle['solution'],
        'difficulty' => $difficulty,
        'room' => 1
    ];

    echo json_encode([
        'success' => true,
        'question' => $puzzle['question'],
        'difficulty' => $difficulty,
        'room' => 1,
        'totalRooms' => $difficulties[$difficulty]['rooms']
    ]);
} catch (Throwable $e) {
    error_log('Ghost Heist puzzle API error: ' . $e->getMessage());

    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'Could not load a puzzle. Please try again.'
    ]);
}
