
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

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
* { box-sizing: border-box; }

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
    width: min(1050px, 94%);
    margin: 28px auto;
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
    background: #ead9b9;
    outline: none;
    cursor: crosshair;
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
    animation: promptPulse 1.2s infinite alternate;
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

.modal-content h2 { color: #7354d6; }

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

#submitAnswer, #continueButton {
    padding: 12px 22px;
    border: none;
    border-radius: 9px;
    background: #7354d6;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

#continueButton { margin-top: 12px; }

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

@keyframes promptPulse {
    from { transform: scale(1); }
    to { transform: scale(1.02); }
}

@media (max-width: 600px) {
    .heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .heading h1 { font-size: 24px; }

    .controls { flex-direction: column; }
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

        <canvas id="gameCanvas" width="900" height="600" tabindex="0"
            aria-label="Ghost Heist game. Use WASD or arrow keys to move.">
        </canvas>

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

    <div id="interactionPrompt">✦ PRESS E OR CLICK INTERACT ✦</div>

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

            <button id="continueButton" type="button" style="display:none;">
                Continue
            </button>

        </div>
    </div>

    <footer>GHOST HEIST | Explore carefully. Stay out of sight.</footer>

</main>

<script>
/* GAME INITIALISATION */

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

const currentRoom = <?= (int) $room ?>;
const currentDifficulty = <?= json_encode($difficulty) ?>;

let puzzleLoading = false;
let challengeCompleted = false;
let puzzleOpened = false;

const keys = {};

const player = {
    x: 110,
    y: 480,
    radius: 17,
    speed: 230,
    direction: 'up'
};

const sparklingObject = { x: 755, y: 475 };
const INTERACTION_DISTANCE = 70;

let lastTime = 0;
let animationTime = 0;

const obstacles = [
    { x: 170, y: 110, w: 280, h: 110 },
    { x: 390, y: 320, w: 180, h: 95 },
    { x: 690, y: 90, w: 110, h: 180 },
    { x: 80, y: 270, w: 95, h: 120 }
];

/* KEY INVENTORY */

function updateKeyInventory(keysEarned) {
    document.getElementById('key1').style.display =
        keysEarned >= 1 ? 'inline-block' : 'none';

    document.getElementById('key2').style.display =
        keysEarned >= 2 ? 'inline-block' : 'none';
}

updateKeyInventory(currentRoom - 1);

/* KEYBOARD CONTROLS */

window.addEventListener('keydown', function(event) {
    const key = event.key.toLowerCase();

    if ([
        'arrowup', 'arrowdown', 'arrowleft', 'arrowright',
        'w', 'a', 's', 'd', 'e'
    ].includes(key)) {
        event.preventDefault();
    }

    if (key === 'e') {
        if (!event.repeat) interact();
        return;
    }

    keys[key] = true;
});

window.addEventListener('keyup', function(event) {
    keys[event.key.toLowerCase()] = false;
});

window.addEventListener('blur', function() {
    Object.keys(keys).forEach(key => keys[key] = false);
});

interactButton.addEventListener('click', interact);

/* INTERACTION DISTANCE */

function getInteractionDistance() {
    return Math.hypot(
        player.x - sparklingObject.x,
        player.y - sparklingObject.y
    );
}

function updateInteractionPrompt() {
    const nearby = getInteractionDistance() <= INTERACTION_DISTANCE
        && !puzzleOpened
        && !challengeCompleted;

    interactionPrompt.style.display = nearby ? 'block' : 'none';
    interactButton.style.display = nearby ? 'block' : 'none';
}

/* COLLISION DETECTION */

function collides(x, y) {
    const r = player.radius;

    if (
        x - r < 15 || x + r > 885 ||
        y - r < 15 || y + r > 585
    ) {
        return true;
    }

    return obstacles.some(obstacle =>
        x + r > obstacle.x &&
        x - r < obstacle.x + obstacle.w &&
        y + r > obstacle.y &&
        y - r < obstacle.y + obstacle.h
    );
}

/* PLAYER MOVEMENT */

function update(delta) {
    if (puzzleModal.style.display === 'flex') return;

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
        if (!collides(nextX, player.y)) player.x = nextX;

        const nextY = player.y + dy * distance;
        if (!collides(player.x, nextY)) player.y = nextY;
    }

    animationTime += delta;
    updateInteractionPrompt();
}

/* DRAW ROOM */

function drawRoom() {
    ctx.fillStyle = '#d9ad7c';
    ctx.fillRect(0, 0, 900, 600);

    for (let y = 20; y < 600; y += 65) {
        ctx.fillStyle = '#c99565';
        ctx.fillRect(15, y, 870, 2);

        for (let x = 80; x < 900; x += 190) {
            ctx.fillRect(x, y, 2, 65);
        }
    }

    ctx.fillStyle = '#fff0d8';
    ctx.fillRect(0, 0, 900, 18);
    ctx.fillRect(0, 0, 18, 600);
    ctx.fillRect(882, 0, 18, 600);
    ctx.fillRect(0, 582, 900, 18);

    ctx.fillStyle = '#d5b48f';
    ctx.fillRect(18, 18, 864, 12);

    ctx.fillStyle = '#9bd9ef';
    ctx.fillRect(485, 42, 125, 62);

    ctx.strokeStyle = '#ffffff';
    ctx.lineWidth = 7;
    ctx.strokeRect(485, 42, 125, 62);

    ctx.beginPath();
    ctx.moveTo(547, 44);
    ctx.lineTo(547, 102);
    ctx.moveTo(487, 73);
    ctx.lineTo(608, 73);
    ctx.stroke();

    ctx.fillStyle = '#b77a49';
    ctx.fillRect(815, 510, 40, 48);

    ctx.fillStyle = '#388d61';
    ctx.beginPath();
    ctx.arc(824, 495, 19, 0, Math.PI * 2);
    ctx.arc(847, 486, 19, 0, Math.PI * 2);
    ctx.arc(838, 470, 18, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#8b70cf';
    ctx.fillRect(235, 425, 390, 130);

    ctx.strokeStyle = '#f6d58c';
    ctx.lineWidth = 5;
    ctx.strokeRect(247, 437, 366, 106);

    ctx.strokeStyle = '#c5b5f1';
    ctx.lineWidth = 2;

    for (let x = 275; x < 600; x += 35) {
        ctx.beginPath();
        ctx.moveTo(x, 445);
        ctx.lineTo(x, 535);
        ctx.stroke();
    }

    drawFurniture(170, 110, 280, 110, '#65a995', 'SOFA');
    drawFurniture(390, 320, 180, 95, '#b98251', 'TABLE');
    drawFurniture(690, 90, 110, 180, '#b97a48', 'SHELF');
    drawFurniture(80, 270, 95, 120, '#d69a62', 'CABINET');

    ctx.fillStyle = '#98603d';
    ctx.fillRect(25, 455, 65, 127);

    ctx.strokeStyle = '#70452f';
    ctx.lineWidth = 5;
    ctx.strokeRect(25, 455, 65, 127);

    ctx.fillStyle = '#f8d36b';
    ctx.beginPath();
    ctx.arc(77, 520, 5, 0, Math.PI * 2);
    ctx.fill();
}

/* DRAW FURNITURE */

function drawFurniture(x, y, w, h, color, label) {
    ctx.fillStyle = 'rgba(60, 38, 25, 0.2)';
    ctx.fillRect(x + 8, y + 9, w, h);

    ctx.fillStyle = color;
    ctx.fillRect(x, y, w, h);

    ctx.strokeStyle = '#765039';
    ctx.lineWidth = 4;
    ctx.strokeRect(x, y, w, h);

    if (label === 'SOFA') {
        ctx.fillStyle = '#8ac7b3';
        ctx.fillRect(x + 12, y + 10, w - 24, 38);

        for (let i = 0; i < 3; i++) {
            ctx.fillStyle = '#4f9685';
            ctx.fillRect(x + 15 + i * 85, y + 55, 76, 42);
        }

        ctx.fillStyle = '#438a78';
        ctx.fillRect(x, y + 5, 20, h - 10);
        ctx.fillRect(x + w - 20, y + 5, 20, h - 10);
    }

    if (label === 'TABLE') {
        ctx.fillStyle = '#d9a66f';
        ctx.fillRect(x + 10, y + 10, w - 20, h - 20);

        ctx.fillStyle = '#f6e6b5';
        ctx.beginPath();
        ctx.arc(x + w / 2, y + h / 2, 15, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = '#ef9f9f';
        ctx.beginPath();
        ctx.arc(x + w / 2, y + h / 2 - 8, 7, 0, Math.PI * 2);
        ctx.fill();
    }

    if (label === 'SHELF') {
        const bookColors = ['#e87568', '#f0c35b', '#6a9ec8', '#80b78e'];

        for (let row = 0; row < 3; row++) {
            for (let col = 0; col < 3; col++) {
                ctx.fillStyle = bookColors[(row + col) % bookColors.length];
                ctx.fillRect(x + 12 + col * 28, y + 12 + row * 52, 19, 40);
            }
        }
    }

    if (label === 'CABINET') {
        ctx.fillStyle = '#f0c28e';
        ctx.fillRect(x + 9, y + 10, w - 18, h - 20);

        ctx.fillStyle = '#81583a';
        ctx.fillRect(x + w / 2 - 3, y + 45, 6, 6);
        ctx.fillRect(x + w / 2 - 3, y + 75, 6, 6);
    }
}

/* SPARKLING OBJECT */

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

        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    }

    ctx.closePath();
    ctx.fillStyle = '#ffffff';
    ctx.fill();
    ctx.restore();
}

/* PLAYER */

function drawPlayer() {
    const x = player.x;
    const y = player.y;

    ctx.fillStyle = 'rgba(40, 25, 35, 0.22)';
    ctx.beginPath();
    ctx.ellipse(x, y + 16, 19, 9, 0, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#33334d';
    ctx.fillRect(x - 10, y + 5, 8, 15);
    ctx.fillRect(x + 3, y + 5, 8, 15);

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(x - 13, y + 17, 12, 6);
    ctx.fillRect(x + 2, y + 17, 12, 6);

    ctx.fillStyle = '#e7a843';
    ctx.fillRect(x - 16, y - 5, 9, 19);

    ctx.fillStyle = '#7452d4';
    ctx.beginPath();

    if (typeof ctx.roundRect === 'function') {
        ctx.roundRect(x - 12, y - 5, 24, 25, 7);
    } else {
        ctx.rect(x - 12, y - 5, 24, 25);
    }

    ctx.fill();

    ctx.fillStyle = '#f4c49d';
    ctx.beginPath();
    ctx.arc(x, y - 12, 13, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#392a36';
    ctx.beginPath();
    ctx.arc(x, y - 17, 13, Math.PI, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#29243b';

    if (player.direction === 'left') {
        ctx.fillRect(x - 8, y - 14, 3, 4);
    } else if (player.direction === 'right') {
        ctx.fillRect(x + 5, y - 14, 3, 4);
    } else {
        ctx.fillRect(x - 5, y - 14, 3, 4);
        ctx.fillRect(x + 4, y - 14, 3, 4);
    }

    ctx.strokeStyle = '#9b5146';
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.arc(x, y - 7, 4, 0.2, Math.PI - 0.2);
    ctx.stroke();
}

/* LOAD PUZZLE */

async function interact() {
    if (puzzleLoading || puzzleOpened || challengeCompleted) return;

    if (getInteractionDistance() > INTERACTION_DISTANCE) {
        message.textContent =
            'You are too far away! Move closer to the sparkling object.';
        return;
    }

    puzzleLoading = true;
    message.textContent = 'You discovered the hidden object! Loading puzzle...';

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

/* SUBMIT PUZZLE ANSWER */

answerForm.addEventListener('submit', async function(event) {
    event.preventDefault();

    if (puzzleLoading || challengeCompleted) return;

    const answer = answerInput.value.trim();

    if (!/^\d{1,3}$/.test(answer)) {
        puzzleFeedback.textContent = 'Please enter a valid answer.';
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
            body: new URLSearchParams({ answer: answer })
        });

        const data = await response.json();

        /*
         * IMPORTANT:
         * An incorrect answer should return:
         * HTTP 200
         * { "success": true, "correct": false, "message": "Incorrect answer. Try again." }
         *
         * A correct answer should return:
         * { "success": true, "correct": true, "message": "...", "keysEarned": 1 }
         */

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Could not submit your answer.');
        }

        if (data.correct === false) {
            puzzleFeedback.textContent =
                data.message || 'Incorrect answer! Please try again.';

            // Re-enable the answer field and button.
            answerInput.value = '';
            answerInput.disabled = false;
            submitAnswer.disabled = false;

            // Keep the puzzle open for another attempt.
            answerInput.focus();

            return;
        }

        if (data.correct !== true) {
            throw new Error('Unexpected response from the server.');
        }

        challengeCompleted = true;
        answerForm.style.display = 'none';

        if (currentRoom === 1 || currentRoom === 2) {
            const keysEarned = Number(data.keysEarned) || currentRoom;

            updateKeyInventory(keysEarned);

            puzzleTitle.textContent = '🔑 You Found a Wooden Key!';

            puzzleDescription.textContent = data.message ||
                `You earned Wooden Key ${currentRoom}!`;

            puzzleFeedback.textContent =
                'Your key has been collected successfully!';

            continueButton.textContent = currentRoom === 1
                ? 'Unlock the Kitchen'
                : 'Enter the Treasure Room';

            continueButton.style.display = 'inline-block';
            message.textContent = data.message || 'You collected a wooden key!';

        } else {
            puzzleTitle.textContent = '🏆 TREASURE UNLOCKED!';

            puzzleDescription.textContent =
                'Congratulations! You completed all three rooms of Ghost Heist!';

            puzzleFeedback.textContent =
                data.message || 'You successfully found the treasure!';

            continueButton.textContent = 'Return to Room Map';
            continueButton.style.display = 'inline-block';

            message.textContent = 'You completed Ghost Heist!';
        }

    } catch (error) {
        puzzleFeedback.textContent = error.message;

        // Allow another submission after a temporary error.
        answerInput.disabled = false;
        submitAnswer.disabled = false;
        answerInput.focus();

    } finally {
        puzzleLoading = false;
    }
});

/* CONTINUE TO NEXT ROOM */

continueButton.addEventListener('click', function() {
    if (currentRoom === 3) {
        window.location.href = 'rooms.php';
        return;
    }

    const nextRoom = currentRoom + 1;

    window.location.href =
        'stealth.php?difficulty=' +
        encodeURIComponent(currentDifficulty) +
        '&room=' + nextRoom;
});

/* RENDER GAME */

function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    drawRoom();
    drawSparkle();
    drawPlayer();
}

/* GAME LOOP */

function gameLoop(timestamp) {
    const delta = lastTime === 0
        ? 0
        : Math.min((timestamp - lastTime) / 1000, 0.05);

    lastTime = timestamp;

    update(delta);
    draw();

    requestAnimationFrame(gameLoop);
}

/* START GAME */

draw();
requestAnimationFrame(gameLoop);

</script>

</body>
</html>
