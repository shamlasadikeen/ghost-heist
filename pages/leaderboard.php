
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#080609">
<title>Leaderboard | Ghost Heist</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Creepster&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
:root {
    --bg: #080609;
    --panel: rgba(20, 13, 18, .92);
    --red: #ed263f;
    --red-light: #ff5265;
    --gold: #ffd16f;
    --silver: #d5dbe5;
    --bronze: #d99b72;
    --text: #f8f1f3;
    --muted: #b8aab0;
    --border: rgba(255,255,255,.09);
}

* { box-sizing: border-box; }

html { min-height: 100%; }

body {
    margin: 0;
    min-height: 100vh;
    color: var(--text);
    background: var(--bg);
    font-family: 'Inter', sans-serif;
    overflow-x: hidden;
}

a { -webkit-tap-highlight-color: transparent; }

a:focus-visible {
    outline: 2px solid var(--gold);
    outline-offset: 4px;
}

/* Background atmosphere */

.haunted-bg {
    position: fixed;
    inset: 0;
    z-index: -2;
    overflow: hidden;
    background:
        radial-gradient(ellipse at 50% 25%, rgba(111, 10, 31, .26), transparent 48%),
        linear-gradient(180deg, #10080d, #080609 80%);
}

.moon {
    position: absolute;
    top: 9%;
    right: 13%;
    width: 145px;
    aspect-ratio: 1;
    border-radius: 50%;
    background: radial-gradient(circle, #d8cbd0, #806b75 42%, transparent 72%);
    opacity: .13;
    filter: blur(3px);
    box-shadow: 0 0 90px rgba(218,180,190,.12);
    animation: moonPulse 7s ease-in-out infinite alternate;
}

.fog {
    position: absolute;
    inset: -30%;
    opacity: .12;
    filter: blur(55px);
    background:
        radial-gradient(ellipse at 20% 60%, #a99aa5, transparent 34%),
        radial-gradient(ellipse at 75% 65%, #642234, transparent 35%);
    animation: fogMove 28s ease-in-out infinite alternate;
}

.fog.two {
    opacity: .08;
    animation-duration: 40s;
    animation-direction: alternate-reverse;
}

#particles {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.background-shade {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.48));
}

/* Navigation */

.topbar {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    min-height: 62px;
    padding: 12px clamp(16px, 4vw, 52px);
    border-bottom: 1px solid var(--border);
    background: rgba(7,5,7,.75);
    backdrop-filter: blur(15px);
}

.brand {
    text-decoration: none;
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 2px;
    white-space: nowrap;
}

.brand-ghost { color: var(--red-light); }
.brand-heist { color: #fff; }

.nav-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 13px;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 8px;
    color: #f0e5e9;
    background: rgba(255,255,255,.04);
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    transition: .2s ease;
}

.nav-link:hover {
    border-color: rgba(255,82,101,.5);
    background: rgba(237,38,63,.09);
}

/* Main layout */

main {
    width: min(100% - 32px, 960px);
    margin: 0 auto;
    padding: 27px 0 42px;
    animation: enter .6s ease both;
}

.breadcrumb {
    margin-bottom: 17px;
    color: #8f8188;
    font-size: 10px;
}

.breadcrumb a {
    color: #c9bac1;
    text-decoration: none;
}

.hero {
    position: relative;
    padding: 24px 22px 28px;
    margin-bottom: 20px;
    overflow: hidden;
    border: 1px solid rgba(237,38,63,.18);
    border-radius: 17px;
    background:
        radial-gradient(ellipse at 50% 100%, rgba(133,12,35,.18), transparent 65%),
        rgba(17,10,15,.76);
    text-align: center;
}

.hero::before,
.hero::after {
    position: absolute;
    top: 50%;
    width: 65px;
    height: 1px;
    content: "";
    background: linear-gradient(90deg, transparent, rgba(255,82,101,.65));
}

.hero::before { left: 7%; }
.hero::after {
    right: 7%;
    transform: rotate(180deg);
}

.eyebrow {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin: 0 0 9px;
    color: var(--red-light);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.live-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--red-light);
    box-shadow: 0 0 12px var(--red);
    animation: blink 1.5s infinite;
}

.hero h1 {
    margin: 0;
    color: #fff5f6;
    font-family: 'Creepster', cursive;
    font-size: clamp(42px, 7vw, 66px);
    font-weight: 400;
    line-height: 1;
    text-shadow: 0 0 30px rgba(237,38,63,.3);
}

.hero p {
    max-width: 480px;
    margin: 12px auto 0;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.8;
}

/* Current player's position */

.player-position {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 18px;
    margin-bottom: 24px;
    border: 1px solid rgba(255,209,111,.22);
    border-radius: 12px;
    background: linear-gradient(110deg, rgba(255,209,111,.07), rgba(20,13,18,.9));
}

.position-label {
    color: #b9aeb3;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
}

.position-subtitle {
    margin-top: 5px;
    color: #93848b;
    font-size: 10px;
}

.position-value {
    color: var(--gold);
    font-size: 27px;
    font-weight: 900;
    white-space: nowrap;
}

/* Section headings */

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin: 0 0 15px;
}

.section-heading h2 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
}

