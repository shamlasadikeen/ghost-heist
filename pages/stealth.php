<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$userId = (int) $_SESSION['user_id'];

$difficulty = $_GET['difficulty'] ?? 'beginner';

if (!in_array($difficulty, ['beginner', 'intermediate', 'hard'], true)) {
    $difficulty = 'beginner';
}

$room = filter_var(
    $_GET['room'] ?? 1,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 3]]
);

if ($room === false) {
    $room = 1;
}

$roomNames = [
    1 => 'The Living Room',
    2 => 'The Kitchen',
    3 => 'The Treasure Room'
];

try {
    $stmt = $pdo->prepare(
        'SELECT room_number
         FROM room_progress_v2
         WHERE user_id = ? AND difficulty = ?'
    );

    $stmt->execute([$userId, $difficulty]);

    $completedRooms = array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    // Prevent reopening a completed room.
    if (in_array($room, $completedRooms, true)) {
        header('Location: rooms.php?difficulty=' . urlencode($difficulty));
        exit;
    }

    // Every earlier room must be completed first.
    for ($previousRoom = 1; $previousRoom < $room; $previousRoom++) {
        if (!in_array($previousRoom, $completedRooms, true)) {
            header('Location: rooms.php?difficulty=' . urlencode($difficulty));
            exit;
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    exit('Unable to verify room progress. Please try again later.');
}

$roomName = $roomNames[$room];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#f5f0e8">

<title>Ghost Heist | <?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?></title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: #f5f0e8;
    color: #29243b;
    font-family: 'Trebuchet MS', Arial, sans-serif;
}

header {
    padding: 15px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    box-shadow: 0 3px 12px rgba(0,0,0,.07);
}

.logo {
    font-size: 25px;
    font-weight: 900;
    color: #7354d6;
    letter-spacing: 1px;
}

header a {
    color: #7354d6;
    text-decoration: none;
    font-weight: bold;
    background: #eee9ff;
    padding: 10px 16px;
    border-radius: 10px;
}

main {
    width: 98%;
    max-width: none;
    margin: 12px auto;
}

.heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.heading h1 {
    margin: 0 0 7px;
    font-size: 30px;
    color: #302748;
}

.heading p {
    margin: 0;
    color: #777184;
}

.difficulty {
    background: #e9e2ff;
    color: #6746c7;
    padding: 10px 16px;
    border-radius: 20px;
    font-weight: bold;
    white-space: nowrap;
}

.game-container {
    padding: 13px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(45,35,65,.10);
    border: 1px solid #eae5f0;
}

canvas {
    display: block;
    width: 100%;
    height: auto;
    border-radius: 13px;
    background: #c99c65;
    outline: none;
    cursor: crosshair;
    image-rendering: pixelated;
}

.controls {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 17px 7px 5px;
    color: #655c72;
    font-size: 14px;
}

kbd {
    background: #eee9f8;
    color: #6044b7;
    padding: 5px 9px;
    border-radius: 5px;
    font-weight: bold;
    box-shadow: 0 2px 0 #d6cbed;
}

#message {
    margin-top: 17px;
    padding: 15px;
    background: #eee9ff;
    border: 1px solid #ddd2ff;
    border-radius: 12px;
    color: #6044b7;
    font-weight: bold;
    text-align: center;
    min-height: 48px;
}

#interactionPrompt {
    display: none;
    margin-top: 10px;
    padding: 12px;
    background: #fff3bf;
    border: 1px solid #f2d66b;
    border-radius: 12px;
    color: #755600;
    font-weight: bold;
    text-align: center;
}

#interactButton {
    display: none;
    margin: 10px auto;
    padding: 12px 25px;
    border: none;
    border-radius: 10px;
    background: #7354d6;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

#interactButton:hover {
    background: #6042bd;
}

#keyInventory {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-top: 15px;
    padding: 15px;
    background: #fff;
    border: 1px solid #eae5f0;
    border-radius: 12px;
}

.key-item {
    padding: 8px 12px;
    background: #f0eafa;
    color: #6044b7;
    border-radius: 8px;
    font-weight: bold;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    inset: 0;
    background: rgba(20, 15, 35, 0.8);
    justify-content: center;
    align-items: center;
    padding: 15px;
}

.modal-content {
    width: min(500px, 100%);
    max-height: 90vh;
    overflow-y: auto;
    padding: 25px;
    border-radius: 18px;
    background: white;
    text-align: center;
}

.modal-content h2 {
    color: #7354d6;
}

#puzzleImage {
    display: block;
    width: 100%;
    max-width: 400px;
    max-height: 350px;
    object-fit: contain;
    margin: 15px auto;
    border-radius: 10px;
}

#answerInput {
    width: 100%;
    padding: 13px;
    margin: 12px 0;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 17px;
}

#submitAnswer,
#continueButton {
    padding: 12px 22px;
    border: none;
    border-radius: 9px;
    background: #7354d6;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

#continueButton {
    margin-top: 12px;
}

#puzzleFeedback {
    font-weight: bold;
    line-height: 1.5;
    min-height: 24px;}

.treasure-celebration {
    position: relative;
    overflow: hidden;
    padding: 20px 10px;
    margin: 15px 0;
    background: linear-gradient(135deg, #fff5c2, #ffe3a0, #fff8dc);
    border: 3px solid #ffc83d;
    border-radius: 20px;
    text-align: center;
}

.treasure-celebration[hidden] {
    display: none;
}

.treasure-bag {
    font-size: 105px;
    line-height: 1.3;
    animation: treasureBounce 0.8s ease-in-out infinite alternate;
}

.gold-coins {
    font-size: 30px;
    animation: coinBounce 0.7s ease-in-out infinite alternate;
}

.gold-amount {
    color: #bd7600;
    font-size: 36px;
    font-weight: 900;
    animation: goldPop 0.6s ease-out;
}

.treasure-message {
    color: #754300;
    font-size: 22px;
    font-weight: 900;
}

.treasure-submessage {
    color: #785d25;
    font-weight: bold;
}

.confetti-burst span {
    display: inline-block;
    margin: 5px;
    font-size: 25px;
    animation: confettiFloat 1.5s ease-in-out infinite alternate;
}

.confetti-burst span:nth-child(even) {
    animation-delay: 0.4s;
}

@keyframes treasureBounce {
    from { transform: translateY(0) rotate(-4deg); }
    to { transform: translateY(-14px) rotate(4deg); }
}

@keyframes coinBounce {
    from { transform: translateY(0); }
    to { transform: translateY(-9px); }
}

@keyframes goldPop {
    from { transform: scale(0.4); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

@keyframes confettiFloat {
    from { transform: translateY(12px) rotate(-15deg); }
    to { transform: translateY(-15px) rotate(25deg); }
}


#puzzleModal button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

footer {
    margin: 25px 0;
    text-align: center;
    color: #9992a3;
    font-size: 13px;
}

@media (max-width: 600px) {
    .heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .heading h1 {
        font-size: 24px;
    }

    .controls {
        flex-direction: column;
    }
}


/* FULL-SCREEN DESKTOP GAME LAYOUT */

html,
body {
    width: 100%;
    height: 100%;
    margin: 0;
    overflow: hidden;
}

body {
    height: 100vh;
    display: flex;
    flex-direction: column;
}

header {
    flex: 0 0 auto;
}

main {
    width: 100%;
    max-width: none;
    height: auto;
    min-height: 0;
    flex: 1 1 auto;
    box-sizing: border-box;
    margin: 0;
    padding: 8px 12px;
    display: flex;
    flex-direction: column;
    gap: 7px;
    overflow: hidden;
}

.heading {
    flex: 0 0 auto;
    margin: 0;
}

.heading h1 {
    margin-top: 0;
    margin-bottom: 3px;
}

.heading p {
    margin-top: 3px;
    margin-bottom: 3px;
}

.game-container {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    padding: 8px;
    border-radius: 12px;
}

canvas {
    display: block;
    flex: 1 1 auto;
    min-height: 0;
    width: 100%;
    height: 100%;
    border-radius: 10px;
    object-fit: fill;
}

.controls {
    flex: 0 0 auto;
    padding: 8px 7px 3px;
    gap: 8px;
    font-size: 13px;
}

#message {
    flex: 0 0 auto;
    min-height: 0;
    margin: 0;
    padding: 8px 12px;
}

#keyInventory {
    flex: 0 0 auto;
    margin: 0;
    padding: 8px 12px;
    gap: 8px;
}

.key-item {
    padding: 6px 10px;
}

#interactionPrompt {
    margin: 0;
    padding: 8px;
}

#interactButton {
    margin: 0 auto;
    padding: 8px 20px;
}

footer {
    flex: 0 0 auto;
    margin: 0;
    padding: 3px 0;
    font-size: 11px;
}

/* Keep the puzzle modal working above the game */
.modal {
    z-index: 1000;
}


/* =====================================
   GHOST HEIST - IMMERSIVE FULL SCREEN
===================================== */

html,
body {
    width: 100%;
    height: 100%;
    margin: 0;
    overflow: hidden;
}

body {
    display: block;
    min-height: 100%;
    background: #17131f;
}

/* Remove the separate website header */
header {
    display: none;
}

/* Use the entire browser window */
main {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    max-width: none;
    margin: 0;
    padding: 0;
    display: block;
    overflow: hidden;
}

/* Room occupies the entire screen */
.game-container {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    margin: 0;
    border: 0;
    border-radius: 0;
    box-shadow: none;
    background: #17131f;
    overflow: hidden;
}

/* Preserve room proportions and fill the screen */
#gameCanvas {
    position: absolute;
    inset: 0;
    display: block;
    width: 100%;
    height: 100%;
    border-radius: 0;
    object-fit: cover;
    image-rendering: auto;
}

/* Room title displayed over the room */
.heading {
    position: absolute;
    z-index: 10;
    top: 18px;
    left: 20px;
    margin: 0;
    padding: 12px 18px;
    background: rgba(25, 20, 35, 0.78);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px;
    color: white;
    backdrop-filter: blur(8px);
}

