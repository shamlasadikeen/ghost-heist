
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
        'description' => 'Every heist begins at the entrance.',
        'symbol' => '♜',
        'art' => 'entrance'
    ],
    2 => [
        'name' => 'The Whispering Hall',
        'description' => 'Something is waiting in the shadows.',
        'symbol' => '♧',
        'art' => 'hall'
    ],
    3 => [
        'name' => 'The Shadow Library',
        'description' => 'Ancient secrets guard the next door.',
        'symbol' => '▤',
        'art' => 'library'
    ],
    4 => [
        'name' => 'The Ghost Chamber',
        'description' => 'Face the mysteries of the mansion.',
        'symbol' => '☽',
        'art' => 'chamber'
    ],
    5 => [
        'name' => 'The Treasure Vault',
        'description' => 'The hidden treasure awaits.',
        'symbol' => '♛',
        'art' => 'vault'
    ]
];

try {
    $stmt = $pdo->prepare(
        'SELECT room_number
         FROM room_progress
         WHERE user_id = ? AND difficulty = ?'
    );
    $stmt->execute([$userId, $difficulty]);

    $completedRooms = array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );

    $stmt = $pdo->prepare(
        'SELECT coins FROM users WHERE id = ?'
    );
    $stmt->execute([$userId]);
    $coins = (int) $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log('Ghost Heist room map error: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not load your mansion progress. Please try again.');
}

$completedCount = count($completedRooms);
$nextRoom = $completedCount < 5 ? $completedCount + 1 : null;
$allCompleted = $completedCount === 5;

$username = $_SESSION['username'] ?? 'Heister';

