
<?php
// Ghost Heist - Public landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Heist | Sneak, Solve & Steal!</title>

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
            font-family: 'Nunito', sans-serif;
            background: var(--cream);
            color: var(--navy);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button, a {
            -webkit-tap-highlight-color: transparent;
        }

        .navbar {
            width: 100%;
            min-height: 78px;
            padding: 14px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border-bottom: 3px solid var(--navy);
            position: relative;
            z-index: 10;
        }

        .brand {
            font-family: 'Fredoka', sans-serif;
            font-size: 29px;
            font-weight: 700;
            color: var(--purple);
        }

        .brand span {
            color: var(--pink);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            font-weight: 800;
        }

        .nav-links a:hover {
            color: var(--purple);
        }

        .nav-button {
            background: var(--yellow);
            padding: 11px 23px;
            border: 2px solid var(--navy);
            border-radius: 14px;
            box-shadow: 3px 4px 0 var(--navy);
            transition: .2s;
        }

        .nav-button:hover {
            transform: translate(2px, 2px);
            box-shadow: 1px 2px 0 var(--navy);
            color: var(--navy) !important;
        }

        .hero {
            min-height: 650px;
            padding: 75px 8%;
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            align-items: center;
            gap: 35px;
            background:
                radial-gradient(circle at 10% 15%, #ffffff 0 5px, transparent 6px),
                radial-gradient(circle at 90% 80%, #ffffff 0 7px, transparent 8px),
                #b8eaff;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .tag {
            display: inline-block;
            padding: 9px 17px;
            background: var(--pink);
            color: #fff;
            border: 2px solid var(--navy);
            border-radius: 30px;
            font-weight: 900;
            transform: rotate(-2deg);
            margin-bottom: 20px;
        }

        .hero h1 {
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(55px, 7vw, 88px);
            line-height: .98;
            font-weight: 700;
            letter-spacing: -2px;
            text-shadow: 4px 5px 0 #fff;
        }

        .hero h1 span {
            color: var(--purple);
            text-shadow: 4px 5px 0 #fff;
        }

        .hero-description {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.8;
            max-width: 510px;
            margin: 24px 0 28px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .main-button,
        .secondary-button {
            display: inline-block;
            padding: 15px 29px;
            border: 3px solid var(--navy);
            border-radius: 15px;
            font-weight: 900;
            font-size: 17px;
            box-shadow: 5px 5px 0 var(--navy);
            transition: .2s;
        }

        .main-button {
            background: var(--yellow);
        }

        .secondary-button {
            background: #fff;
        }

        .main-button:hover,
        .secondary-button:hover {
            transform: translate(3px, 3px);
            box-shadow: 2px 2px 0 var(--navy);
        }

        .hero-art {
            min-height: 390px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .comic-circle {
            width: min(390px, 90%);
            aspect-ratio: 1;
            background: var(--yellow);
            border: 5px solid var(--navy);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 12px 13px 0 var(--navy);
            position: relative;
        }

        .hero-character {
            font-size: clamp(125px, 19vw, 220px);
            filter: drop-shadow(5px 7px 0 rgba(32,35,75,.2));
            transform: rotate(-7deg);
        }

        .comic-bubble {
            position: absolute;
            top: 8%;
            right: -3%;
            background: var(--pink);
            color: white;
            border: 3px solid var(--navy);
            border-radius: 50%;
            padding: 20px;
            font-family: 'Fredoka', sans-serif;
            font-weight: 700;
            font-size: 24px;
            transform: rotate(12deg);
            box-shadow: 4px 4px 0 var(--navy);
        }

        .sparkle {
            position: absolute;
            font-size: 40px;
        }

        .sparkle-one {
            left: 0;
            top: 18%;
        }

        .sparkle-two {
            right: 0;
            bottom: 15%;
        }

        .section {
            padding: 85px 8%;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 45px;
        }

        .section-heading h2 {
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(36px, 5vw, 52px);
            margin-bottom: 12px;
        }

        .section-heading p {
            font-size: 17px;
            font-weight: 700;
            color: #60627c;
        }

        .about {
            background: #fff;
        }

        .about-content {
            max-width: 850px;
            margin: auto;
            text-align: center;
            font-size: 18px;
            line-height: 1.9;
            font-weight: 700;
        }

        .about-highlight {
            display: inline-block;
            margin-top: 25px;
            padding: 13px 22px;
            background: #fff0a9;
            border: 2px solid var(--navy);
            border-radius: 15px;
            font-weight: 900;
        }

        .rooms {
            background: #f5eaff;
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            max-width: 1100px;
            margin: auto;
        }

        .room-card {
            background: #fff;
            border: 3px solid var(--navy);
            border-radius: 22px;
            padding: 28px 23px;
            text-align: center;
            box-shadow: 6px 7px 0 var(--navy);
            transition: .2s;
        }

        .room-card:hover {
            transform: translateY(-7px) rotate(-1deg);
        }

        .room-icon {
            width: 105px;
            height: 105px;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            border: 3px solid var(--navy);
            border-radius: 25px;
            font-size: 55px;
        }

        .room-card:nth-child(1) .room-icon {
            background: #ffdf83;
        }

        .room-card:nth-child(2) .room-icon {
            background: #ffb7ca;
        }

        .room-card:nth-child(3) .room-icon {
            background: #a9e8c4;
        }

        .room-card h3 {
            font-family: 'Fredoka', sans-serif;
            font-size: 25px;
            margin-bottom: 10px;
        }

        .room-card p {
            color: #66677d;
            line-height: 1.7;
            font-weight: 700;
        }

        .how-to {
            background: var(--yellow);
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            max-width: 1050px;
            margin: auto;
        }

        .step {
            background: #fff;
            border: 3px solid var(--navy);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 5px 6px 0 var(--navy);
        }

        .step-number {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--purple);
            color: white;
            display: grid;
            place-items: center;
            font-family: 'Fredoka', sans-serif;
            font-size: 24px;
            border: 2px solid var(--navy);
            margin-bottom: 15px;
        }

        .step h3 {
            font-family: 'Fredoka', sans-serif;
            font-size: 22px;
            margin-bottom: 9px;
        }

        .step p {
            line-height: 1.7;
            font-weight: 700;
            color: #66677d;
        }

        .final-cta {
            padding: 75px 8%;
            background: var(--purple);
            color: #fff;
            text-align: center;
        }

        .final-cta h2 {
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(38px, 5vw, 55px);
            margin-bottom: 15px;
        }

        .final-cta p {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 28px;
        }

        .final-cta .main-button {
            display: inline-block;
        }

        footer {
            padding: 28px 8%;
            background: var(--navy);
            color: #fff;
            text-align: center;
            font-weight: 700;
        }

        footer span {
            color: var(--yellow);
        }

        @media (max-width: 850px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                padding-top: 65px;
            }

            .hero-description {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-art {
                min-height: 330px;
            }

            .room-grid,
            .steps {
                grid-template-columns: 1fr;
                max-width: 480px;
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 13px 5%;
                flex-wrap: wrap;
                gap: 12px;
            }

            .brand {
                font-size: 25px;
            }

            .nav-links {
                gap: 14px;
                font-size: 13px;
            }

            .nav-links .nav-button {
                padding: 8px 12px;
            }

            .hero {
                padding: 55px 6%;
            }

            .hero-art {
                min-height: 280px;
            }

            .comic-bubble {
                font-size: 17px;
                padding: 14px;
            }

            .section {
                padding: 65px 6%;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="brand">GHOST <span>HEIST</span> 🎭</a>

    <nav class="nav-links">
        <a href="#about">About</a>
        <a href="#how-to-play">How to Play</a>
        <a href="login.php">Log In</a>
        <a href="register.php" class="nav-button">Join the Heist</a>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <span class="tag">🎮 THE ULTIMATE SNEAKY ADVENTURE</span>

            <h1>
                SNEAK IN.<br>
                GRAB THE<br>
                <span>TREASURE!</span>
            </h1>

            <p class="hero-description">
                Ready for a hilarious heist? Sneak past the guards,
                hide from watchful eyes, solve tricky Banana puzzles
                and collect the treasure. One wrong move and you're caught!
            </p>

            <div class="hero-buttons">
                <a href="register.php" class="main-button">🚀 Play Now</a>
                <a href="login.php" class="secondary-button">Log In</a>
            </div>
        </div>

        <div class="hero-art">
            <span class="sparkle sparkle-one">✨</span>

            <div class="comic-circle">
                <div class="hero-character">🥷</div>
                <div class="comic-bubble">SHHH!</div>
            </div>

            <span class="sparkle sparkle-two">💰</span>
        </div>
    </section>

    <section class="section about" id="about">
        <div class="section-heading">
            <h2>What's the Big Heist? 🕵️</h2>
            <p>A sneaky adventure full of puzzles, guards and surprises!</p>
        </div>

        <div class="about-content">
            <p>
                Welcome to Ghost Heist! Your mission is to explore different
                rooms, avoid the guards and find hidden objects. But getting
                the treasure isn't that easy. You must stay out of sight,
                solve Banana number puzzles and collect the keys needed
                to unlock the next room.
            </p>

            <div class="about-highlight">
                Three rooms. Three challenges. One big treasure! 🏆
            </div>
        </div>
    </section>

    <section class="section rooms" id="rooms">
        <div class="section-heading">
            <h2>Your Heist Adventure 🗝️</h2>
            <p>Three exciting rooms stand between you and the treasure.</p>
        </div>

        <div class="room-grid">
            <article class="room-card">
                <div class="room-icon">🛋️</div>
                <h3>Room 1: Living Room</h3>
                <p>
                    Explore the living room, avoid Granny's watchful eyes,
                    discover the hidden object and solve the first puzzle.
                </p>
            </article>

            <article class="room-card">
                <div class="room-icon">🍳</div>
                <h3>Room 2: Kitchen</h3>
                <p>
                    Sneak around the kitchen, watch out for the guard
                    and complete another puzzle to find your next key.
                </p>
            </article>

            <article class="room-card">
                <div class="room-icon">💎</div>
                <h3>Room 3: Treasure Room</h3>
                <p>
                    This is the final challenge! Reach the chest,
                    avoid getting caught and claim your treasure.
                </p>
            </article>
        </div>
    </section>

    <section class="section how-to" id="how-to-play">
        <div class="section-heading">
            <h2>How to Play 🎯</h2>
            <p>Follow these simple steps to become the ultimate sneaky thief.</p>
        </div>

        <div class="steps">
            <article class="step">
                <div class="step-number">1</div>
                <h3>Move Carefully</h3>
                <p>
                    Use your keyboard to move around the room.
                    Keep an eye on the guards and stay out of their sight.
                </p>
            </article>

            <article class="step">
                <div class="step-number">2</div>
                <h3>Find & Solve</h3>
                <p>
                    Look for sparkling hidden objects. Interact with them
                    when it is safe and solve the Banana puzzles.
                </p>
            </article>

            <article class="step">
                <div class="step-number">3</div>
                <h3>Collect the Treasure</h3>
                <p>
                    Collect keys to unlock new rooms. Complete all three
                    challenges and grab the final treasure!
                </p>
            </article>
        </div>
    </section>

    <section class="final-cta">
        <h2>Ready to Pull Off the Perfect Heist?</h2>
        <p>Your adventure is waiting. Can you escape without getting caught?</p>
        <a href="register.php" class="main-button">Let's Play! 🥷</a>
    </section>
</main>

<footer>
    <p>© <?php echo date('Y'); ?> <span>Ghost Heist</span> · Sneak smart. Solve fast. Get rich!</p>
</footer>

</body>
</html>