.heading h1 {
    margin: 0;
    color: white;
    font-size: 22px;
}

.heading p {
    display: none;
}

.difficulty {
    margin-left: 12px;
    padding: 7px 12px;
    color: white;
    background: rgba(115,84,214,0.85);
}

/* Controls float over the room */
.controls {
    position: fixed;
    z-index: 10;
    top: 18px;
    right: 20px;
    bottom: auto;
    left: auto;
    padding: 12px 15px;
    margin: 0;
    display: flex;
    gap: 15px;
    color: white;
    background: rgba(25,20,35,0.78);
    border-radius: 12px;
    backdrop-filter: blur(8px);
}

/* No permanent message or key inventory panels */
#message,
#keyInventory {
    display: none !important;
}

/* Interaction prompt appears only when needed */
#interactionPrompt {
    position: fixed;
    z-index: 20;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    width: max-content;
    max-width: 90%;
    margin: 0;
}

/* Interaction button floats over the room */
#interactButton {
    position: fixed;
    z-index: 21;
    bottom: 75px;
    left: 50%;
    transform: translateX(-50%);
    margin: 0;
}

/* Remove footer space */
footer {
    display: none;
}

/* Puzzle modal remains unchanged */
.modal {
    z-index: 1000;
}

/* =====================================
   GAME TIMER
===================================== */

#gameTimer {
    position: fixed;
    z-index: 30;
    top: 105px;
    right: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    padding: 10px 20px;
    min-width: 125px;
    color: white;
    background: rgba(25, 20, 35, 0.88);
    border: 2px solid #e6b84c;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

#gameTimer span {
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 2px;
}

#timerValue {
    font-size: 27px;
    color: #ffe078;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

/* =====================================
   GAME OVER SCREEN
===================================== */

#gameOverScreen {
    display: none;
    position: fixed;
    z-index: 2000;
    inset: 0;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(12, 8, 20, 0.88);
    backdrop-filter: blur(7px);
}

.game-over-content {
    width: min(420px, 100%);
    padding: 35px 25px;
    text-align: center;
    color: white;
    background: #282035;
    border: 2px solid #a94c55;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.5);
}

.game-over-icon {
    font-size: 55px;
}

.game-over-content h1 {
    margin: 12px 0;
    color: #ff737d;
    font-size: 36px;
    letter-spacing: 3px;
}

.game-over-content p {
    color: #ded6e8;
    line-height: 1.6;
}

#tryAgainButton {
    margin-top: 20px;
    padding: 14px 30px;
    border: none;
    border-radius: 10px;
    color: white;
    background: #7354d6;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

#tryAgainButton:hover {
    background: #8b70eb;
}

/* Ghost Heist - Room Frame Redesign */
.game-container {
    padding: 18px !important;
    border: 8px solid #8b78d8 !important;
    border-radius: 26px !important;
    background: linear-gradient(135deg, #b9e8ff, #e8caff) !important;
    box-sizing: border-box;
    box-shadow:
        0 0 0 5px #f5c4e8,
        0 12px 30px rgba(55, 35, 100, 0.25) !important;
}

#gameCanvas {
    inset: 18px !important;
    width: calc(100% - 36px) !important;
    height: calc(100% - 36px) !important;
    border: 4px solid #ffffff;
    border-radius: 14px;
    box-sizing: border-box;
}

</style>
</head>

<body>

<header>
    <div class="logo">GHOST HEIST</div>
    <a href="rooms.php">&larr; Room Map</a>
</header>

<main>

    <section class="heading">
        <div>
            <h1><?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Explore the room, avoid danger and find the sparkling object.</p>
        </div>

        <span class="difficulty">
            <?= htmlspecialchars(ucfirst($difficulty), ENT_QUOTES, 'UTF-8') ?> Mode
        </span>
    </section>

    <section class="game-container">

 <div class="controls">
            <span>
                Move:
                <kbd>W</kbd> <kbd>A</kbd> <kbd>S</kbd> <kbd>D</kbd>
                or arrow keys
            </span>

            <span>Interact: <kbd>E</kbd></span>
        </div>

        <div id="gameTimer">
            <span>TIME LEFT</span>
            <strong id="timerValue">00:45</strong>
        </div>

        <canvas
            id="gameCanvas"
            width="900"
            height="600"
            tabindex="0"
            aria-label="Ghost Heist game. Use WASD or arrow keys to move."
        ></canvas>

        <div class="controls">
            <span>
                Move:
                <kbd>W</kbd> <kbd>A</kbd> <kbd>S</kbd> <kbd>D</kbd>
                or arrow keys
            </span>

            <span>Interact: <kbd>E</kbd></span>
        </div>

    </section>

    <div id="message" role="status" aria-live="polite">
        Explore the room and find the sparkling object!
    </div>

    <div id="keyInventory">
    <strong>YOUR KEYS:</strong>
    <span id="key1" class="key-item">&#128273; Wooden Key 1</span>
    <span id="key2" class="key-item">&#128273; Wooden Key 2</span>
</div>

    <div id="interactionPrompt">
        ✦ PRESS E OR CLICK INTERACT ✦
    </div>

    <button id="interactButton" type="button">INTERACT</button>

    <div id="puzzleModal" class="modal">
        <div class="modal-content">

            <h2 id="puzzleTitle">Solve the Banana Puzzle!</h2>

            <p id="puzzleDescription">
                Find the missing number to continue your adventure.
            </p>

            <img id="puzzleImage" src="" alt="Banana puzzle">

            <form id="answerForm">

                <input
                    type="number"
                    id="answerInput"
                    name="answer"
                    placeholder="Enter your answer"
                    required
                    min="0"
                    max="999"
                >

                <button type="submit" id="submitAnswer">
                    Submit Answer
                </button>

            </form>

            <p id="puzzleFeedback" role="status" aria-live="polite"></p>

            <div id="treasureCelebration" class="treasure-celebration" hidden>

    <div class="confetti-burst" aria-hidden="true">
        <span>🎉</span>
        <span>✨</span>
        <span>🎊</span>
        <span>⭐</span>
        <span>🎉</span>
        <span>✨</span>
        <span>🎊</span>
        <span>⭐</span>
    </div>

    <div class="treasure-bag" aria-hidden="true">
        <span>💰</span>
    </div>

    <div class="gold-coins" aria-hidden="true">
        🪙 🪙 🪙 🪙 🪙
    </div>

    <h2 class="gold-amount">+1,000 GOLD!</h2>

    <p class="treasure-message">
        YOU FOUND THE TREASURE!
    </p>

    <p class="treasure-submessage">
        The treasure is yours! Amazing work!
    </p>

</div>

            <button
                id="continueButton"
                type="button"
                style="display:none;"
            >
                Continue
            </button>

        </div>
    </div>

      <div id="gameOverScreen">
        <div class="game-over-content">
            <div class="game-over-icon">💀</div>
            <h1>GAME OVER</h1>
            <p id="gameOverReason">
                Time is up! You failed to find the hidden object.
            </p>
            <button type="button" id="tryAgainButton">
                TRY AGAIN
            </button>
        </div>
    </div>

    <footer>GHOST HEIST | Explore carefully. Stay out of sight.</footer>

</main>

<script>

/* ==========================================
   GAME INITIALISATION
========================================== */

const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');

const message = document.getElementById('message');
const interactionPrompt = document.getElementById('interactionPrompt');
const interactButton = document.getElementById('interactButton');

const puzzleModal = document.getElementById('puzzleModal');
const puzzleImage = document.getElementById('puzzleImage');
const puzzleTitle = document.getElementById('puzzleTitle');
const puzzleDescription = document.getElementById('puzzleDescription');
const answerForm = document.getElementById('answerForm');
const answerInput = document.getElementById('answerInput');
const submitAnswer = document.getElementById('submitAnswer');
const puzzleFeedback = document.getElementById('puzzleFeedback');
const continueButton = document.getElementById('continueButton');
const gameOverScreen = document.getElementById('gameOverScreen');
const gameOverReason = document.getElementById('gameOverReason');
const tryAgainButton = document.getElementById('tryAgainButton');

let gameEnded = false;

const timerValue = document.getElementById('timerValue');
let timeRemaining = 45;

function endGame(reason) {
    if (gameEnded) return;

    gameEnded = true;
    gameOverReason.textContent = reason;
    gameOverScreen.style.display = 'flex';
}

tryAgainButton.addEventListener('click', function() {
    window.location.reload();
});

const currentRoom = <?= (int)$room ?>;
const currentDifficulty = <?= json_encode($difficulty) ?>;

let puzzleLoading = false;
let challengeCompleted = false;
let puzzleOpened = false;

const keys = {};


/* ==========================================
   KENNEY WOODEN FLOOR TILES
========================================== */

const indoorTiles = new Image();

let tilesLoaded = false;

const TILE_SIZE = 16;
const TILE_STEP = 17;
const TILE_SCALE = 2;
const DRAW_TILE_SIZE = 32;

indoorTiles.onload = function() {
    tilesLoaded = true;
    console.log('Indoor tileset loaded successfully.');
};

indoorTiles.onerror = function() {
    tilesLoaded = false;
    console.error('Indoor tileset could not be loaded. Using fallback floor.');
};

indoorTiles.src =
    '../assets/tilesets/kenney-indoors/Tilesheets/roguelikeIndoor_transparent.png';


function drawTile(column, row, x, y) {

    if (!tilesLoaded) return;

    const sourceX = column * TILE_STEP;
    const sourceY = row * TILE_STEP;

    if (
        sourceX + TILE_SIZE > indoorTiles.naturalWidth ||
        sourceY + TILE_SIZE > indoorTiles.naturalHeight
    ) {
        return;
    }

    ctx.drawImage(
        indoorTiles,
        sourceX,
        sourceY,
        TILE_SIZE,
        TILE_SIZE,
        x,
        y,
        DRAW_TILE_SIZE,
        DRAW_TILE_SIZE
    );
}


/* ==========================================
   PLAYER AND ROGUE SPRITES
========================================== */

