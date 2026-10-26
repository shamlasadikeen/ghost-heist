
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$difficulties = ['beginner', 'intermediate', 'hard'];
$difficulty = strtolower($_GET['difficulty'] ?? 'beginner');

if (!in_array($difficulty, $difficulties, true)) {
    $difficulty = 'beginner';
}

$room = filter_var(
    $_GET['room'] ?? 1,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 5]]
);

if ($room === false) {
    $room = 1;
}

$rooms = [
    1 => 'The Forgotten Entrance',
    2 => 'The Whispering Hall',
    3 => 'The Shadow Library',
    4 => 'The Ghost Chamber',
    5 => 'The Treasure Vault'
];

$roomName = $rooms[$room];
$progress = (int) round(($room / count($rooms)) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#080609">
<title><?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?> | Ghost Heist</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Creepster&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --red: #ed263f;
    --red-light: #ff5265;
    --gold: #ffd16f;
    --bg: #080609;
    --panel: rgba(18, 12, 16, .91);
    --text: #f8f1f3;
    --muted: #b8aab0;
}

* { box-sizing: border-box; }

html { min-height: 100%; }

body {
    margin: 0;
    min-height: 100vh;
    min-height: 100dvh;
    color: var(--text);
    background: var(--bg);
    font-family: 'Inter', sans-serif;
    overflow-x: hidden;
}

button, input { font: inherit; }
button, a { -webkit-tap-highlight-color: transparent; }

button:focus-visible,
a:focus-visible,
input:focus-visible {
    outline: 2px solid var(--gold);
    outline-offset: 3px;
}

/* Haunted background */