$roomNames = array_column($rooms, 'name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>The Mansion | Ghost Heist</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        :root {
            --bg: #0c0a12;
            --panel: #171321;
            --panel-light: #201a2d;
            --purple: #a78bfa;
            --purple-light: #c4b5fd;
            --gold: #f5c96b;
            --text: #f5f0ff;
            --muted: #aaa1ba;
            --border: #393047;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(ellipse at 50% 0%, #302044 0%, transparent 48%),
                var(--bg);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        .map-shell {
            width: min(1180px, 94%);
            margin: auto;
            padding: 24px 0 60px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand {
            color: var(--purple-light);
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .top-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .top-links a {
            color: var(--muted);
            text-decoration: none;
            font-size: .92rem;
        }

        .top-links a:hover {
            color: var(--purple-light);
        }

        .hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            margin-top: 30px;
            padding: clamp(28px, 6vw, 62px);
            border: 1px solid #4b3b60;
            border-radius: 24px;
            background:
                linear-gradient(90deg, rgba(12,10,18,.97), rgba(12,10,18,.72)),
                radial-gradient(ellipse at 75% 40%, #7851a5, transparent 52%),
                linear-gradient(135deg, #332342, #15101f 75%);
            box-shadow: 0 25px 70px #0005;
        }

        .hero::after {
            content: "♜";
            position: absolute;
            z-index: -1;
            right: 5%;
            top: -50px;
            color: #c4b5fd0d;
            font-size: clamp(200px, 36vw, 410px);
            line-height: 1;
            transform: rotate(-8deg);
        }

        .eyebrow {
            color: var(--gold);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .hero h1 {
            max-width: 650px;
            margin: 14px 0;
            font-family: Georgia, serif;
            font-size: clamp(2.4rem, 6vw, 4.5rem);
            line-height: 1.05;
        }

        .hero h1 span {
            color: var(--purple-light);
        }

        .hero p {
            max-width: 530px;
            color: #c0b6d0;
            line-height: 1.8;
        }

        .hero-bottom {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 11px 15px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #171321cf;
            font-size: .9rem;
        }

        .gold {
            color: var(--gold);
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 15px;
            flex-wrap: wrap;
            margin: 42px 0 20px;
        }

        .section-heading h2 {
            margin: 0 0 8px;
            font-family: Georgia, serif;
            font-size: clamp(1.7rem, 4vw, 2.4rem);
        }

        .section-heading p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .difficulty-switch {
            display: flex;
            gap: 5px;
            padding: 5px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--panel);
        }

        .difficulty-switch a {
            padding: 10px 13px;
            border-radius: 8px;
            color: var(--muted);
            font-size: .83rem;
            text-decoration: none;
        }

        .difficulty-switch a.active {
            background: var(--purple);
            color: #171321;
            font-weight: 800;
        }

        .map-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding: 16px 20px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: linear-gradient(100deg, #1d172a, #15111d);
        }

        .map-banner strong {
            color: var(--purple-light);
        }

        .map-banner span {
            color: var(--muted);
            font-size: .9rem;
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .room-card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 17px;
            background: var(--panel);
            transition: transform .2s, border-color .2s, box-shadow .2s;
        }

        .room-card.available:hover {
            transform: translateY(-5px);
            border-color: var(--purple);
            box-shadow: 0 14px 35px #0005, 0 0 22px #a78bfa12;
        }

        .room-art {
            position: relative;
            display: grid;
            place-items: center;
            min-height: 175px;
            overflow: hidden;
            border-bottom: 1px solid var(--border);
            background:
                radial-gradient(ellipse at 50% 100%, #8b5cc64d, transparent 60%),
                linear-gradient(145deg, #30223f, #171321 72%);
        }

        .room-art::before {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            border: 1px solid #c4b5fd20;
            border-radius: 50%;
        }

        .room-art::after {
            content: "";
            position: absolute;
            width: 100%;
            height: 1px;
            bottom: 25px;
            background: linear-gradient(90deg, transparent, #a78bfa55, transparent);
        }

        .room-art .symbol {
            position: relative;
            z-index: 1;
            color: var(--purple-light);
            font-family: Georgia, serif;
            font-size: 5rem;
            text-shadow: 0 0 32px #a78bfa80;
        }

        .room-art .room-index {
            position: absolute;
            left: 15px;
            top: 14px;
            color: #ffffff90;
            font-size: .76rem;
            letter-spacing: 2px;
        }

        .room-art .status-icon {
            position: absolute;
            right: 14px;
            top: 13px;
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border: 1px solid #ffffff25;
            border-radius: 50%;
            background: #100d18a8;
            font-size: 1rem;
        }

        .room-card.locked .room-art {
            filter: saturate(.45);
        }

        .room-card.locked .symbol {
            color: #777080;
            text-shadow: none;
        }

        .room-card.completed {
            border-color: #a68a4b80;
        }

        .room-card.completed .room-art {
            background:
                radial-gradient(ellipse at 50% 100%, #d6a84340, transparent 60%),
                linear-gradient(145deg, #382e24, #171321 72%);
        }

        .room-card.completed .symbol {
            color: var(--gold);
            text-shadow: 0 0 30px #f5c96b55;
        }

        .room-card.completed .status-icon {
            color: var(--gold);
            border-color: #f5c96b55;
        }

        .room-content {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 19px;
        }

        .room-status {
            margin-bottom: 10px;
            color: var(--purple-light);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .room-card.completed .room-status {
            color: var(--gold);
        }

        .room-card.locked .room-status {
            color: #8f879b;
        }

        .room-content h3 {
            margin: 0 0 9px;
            font-family: Georgia, serif;
            font-size: 1.25rem;
            line-height: 1.35;
        }

        .room-content p {
            flex: 1;
            margin: 0 0 20px;
            color: var(--muted);
            font-size: .88rem;
            line-height: 1.65;
        }

        .room-action {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #a78bfa50;
            border-radius: 9px;
            background: #a78bfa;
            color: #171321;
            font-weight: 800;
            text-align: center;
            text-decoration: none;
            transition: background .2s;
        }

        .room-action:hover {
            background: var(--purple-light);
        }

        .room-card.completed .room-action {
            border-color: #f5c96b50;
            background: #f5c96b12;
            color: var(--gold);
        }

        .room-card.locked .room-action {
            border-color: var(--border);
            background: #211c2a;
            color: #8f879b;
            cursor: not-allowed;
        }

        .bottom-note {
            margin-top: 28px;
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--muted);
            font-size: .9rem;
            line-height: 1.7;
            text-align: center;
        }

        .bottom-note strong {
            color: var(--gold);
        }

        @media (max-width: 850px) {
            .room-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 540px) {
            .room-grid {
                grid-template-columns: 1fr;
            }

            .room-art {
                min-height: 150px;
            }

            .top-links {
                gap: 12px;
            }

            .difficulty-switch {
                width: 100%;
            }

            .difficulty-switch a {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<main class="map-shell">

    <nav class="topbar">
        <div class="brand">GHOST HEIST</div>

        <div class="top-links">
            <a href="dashboard.php">&larr; Dashboard</a>
            <a href="leaderboard.php">Leaderboard</a>
            <a href="../logout.php">Exit</a>
        </div>
    </nav>

    <header class="hero">
        <div class="eyebrow">Your adventure begins here</div>

        <h1>Enter the <span>Haunted Mansion.</span></h1>

        <p>
            Five mysterious rooms. Five puzzles. One hidden treasure.
            Outsmart the ghosts, unlock every door, and claim your reward.
        </p>

        <div class="hero-bottom">
            <div class="stat-pill">
                <span class="gold">✦</span>
                <span><?= number_format($coins) ?> coins</span>
            </div>

            <div class="stat-pill">
                <span>◈</span>
                <span><?= $completedCount ?> of 5 rooms completed</span>
            </div>
        </div>
    </header>

    <section class="section-heading">
        <div>
            <h2>The Mansion Map</h2>
            <p>
                Welcome back, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>.
                Choose your next destination.
            </p>
        </div>

        <nav class="difficulty-switch" aria-label="Choose difficulty">
            <?php foreach ($levels as $key => $label): ?>
                <a
                    href="rooms.php?difficulty=<?= urlencode($key) ?>"
                    class="<?= $difficulty === $key ? 'active' : '' ?>"
                    <?= $difficulty === $key ? 'aria-current="page"' : '' ?>
                >
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <div class="map-banner">
        <div>
            <strong><?= htmlspecialchars($levels[$difficulty]) ?> Mode</strong>
            <span> — Your progress is saved separately for each difficulty.</span>
        </div>

        <span>✦ 100 coins per room</span>
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
                    $status = 'Completed';
                    $icon = '✓';
                    $action = 'Replay unavailable';
                } elseif ($isUnlocked) {
                    $status = 'Unlocked';
                    $icon = '↗';
                    $action = 'Enter Room';
                } else {
                    $status = 'Locked';
                    $icon = '🔒';
                    $action = 'Complete Previous Room';
                }
            ?>

            <article class="room-card <?= $cardClass ?>">
                <div class="room-art">
                    <span class="room-index">
                        ROOM <?= str_pad((string) $number, 2, '0', STR_PAD_LEFT) ?>
                    </span>

                    <span class="status-icon" aria-label="<?= $status ?>">
                        <?= $icon ?>
                    </span>

                    <span class="symbol" aria-hidden="true">
                        <?= $roomData['symbol'] ?>
                    </span>
                </div>

                <div class="room-content">
                    <div class="room-status"><?= $status ?></div>

                    <h3><?= htmlspecialchars($roomData['name']) ?></h3>

                    <p><?= htmlspecialchars($roomData['description']) ?></p>

                    <?php if ($isUnlocked && !$isCompleted): ?>
                        <a
                            class="room-action"
                            href="game.php?difficulty=<?= urlencode($difficulty) ?>&room=<?= $number ?>"
                        >
                            <?= $action ?> &rarr;
                        </a>
                    <?php elseif ($isCompleted): ?>
                        <div class="room-action">
                            ✓ Treasure secured
                        </div>
                    <?php else: ?>
                        <div class="room-action" aria-disabled="true">
                            🔒 <?= $action ?>
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
            <strong>Heist tip:</strong>
            Solve the current room to unlock the next door.
            Each new room rewards you with 100 coins.
        <?php endif; ?>
    </div>

</main>
</body>
</html>