const player = {
    x: 110,
    y: 480,
    radius: 14,
    speed: 230,
    direction: 'up',
    isMoving: false
};

const granny = {
    x: 430,
    y: 455,
    speed: 65,
    minX: 400,
    maxX: 620,
    direction: -1,
    angle: Math.PI,
    visionRange: 190,
    visionAngle: Math.PI / 5
};

const grannyPatrolPoints = [
    { x: 430, y: 455 },
    { x: 520, y: 480 },
    { x: 700, y: 480 },
    { x: 700, y: 250 },
    { x: 350, y: 250 },
    { x: 350, y: 455 },
    { x: 430, y: 455 }
];

let grannyPatrolIndex = 1;

// Kitchen-only patrol route.
// The Postie walks around the furniture.
const kitchenGrannyPatrolPoints = [{ x: 430, y: 455 }, { x: 552, y: 455 }, { x: 552, y: 240 }, { x: 220, y: 240 }, { x: 220, y: 455 }, { x: 430, y: 455 }];
// Treasure Room route: go around the right side of the chest.
const treasureGrannyPatrolPoints = [
    { x: 430, y: 455 },
    { x: 520, y: 480 },
    { x: 790, y: 480 },
    { x: 790, y: 240 },
    { x: 350, y: 240 },
    { x: 350, y: 455 },
    { x: 430, y: 455 }
];

// Granny's animated character sprites
const grannySprites = {
    down: [],
    up: [],
    left: [],
    right: []
};

['down', 'up', 'left', 'right'].forEach(direction => {
    [1, 2].forEach(frame => {
        const sprite = new Image();

        sprite.src =
            `../assets/characters/player-postie/${direction}/frame_0${frame}.png`;

        grannySprites[direction].push(sprite);
    });
});

const spritePath =
    '../assets/characters/topdown-roster/topdown-roster-lite/sprites/';

const playerSprites = {
    idle: new Image(),
    up: new Image(),
    down: new Image(),
    left: new Image(),
    right: new Image()
};

playerSprites.idle.src = spritePath + 'rogue_idle_strip.png';
playerSprites.up.src = spritePath + 'rogue_walk_up_strip.png';
playerSprites.down.src = spritePath + 'rogue_walk_down_strip.png';
playerSprites.left.src = spritePath + 'rogue_walk_left_strip.png';
playerSprites.right.src = spritePath + 'rogue_walk_right_strip.png';

Object.entries(playerSprites).forEach(([name, sprite]) => {

    sprite.onload = () => console.log('Loaded character:', name);

    sprite.onerror = () => console.error('Character sprite failed:', name);

});


/* ==========================================
   FLOOR GENERATION
========================================== */

const GRID_COLUMNS = Math.ceil(canvas.width / DRAW_TILE_SIZE);
const GRID_ROWS = Math.ceil(canvas.height / DRAW_TILE_SIZE);

const floorMap = [];

function getRandomFloorColumn() {

    const random = Math.random();

    if (random < 0.70) return 4;
    if (random < 0.90) return 5;

    return 6;
}

function generateFloorMap() {

    for (let row = 0; row < GRID_ROWS; row++) {

        const floorRow = [];

        for (let column = 0; column < GRID_COLUMNS; column++) {
            floorRow.push(getRandomFloorColumn());
        }

        floorMap.push(floorRow);
    }

}

generateFloorMap();


function drawFallbackFloor() {

    // Used only if the Kenney image cannot load.
    ctx.fillStyle = '#a87546';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    for (let y = 0; y < canvas.height; y += 32) {

        const offset = (Math.floor(y / 32) % 2) * 16;

        for (let x = -16; x < canvas.width; x += 64) {

            const plankX = x + offset;

            ctx.fillStyle =
                (Math.floor(x / 64) + Math.floor(y / 32)) % 2 === 0
                    ? '#bd8957'
                    : '#ad794b';

            ctx.fillRect(plankX, y, 64, 32);

            ctx.strokeStyle = 'rgba(72, 42, 22, 0.28)';
            ctx.lineWidth = 1;
            ctx.strokeRect(plankX, y, 64, 32);

            ctx.strokeStyle = 'rgba(255, 220, 170, 0.18)';
            ctx.beginPath();
            ctx.moveTo(plankX + 7, y + 8);
            ctx.lineTo(plankX + 55, y + 8);
            ctx.stroke();
        }
    }
}


function drawFloorLayer() {

    // Soft ivory floor.
    ctx.fillStyle = '#fbfaf6';
    ctx.fillRect(32, 32, 836, 536);

    // Subtle pale floor tiles.
    const tileSize = 48;

    for (let y = 96; y < 536; y += tileSize) {
        for (let x = 64; x < 836; x += tileSize) {

            const alternate =
                ((Math.floor(x / tileSize) +
                  Math.floor(y / tileSize)) % 2) === 0;

            ctx.fillStyle = alternate ? '#fffefa' : '#f6f5ef';
            ctx.fillRect(x, y, tileSize, tileSize);
        }
    }

    ctx.strokeStyle = 'rgba(190, 190, 180, 0.28)';
    ctx.lineWidth = 1;

    for (let x = 64; x <= 836; x += tileSize) {
        ctx.beginPath();
        ctx.moveTo(x, 96);
        ctx.lineTo(x, 536);
        ctx.stroke();
    }

    for (let y = 96; y <= 536; y += tileSize) {
        ctx.beginPath();
        ctx.moveTo(64, y);
        ctx.lineTo(836, y);
        ctx.stroke();
    }
}


/* ==========================================
   DRAWING HELPERS
========================================== */

function roundedRect(x, y, width, height, radius, fill, stroke = null) {

    ctx.beginPath();

    ctx.roundRect(x, y, width, height, radius);

    ctx.fillStyle = fill;
    ctx.fill();

    if (stroke) {
        ctx.strokeStyle = stroke;
        ctx.lineWidth = 2;
        ctx.stroke();
    }
}


function drawShadow(x, y, width, height, radius = 12) {

    ctx.save();

    ctx.shadowColor = 'rgba(30, 18, 10, 0.30)';
    ctx.shadowBlur = 12;
    ctx.shadowOffsetX = 4;
    ctx.shadowOffsetY = 7;

    roundedRect(x, y, width, height, radius, '#777');

    ctx.restore();
}


function drawWoodPlanks(x, y, width, height) {

    ctx.save();

    roundedRect(x, y, width, height, 5, '#85502e', '#57341f');

    const plankCount = Math.max(2, Math.floor(width / 35));

    for (let i = 0; i < plankCount; i++) {

        const plankX = x + (width / plankCount) * i;

        ctx.strokeStyle = 'rgba(255, 211, 155, 0.30)';
        ctx.lineWidth = 2;

        ctx.beginPath();
        ctx.moveTo(plankX + 5, y + 5);
        ctx.lineTo(plankX + 5, y + height - 5);
        ctx.stroke();
    }

    ctx.restore();
}


/* ==========================================
   ROOM WALLS
========================================== */

function drawWalls() {

    // Dark outer frame.
    ctx.fillStyle = '#563a2a';
    ctx.fillRect(0, 0, 900, 600);

    // Top wall.
    const wallGradient = ctx.createLinearGradient(0, 32, 0, 100);
    wallGradient.addColorStop(0, '#f0d9ad');
    wallGradient.addColorStop(1, '#d4ae7d');

    ctx.fillStyle = wallGradient;
    ctx.fillRect(32, 32, 836, 64);

    // Side walls.
    ctx.fillStyle = '#d7b486';
    ctx.fillRect(32, 32, 32, 536);
    ctx.fillRect(836, 32, 32, 536);

    // Bottom wall.
    ctx.fillStyle = '#d7b486';
    ctx.fillRect(32, 536, 836, 32);

    // Wall panel lines.
    ctx.strokeStyle = 'rgba(120, 75, 42, 0.22)';
    ctx.lineWidth = 2;

    for (let x = 85; x < 830; x += 95) {

        ctx.beginPath();
        ctx.moveTo(x, 39);
        ctx.lineTo(x, 86);
        ctx.stroke();
    }

    // Wooden skirting.
    ctx.fillStyle = '#80512f';
    ctx.fillRect(64, 88, 772, 8);
    ctx.fillRect(64, 528, 772, 8);
    ctx.fillRect(56, 96, 8, 432);
    ctx.fillRect(836, 96, 8, 432);

    ctx.fillStyle = '#bd8955';
    ctx.fillRect(64, 88, 772, 3);
    ctx.fillRect(64, 528, 772, 3);

    // Window on the top wall.
    drawWindow(370, 43, 160, 42);

    // Wall picture frames.
    drawWallFrame(125, 43, 48, 37);
    drawWallFrame(710, 43, 48, 37);

}


function drawWindow(x, y, width, height) {

    drawShadow(x, y, width, height, 4);

    roundedRect(x, y, width, height, 4, '#80512f');

    roundedRect(x + 5, y + 5, width - 10, height - 10, 2, '#9fd0d9');

    // Window panes.
    ctx.fillStyle = '#e7f1dc';
    ctx.fillRect(x + width / 2 - 2, y + 5, 4, height - 10);
    ctx.fillRect(x + 5, y + height / 2 - 2, width - 10, 4);

    // Window sill.
    ctx.fillStyle = '#704329';
    ctx.fillRect(x - 5, y + height - 2, width + 10, 6);

}


function drawWallFrame(x, y, width, height) {

    roundedRect(x, y, width, height, 3, '#70452c');

    roundedRect(x + 4, y + 4, width - 8, height - 8, 2, '#f1e1bd');

    ctx.fillStyle = '#b56f43';
    ctx.fillRect(x + 12, y + 12, width - 24, height - 24);

    ctx.fillStyle = '#e9c58a';
    ctx.fillRect(x + 19, y + 17, 8, 8);

}


/* ==========================================
   LIVING ROOM RUG
========================================== */

