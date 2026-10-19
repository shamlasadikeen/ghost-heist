
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
    $stmt = $pdo->prepare('SELECT username, coins FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($player) {
        $username = $player['username'];
        $coins = (int) $player['coins'];
    } else {
        session_unset();
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Ghost Heist dashboard error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Ghost Heist</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard { max-width: 1100px; margin: auto; padding: 35px 22px 60px; }
        .dash-nav { display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap; }
        .dash-nav a, .dash-link { color:#c4b5fd; text-decoration:none; }
        .welcome { padding:45px 0 30px; }
        .welcome h1 { font-size:clamp(2rem,5vw,3.5rem); margin:10px 0; }
        .welcome p, .section-intro { color:#b8b1c6; line-height:1.7; }
        .eyebrow { color:#c4b5fd; text-transform:uppercase; letter-spacing:2px; font-size:.8rem; }
        .coin-pill { background:#272036; border:1px solid #494057; padding:10px 16px; border-radius:30px; }
        .difficulty-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; margin:25px 0 45px; }
        .difficulty-card { padding:25px; background:#171321; border:1px solid #393047; border-radius:16px; }
        .difficulty-card h3 { margin-top:10px; }
        .difficulty-card p { color:#b8b1c6; line-height:1.6; min-height:48px; }
        .difficulty-card a { display:inline-block; padding:11px 16px; background:#a78bfa; color:#171321; border-radius:8px; font-weight:700; text-decoration:none; }
        .difficulty-card a:hover { background:#c4b5fd; }
        .feature-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; margin-top:20px; }
        .feature-card { padding:24px; border:1px solid #393047; border-radius:14px; background:#171321; }
        .feature-card p { color:#b8b1c6; line-height:1.6; }
        .coming-soon { color:#c4b5fd; font-size:.85rem; }
        .dash-footer { margin-top:50px; padding-top:20px; border-top:1px solid #393047; color:#938aa3; }
        @media(max-width:700px) { .difficulty-grid { grid-template-columns:1fr; } .feature-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<main class="dashboard">
    <nav class="dash-nav">
        <a href="../index.php"><strong>👻 GHOST HEIST</strong></a>
        <div>
            <span class="coin-pill">🪙 <?= $coins ?> coins</span>
            &nbsp;&nbsp;
            <a href="../logout.php">Log out ↗</a>
        </div>
    </nav>

    <section class="welcome">
        <span class="eyebrow">Your adventure begins</span>
        <h1>Welcome back, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>!</h1>
        <p>The mansion is waiting. Choose your difficulty, solve the puzzles, and race the ghost to the treasure.</p>
    </section>

    <section>
        <h2>Choose Your Heist</h2>
        <p class="section-intro">Each difficulty takes you through five mysterious rooms. Choose wisely!</p>

        <div class="difficulty-grid">
            <article class="difficulty-card">
                <span>🕯️</span>
                <h3>Beginner</h3>
                <p>New to the mansion? Start here and learn to solve the puzzles.</p>
                <a href="rooms.php?difficulty=beginner">Enter Mansion →</a>
            </article>

            <article class="difficulty-card">
                <span>🔮</span>
                <h3>Intermediate</h3>
                <p>Ready for a challenge? Sharpen your mind and move quickly.</p>
                <a href="rooms.php?difficulty=intermediate">Enter Mansion →</a>
            </article>

            <article class="difficulty-card">
                <span>💀</span>
                <h3>Hard</h3>
                <p>Only the bravest thieves dare to face the toughest puzzles.</p>
                <a href="rooms.php?difficulty=hard">Enter Mansion →</a>
            </article>
        </div>
    </section>

    <section>
        <h2>Your Heist Toolkit</h2>
        <div class="feature-grid">
            <article class="feature-card">
                <h3>🏆 Leaderboard</h3>
                <p>See how your treasure-hunting skills compare with other players.</p>
                <a class="dash-link" href="leaderboard.php">View leaderboard →</a>
            </article>

            <article class="feature-card">
                <h3>📜 How to Play</h3>
                <p>Solve each banana puzzle, unlock the next room, collect coins, and reach the final treasure.</p>
                <a class="dash-link" href="../index.php#how-to-play">Read the rules →</a>
            </article>
        </div>
    </section>

    <footer class="dash-footer">
        Ghost Heist · Think fast. Stay brave. Take the treasure.
    </footer>
</main>
</body>
</html>
