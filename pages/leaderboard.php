
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
        'SELECT id, username, coins
         FROM users
         ORDER BY coins DESC, username ASC'
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard | Ghost Heist</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .leaderboard-page {
            max-width: 900px;
            margin: auto;
            padding: 35px 22px 60px;
        }

        .leaderboard-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .leaderboard-nav a {
            color: #c4b5fd;
            text-decoration: none;
        }

        .leaderboard-heading {
            padding: 45px 0 25px;
        }

        .leaderboard-heading .eyebrow {
            color: #c4b5fd;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: .8rem;
        }

        .leaderboard-heading h1 {
            font-size: clamp(2rem, 5vw, 3.3rem);
            margin: 12px 0;
        }

        .leaderboard-heading p {
            color: #b8b1c6;
            line-height: 1.7;
        }

        .leaderboard-card {
            background: #171321;
            border: 1px solid #393047;
            border-radius: 16px;
            overflow: hidden;
        }

        .leaderboard-row {
            display: grid;
            grid-template-columns: 75px minmax(0, 1fr) 110px;
            align-items: center;
            gap: 15px;
            padding: 19px 22px;
            border-bottom: 1px solid #393047;
        }

        .leaderboard-row:last-child {
            border-bottom: none;
        }

        .leaderboard-header {
            background: #211a2d;
            color: #b8b1c6;
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .rank {
            color: #c4b5fd;
            font-weight: 800;
        }

        .player-name {
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .player-coins {
            text-align: right;
            color: #fcd34d;
            font-weight: 700;
            white-space: nowrap;
        }

        .leaderboard-row.current-player {
            background: #29213a;
            box-shadow: inset 4px 0 #a78bfa;
        }

        .you-label {
            display: inline-block;
            margin-left: 8px;
            color: #c4b5fd;
            font-size: .72rem;
            font-weight: 700;
        }

        .empty-state,
        .error-state {
            padding: 30px 22px;
            color: #b8b1c6;
            line-height: 1.7;
        }

        .leaderboard-footer {
            margin-top: 28px;
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        .leaderboard-button {
            display: inline-block;
            padding: 12px 17px;
            border-radius: 8px;
            background: #a78bfa;
            color: #171321 !important;
            text-decoration: none;
            font-weight: 700;
        }

        .leaderboard-button.secondary {
            background: #272036;
            color: #c4b5fd !important;
            border: 1px solid #494057;
        }

        @media (max-width: 500px) {
            .leaderboard-row {
                grid-template-columns: 45px minmax(0, 1fr) 85px;
                gap: 8px;
                padding: 16px 12px;
            }

            .you-label {
                display: block;
                margin: 4px 0 0;
            }
        }
    </style>
</head>
<body>
<main class="leaderboard-page">
    <nav class="leaderboard-nav">
        <a href="../index.php"><strong>👻 GHOST HEIST</strong></a>
        <a href="dashboard.php">← Back to dashboard</a>
    </nav>

    <header class="leaderboard-heading">
        <span class="eyebrow">The treasure hall</span>
        <h1>🏆 Leaderboard</h1>
        <p>
            Every coin counts. See who's leading the heist and where
            you stand among the mansion's treasure hunters.
        </p>
    </header>

    <section class="leaderboard-card" aria-label="Player rankings">
        <div class="leaderboard-row leaderboard-header">
            <span>Rank</span>
            <span>Player</span>
            <span class="player-coins">Coins</span>
        </div>

        <?php if ($error !== null): ?>
            <div class="error-state">
                <?= escapeLeaderboardValue($error) ?>
            </div>
        <?php elseif (empty($players)): ?>
            <div class="empty-state">
                No players yet. Be the first to enter the mansion!
            </div>
        <?php else: ?>
            <?php foreach ($players as $index => $player): ?>
                <?php
                    $isCurrentPlayer =
                        (int) $player['id'] === $currentUserId;

                    $rank = $index + 1;
                    $rankLabel = match ($rank) {
                        1 => '🥇',
                        2 => '🥈',
                        3 => '🥉',
                        default => '#' . $rank,
                    };
                ?>
                <div class="leaderboard-row<?= $isCurrentPlayer ? ' current-player' : '' ?>">
                    <span class="rank">
                        <?= escapeLeaderboardValue($rankLabel) ?>
                    </span>

                    <span class="player-name">
                        <?= escapeLeaderboardValue($player['username']) ?>
                        <?php if ($isCurrentPlayer): ?>
                            <span class="you-label">YOU</span>
                        <?php endif; ?>
                    </span>

                    <span class="player-coins">
                        🪙 <?= number_format((int) $player['coins']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <footer class="leaderboard-footer">
        <a class="leaderboard-button" href="rooms.php?difficulty=beginner">
            Continue Heist →
        </a>
        <a class="leaderboard-button secondary" href="dashboard.php">
            Dashboard
        </a>
    </footer>
</main>
</body>
</html>
