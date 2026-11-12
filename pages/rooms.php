
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$userId = (int) $_SESSION['user_id'];
// Ghost Heist now has one fixed gameplay mode.
$difficulty = 'beginner';

/*
 * Three rooms for Ghost Heist.
 * Room progression and database functionality are preserved.
 */
$rooms = [
    1 => [
        'name' => 'The Living Room',
        'description' => 'Sneak around the living room, avoid Granny and discover the hidden object.',
        'emoji' => '🛋️',
        'tag' => 'THE FIRST MISSION',
        'background' => 'living'
    ],
    2 => [
        'name' => 'The Kitchen',
        'description' => 'Watch out for the guard! Explore the kitchen and find your next key.',
        'emoji' => '🍳',
        'tag' => 'THE SECOND MISSION',
        'background' => 'kitchen'
    ],
    3 => [
        'name' => 'The Treasure Room',
        'description' => 'Your final challenge! Find the chest, solve the puzzle and grab the treasure.',
        'emoji' => '💎',
        'tag' => 'THE FINAL MISSION',
        'background' => 'treasure'
    ]
];

try {
    /*
     * Load completed rooms for the selected difficulty.
     */
    $stmt = $pdo->prepare(
        'SELECT room_number
         FROM room_progress_v2
         WHERE user_id = ? AND difficulty = ?'
    );

    $stmt->execute([$userId, $difficulty]);

    $completedRooms = array_values(array_unique(array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    )));

    /*
     * Preserve the existing coin balance.
     */
   

} catch (PDOException $e) {
    error_log('Ghost Heist room map error: ' . $e->getMessage());

    http_response_code(500);
    exit('Could not load your game progress. Please try again.');
}

/*
 * Unlock only the first incomplete room.
 */
$nextRoom = null;

foreach (array_keys($rooms) as $roomNumber) {
    if (!in_array($roomNumber, $completedRooms, true)) {
        $nextRoom = $roomNumber;
        break;
    }
}

$completedCount = count(array_intersect(
    array_keys($rooms),
    $completedRooms
));

$allCompleted = $completedCount === count($rooms);
$username = $_SESSION['username'] ?? 'Heister';

