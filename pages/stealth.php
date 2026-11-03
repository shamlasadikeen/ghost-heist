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
    min-height: 24px;
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
            <strong id="timerValue">01:00</strong>
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
        <span id="key1" class="key-item">🔑 Wooden Key 1</span>
        <span id="key2" class="key-item">🔑 Wooden Key 2</span>
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
    { x: 620, y: 455 },
    { x: 620, y: 250 },
    { x: 350, y: 250 }
];

let grannyPatrolIndex = 1;

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

    if (!tilesLoaded) {
        drawFallbackFloor();
        return;
    }

    for (let row = 0; row < GRID_ROWS; row++) {

        for (let column = 0; column < GRID_COLUMNS; column++) {

            drawTile(
                floorMap[row][column],
                12,
                column * DRAW_TILE_SIZE,
                row * DRAW_TILE_SIZE
            );

        }
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

function drawLivingRoom() {

    // Background floor.
    drawFloorLayer();

    // Room structure.
    drawWalls();

    // Rug.
    drawRug();

    // Decorations.
    drawPlant(790, 190, 1.05);
    drawPlant(190, 405, 0.75);
    drawFloorLamp();

    // Furniture.
    drawBookshelf();
    drawSofa();
    drawTelevision();
    drawSideTable();
    drawCoffeeTable();
    drawArmchair();

}


/* ==========================================
   COMPLETE KITCHEN
========================================== */

function drawKitchen() {

    // Kitchen floor.
    ctx.fillStyle = '#e9dfc9';
    ctx.fillRect(0, 0, 900, 600);

    // Floor tiles.
    ctx.strokeStyle = 'rgba(160, 145, 120, 0.25)';
    ctx.lineWidth = 2;

    for (let x = 35; x < 870; x += 55) {
        ctx.beginPath();
        ctx.moveTo(x, 100);
        ctx.lineTo(x, 535);
        ctx.stroke();
    }

    for (let y = 105; y < 535; y += 55) {
        ctx.beginPath();
        ctx.moveTo(35, y);
        ctx.lineTo(865, y);
        ctx.stroke();
    }

    // Room walls.
    drawWalls();

    // Back kitchen cabinets.
    roundedRect(90, 105, 700, 100, 12, '#a85e38', '#693a27');

    // Cabinet doors.
    for (let x = 105; x < 770; x += 95) {
        roundedRect(x, 120, 80, 68, 6, '#e9b879', '#80502f');
        ctx.fillStyle = '#80502f';
        ctx.beginPath();
        ctx.arc(x + 68, 154, 4, 0, Math.PI * 2);
        ctx.fill();
    }

    // Countertop.
    roundedRect(78, 96, 724, 22, 6, '#f4f0e7', '#a28e78');

    // Refrigerator.
    roundedRect(90, 235, 115, 205, 12, '#dce8ed', '#71828a');
    roundedRect(99, 244, 97, 85, 7, '#f5fafb', '#9aaeb5');
    roundedRect(99, 335, 97, 94, 7, '#f5fafb', '#9aaeb5');

    ctx.fillStyle = '#71828a';
    roundedRect(185, 265, 5, 48, 3, '#71828a');
    roundedRect(185, 350, 5, 48, 3, '#71828a');

    // Stove and oven.
    roundedRect(625, 235, 165, 170, 10, '#d6d9d8', '#707979');
    roundedRect(640, 248, 135, 95, 7, '#f5f5f0', '#858b89');

    // Stove burners.
    const burners = [
        [670, 276], [745, 276],
        [670, 316], [745, 316]
    ];

    burners.forEach(([x, y]) => {
        ctx.beginPath();
        ctx.arc(x, y, 13, 0, Math.PI * 2);
        ctx.fillStyle = '#454b4b';
        ctx.fill();

        ctx.beginPath();
        ctx.arc(x, y, 6, 0, Math.PI * 2);
        ctx.fillStyle = '#aeb5b2';
        ctx.fill();
    });

    // Oven window.
    roundedRect(646, 355, 122, 38, 5, '#41494b', '#7a8585');

    // Kitchen island.
    roundedRect(300, 315, 260, 125, 14, '#9b5935', '#633a27');
    roundedRect(290, 300, 280, 32, 10, '#f4f0e7', '#a28e78');

    // Fruit bowl.
    ctx.beginPath();
    ctx.ellipse(430, 286, 45, 13, 0, 0, Math.PI * 2);
    ctx.fillStyle = '#b96c32';
    ctx.fill();

    // Fruits.
    const fruits = [
        [415, 278, '#d94a37'],
        [435, 274, '#e6b83f'],
        [450, 280, '#5c9b50']
    ];

    fruits.forEach(([x, y, color]) => {
        ctx.beginPath();
        ctx.arc(x, y, 10, 0, Math.PI * 2);
        ctx.fillStyle = color;
        ctx.fill();
    });

    // Kitchen rug.
    roundedRect(330, 465, 210, 48, 8, '#8bb5a2', '#527d6a');

    // Rug stripes.
    ctx.strokeStyle = '#e9dfc9';
    ctx.lineWidth = 4;

    for (let x = 350; x < 530; x += 25) {
        ctx.beginPath();
        ctx.moveTo(x, 470);
        ctx.lineTo(x, 508);
        ctx.stroke();
    }

    // Small wall clock.
    ctx.beginPath();
    ctx.arc(450, 68, 20, 0, Math.PI * 2);
    ctx.fillStyle = '#fff5df';
    ctx.fill();
    ctx.strokeStyle = '#80502f';
    ctx.lineWidth = 4;
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(450, 68);
    ctx.lineTo(450, 55);
    ctx.moveTo(450, 68);
    ctx.lineTo(460, 73);
    ctx.stroke();
}


/* ==========================================
   FURNITURE COLLISION
========================================== */

const obstacles = [
    // Sofa.
    {x: 125, y: 135, w: 235, h: 112},

    // Bookshelf.
    {x: 78, y: 276, w: 82, h: 156},

    // TV cabinet and television.
    {x: 655, y: 76, w: 158, h: 102},

    // Side table.
    {x: 390, y: 150, w: 75, h: 55},

    // Coffee table.
    {x: 390, y: 310, w: 150, h: 80},

    // Armchair.
    {x: 665, y: 310, w: 112, h: 105},

    // Floor lamp base.
    {x: 564, y: 210, w: 42, h: 22},

    
    // Plants.
    {x: 770, y: 180, w: 40, h: 40},
    {x: 175, y: 395, w: 30, h: 30}
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
    { x: 290, y: 300, w: 280, h: 140 }

];




/* ==========================================
   GAME OBJECTS
========================================== */

const sparklingObject = {
    x: currentRoom === 2 ? 240 : 755,
    y: currentRoom === 2 ? 250 : 475
};

const INTERACTION_DISTANCE = 70;

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


/* ==========================================
   INTERACTION DISTANCE
========================================== */

function getInteractionDistance() {

    return Math.hypot(
        player.x - sparklingObject.x,
        player.y - sparklingObject.y
    );

}


function updateInteractionPrompt() {

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

    // Keep the player inside the actual room.
    if (
        x - r < 64 ||
        x + r > 836 ||
        y - r < 96 ||
        y + r > 528
    ) {
        return true;
    }

    // Furniture collision.
    const roomObstacles =
    currentRoom === 2 ? kitchenObstacles : obstacles;

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
const target = grannyPatrolPoints[grannyPatrolIndex];

const moveX = target.x - granny.x;
const moveY = target.y - granny.y;

const distance = Math.hypot(moveX, moveY);
const movement = granny.speed * delta;

if (distance <= movement) {

    granny.x = target.x;
    granny.y = target.y;

    grannyPatrolIndex =
        (grannyPatrolIndex + 1) % grannyPatrolPoints.length;

} else if (distance > 0) {

    granny.x += (moveX / distance) * movement;
    granny.y += (moveY / distance) * movement;
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

    const pulse = 1 + Math.sin(animationTime * 5) * 0.15;

    ctx.save();

    ctx.translate(sparklingObject.x, sparklingObject.y);
    ctx.scale(pulse, pulse);

    ctx.fillStyle = '#ffe65b';
    ctx.shadowColor = '#ffca28';
    ctx.shadowBlur = 25;

    ctx.beginPath();
    ctx.arc(0, 0, 15, 0, Math.PI * 2);
    ctx.fill();

    ctx.shadowBlur = 0;

    ctx.beginPath();

    for (let i = 0; i < 8; i++) {

        const angle = (Math.PI / 4) * i - Math.PI / 2;
        const radius = i % 2 === 0 ? 12 : 5;

        const x = Math.cos(angle) * radius;
        const y = Math.sin(angle) * radius;

        if (i === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    }

    ctx.closePath();

    ctx.fillStyle = '#ffffff';
    ctx.fill();

    ctx.restore();
}

function drawGranny() {

    const x = granny.x;
    const y = granny.y;

    ctx.save();

    // Granny's vision cone
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

    // Shadow
    ctx.fillStyle = 'rgba(0,0,0,0.3)';
    ctx.beginPath();
    ctx.ellipse(x, y + 13, 17, 7, 0, 0, Math.PI * 2);
    ctx.fill();

    // Dress
    ctx.fillStyle = '#49304f';
    ctx.beginPath();
    ctx.ellipse(x, y + 5, 14, 17, 0, 0, Math.PI * 2);
    ctx.fill();

    // Face
    ctx.fillStyle = '#f0c6a5';
    ctx.beginPath();
    ctx.arc(x, y - 13, 10, 0, Math.PI * 2);
    ctx.fill();

    // Grey hair
    ctx.fillStyle = '#d5d2d0';
    ctx.beginPath();
    ctx.arc(x, y - 19, 10, Math.PI, Math.PI * 2);
    ctx.fill();

    // Hair bun
    ctx.beginPath();
    ctx.arc(x + 7, y - 23, 5, 0, Math.PI * 2);
    ctx.fill();

    // Eyes
    ctx.fillStyle = '#25202a';
    ctx.beginPath();
    ctx.arc(x - 4, y - 14, 1.5, 0, Math.PI * 2);
    ctx.arc(x + 4, y - 14, 1.5, 0, Math.PI * 2);
    ctx.fill();

    // Mouth
    ctx.strokeStyle = '#8b4141';
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.arc(x, y - 8, 3, 0, Math.PI);
    ctx.stroke();

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
        x - 24,
        y - 28,
        48,
        48
    );
}


/* ==========================================
   LOAD PUZZLE
========================================== */

async function interact() {

    if (puzzleLoading || puzzleOpened || challengeCompleted) {
        return;
    }

    if (getInteractionDistance() > INTERACTION_DISTANCE) {

        message.textContent =
            'You are too far away! Move closer to the sparkling object.';

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

            puzzleTitle.textContent =
                '🏆 TREASURE UNLOCKED!';

            puzzleDescription.textContent =
                'Congratulations! You completed all three rooms of Ghost Heist!';

            puzzleFeedback.textContent =
                data.message || 'You successfully found the treasure!';

            continueButton.textContent =
                'Return to Room Map';

            continueButton.style.display = 'inline-block';

            message.textContent =
                'You completed Ghost Heist!';
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
        drawKitchen(); // Temporary until we design the treasure room.
    }

}

/* ==========================================
   RENDER GAME
========================================== */

function draw() {

    drawRoom();

    drawSparkle();

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
