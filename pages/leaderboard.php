
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$currentUserId = (int) $_SESSION['user_id'];
$players = [];
$error = null;

try {
    $stmt = $pdo->query(
        'SELECT
            u.id,
            u.username,
            u.gold,
            COALESCE(p.wooden_keys, 0) AS wooden_keys,
            (
                COALESCE(p.wooden_keys, 0) * 100 + u.gold
            ) AS total_score
         FROM users u
         LEFT JOIN (
            SELECT
                user_id,
                COUNT(*) AS wooden_keys
            FROM room_progress_v2
            WHERE room_number IN (1, 2)
            GROUP BY user_id
         ) p ON p.user_id = u.id
         ORDER BY
            total_score DESC,
            wooden_keys DESC,
            u.gold DESC,
            u.username ASC'
    );

    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Ghost Heist leaderboard error: ' . $e->getMessage());
    $error = 'The leaderboard is temporarily unavailable. Please try again later.';
}

function escapeLeaderboardValue($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$currentRank = null;

foreach ($players as $index => $player) {
    if ((int) $player['id'] === $currentUserId) {
        $currentRank = $index + 1;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fff9ed">

    <title>Ghost Heist | Leaderboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #20234b;
            --purple: #7357e8;
            --yellow: #ffcf45;
            --pink: #ff7197;
            --blue: #66c8f2;
            --cream: #fff9ed;
            --green: #77d6a1;
            --muted: #72748e;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            background: var(--cream);
            color: var(--navy);
            font-family: 'Nunito', sans-serif;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* NAVIGATION */

        .navbar {
            min-height: 76px;
            padding: 13px 7%;
            background: var(--white);
            border-bottom: 3px solid var(--navy);

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
        }

        .brand {
            font-family: 'Fredoka', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--purple);
        }

        .brand span {
            color: var(--pink);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-link {
            display: inline-block;
            padding: 10px 17px;
            background: var(--yellow);
            border: 2px solid var(--navy);
            border-radius: 12px;
            box-shadow: 3px 4px 0 var(--navy);
            font-weight: 900;
            transition: .2s;
        }

        .nav-link:hover {
            transform: translate(2px, 2px);
            box-shadow: 1px 2px 0 var(--navy);
        }

        /* MAIN CONTAINER */

        main {
            max-width: 1180px;
            width: 92%;
            margin: auto;
            padding: 35px 0 60px;
        }

        .breadcrumb {
            margin-bottom: 24px;
            color: var(--muted);
            font-weight: 800;
        }

        .breadcrumb a {
            color: var(--purple);
        }

        /* HERO */

        .hero {
            position: relative;
            overflow: hidden;
            padding: 45px;
            margin-bottom: 38px;

            background:
                radial-gradient(circle at 90% 15%, #ffffff 0 7px, transparent 8px),
                radial-gradient(circle at 80% 80%, #ffffff 0 5px, transparent 6px),
                #b8eaff;

            border: 3px solid var(--navy);
            border-radius: 26px;
            box-shadow: 7px 8px 0 var(--navy);
        }

        .hero::after {
            content: "🏆";
            position: absolute;
            right: 8%;
            top: 50%;
            transform: translateY(-50%) rotate(10deg);
            font-size: clamp(75px, 12vw, 135px);
            opacity: .95;
        }

        .eyebrow {
            display: inline-block;
            padding: 8px 16px;
            margin-bottom: 17px;

            background: var(--pink);
            color: white;
            border: 2px solid var(--navy);
            border-radius: 25px;

            font-weight: 900;
            transform: rotate(-2deg);
        }

        .hero h1 {
            max-width: 650px;
            margin-bottom: 12px;

            font-family: 'Fredoka', sans-serif;
            font-size: clamp(42px, 6vw, 68px);
            line-height: 1.05;
            color: var(--purple);
            text-shadow: 3px 4px 0 white;
        }

        .hero p {
            max-width: 620px;
            font-size: 17px;
            line-height: 1.8;
            font-weight: 800;
        }

        /* YOUR POSITION */

        .player-position {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;

            padding: 23px 28px;
            margin-bottom: 40px;

            background: var(--yellow);
            border: 3px solid var(--navy);
            border-radius: 20px;
            box-shadow: 5px 6px 0 var(--navy);
        }

        .position-label {
            font-family: 'Fredoka', sans-serif;
            font-size: 25px;
            font-weight: 700;
        }

        .position-subtitle {
            margin-top: 5px;
            font-weight: 800;
        }

        .position-value {
            font-family: 'Fredoka', sans-serif;
            font-size: 45px;
            font-weight: 700;
            color: var(--purple);
        }

        /* SECTION HEADINGS */

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin: 40px 0 22px;
        }

        .section-heading h2 {
            font-family: 'Fredoka', sans-serif;
            font-size: 32px;
            color: var(--purple);
        }

        .section-heading p {
            margin-top: 4px;
            color: var(--muted);
            font-weight: 800;
        }

        .player-count {
            padding: 9px 15px;
            background: var(--pink);
            color: white;
            border: 2px solid var(--navy);
            border-radius: 12px;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        /* PODIUM */

        .podium {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: end;
            gap: 20px;
            margin: 35px 0 45px;
        }

        .podium-card {
            position: relative;
            min-width: 0;
            padding: 25px 17px;
            text-align: center;

            background: white;
            border: 3px solid var(--navy);
            border-radius: 22px;
            box-shadow: 6px 7px 0 var(--navy);
        }

        .podium-card.second {
            order: 1;
            background: var(--blue);
        }

        .podium-card.first {
            order: 2;
            padding-top: 37px;
            background: #ffdf78;
            transform: translateY(-13px);
        }

        .podium-card.third {
            order: 3;
            background: #ffd2df;
        }

        .podium-crown {
            margin-bottom: 8px;
            font-size: 39px;
        }

        .podium-medal {
            margin-bottom: 5px;
            font-size: 31px;
        }

        .podium-rank {
            margin-bottom: 10px;
            color: var(--purple);
            font-size: 13px;
            font-weight: 900;
        }

        .podium-name {
            margin-bottom: 17px;
            font-family: 'Fredoka', sans-serif;
            font-size: 23px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .podium-coins {
            display: block;
            padding: 9px;
            margin: 8px 0;

            background: rgba(255,255,255,.8);
            border: 2px solid var(--navy);
            border-radius: 12px;

            font-size: 14px;
            font-weight: 900;
        }

        .podium-current {
            display: inline-block;
            padding: 6px 12px;
            margin-top: 12px;

            background: var(--purple);
            color: white;
            border-radius: 20px;

            font-size: 12px;
            font-weight: 900;
        }

        /* ALL PLAYERS */

        .leaderboard-card {
            overflow: hidden;
            background: white;
            border: 3px solid var(--navy);
            border-radius: 20px;
            box-shadow: 6px 7px 0 var(--navy);
        }

        .leaderboard-row {
            display: grid;
            grid-template-columns: 75px minmax(0, 1fr) minmax(180px, 220px);
            align-items: center;
            gap: 15px;

            padding: 17px 20px;
            border-bottom: 2px solid #eeeaf5;
        }

        .leaderboard-row:last-child {
            border-bottom: none;
        }

        .leaderboard-header {
            background: var(--purple);
            color: white;
            font-weight: 900;
        }

        .rank {
            font-family: 'Fredoka', sans-serif;
            font-size: 21px;
            font-weight: 700;
        }

        .top-rank {
            color: #e69b00;
        }

        .player-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .player-avatar {
            width: 45px;
            height: 45px;
            flex: 0 0 45px;

            display: grid;
            place-items: center;

            background: var(--blue);
            border: 2px solid var(--navy);
            border-radius: 14px;

            font-family: 'Fredoka', sans-serif;
            font-size: 21px;
            font-weight: 700;
        }

        .player-details {
            min-width: 0;
        }

        .player-name {
            display: block;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .player-subtitle {
            margin-top: 3px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .you-label {
            display: inline-block;
            padding: 3px 8px;
            margin-left: 5px;

            background: var(--purple);
            color: white;
            border-radius: 15px;

            font-size: 10px;
            font-weight: 900;
        }

        .player-coins {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;

            font-size: 13px;
            font-weight: 800;
        }

        .player-coins strong {
            color: var(--purple);
            font-size: 15px;
        }

        .current-player {
            background: #fff0b8;
        }

        .current-player .player-avatar {
            background: var(--yellow);
        }

        /* EMPTY AND ERROR STATES */

        .state-message {
            padding: 40px 20px;
            text-align: center;
            font-weight: 800;
            line-height: 1.7;
        }

        .state-icon {
            margin-bottom: 10px;
            font-size: 43px;
        }

        .state-message strong {
            display: block;
            margin-bottom: 8px;
            font-family: 'Fredoka', sans-serif;
            font-size: 25px;
            color: var(--purple);
        }

        /* FOOTER */

        .footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;

            margin-top: 42px;
            padding: 26px;

            background: #b8eaff;
            border: 3px solid var(--navy);
            border-radius: 20px;
            box-shadow: 5px 6px 0 var(--navy);
        }

        .footer-note {
            font-weight: 900;
            line-height: 1.8;
        }

        .footer-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .action-button {
            display: inline-block;
            padding: 12px 20px;

            background: var(--yellow);
            color: var(--navy);
            border: 2px solid var(--navy);
            border-radius: 13px;
            box-shadow: 3px 4px 0 var(--navy);

            font-weight: 900;
            transition: .2s;
        }

        .action-button:hover {
            transform: translate(2px, 2px);
            box-shadow: 1px 2px 0 var(--navy);
        }

        .action-button.secondary {
            background: var(--pink);
            color: white;
        }

        .bottom-mark {
            margin-top: 25px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        /* RESPONSIVE */

        @media (max-width: 760px) {
            main {
                padding-top: 22px;
            }

            .hero {
                padding: 30px 23px;
            }

            .hero::after {
                right: 3%;
                top: 15%;
                font-size: 65px;
                transform: none;
                opacity: .35;
            }

            .hero h1 {
                font-size: 43px;
            }

            .podium {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .podium-card.second,
            .podium-card.first,
            .podium-card.third {
                order: initial;
                transform: none;
                padding: 23px 17px;
            }

            .leaderboard-row {
                grid-template-columns: 42px minmax(0, 1fr);
                gap: 10px;
                padding: 15px 12px;
            }

            .leaderboard-header span:last-child {
                display: none;
            }

            .player-coins {
                grid-column: 2;
                padding-left: 57px;
            }

            .position-value {
                font-size: 34px;
            }

            .footer-actions {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 420px) {
            .navbar {
                padding: 12px 4%;
            }

            .brand {
                font-size: 23px;
            }

            .nav-link {
                padding: 9px 11px;
                font-size: 13px;
            }

            .hero h1 {
                font-size: 37px;
            }

            .section-heading h2 {
                font-size: 26px;
            }

            .player-position {
                padding: 17px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <a class="brand" href="../index.php">
        GHOST <span>HEIST</span>
    </a>

    <div class="nav-right">
        <a class="nav-link" href="dashboard.php">
            ← Dashboard
        </a>
    </div>
</nav>

<main>

    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        &nbsp; / &nbsp; Leaderboard
    </div>

    <header class="hero">
        <div class="eyebrow">🏆 THE TREASURE HALL</div>

        <h1>Leaderboard</h1>

        <p>
            Who is the greatest treasure hunter?
            Collect wooden keys, earn gold and climb your way
            to the top of the Ghost Heist leaderboard!
        </p>
    </header>

    <?php if ($error === null && !empty($players)): ?>

        <section class="player-position" aria-label="Your leaderboard position">

            <div>
                <div class="position-label">⭐ Your position</div>

                <div class="position-subtitle">
                    <?= $currentRank !== null
                        ? 'Keep playing to climb the rankings!'
                        : 'Start playing to appear on the leaderboard.' ?>
                </div>
            </div>

            <div class="position-value">
                <?= $currentRank !== null ? '#' . (int) $currentRank : '—' ?>
            </div>

        </section>

    <?php endif; ?>

    <section aria-labelledby="championsTitle">

        <div class="section-heading">

            <div>
                <h2 id="championsTitle">🏆 The champions</h2>
                <p>Meet the top three treasure hunters!</p>
            </div>

            <span class="player-count">
                <?= $error === null ? count($players) : '—' ?>
                PLAYERS
            </span>

        </div>

        <?php if ($error !== null): ?>

            <div class="leaderboard-card">
                <div class="state-message" role="alert">
                    <div class="state-icon">⚠️</div>
                    <strong>Something went wrong</strong>
                    <?= escapeLeaderboardValue($error) ?>
                </div>
            </div>

        <?php elseif (empty($players)): ?>

            <div class="leaderboard-card">
                <div class="state-message">
                    <div class="state-icon">👻</div>
                    <strong>The treasure hall is empty!</strong>
                    No players yet. Be the first to start your adventure!
                </div>
            </div>

        <?php else: ?>

            <div class="podium">

                <?php
                $podiumPositions = [
                    [
                        'index' => 1,
                        'class' => 'second',
                        'label' => '2ND PLACE',
                        'medal' => '🥈'
                    ],
                    [
                        'index' => 0,
                        'class' => 'first',
                        'label' => 'CHAMPION',
                        'medal' => '🥇'
                    ],
                    [
                        'index' => 2,
                        'class' => 'third',
                        'label' => '3RD PLACE',
                        'medal' => '🥉'
                    ]
                ];

                foreach ($podiumPositions as $position):

                    $playerIndex = $position['index'];

                    if (!isset($players[$playerIndex])) {
                        continue;
                    }

                    $player = $players[$playerIndex];

                    $isCurrentPlayer =
                        (int) $player['id'] === $currentUserId;

                    $username = (string) $player['username'];
                ?>

                    <article class="podium-card <?= $position['class'] ?>">

                        <?php if ($position['class'] === 'first'): ?>
                            <div class="podium-crown" aria-hidden="true">
                                👑
                            </div>
                        <?php endif; ?>

                        <div class="podium-medal">
                            <?= $position['medal'] ?>
                        </div>

                        <div class="podium-rank">
                            <?= escapeLeaderboardValue($position['label']) ?>
                        </div>

                        <div class="podium-name">
                            <?= escapeLeaderboardValue($username) ?>
                        </div>

                        <div class="podium-coins">
                            🗝️
                            <?= number_format((int) $player['wooden_keys']) ?>
                            Keys
                        </div>

                        <div class="podium-coins">
                            🪙
                            <?= number_format((int) $player['gold']) ?>
                            Gold
                        </div>

                        <div class="podium-coins">
                            ⭐
                            <?= number_format((int) $player['total_score']) ?>
                            Points
                        </div>

                        <?php if ($isCurrentPlayer): ?>
                            <span class="podium-current">
                                ✨ YOU ✨
                            </span>
                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

            <div class="section-heading">

                <div>
                    <h2>🎮 All treasure hunters</h2>
                    <p>Ranked by total score</p>
                </div>

            </div>

            <section class="leaderboard-card" aria-label="All player rankings">

                <div class="leaderboard-row leaderboard-header">
                    <span>Rank</span>
                    <span>Player</span>
                    <span>Rewards</span>
                </div>

                <?php foreach ($players as $index => $player): ?>

                    <?php
                    $rank = $index + 1;

                    $isCurrentPlayer =
                        (int) $player['id'] === $currentUserId;

                    $username = (string) $player['username'];

                    $initial = function_exists('mb_substr')
                        ? mb_strtoupper(
                            mb_substr($username, 0, 1, 'UTF-8'),
                            'UTF-8'
                        )
                        : strtoupper(substr($username, 0, 1));

                    $rankLabel = match ($rank) {
                        1 => '🥇',
                        2 => '🥈',
                        3 => '🥉',
                        default => '#' . $rank
                    };
                    ?>

                    <div class="leaderboard-row<?= $isCurrentPlayer ? ' current-player' : '' ?>">

                        <span class="rank<?= $rank <= 3 ? ' top-rank' : '' ?>">
                            <?= escapeLeaderboardValue($rankLabel) ?>
                        </span>

                        <div class="player-cell">

                            <span class="player-avatar" aria-hidden="true">
                                <?= escapeLeaderboardValue($initial) ?>
                            </span>

                            <div class="player-details">

                                <span class="player-name">
                                    <?= escapeLeaderboardValue($username) ?>

                                    <?php if ($isCurrentPlayer): ?>
                                        <span class="you-label">YOU</span>
                                    <?php endif; ?>
                                </span>

                                <div class="player-subtitle">
                                    <?= $rank === 1
                                        ? 'Mansion champion'
                                        : 'Treasure hunter' ?>
                                </div>

                            </div>

                        </div>

                        <div class="player-coins">

                            <span>
                                🗝️
                                <?= number_format((int) $player['wooden_keys']) ?>
                                Keys
                            </span>

                            <span>
                                🪙
                                <?= number_format((int) $player['gold']) ?>
                                Gold
                            </span>

                            <strong>
                                ⭐
                                <?= number_format((int) $player['total_score']) ?>
                                Points
                            </strong>

                        </div>

                    </div>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

    </section>

    <footer class="footer-actions">

        <div class="footer-note">
            Your next victory could change everything!<br>
            Collect wooden keys and gold to climb the rankings.
        </div>

        <div class="footer-buttons">

            <a class="action-button" href="rooms.php?difficulty=beginner">
                Continue Heist →
            </a>

            <a class="action-button secondary" href="dashboard.php">
                Dashboard
            </a>

        </div>

    </footer>

    <div class="bottom-mark">
        GHOST HEIST · SNEAK, SOLVE & STEAL!
    </div>

</main>

<script src="../assets/js/site-ambience.js"></script>
</body>
</html>