.haunted-bg {
    position: fixed;
    inset: 0;
    z-index: -5;
    overflow: hidden;
    background:
        radial-gradient(ellipse at 50% 40%, rgba(100, 8, 25, .35), transparent 52%),
        linear-gradient(#10080d, #080609 85%);
}

.moon {
    position: absolute;
    top: 9%;
    right: 13%;
    width: clamp(90px, 12vw, 155px);
    aspect-ratio: 1;
    border-radius: 50%;
    background: radial-gradient(circle, #d8cbd0 0%, #96818a 38%, rgba(110, 75, 88, .12) 72%, transparent 74%);
    opacity: .22;
    filter: blur(2px);
    box-shadow: 0 0 90px rgba(218, 180, 190, .12);
    animation: moonPulse 8s ease-in-out infinite alternate;
}

.haunted-silhouette {
    position: absolute;
    bottom: -5%;
    left: -5%;
    width: 110%;
    height: 55%;
    opacity: .32;
    background:
        linear-gradient(150deg, transparent 30%, #030204 30.3% 31%, transparent 31.3%),
        linear-gradient(25deg, transparent 42%, #030204 42.3% 43%, transparent 43.3%),
        linear-gradient(180deg, transparent, #030204 80%);
    clip-path: polygon(
        0 45%, 8% 45%, 8% 25%, 14% 25%, 14% 40%,
        23% 40%, 23% 12%, 28% 12%, 28% 39%, 38% 39%,
        38% 30%, 46% 30%, 46% 43%, 58% 43%, 58% 15%,
        64% 15%, 64% 39%, 75% 39%, 75% 25%, 83% 25%,
        83% 42%, 100% 42%, 100% 100%, 0 100%
    );
}

.fog {
    position: absolute;
    inset: -30%;
    opacity: .19;
    filter: blur(38px);
    background:
        radial-gradient(ellipse at 20% 55%, #b3a5b0 0, transparent 34%),
        radial-gradient(ellipse at 70% 70%, #71616e 0, transparent 35%),
        radial-gradient(ellipse at 50% 30%, #5a2535 0, transparent 30%);
    animation: fogMove 25s ease-in-out infinite alternate;
}

.fog.two {
    opacity: .12;
    filter: blur(60px);
    animation-duration: 36s;
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
    background: linear-gradient(180deg, rgba(0, 0, 0, .12), rgba(0, 0, 0, .56));
}

/* Header */

header {
    position: relative;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 54px;
    padding: 9px clamp(14px, 3vw, 38px);
    border-bottom: 1px solid rgba(255, 255, 255, .08);
    background: rgba(7, 5, 7, .72);
    backdrop-filter: blur(15px);
}

.brand {
    text-decoration: none;
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 2px;
    white-space: nowrap;
}

.brand .ghost { color: var(--red-light); }
.brand .heist { color: white; }

.header-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.small-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 34px;
    padding: 7px 11px;
    border: 1px solid rgba(255, 255, 255, .13);
    border-radius: 8px;
    color: #f3e9ed;
    background: rgba(255, 255, 255, .045);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s ease;
}

.small-button:hover {
    border-color: var(--red-light);
    background: rgba(237, 38, 63, .12);
}

#soundToggle[aria-pressed="true"] {
    color: var(--red-light);
    border-color: rgba(255, 82, 101, .5);
}

/* Main content */

main {
    width: min(100% - 28px, 1040px);
    margin: 0 auto;
    padding: 12px 0;
    animation: enter .6s ease both;
}

.breadcrumb {
    margin-bottom: 7px;
    color: var(--muted);
    font-size: 10px;
}

.breadcrumb a {
    color: #eee1e5;
    text-decoration: none;
}

.heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.eyebrow {
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 0 0 4px;
    color: var(--red-light);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.live-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--red-light);
    box-shadow: 0 0 12px var(--red);
    animation: blink 1.6s infinite;
}

h1 {
    margin: 0;
    font-family: 'Creepster', cursive;
    font-size: clamp(29px, 4vw, 43px);
    font-weight: 400;
    line-height: 1.05;
    text-shadow: 0 0 26px rgba(237, 38, 63, .28);
}

.subtitle {
    margin: 4px 0 0;
    color: #c1b3b9;
    font-size: 11px;
    line-height: 1.5;
}

.difficulty {
    flex-shrink: 0;
    padding: 7px 10px;
    border: 1px solid rgba(255, 209, 111, .35);
    border-radius: 8px;
    color: var(--gold);
    background: rgba(255, 209, 111, .06);
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
}

/* Progress */

.progress-panel {
    padding: 9px 12px;
    margin-bottom: 10px;
    border: 1px solid rgba(255, 255, 255, .09);
    border-radius: 10px;
    background: rgba(12, 9, 12, .72);
    backdrop-filter: blur(12px);
}

.progress-labels {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 7px;
    color: #d8cbd0;
    font-size: 9px;
    font-weight: 700;
}

.progress-labels span:last-child { color: var(--gold); }

.progress-track {
    height: 4px;
    overflow: hidden;
    border-radius: 99px;
    background: rgba(255, 255, 255, .1);
}

.progress-fill {
    width: <?= $progress ?>%;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #8d1027, var(--red-light));
    box-shadow: 0 0 14px rgba(237, 38, 63, .6);
}

/* Game layout */

.game-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 220px;
    align-items: start;
    gap: 14px;
}

.game-card,
.mission-card {
    border: 1px solid rgba(255, 255, 255, .09);
    border-radius: 13px;
    background: var(--panel);
    box-shadow: 0 18px 55px rgba(0, 0, 0, .3);
    backdrop-filter: blur(16px);
}

.game-card {
    min-width: 0;
    padding: 14px;
    overflow: hidden;
}

.card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}

.card-top h2 { margin: 0; font-size: 13px; }

.puzzle-label {
    color: var(--red-light);
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Puzzle */

.puzzle-frame {
    display: grid;
    place-items: center;
    min-height: 95px;
    padding: 8px;
    border: 1px solid rgba(237, 38, 63, .24);
    border-radius: 10px;
    background:
        radial-gradient(ellipse at center, rgba(100, 10, 28, .2), transparent 68%),
        rgba(0, 0, 0, .28);
}

.loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 14px 8px;
    color: #c8b9bf;
    text-align: center;
    font-size: 10px;
}

.spinner {
    width: 25px;
    height: 25px;
    border: 2px solid rgba(255, 255, 255, .12);
    border-top-color: var(--red-light);
    border-radius: 50%;
    animation: spin .8s linear infinite;
}

#puzzleImage {
    display: block;
    max-width: 100%;
    max-height: min(34vh, 250px);
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 6px;
    animation: imageIn .35s ease both;
}

#puzzleImage[hidden],
.loading[hidden],
#answerForm[hidden],
#feedback[hidden],
#completion[hidden],
#retryButton[hidden] {
    display: none !important;
}

/* Answer form */

.instruction {
    margin: 9px 0 10px;
    color: #c6b9be;
    font-size: 10px;
    line-height: 1.5;
}

.answer-label {
    display: block;
    margin-bottom: 6px;
    color: #f1e7ea;
    font-size: 10px;
    font-weight: 700;
}

.answer-row { display: flex; gap: 8px; }

#answerInput {
    width: 100%;
    min-width: 0;
    min-height: 40px;
    padding: 0 11px;
    border: 1px solid rgba(255, 255, 255, .14);
    border-radius: 8px;
    color: white;
    background: rgba(0, 0, 0, .35);
    font-size: 12px;
}

