
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
<title>Ghost Heist | <?= htmlspecialchars($roomName) ?></title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: #f5f0e8;
    color: #29243b;
    font-family: Arial, sans-serif;
}

header {
    padding: 15px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    box-shadow: 0 3px 12px #00000012;
}

.logo {
    font-size: 23px;
    font-weight: 900;
    color: #7354d6;
}

header a {
    color: #7354d6;
    text-decoration: none;
    font-weight: bold;
}

main {
    width: min(1000px, 94%);
    margin: 25px auto;
}

.heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 18px;
}

.heading h1 {
    margin: 0 0 6px;
}

.heading p {
    margin: 0;
    color: #777;
}

.difficulty {
    background: #e9e2ff;
    color: #6746c7;
    padding: 9px 15px;
    border-radius: 20px;
    font-weight: bold;
}

.game-container {
    padding: 12px;
    background: white;
    border-radius: 18px;
    box-shadow: 0 8px 30px #00000012;
}

canvas {
    display: block;
    width: 100%;
    height: auto;
    border-radius: 12px;
    background: #ead9b9;
}

.controls {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 15px 5px 5px;
    color: #655c72;
    font-size: 14px;
}

kbd {
    background: #eee9f8;
    color: #6044b7;
    padding: 4px 8px;
    border-radius: 5px;
    font-weight: bold;
}

#message {
    margin-top: 14px;
    padding: 13px;
    background: #eee9ff;
    border-radius: 10px;
    color: #6044b7;
    font-weight: bold;
    text-align: center;
    min-height: 44px;
}

@media (max-width: 600px) {
    .heading {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">GHOST HEIST</div>
    <a href="rooms.php">← Room Map</a>
</header>

<main>

    <section class="heading">
        <div>
            <h1><?= htmlspecialchars($roomName) ?></h1>
            <p>Explore the room and find the sparkling object.</p>
        </div>

        <span class="difficulty">
            <?= htmlspecialchars(ucfirst($difficulty)) ?> Mode
        </span>
    </section>

    <section class="game-container">

        <canvas id="gameCanvas" width="900" height="600"></canvas>

        <div class="controls">
            <span>Move: <kbd>W</kbd> <kbd>A</kbd> <kbd>S</kbd> <kbd>D</kbd> or arrow keys</span>
            <span>Interact: <kbd>E</kbd></span>
        </div>

    </section>

    <div id="message">Explore the living room!</div>

</main>

<script>
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const message = document.getElementById('message');

const keys = {};

const player = {
    x: 110,
    y: 480,
    radius: 17,
    speed: 230,
    direction: 'up'
};

// Furniture collision areas.
const obstacles = [
    { x: 170, y: 110, w: 280, h: 110 },
    { x: 390, y: 320, w: 180, h: 95 },
    { x: 690, y: 90, w: 110, h: 180 },
    { x: 80, y: 270, w: 95, h: 120 }
];

const sparklingObject = {
    x: 755,
    y: 475
};

let lastTime = 0;
let animationTime = 0;

document.addEventListener('keydown', (event) => {
    const key = event.key.toLowerCase();

    if ([
        'arrowup', 'arrowdown', 'arrowleft', 'arrowright',
        'w', 'a', 's', 'd', 'e'
    ].includes(key)) {
        event.preventDefault();
    }

    keys[key] = true;

    if (key === 'e') {
        interact();
    }
});

document.addEventListener('keyup', (event) => {
    keys[event.key.toLowerCase()] = false;
});

function collides(x, y) {
    const r = player.radius;

    // Room boundaries.
    if (
        x - r < 15 ||
        x + r > 885 ||
        y - r < 15 ||
        y + r > 585
    ) {
        return true;
    }

    // Furniture.
    return obstacles.some(obstacle => {
        return (
            x + r > obstacle.x &&
            x - r < obstacle.x + obstacle.w &&
            y + r > obstacle.y &&
            y - r < obstacle.y + obstacle.h
        );
    });
}

function update(delta) {
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
        }

        const nextY = player.y + dy * distance;

        if (!collides(player.x, nextY)) {
            player.y = nextY;
        }
    }

    animationTime += delta;
}

