
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$userId = (int) $_SESSION['user_id'];
$difficulty = $_GET['difficulty'] ?? 'beginner';

$levels = [
    'beginner' => 'Beginner',
    'intermediate' => 'Intermediate',
    'hard' => 'Hard'
];

if (!isset($levels[$difficulty])) {
    $difficulty = 'beginner';
}

$rooms = [
    1 => [
        'name' => 'The Forgotten Entrance',
        'description' => 'The mansion doors creak open. Your nightmare begins here.',
        'image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1000&q=85'
    ],
    2 => [
        'name' => 'The Whispering Hall',
        'description' => 'Whispers follow you. Something moves behind the walls.',
        'image' => 'https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1000&q=85'
    ],
    3 => [
        'name' => 'The Shadow Library',
        'description' => 'Forbidden books conceal the secrets of the dead.',
        'image' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1000&q=85'
    ],
    4 => [
        'name' => 'The Ghost Chamber',
        'description' => 'The shadows are alive. Do not trust what you see.',
        'image' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=1000&q=85'
    ],
    5 => [
        'name' => 'The Treasure Vault',
        'description' => 'The final door stands between you and the treasure.',
        'image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1000&q=85'
    ]
];

try {
    $stmt = $pdo->prepare(
        'SELECT room_number
         FROM room_progress
         WHERE user_id = ? AND difficulty = ?'
    );
    $stmt->execute([$userId, $difficulty]);

    $completedRooms = array_values(array_unique(array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    )));

    $stmt = $pdo->prepare('SELECT coins FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $coins = (int) $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log('Ghost Heist room map error: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not load your mansion progress. Please try again.');
}