#answerInput::placeholder { color: #7f7077; }

#answerInput:focus {
    outline: none;
    border-color: var(--red-light);
    box-shadow: 0 0 0 3px rgba(237, 38, 63, .1);
}

.submit-button,
.primary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 40px;
    padding: 0 13px;
    border: 1px solid rgba(255, 82, 101, .55);
    border-radius: 8px;
    color: white;
    background: linear-gradient(135deg, #f12b45, #a30f29);
    box-shadow: 0 7px 22px rgba(237, 38, 63, .18);
    text-decoration: none;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
    cursor: pointer;
    transition: .2s ease;
}

.submit-button:hover:not(:disabled),
.primary-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 27px rgba(237, 38, 63, .3);
}

.submit-button:disabled { opacity: .6; cursor: wait; }

/* Feedback */

.feedback {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    padding: 10px;
    border: 1px solid rgba(255, 255, 255, .12);
    border-radius: 9px;
    background: rgba(255, 255, 255, .035);
    animation: imageIn .25s ease;
}

.feedback.error {
    border-color: rgba(255, 82, 101, .35);
    background: rgba(237, 38, 63, .08);
}

.feedback.warning { border-color: rgba(255, 209, 111, .3); }

.feedback strong {
    display: block;
    margin-bottom: 4px;
    font-size: 11px;
}

.feedback p {
    margin: 0;
    color: #cbbec3;
    font-size: 10px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.retry-button { margin-top: 10px; }

/* Mission briefing */

.mission-card { padding: 13px; }

.mission-heading {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
    font-size: 12px;
}

.mission-heading span { color: var(--red-light); }

.mission-item {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin: 0 0 10px;
    color: #c5b8be;
    font-size: 10px;
    line-height: 1.5;
}

.mission-item:last-child { margin-bottom: 0; }

.mission-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 27px;
    height: 27px;
    border: 1px solid rgba(255, 255, 255, .1);
    border-radius: 7px;
    color: var(--gold);
    background: rgba(255, 255, 255, .04);
}

.mission-item strong {
    display: block;
    margin-bottom: 2px;
    color: #f3e9ec;
    font-size: 10px;
}

.mission-room {
    margin-top: 10px;
    padding: 9px;
    border: 1px solid rgba(237, 38, 63, .18);
    border-radius: 8px;
    background: rgba(237, 38, 63, .055);
}

.mission-room span {
    display: block;
    color: #a99aa1;
    font-size: 9px;
}

.mission-room strong {
    display: block;
    margin-top: 4px;
    color: var(--red-light);
    font-size: 10px;
}

/* Completion */

#completion {
    padding: 16px 8px 8px;
    text-align: center;
    animation: victoryIn .6s ease both;
}

.victory-icon {
    display: grid;
    place-items: center;
    width: 58px;
    height: 58px;
    margin: 0 auto 9px;
    border: 1px solid rgba(255, 209, 111, .65);
    border-radius: 50%;
    color: var(--gold);
    background: radial-gradient(circle, rgba(255, 209, 111, .2), rgba(255, 209, 111, .025));
    box-shadow: 0 0 35px rgba(255, 209, 111, .15);
    font-size: 26px;
    animation: coinGlow 1.5s ease-in-out infinite alternate;
}

#completion h2 {
    margin: 0 0 6px;
    color: var(--gold);
    font-family: 'Creepster', cursive;
    font-size: clamp(28px, 4vw, 38px);
    font-weight: 400;
}

#completion p {
    margin: 0 auto 12px;
    color: #c9bac0;
    font-size: 10px;
    line-height: 1.6;
}

.reward-box {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-width: 160px;
    margin: 3px auto 14px;
    padding: 9px 15px;
    overflow: hidden;
    border: 1px solid rgba(255, 209, 111, .55);
    border-radius: 11px;
    color: var(--gold);
    background: linear-gradient(135deg, rgba(255, 209, 111, .14), rgba(138, 84, 13, .09));
    box-shadow: 0 0 25px rgba(255, 209, 111, .1);
}

