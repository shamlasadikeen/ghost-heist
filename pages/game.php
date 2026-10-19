
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$difficulty = $_GET['difficulty'] ?? 'beginner';

$levels = [
    'beginner' => 'Beginner',
    'intermediate' => 'Intermediate',
    'hard' => 'Hard'
];

if (!is_string($difficulty) || !isset($levels[$difficulty])) {
    $difficulty = 'beginner';
}

$room = filter_input(INPUT_GET, 'room', FILTER_VALIDATE_INT);

if ($room === false || $room === null || $room < 1 || $room > 5) {
    $room = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haunted Mansion | Ghost Heist</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #100d18;
            color: #f5f0ff;
            font-family: Arial, sans-serif;
        }

        .game-shell {
            width: min(1000px, 92%);
            margin: auto;
            padding: 28px 0 50px;
        }

        .game-nav,
        .game-stats {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .game-nav a,
        .game-link {
            color: #c4b5fd;
            text-decoration: none;
        }

        .game-nav a:hover,
        .game-link:hover {
            color: #ffffff;
        }

        .game-title {
            text-align: center;
            margin: 45px 0 30px;
        }

        .game-title h1 {
            font-size: clamp(2rem, 5vw, 3.2rem);
            margin-bottom: 10px;
        }

        .game-title p,
        .muted {
            color: #b8b1c6;
        }

        .game-stats {
            padding: 16px 20px;
            border: 1px solid #393047;
            border-radius: 14px;
            background: #171321;
        }

        .game-card {
            margin-top: 25px;
            padding: clamp(20px, 5vw, 40px);
            background: #171321;
            border: 1px solid #393047;
            border-radius: 20px;
            text-align: center;
        }

        .room-number {
            color: #c4b5fd;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .puzzle-image {
            display: block;
            width: 100%;
            max-width: 420px;
            max-height: 360px;
            object-fit: contain;
            margin: 25px auto;
            border-radius: 12px;
            background: #fff;
            min-height: 180px;
        }

        .answer-form {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .answer-form input {
            width: min(220px, 100%);
            padding: 14px;
            box-sizing: border-box;
            border: 1px solid #494057;
            border-radius: 9px;
            background: #100d18;
            color: white;
            font-size: 1rem;
        }

        .primary-button {
            display: inline-block;
            padding: 14px 22px;
            border: none;
            border-radius: 9px;
            background: #a78bfa;
            color: #171321;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .primary-button:hover {
            background: #c4b5fd;
            transform: translateY(-1px);
        }

        .primary-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            min-height: 28px;
            margin-top: 20px;
            font-weight: bold;
            line-height: 1.6;
        }

        .message.error {
            color: #ff9da9;
        }

        .message.success {
            color: #86efac;
        }

        .progress-track {
            height: 8px;
            margin-top: 25px;
            background: #393047;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            width: 20%;
            background: #a78bfa;
            transition: width 0.3s ease;
        }

        @media (max-width: 500px) {
            .game-title {
                margin-top: 30px;
            }

            .answer-form {
                flex-direction: column;
            }

            .answer-form input,
            .answer-form button {
                width: 100%;
            }

            .game-stats {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
<main class="game-shell">

    <nav class="game-nav">
        <a href="rooms.php?difficulty=<?= urlencode($difficulty) ?>">
            &larr; Mansion Map
        </a>

        <a href="../logout.php">Exit Game</a>
    </nav>

    <header class="game-title">
        <div aria-hidden="true">👻 🏚️ 👻</div>
        <h1>The Haunted Mansion</h1>
        <p>Every puzzle brings you closer to the hidden treasure.</p>
    </header>

    <section class="game-stats">
        <span>
            Difficulty:
            <strong id="difficulty">
                <?= htmlspecialchars($levels[$difficulty]) ?>
            </strong>
        </span>

        <span>
            Room:
            <strong id="room-number"><?= $room ?> / 5</strong>
        </span>

        <span>
            Coins earned:
            <strong id="coins">0</strong> 🪙
        </span>
    </section>

    <section class="game-card">

        <div class="room-number" id="room-label">
            Room <?= $room ?> —
            <?= htmlspecialchars([
                1 => 'The Forgotten Entrance',
                2 => 'The Whispering Hall',
                3 => 'The Shadow Library',
                4 => 'The Ghost Chamber',
                5 => 'The Treasure Vault'
            ][$room]) ?>
        </div>

        <h2>Solve the Banana Puzzle</h2>

        <p class="muted">
            Study the image and enter the missing number.
        </p>

        <div
            id="puzzle-status"
            class="muted"
            role="status"
            aria-live="polite"
        >
            Loading your puzzle...
        </div>

        <img
            id="puzzle-image"
            class="puzzle-image"
            alt="Banana number puzzle"
            hidden
        >

        <form id="answer-form" class="answer-form">
            <input
                type="number"
                id="answer"
                name="answer"
                min="0"
                max="999"
                placeholder="Your answer"
                aria-label="Your puzzle answer"
                required
                disabled
            >

            <button
                class="primary-button"
                id="submit-button"
                type="submit"
                disabled
            >
                Unlock the Door
            </button>
        </form>

        <div
            id="message"
            class="message"
            role="status"
            aria-live="polite"
        ></div>

        <a
            id="map-button"
            class="primary-button"
            href="rooms.php?difficulty=<?= urlencode($difficulty) ?>"
            style="display: none;"
        >
            Return to Mansion Map
        </a>

        <div class="progress-track" aria-label="Room progress">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

    </section>
</main>

<script>
const difficulty = <?= json_encode($difficulty) ?>;
let currentRoom = <?= json_encode($room) ?>;

const puzzleImage = document.getElementById('puzzle-image');
const puzzleStatus = document.getElementById('puzzle-status');
const answerInput = document.getElementById('answer');
const submitButton = document.getElementById('submit-button');
const answerForm = document.getElementById('answer-form');
const message = document.getElementById('message');
const coinsDisplay = document.getElementById('coins');
const mapButton = document.getElementById('map-button');

let loading = false;

const roomNames = [
    'The Forgotten Entrance',
    'The Whispering Hall',
    'The Shadow Library',
    'The Ghost Chamber',
    'The Treasure Vault'
];

function updateRoomDisplay() {
    document.getElementById('room-number').textContent =
        `${currentRoom} / 5`;

    document.getElementById('room-label').textContent =
        `Room ${currentRoom} — ${roomNames[currentRoom - 1]}`;

    document.getElementById('progress-fill').style.width =
        `${currentRoom * 20}%`;
}

async function loadPuzzle() {
    loading = true;

    puzzleStatus.hidden = false;
    puzzleStatus.textContent = 'Summoning a puzzle...';

    puzzleImage.hidden = true;
    puzzleImage.removeAttribute('src');

    answerInput.disabled = true;
    answerInput.value = '';

    submitButton.disabled = true;
    answerForm.hidden = false;
    mapButton.style.display = 'none';

    message.textContent = '';
    message.className = 'message';

    updateRoomDisplay();

    try {
        const response = await fetch(
            `../api/get_puzzle.php?difficulty=${encodeURIComponent(difficulty)}&room=${currentRoom}`,
            { cache: 'no-store' }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || 'Could not load a puzzle.'
            );
        }

        puzzleImage.onload = () => {
            puzzleStatus.hidden = true;
            puzzleImage.hidden = false;

            answerInput.disabled = false;
            submitButton.disabled = false;

            answerInput.focus();
        };

        puzzleImage.onerror = () => {
            puzzleStatus.hidden = false;
            puzzleStatus.textContent =
                'The puzzle image could not load. Please try reloading the page.';

            answerInput.disabled = true;
            submitButton.disabled = true;
        };

        puzzleImage.src = data.question;

    } catch (error) {
        puzzleStatus.hidden = false;
        puzzleStatus.textContent = error.message;

        answerInput.disabled = true;
        submitButton.disabled = true;

    } finally {
        loading = false;
    }
}

answerForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (loading || answerInput.disabled) {
        return;
    }

    const answer = answerInput.value.trim();

    if (answer === '') {
        message.textContent = 'Enter your answer first.';
        message.className = 'message error';
        return;
    }

    loading = true;
    submitButton.disabled = true;

    message.textContent = 'Checking your answer...';
    message.className = 'message';

    try {
        const body = new URLSearchParams({
            answer: answer
        });

        const response = await fetch('../api/submit_answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || 'Could not check your answer.'
            );
        }

        if (!data.correct) {
            message.textContent = data.message;
            message.className = 'message error';

            answerInput.select();
            return;
        }

        // The server awards the coins and saves room progress.
        // Do not award coins again in the browser.
        message.textContent = data.message ||
            'Room completed! You earned 100 coins!';

        message.className = 'message success';

        answerInput.disabled = true;
        submitButton.disabled = true;

        // Stop the player from moving directly to another puzzle.
        answerForm.hidden = true;
        puzzleImage.hidden = true;

        // Return to the room-selection screen after success.
        mapButton.style.display = 'inline-block';

        if (currentRoom === 5) {
            document.getElementById('room-label').textContent =
                'Treasure Found!';

            puzzleStatus.hidden = false;
            puzzleStatus.textContent =
                '🏆 You completed all five rooms and found the treasure!';

            document.getElementById('progress-fill').style.width = '100%';

            mapButton.textContent = 'Return to Mansion Map';
        } else {
            puzzleStatus.hidden = false;
            puzzleStatus.textContent =
                'Room completed! Return to the mansion map to continue.';
        }

    } catch (error) {
        message.textContent = error.message;
        message.className = 'message error';

    } finally {
        loading = false;

        if (!answerInput.disabled && !answerForm.hidden) {
            submitButton.disabled = false;
        }
    }
});

updateRoomDisplay();
loadPuzzle();
</script>
</body>
</html>
