
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

    <title>Ghost Heist | Player Dashboard</title>

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

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--cream);
            color: var(--navy);
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .navbar {
            min-height: 76px;
            padding: 13px 7%;
            background: #fff;
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
            gap: 20px;
        }

        .coin-display {
            background: var(--yellow);
            border: 2px solid var(--navy);
            border-radius: 13px;
            padding: 9px 16px;
            font-weight: 900;
            box-shadow: 3px 3px 0 var(--navy);
        }

        .logout {
            padding: 10px 17px;
            background: var(--pink);
            color: white;
            border: 2px solid var(--navy);
            border-radius: 12px;
            font-weight: 900;
            box-shadow: 3px 3px 0 var(--navy);
            transition: .2s;
        }

        .logout:hover {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--navy);
        }

        .container {
            max-width: 1180px;
            margin: auto;
            padding: 48px 25px 70px;
        }

        .welcome {
            background: #b8eaff;
            border: 3px solid var(--navy);
            border-radius: 25px;
            padding: 38px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            box-shadow: 7px 8px 0 var(--navy);
            position: relative;
            overflow: hidden;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-tag {
            display: inline-block;
            padding: 7px 15px;
            background: var(--pink);
            color: white;
            border: 2px solid var(--navy);
            border-radius: 25px;
            font-weight: 900;
            margin-bottom: 15px;
        }

        .welcome h1 {
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(34px, 5vw, 52px);
            line-height: 1.1;
            margin-bottom: 12px;
        }

        .welcome p {
            font-size: 17px;
            font-weight: 800;
            line-height: 1.7;
            max-width: 600px;
        }

        .welcome-character {
            font-size: clamp(90px, 14vw, 150px);
            transform: rotate(-8deg);
            position: relative;
            z-index: 1;
        }

        .section-title {
            font-family: 'Fredoka', sans-serif;
            font-size: 32px;
            margin: 55px 0 10px;
        }

        .section-description {
            color: #686981;
            font-weight: 800;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .difficulty-grid {
    display: grid;
    grid-template-columns: minmax(280px, 520px);
    justify-content: center;
    gap: 22px;
}

        .difficulty-card {
            border: 3px solid var(--navy);
            border-radius: 22px;
            padding: 27px 23px;
            box-shadow: 6px 7px 0 var(--navy);
            transition: .2s;
            display: flex;
            flex-direction: column;
            min-height: 290px;
        }

        .difficulty-card:hover {
            transform: translateY(-6px) rotate(-1deg);
        }

        .difficulty-card.classic {
    background: #baf0d1;
}

        .difficulty-icon {
            font-size: 50px;
            margin-bottom: 14px;
        }

        .difficulty-card h3 {
            font-family: 'Fredoka', sans-serif;
            font-size: 26px;
            margin-bottom: 10px;
        }

        .difficulty-card p {
            font-weight: 700;
            line-height: 1.7;
            color: #45465e;
            margin-bottom: 22px;
            flex-grow: 1;
        }

        .play-button {
            display: block;
            padding: 12px 15px;
            text-align: center;
            background: #fff;
            border: 2px solid var(--navy);
            border-radius: 12px;
            font-weight: 900;
            box-shadow: 3px 3px 0 var(--navy);
            transition: .2s;
        }

        .play-button:hover {
            background: var(--yellow);
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--navy);
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-top: 50px;
        }

        .info-card {
            background: #fff;
            border: 3px solid var(--navy);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 5px 6px 0 var(--navy);
        }

        .info-card h2 {
            font-family: 'Fredoka', sans-serif;
            font-size: 25px;
            margin-bottom: 15px;
        }

        .info-card p,
        .info-card li {
            font-weight: 700;
            line-height: 1.8;
            color: #565770;
        }

        .info-card ul {
            padding-left: 21px;
        }

        .info-link {
            display: inline-block;
            margin-top: 16px;
            padding: 10px 15px;
            background: var(--yellow);
            border: 2px solid var(--navy);
            border-radius: 10px;
            font-weight: 900;
            box-shadow: 3px 3px 0 var(--navy);
        }

        .info-link:hover {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--navy);
        }

        footer {
            background: var(--navy);
            color: #fff;
            padding: 25px;
            text-align: center;
            font-weight: 700;
        }

        footer a {
            color: var(--yellow);
            font-weight: 900;
        }

        @media (max-width: 850px) {
            .difficulty-grid {
                grid-template-columns: 1fr 1fr;
            }

            .welcome {
                padding: 28px;
            }
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 13px 5%;
                flex-wrap: wrap;
            }

            .brand {
                font-size: 24px;
            }

            .nav-right {
                gap: 10px;
            }

            .coin-display,
            .logout {
                padding: 8px 10px;
                font-size: 13px;
            }

            .container {
                padding: 30px 17px 50px;
            }

            .welcome {
                padding: 25px 20px;
            }

            .welcome-character {
                font-size: 70px;
            }

            .difficulty-grid,
            .bottom-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="../index.php" class="brand">GHOST <span>HEIST</span> 🎭</a>

    <div class="nav-right">
        <div class="coin-display">
            🪙 <?php echo number_format($coins); ?> Coins
        </div>

        <a href="../logout.php" class="logout">Log Out</a>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <div class="welcome-content">
            <span class="welcome-tag">🥷 YOUR SECRET HEIST HQ</span>

            <h1>
                Hey, <?php echo $safeUsername; ?>! 👋
            </h1>

            <p>
                Welcome back, sneaky thief! Your next adventure is waiting.
                Explore three rooms, avoid the guards, solve the puzzles
and collect the treasure!
            </p>
        </div>

        <div class="welcome-character">🥷</div>
    </section>

    <h2 class="section-title">Your Adventure 🎮</h2>

    <p class="section-description">
         Enter Classic Mode and get ready for the heist!
    </p>

    <section class="difficulty-grid">

    <article class="difficulty-card classic">

        <div class="difficulty-icon">🎮</div>

        <h3>Classic Mode</h3>

        <p>
            Enter the heist, explore three exciting rooms,
            avoid Granny, solve the puzzles and find the treasure!
        </p>

        <a href="rooms.php" class="play-button">
            Start Your Adventure →
        </a>

    </article>

</section>

    <section class="bottom-grid">

        <article class="info-card">
            <h2>🏆 Your Leaderboard</h2>

            <p>
                Curious to see how you rank against other players?
                Visit the leaderboard and check out the competition.
            </p>

            <a href="leaderboard.php" class="info-link">
                View Leaderboard →
            </a>
        </article>

        <article class="info-card">
            <h2>💡 How to Play</h2>

            <ul>
                <li>Use your keyboard to move around.</li>
                <li>Stay away from the guards' vision.</li>
                <li>Find hidden objects when it is safe.</li>
                <li>Solve Banana puzzles to progress.</li>
                <li>Collect keys and reach the treasure!</li>
            </ul>

            <a href="../index.php#how-to-play" class="info-link">
                Learn the Rules →
            </a>
        </article>

    </section>

</main>

<footer>
    <p>
        © <?php echo date('Y'); ?> Ghost Heist ·
        <a href="../index.php">Back to Home</a>
    </p>
</footer>

<script src="../assets/js/site-ambience.js"></script>
</body>
</html>