.section-heading p {
    margin: 4px 0 0;
    color: #91838a;
    font-size: 10px;
}

.player-count {
    padding: 7px 10px;
    border: 1px solid var(--border);
    border-radius: 7px;
    color: #c5b7bd;
    background: rgba(255,255,255,.035);
    font-size: 9px;
    font-weight: 800;
    white-space: nowrap;
}

/* Top three podium */

.podium {
    display: grid;
    grid-template-columns: 1fr 1.08fr 1fr;
    align-items: end;
    gap: 12px;
    margin-bottom: 25px;
}

.podium-card {
    position: relative;
    min-width: 0;
    padding: 17px 9px 16px;
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 13px;
    background: linear-gradient(180deg, rgba(29,21,29,.96), rgba(13,9,13,.95));
    text-align: center;
    transition: transform .25s ease, border-color .25s ease;
}

.podium-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255,209,111,.3);
}

.podium-card.first {
    min-height: 190px;
    border-color: rgba(255,209,111,.4);
    background: linear-gradient(180deg, rgba(94,64,25,.2), rgba(17,12,15,.98));
    box-shadow: 0 0 35px rgba(255,209,111,.055);
}

.podium-card.second { min-height: 165px; }
.podium-card.third { min-height: 150px; }

.podium-crown {
    margin-bottom: 6px;
    font-size: 23px;
    filter: drop-shadow(0 0 10px rgba(255,209,111,.3));
}

.podium-medal {
    display: grid;
    place-items: center;
    width: 47px;
    height: 47px;
    margin: 0 auto 10px;
    border: 2px solid currentColor;
    border-radius: 50%;
    color: var(--gold);
    background: radial-gradient(circle at 30% 25%, rgba(255,255,255,.2), rgba(255,209,111,.08));
    box-shadow: 0 0 20px rgba(255,209,111,.08);
    font-size: 21px;
}

.second .podium-medal {
    color: var(--silver);
    background: rgba(213,219,229,.07);
}

.third .podium-medal {
    color: var(--bronze);
    background: rgba(217,155,114,.07);
}

.podium-rank {
    margin-bottom: 8px;
    color: var(--gold);
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 2px;
}

.second .podium-rank { color: var(--silver); }
.third .podium-rank { color: var(--bronze); }

.podium-name {
    overflow-wrap: anywhere;
    color: #f6edf0;
    font-size: 12px;
    font-weight: 800;
}

.podium-coins {
    margin-top: 8px;
    color: var(--gold);
    font-size: 12px;
    font-weight: 900;
}

.podium-coins span {
    margin-right: 3px;
    font-size: 13px;
}

.podium-current {
    display: inline-block;
    margin-top: 7px;
    color: var(--red-light);
    font-size: 8px;
    font-weight: 900;
    letter-spacing: 1px;
}

/* Leaderboard table */

.leaderboard-card {
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.09);
    border-radius: 13px;
    background: rgba(17,12,17,.91);
    box-shadow: 0 20px 60px rgba(0,0,0,.22);
}

.leaderboard-row {
    display: grid;
    grid-template-columns: 78px minmax(0,1fr) 130px;
    align-items: center;
    gap: 13px;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,.055);
    transition: background .2s ease;
}