.reward-coin {
    display: grid;
    place-items: center;
    width: 31px;
    height: 31px;
    border: 2px solid #ffe5a0;
    border-radius: 50%;
    color: #704000;
    background: radial-gradient(circle at 30% 25%, #fff2b5, #e6a52d 68%, #9d6413);
    box-shadow: 0 0 16px rgba(255, 209, 111, .4);
    font-size: 16px;
    font-weight: 900;
    animation: coinSpin 1s ease-out both;
}

.reward-amount { font-size: 19px; font-weight: 900; }

.reward-caption {
    display: block;
    margin-top: 2px;
    color: #ead7a6;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 1px;
    text-align: left;
    text-transform: uppercase;
}

.completion-actions {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
}

.secondary-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 39px;
    padding: 0 13px;
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 8px;
    color: #f3e9ed;
    background: rgba(255, 255, 255, .045);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
}

.secondary-button:hover { border-color: rgba(255, 255, 255, .35); }

/* Coin animation */

#coinBurst {
    position: fixed;
    inset: 0;
    z-index: 20;
    overflow: hidden;
    pointer-events: none;
}

.flying-coin {
    position: absolute;
    left: var(--x);
    top: var(--y);
    color: #ffd56c;
    font-size: var(--size);
    text-shadow: 0 0 16px rgba(255, 194, 52, .9);
    animation: coinFly var(--duration) cubic-bezier(.12, .7, .25, 1) forwards;
}

.footer {
    margin-top: 8px;
    color: #83747b;
    font-size: 9px;
    text-align: center;
}

.shake { animation: shake .3s ease; }
.success-flash { animation: successFlash .7s ease; }

/* Animations */

@keyframes fogMove {
    from { transform: translate(-3%, 1%) scale(1); }
    to { transform: translate(4%, -3%) scale(1.12); }
}

@keyframes moonPulse {
    from { opacity: .15; }
    to { opacity: .28; }
}

@keyframes blink { 50% { opacity: .45; } }
@keyframes spin { to { transform: rotate(360deg); } }

@keyframes enter {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes imageIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes victoryIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes coinGlow {
    from { box-shadow: 0 0 18px rgba(255, 209, 111, .12); }
    to { box-shadow: 0 0 42px rgba(255, 209, 111, .32); }
}

@keyframes coinSpin {
    from { transform: rotateY(0deg) scale(.5); opacity: 0; }
    to { transform: rotateY(360deg) scale(1); opacity: 1; }
}

@keyframes coinFly {
    0% { opacity: 0; transform: translate(0, 0) scale(.5) rotate(0); }
    15% { opacity: 1; }
    100% { opacity: 0; transform: translate(var(--dx), var(--dy)) scale(.2) rotate(540deg); }
}

@keyframes shake {
    25% { transform: translateX(-4px); }
    50% { transform: translateX(4px); }
    75% { transform: translateX(-2px); }
}

@keyframes successFlash {
    50% { box-shadow: 0 0 38px rgba(255, 209, 111, .18); }
}

/* Compact laptop layout */

@media (min-width: 761px) and (max-height: 800px) {
    main { padding-top: 7px; padding-bottom: 7px; }
    .breadcrumb { margin-bottom: 4px; }
    .heading { margin-bottom: 7px; }
    h1 { font-size: clamp(28px, 3.5vw, 37px); }
    .progress-panel { padding: 7px 11px; margin-bottom: 8px; }
    .game-card { padding: 11px; }
    .card-top { margin-bottom: 8px; }
    .puzzle-frame { min-height: 75px; }
    #puzzleImage { max-height: min(27vh, 200px); }
    .instruction { margin: 7px 0 8px; }
    .mission-card { padding: 11px; }
    .mission-item { margin-bottom: 7px; }
    .mission-room { margin-top: 8px; }
    .footer { margin-top: 5px; }
}

/* Tablet */

@media (max-width: 760px) {
    main { width: min(100% - 24px, 600px); }
    .game-layout { grid-template-columns: 1fr; }
    .mission-card { padding: 13px; }
    #puzzleImage { max-height: 38vh; }
}

/* Mobile */

@media (max-width: 480px) {
    header { padding: 10px 12px; }
    .brand { font-size: 15px; letter-spacing: 1px; }
    .small-button { padding: 7px 8px; font-size: 9px; }
    .sound-label { display: none; }
    main { width: calc(100% - 22px); padding-top: 13px; }
    .heading { align-items: flex-start; flex-direction: column; }
    .difficulty { align-self: flex-start; }
    .answer-row { flex-direction: column; }
    .submit-button { width: 100%; }
    .puzzle-frame { min-height: 110px; }
}

/* Reduced motion */

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
}
</style>
</head>