function drawRug() {

    // Rug shadow.
    drawShadow(260, 245, 370, 235, 13);

    // Main rug.
    roundedRect(260, 245, 370, 235, 12, '#9c4d3e', '#61352e');

    // Inner border.
    roundedRect(273, 258, 344, 209, 8, '#d7a46b', '#713c30');

    roundedRect(284, 269, 322, 187, 5, '#a84f40', '#e6bd86');

    // Decorative rug stripes.
    ctx.strokeStyle = '#e9c58a';
    ctx.lineWidth = 3;

    for (let x = 300; x <= 590; x += 24) {

        ctx.beginPath();
        ctx.moveTo(x, 281);
        ctx.lineTo(x, 444);
        ctx.stroke();
    }

    // Centre decorative diamond.
    ctx.save();

    ctx.translate(445, 362);
    ctx.rotate(Math.PI / 4);

    roundedRect(-35, -35, 70, 70, 4, '#e2b477', '#713c30');
    roundedRect(-24, -24, 48, 48, 3, '#9c4d3e', '#713c30');

    ctx.restore();

    // Rug tassels.
    ctx.strokeStyle = '#e8c18a';
    ctx.lineWidth = 3;

    for (let x = 275; x < 620; x += 16) {

        ctx.beginPath();
        ctx.moveTo(x, 245);
        ctx.lineTo(x, 238);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(x, 480);
        ctx.lineTo(x, 487);
        ctx.stroke();
    }
}


/* ==========================================
   SOFA
========================================== */

function drawSofa() {

    // Shadow.
    drawShadow(125, 135, 235, 112, 16);

    // Main sofa frame.
    roundedRect(125, 135, 235, 112, 15, '#754638', '#4d3029');

    // Backrest.
    roundedRect(137, 142, 211, 35, 10, '#a95e4d', '#6e4037');

    // Seat cushions.
    roundedRect(139, 181, 100, 55, 9, '#c77d63', '#77483c');
    roundedRect(244, 181, 102, 55, 9, '#c77d63', '#77483c');

    // Cushion details.
    ctx.strokeStyle = 'rgba(83, 42, 36, 0.30)';
    ctx.lineWidth = 2;

    ctx.beginPath();
    ctx.moveTo(189, 190);
    ctx.lineTo(189, 226);
    ctx.moveTo(294, 190);
    ctx.lineTo(294, 226);
    ctx.stroke();

    // Sofa arms.
    roundedRect(125, 169, 24, 73, 8, '#8d5143', '#57372f');
    roundedRect(336, 169, 24, 73, 8, '#8d5143', '#57372f');

    // Decorative pillows.
    roundedRect(154, 184, 34, 34, 8, '#e6c58f', '#8a5a3c');
    roundedRect(296, 184, 34, 34, 8, '#e6c58f', '#8a5a3c');

    // Pillow seams.
    ctx.strokeStyle = '#b47b4f';
    ctx.lineWidth = 2;

    ctx.beginPath();
    ctx.moveTo(171, 190);
    ctx.lineTo(171, 212);
    ctx.moveTo(313, 190);
    ctx.lineTo(313, 212);
    ctx.stroke();

    // Feet.
    ctx.fillStyle = '#493024';
    ctx.fillRect(142, 240, 12, 9);
    ctx.fillRect(331, 240, 12, 9);
}


/* ==========================================
   TV CABINET AND TELEVISION
========================================== */

function drawTelevision() {

    // Cabinet shadow.
    drawShadow(655, 116, 158, 62, 8);

    // Wooden cabinet.
    roundedRect(655, 116, 158, 62, 7, '#70442b', '#472d21');

    // Cabinet top.
    roundedRect(659, 116, 150, 15, 5, '#a66b3e', '#70442b');

    // Cabinet doors.
    roundedRect(666, 137, 57, 32, 3, '#95603b', '#563521');
    roundedRect(745, 137, 57, 32, 3, '#95603b', '#563521');

    // Door handles.
    ctx.fillStyle = '#e2c18b';
    ctx.beginPath();
    ctx.arc(715, 153, 3, 0, Math.PI * 2);
    ctx.arc(753, 153, 3, 0, Math.PI * 2);
    ctx.fill();

    // TV screen.
    drawShadow(692, 76, 85, 52, 5);

    roundedRect(692, 76, 85, 52, 5, '#252a34', '#11151b');

    roundedRect(698, 82, 73, 40, 2, '#536d7c');

    // Screen reflection.
    ctx.fillStyle = 'rgba(255,255,255,0.16)';
    ctx.beginPath();
    ctx.moveTo(701, 85);
    ctx.lineTo(725, 85);
    ctx.lineTo(705, 119);
    ctx.lineTo(701, 119);
    ctx.closePath();
    ctx.fill();

    // TV indicator.
    ctx.fillStyle = '#8ee4ad';
    ctx.beginPath();
    ctx.arc(735, 125, 2, 0, Math.PI * 2);
    ctx.fill();
}


/* ==========================================
   COFFEE TABLE
========================================== */