$progressPercentage = (int) round(
    ($completedCount / count($rooms)) * 100
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fff7e8">

    <title>Choose Your Room | Ghost Heist</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --purple: #7046c8;
            --purple-dark: #50309b;
            --yellow: #ffca45;
            --pink: #ff7896;
            --blue: #72c8ed;
            --green: #8bd99a;
            --ink: #29213e;
            --muted: #777187;
            --cream: #fff7e8;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 8% 12%, #ffe8b8 0 8%, transparent 8.2%),
                radial-gradient(circle at 95% 70%, #e9dcff 0 10%, transparent 10.2%),
                var(--cream);
            color: var(--ink);
            font-family: 'Nunito', sans-serif;
        }

        a, button {
            font: inherit;
            -webkit-tap-highlight-color: transparent;
        }

        .page-shell {
            width: min(1160px, 92%);
            margin: auto;
            padding: 20px 0 35px;
        }

        /* Navigation */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            padding: 10px 0 20px;
            border-bottom: 2px solid #29213e18;
        }

        .brand {
            color: var(--purple);
            font-family: 'Fredoka', sans-serif;
            font-size: 29px;
            font-weight: 700;
            letter-spacing: -1px;
            text-decoration: none;
        }

        .brand span {
            color: #ff8a45;
        }

        .top-links {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .top-links a {
            color: var(--ink);
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            transition: color .2s;
        }

        .top-links a:hover {
            color: var(--purple);
        }

        .exit-link {
            color: #e45c78 !important;
        }

        /* Page heading */

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            margin: 35px 0 25px;
        }

        .heading-copy {
            max-width: 650px;
        }

        .eyebrow {
            display: inline-block;
            padding: 8px 14px;
            border: 2px solid var(--ink);
            border-radius: 30px;
            background: var(--yellow);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1px;
            transform: rotate(-2deg);
        }

        .welcome-section h1 {
            margin: 17px 0 8px;
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(38px, 5vw, 60px);
            line-height: 1;
            letter-spacing: -1.5px;
        }

        .welcome-section h1 span {
            color: var(--purple);
        }

        .welcome-section p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
            line-height: 1.8;
        }

        .welcome-character {
            display: grid;
            place-items: center;
            width: 145px;
            height: 145px;
            flex-shrink: 0;
            border: 3px solid var(--ink);
            border-radius: 35px;
            background: #ffe39c;
            box-shadow: 6px 7px 0 var(--ink);
            font-size: 82px;
            transform: rotate(4deg);
            animation: characterBob 3s ease-in-out infinite;
        }

        /* Player information */

        .player-bar {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr;
            gap: 14px;
            margin: 28px 0;
        }

        .player-stat {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
            padding: 16px;
            border: 2px solid var(--ink);
            border-radius: 17px;
            background: white;
            box-shadow: 3px 4px 0 var(--ink);
        }

        .stat-icon {
            display: grid;
            place-items: center;
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            border-radius: 13px;
            background: #fff0be;
            font-size: 23px;
        }

        .player-stat:nth-child(2) .stat-icon {
            background: #e8ddff;
        }

        .player-stat:nth-child(3) .stat-icon {
            background: #d8f4e0;
        }

        .stat-label {
            display: block;
            margin-bottom: 3px;
            color: var(--muted);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .stat-value {
            display: block;
            overflow: hidden;
            color: var(--ink);
            font-family: 'Fredoka', sans-serif;
            font-size: 20px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Progress bar */

        .progress-panel {
            margin: 25px 0;
            padding: 19px 22px;
            border: 2px solid var(--ink);
            border-radius: 17px;
            background: #fff;
            box-shadow: 3px 4px 0 var(--ink);
        }

        .progress-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .progress-top strong {
            font-family: 'Fredoka', sans-serif;
            font-size: 17px;
        }

        .progress-number {
            color: var(--purple);
            font-size: 13px;
            font-weight: 900;
        }

        .progress-track {
            height: 13px;
            overflow: hidden;
            border: 2px solid var(--ink);
            border-radius: 20px;
            background: #eee9f4;
        }

        .progress-fill {
            height: 100%;
            width: <?= $progressPercentage ?>%;
            border-radius: 20px;
            background: linear-gradient(90deg, #8bd99a, #55c982);
            transition: width .5s ease;
        }

        /* Difficulty selector */

        .room-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 35px 0 20px;
        }

        .room-toolbar h2 {
            margin: 0;
            font-family: 'Fredoka', sans-serif;
            font-size: 26px;
        }

        .difficulty-switch {
            display: flex;
            gap: 5px;
            padding: 5px;
            border: 2px solid var(--ink);
            border-radius: 13px;
            background: white;
            box-shadow: 3px 3px 0 var(--ink);
        }

        .difficulty-switch a {
            padding: 9px 13px;
            border-radius: 8px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
            transition: .2s;
        }

        .difficulty-switch a:hover {
            color: var(--purple);
            background: #f0e9ff;
        }

        .difficulty-switch a.active {
            background: var(--purple);
            color: white;
        }

        /* Room cards */

        .room-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .room-card {
            min-width: 0;
            overflow: hidden;
            border: 3px solid var(--ink);
            border-radius: 21px;
            background: white;
            box-shadow: 5px 6px 0 var(--ink);
            transition: transform .25s, box-shadow .25s;
            animation: cardAppear .55s ease both;
        }

        .room-card:nth-child(2) {
            animation-delay: .08s;
        }

        .room-card:nth-child(3) {
            animation-delay: .16s;
        }

        .room-card.available:hover {
            transform: translateY(-6px);
            box-shadow: 7px 12px 0 var(--ink);
        }

        .room-card.completed {
            border-color: #378f58;
            box-shadow: 5px 6px 0 #378f58;
        }

        .room-art {
            position: relative;
            display: grid;
            place-items: center;
            height: 220px;
            overflow: hidden;
        }

        .room-art.living {
            background: #bdeaff;
        }

        .room-art.kitchen {
            background: #ffd5a1;
        }

        .room-art.treasure {
            background: #e3d2ff;
        }

        .room-art::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .25;
            background-image: radial-gradient(#29213e 1px, transparent 1px);
            background-size: 17px 17px;
        }

        .room-art.living::after,
        .room-art.kitchen::after,
        .room-art.treasure::after {
            position: absolute;
            right: -20px;
            bottom: -35px;
            width: 150px;
            height: 150px;
            border: 3px solid #29213e;
            border-radius: 50%;
            background: #ffffff30;
            content: "";
        }

        .room-emoji {
            position: relative;
            z-index: 1;
            font-size: 103px;
            filter: drop-shadow(4px 5px 0 #29213e25);
            transition: transform .3s;
        }

        .room-card.available:hover .room-emoji {
            transform: scale(1.12) rotate(-5deg);
        }

        .room-number {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 2;
            padding: 7px 11px;
            border: 2px solid var(--ink);
            border-radius: 20px;
            background: white;
            color: var(--ink);
            font-size: 10px;
            font-weight: 900;
        }

        .status-label {
            position: absolute;
            top: 13px;
            right: 13px;
            z-index: 2;
            padding: 7px 10px;
            border: 2px solid var(--ink);
            border-radius: 20px;
            background: white;
            color: var(--purple);
            font-size: 10px;
            font-weight: 900;
        }

        .room-card.completed .status-label {
            background: #d9f5df;
            color: #267341;
        }

        .room-card.locked .room-art {
            filter: saturate(.55);
        }

        .room-card.locked .room-emoji {
            opacity: .45;
            filter: grayscale(.65);
        }

        .room-card.locked .status-label {
            background: #eeeaf3;
            color: #777187;
        }

        .room-content {
            padding: 19px;
        }

        .room-tag {
            display: block;
            margin-bottom: 8px;
            color: var(--purple);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .room-card.completed .room-tag {
            color: #378f58;
        }

        .room-content h3 {
            margin: 0 0 9px;
            font-family: 'Fredoka', sans-serif;
            font-size: 23px;
            line-height: 1.2;
        }

        .room-content p {
            min-height: 66px;
            margin: 0 0 17px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.8;
        }

        .room-action {
            display: block;
            width: 100%;
            padding: 12px;
            border: 2px solid var(--ink);
            border-radius: 11px;
            background: var(--purple);
            color: white;
            font-family: 'Fredoka', sans-serif;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 0 var(--ink);
            transition: .2s;
        }

        a.room-action:hover {
            background: var(--purple-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 0 var(--ink);
        }

        .room-card.completed .room-action {
            border-color: #378f58;
            background: #e0f7e5;
            color: #267341;
            box-shadow: 0 4px 0 #378f58;
        }

        .room-card.locked .room-action {
            border-color: #d8d2e1;
            background: #eeeaf3;
            color: #888194;
            box-shadow: none;
            cursor: not-allowed;
        }

        /* Bottom message */

        .bottom-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
            padding: 18px;
            border: 2px solid var(--ink);
            border-radius: 15px;
            background: #fff0bd;
            text-align: center;
            box-shadow: 3px 4px 0 var(--ink);
        }

        .bottom-note .note-icon {
            font-size: 28px;
        }

        .bottom-note p {
            margin: 0;
            color: var(--ink);
            font-size: 12px;
            font-weight: 800;
            line-height: 1.7;
        }

        .bottom-note strong {
            color: var(--purple);
        }

        /* Footer */

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
            padding-top: 18px;
            border-top: 2px solid #29213e18;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }

        .footer a {
            color: var(--purple);
            text-decoration: none;
        }

        .footer a:hover {
            color: #ff7896;
        }

        /* Animations */

        @keyframes characterBob {
            0%, 100% {
                transform: translateY(0) rotate(4deg);
            }
            50% {
                transform: translateY(-8px) rotate(1deg);
            }
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */

        @media (max-width: 850px) {
            .room-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .welcome-character {
                width: 115px;
                height: 115px;
                font-size: 65px;
            }
        }

        @media (max-width: 600px) {
            .page-shell {
                width: 92%;
                padding-top: 14px;
            }

            .topbar {
                align-items: flex-start;
            }

            .brand {
                font-size: 25px;
            }

            .top-links {
                gap: 12px;
            }

            .top-links a {
                font-size: 11px;
            }

            .welcome-section {
                align-items: flex-start;
                margin-top: 28px;
            }

            .welcome-section h1 {
                font-size: 40px;
            }

            .welcome-character {
                width: 75px;
                height: 75px;
                border-radius: 20px;
                font-size: 43px;
                box-shadow: 4px 4px 0 var(--ink);
            }

            .player-bar {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .player-stat {
                padding: 12px;
            }

            .room-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .difficulty-switch {
                width: 100%;
            }

            .difficulty-switch a {
                flex: 1;
                padding: 10px 4px;
                text-align: center;
            }

            .room-grid {
                grid-template-columns: 1fr;
                gap: 19px;
            }

            .room-art {
                height: 205px;
            }

            .room-content p {
                min-height: 0;
            }

            .bottom-note {
                align-items: flex-start;
                text-align: left;
            }

            .footer {
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

<main class="page-shell">

    <nav class="topbar">

        <a class="brand" href="../index.php">
            GHOST <span>HEIST</span>
        </a>

        <div class="top-links">
            <a href="dashboard.php">← Dashboard</a>
            <a href="leaderboard.php">🏆 Leaderboard</a>
            <a class="exit-link" href="../logout.php">Exit Game</a>
        </div>

    </nav>

    <header class="welcome-section">

        <div class="heading-copy">

            <span class="eyebrow">🎮 YOUR NEXT ADVENTURE</span>

            <h1>
                Choose Your
                <span>Mission!</span>
            </h1>

            <p>
                Welcome back,
                <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>!
                Three rooms, three challenges and one big treasure.
                Are you ready?
            </p>

        </div>

        <div class="welcome-character" aria-hidden="true">
            🥷
        </div>

    </header>

    <section class="player-bar" aria-label="Player information">

        <div class="player-stat">
            <div class="stat-icon">👤</div>
            <div>
                <span class="stat-label">Player</span>
                <span class="stat-value">
                    <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>

        <div class="player-stat">
            <div class="stat-icon">🪙</div>
            <div>
                <span class="stat-label">Your Keys</span>
                <span class="stat-value">
                    <?= number_format($completedCount) ?>
                </span>
            </div>
        </div>

        <div class="player-stat">
            <div class="stat-icon">🏅</div>
            <div>
                <span class="stat-label">Rooms Completed</span>
                <span class="stat-value">
                    <?= $completedCount ?> / 3
                </span>
            </div>
        </div>

    </section>

    <section class="progress-panel">

        <div class="progress-top">
            <strong>🏆 Your Mission Progress</strong>
            <span class="progress-number">
                <?= $progressPercentage ?>%
            </span>
        </div>

        <div
            class="progress-track"
            role="progressbar"
            aria-label="Mission progress"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="<?= $progressPercentage ?>"
        >
            <div class="progress-fill"></div>
        </div>

    </section>

    <section>

        <div class="room-toolbar">

            <h2>🗺️ Explore the Rooms</h2>

            <span class="mode-label">Classic Mode</span>

        </div>

        <div class="room-grid" aria-label="Three game rooms">

            <?php foreach ($rooms as $number => $roomData): ?>

                <?php
                    $isCompleted = in_array(
                        $number,
                        $completedRooms,
                        true
                    );

                    $isUnlocked = $isCompleted || $number === $nextRoom;

                    $cardClass = $isCompleted
                        ? 'completed'
                        : ($isUnlocked ? 'available' : 'locked');

                    if ($isCompleted) {
                        $status = 'Completed ✓';
                        $action = '✓ Mission Completed';

                    } elseif ($isUnlocked) {
                        $status = 'Ready to Play!';
                        $action = 'Enter This Room →';

                    } else {
                        $status = 'Locked 🔒';
                        $action = 'Complete Previous Room';
                    }
                ?>

                <article class="room-card <?= $cardClass ?>">

                    <div class="room-art <?= htmlspecialchars($roomData['background'], ENT_QUOTES, 'UTF-8') ?>">

                        <span class="room-number">
                            ROOM <?= str_pad((string) $number, 2, '0', STR_PAD_LEFT) ?>
                        </span>

                        <span class="status-label">
                            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <div class="room-emoji" aria-hidden="true">
                            <?= $roomData['emoji'] ?>
                        </div>

                    </div>

                    <div class="room-content">

                        <span class="room-tag">
                            <?= htmlspecialchars($roomData['tag'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <h3>
                            <?= htmlspecialchars($roomData['name'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($roomData['description'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <?php if ($isUnlocked && !$isCompleted): ?>

                            <a
                                class="room-action"
                                href="stealth.php?difficulty=<?= urlencode($difficulty) ?>&room=<?= $number ?>"
                            >
                                <?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>
                            </a>

                        <?php elseif ($isCompleted): ?>

                            <div class="room-action">
                                ✓ Mission Completed
                            </div>

                        <?php else: ?>

                            <div class="room-action" aria-disabled="true">
                                🔒 Complete Previous Room
                            </div>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </section>

    <div class="bottom-note">

        <span class="note-icon" aria-hidden="true">
            <?php if ($allCompleted): ?>
                🏆
            <?php else: ?>
                💡
            <?php endif; ?>
        </span>

        <p>

            <?php if ($allCompleted): ?>

                <strong>Fantastic! All three rooms completed!</strong>
                You've completed Classic Mode and collected the treasure.

            <?php else: ?>

                <strong>Mission tip:</strong>
                Complete your current room to unlock the next one.
                Watch out for the guards!

            <?php endif; ?>

        </p>

    </div>

    <footer class="footer">

        <span>GHOST HEIST © <?= date('Y') ?></span>

        <span>
            <a href="../index.php">Home</a>
            · Think fast. Stay sneaky. Get the treasure! 💰
        </span>

    </footer>

</main>

<script src="../assets/js/site-ambience.js"></script>
</body>
</html>