function drawRoom() {
    // Floor.
    ctx.fillStyle = '#e8cda5';
    ctx.fillRect(0, 0, 900, 600);

    // Floorboard lines.
    ctx.strokeStyle = '#d8b98e';
    ctx.lineWidth = 2;

    for (let y = 30; y < 600; y += 65) {
        ctx.beginPath();
        ctx.moveTo(15, y);
        ctx.lineTo(885, y);
        ctx.stroke();
    }

    // Walls.
    ctx.fillStyle = '#f6e9d5';
    ctx.fillRect(0, 0, 900, 15);
    ctx.fillRect(0, 0, 15, 600);
    ctx.fillRect(885, 0, 15, 600);
    ctx.fillRect(0, 585, 900, 15);

    // Rug.
    ctx.fillStyle = '#b5a0e9';
    ctx.fillRect(240, 430, 380, 120);

    ctx.strokeStyle = '#8068c4';
    ctx.lineWidth = 5;
    ctx.strokeRect(250, 440, 360, 100);

    // Sofa.
    drawFurniture(170, 110, 280, 110, '#70b7a0', 'SOFA');

    // Coffee table.
    drawFurniture(390, 320, 180, 95, '#bd895b', 'TABLE');

    // Bookshelf.
    drawFurniture(690, 90, 110, 180, '#c58c55', 'SHELF');

    // Cabinet.
    drawFurniture(80, 270, 95, 120, '#e6a66d', 'CABINET');
}

function drawFurniture(x, y, w, h, color, label) {
    ctx.fillStyle = '#00000018';
    ctx.fillRect(x + 6, y + 8, w, h);

    ctx.fillStyle = color;
    ctx.fillRect(x, y, w, h);

    ctx.strokeStyle = '#72523f';
    ctx.lineWidth = 4;
    ctx.strokeRect(x, y, w, h);

    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 15px Arial';
    ctx.textAlign = 'center';
    ctx.fillText(label, x + w / 2, y + h / 2 + 5);
}

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
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 26px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('✦', 0, 9);

    ctx.restore();
}

function drawPlayer() {
    const x = player.x;
    const y = player.y;

    // Shadow.
    ctx.fillStyle = '#00000025';
    ctx.beginPath();
    ctx.ellipse(x, y + 15, 18, 8, 0, 0, Math.PI * 2);
    ctx.fill();

    // Body.
    ctx.fillStyle = '#7954d8';
    ctx.beginPath();
    ctx.arc(x, y + 7, 14, 0, Math.PI * 2);
    ctx.fill();

    // Head.
    ctx.fillStyle = '#f3c49d';
    ctx.beginPath();
    ctx.arc(x, y - 9, 13, 0, Math.PI * 2);
    ctx.fill();

    // Hair.
    ctx.fillStyle = '#382a3d';
    ctx.beginPath();
    ctx.arc(x, y - 14, 13, Math.PI, Math.PI * 2);
    ctx.fill();

    // Eyes.
    ctx.fillStyle = '#29243b';

    if (player.direction === 'left') {
        ctx.fillRect(x - 7, y - 10, 3, 3);
    } else if (player.direction === 'right') {
        ctx.fillRect(x + 4, y - 10, 3, 3);
    } else {
        ctx.fillRect(x - 5, y - 10, 3, 3);
        ctx.fillRect(x + 4, y - 10, 3, 3);
    }
}

function interact() {
    const distance = Math.hypot(
        player.x - sparklingObject.x,
        player.y - sparklingObject.y
    );

    if (distance < 55) {
        message.textContent =
            'You found the mysterious object! Puzzle interaction comes next.';
    } else {
        message.textContent =
            'Move closer to the sparkling object to interact.';
    }
}

function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    drawRoom();
    drawSparkle();
    drawPlayer();
}

function gameLoop(timestamp) {
    const delta = Math.min((timestamp - lastTime) / 1000, 0.05);
    lastTime = timestamp;

    update(delta);
    draw();

    requestAnimationFrame(gameLoop);
}

draw();
requestAnimationFrame(gameLoop);
</script>

</body>
</html>