.leaderboard-row:last-child { border-bottom: none; }

.leaderboard-header {
    padding-top: 13px;
    padding-bottom: 13px;
    background: rgba(255,255,255,.025);
    color: #958790;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.6px;
    text-transform: uppercase;
}

.leaderboard-header .player-coins { color: #958790; }

.leaderboard-row:not(.leaderboard-header):hover {
    background: rgba(255,255,255,.025);
}

.leaderboard-row.current-player {
    background: linear-gradient(90deg, rgba(237,38,63,.12), rgba(237,38,63,.025));
    box-shadow: inset 3px 0 var(--red-light);
}

.rank {
    color: #c3b4bc;
    font-size: 12px;
    font-weight: 900;
}

.top-rank { color: var(--gold); }

.player-cell {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.player-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 35px;
    height: 35px;
    border: 1px solid rgba(237,38,63,.22);
    border-radius: 10px;
    color: #ffc0c9;
    background: linear-gradient(145deg, rgba(237,38,63,.16), rgba(255,255,255,.025));
    font-size: 12px;
    font-weight: 900;
}

.current-player .player-avatar {
    border-color: rgba(255,82,101,.55);
    background: rgba(237,38,63,.2);
}

.player-details { min-width: 0; }

.player-name {
    display: block;
    overflow-wrap: anywhere;
    color: #f3e9ed;
    font-size: 11px;
    font-weight: 800;
}

.you-label {
    display: inline-block;
    margin-left: 7px;
    padding: 3px 5px;
    border: 1px solid rgba(255,82,101,.3);
    border-radius: 4px;
    color: var(--red-light);
    background: rgba(237,38,63,.08);
    font-size: 7px;
    font-weight: 900;
    letter-spacing: .7px;
    vertical-align: middle;
}

.player-subtitle {
    margin-top: 4px;
    color: #8e8088;
    font-size: 9px;
}

.player-coins {
    color: var(--gold);
    text-align: right;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
}

.player-coins small {
    display: block;
    margin-top: 4px;
    color: #93848b;
    font-size: 8px;
    font-weight: 600;
}

/* Empty and error states */

.state-message {
    padding: 35px 20px;
    color: #b8aab0;
    text-align: center;
    font-size: 12px;
    line-height: 1.8;
}

.state-icon {
    margin-bottom: 10px;
    font-size: 28px;
}

.state-message strong {
    display: block;
    margin-bottom: 5px;
    color: #f3e9ed;
}

/* Footer actions */

.footer-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 22px;
}

.footer-note {
    color: #8e8088;
    font-size: 10px;
    line-height: 1.7;
}

.footer-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid rgba(255,82,101,.4);
    border-radius: 8px;
    color: white;
    background: linear-gradient(135deg, #e92540, #991026);
    text-decoration: none;
    font-size: 10px;
    font-weight: 800;
    transition: .2s ease;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(237,38,63,.2);
}

.action-button.secondary {
    border-color: rgba(255,255,255,.12);
    color: #ded1d7;
    background: rgba(255,255,255,.04);
}

.bottom-mark {
    margin-top: 35px;
    color: #5f535a;
    font-size: 9px;
    letter-spacing: 2px;
    text-align: center;
}

/* Animations */

@keyframes fogMove {
    from { transform: translate(-3%, 1%) scale(1); }
    to { transform: translate(4%, -3%) scale(1.12); }
}

@keyframes moonPulse {
    from { opacity: .09; }
    to { opacity: .18; }
}

@keyframes blink { 50% { opacity: .4; } }