<body>
<div class="haunted-bg" aria-hidden="true">
    <div class="moon"></div>
    <div class="haunted-silhouette"></div>
    <div class="fog"></div>
    <div class="fog two"></div>
    <canvas id="particles"></canvas>
    <div class="background-shade"></div>
</div>

<div id="coinBurst" aria-hidden="true"></div>

<header>
    <a class="brand" href="rooms.php" aria-label="Ghost Heist room map">
        <span class="ghost">GHOST</span>
        <span class="heist">HEIST</span>
    </a>

    <div class="header-actions">
        <button class="small-button" type="button" id="soundToggle" aria-pressed="false">
            <span id="soundIcon" aria-hidden="true">♫</span>
            <span class="sound-label" id="soundLabel">Sound Off</span>
        </button>
        <a class="small-button" href="rooms.php">↩ Room Map</a>
    </div>
</header>

<main>
    <div class="breadcrumb">
        <a href="rooms.php">Haunted Mansion</a>
        &nbsp; / &nbsp;
        Room <?= (int) $room ?>
    </div>

    <section class="heading">
        <div>
            <p class="eyebrow">
                <span class="live-dot"></span>
                Your next challenge
            </p>
            <h1><?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="subtitle">Solve the clue. Claim your reward. Escape the room.</p>
        </div>

        <span class="difficulty">
            <?= htmlspecialchars(ucfirst($difficulty), ENT_QUOTES, 'UTF-8') ?> Mode
        </span>
    </section>

    <section class="progress-panel" aria-label="Mansion progress">
        <div class="progress-labels">
            <span>MANSION PROGRESS</span>
            <span>ROOM <?= (int) $room ?> / <?= count($rooms) ?></span>
        </div>

        <div
            class="progress-track"
            role="progressbar"
            aria-valuemin="1"
            aria-valuemax="<?= count($rooms) ?>"
            aria-valuenow="<?= (int) $room ?>"
            aria-label="Current room"
        >
            <div class="progress-fill"></div>
        </div>
    </section>

    <div class="game-layout">
        <section class="game-card" id="gameCard">
            <div class="card-top">
                <h2>Find the answer</h2>
                <span class="puzzle-label">◈ Puzzle challenge</span>
            </div>

            <div class="puzzle-frame">
                <div class="loading" id="loadingState" role="status" aria-live="polite">
                    <span class="spinner"></span>
                    <span id="loadingText">Awakening the mystery...</span>
                </div>

                <img id="puzzleImage" alt="Puzzle clue for this room" hidden>
            </div>

            <p class="instruction" id="instruction">
                Examine the image carefully and enter your answer below.
            </p>

            <form id="answerForm" hidden>
                <label class="answer-label" for="answerInput">Your answer</label>

                <div class="answer-row">
                    <input
                        id="answerInput"
                        name="answer"
                        type="text"
                        placeholder="Enter your answer..."
                        maxlength="200"
                        autocomplete="off"
                        autocapitalize="none"
                        required
                    >

                    <button class="submit-button" id="submitButton" type="submit">
                        <span id="submitText">Submit Answer</span> →
                    </button>
                </div>
            </form>

            <div class="feedback" id="feedback" role="status" aria-live="polite" hidden>
                <span id="feedbackIcon" aria-hidden="true">!</span>
                <div>
                    <strong id="feedbackTitle"></strong>
                    <p id="feedbackMessage"></p>
                </div>
            </div>

            <button class="small-button retry-button" id="retryButton" type="button" hidden>
                ↻ Reload puzzle
            </button>

            <section id="completion" hidden aria-live="polite">
                <div class="victory-icon" aria-hidden="true">✦</div>
                <h2>Room Completed!</h2>
                <p id="completionMessage">You solved the puzzle. Your reward is ready!</p>

                <div class="reward-box" id="rewardBox">
                    <span class="reward-coin" aria-hidden="true">G</span>
                    <span>
                        <span class="reward-amount" id="rewardAmount">+100</span>
                        <span class="reward-caption">Coins earned</span>
                    </span>
                </div>

                <div class="completion-actions">
                    <a class="primary-button" href="rooms.php">Return to Map →</a>

                    <?php if ($room < count($rooms)): ?>
                        <a
                            class="secondary-button"
                            href="?room=<?= (int) ($room + 1) ?>&amp;difficulty=<?= rawurlencode($difficulty) ?>"
                        >Next Room →</a>
                    <?php endif; ?>
                </div>
            </section>
        </section>

        <aside class="mission-card">
            <h3 class="mission-heading"><span aria-hidden="true">⌁</span> Mission briefing</h3>

            <div class="mission-item">
                <span class="mission-icon" aria-hidden="true">⌕</span>
                <div><strong>Investigate</strong>Study the puzzle for clues.</div>
            </div>

            <div class="mission-item">
                <span class="mission-icon" aria-hidden="true">✎</span>
                <div><strong>Answer</strong>Enter your best guess.</div>
            </div>

            <div class="mission-item">
                <span class="mission-icon" aria-hidden="true">✦</span>
                <div><strong>Collect rewards</strong>Correct answers earn coins.</div>
            </div>

            <div class="mission-room">
                <span>CURRENT LOCATION</span>
                <strong>
                    ROOM <?= (int) $room ?> ·
                    <?= htmlspecialchars(strtoupper($difficulty), ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>
        </aside>
    </div>

    <footer class="footer">GHOST HEIST · The mansion is waiting.</footer>
</main>

<script>
'use strict';

const difficulty = <?= json_encode($difficulty) ?>;
const room = <?= (int) $room ?>;

const image = document.getElementById('puzzleImage');
const loading = document.getElementById('loadingState');
const loadingText = document.getElementById('loadingText');
const form = document.getElementById('answerForm');
const answerInput = document.getElementById('answerInput');
const submitButton = document.getElementById('submitButton');
const submitText = document.getElementById('submitText');
const feedback = document.getElementById('feedback');
const feedbackIcon = document.getElementById('feedbackIcon');
const feedbackTitle = document.getElementById('feedbackTitle');
const feedbackMessage = document.getElementById('feedbackMessage');
const retryButton = document.getElementById('retryButton');
const completion = document.getElementById('completion');
const completionMessage = document.getElementById('completionMessage');
const rewardAmount = document.getElementById('rewardAmount');
const instruction = document.getElementById('instruction');
const gameCard = document.getElementById('gameCard');

let loadingPuzzle = false;
let submitting = false;
let completed = false;

function showFeedback(type, title, message) {
    feedback.className = 'feedback ' + type;
    feedbackIcon.textContent = type === 'warning' ? '⚠' : '!';
    feedbackTitle.textContent = title;
    feedbackMessage.textContent = message;
    feedback.hidden = false;
}

function hideFeedback() {
    feedback.hidden = true;
}

function setSubmitting(value) {
    submitButton.disabled = value;
    answerInput.disabled = value;
    submitText.textContent = value ? 'Checking...' : 'Submit Answer';
}

function showLoadError(message) {
    loading.hidden = true;
    image.hidden = true;
    form.hidden = true;
    retryButton.hidden = false;
    showFeedback('error', 'Could not load the puzzle', message);
}

async function loadPuzzle() {
    if (loadingPuzzle) return;

    loadingPuzzle = true;
    completed = false;

    hideFeedback();
    retryButton.hidden = true;
    completion.hidden = true;
    form.hidden = true;
    instruction.hidden = false;
    answerInput.value = '';

    setSubmitting(false);

    loading.hidden = false;
    loadingText.textContent = 'Awakening the mystery...';
    image.hidden = true;

    image.onload = null;
    image.onerror = null;
    image.removeAttribute('src');

    try {
        const url = new URL('../api/get_puzzle.php', window.location.href);
        url.searchParams.set('difficulty', difficulty);
        url.searchParams.set('room', String(room));

        const response = await fetch(url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error('Puzzle server returned HTTP ' + response.status + '.');
        }

        const data = await response.json();

        if (!data || data.success !== true || !data.question) {
            throw new Error(data?.message || 'The puzzle could not be loaded.');
        }

        const imageUrl = new URL(String(data.question), window.location.href);

        if (!['http:', 'https:'].includes(imageUrl.protocol)) {
            throw new Error('The puzzle image URL is invalid.');
        }

        image.onload = () => {
            loading.hidden = true;
            image.hidden = false;
            form.hidden = false;
            loadingPuzzle = false;
            answerInput.focus({ preventScroll: true });
        };

        image.onerror = () => {
            loadingPuzzle = false;
            showLoadError('The image could not be displayed. Check its path and try again.');
        };

        image.src = imageUrl.toString();

    } catch (error) {
        loadingPuzzle = false;
        showLoadError(
            error.message || 'Check that Apache and the puzzle API are running.'
        );
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (submitting || completed || loadingPuzzle) return;

    const answer = answerInput.value.trim();

    if (!answer) {
        showFeedback('warning', 'Enter an answer first', 'Type your answer before submitting.');
        answerInput.focus();
        return;
    }

    /*
     * Prepare/resume audio during the user's submit interaction.
     * This helps browsers permit the reward sound after the API responds.
     */
    prepareAudio();

    submitting = true;
    hideFeedback();
    setSubmitting(true);

    try {
        const response = await fetch('../api/submit_answer.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({ answer })
        });

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error('The server returned an invalid response.');
        }

        if (!response.ok) {
            throw new Error(data?.message || 'The server could not check your answer.');
        }

        if (!data || data.success !== true) {
            showFeedback(
                'error',
                'Could not check your answer',
                data?.message || 'Please try again.'
            );

            gameCard.classList.remove('shake');
            void gameCard.offsetWidth;
            gameCard.classList.add('shake');
            return;
        }

        if (data.correct === true) {
            completed = true;

            form.hidden = true;
            instruction.hidden = true;
            feedback.hidden = true;
            completion.hidden = false;

            completionMessage.textContent = data.message
                ? String(data.message)
                : 'The server confirmed your answer. You have completed this room!';

            // Display the reward returned by the server when available.
            const rawReward =
                data.coins_earned ??
                data.coins_awarded ??
                data.reward_coins ??
                100;

            const reward = Number(rawReward);

            rewardAmount.textContent =
                Number.isFinite(reward) && reward >= 0
                    ? '+' + Math.floor(reward)
                    : '+100';

            gameCard.classList.remove('success-flash');
            void gameCard.offsetWidth;
            gameCard.classList.add('success-flash');

            // Play the reward chime and launch the coin animation.
            playCoinSound();
            launchCoins();

        } else {
            showFeedback(
                'error',
                'Not quite!',
                data.message
                    ? String(data.message)
                    : 'That answer is incorrect. Look at the clue and try again.'
            );

            gameCard.classList.remove('shake');
            void gameCard.offsetWidth;
            gameCard.classList.add('shake');

            answerInput.select();
        }

    } catch (error) {
        showFeedback(
            'error',
            'Connection interrupted',
            error.message || 'Unable to submit your answer. Try again.'
        );
    } finally {
        submitting = false;

        if (!completed) {
            setSubmitting(false);
        }
    }
});