/* Unlock the first incomplete room only. */
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080608">

    <title>The Mansion | Ghost Heist</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Creepster&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --red: #e51e35;
            --red-light: #ff4255;
            --gold: #e8bc71;
            --text: #f7f1f1;
            --muted: #b7a9ae;
            --panel: rgba(17, 12, 17, .92);
            --border: rgba(255, 255, 255, .12);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            position: relative;
            isolation: isolate;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            background: #080608;
            color: var(--text);
            font-family: 'Inter', sans-serif;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -3;
            pointer-events: none;
            background:
                linear-gradient(
                    180deg,
                    rgba(8, 5, 8, .76),
                    rgba(8, 5, 8, .94)
                ),
                url("https://images.unsplash.com/photo-1657265514497-02611707af59?auto=format&fit=crop&fm=jpg&q=85&w=2400")
                center / cover no-repeat;
            transform: scale(1.03);
            animation: mansionReveal 2s ease-out both;
        }

        body::after {
            content: "";
            position: fixed;
            inset: -25%;
            z-index: -2;
            pointer-events: none;
            background:
                radial-gradient(ellipse at 20% 60%, #a30c281f, transparent 35%),
                radial-gradient(ellipse at 80% 30%, #d7193517, transparent 35%);
            filter: blur(35px);
            animation: fogDrift 20s ease-in-out infinite alternate;
        }

        button,
        a {
            font: inherit;
            -webkit-tap-highlight-color: transparent;
        }

        /* Rain animation */

        .rain {
            position: fixed;
            inset: 0;
            z-index: -1;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: .25;
        }

        .map-shell {
            position: relative;
            width: min(1240px, 92%);
            margin: auto;
            padding: 22px 0 35px;
        }

        /* Navigation and branding */

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            padding: 8px 0 20px;
            border-bottom: 1px solid var(--border);
        }

        /* Override conflicting styles from style.css */
        .topbar .brand,
        .topbar a.brand {
            color: #ffffff !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 1rem !important;
            font-weight: 800 !important;
            letter-spacing: 3px !important;
            text-decoration: none !important;
            text-shadow: none !important;
        }

        .topbar .brand span,
        .topbar a.brand span {
            color: #ff3048 !important;
            text-shadow:
                0 0 8px rgba(255, 48, 72, .7),
                0 0 22px rgba(229, 30, 53, .45) !important;
        }

        .topbar .brand:hover,
        .topbar a.brand:hover {
            color: #ffffff !important;
        }

        .topbar .brand:hover span,
        .topbar a.brand:hover span {
            color: #ff6576 !important;
        }

        .top-links {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .top-links a,
        .sound-toggle {
            padding: 8px 0;
            border: 0;
            background: transparent;
            color: #ded3d7;
            font-size: .82rem;
            text-decoration: none;
            cursor: pointer;
            transition: color .2s;
        }

        .top-links a:hover,
        .sound-toggle:hover {
            color: var(--red-light);
        }

        .sound-toggle {
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #100b0de8;
        }

        .sound-toggle:focus-visible,
        a:focus-visible {
            outline: 2px solid var(--red-light);
            outline-offset: 4px;
        }

        /* Page heading */

        .room-heading {
            margin: 25px 0 20px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 8px;
            color: var(--red-light);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 23px;
            height: 1px;
            background: var(--red-light);
        }

        .room-heading h1 {
            margin: 0;
            font-family: 'Creepster', cursive;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 400;
            line-height: 1.05;
            text-shadow: 0 0 28px #e51e3525;
        }

        .room-heading h1 span {
            color: var(--red-light);
        }

        .heading-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .heading-description {
            margin: 0;
            color: var(--muted);
            font-size: .84rem;
            line-height: 1.7;
        }

        .room-stats {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .stat-pill {
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #100b0de8;
            color: #e7dce0;
            font-size: .76rem;
        }

        .stat-pill .gold {
            color: var(--gold);
        }

        /* Difficulty selection */

        .directory-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 23px 0 17px;
        }

        .directory-toolbar p {
            margin: 0;
            color: var(--muted);
            font-size: .8rem;
        }

        .difficulty-switch {
            display: flex;
            gap: 4px;
            padding: 4px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #0d090ce8;
        }

        .difficulty-switch a {
            padding: 9px 12px;
            border-radius: 6px;
            color: #c5b8bd;
            font-size: .76rem;
            text-decoration: none;
            transition: background .2s, color .2s;
        }

        .difficulty-switch a:hover {
            color: white;
        }

        .difficulty-switch a.active {
            background: var(--red);
            color: white;
            font-weight: 800;
            box-shadow: 0 0 18px #e51e352d;
        }

        /* Room cards */

        .room-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .room-card {
            position: relative;
            min-width: 0;
            overflow: hidden;
            border: 1px solid #ffffff1c;
            border-radius: 12px;
            background: var(--panel);
            box-shadow: 0 12px 35px #0006;
            animation: riseIn .65s ease-out both;
            transition:
                transform .25s,
                border-color .25s,
                box-shadow .25s;
        }

        .room-card:nth-child(2) { animation-delay: .07s; }
        .room-card:nth-child(3) { animation-delay: .14s; }
        .room-card:nth-child(4) { animation-delay: .21s; }
        .room-card:nth-child(5) { animation-delay: .28s; }

        .room-card.available:hover {
            transform: translateY(-5px);
            border-color: #ff425599;
            box-shadow: 0 18px 42px #000a, 0 0 24px #e51e351c;
        }

        .room-card.completed {
            border-color: #e8bc7155;
        }

        .room-art {
            position: relative;
            isolation: isolate;
            min-height: 250px;
            overflow: hidden;
            background: #160b10;
        }

        .room-art::before {
            content: "";
            position: absolute;
            inset: -5px;
            z-index: -2;
            background-image: var(--room-image);
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            filter: blur(2px) brightness(.65);
            transform: scale(1.03);
            transition: filter .35s ease, transform .6s ease;
        }

        .room-card.available:hover .room-art::before {
            filter: blur(0) brightness(.82);
            transform: scale(1.08);
        }

        .image-overlay {
            position: absolute;
            inset: 0;
            z-index: -1;
            background:
                linear-gradient(
                    180deg,
                    rgba(8, 5, 8, .3),
                    rgba(8, 5, 8, .08) 35%,
                    rgba(8, 5, 8, .82) 75%,
                    rgba(8, 5, 8, .98)
                ),
                linear-gradient(
                    100deg,
                    rgba(40, 0, 8, .25),
                    transparent 60%,
                    rgba(0, 0, 0, .25)
                );
        }

        .room-art::after {
            content: "";
            position: absolute;
            right: -25%;
            bottom: 0;
            left: -25%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #ff4255b0, transparent);
            box-shadow: 0 0 18px #e51e3550;
            animation: lightSweep 5s ease-in-out infinite;
        }

        .room-index {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 2;
            padding: 7px 9px;
            border: 1px solid #ffffff30;
            border-radius: 5px;
            background: #080608d9;
            color: #fff;
            font-size: .64rem;
            font-weight: 800;
            letter-spacing: 2px;
            backdrop-filter: blur(8px);
        }

        .status-icon {
            position: absolute;
            top: 13px;
            right: 13px;
            z-index: 2;
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border: 1px solid #ffffff3b;
            border-radius: 50%;
            background: #080608e8;
            color: var(--red-light);
            font-size: 1rem;
            backdrop-filter: blur(8px);
        }

        .room-card.locked .room-art::before {
            filter: blur(3px) brightness(.35) saturate(.5);
        }

        .room-card.locked .image-overlay {
            background: linear-gradient(
                180deg,
                rgba(0, 0, 0, .48),
                rgba(8, 5, 8, .55) 35%,
                rgba(8, 5, 8, .98)
            );
        }

        .room-card.locked .status-icon {
            color: #e5dce0;
            border-color: #ffffff40;
        }

        .room-card.completed .status-icon {
            color: var(--gold);
            border-color: #e8bc7180;
        }

        .image-title {
            position: absolute;
            right: 18px;
            bottom: 17px;
            left: 18px;
            z-index: 2;
        }

        .image-title span {
            display: block;
            margin-bottom: 7px;
            color: var(--red-light);
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .room-card.locked .image-title span {
            color: #bcb0b5;
        }

        .room-card.completed .image-title span {
            color: var(--gold);
        }

        .image-title h2 {
            margin: 0;
            font-family: 'Creepster', cursive;
            font-size: clamp(1.65rem, 2.5vw, 2.1rem);
            font-weight: 400;
            line-height: 1.15;
            text-shadow: 0 3px 15px #000;
        }

        /* Room actions */

        .room-content {
            padding: 14px 17px 17px;
            border-top: 1px solid #ffffff12;
        }

        .room-content p {
            min-height: 42px;
            margin: 0 0 13px;
            color: var(--muted);
            font-size: .77rem;
            line-height: 1.7;
        }

        .room-action {
            display: block;
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ff42556b;
            border-radius: 7px;
            background: linear-gradient(100deg, #b6112b, #e51e35);
            color: #fff;
            font-size: .78rem;
            font-weight: 800;
            text-align: center;
            text-decoration: none;
            transition: background .2s, box-shadow .2s, transform .2s;
        }

        a.room-action:hover {
            background: linear-gradient(100deg, #d51734, #ff4255);
            box-shadow: 0 0 20px #e51e3530;
            transform: translateY(-1px);
        }

        .room-card.completed .room-action {
            border-color: #e8bc7140;
            background: #e8bc7110;
            color: var(--gold);
        }

        .room-card.locked .room-action {
            border-color: #ffffff12;
            background: #ffffff06;
            color: #a49aa0;
            cursor: not-allowed;
        }

        .bottom-note {
            margin-top: 22px;
            padding: 15px 18px;
            border: 1px solid #ffffff17;
            border-radius: 9px;
            background: #100b0de8;
            color: var(--muted);
            font-size: .8rem;
            line-height: 1.7;
            text-align: center;
        }

        .bottom-note strong {
            color: var(--gold);
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 17px;
            border-top: 1px solid #ffffff17;
            color: #95868d;
            font-size: .72rem;
        }

        .footer span:last-child {
            color: #c7a2a9;
        }

        /* Animations */

        @keyframes mansionReveal {
            from { opacity: .5; transform: scale(1.08); }
            to { opacity: 1; transform: scale(1.03); }
        }

        @keyframes fogDrift {
            from { transform: translate3d(-2%, 1%, 0) scale(1); }
            to { transform: translate3d(3%, -2%, 0) scale(1.08); }
        }

        @keyframes lightSweep {
            0%, 100% { transform: translateY(-15px); opacity: .2; }
            50% { transform: translateY(0); opacity: .9; }
        }

        @keyframes riseIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive layout */

        @media (max-width: 900px) {
            .room-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .room-art {
                min-height: 230px;
            }
        }

        @media (max-width: 580px) {
            .map-shell {
                width: 92%;
                padding-top: 15px;
            }

            .topbar {
                align-items: flex-start;
            }

            .top-links {
                gap: 12px;
            }

            .top-links a,
            .sound-toggle {
                font-size: .74rem;
            }

            .room-heading {
                margin-top: 22px;
            }

            .room-heading h1 {
                font-size: clamp(2.5rem, 12vw, 3.5rem);
            }

            .heading-bottom,
            .directory-toolbar {
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
                gap: 15px;
            }

            .room-art {
                min-height: 250px;
            }

            .image-title h2 {
                font-size: 2rem;
            }

            .room-content p {
                min-height: 0;
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
<canvas class="rain" id="rainCanvas" aria-hidden="true"></canvas>

<main class="map-shell">

    <nav class="topbar">
        <a class="brand" href="../index.php">
            <span>GHOST</span> HEIST
        </a>

        <div class="top-links">
            <button
                class="sound-toggle"
                id="soundToggle"
                type="button"
                aria-pressed="false"
            >♫ Sound: Off</button>

            <a href="dashboard.php">&larr; Dashboard</a>
            <a href="leaderboard.php">Leaderboard</a>
            <a href="../logout.php">Exit ↗</a>
        </div>
    </nav>

    <header class="room-heading">
        <p class="eyebrow">Enter if you dare</p>

        <h1>Choose Your <span>Nightmare</span></h1>

        <div class="heading-bottom">
            <p class="heading-description">
                Five cursed rooms. Complete each one to unlock the next.
            </p>

            <div class="room-stats">
                <div class="stat-pill">
                    <span class="gold">✦</span>
                    <?= number_format($coins) ?> coins
                </div>

                <div class="stat-pill">
                    ☠ <?= $completedCount ?> / 5 rooms completed
                </div>
            </div>
        </div>
    </header>

    <div class="directory-toolbar">
        <p>
            Welcome back,
            <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>.
            The mansion remembers you.
        </p>

        <nav class="difficulty-switch" aria-label="Choose difficulty">
            <?php foreach ($levels as $key => $label): ?>
                <a
                    href="rooms.php?difficulty=<?= urlencode($key) ?>"
                    class="<?= $difficulty === $key ? 'active' : '' ?>"
                    <?= $difficulty === $key ? 'aria-current="page"' : '' ?>
                ><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <section class="room-grid" aria-label="Five mansion rooms">
        <?php foreach ($rooms as $number => $roomData): ?>
            <?php
                $isCompleted = in_array($number, $completedRooms, true);
                $isUnlocked = $isCompleted || $number === $nextRoom;

                $cardClass = $isCompleted
                    ? 'completed'
                    : ($isUnlocked ? 'available' : 'locked');

                if ($isCompleted) {
                    $status = 'Room cleared';
                    $icon = '✓';
                    $action = 'Treasure secured';
                } elseif ($isUnlocked) {
                    $status = 'Door unlocked';
                    $icon = '↗';
                    $action = 'Enter Room';
                } else {
                    $status = 'Access denied';
                    $icon = '🔒';
                    $action = 'Complete Previous Room';
                }
            ?>

            <article class="room-card <?= $cardClass ?>">
                <div
                    class="room-art"
                    style="--room-image: url('<?= htmlspecialchars($roomData['image'], ENT_QUOTES, 'UTF-8') ?>');"
                >
                    <div class="image-overlay"></div>

                    <span class="room-index">
                        ROOM <?= str_pad((string) $number, 2, '0', STR_PAD_LEFT) ?>
                    </span>

                    <span
                        class="status-icon"
                        aria-label="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                        title="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                    ><?= $icon ?></span>

                    <div class="image-title">
                        <span><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                        <h2><?= htmlspecialchars($roomData['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                </div>

                <div class="room-content">
                    <p><?= htmlspecialchars($roomData['description'], ENT_QUOTES, 'UTF-8') ?></p>

                    <?php if ($isUnlocked && !$isCompleted): ?>
                        <a
                            class="room-action"
                            href="game.php?difficulty=<?= urlencode($difficulty) ?>&room=<?= $number ?>"
                        ><?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?> &rarr;</a>

                    <?php elseif ($isCompleted): ?>
                        <div class="room-action">✓ Treasure secured</div>

                    <?php else: ?>
                        <div class="room-action" aria-disabled="true">
                            🔒 <?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="bottom-note">
        <?php if ($allCompleted): ?>
            <strong>All five rooms conquered!</strong>
            You've completed this difficulty. The treasure is yours!
        <?php else: ?>
            <strong>Survival tip:</strong>
            Solve the current room to break its curse and unlock the next door.
        <?php endif; ?>
    </div>

    <footer class="footer">
        <span>GHOST HEIST &copy; <?= date('Y') ?></span>
        <span>Think fast. Stay brave. Fear nothing.</span>
    </footer>

</main>

<script>
/* Atmospheric rain effect */
(() => {
    const canvas = document.getElementById('rainCanvas');
    const ctx = canvas.getContext('2d');

    if (!ctx) return;

    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    if (reduceMotion) {
        canvas.hidden = true;
        return;
    }

    let drops = [];
    let width = 0;
    let height = 0;
    let animationFrame = null;
    let isPaused = false;

    function resizeCanvas() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;

        const count = Math.min(150, Math.floor(width / 8));

        drops = Array.from({ length: count }, () => ({
            x: Math.random() * width,
            y: Math.random() * height,
            length: 8 + Math.random() * 18,
            speed: 5 + Math.random() * 8,
            opacity: 0.08 + Math.random() * 0.22
        }));
    }

    function drawRain() {
        if (isPaused) return;

        ctx.clearRect(0, 0, width, height);
        ctx.lineWidth = 1;

        drops.forEach(drop => {
            ctx.strokeStyle = `rgba(220, 205, 215, ${drop.opacity})`;
            ctx.beginPath();
            ctx.moveTo(drop.x, drop.y);
            ctx.lineTo(drop.x - 2, drop.y + drop.length);
            ctx.stroke();

            drop.y += drop.speed;
            drop.x -= 0.7;

            if (drop.y > height) {
                drop.y = -drop.length;
                drop.x = Math.random() * width;
            }

            if (drop.x < 0) {
                drop.x = width;
            }
        });

        animationFrame = requestAnimationFrame(drawRain);
    }

    function pauseRain() {
        isPaused = true;

        if (animationFrame !== null) {
            cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }
    }

    function resumeRain() {
        if (!isPaused) return;

        isPaused = false;
        drawRain();
    }

    window.addEventListener('resize', resizeCanvas);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            pauseRain();
        } else {
            resumeRain();
        }
    });

    resizeCanvas();
    drawRain();
})();

/*
 * Ambient horror sound.
 * Audio starts only after the user presses the sound button.
 */
(() => {
    const button = document.getElementById('soundToggle');

    let audioContext = null;
    let masterGain = null;
    let enabled = false;
    let starting = false;
    let oscillators = [];

    function setButtonState(isEnabled) {
        button.textContent = isEnabled
            ? '♫ Sound: On'
            : '♫ Sound: Off';

        button.setAttribute('aria-pressed', String(isEnabled));
    }

    async function startSound() {
        if (starting || enabled) return;

        starting = true;
        button.textContent = '♫ Starting sound...';

        const AudioContextClass =
            window.AudioContext || window.webkitAudioContext;

        if (!AudioContextClass) {
            button.textContent = 'Sound not supported';
            starting = false;
            return;
        }

        try {
            if (!audioContext || audioContext.state === 'closed') {
                audioContext = new AudioContextClass();
                masterGain = audioContext.createGain();

                /* Keep the sound at a moderate volume. */
                masterGain.gain.value = 0;
                masterGain.connect(audioContext.destination);

                const tones = [
                    { frequency: 55, volume: 0.45, type: 'sine' },
                    { frequency: 82.41, volume: 0.18, type: 'triangle' },
                    { frequency: 110, volume: 0.08, type: 'sine' }
                ];

                tones.forEach(tone => {
                    const oscillator = audioContext.createOscillator();
                    const gain = audioContext.createGain();

                    oscillator.type = tone.type;
                    oscillator.frequency.value = tone.frequency;
                    gain.gain.value = tone.volume;

                    oscillator.connect(gain);
                    gain.connect(masterGain);
                    oscillator.start();

                    oscillators.push(oscillator);
                });
            }

            await audioContext.resume();

            if (audioContext.state !== 'running') {
                throw new Error('The browser did not start the audio context.');
            }

            masterGain.gain.cancelScheduledValues(audioContext.currentTime);
            masterGain.gain.setTargetAtTime(
                0.22,
                audioContext.currentTime,
                0.7
            );

            enabled = true;
            setButtonState(true);

        } catch (error) {
            console.error('Could not start Ghost Heist ambient sound:', error);
            button.textContent = 'Sound unavailable';
            button.setAttribute('aria-pressed', 'false');
        } finally {
            starting = false;
        }
    }

    async function stopSound() {
        if (audioContext && masterGain) {
            masterGain.gain.cancelScheduledValues(audioContext.currentTime);

            masterGain.gain.setTargetAtTime(
                0,
                audioContext.currentTime,
                0.12
            );
        }

        enabled = false;
        setButtonState(false);
    }

    button.addEventListener('click', async () => {
        if (enabled) {
            await stopSound();
        } else {
            await startSound();
        }
    });

    /* Stop sound when the player leaves the tab. */
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && enabled) {
            stopSound();
        }
    });
})();
</script>

</body>
</html>