@keyframes enter {
    from { opacity: 0; transform: translateY(9px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive layout */

@media (max-width: 600px) {
    .topbar { min-height: 56px; padding: 10px 14px; }
    .brand { font-size: 15px; letter-spacing: 1px; }
    .nav-link { padding: 9px 10px; font-size: 9px; }

    main {
        width: calc(100% - 22px);
        padding-top: 18px;
    }

    .hero { padding: 24px 12px; }
    .hero::before, .hero::after { display: none; }
    .hero h1 { font-size: 46px; }
    .hero p { font-size: 11px; }

    .player-position { padding: 13px; }
    .position-value { font-size: 23px; }

    .podium { gap: 7px; }
    .podium-card { padding: 13px 5px; }
    .podium-card.first { min-height: 170px; }
    .podium-card.second { min-height: 150px; }
    .podium-card.third { min-height: 140px; }
    .podium-medal { width: 38px; height: 38px; font-size: 18px; }
    .podium-crown { font-size: 19px; }
    .podium-name { font-size: 10px; }
    .podium-coins { font-size: 10px; }

    .leaderboard-row {
        grid-template-columns: 43px minmax(0,1fr) 85px;
        gap: 7px;
        padding: 13px 10px;
    }

    .player-cell { gap: 8px; }
    .player-avatar { width: 30px; height: 30px; border-radius: 8px; }
    .player-name { font-size: 10px; }
    .player-subtitle { font-size: 8px; }
    .player-coins { font-size: 10px; }
    .you-label { margin-left: 4px; }
    .footer-actions { align-items: flex-start; flex-direction: column; }
    .footer-buttons { width: 100%; }
    .action-button { flex: 1; }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
    }
}
</style>
</head>

<body>
<div class="haunted-bg" aria-hidden="true">
    <div class="moon"></div>
    <div class="fog"></div>
    <div class="fog two"></div>
    <canvas id="particles"></canvas>
    <div class="background-shade"></div>
</div>

<nav class="topbar">
    <a class="brand" href="../index.php" aria-label="Ghost Heist home">
        <span class="brand-ghost">GHOST</span>
        <span class="brand-heist"> HEIST</span>
    </a>

    <a class="nav-link" href="dashboard.php">← Dashboard</a>
</nav>

<main>
    <div class="breadcrumb">
        <a href="dashboard.php">Dashboard</a>
        &nbsp; / &nbsp; Hall of Fame
    </div>

    <header class="hero">
        <p class="eyebrow"><span class="live-dot"></span> The treasure hall</p>
        <h1>Leaderboard</h1>
        <p>
            Every coin tells a story. Discover the mansion's greatest treasure
            hunters and see who rules the heist.
        </p>
    </header>

    <?php if ($error === null && !empty($players)): ?>
        <section class="player-position" aria-label="Your leaderboard position">
            <div>
                <div class="position-label">Your position</div>
                <div class="position-subtitle">
                    <?= $currentRank !== null
                        ? 'Keep playing to climb the rankings.'
                        : 'Start playing to appear among the treasure hunters.' ?>
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
                <h2 id="championsTitle">The champions</h2>
                <p>The three players with the most coins</p>
            </div>

            <span class="player-count">
                <?= $error === null ? count($players) : '—' ?>
                PLAYERS
            </span>
        </div>

        <?php if ($error !== null): ?>
            <div class="leaderboard-card">
                <div class="state-message" role="alert">
                    <div class="state-icon">⚠</div>
                    <strong>Something went wrong</strong>
                    <?= escapeLeaderboardValue($error) ?>
                </div>
            </div>

        <?php elseif (empty($players)): ?>
            <div class="leaderboard-card">
                <div class="state-message">
                    <div class="state-icon">👻</div>
                    <strong>The hall is still empty</strong>
                    No players yet. Be the first to enter the mansion!
                </div>
            </div>

        <?php else: ?>
            <div class="podium">
                <?php
                $podiumPositions = [
                    ['index' => 1, 'class' => 'second', 'label' => '2ND PLACE', 'medal' => 'Ⅱ'],
                    ['index' => 0, 'class' => 'first', 'label' => 'CHAMPION', 'medal' => 'Ⅰ'],
                    ['index' => 2, 'class' => 'third', 'label' => '3RD PLACE', 'medal' => 'Ⅲ']
                ];

                foreach ($podiumPositions as $position):
                    $playerIndex = $position['index'];

                    if (!isset($players[$playerIndex])) {
                        continue;
                    }

                    $player = $players[$playerIndex];
                    $isCurrentPlayer = (int) $player['id'] === $currentUserId;
                    $username = (string) $player['username'];
                    $initial = function_exists('mb_substr')
                        ? mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8')
                        : strtoupper(substr($username, 0, 1));
                ?>
                    <article class="podium-card <?= $position['class'] ?>">
                        <?php if ($position['class'] === 'first'): ?>
                            <div class="podium-crown" aria-hidden="true">👑</div>
                        <?php endif; ?>

                        <div class="podium-medal" aria-hidden="true">
                            <?= escapeLeaderboardValue($position['medal']) ?>
                        </div>

                        <div class="podium-rank">
                            <?= escapeLeaderboardValue($position['label']) ?>
                        </div>

                        <div class="podium-name">
                            <?= escapeLeaderboardValue($username) ?>
                        </div>

                        <div class="podium-coins">
                            <span aria-hidden="true">🪙</span>
                            <?= number_format((int) $player['coins']) ?>
                        </div>

                        <?php if ($isCurrentPlayer): ?>
                            <span class="podium-current">✦ YOU ✦</span>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="section-heading">
                <div>
                    <h2>All treasure hunters</h2>
                    <p>Ranked by total coins collected</p>
                </div>
            </div>

            <section class="leaderboard-card" aria-label="All player rankings">
                <div class="leaderboard-row leaderboard-header">
                    <span>Rank</span>
                    <span>Player</span>
                    <span class="player-coins">Coins</span>
                </div>

                <?php foreach ($players as $index => $player): ?>
                    <?php
                    $rank = $index + 1;
                    $isCurrentPlayer = (int) $player['id'] === $currentUserId;
                    $username = (string) $player['username'];

                    $initial = function_exists('mb_substr')
                        ? mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8')
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

                        <span class="player-coins">
                            🪙 <?= number_format((int) $player['coins']) ?>
                            <small>COINS</small>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </section>

    <footer class="footer-actions">
        <div class="footer-note">
            Your next victory could change everything.<br>
            Keep solving puzzles and collecting coins.
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

    <div class="bottom-mark">GHOST HEIST · CLAIM YOUR PLACE IN THE DARK</div>
</main>

<script>
'use strict';

const canvas = document.getElementById('particles');
const ctx = canvas.getContext('2d');

let particles = [];
let canvasWidth = 0;
let canvasHeight = 0;
let animationFrame = null;

function resizeCanvas() {
    const ratio = Math.min(window.devicePixelRatio || 1, 2);

    canvasWidth = window.innerWidth;
    canvasHeight = window.innerHeight;

    canvas.width = Math.floor(canvasWidth * ratio);
    canvas.height = Math.floor(canvasHeight * ratio);
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

    particles = Array.from(
        { length: Math.min(55, Math.floor(canvasWidth / 22)) },
        () => ({
            x: Math.random() * canvasWidth,
            y: Math.random() * canvasHeight,
            r: .5 + Math.random() * 1.5,
            speed: .12 + Math.random() * .35,
            drift: (Math.random() - .5) * .3,
            alpha: .12 + Math.random() * .35
        })
    );
}

function animateParticles() {
    if (document.hidden) {
        animationFrame = null;
        return;
    }

    ctx.clearRect(0, 0, canvasWidth, canvasHeight);

    for (const particle of particles) {
        particle.y -= particle.speed;
        particle.x += particle.drift;

        if (particle.y < -5) {
            particle.y = canvasHeight + 5;
            particle.x = Math.random() * canvasWidth;
        }

        if (particle.x < -5) particle.x = canvasWidth + 5;
        if (particle.x > canvasWidth + 5) particle.x = -5;

        ctx.beginPath();
        ctx.arc(particle.x, particle.y, particle.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(255, 100, 120, ${particle.alpha})`;
        ctx.fill();
    }

    animationFrame = window.requestAnimationFrame(animateParticles);
}

function startParticles() {
    if (!document.hidden && animationFrame === null) {
        animationFrame = window.requestAnimationFrame(animateParticles);
    }
}

resizeCanvas();
startParticles();

window.addEventListener('resize', () => {
    resizeCanvas();
    startParticles();
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden && animationFrame !== null) {
        window.cancelAnimationFrame(animationFrame);
        animationFrame = null;
    } else {
        startParticles();
    }
});
</script>
</body>
</html>