retryButton.addEventListener('click', loadPuzzle);

/* Reward coin animation */

function launchCoins() {
    const layer = document.getElementById('coinBurst');
    layer.replaceChildren();

    for (let i = 0; i < 34; i++) {
        const coin = document.createElement('span');
        coin.className = 'flying-coin';
        coin.textContent = '●';

        coin.style.setProperty('--x', (25 + Math.random() * 50) + 'vw');
        coin.style.setProperty('--y', (30 + Math.random() * 35) + 'vh');
        coin.style.setProperty('--dx', ((Math.random() - .5) * 70) + 'vw');
        coin.style.setProperty('--dy', (-20 - Math.random() * 65) + 'vh');
        coin.style.setProperty('--size', (12 + Math.random() * 17) + 'px');
        coin.style.setProperty('--duration', (1.1 + Math.random() * 1.1) + 's');

        layer.appendChild(coin);
        coin.addEventListener('animationend', () => coin.remove(), { once: true });
    }

    window.setTimeout(() => layer.replaceChildren(), 2600);
}

/* Audio system: ambient drone and independent reward chime */

const soundToggle = document.getElementById('soundToggle');
const soundIcon = document.getElementById('soundIcon');
const soundLabel = document.getElementById('soundLabel');

let audioContext = null;
let masterGain = null;
let ambientEnabled = false;
let ambientStarted = false;

