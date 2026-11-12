
<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

$error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config.php';

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($email) > 100) {
        $error = 'Email address is too long.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } else {
        try {
            $check = $pdo->prepare(
                'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1'
            );
            $check->execute([$username, $email]);

            if ($check->fetch()) {
                $error = 'That username or email is already registered.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, email, password)
                     VALUES (?, ?, ?)'
                );
                $stmt->execute([$username, $email, $hashedPassword]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                $_SESSION['username'] = $username;

                header('Location: pages/dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Ghost Heist registration error: ' . $e->getMessage());
            $error = 'Registration could not be completed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fff7e8">
    <title>Join the Heist | Ghost Heist</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --purple: #7046c8;
            --purple-dark: #50309b;
            --yellow: #ffca45;
            --pink: #ff7896;
            --blue: #72c8ed;
            --ink: #29213e;
            --muted: #777187;
            --cream: #fff7e8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 18%, #ffe4a3 0 8%, transparent 8.2%),
                radial-gradient(circle at 88% 75%, #e6d8ff 0 11%, transparent 11.2%),
                var(--cream);
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 6%;
        }

        .brand {
            color: var(--purple);
            font-family: 'Fredoka', sans-serif;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .brand span {
            color: #ff8a45;
        }

        .top-link {
            color: var(--purple-dark);
            font-size: 14px;
            font-weight: 900;
        }

        .top-link:hover {
            color: var(--pink);
        }

        .layout {
            width: min(1100px, 90%);
            min-height: calc(100vh - 90px);
            margin: auto;
            padding: 25px 0 55px;
            display: grid;
            grid-template-columns: 1fr 430px;
            align-items: center;
            gap: 65px;
        }

        .intro {
            animation: appear .7s ease both;
        }

        .intro-label {
            display: inline-block;
            padding: 9px 17px;
            border: 2px solid var(--ink);
            border-radius: 30px;
            background: var(--yellow);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
            transform: rotate(-3deg);
        }

        .intro h1 {
            margin: 24px 0 16px;
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(55px, 7vw, 85px);
            font-weight: 700;
            line-height: .98;
            letter-spacing: -3px;
        }

        .intro h1 span {
            display: block;
            color: var(--purple);
        }

        .intro-copy {
            max-width: 440px;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.9;
            font-weight: 700;
        }

        .cartoon-scene {
            position: relative;
            width: min(100%, 390px);
            height: 235px;
            margin-top: 32px;
            border: 3px solid var(--ink);
            border-radius: 25px;
            background: #bdeaff;
            box-shadow: 7px 8px 0 var(--ink);
            overflow: hidden;
        }

        .scene-window {
            position: absolute;
            top: 22px;
            right: 25px;
            width: 105px;
            height: 95px;
            border: 4px solid var(--ink);
            border-radius: 13px;
            background: #fff5c6;
        }

        .scene-window::before,
        .scene-window::after {
            content: "";
            position: absolute;
            background: var(--ink);
        }

        .scene-window::before {
            width: 4px;
            height: 100%;
            left: 50%;
        }

        .scene-window::after {
            width: 100%;
            height: 4px;
            top: 50%;
        }

        .scene-floor {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 55px;
            background: #f4b875;
            border-top: 4px solid var(--ink);
        }

        .scene-door {
            position: absolute;
            bottom: 55px;
            left: 35px;
            width: 70px;
            height: 115px;
            border: 4px solid var(--ink);
            border-radius: 12px 12px 0 0;
            background: #9a72d9;
        }

        .scene-door::after {
            content: "";
            position: absolute;
            top: 52%;
            right: 9px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--yellow);
            border: 2px solid var(--ink);
        }

        .cartoon-character {
            position: absolute;
            bottom: 32px;
            left: 135px;
            z-index: 2;
            font-size: 78px;
            filter: drop-shadow(3px 4px 0 rgba(41,33,62,.15));
            animation: bob 2s ease-in-out infinite;
        }

        .scene-star {
            position: absolute;
            top: 22px;
            left: 135px;
            color: #ffad35;
            font-size: 32px;
            animation: bob 2.5s ease-in-out infinite;
        }

        .scene-caption {
            position: absolute;
            right: 15px;
            bottom: 12px;
            z-index: 3;
            padding: 6px 11px;
            border: 2px solid var(--ink);
            border-radius: 20px;
            background: white;
            font-size: 10px;
            font-weight: 900;
            transform: rotate(-3deg);
        }

        .auth-card {
            padding: 34px;
            border: 3px solid var(--ink);
            border-radius: 27px;
            background: #fff;
            box-shadow: 9px 10px 0 var(--ink);
            animation: appear .8s .1s ease both;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-icon {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            border: 2px solid var(--ink);
            border-radius: 17px;
            background: var(--yellow);
            font-size: 28px;
            transform: rotate(-5deg);
        }

        .card-tag {
            padding: 7px 12px;
            border-radius: 20px;
            background: #f0e9ff;
            color: var(--purple);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .7px;
        }

        .auth-card h2 {
            margin: 23px 0 5px;
            font-family: 'Fredoka', sans-serif;
            font-size: 35px;
            font-weight: 700;
        }

        .card-description {
            margin: 0 0 24px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.7;
        }

        .auth-error {
            padding: 12px 14px;
            border: 2px solid #e75c78;
            border-radius: 12px;
            background: #fff0f2;
            color: #bd304f;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.5;
        }

        .field {
            margin-top: 17px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 900;
        }

        .input-wrap {
            position: relative;
        }

        .field input {
            width: 100%;
            min-height: 49px;
            padding: 13px 14px;
            border: 2px solid #e6e0ef;
            border-radius: 12px;
            outline: none;
            background: #fcfaff;
            color: var(--ink);
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            transition: .2s;
        }

        .field input::placeholder {
            color: #aaa4b5;
        }

        .field input:focus {
            border-color: var(--purple);
            background: white;
            box-shadow: 0 0 0 4px #7046c81c;
        }

        .password-input {
            padding-right: 75px !important;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            padding: 6px;
            border: none;
            background: transparent;
            color: var(--purple);
            font-family: inherit;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
        }

        .submit-button {
            width: 100%;
            min-height: 52px;
            margin-top: 25px;
            border: 2px solid var(--ink);
            border-radius: 13px;
            background: var(--purple);
            color: white;
            font-family: 'Fredoka', sans-serif;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 5px 0 var(--ink);
            transition: .2s;
        }

        .submit-button:hover {
            background: var(--purple-dark);
            transform: translateY(-2px);
            box-shadow: 0 7px 0 var(--ink);
        }

        .submit-button:active {
            transform: translateY(3px);
            box-shadow: 0 2px 0 var(--ink);
        }

        .auth-footer {
            margin: 22px 0 0;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .auth-footer a {
            color: var(--purple);
            font-weight: 900;
        }

        .auth-footer a:hover {
            color: #ff7896;
        }

        .card-bottom {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            padding-top: 17px;
            border-top: 1px solid #eee9f4;
            color: #9690a3;
            font-size: 10px;
            font-weight: 800;
        }

        .page-footer {
            padding: 0 20px 22px;
            color: #8b8497;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
        }

        @keyframes appear {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bob {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-7px); }
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
                max-width: 500px;
                gap: 35px;
            }

            .intro {
                text-align: center;
            }

            .intro-copy {
                margin-inline: auto;
            }

            .cartoon-scene {
                margin-inline: auto;
            }
        }

        @media (max-width: 480px) {
            .topbar {
                padding: 19px 5%;
            }

            .brand {
                font-size: 25px;
            }

            .layout {
                width: 91%;
                padding-top: 12px;
            }

            .intro h1 {
                font-size: 58px;
            }

            .auth-card {
                padding: 25px 20px;
                box-shadow: 6px 7px 0 var(--ink);
            }

            .cartoon-scene {
                height: 205px;
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

<header class="topbar">
    <a href="index.php" class="brand">GHOST <span>HEIST</span></a>
    <a href="login.php" class="top-link">Already a player? Log in →</a>
</header>

<main class="layout">

    <section class="intro">
        <span class="intro-label">🎮 YOUR ADVENTURE STARTS HERE</span>

        <h1>
            READY TO
            <span>JOIN THE HEIST?</span>
        </h1>

        <p class="intro-copy">
            Create your player account and get ready for a sneaky adventure!
            Explore colourful rooms, avoid the guards, solve puzzles and
            collect the treasure.
        </p>

        <div class="cartoon-scene" aria-label="Cartoon game room illustration">
            <div class="scene-window"></div>
            <div class="scene-door"></div>
            <div class="scene-star">✦</div>
            <div class="cartoon-character" aria-hidden="true">🥷</div>
            <div class="scene-floor"></div>
            <div class="scene-caption">MISSION: GET THE TREASURE!</div>
        </div>
    </section>

    <section class="auth-card" aria-labelledby="register-title">

        <div class="card-top">
            <div class="card-icon" aria-hidden="true">🎮</div>
            <span class="card-tag">NEW PLAYER</span>
        </div>

        <h2 id="register-title">Create Account</h2>

        <p class="card-description">
            Set up your player identity and enter the game!
        </p>

        <?php if ($error !== ''): ?>
            <p class="auth-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="register.php" method="POST">

            <div class="field">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    minlength="3"
                    maxlength="50"
                    autocomplete="username"
                    placeholder="Choose your player name"
                    required
                >
            </div>

            <div class="field">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    autocomplete="email"
                    placeholder="you@example.com"
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
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
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
                Create My Account →
            </button>

        </form>

        <p class="auth-footer">
            Already have an account?
            <a href="login.php">Log in here</a>
        </p>

        <div class="card-bottom">
            <span>⭐ THINK FAST</span>
            <span>•</span>
            <span>STAY SNEAKY</span>
            <span>•</span>
            <span>💰 GET THE TREASURE</span>
        </div>

    </section>

</main>

<footer class="page-footer">
    GHOST HEIST © 2026 · YOUR NEXT ADVENTURE AWAITS!
</footer>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function () {
        const showPassword = passwordInput.type === 'password';

        passwordInput.type = showPassword ? 'text' : 'password';
        togglePassword.textContent = showPassword ? 'HIDE' : 'SHOW';
        togglePassword.setAttribute('aria-pressed', String(showPassword));
    });
</script>

<script src="assets/js/site-ambience.js"></script>
</body>
</html>
