
<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config.php';

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $error = 'Please enter your password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, username, password
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['username'];

                header('Location: pages/dashboard.php');
                exit;
            }

            $error = 'The email or password is incorrect.';
        } catch (PDOException $e) {
            error_log('Ghost Heist login error: ' . $e->getMessage());
            $error = 'Login could not be completed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080607">
    <title>Enter the Mansion | Ghost Heist</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Creepster&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --red: #ed2638;
            --red-light: #ff5260;
            --bg: #080607;
            --text: #f7f1f1;
            --muted: #b9abad;
            --panel: rgba(15, 10, 12, .84);
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
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
                linear-gradient(90deg, rgba(5,4,5,.82), rgba(5,4,5,.35)),
                linear-gradient(0deg, #080607 0%, transparent 45%, rgba(0,0,0,.38)),
                url("https://images.unsplash.com/photo-1657265514497-02611707af59?auto=format&fit=crop&fm=jpg&q=85&w=2400")
                center 55% / cover no-repeat;
            transform: scale(1.04);
            animation: backgroundReveal 2s ease-out both,
                       slowZoom 24s ease-in-out infinite alternate;
        }

        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse at 75% 45%, transparent 5%, rgba(5,3,5,.35) 70%),
                radial-gradient(ellipse at 15% 90%, rgba(112,0,12,.22), transparent 45%),
                repeating-linear-gradient(
                    105deg,
                    transparent 0 90px,
                    rgba(255,255,255,.018) 91px,
                    transparent 92px 160px
                );
        }

        #rain {
            position: fixed;
            inset: 0;
            z-index: 1;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: .24;
        }

        .fog {
            position: fixed;
            z-index: 1;
            left: -20%;
            bottom: -20%;
            width: 140%;
            height: 45%;
            pointer-events: none;
            background: radial-gradient(ellipse, rgba(175,168,174,.13), transparent 65%);
            filter: blur(32px);
            animation: fogDrift 18s ease-in-out infinite alternate;
        }

        .topbar {
            position: relative;
            z-index: 3;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 6%;
        }

        .brand {
            color: #fff;
            font-family: 'Creepster', cursive;
            font-size: 30px;
            letter-spacing: 2px;
            text-decoration: none;
            text-shadow: 0 0 18px rgba(237,38,56,.5);
        }

        .brand span {
            color: var(--red);
        }

        .sound-button {
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 30px;
            padding: 10px 15px;
            color: #eee;
            background: rgba(0,0,0,.55);
            font: inherit;
            font-size: 12px;
            cursor: pointer;
            backdrop-filter: blur(8px);
            transition: .25s;
        }

        .sound-button:hover {
            border-color: var(--red);
            box-shadow: 0 0 20px rgba(237,38,56,.18);
        }

        .layout {
            position: relative;
            z-index: 2;
            min-height: calc(100vh - 85px);
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(340px, 440px);
            align-items: center;
            gap: clamp(35px, 7vw, 110px);
            max-width: 1350px;
            margin: 0 auto;
            padding: 25px 6% 65px;
        }

        .intro {
            max-width: 650px;
            animation: riseIn .9s .1s both;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--red-light);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 32px;
            height: 1px;
            background: var(--red);
            box-shadow: 0 0 10px var(--red);
        }

        .intro h1 {
            margin: 20px 0;
            font-family: 'Creepster', cursive;
            font-size: clamp(64px, 8vw, 112px);
            font-weight: 400;
            line-height: .92;
            letter-spacing: 1px;
            text-shadow: 0 4px 30px rgba(0,0,0,.8);
        }

        .intro h1 span {
            display: block;
            color: var(--red);
            text-shadow: 0 0 30px rgba(237,38,56,.28);
        }

        .intro-copy {
            max-width: 470px;
            color: #d0c5c8;
            font-size: 15px;
            line-height: 1.9;
        }

        .warning {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 30px;
            color: #c3b6b9;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .warning-mark {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border: 1px solid rgba(237,38,56,.5);
            color: var(--red-light);
            background: rgba(237,38,56,.08);
            font-size: 17px;
        }

        .auth-card {
            position: relative;
            padding: 35px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 5px;
            background: var(--panel);
            box-shadow:
                0 30px 100px rgba(0,0,0,.65),
                0 0 45px rgba(112,0,12,.1),
                inset 0 1px rgba(255,255,255,.05);
            backdrop-filter: blur(18px);
            animation: cardReveal 1s .15s both;
        }

        .auth-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--red), transparent);
            box-shadow: 0 0 20px rgba(237,38,56,.65);
        }

        .card-symbol {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(237,38,56,.4);
            background: rgba(237,38,56,.08);
            color: var(--red-light);
            font-size: 23px;
            box-shadow: 0 0 24px rgba(237,38,56,.1);
        }

        .card-kicker {
            margin: 24px 0 7px;
            color: var(--red-light);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .auth-card h2 {
            margin: 0;
            font-family: 'Creepster', cursive;
            font-size: 42px;
            font-weight: 400;
            letter-spacing: .5px;
        }

        .card-description {
            margin: 10px 0 25px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .auth-error {
            margin: 0 0 20px;
            padding: 12px 14px;
            border: 1px solid rgba(255,70,90,.38);
            background: rgba(100,10,23,.3);
            color: #ffb6bd;
            font-size: 12px;
            line-height: 1.6;
        }

        .field {
            margin-top: 20px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: #e7dfe1;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .input-wrap {
            position: relative;
        }

        .field input {
            width: 100%;
            min-height: 49px;
            padding: 13px 14px;
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 2px;
            outline: none;
            background: rgba(0,0,0,.42);
            color: #fff;
            font: inherit;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .field input::placeholder {
            color: #71676b;
        }

        .field input:focus {
            border-color: var(--red);
            background: rgba(20,5,8,.75);
            box-shadow: 0 0 0 3px rgba(237,38,56,.09);
        }

        .password-input {
            padding-right: 72px !important;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            padding: 7px;
            border: 0;
            background: transparent;
            color: #b9abad;
            font: inherit;
            font-size: 10px;
            cursor: pointer;
        }

        .toggle-password:hover {
            color: #fff;
        }

        .submit-button {
            width: 100%;
            min-height: 50px;
            margin-top: 28px;
            border: 1px solid var(--red);
            border-radius: 2px;
            background: var(--red);
            color: #fff;
            font: inherit;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .25s, box-shadow .25s, transform .25s;
        }

        .submit-button:hover {
            background: #c91629;
            box-shadow: 0 0 28px rgba(237,38,56,.3);
            transform: translateY(-1px);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .auth-footer {
            margin: 24px 0 0;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.8;
        }

        .auth-footer a,
        .back-link {
            color: #ff7c87;
            text-decoration: none;
            transition: color .2s;
        }

        .auth-footer a {
            font-weight: 700;
        }

        .auth-footer a:hover,
        .back-link:hover {
            color: #fff;
        }

        .back-link {
            position: relative;
            z-index: 2;
            color: #ddd0d3;
            font-size: 11px;
            letter-spacing: .5px;
        }

        .card-bottom {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 25px;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,.09);
            color: #817478;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .status-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            margin-right: 6px;
            border-radius: 50%;
            background: var(--red);
            box-shadow: 0 0 9px var(--red);
            animation: pulse 1.8s infinite;
        }

        .page-footer {
            position: relative;
            z-index: 2;
            padding: 0 6% 20px;
            color: rgba(230,220,222,.45);
            text-align: center;
            font-size: 10px;
            letter-spacing: 1px;
        }

        @keyframes backgroundReveal {
            from { opacity: .35; }
            to { opacity: 1; }
        }

        @keyframes slowZoom {
            from { transform: scale(1.04); }
            to { transform: scale(1.12); }
        }

        @keyframes fogDrift {
            from { transform: translateX(-5%) translateY(0); opacity: .45; }
            to { transform: translateX(5%) translateY(-12%); opacity: .85; }
        }

        @keyframes riseIn {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes cardReveal {
            from { opacity: 0; transform: translateY(20px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .35; }
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
                max-width: 560px;
                gap: 35px;
                padding-top: 30px;
            }

            .intro {
                text-align: center;
            }

            .eyebrow {
                justify-content: center;
            }

            .intro h1 {
                font-size: clamp(58px, 12vw, 85px);
            }

            .intro-copy {
                margin-inline: auto;
            }

            .warning {
                justify-content: center;
            }

            .auth-card {
                padding: 30px;
            }
        }

        @media (max-width: 480px) {
            .topbar {
                padding: 20px;
            }

            .brand {
                font-size: 25px;
            }

            .layout {
                padding: 20px 18px 40px;
            }

            .intro h1 {
                font-size: 58px;
            }

            .auth-card {
                padding: 25px 20px;
            }

            .auth-card h2 {
                font-size: 37px;
            }

            .card-bottom {
                font-size: 8px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>
    <canvas id="rain" aria-hidden="true"></canvas>
    <div class="fog" aria-hidden="true"></div>

    <header class="topbar">
        <a class="brand" href="index.php">GHOST <span>HEIST</span></a>

        <button class="sound-button" id="soundToggle" type="button"
                aria-pressed="false">
            ♫ SOUND OFF
        </button>
    </header>

    <main class="layout">
        <section class="intro">
            <div class="eyebrow">The mansion remembers you</div>

            <h1>WELCOME <span>BACK</span></h1>

            <p class="intro-copy">
                You know the way in. But do you remember the way out?
                Return to the mansion, solve its mysteries, and claim the
                treasure hidden beyond its locked doors.
            </p>

            <div class="warning">
                <span class="warning-mark" aria-hidden="true">!</span>
                <span>Every second inside brings another mystery.</span>
            </div>
        </section>

        <section class="auth-card" aria-labelledby="login-title">
            <a class="back-link" href="index.php">
                &larr; Return to the entrance
            </a>

            <div class="card-symbol" aria-hidden="true">☠</div>

            <p class="card-kicker">Identity verification</p>

            <h2 id="login-title">Enter the Mansion</h2>

            <p class="card-description">
                Your next adventure is waiting. Identify yourself to continue.
            </p>

            <?php if ($error !== ''): ?>
                <p class="auth-error" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="field">
                    <label for="email">Email address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="100"
                        autocomplete="username"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>

                    <div class="input-wrap">
                        <input
                            class="password-input"
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            class="toggle-password"
                            type="button"
                            id="togglePassword"
                            aria-pressed="false"
                        >
                            SHOW
                        </button>
                    </div>
                </div>

                <button class="submit-button" type="submit">
                    Enter the Mansion &rarr;
                </button>
            </form>

            <p class="auth-footer">
                New to the mansion?
                <a href="register.php">Create an account</a>
            </p>

            <div class="card-bottom">
                <span><i class="status-dot"></i> Mansion online</span>
                <span>Access is being monitored</span>
            </div>
        </section>
    </main>

    <footer class="page-footer">
        GHOST HEIST &bull; THINK FAST. TRUST NO SHADOW.
    </footer>

    <script>
        // Animated rain
        (() => {
            const canvas = document.getElementById('rain');
            const ctx = canvas.getContext('2d');

            const reduceMotion = window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches;

            let width = 0;
            let height = 0;
            let drops = [];

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
                    requestAnimationFrame(draw);
                }
            }

            resize();
            window.addEventListener('resize', resize);
            draw();
        })();

        // Password visibility toggle
        (() => {
            const button = document.getElementById('togglePassword');
            const password = document.getElementById('password');

            button.addEventListener('click', () => {
                const show = password.type === 'password';

                password.type = show ? 'text' : 'password';
                button.textContent = show ? 'HIDE' : 'SHOW';
                button.setAttribute('aria-pressed', String(show));
            });
        })();

        // Ambient horror sound
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

                    // Layered eerie drone
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

                    // Slow atmospheric pulse
                    const pulse = audioContext.createOscillator();
                    const pulseDepth = audioContext.createGain();

                    pulse.frequency.value = 0.14;
                    pulseDepth.gain.value = 0.025;
                    pulse.connect(pulseDepth);
                    pulseDepth.connect(masterGain.gain);
                    pulse.start();

                    soundNodes.push(pulse, pulseDepth);

                    // Soft wind noise
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
