
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07070b">
    <meta name="description" content="Enter Ghost Heist, solve five haunted number puzzles and claim the hidden treasure.">
    <title>Ghost Heist | Enter If You Dare</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Creepster&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --red: #c32936;
            --red-light: #ff4655;
            --white: #f3f0ed;
            --muted: #b5b0b5;
            --dark: #07070b;
        }

        * { box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 30px;
        }

        body {
            margin: 0;
            background: var(--dark);
            color: var(--white);
            font-family: Inter, sans-serif;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button { font-family: inherit; }

        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: 10;
            pointer-events: none;
            opacity: .12;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.25'/%3E%3C/svg%3E");
        }

        /* Haunted mansion background */
        .hero {
            position: relative;
            isolation: isolate;
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #09080d;
        }

        .scene {
            position: absolute;
            inset: 0;
            z-index: -3;
            background-image: url("https://images.unsplash.com/photo-1657265514497-02611707af59?auto=format&fit=crop&fm=jpg&q=85&w=2400");
            background-size: cover;
            background-position: center 48%;
            transform: scale(1.04);
            animation: sceneReveal 2s ease-out both,
                       slowZoom 24s ease-in-out infinite alternate;
        }

        .scene::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, #050509 0%, #050509e8 24%, #0505098a 58%, #0505093b 100%),
                linear-gradient(0deg, #07070b 0%, transparent 32%, #0505096b 100%),
                linear-gradient(180deg, #05050985, transparent 25%);
        }

        .red-glow {
            position: absolute;
            z-index: -2;
            width: 50vw;
            height: 50vw;
            max-width: 600px;
            max-height: 600px;
            right: -15%;
            top: 12%;
            border-radius: 50%;
            background: #a20e1d;
            filter: blur(150px);
            opacity: .17;
            animation: pulse 6s ease-in-out infinite alternate;
        }

        .fog {
            position: absolute;
            z-index: -1;
            left: -20%;
            bottom: -30%;
            width: 140%;
            height: 65%;
            pointer-events: none;
            filter: blur(30px);
            opacity: .5;
            background:
                radial-gradient(ellipse at 20% 50%, #c4c3d51b, transparent 42%),
                radial-gradient(ellipse at 65% 30%, #d4d0e31a, transparent 44%),
                radial-gradient(ellipse at 85% 70%, #b7b1ce17, transparent 40%);
            animation: fogDrift 18s ease-in-out infinite alternate;
        }

        #rain {
            position: absolute;
            inset: 0;
            z-index: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: .45;
        }

        /* Navigation */
        .navbar {
            position: relative;
            z-index: 3;
            width: min(1380px, 90%);
            margin: 0 auto;
            min-height: 92px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid #ffffff17;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: .88rem;
            font-weight: 800;
            letter-spacing: 4px;
            white-space: nowrap;
        }

        .brand-mark {
            width: 37px;
            height: 37px;
            display: grid;
            place-items: center;
            border: 1px solid #ff46556b;
            color: var(--red-light);
            font-size: 1.35rem;
            box-shadow: 0 0 25px #c3293626;
        }

        .brand span:last-child { color: var(--red-light); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-links a {
            color: #d0cbd0;
            font-size: .78rem;
            font-weight: 600;
            transition: color .2s;
        }

        .nav-links a:hover { color: white; }

        .nav-login {
            border: 1px solid #ffffff47;
            padding: 11px 19px;
        }

        .sound-toggle {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 13px;
            background: #08070a75;
            border: 1px solid #ffffff29;
            color: white;
            font: inherit;
            font-size: .68rem;
            letter-spacing: 1px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }

        .sound-toggle:hover {
            border-color: var(--red-light);
            background: #c3293620;
        }

        .sound-dot {
            width: 7px;
            height: 7px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #858087;
        }

        .sound-toggle[aria-pressed="true"] .sound-dot {
            background: #ef3d4d;
            box-shadow: 0 0 12px #ef3d4d;
            animation: pulse 1.4s infinite alternate;
        }

        /* Hero content */
        .hero-content {
            position: relative;
            z-index: 2;
            width: min(1380px, 90%);
            margin: auto;
            padding: 75px 0 85px;
            animation: contentReveal 1.2s .2s both;
        }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ff6975;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 32px;
            height: 1px;
            background: var(--red-light);
            box-shadow: 0 0 10px var(--red);
        }

        h1 {
            margin: 25px 0 20px;
            max-width: 900px;
            font-family: Creepster, Georgia, serif;
            font-size: clamp(5rem, 12vw, 10.5rem);
            font-weight: 400;
            line-height: .78;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-shadow: 0 5px 35px #000;
        }

        h1 .line-two {
            display: block;
            margin-top: 20px;
            color: var(--red-light);
            text-shadow: 0 0 35px #a40d1c56, 0 5px 35px #000;
        }

        .hero-description {
            max-width: 480px;
            margin: 30px 0;
            color: #d0cbd0;
            font-size: .97rem;
            line-height: 1.9;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .enter-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 28px;
            min-height: 58px;
            padding: 0 25px;
            overflow: hidden;
            background: var(--red);
            border: 1px solid #ff6671;
            color: white;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            transition: .3s;
        }

        .enter-button::before {
            content: "";
            position: absolute;
            top: -100%;
            left: -40%;
            width: 25%;
            height: 300%;
            transform: rotate(25deg);
            background: #ffffff38;
            transition: left .5s;
        }

        .enter-button:hover {
            background: #ed293b;
            transform: translateY(-3px);
            box-shadow: 0 12px 35px #d4142b50;
        }

        .enter-button:hover::before { left: 130%; }

        .enter-button .arrow {
            font-size: 1.4rem;
            transition: transform .2s;
        }

        .enter-button:hover .arrow { transform: translateX(5px); }

        .secondary-button {
            min-height: 58px;
            display: inline-flex;
            align-items: center;
            padding: 0 22px;
            border: 1px solid #ffffff42;
            color: #eee9ee;
            font-size: .73rem;
            font-weight: 700;
            letter-spacing: 1px;
            transition: .25s;
        }

        .secondary-button:hover {
            background: #ffffff13;
            border-color: white;
        }

        .game-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 35px;
            margin-top: 58px;
        }

        .stat {
            border-left: 1px solid #ff46557d;
            padding-left: 14px;
        }

        .stat strong {
            display: block;
            font-size: 1.5rem;
            font-weight: 800;
        }

        .stat span {
            display: block;
            margin-top: 5px;
            color: #bdb6bf;
            font-size: .61rem;
            letter-spacing: 1.5px;
        }

        .warning {
            position: absolute;
            z-index: 2;
            right: 5%;
            bottom: 45px;
            writing-mode: vertical-rl;
            color: #ffffff8c;
            font-size: .6rem;
            letter-spacing: 4px;
        }

        .warning span { color: var(--red-light); }

        .scroll-hint {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 40px;
            color: #b7b0b9;
            font-size: .62rem;
            letter-spacing: 2px;
        }

        .scroll-line {
            width: 42px;
            height: 1px;
            background: var(--red-light);
            animation: linePulse 2s infinite;
        }

        /* Story and instructions */
        .section {
            padding: 105px 5%;
            text-align: center;
            background:
                radial-gradient(ellipse at 50% 0%, #34101a40, transparent 55%),
                #09080c;
        }

        .section .eyebrow { justify-content: center; }

        .section h2 {
            margin: 22px 0;
            font-size: clamp(2rem, 5vw, 3.8rem);
            letter-spacing: -2px;
        }

        .section h2 span { color: var(--red-light); }

        .section-intro {
            max-width: 620px;
            margin: 0 auto;
            color: var(--muted);
            line-height: 1.9;
        }

        .steps {
            width: min(1050px, 100%);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin: 55px auto 0;
            text-align: left;
        }

        .step-card {
            padding: 28px;
            border: 1px solid #ffffff16;
            background: linear-gradient(145deg, #19151b, #0c0a0f);
            transition: .3s;
        }

        .step-card:hover {
            transform: translateY(-6px);
            border-color: #c329367d;
        }

        .step-number {
            color: var(--red-light);
            font-size: .78rem;
            letter-spacing: 2px;
        }

        .step-card h3 { margin: 22px 0 10px; }

        .step-card p {
            margin-bottom: 0;
            color: var(--muted);
            font-size: .87rem;
            line-height: 1.8;
        }

        /* Footer */
        footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            padding: 28px 5%;
            border-top: 1px solid #ffffff14;
            background: #07070b;
        }

        footer p {
            color: #9c959e;
            font-size: .78rem;
        }

        /* Cinematic registration transition */
        .transition-screen {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: grid;
            place-items: center;
            background: #030306;
            opacity: 0;
            visibility: hidden;
            transition: opacity .8s, visibility .8s;
        }

        .transition-screen.active {
            opacity: 1;
            visibility: visible;
        }

        .transition-screen p {
            padding: 20px;
            color: var(--red-light);
            font-family: Creepster, Georgia, serif;
            font-size: clamp(2rem, 7vw, 5rem);
            letter-spacing: 4px;
            text-align: center;
            animation: pulse 1s infinite alternate;
        }

        .glitch { animation: glitch .25s steps(2, end); }

        /* Animations */
        @keyframes sceneReveal {
            from { opacity: 0; filter: brightness(0); }
            to { opacity: 1; filter: brightness(1); }
        }

        @keyframes slowZoom {
            from { transform: scale(1.04); }
            to { transform: scale(1.13); }
        }

        @keyframes contentReveal {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fogDrift {
            from { transform: translateX(-4%) translateY(0); }
            to { transform: translateX(5%) translateY(-10%); }
        }

        @keyframes pulse {
            from { opacity: .55; }
            to { opacity: 1; }
        }

        @keyframes linePulse {
            0%, 100% {
                opacity: .35;
                transform: scaleX(.65);
                transform-origin: left;
            }
            50% {
                opacity: 1;
                transform: scaleX(1);
            }
        }

        @keyframes glitch {
            0% { transform: translate(0); filter: none; }
            25% { transform: translate(-4px, 2px); filter: hue-rotate(90deg); }
            50% { transform: translate(3px, -1px); filter: contrast(1.8); }
            75% { transform: translate(-2px, -2px); }
            100% { transform: translate(0); filter: none; }
        }

        /* Responsive layout */
        @media (max-width: 760px) {
            .navbar { min-height: 75px; }
            .nav-links { gap: 14px; }
            .nav-links .optional { display: none; }
            .brand { letter-spacing: 2px; font-size: .75rem; }
            .sound-toggle { padding: 9px; }
            .hero-content { padding: 85px 0 75px; }
            h1 { font-size: clamp(4.5rem, 17vw, 7.5rem); }
            .hero-description { font-size: .9rem; }
            .game-stats { gap: 20px; margin-top: 45px; }
            .stat strong { font-size: 1.2rem; }
            .warning { display: none; }
            .steps { grid-template-columns: 1fr; }
            .section { padding: 75px 6%; }
        }

        @media (max-width: 420px) {
            .navbar { width: 92%; gap: 10px; }
            .brand { gap: 7px; font-size: .66rem; }
            .brand-mark { width: 30px; height: 30px; }
            .nav-links { gap: 8px; }
            .nav-links a { font-size: .68rem; }
            .nav-login { padding: 9px; }
            .sound-toggle { padding: 9px 7px; }
            .sound-label { font-size: .58rem; }
            .hero-content { width: 88%; }
            .hero-actions { align-items: stretch; flex-direction: column; }
            .enter-button, .secondary-button { justify-content: center; }
            .game-stats { gap: 13px; }
            .stat { padding-left: 9px; }
            .stat span { font-size: .52rem; letter-spacing: .7px; }
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
    <div class="transition-screen" id="transitionScreen" aria-hidden="true">
        <p>THE DOOR IS OPENING...</p>
    </div>

    <main>
        <section class="hero">
            <div class="scene" aria-hidden="true"></div>
            <div class="red-glow" aria-hidden="true"></div>
            <div class="fog" aria-hidden="true"></div>
            <canvas id="rain" aria-hidden="true"></canvas>

            <header class="navbar">
                <a class="brand" href="index.php" aria-label="Ghost Heist home">
                    <span class="brand-mark">G</span>
                    GHOST <span>HEIST</span>
                </a>

                <nav class="nav-links" aria-label="Main navigation">
                    <a class="optional" href="#about">THE STORY</a>
                    <a class="optional" href="#how-to-play">HOW TO PLAY</a>
                    <a class="nav-login" href="login.php">LOG IN</a>

                    <button class="sound-toggle" id="soundToggle"
                        type="button" aria-pressed="false">
                        <span class="sound-dot"></span>
                        <span class="sound-label" id="soundLabel">SOUND OFF</span>
                    </button>
                </nav>
            </header>

            <div class="hero-content">
                <p class="eyebrow">YOU WERE NOT INVITED</p>

                <h1 id="heroTitle">
                    ENTER IF
                    <span class="line-two">YOU DARE.</span>
                </h1>

                <p class="hero-description">
                    Behind these walls lie five haunted rooms, deadly
                    number puzzles, and a treasure guarded by something
                    that never sleeps. Solve the mystery. Outsmart the
                    ghost. Get out with the gold.
                </p>

                <div class="hero-actions">
                    <a class="enter-button" id="enterButton" href="register.php">
                        <span>ENTER THE MANSION</span>
                        <span class="arrow" aria-hidden="true">&rarr;</span>
                    </a>

                    <a class="secondary-button" href="#how-to-play">
                        DISCOVER THE RULES
                    </a>
                </div>

                <div class="game-stats">
                    <div class="stat">
                        <strong>05</strong>
                        <span>HAUNTED ROOMS</span>
                    </div>
                    <div class="stat">
                        <strong>100</strong>
                        <span>COINS PER ROOM</span>
                    </div>
                    <div class="stat">
                        <strong>01</strong>
                        <span>HIDDEN TREASURE</span>
                    </div>
                </div>

                <a class="scroll-hint" href="#about">
                    <span class="scroll-line"></span>
                    DESCEND INTO THE UNKNOWN
                </a>
            </div>

            <div class="warning">
                <span>WARNING</span> &nbsp; THERE IS NO GUARANTEE YOU WILL LEAVE
            </div>
        </section>

        <section class="section" id="about">
            <p class="eyebrow">THE LEGEND</p>
            <h2>THE HOUSE KNOWS <span>YOU'RE HERE.</span></h2>
            <p class="section-intro">
                An abandoned mansion. A fortune nobody has claimed.
                Five doors stand between you and the treasure.
                Every room tests your mind, and every mistake brings
                you closer to the thing waiting in the dark.
            </p>
        </section>

        <section class="section" id="how-to-play">
            <p class="eyebrow">YOUR SURVIVAL GUIDE</p>
            <h2>THREE STEPS. <span>NO SECOND CHANCES.</span></h2>

            <div class="steps">
                <article class="step-card">
                    <span class="step-number">01 / THE ENTRY</span>
                    <h3>Choose Your Path</h3>
                    <p>Create your identity, choose a difficulty, and enter the mansion.</p>
                </article>

                <article class="step-card">
                    <span class="step-number">02 / THE TRIAL</span>
                    <h3>Defeat the Puzzles</h3>
                    <p>Find the missing numbers and unlock the next haunted room.</p>
                </article>

                <article class="step-card">
                    <span class="step-number">03 / THE PRIZE</span>
                    <h3>Claim the Treasure</h3>
                    <p>Earn coins, complete all five rooms, and discover what lies in the vault.</p>
                </article>
            </div>
        </section>
    </main>

    <footer>
        <a class="brand" href="index.php">
            <span class="brand-mark">G</span>
            GHOST <span>HEIST</span>
        </a>
        <p>Think fast. Stay brave. Take the treasure.</p>
    </footer>

    <script>
        // Animated rain with safe pause/resume handling.
        (() => {
            const canvas = document.getElementById('rain');
            const ctx = canvas.getContext('2d');

            const reducedMotion = window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches;

            let drops = [];
            let width = 0;
            let height = 0;
            let animationId = null;
            let running = false;

            function resize() {
                const rect = canvas.getBoundingClientRect();
                const dpr = Math.min(window.devicePixelRatio || 1, 2);

                width = rect.width;
                height = rect.height;

                canvas.width = Math.round(width * dpr);
                canvas.height = Math.round(height * dpr);
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                drops = Array.from(
                    { length: Math.min(Math.floor(width / 5), 190) },
                    () => ({
                        x: Math.random() * width,
                        y: Math.random() * height,
                        length: 9 + Math.random() * 18,
                        speed: 5 + Math.random() * 9,
                        opacity: .08 + Math.random() * .23
                    })
                );
            }

            function drawRain() {
                if (!running) return;

                ctx.clearRect(0, 0, width, height);

                drops.forEach(drop => {
                    ctx.strokeStyle =
                        `rgba(190, 198, 220, ${drop.opacity})`;
                    ctx.lineWidth = .7;
                    ctx.beginPath();
                    ctx.moveTo(drop.x, drop.y);
                    ctx.lineTo(drop.x - 2, drop.y + drop.length);
                    ctx.stroke();

                    drop.y += drop.speed;
                    drop.x -= .8;

                    if (drop.y > height) {
                        drop.y = -drop.length;
                        drop.x = Math.random() * width;
                    }

                    if (drop.x < 0) drop.x = width;
                });

                animationId = requestAnimationFrame(drawRain);
            }

            function startRain() {
                if (running || reducedMotion) return;
                running = true;
                drawRain();
            }

            function stopRain() {
                running = false;

                if (animationId !== null) {
                    cancelAnimationFrame(animationId);
                    animationId = null;
                }
            }

            resize();
            window.addEventListener('resize', resize);
            startRain();

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopRain();
                } else {
                    startRain();
                }
            });
        })();

        // Layered horror ambience: deep rumble, wind and ghostly tones.
        // Click the sound button to enable audio; browsers block autoplay.
        (() => {
            const button = document.getElementById('soundToggle');
            const label = document.getElementById('soundLabel');

            let audioContext = null;
            let masterGain = null;
            let enabled = false;
            let oscillators = [];
            let noiseSource = null;
            let noiseFilter = null;
            let windLfo = null;

            function createNoiseBuffer(context) {
                const buffer = context.createBuffer(
                    1,
                    context.sampleRate * 4,
                    context.sampleRate
                );

                const data = buffer.getChannelData(0);

                for (let i = 0; i < data.length; i++) {
                    data[i] = Math.random() * 2 - 1;
                }

                return buffer;
            }

            function buildHorrorSound() {
                const AudioContextClass =
                    window.AudioContext || window.webkitAudioContext;

                if (!AudioContextClass) {
                    throw new Error('Web Audio API is not supported by this browser.');
                }

                audioContext = new AudioContextClass();

                masterGain = audioContext.createGain();
                masterGain.gain.value = 0;
                masterGain.connect(audioContext.destination);

                // Deep haunted-house rumble.
                [
                    { frequency: 42, type: 'sine', volume: 0.22 },
                    { frequency: 55, type: 'triangle', volume: 0.13 },
                    { frequency: 73.4, type: 'sine', volume: 0.09 }
                ].forEach(tone => {
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

                // Filtered noise creates a continuous wind-like sound.
                noiseSource = audioContext.createBufferSource();
                noiseSource.buffer = createNoiseBuffer(audioContext);
                noiseSource.loop = true;

                noiseFilter = audioContext.createBiquadFilter();
                noiseFilter.type = 'lowpass';
                noiseFilter.frequency.value = 450;
                noiseFilter.Q.value = 0.7;

                const windGain = audioContext.createGain();
                windGain.gain.value = 0.22;

                noiseSource.connect(noiseFilter);
                noiseFilter.connect(windGain);
                windGain.connect(masterGain);
                noiseSource.start();

                // Slowly moving filter makes the wind sound less static.
                windLfo = audioContext.createOscillator();
                const windLfoGain = audioContext.createGain();

                windLfo.frequency.value = 0.08;
                windLfoGain.gain.value = 260;

                windLfo.connect(windLfoGain);
                windLfoGain.connect(noiseFilter.frequency);
                windLfo.start();

                oscillators.push(windLfo);

                // Dissonant high tones add a ghostly atmosphere.
                [
                    { frequency: 174.6, volume: 0.035 },
                    { frequency: 185, volume: 0.025 },
                    { frequency: 277.2, volume: 0.018 }
                ].forEach(tone => {
                    const oscillator = audioContext.createOscillator();
                    const gain = audioContext.createGain();

                    oscillator.type = 'sine';
                    oscillator.frequency.value = tone.frequency;
                    gain.gain.value = tone.volume;

                    oscillator.connect(gain);
                    gain.connect(masterGain);
                    oscillator.start();

                    oscillators.push(oscillator);
                });
            }

            async function startSound() {
                try {
                    if (!audioContext) {
                        buildHorrorSound();
                    }

                    await audioContext.resume();

                    if (audioContext.state !== 'running') {
                        throw new Error('Browser audio is not running.');
                    }

                    masterGain.gain.cancelScheduledValues(
                        audioContext.currentTime
                    );

                    masterGain.gain.setTargetAtTime(
                        0.8,
                        audioContext.currentTime,
                        0.5
                    );

                    enabled = true;
                    button.setAttribute('aria-pressed', 'true');
                    label.textContent = 'SOUND ON';
                } catch (error) {
                    console.error('Horror sound error:', error);
                    enabled = false;
                    button.setAttribute('aria-pressed', 'false');
                    label.textContent = 'AUDIO ERROR';
                }
            }

            function stopSound() {
                if (audioContext && masterGain) {
                    masterGain.gain.cancelScheduledValues(
                        audioContext.currentTime
                    );

                    masterGain.gain.setTargetAtTime(
                        0,
                        audioContext.currentTime,
                        0.15
                    );
                }

                enabled = false;
                button.setAttribute('aria-pressed', 'false');
                label.textContent = 'SOUND OFF';
            }

            button.addEventListener('click', () => {
                if (enabled) {
                    stopSound();
                } else {
                    startSound();
                }
            });
        })();

        // Occasional visual disturbance on the heading.
        (() => {
            const title = document.getElementById('heroTitle');

            const reducedMotion = window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches;

            if (reducedMotion) return;

            function glitchTitle() {
                title.classList.add('glitch');

                setTimeout(() => {
                    title.classList.remove('glitch');
                }, 260);

                setTimeout(
                    glitchTitle,
                    6500 + Math.random() * 7000
                );
            }

            setTimeout(glitchTitle, 5000);
        })();

        // Cinematic transition before opening registration.
        document.getElementById('enterButton').addEventListener(
            'click',
            event => {
                if (
                    event.ctrlKey ||
                    event.metaKey ||
                    event.shiftKey ||
                    event.altKey ||
                    event.button !== 0
                ) {
                    return;
                }

                event.preventDefault();

                const destination = event.currentTarget.href;
                const screen = document.getElementById('transitionScreen');

                screen.classList.add('active');
                screen.setAttribute('aria-hidden', 'false');

                setTimeout(() => {
                    window.location.href = destination;
                }, 850);
            }
        );
    </script>
</body>
</html>
