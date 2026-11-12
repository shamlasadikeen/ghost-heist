
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

// Only logged-in game users can search sounds.
if (!isset($_SESSION['user_id'])) {
    respond(401, [
        'success' => false,
        'message' => 'Please log in first.'
    ]);
}

$configPath = dirname(__DIR__) . '/config/freesound.php';

if (!file_exists($configPath)) {
    respond(500, [
        'success' => false,
        'message' => 'Freesound configuration was not found.'
    ]);
}

$config = require $configPath;
$apiKey = $config['api_key'] ?? '';

if ($apiKey === '') {
    respond(500, [
        'success' => false,
        'message' => 'Freesound API key is missing.'
    ]);
}

$query = trim($_GET['query'] ?? 'cartoon pop');

if ($query === '' || strlen($query) > 100) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid search query.'
    ]);
}

$url = 'https://freesound.org/apiv2/search/?' . http_build_query([
    'query' => $query,
    'page_size' => 10,
    'fields' => 'id,name,username,license,previews,tags',
]);

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Token ' . $apiKey,
        'Accept: application/json'
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($response === false) {
    respond(502, [
        'success' => false,
        'message' => 'Could not connect to Freesound.',
        'error' => $error
    ]);
}

if ($status !== 200) {
    respond($status, [
        'success' => false,
        'message' => 'Freesound returned an error.',
        'details' => json_decode($response, true)
    ]);
}

$data = json_decode($response, true);

respond(200, [
    'success' => true,
    'results' => $data['results'] ?? []
]);