function prepareAudio() {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    if (!AudioContextClass) return false;

    try {
        if (!audioContext) {
            audioContext = new AudioContextClass();

            masterGain = audioContext.createGain();
            masterGain.gain.value = 0;
            masterGain.connect(audioContext.destination);
        }

        // Call resume immediately from a user interaction where possible.
        if (audioContext.state === 'suspended') {
            audioContext.resume().catch((error) => {
                console.warn('Audio context could not resume:', error);
            });
        }

        return true;
    } catch (error) {
        console.warn('Audio could not be prepared:', error);
        return false;
    }
}

function addDrone(frequency, type, volume, detune = 0) {
    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();

    oscillator.type = type;
    oscillator.frequency.value = frequency;
    oscillator.detune.value = detune;
    gain.gain.value = volume;

    oscillator.connect(gain);
    gain.connect(masterGain);
    oscillator.start();
}

async function turnSoundOn() {
    if (!prepareAudio()) {
        showFeedback(
            'warning',
            'Audio unavailable',
            'Your browser does not support the sound feature.'
        );
        return;
    }

    try {
        if (audioContext.state === 'suspended') {
            await audioContext.resume();
        }

        if (!ambientStarted) {
            addDrone(55, 'sine', .32);
            addDrone(82.4, 'triangle', .12, -7);
            addDrone(110, 'sine', .07, 5);
            ambientStarted = true;
        }

        masterGain.gain.cancelScheduledValues(audioContext.currentTime);
        masterGain.gain.setTargetAtTime(.045, audioContext.currentTime, .5);

        ambientEnabled = true;
        soundToggle.setAttribute('aria-pressed', 'true');
        soundLabel.textContent = 'Sound On';
        soundIcon.textContent = '♫';

    } catch (error) {
        console.warn('Ambient sound could not start:', error);

        showFeedback(
            'warning',
            'Sound could not start',
            'Click the sound toggle again to retry.'
        );
    }
}