function drawCoffeeTable() {

    drawShadow(390, 310, 150, 80, 10);

    // Table legs.
    ctx.fillStyle = '#593722';
    ctx.fillRect(400, 370, 12, 17);
    ctx.fillRect(518, 370, 12, 17);

    // Wooden tabletop.
    roundedRect(390, 310, 150, 70, 10, '#9a6036', '#56351f');

    roundedRect(398, 316, 134, 58, 6, '#bd8753', '#80512f');

    // Table wood grain.
    ctx.strokeStyle = 'rgba(87, 48, 25, 0.35)';
    ctx.lineWidth = 2;

    ctx.beginPath();
    ctx.moveTo(408, 331);
    ctx.lineTo(522, 331);
    ctx.moveTo(408, 356);
    ctx.lineTo(522, 356);
    ctx.stroke();

    // A small book on the table.
    roundedRect(414, 326, 36, 24, 3, '#477b68', '#294d42');
    ctx.fillStyle = '#e8d3a7';
    ctx.fillRect(418, 329, 28, 2);
    ctx.fillRect(418, 344, 28, 2);

    // Decorative vase.
    ctx.fillStyle = '#e7d3b2';
    ctx.beginPath();
    ctx.ellipse(492, 346, 10, 13, 0, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#a9c18d';
    ctx.fillRect(490, 323, 4, 13);

    ctx.fillStyle = '#62865b';
    ctx.beginPath();
    ctx.ellipse(484, 326, 7, 4, -0.6, 0, Math.PI * 2);
    ctx.ellipse(499, 321, 7, 4, 0.6, 0, Math.PI * 2);
    ctx.fill();
}


/* ==========================================
   ARMCHAIR
========================================== */

function drawArmchair() {

    drawShadow(665, 310, 112, 105, 15);

    // Chair frame.
    roundedRect(665, 310, 112, 105, 15, '#654034', '#422b25');

    // Back cushion.
    roundedRect(678, 319, 86, 33, 9, '#a85e4c', '#6d3e34');

    // Seat.
    roundedRect(678, 355, 86, 49, 10, '#c77c62', '#77483b');

    // Arms.
    roundedRect(665, 348, 22, 59, 8, '#8d5143', '#57372f');
    roundedRect(755, 348, 22, 59, 8, '#8d5143', '#57372f');

    // Cushion detail.
    ctx.strokeStyle = 'rgba(83,42,36,0.30)';
    ctx.lineWidth = 2;

    ctx.beginPath();
    ctx.moveTo(721, 363);
    ctx.lineTo(721, 397);
    ctx.stroke();
}


/* ==========================================
   BOOKSHELF
========================================== */

function drawBookshelf() {

    drawShadow(78, 276, 82, 156, 7);

    // Outer wooden frame.
    roundedRect(78, 276, 82, 156, 6, '#684027', '#422a1d');

    // Shelves.
    for (let y = 286; y < 420; y += 42) {

        ctx.fillStyle = '#b47b49';
        ctx.fillRect(85, y + 34, 68, 7);

        ctx.fillStyle = '#4b2d1e';
        ctx.fillRect(85, y + 39, 68, 3);
    }

    // Books on the top shelf.
    const bookColors = ['#a94e43', '#47745d', '#d3a14f', '#596f9a'];

    for (let i = 0; i < 4; i++) {

        const bookX = 88 + i * 15;
        const bookHeight = 24 + (i % 2) * 5;

        roundedRect(
            bookX,
            296 - bookHeight,
            12,
            bookHeight,
            2,
            bookColors[i],
            '#422d22'
        );

        ctx.fillStyle = 'rgba(255,255,255,0.35)';
        ctx.fillRect(bookX + 3, 298 - bookHeight, 2, bookHeight - 5);
    }

    // Books on middle shelf.
    roundedRect(89, 346, 13, 24, 2, '#557c6a');
    roundedRect(105, 342, 12, 28, 2, '#c18b4c');
    roundedRect(121, 350, 14, 20, 2, '#a65347');

    // Storage boxes on bottom shelf.
    roundedRect(89, 390, 28, 25, 3, '#b88c5e');
    roundedRect(121, 390, 25, 25, 3, '#d4b27e');
}


/* ==========================================
   PLANTS
========================================== */

function drawPlant(x, y, scale = 1) {

    ctx.save();

    ctx.translate(x, y);
    ctx.scale(scale, scale);

    // Pot shadow.
    ctx.fillStyle = 'rgba(0,0,0,0.18)';
    ctx.beginPath();
    ctx.ellipse(0, 17, 22, 8, 0, 0, Math.PI * 2);
    ctx.fill();

    // Pot.
    roundedRect(-17, -1, 34, 25, 5, '#a95c3e', '#70402e');

    ctx.fillStyle = '#d38b5d';
    ctx.fillRect(-17, -1, 34, 5);

    // Leaves.
    ctx.fillStyle = '#4d8052';

    ctx.beginPath();
    ctx.ellipse(-12, -20, 9, 19, -0.6, 0, Math.PI * 2);
    ctx.ellipse(12, -22, 9, 20, 0.6, 0, Math.PI * 2);
    ctx.ellipse(0, -34, 10, 21, 0, 0, Math.PI * 2);
    ctx.ellipse(-18, -8, 8, 14, -1.0, 0, Math.PI * 2);
    ctx.ellipse(18, -9, 8, 14, 1.0, 0, Math.PI * 2);

    ctx.fill();

    // Leaf highlights.
    ctx.strokeStyle = '#88ae69';
    ctx.lineWidth = 2;

    ctx.beginPath();
    ctx.moveTo(0, -9);
    ctx.lineTo(0, -47);
    ctx.moveTo(-5, -12);
    ctx.lineTo(-17, -28);
    ctx.moveTo(5, -12);
    ctx.lineTo(17, -29);
    ctx.stroke();

    ctx.restore();
}


/* ==========================================
   FLOOR LAMP
========================================== */

function drawFloorLamp() {

    // Base.
    ctx.fillStyle = '#65442e';
    ctx.beginPath();
    ctx.ellipse(585, 222, 21, 8, 0, 0, Math.PI * 2);
    ctx.fill();

    // Stand.
    ctx.fillStyle = '#745238';
    ctx.fillRect(581, 143, 8, 78);

    // Lampshade.
    ctx.fillStyle = '#e8d1a0';

    ctx.beginPath();
    ctx.moveTo(558, 149);
    ctx.lineTo(569, 112);
    ctx.lineTo(601, 112);
    ctx.lineTo(612, 149);
    ctx.closePath();
    ctx.fill();

    ctx.strokeStyle = '#9d7447';
    ctx.lineWidth = 2;
    ctx.stroke();
}


/* ==========================================
   SIDE TABLE AND DECORATION
========================================== */

function drawSideTable() {

    drawShadow(390, 150, 75, 55, 7);

    roundedRect(390, 150, 75, 55, 7, '#82512f', '#4f321f');
    roundedRect(395, 155, 65, 45, 4, '#b27a49', '#79502f');

    // Small decorative object.
    ctx.fillStyle = '#d7b46f';
    ctx.beginPath();
    ctx.arc(428, 178, 11, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#8d5741';
    ctx.beginPath();
    ctx.arc(428, 178, 6, 0, Math.PI * 2);
    ctx.fill();
}


/* ==========================================
   COMPLETE LIVING ROOM
========================================== */
function drawWallDecorations() {

    // Left wall picture frame.
    roundedRect(245, 65, 95, 65, 5, '#70472e', '#422b20');
    roundedRect(251, 71, 83, 53, 2, '#f5e7c8', '#b18a5b');

    // Picture landscape.
    ctx.fillStyle = '#91b8a0';
    ctx.fillRect(255, 75, 75, 45);

    // Mountains.
    ctx.fillStyle = '#62876d';
    ctx.beginPath();
    ctx.moveTo(255, 112);
    ctx.lineTo(275, 84);
    ctx.lineTo(293, 105);
    ctx.lineTo(309, 86);
    ctx.lineTo(330, 112);
    ctx.closePath();
    ctx.fill();

    // Sun.
    ctx.fillStyle = '#f2c66d';
    ctx.beginPath();
    ctx.arc(315, 85, 8, 0, Math.PI * 2);
    ctx.fill();


    // Wall clock.
    ctx.fillStyle = '#70472e';
    ctx.beginPath();
    ctx.arc(510, 90, 23, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#fff0d0';
    ctx.beginPath();
    ctx.arc(510, 90, 19, 0, Math.PI * 2);
    ctx.fill();

    ctx.strokeStyle = '#70472e';
    ctx.lineWidth = 2;

    ctx.beginPath();
    ctx.moveTo(510, 90);
    ctx.lineTo(510, 77);
    ctx.moveTo(510, 90);
    ctx.lineTo(520, 95);
    ctx.stroke();


    // Small decorative wall shelf.
    roundedRect(555, 125, 85, 9, 3, '#80502f', '#52331f');

    // Small flower pot.
    roundedRect(580, 105, 22, 19, 4, '#bd7650', '#80503a');

    ctx.fillStyle = '#4d8052';
    ctx.beginPath();
    ctx.ellipse(585, 101, 7, 12, -0.5, 0, Math.PI * 2);
    ctx.ellipse(598, 99, 7, 12, 0.5, 0, Math.PI * 2);
    ctx.fill();
}


/* ==========================================
   NASHA SPRITE LOADER
========================================== */

const roomSpriteImages = {};

const roomSpriteFiles = {
    sofa: 'living_room/variants/three_seat_sofa__lavender.png',
    sofa2: 'living_room/variants/three_seat_sofa__lavender.png',
    armchair: 'living_room/armchair.png',
    coffeeTable: 'living_room/low_coffee_table.png',
    bookshelf: 'living_room/variants/tall_bookshelf_with_books__white_paint.png',
    floorLamp: 'living_room/floor_lamp.png',
    livingRug: 'living_room/variants/large_patterned_rug__baby_blue.png',
    plant: 'living_room/potted_fiddle_leaf_fig.png',
    monstera: 'living_room/potted_monstera_plant.png',
    painting: 'living_room/framed_landscape_painting.png',
    curtains: 'living_room/window_cream_curtains.png',
    basket: 'living_room/basket_of_blankets.png',
    pouf: 'living_room/variants/knitted_pouf__baby_blue.png',
    books: 'living_room/stack_of_books.png',

    fridge: 'kitchen/tall_cream_refrigerator_with_rounded_corners.png',
    sink: 'kitchen/kitchen_counter_with_a_white_ceramic_sink.png',
    stove: 'kitchen/kitchen_counter_with_a_four_burner_stove_top.png',
    counter: 'kitchen/long_wooden_kitchen_counter_with_drawers.png',
    island: 'kitchen/wooden_kitchen_island_with_a_butcher_block_top.png',
    kitchenRug: 'kitchen/rag_rug_for_the_kitchen_floor.png',
    table: 'kitchen/small_round_wooden_table.png',
    chair: 'kitchen/wooden_chair_with_a_gingham_seat_cushion.png',
    herbs: 'kitchen/tall_potted_herb_stand_with_three_shelves_of_herbs.png',
    hutch: 'kitchen/tall_kitchen_hutch_with_plates_on_its_open_shelves.png',
    shelf: 'kitchen/long_wall_shelf_with_mugs_hanging_below.png',
    toaster: 'kitchen/toaster_with_toast_popping_up.png',
    bread: 'kitchen/bread_bin.png',
        // Treasure Room furniture and decorations.
    treasureBookshelf: 'living_room/variants/tall_bookshelf_with_books__white_paint.png',
    treasureSofa: 'living_room/variants/three_seat_sofa__lavender.png',
    treasureArmchair: 'living_room/variants/armchair__baby_blue.png',
    treasureCoffeeTable: 'living_room/variants/low_coffee_table__white_paint.png',
    treasureRug: 'living_room/variants/large_patterned_rug__baby_blue.png',
    treasurePouf: 'living_room/variants/knitted_pouf__turquoise.png',
    treasureFloorLamp: 'living_room/floor_lamp.png',
    treasureMonstera: 'living_room/potted_monstera_plant.png',
    treasureFiddleLeaf: 'living_room/potted_fiddle_leaf_fig.png',
    treasureBooks: 'living_room/stack_of_books.png',

    treasureWardrobe: 'bedroom/variants/wardrobe__white_paint.png',
    treasureNightstand: 'bedroom/variants/nightstand_with_two_drawers__white_paint.png',
    treasureTableLamp: 'bedroom/table_lamp.png',

    treasureClock: 'decor/round_wall_clock.png',
    treasureMirror: 'decor/round_wall_mirror.png',
    treasureLantern: 'decor/candle_lantern.png',
    treasureRoundRug: 'decor/variants/round_braided_rug__baby_blue.png',

    treasureChestClosed: 'treasure_room/TreasureChestPack/Wooden_Angled_Closed.png',
    treasureChestOpen: 'treasure_room/TreasureChestPack/Wooden_Angled_Open.png',
    roundRug: 'decor/round_braided_rug.png',
    lantern: 'decor/candle_lantern.png',
    fern: 'decor/potted_fern.png',
    flowers: 'decor/vase_of_daisies.png',
    wardrobe: 'bedroom/wardrobe.png',
    bedroomRug: 'bedroom/fringed_bedroom_rug.png',

treasureBed: 'bedroom/variants/double_bed__cherry.png',
treasureBedroomRug: 'bedroom/variants/fringed_bedroom_rug__baby_blue.png',
treasureNightstand: 'bedroom/variants/nightstand_with_two_drawers__cherry.png',
treasureWardrobe: 'bedroom/variants/wardrobe__cherry.png',
treasureVanity: 'bedroom/variants/vanity_table_with_a_mirror__cherry.png',
treasureTableLamp: 'bedroom/table_lamp.png',
treasureSnakePlant: 'bedroom/potted_snake_plant.png',
treasurePainting: 'bedroom/framed_flower_painting.png',
treasureTeddy: 'bedroom/teddy_bear.png',

treasureBathtub: 'bathroom/bathtub.png',
treasureBathroomSink: 'bathroom/bathroom_sink_with_a_mirror.png',
treasureToilet: 'bathroom/toilet.png',
treasureSoap: 'bathroom/bar_of_soap.png',
treasureSoapPump: 'bathroom/soap_pump_bottle.png',
treasureShampoo: 'bathroom/shampoo_bottle.png',
treasureTowels: 'bathroom/stack_of_folded_towels.png',
treasureDuck: 'bathroom/rubber_duck.png',
treasureToothbrush: 'bathroom/toothbrush_cup.png',

treasureChestOpen: 'treasure_room/TreasureChestPack/Wooden_Angled_Open.png',
};

Object.entries(roomSpriteFiles).forEach(([name, file]) => {
    const img = new Image();
    img.src = '../assets/sprites/' + file;
    roomSpriteImages[name] = img;
});

function drawRoomSprite(name, x, y, width, height) {
    const img = roomSpriteImages[name];

    if (!img || !img.complete || !img.naturalWidth) {
        return;
    }

    const scale = Math.min(
        width / img.naturalWidth,
        height / img.naturalHeight
    );

    const drawWidth = img.naturalWidth * scale;
    const drawHeight = img.naturalHeight * scale;

    ctx.drawImage(
        img,
        x + (width - drawWidth) / 2,
        y + (height - drawHeight) / 2,
        drawWidth,
        drawHeight
    );
}


/* ==========================================
   LIVING ROOM
========================================== */

function drawLivingRoom() {

    drawFloorLayer();
    drawWalls();

    // Large baby-blue carpet anchors the seating area.
    drawRoomSprite('livingRug', 235, 275, 430, 203);

    // TV on the top wall, facing the seating area.
    drawShadow(380, 98, 140, 76, 9);
    roundedRect(380, 98, 140, 76, 9, '#eee8f7', '#a58bbd');
    roundedRect(389, 106, 122, 56, 5, '#343b55', '#c5b4d9');

    ctx.fillStyle = '#bce9fa';
    ctx.fillRect(396, 113, 108, 42);

    ctx.fillStyle = '#fff0ad';
    ctx.beginPath();
    ctx.arc(450, 134, 12, 0, Math.PI * 2);
    ctx.fill();

    // Matching lavender sofas, arranged as one seating zone.
    drawRoomSprite('sofa', 115, 160, 245, 110);
    drawRoomSprite('sofa2', 540, 160, 245, 110);

    // Central coffee table on the carpet.
    drawRoomSprite('coffeeTable', 365, 325, 160, 86);

    // White bookshelf along the left wall.
    drawRoomSprite('bookshelf', 75, 285, 110, 175);

    // A cosy reading corner.
    drawRoomSprite('floorLamp', 735, 265, 48, 120);
    drawRoomSprite('pouf', 570, 390, 78, 70);

    // Plants grouped near the room edges.
    drawRoomSprite('plant', 75, 135, 65, 90);
    drawRoomSprite('monstera', 755, 385, 65, 75);

    // Wall decoration and small finishing touches.
    drawRoomSprite('curtains', 190, 75, 115, 95);
    drawRoomSprite('painting', 585, 75, 90, 75);
    drawRoomSprite('basket', 210, 430, 65, 60);
    drawRoomSprite('books', 405, 290, 45, 40);
}


/* ==========================================
   KITCHEN
========================================== */

function drawKitchen() {

    drawFloorLayer();
    drawWalls();

    // Kitchen rug.
    drawRoomSprite('kitchenRug', 330, 450, 230, 80);

    // Back counter and kitchen worktops.
    drawRoomSprite('sink', 80, 105, 170, 105);
    drawRoomSprite('counter', 235, 105, 300, 105);
    drawRoomSprite('stove', 610, 105, 185, 105);

    // Kitchen appliances.
    drawRoomSprite('fridge', 85, 225, 120, 215);

    // Kitchen island.
    drawRoomSprite('island', 290, 300, 280, 145);

    // Small kitchen decorations.
    drawRoomSprite('shelf', 345, 70, 180, 90);
    drawRoomSprite('herbs', 780, 220, 65, 135);
    drawRoomSprite('hutch', 690, 240, 100, 160);

    // Table and chairs.
    drawRoomSprite('table', 620, 395, 100, 90);
    drawRoomSprite('chair', 570, 390, 65, 85);
    drawRoomSprite('chair', 705, 390, 65, 85);

    // Small objects.
    drawRoomSprite('toaster', 535, 155, 55, 55);
    drawRoomSprite('bread', 260, 155, 55, 45);
}


/* ==========================================
   TREASURE ROOM
========================================== */





function drawTreasureRoom() {
    drawFloorLayer();
    drawWalls();

    // Soft purple and golden bedroom atmosphere.
    const bedroomGlow = ctx.createRadialGradient(
        480, 280, 10,
        480, 280, 380
    );

    bedroomGlow.addColorStop(0, 'rgba(160, 125, 255, 0.16)');
    bedroomGlow.addColorStop(1, 'rgba(160, 125, 255, 0)');

    ctx.fillStyle = bedroomGlow;
    ctx.fillRect(64, 96, 772, 432);

    // =====================================
    // BEDROOM WALL DECORATION
    // =====================================

    drawRoomSprite('treasurePainting', 365, 105, 75, 65);

    // =====================================
    // MAIN BED AND RUG
    // =====================================

    drawRoomSprite(
        'treasureBedroomRug',
        245, 270, 350, 220
    );

    drawRoomSprite(
        'treasureBed',
        300, 130, 235, 195
    );

    drawRoomSprite(
        'treasureTeddy',
        445, 275, 45, 45
    );

    // =====================================
    // MATCHING BEDSIDE TABLES
    // =====================================

    drawRoomSprite('treasureNightstand', 220, 160, 80, 90);
    drawRoomSprite('treasureNightstand', 535, 160, 80, 90);

    drawRoomSprite('treasureTableLamp', 235, 140, 45, 50);
    drawRoomSprite('treasureTableLamp', 550, 140, 45, 50);

    // =====================================
    // WARDROBE AND DRESSING AREA
    // =====================================

    drawRoomSprite(
        'treasureWardrobe',
        680, 130, 125, 175
    );

    drawRoomSprite(
        'treasureVanity',
        90, 330, 140, 125
    );

    drawRoomSprite(
        'treasureSnakePlant',
        115, 175, 65, 100
    );

    drawRoomSprite(
        'treasureSnakePlant',
        755, 390, 65, 100
    );

    // =====================================
    // TREASURE CHEST
    // =====================================

    if (challengeCompleted) {

        const treasureGlow = ctx.createRadialGradient(
            700, 390, 5,
            700, 390, 115
        );

        treasureGlow.addColorStop(
            0,
            'rgba(255, 210, 80, 0.65)'
        );

        treasureGlow.addColorStop(
            1,
            'rgba(255, 210, 80, 0)'
        );

        ctx.fillStyle = treasureGlow;
        ctx.fillRect(590, 285, 220, 190);

        // Treasure revealed!
        drawRoomSprite(
            'treasureChestOpen',
            650, 345, 125, 115
        );

    } else {

        // Closed chest is visible before solving the puzzle.
        drawRoomSprite(
            'treasureChestClosed',
            650, 345, 125, 115
        );
    }
}




/* ==========================================
   FURNITURE COLLISION
========================================== */

const obstacles = [

    // Left lavender sofa.
    { x: 125, y: 175, w: 225, h: 85 },

    // Right lavender sofa.
    { x: 550, y: 175, w: 225, h: 85 },

    // TV.
    { x: 385, y: 100, w: 130, h: 65 },

    // Central coffee table.
    { x: 375, y: 335, w: 140, h: 65 },

    // White bookshelf.
    { x: 80, y: 295, w: 95, h: 155 },

    // Floor lamp base.
    { x: 745, y: 350, w: 28, h: 22 },

    // Plants.
    { x: 85, y: 155, w: 40, h: 45 },
    { x: 765, y: 400, w: 40, h: 40 }

];


/* ==========================================
   KITCHEN FURNITURE COLLISION
========================================== */

const kitchenObstacles = [

    // Back kitchen cabinets and countertop.
    { x: 90, y: 105, w: 700, h: 100 },

    // Refrigerator.
    { x: 90, y: 235, w: 115, h: 205 },

    // Stove and oven.
    { x: 625, y: 235, w: 165, h: 170 },

    // Kitchen island.
    { x: 326, y: 300, w: 208, h: 145 },

    // Kitchen chairs.
    { x: 570, y: 390, w: 65, h: 85 },


    { x: 705, y: 390, w: 65, h: 85 }

];




/* ==========================================
   EXTRA LIVING ROOM COLLISION OBJECTS
========================================== */

const livingRoomExtraObstacles = [

    // Baby-blue knitted pouf.
    { x: 570, y: 390, w: 78, h: 70 },

    // Basket of blankets.
    { x: 210, y: 430, w: 65, h: 60 }

];

/* ==========================================
   TREASURE ROOM FURNITURE COLLISION
========================================== */

const treasureRoomObstacles = [

    // Bed.
    { x: 230, y: 145, w: 190, h: 85 },

    // Left bedside table.
    { x: 180, y: 145, w: 65, h: 60 },

    // Right bedside table.
    { x: 500, y: 145, w: 70, h: 60 },

    // Wardrobe.
    { x: 620, y: 120, w: 120, h: 100 },

    // Vanity.
    { x: 64, y: 310, w: 100, h: 85 },

    // Treasure chest.
    { x: 590, y: 300, w: 120, h: 140 },

    // Left plant.
    { x: 90, y: 200, w: 45, h: 50 },

    // Right plant.
    { x: 730, y: 420, w: 45, h: 45 }

];

/* ==========================================
   GAME OBJECTS
========================================== */

const sparklingObject = {
    x: currentRoom === 2 ? 840 : currentRoom === 3 ? 625 : 755,
    y: currentRoom === 2 ? 180 : currentRoom === 3 ? 345 : 475
};

const INTERACTION_DISTANCE = 70;
const TREASURE_INTERACTION_DISTANCE = 135;

let lastTime = 0;
let animationTime = 0;


/* ==========================================
   KEY INVENTORY
========================================== */

function updateKeyInventory(keysEarned) {

    document.getElementById('key1').style.display =
        keysEarned >= 1 ? 'inline-block' : 'none';

    document.getElementById('key2').style.display =
        keysEarned >= 2 ? 'inline-block' : 'none';

}

updateKeyInventory(currentRoom - 1);


/* ==========================================
   KEYBOARD CONTROLS
========================================== */

window.addEventListener('keydown', function(event) {

    const key = event.key.toLowerCase();

    if ([
        'arrowup',
        'arrowdown',
        'arrowleft',
        'arrowright',
        'w',
        'a',
        's',
        'd',
        'e'
    ].includes(key)) {
        event.preventDefault();
    }

    if (key === 'e') {

        if (!event.repeat) {
            interact();
        }

        return;
    }

    keys[key] = true;

});


window.addEventListener('keyup', function(event) {
    keys[event.key.toLowerCase()] = false;
});


window.addEventListener('blur', function() {

    Object.keys(keys).forEach(key => {
        keys[key] = false;
    });

});


interactButton.addEventListener('click', interact);

canvas.addEventListener('click', function(event) {

    if (currentRoom !== 3) {
        return;
    }

    const rect = canvas.getBoundingClientRect();

    const mouseX =
        (event.clientX - rect.left) * (canvas.width / rect.width);

    const mouseY =
        (event.clientY - rect.top) * (canvas.height / rect.height);

    // Clickable area around the visible treasure chest.
    const clickedChest =
        mouseX >= 585 &&
        mouseX <= 715 &&
        mouseY >= 285 &&
        mouseY <= 405;

    if (clickedChest) {
        interact();
    }

});


/* ==========================================
   INTERACTION DISTANCE
========================================== */

function getInteractionDistance() {

    if (currentRoom === 3) {
        return Math.hypot(
            player.x - 650,
            player.y - 345
        );
    }

    return Math.hypot(
        player.x - sparklingObject.x,
        player.y - sparklingObject.y
    );

}


function updateInteractionPrompt() {

    if (currentRoom === 3) {
        interactionPrompt.style.display = 'none';
        interactButton.style.display = 'none';
        return;
    }

    const nearby =
        getInteractionDistance() <= INTERACTION_DISTANCE &&
        !puzzleOpened &&
        !challengeCompleted;

    interactionPrompt.style.display = nearby ? 'block' : 'none';
    interactButton.style.display = nearby ? 'block' : 'none';

}


/* ==========================================
   COLLISION DETECTION
========================================== */

function collides(x, y) {

    const r = player.radius;

    // Keep the player inside the room.
    if (
        x - r < 64 ||
        x + r > 836 ||
        y - r < 96 ||
        y + r > 528
    ) {
        return true;
    }

    // Select only the furniture belonging to this room.
    let roomObstacles;

    if (currentRoom === 1) {
        roomObstacles = [
            ...obstacles,
            ...livingRoomExtraObstacles
        ];
    }
    else if (currentRoom === 2) {
        roomObstacles = kitchenObstacles;
    }
    else {
        roomObstacles = treasureRoomObstacles;
    }

    // Check whether the player's body touches furniture.
    return roomObstacles.some(obstacle =>

        x + r > obstacle.x &&
        x - r < obstacle.x + obstacle.w &&
        y + r > obstacle.y &&
        y - r < obstacle.y + obstacle.h

    );

}




/* ==========================================
   PLAYER MOVEMENT
========================================== */
/* ==========================================
   GRANNY VISION DETECTION
========================================== */

function checkGrannyDetection() {

    const toPlayerX = player.x - granny.x;
    const toPlayerY = player.y - granny.y;

    const distanceToPlayer = Math.hypot(
        toPlayerX,
        toPlayerY
    );

    const playerAngle = Math.atan2(
        toPlayerY,
        toPlayerX
    );

    const angleDifference = Math.atan2(
        Math.sin(playerAngle - granny.angle),
        Math.cos(playerAngle - granny.angle)
    );

    const insideVision =
        distanceToPlayer <= granny.visionRange &&
        Math.abs(angleDifference) <= granny.visionAngle;

    if (insideVision) {
        endGame('Oh no! Granny spotted you! Try again.');
    }
}
function update(delta) {

    // Countdown timer
    if (!gameEnded) {
        timeRemaining = Math.max(0, timeRemaining - delta);

        const seconds = Math.ceil(timeRemaining);

        timerValue.textContent =
            '00:' + String(seconds).padStart(2, '0');

        if (timeRemaining <= 0) {
            timerValue.textContent = '00:00';
            endGame('Time is up! Try again.');
            return;
        }
    }

    // Stop gameplay after Game Over
    if (gameEnded) return;

    player.isMoving = false;

    if (puzzleModal.style.display === 'flex') {
        return;
    }

    let dx = 0;
    let dy = 0;

    if (keys['arrowup'] || keys['w']) dy--;
    if (keys['arrowdown'] || keys['s']) dy++;
    if (keys['arrowleft'] || keys['a']) dx--;
    if (keys['arrowright'] || keys['d']) dx++;

    if (dx !== 0 || dy !== 0) {

        const length = Math.hypot(dx, dy);

        dx /= length;
        dy /= length;

        const distance = player.speed * delta;

        if (Math.abs(dx) > Math.abs(dy)) {
            player.direction = dx > 0 ? 'right' : 'left';
        } else {
            player.direction = dy > 0 ? 'down' : 'up';
        }

        const nextX = player.x + dx * distance;

        if (!collides(nextX, player.y)) {
            player.x = nextX;
            player.isMoving = true;
        }

        const nextY = player.y + dy * distance;

        if (!collides(player.x, nextY)) {
            player.y = nextY;
            player.isMoving = true;
        }
    }

        
// Move Granny around her rectangular patrol route.

// Move Granny around her patrol route.
// Kitchen uses its own route and furniture collision checks.
const isKitchenRoom = currentRoom === 2;
const isTreasureRoom = currentRoom === 3;

const activePatrolPoints = isKitchenRoom
    ? kitchenGrannyPatrolPoints
    : isTreasureRoom
        ? treasureGrannyPatrolPoints
        : grannyPatrolPoints;
const target = activePatrolPoints[grannyPatrolIndex];

const moveX = target.x - granny.x;
const moveY = target.y - granny.y;

const distance = Math.hypot(moveX, moveY);
const movement = granny.speed * delta;

const previousX = granny.x;
const previousY = granny.y;

if (!isKitchenRoom) {

    // Preserve the original movement in other rooms.
    if (distance <= movement) {

        granny.x = target.x;
        granny.y = target.y;

        grannyPatrolIndex =
            (grannyPatrolIndex + 1) % activePatrolPoints.length;

    } else if (distance > 0) {

        granny.x += (moveX / distance) * movement;
        granny.y += (moveY / distance) * movement;
    }

} else {

    // Check the Postie's position against kitchen furniture.
    const grannyRadius = 10;

    function grannyHitsKitchenFurniture(x, y) {
        return kitchenObstacles.some(obstacle =>
            x + grannyRadius > obstacle.x &&
            x - grannyRadius < obstacle.x + obstacle.w &&
            y + grannyRadius > obstacle.y &&
            y - grannyRadius < obstacle.y + obstacle.h
        );
    }

    // Move in small steps to prevent passing through objects.
    const steps = Math.max(1, Math.ceil(movement / 4));
    const stepSize = movement / steps;

    for (let i = 0; i < steps && distance > 0; i++) {

        const remainingX = target.x - granny.x;
        const remainingY = target.y - granny.y;
        const remainingDistance = Math.hypot(remainingX, remainingY);

        if (remainingDistance <= stepSize) {
            break;
        }

        const nextX = granny.x +
            (remainingX / remainingDistance) * stepSize;

        const nextY = granny.y +
            (remainingY / remainingDistance) * stepSize;

        if (!grannyHitsKitchenFurniture(nextX, granny.y)) {
            granny.x = nextX;
        }

        if (!grannyHitsKitchenFurniture(granny.x, nextY)) {
            granny.y = nextY;
        }
    }

    // Advance to the next patrol point when reached.
    if (Math.hypot(target.x - granny.x, target.y - granny.y) <= 5) {

        granny.x = target.x;
        granny.y = target.y;

        grannyPatrolIndex =
            (grannyPatrolIndex + 1) % activePatrolPoints.length;
    }
}


// Rotate Granny's vision cone in her walking direction.
granny.angle = Math.atan2(moveY, moveX);

// Check whether Granny can see Rogue.
checkGrannyDetection();

if (gameEnded) {
    return;
}

    animationTime += delta;

    updateInteractionPrompt();
}


/* ==========================================
   SPARKLING OBJECT
========================================== */




function drawSparkle() {

    const pulse = 1 + Math.sin(animationTime * 3) * 0.035;
    const floatY = Math.sin(animationTime * 2.5) * 3;

    ctx.save();

    ctx.translate(
        sparklingObject.x,
        sparklingObject.y + floatY
    );

    ctx.scale(pulse, pulse);

    // Small glossy transparent water bubble
    const bubbleGradient = ctx.createRadialGradient(
        -9, -13, 2,
        0, 0, 27
    );

    bubbleGradient.addColorStop(0, 'rgba(255,255,255,0.42)');
    bubbleGradient.addColorStop(0.55, 'rgba(120,220,255,0.10)');
    bubbleGradient.addColorStop(1, 'rgba(80,180,255,0.22)');

    ctx.shadowColor = '#75dfff';
    ctx.shadowBlur = 12;

    ctx.beginPath();
    ctx.arc(0, 0, 25, 0, Math.PI * 2);
    ctx.fillStyle = bubbleGradient;
    ctx.fill();

    ctx.shadowBlur = 0;

    // Bubble outline
    ctx.strokeStyle = 'rgba(190,240,255,0.95)';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    // Smaller wooden key
    ctx.save();
    ctx.scale(0.7, 0.7);

    const woodGradient = ctx.createLinearGradient(
        -12, -20, 12, 22
    );

    woodGradient.addColorStop(0, '#f2c27a');
    woodGradient.addColorStop(0.5, '#a96732');
    woodGradient.addColorStop(1, '#70401f');

    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    // Key handle
    ctx.beginPath();
    ctx.arc(0, -12, 11, 0, Math.PI * 2);
    ctx.strokeStyle = '#63391f';
    ctx.lineWidth = 9;
    ctx.stroke();

    ctx.beginPath();
    ctx.arc(0, -12, 11, 0, Math.PI * 2);
    ctx.strokeStyle = woodGradient;
    ctx.lineWidth = 6;
    ctx.stroke();

    // Key shaft
    ctx.beginPath();
    ctx.moveTo(0, -1);
    ctx.lineTo(0, 21);
    ctx.strokeStyle = '#63391f';
    ctx.lineWidth = 9;
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(0, -1);
    ctx.lineTo(0, 21);
    ctx.strokeStyle = woodGradient;
    ctx.lineWidth = 6;
    ctx.stroke();

    // Key teeth
    ctx.beginPath();
    ctx.moveTo(0, 10);
    ctx.lineTo(9, 10);
    ctx.lineTo(9, 16);

    ctx.moveTo(0, 20);
    ctx.lineTo(-8, 20);

    ctx.strokeStyle = '#70401f';
    ctx.lineWidth = 6;
    ctx.stroke();

    // Wooden texture
    ctx.beginPath();
    ctx.moveTo(-3, 3);
    ctx.lineTo(-3, 7);

    ctx.moveTo(3, 13);
    ctx.lineTo(3, 17);

    ctx.strokeStyle = 'rgba(255,220,160,0.65)';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    ctx.restore();

    // Bubble reflections
    ctx.beginPath();
    ctx.ellipse(-16, -16, 3, 6, -0.6, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(255,255,255,0.9)';
    ctx.fill();

    ctx.beginPath();
    ctx.arc(17, 12, 2, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(255,255,255,0.9)';
    ctx.fill();

    // Small floating highlights
    ctx.fillStyle = '#ffffff';

    ctx.beginPath();
    ctx.arc(-20, 3, 1.5, 0, Math.PI * 2);
    ctx.fill();

    ctx.beginPath();
    ctx.arc(19, -16, 1.5, 0, Math.PI * 2);
    ctx.fill();

    ctx.restore();
}



function drawGranny() {

    const x = granny.x;
    const y = granny.y;

    ctx.save();

    // Draw Granny's vision cone
    ctx.fillStyle = 'rgba(255, 60, 60, 0.18)';
    ctx.beginPath();
    ctx.moveTo(x, y);

    ctx.arc(
        x,
        y,
        granny.visionRange,
        granny.angle - granny.visionAngle,
        granny.angle + granny.visionAngle
    );

    ctx.closePath();
    ctx.fill();

    // Character shadow
    ctx.fillStyle = 'rgba(0, 0, 0, 0.25)';
    ctx.beginPath();
    ctx.ellipse(x, y + 25, 19, 8, 0, 0, Math.PI * 2);
    ctx.fill();

    // Determine walking direction from Granny's patrol angle
    const horizontal = Math.cos(granny.angle);
    const vertical = Math.sin(granny.angle);

    let direction;

    if (Math.abs(horizontal) > Math.abs(vertical)) {
        direction = horizontal > 0 ? 'right' : 'left';
    } else {
        direction = vertical > 0 ? 'down' : 'up';
    }

    // Alternate between the two walking frames
    const frameIndex = Math.floor(animationTime * 6) % 2;

    const sprite = grannySprites[direction][frameIndex];

    if (sprite && sprite.complete && sprite.naturalWidth > 0) {

        ctx.drawImage(
            sprite,
            x - 32,
            y - 32,
            64,
            64
        );

    } else {

        // Temporary fallback while the image loads
        ctx.fillStyle = '#49304f';
        ctx.beginPath();
        ctx.arc(x, y, 15, 0, Math.PI * 2);
        ctx.fill();

    }

    ctx.restore();
}

/* ==========================================
   ROGUE CHARACTER DRAWING
========================================== */

function drawPlayer() {

    const x = player.x;
    const y = player.y;

    // Character shadow.
    ctx.fillStyle = 'rgba(0, 0, 0, 0.30)';

    ctx.beginPath();

    ctx.ellipse(
        x,
        y + 12,
        13,
        6,
        0,
        0,
        Math.PI * 2
    );

    ctx.fill();

    const sprite = player.isMoving
        ? playerSprites[player.direction]
        : playerSprites.idle;

    if (!sprite || !sprite.complete || sprite.naturalWidth === 0) {
        return;
    }

    const totalFrames = player.isMoving ? 4 : 2;
    const frameSpeed = player.isMoving ? 8 : 3;

    const frame = Math.floor(
        animationTime * frameSpeed
    ) % totalFrames;

    ctx.imageSmoothingEnabled = false;

    ctx.drawImage(
        sprite,
        frame * 16,
        0,
        16,
        16,
        x - 27,
        y - 35,
        54,
        58
    );
}


/* ==========================================
   LOAD PUZZLE
========================================== */

async function interact() {

    if (puzzleLoading || puzzleOpened || challengeCompleted) {
        return;
    }

    const allowedDistance = currentRoom === 3
    ? TREASURE_INTERACTION_DISTANCE
    : INTERACTION_DISTANCE;

if (getInteractionDistance() > allowedDistance) {

    message.textContent = currentRoom === 3
        ? 'Move closer to the treasure chest!'
        : 'You are too far away! Move closer to the sparkling object.';

    return;
}

    puzzleLoading = true;

    message.textContent =
        'You discovered the hidden object! Loading puzzle...';

    try {

        const response = await fetch(
            '../api/get_puzzle.php?difficulty=' +
            encodeURIComponent(currentDifficulty) +
            '&room=' + currentRoom
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Could not load the puzzle.');
        }

        puzzleTitle.textContent = currentRoom === 3
            ? 'Final Challenge: Unlock the Treasure!'
            : 'Solve the Banana Puzzle!';

        puzzleDescription.textContent = currentRoom === 3
            ? 'You have both wooden keys. Solve this final puzzle to open the treasure chest!'
            : 'Solve the puzzle correctly to collect your wooden key.';

        puzzleImage.src = data.question;
        puzzleImage.alt = 'Banana puzzle';

        puzzleFeedback.textContent = '';
        answerInput.value = '';

        answerInput.disabled = false;
        submitAnswer.disabled = false;

        answerForm.style.display = 'block';
        continueButton.style.display = 'none';

        puzzleModal.style.display = 'flex';
        puzzleOpened = true;

        message.textContent = 'Solve the puzzle to continue!';

        answerInput.focus();

    } catch (error) {

        message.textContent = error.message;

    } finally {

        puzzleLoading = false;

    }
}


/* ==========================================
   SUBMIT PUZZLE ANSWER
========================================== */

answerForm.addEventListener('submit', async function(event) {

    event.preventDefault();

    if (puzzleLoading || challengeCompleted) {
        return;
    }

    const answer = answerInput.value.trim();

    if (!/^\d{1,3}$/.test(answer)) {

        puzzleFeedback.textContent =
            'Please enter a valid answer.';

        return;
    }

    puzzleLoading = true;

    submitAnswer.disabled = true;
    answerInput.disabled = true;

    puzzleFeedback.textContent = 'Checking your answer...';

    try {

        const response = await fetch('../api/submit_answer.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            body: new URLSearchParams({
                answer: answer
            })

        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Could not submit your answer.');
        }

        if (data.correct === false) {

            puzzleFeedback.textContent =
                data.message || 'Incorrect answer! Please try again.';

            answerInput.value = '';
            answerInput.disabled = false;
            submitAnswer.disabled = false;

            answerInput.focus();

            return;
        }

        if (data.correct !== true) {
            throw new Error('Unexpected response from the server.');
        }

        challengeCompleted = true;

        answerForm.style.display = 'none';

        if (currentRoom === 1 || currentRoom === 2) {

            const keysEarned =
                Number(data.keysEarned) || currentRoom;

            updateKeyInventory(keysEarned);

            puzzleTitle.textContent =
                '🔑 You Found a Wooden Key!';

            puzzleDescription.textContent =
                data.message || `You earned Wooden Key ${currentRoom}!`;

            puzzleFeedback.textContent =
                'Your key has been collected successfully!';

            continueButton.textContent = 'Return to Map';

            continueButton.style.display = 'inline-block';

            message.textContent =
                data.message || 'You collected a wooden key!';

        } else {

    const goldEarned = Number(data.goldAwarded) || 1000;

    // Hide the puzzle image and show the celebration.
    document.getElementById('puzzleImage').style.display = 'none';

    const celebration =
        document.getElementById('treasureCelebration');

    celebration.hidden = false;

    // Update the treasure message.
    puzzleTitle.textContent = 'TREASURE UNLOCKED!';

    puzzleDescription.textContent =
        'You solved the final Banana Puzzle!';

    puzzleFeedback.textContent =
        'Amazing! You found the hidden treasure!';

    // Display the gold reward.
    document.querySelector('.gold-amount').textContent =
        `+${goldEarned.toLocaleString()} GOLD!`;

    continueButton.textContent = 'Collect Treasure & Return to Map';

    continueButton.style.display = 'inline-block';

    message.textContent =
        `Congratulations! You earned ${goldEarned.toLocaleString()} gold!`;
}

    } catch (error) {

        puzzleFeedback.textContent = error.message;

        answerInput.disabled = false;
        submitAnswer.disabled = false;

        answerInput.focus();

    } finally {

        puzzleLoading = false;

    }

});


/* ==========================================
   CONTINUE TO NEXT ROOM
========================================== */

continueButton.addEventListener('click', function() {
    window.location.href =
        'rooms.php?difficulty=' +
        encodeURIComponent(currentDifficulty);
});


/* ==========================================
   DRAW ROOM
========================================== */

function drawRoom() {

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    ctx.imageSmoothingEnabled = false;

    if (currentRoom === 1) {
        drawLivingRoom();
    } 
    else if (currentRoom === 2) {
        drawKitchen();
    } 
    else if (currentRoom === 3) {
        drawTreasureRoom();
    }

}

/* ==========================================
   RENDER GAME
========================================== */

function draw() {

    drawRoom();

    if (currentRoom !== 3) {
        drawSparkle();
    }

    drawGranny();
    drawPlayer();

}

/* ==========================================
   GAME LOOP
========================================== */

function gameLoop(timestamp) {

    const delta = lastTime === 0
        ? 0
        : Math.min((timestamp - lastTime) / 1000, 0.05);

    lastTime = timestamp;

    update(delta);

    draw();

    requestAnimationFrame(gameLoop);

}


/* ==========================================
   START GAME
========================================== */

draw();

requestAnimationFrame(gameLoop);

</script>

</body>
</html>
