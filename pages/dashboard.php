
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$username = $_SESSION['username'] ?? 'Player';
$coins = 0;

try {
    $stmt = $pdo->prepare(
        'SELECT username, coins FROM users WHERE id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($player) {
        $username = $player['username'];
        $coins = (int) $player['coins'];
        $_SESSION['username'] = $username;
    } else {
        session_unset();
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Ghost Heist dashboard error: ' . $e->getMessage());
}

$safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080607">
    <title>Player Dashboard | Ghost Heist</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Creepster&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --red: #ed2638;
            --red-light: #ff5260;
            --bg: #080607;
            --panel: rgba(15, 10, 12, .88);
            --text: #f7f1f1;
            --muted: #b9abad;
            --border: rgba(255, 255, 255, .12);
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            scroll-behavior: smooth;
            background: var(--bg);
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                linear-gradient(90deg, rgba(5,4,5,.88), rgba(5,4,5,.48)),
                linear-gradient(0deg, #080607 0%, transparent 55%, rgba(0,0,0,.4)),
                url("https://images.unsplash.com/photo-1657265514497-02611707af59?auto=format&fit=crop&fm=jpg&q=85&w=2400")
                center / cover no-repeat;
            transform: scale(1.04);
            animation: backgroundReveal 2s ease-out both,
                       slowZoom 28s ease-in-out infinite alternate;
        }

        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse at 75% 35%, transparent 0%, rgba(5,3,5,.35) 75%),
                radial-gradient(ellipse at 10% 85%, rgba(112,0,12,.2), transparent 45%);
        }

        #rain {
            position: fixed;
            inset: 0;
            z-index: 1;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: .2;
        }

        .fog {
            position: fixed;
            z-index: 1;
            left: -20%;
            bottom: -20%;
            width: 140%;
            height: 45%;
            pointer-events: none;
            background: radial-gradient(ellipse, rgba(175,168,174,.12), transparent 65%);
            filter: blur(32px);
            animation: fogDrift 18s ease-in-out infinite alternate;
        }

        a {
            color: inherit;
        }

        .page-shell {
            position: relative;
            z-index: 2;
            min-height: 100vh;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 6%;
            border-bottom: 1px solid rgba(255,255,255,.08);
            background: rgba(8,6,7,.78);
            backdrop-filter: blur(16px);
        }

        .brand {
            flex-shrink: 0;
            color: #fff;
            font-family: 'Creepster', cursive;
            font-size: 30px;
            letter-spacing: 2px;
            text-decoration: none;
            text-shadow: 0 0 18px rgba(237,38,56,.4);
        }

        .brand span {
            color: var(--red);
        }

        .nav-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .coin-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 15px;
            border: 1px solid rgba(255,190,85,.3);
            border-radius: 30px;
            background: rgba(60,37,12,.28);
            color: #ffda91;
            font-size: 12px;
            font-weight: 700;
        }

        .coin-icon {
            font-size: 17px;
        }

        .sound-button,
        .logout-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 9px 13px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 3px;
            background: rgba(0,0,0,.35);
            color: #eee;
            font: inherit;
            font-size: 11px;
            text-decoration: none;
            cursor: pointer;
            transition: .25s;
        }

        .sound-button:hover,
        .logout-link:hover {
            border-color: var(--red);
            color: #fff;
            box-shadow: 0 0 18px rgba(237,38,56,.15);
        }

        .dashboard {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 35px 28px 55px;
        }

        .welcome {
            position: relative;
            padding: 52px 0 42px;
            animation: riseIn .8s both;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--red-light);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 30px;
            height: 1px;
            background: var(--red);
            box-shadow: 0 0 10px var(--red);
        }

        .welcome h1 {
            margin: 20px 0 16px;
            font-size: clamp(35px, 5.5vw, 66px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -2px;
            overflow-wrap: anywhere;
            text-shadow: 0 5px 30px rgba(0,0,0,.65);
        }

        .welcome h1 span {
            color: var(--red-light);
        }

        .welcome-copy {
            max-width: 620px;
            color: #c8bec1;
            font-size: 14px;
            line-height: 1.9;
        }

        .welcome-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-top: 25px;
        }

        .primary-button,
        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 45px;
            padding: 12px 18px;
            border: 1px solid var(--red);
            border-radius: 2px;
            background: var(--red);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-decoration: none;
            text-transform: uppercase;
            transition: .25s;
        }

        .primary-button:hover {
            background: #c91629;
            box-shadow: 0 0 26px rgba(237,38,56,.28);
            transform: translateY(-2px);
        }

        .secondary-button {
            border-color: rgba(255,255,255,.2);
            background: rgba(0,0,0,.25);
            color: #eee;
        }

        .secondary-button:hover {
            border-color: var(--red);
            background: rgba(237,38,56,.08);
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 21px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: clamp(22px, 3vw, 30px);
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .section-heading p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .section-number {
            flex-shrink: 0;
            color: rgba(255,255,255,.28);
            font-family: 'Creepster', cursive;
            font-size: 28px;
            letter-spacing: 2px;
        }

        .difficulty-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 58px;
        }

        .difficulty-card {
            --accent: #ffbd78;
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 315px;
            padding: 24px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.13);
            border-radius: 4px;
            background: linear-gradient(145deg, rgba(34,22,26,.91), rgba(10,8,10,.95));
            box-shadow: 0 15px 35px rgba(0,0,0,.22);
            transition: transform .3s, border-color .3s, box-shadow .3s;
        }

        .difficulty-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: .8;
        }

        .difficulty-card::after {
            content: "";
            position: absolute;
            right: -50px;
            top: -55px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--accent);
            opacity: .07;
            filter: blur(35px);
            pointer-events: none;
        }

        .difficulty-card:hover {
            transform: translateY(-6px);
            border-color: color-mix(in srgb, var(--accent) 60%, transparent);
            box-shadow: 0 20px 45px rgba(0,0,0,.4);
        }

        .difficulty-card.intermediate {
            --accent: #c4a3ff;
        }

        .difficulty-card.hard {
            --accent: #ff6572;
        }

        .difficulty-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .difficulty-symbol {
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            border: 1px solid color-mix(in srgb, var(--accent) 40%, transparent);
            background: color-mix(in srgb, var(--accent) 10%, transparent);
            color: var(--accent);
            font-size: 25px;
        }

        .level-tag {
            padding: 6px 8px;
            border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
            color: var(--accent);
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .difficulty-card h3 {
            margin: 24px 0 10px;
            font-size: 22px;
            font-weight: 800;
        }

        .difficulty-card p {
            margin: 0 0 23px;
            color: #bdb1b5;
            font-size: 12px;
            line-height: 1.85;
            flex: 1;
        }

        .room-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            color: #8f8286;
            font-size: 10px;
            letter-spacing: .5px;
        }

        .difficulty-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            min-height: 44px;
            padding: 12px 14px;
            border: 1px solid color-mix(in srgb, var(--accent) 45%, transparent);
            background: color-mix(in srgb, var(--accent) 9%, transparent);
            color: var(--accent);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            text-decoration: none;
            text-transform: uppercase;
            transition: .25s;
        }

        .difficulty-link:hover {
            background: var(--accent);
            color: #12090b;
        }

        .toolkit {
            margin-top: 8px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }

        .feature-card {
            position: relative;
            padding: 25px;
            border: 1px solid var(--border);
            border-radius: 4px;
            background: var(--panel);
            transition: border-color .25s, transform .25s;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            border-color: rgba(237,38,56,.4);
        }

        .feature-icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border: 1px solid rgba(237,38,56,.35);
            background: rgba(237,38,56,.08);
            color: var(--red-light);
            font-size: 21px;
        }

        .feature-card h3 {
            margin: 20px 0 9px;
            font-size: 17px;
        }

        .feature-card p {
            margin: 0 0 20px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.8;
        }

        .feature-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #ff7c87;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .feature-link:hover {
            color: #fff;
        }

        .survival-note {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-top: 45px;
            padding: 23px;
            border: 1px solid rgba(237,38,56,.2);
            background: linear-gradient(90deg, rgba(100,8,20,.22), rgba(0,0,0,.2));
        }

        .survival-symbol {
            flex-shrink: 0;
            color: var(--red-light);
            font-size: 25px;
        }

        .survival-note strong {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            letter-spacing: .5px;
        }

        .survival-note p {
            margin: 0;
            color: #b9abad;
            font-size: 11px;
            line-height: 1.7;
        }

        .dash-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 48px;
            padding-top: 22px;
            border-top: 1px solid rgba(255,255,255,.1);
            color: #8f8286;
            font-size: 10px;
            letter-spacing: .8px;
        }

        .footer-brand {
            color: #b8a9ad;
            font-family: 'Creepster', cursive;
            font-size: 19px;
            letter-spacing: 1px;
        }

        @keyframes backgroundReveal {
            from { opacity: .35; }
            to { opacity: 1; }
        }

        @keyframes slowZoom {
            from { transform: scale(1.04); }
            to { transform: scale(1.1); }
        }

        @keyframes fogDrift {
            from { transform: translateX(-5%) translateY(0); opacity: .4; }
            to { transform: translateX(5%) translateY(-12%); opacity: .8; }
        }

        @keyframes riseIn {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 850px) {
            .difficulty-grid {
                grid-template-columns: 1fr 1fr;
            }

            .difficulty-card:last-child {
                grid-column: 1 / -1;
                min-height: 275px;
            }

            .welcome {
                padding-top: 35px;
            }
        }

        @media (max-width: 600px) {
            .topbar {
                padding: 15px 18px;
            }

            .brand {
                font-size: 24px;
            }

            .nav-right {
                gap: 7px;
            }

            .coin-pill {
                padding: 8px 10px;
                font-size: 10px;
            }

            .sound-button,
            .logout-link {
                min-height: 34px;
                padding: 8px 9px;
                font-size: 10px;
            }

            .dashboard {
                padding: 20px 18px 35px;
            }

            .welcome {
                padding: 35px 0;
            }

            .welcome h1 {
                letter-spacing: -1px;
            }

            .welcome-copy {
                font-size: 13px;
            }

            .section-heading {
                align-items: flex-start;
            }

            .difficulty-grid,
            .feature-grid {
                grid-template-columns: 1fr;
            }

            .difficulty-card,
            .difficulty-card:last-child {
                grid-column: auto;
                min-height: 285px;
            }

            .difficulty-grid {
                gap: 14px;
                margin-bottom: 45px;
            }

            .feature-card {
                padding: 22px;
            }

            .survival-note {
                align-items: flex-start;
                padding: 18px;
            }
        }

        @media (max-width: 390px) {
            .topbar {
                align-items: flex-start;
            }

            .nav-right {
                max-width: 205px;
            }

            .brand {
                font-size: 21px;
            }

            .welcome-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .primary-button,
            .secondary-button {
                width: 100%;
            }
        }

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
    <canvas id="rain" aria-hidden="true"></canvas>
    <div class="fog" aria-hidden="true"></div>

    <div class="page-shell">
        <header class="topbar">
            <a class="brand" href="../index.php">
                GHOST <span>HEIST</span>
            </a>

            <div class="nav-right">
                <div class="coin-pill" aria-label="<?= $coins ?> coins">
                    <span class="coin-icon" aria-hidden="true">🪙</span>
                    <span><?= number_format($coins) ?> COINS</span>
                </div>

                <button class="sound-button" id="soundToggle"
                        type="button" aria-pressed="false">
                    ♫ SOUND OFF
                </button>

                <a class="logout-link" href="../logout.php">
                    LOG OUT ↗
                </a>
            </div>
        </header>

        <main class="dashboard">
            <section class="welcome">
                <div class="eyebrow">The mansion is calling</div>

                <h1>
                    Welcome back,<br>
                    <span><?= $safeUsername ?>.</span>
                </h1>

                <p class="welcome-copy">
                    The doors are open, the puzzles are waiting, and the
                    treasure is still hidden. Choose your difficulty,
                    outsmart the ghost, and see how far you can make it.
                </p>

                <div class="welcome-actions">
                    <a class="primary-button" href="#choose-heist">
                        Enter the Mansion <span aria-hidden="true">→</span>
                    </a>

                    <a class="secondary-button" href="leaderboard.php">
                        View the Leaderboard
                    </a>
                </div>
            </section>

            <section id="choose-heist">
                <div class="section-heading">
                    <div>
                        <h2>Choose Your Heist</h2>
                        <p>Three difficulty levels. Five rooms. One treasure.</p>
                    </div>
                    <span class="section-number">01 / 03</span>
                </div>

                <div class="difficulty-grid">
                    <article class="difficulty-card beginner">
                        <div class="difficulty-top">
                            <div class="difficulty-symbol" aria-hidden="true">🕯</div>
                            <span class="level-tag">First steps</span>
                        </div>

                        <h3>Beginner</h3>

                        <p>
                            New to the mansion? Learn the puzzle mechanics
                            and discover the secrets hidden in the shadows.
                        </p>

                        <div class="room-info">
                            <span aria-hidden="true">▦</span>
                            5 ROOMS &bull; START YOUR JOURNEY
                        </div>

                        <a class="difficulty-link"
                           href="rooms.php?difficulty=beginner">
                            <span>Enter the Mansion</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </article>

                    <article class="difficulty-card intermediate">
                        <div class="difficulty-top">
                            <div class="difficulty-symbol" aria-hidden="true">🔮</div>
                            <span class="level-tag">Test your mind</span>
                        </div>

                        <h3>Intermediate</h3>

                        <p>
                            The puzzles grow harder. Think quickly, avoid
                            mistakes, and stay ahead of the ghost.
                        </p>

                        <div class="room-info">
                            <span aria-hidden="true">▦</span>
                            5 ROOMS &bull; RISING DIFFICULTY
                        </div>

                        <a class="difficulty-link"
                           href="rooms.php?difficulty=intermediate">
                            <span>Face the Challenge</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </article>

                    <article class="difficulty-card hard">
                        <div class="difficulty-top">
                            <div class="difficulty-symbol" aria-hidden="true">💀</div>
                            <span class="level-tag">Extreme danger</span>
                        </div>

                        <h3>Hard</h3>

                        <p>
                            No easy escape. Face the toughest puzzles,
                            trust your instincts, and fight for the treasure.
                        </p>

                        <div class="room-info">
                            <span aria-hidden="true">▦</span>
                            5 ROOMS &bull; BRAVE THE UNKNOWN
                        </div>

                        <a class="difficulty-link"
                           href="rooms.php?difficulty=hard">
                            <span>Risk Everything</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </article>
                </div>
            </section>

            <section class="toolkit">
                <div class="section-heading">
                    <div>
                        <h2>Your Heist Toolkit</h2>
                        <p>Track your rivals and sharpen your strategy.</p>
                    </div>
                    <span class="section-number">02 / 03</span>
                </div>

                <div class="feature-grid">
                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">🏆</div>

                        <h3>The Leaderboard</h3>

                        <p>
                            Think you are the smartest thief in the mansion?
                            Compare your results with other players and
                            see who rises to the top.
                        </p>

                        <a class="feature-link" href="leaderboard.php">
                            View rankings <span aria-hidden="true">→</span>
                        </a>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">📜</div>

                        <h3>How to Play</h3>

                        <p>
                            Learn how to solve the banana puzzles, unlock
                            rooms, collect coins, and reach the final treasure.
                        </p>

                        <a class="feature-link"
                           href="../index.php#how-to-play">
                            Read the rules <span aria-hidden="true">→</span>
                        </a>
                    </article>
                </div>
            </section>

            <aside class="survival-note">
                <div class="survival-symbol" aria-hidden="true">⚠</div>
                <div>
                    <strong>ONE LAST WARNING</strong>
                    <p>
                        The ghost is always watching. Think before you answer,
                        keep your focus, and do not let the mansion defeat you.
                    </p>
                </div>
            </aside>

            <footer class="dash-footer">
                <span class="footer-brand">GHOST HEIST</span>
                <span>THINK FAST. STAY BRAVE. TRUST NO SHADOW.</span>
                <a class="feature-link" href="../logout.php">Leave the mansion ↗</a>
            </footer>
        </main>
    </div>

    <script>
        // Rain effect
        (() => {
            const canvas = document.getElementById('rain');
            const ctx = canvas.getContext('2d');

            const reduceMotion = window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches;

            let width = 0;
            let height = 0;
            let drops = [];
            let animationFrame = null;

            function resize() {
                const ratio = Math.min(window.devicePixelRatio || 1, 2);

                width = window.innerWidth;
                height = window.innerHeight;

                canvas.width = width * ratio;
                canvas.height = height * ratio;
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';

                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

                drops = Array.from(
                    { length: Math.min(Math.floor(width / 5), 260) },
                    () => ({
                        x: Math.random() * width,
                        y: Math.random() * height,
                        length: 8 + Math.random() * 20,
                        speed: 5 + Math.random() * 9
                    })
                );
            }

            function draw() {
                ctx.clearRect(0, 0, width, height);
                ctx.strokeStyle = 'rgba(190,195,210,.32)';
                ctx.lineWidth = .7;
                ctx.beginPath();

                drops.forEach(drop => {
                    ctx.moveTo(drop.x, drop.y);
                    ctx.lineTo(drop.x - 2, drop.y + drop.length);

                    drop.y += drop.speed;
                    drop.x -= .8;

                    if (drop.y > height) {
                        drop.y = -drop.length;
                        drop.x = Math.random() * width;
                    }

                    if (drop.x < 0) drop.x = width;
                });

                ctx.stroke();

                if (!reduceMotion) {
                    animationFrame = requestAnimationFrame(draw);
                }
            }

            resize();
            window.addEventListener('resize', resize);

            if (reduceMotion) {
                draw();
            } else {
                draw();
            }
        })();

        // Atmospheric horror sound
        (() => {
            const button = document.getElementById('soundToggle');

            let audioContext = null;
            let masterGain = null;
            let soundNodes = [];
            let isPlaying = false;
            let stopping = false;

            function cleanNodes(nodes) {
                nodes.forEach(node => {
                    try {
                        if (typeof node.stop === 'function') node.stop();
                    } catch (_) {}

                    try {
                        node.disconnect();
                    } catch (_) {}
                });
            }

            async function startSound() {
                const AudioContextClass =
                    window.AudioContext || window.webkitAudioContext;

                if (!AudioContextClass) {
                    button.textContent = 'AUDIO NOT SUPPORTED';
                    return;
                }

                if (isPlaying || stopping) return;

                try {
                    if (!audioContext || audioContext.state === 'closed') {
                        audioContext = new AudioContextClass();
                    }

                    await audioContext.resume();

                    const now = audioContext.currentTime;

                    masterGain = audioContext.createGain();
                    masterGain.gain.setValueAtTime(0.0001, now);
                    masterGain.gain.exponentialRampToValueAtTime(
                        0.18, now + 1.5
                    );
                    masterGain.connect(audioContext.destination);

                    // Layered low-frequency drone
                    [65.41, 82.41, 110, 130.81, 164.81].forEach(
                        (frequency, index) => {
                            const oscillator = audioContext.createOscillator();
                            const volume = audioContext.createGain();
                            const filter = audioContext.createBiquadFilter();

                            oscillator.type = index === 2 ? 'triangle' : 'sine';
                            oscillator.frequency.value = frequency;
                            volume.gain.value =
                                [0.24, 0.17, 0.10, 0.07, 0.05][index];

                            filter.type = 'lowpass';
                            filter.frequency.value = 650;

                            oscillator.connect(filter);
                            filter.connect(volume);
                            volume.connect(masterGain);
                            oscillator.start();

                            soundNodes.push(oscillator, volume, filter);
                        }
                    );

                    // Slow volume pulse
                    const pulse = audioContext.createOscillator();
                    const pulseDepth = audioContext.createGain();

                    pulse.frequency.value = 0.14;
                    pulseDepth.gain.value = 0.025;
                    pulse.connect(pulseDepth);
                    pulseDepth.connect(masterGain.gain);
                    pulse.start();

                    soundNodes.push(pulse, pulseDepth);

                    // Generated wind noise
                    const length = audioContext.sampleRate * 3;
                    const noiseBuffer = audioContext.createBuffer(
                        1, length, audioContext.sampleRate
                    );
                    const samples = noiseBuffer.getChannelData(0);

                    for (let i = 0; i < length; i++) {
                        samples[i] = Math.random() * 2 - 1;
                    }

                    const wind = audioContext.createBufferSource();
                    const windFilter = audioContext.createBiquadFilter();
                    const windVolume = audioContext.createGain();

                    wind.buffer = noiseBuffer;
                    wind.loop = true;

                    windFilter.type = 'lowpass';
                    windFilter.frequency.value = 700;
                    windVolume.gain.value = 0.13;

                    wind.connect(windFilter);
                    windFilter.connect(windVolume);
                    windVolume.connect(masterGain);
                    wind.start();

                    soundNodes.push(wind, windFilter, windVolume);

                    isPlaying = true;
                    button.textContent = '♫ SOUND ON';
                    button.setAttribute('aria-pressed', 'true');
                } catch (error) {
                    console.error('Ghost Heist audio error:', error);

                    cleanNodes(soundNodes);
                    soundNodes = [];

                    if (masterGain) {
                        try {
                            masterGain.disconnect();
                        } catch (_) {}
                    }

                    masterGain = null;
                    button.textContent = 'SOUND ERROR - TRY AGAIN';
                }
            }

            function stopSound() {
                if (!audioContext || !masterGain || !isPlaying) return;

                isPlaying = false;
                stopping = true;

                const oldMaster = masterGain;
                const oldNodes = soundNodes;
                const now = audioContext.currentTime;

                oldMaster.gain.cancelScheduledValues(now);
                oldMaster.gain.setTargetAtTime(0.0001, now, 0.12);

                masterGain = null;
                soundNodes = [];

                button.textContent = '♫ SOUND OFF';
                button.setAttribute('aria-pressed', 'false');

                window.setTimeout(() => {
                    cleanNodes(oldNodes);

                    try {
                        oldMaster.disconnect();
                    } catch (_) {}

                    stopping = false;
                }, 600);
            }

            button.addEventListener('click', () => {
                if (isPlaying) {
                    stopSound();
                } else {
                    startSound();
                }
            });
        })();
    </script>
</body>
</html>