function turnSoundOff() {
    if (audioContext && masterGain) {
        masterGain.gain.cancelScheduledValues(audioContext.currentTime);
        masterGain.gain.setTargetAtTime(0, audioContext.currentTime, .12);
    }

    ambientEnabled = false;
    soundToggle.setAttribute('aria-pressed', 'false');
    soundLabel.textContent = 'Sound Off';
    soundIcon.textContent = '♫';
}

/*
 * The coin sound does not depend on ambientEnabled.
 * It uses its own gain nodes connected directly to the audio output.
 */
function playCoinSound() {
    if (!prepareAudio() || !audioContext) return;

    const playChime = () => {
        if (!audioContext || audioContext.state !== 'running') return;

        const now = audioContext.currentTime;

        // Three bright notes for a more noticeable coin reward.
        const notes = [
            { frequency: 784, start: 0, duration: .20 },
            { frequency: 1046.5, start: .10, duration: .24 },
            { frequency: 1568, start: .22, duration: .42 }
        ];

        notes.forEach(({ frequency, start, duration }) => {
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(frequency, now + start);

            gain.gain.setValueAtTime(.0001, now + start);
            gain.gain.exponentialRampToValueAtTime(.20, now + start + .015);
            gain.gain.exponentialRampToValueAtTime(
                .0001,
                now + start + duration
            );

            oscillator.connect(gain);
            gain.connect(audioContext.destination);

            oscillator.start(now + start);
            oscillator.stop(now + start + duration + .02);
        });
    };

    if (audioContext.state === 'running') {
        playChime();
    } else {
        audioContext.resume()
            .then(playChime)
            .catch((error) => {
                console.warn('Coin reward sound could not play:', error);
            });
    }
}

soundToggle.addEventListener('click', () => {
    if (ambientEnabled) {
        turnSoundOff();
    } else {
        turnSoundOn();
    }
});

/* Floating background particles */

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
        { length: Math.min(65, Math.floor(canvasWidth / 18)) },
        () => ({
            x: Math.random() * canvasWidth,
            y: Math.random() * canvasHeight,
            r: .5 + Math.random() * 1.8,
            speed: .15 + Math.random() * .5,
            drift: (Math.random() - .5) * .35,
            alpha: .15 + Math.random() * .5
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
    if (document.hidden) {
        if (animationFrame !== null) {
            window.cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }

        if (audioContext && masterGain && ambientEnabled) {
            masterGain.gain.setTargetAtTime(0, audioContext.currentTime, .1);
        }
    } else {
        if (audioContext && masterGain && ambientEnabled) {
            masterGain.gain.setTargetAtTime(.045, audioContext.currentTime, .5);
        }

        startParticles();
    }
});

loadPuzzle();
</script>
</body>
</html>
