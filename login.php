
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
    <meta name="theme-color" content="#fff9ed">

    <title>Login | Ghost Heist</title>

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
            min-height: 100vh;
            font-family: 'Nunito', sans-serif;
            color: var(--navy);
            background:
                radial-gradient(circle at 10% 15%, #ffffff 0 5px, transparent 6px),
                radial-gradient(circle at 90% 80%, #ffffff 0 7px, transparent 8px),
                var(--cream);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .navbar {
            min-height: 78px;
            padding: 14px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-bottom: 3px solid var(--navy);
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

        .home-link {
            padding: 10px 17px;
            background: var(--yellow);
            border: 2px solid var(--navy);
            border-radius: 12px;
            box-shadow: 3px 3px 0 var(--navy);
            font-weight: 900;
            transition: .2s;
        }

        .home-link:hover {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--navy);
        }

        .login-layout {
            max-width: 1100px;
            min-height: calc(100vh - 145px);
            margin: auto;
            padding: 55px 25px;
            display: grid;
            grid-template-columns: 1fr 440px;
            align-items: center;
            gap: 65px;
        }

        .welcome-section {
            position: relative;
        }

        .welcome-tag {
            display: inline-block;
            padding: 9px 17px;
            background: var(--pink);
            color: white;
            border: 2px solid var(--navy);
            border-radius: 30px;
            font-weight: 900;
            transform: rotate(-2deg);
            margin-bottom: 22px;
        }

        .welcome-section h1 {
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(52px, 7vw, 82px);
            line-height: .98;
            letter-spacing: -2px;
            text-shadow: 4px 5px 0 white;
        }

        .welcome-section h1 span {
            color: var(--purple);
        }

        .welcome-section p {
            max-width: 460px;
            margin-top: 22px;
            font-size: 17px;
            font-weight: 700;
            line-height: 1.8;
            color: #5e6078;
        }

        .character-scene {
            position: relative;
            width: 260px;
            height: 210px;
            margin-top: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--yellow);
            border: 4px solid var(--navy);
            border-radius: 45% 55% 48% 52%;
            box-shadow: 7px 8px 0 var(--navy);
            transform: rotate(-4deg);
        }

        .character {
            font-size: 125px;
            transform: rotate(5deg);
        }

        .speech-bubble {
            position: absolute;
            top: -22px;
            right: -45px;
            padding: 12px 17px;
            background: white;
            border: 3px solid var(--navy);
            border-radius: 18px;
            font-family: 'Fredoka', sans-serif;
            font-size: 19px;
            font-weight: 700;
            transform: rotate(7deg);
            box-shadow: 3px 3px 0 var(--navy);
        }

        .floating-star {
            position: absolute;
            font-size: 35px;
        }

        .star-one {
            top: 0;
            left: -20px;
        }

        .star-two {
            bottom: 5px;
            right: -20px;
        }

        .login-card {
            padding: 35px;
            background: white;
            border: 3px solid var(--navy);
            border-radius: 24px;
            box-shadow: 8px 9px 0 var(--navy);
            animation: appear .6s ease-out;
        }

        .card-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            background: #eee9ff;
            border: 2px solid var(--navy);
            border-radius: 17px;
            font-size: 31px;
            margin-bottom: 20px;
        }

        .login-card h2 {
            font-family: 'Fredoka', sans-serif;
            font-size: 36px;
            margin-bottom: 8px;
        }

        .card-description {
            color: #77788d;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .error-message {
            margin-bottom: 20px;
            padding: 13px 15px;
            color: #a51f3e;
            background: #ffe5ec;
            border: 2px solid #ff9bb2;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.6;
        }

        .field {
            margin-bottom: 19px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 900;
        }

        .field input {
            width: 100%;
            min-height: 49px;
            padding: 12px 14px;
            border: 2px solid #deddea;
            border-radius: 12px;
            outline: none;
            background: #faf9ff;
            color: var(--navy);
            font-family: inherit;
            font-size: 14px;
            transition: .2s;
        }

        .field input:focus {
            border-color: var(--purple);
            background: white;
            box-shadow: 0 0 0 4px rgba(115,87,232,.12);
        }

        .field input::placeholder {
            color: #a3a2b3;
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 70px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
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
            margin-top: 8px;
            border: 3px solid var(--navy);
            border-radius: 13px;
            background: var(--yellow);
            color: var(--navy);
            font-family: inherit;
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 4px 5px 0 var(--navy);
            transition: .2s;
        }

        .submit-button:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 3px 0 var(--navy);
            background: #ffd95f;
        }

        .register-prompt {
            margin-top: 24px;
            text-align: center;
            color: #77788d;
            font-size: 13px;
            font-weight: 800;
        }

        .register-prompt a {
            color: var(--purple);
            font-weight: 900;
        }

        .register-prompt a:hover {
            color: var(--pink);
        }

        .security-note {
            margin-top: 22px;
            padding-top: 17px;
            border-top: 2px dashed #e3e1ed;
            text-align: center;
            color: #85859a;
            font-size: 11px;
            font-weight: 800;
        }

        footer {
            padding: 23px;
            background: var(--navy);
            color: white;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        footer span {
            color: var(--yellow);
        }

        @keyframes appear {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 850px) {
            .login-layout {
                grid-template-columns: 1fr;
                max-width: 520px;
                gap: 45px;
            }

            .welcome-section {
                text-align: center;
            }

            .welcome-section p {
                margin-left: auto;
                margin-right: auto;
            }

            .character-scene {
                margin: 35px auto 0;
            }
        }

        @media (max-width: 500px) {
            .navbar {
                padding: 13px 5%;
            }

            .brand {
                font-size: 24px;
            }

            .login-layout {
                padding: 38px 18px;
            }

            .welcome-section h1 {
                font-size: 55px;
            }

            .character-scene {
                width: 220px;
                height: 180px;
            }

            .character {
                font-size: 105px;
            }

            .speech-bubble {
                right: -25px;
                font-size: 16px;
            }

            .login-card {
                padding: 27px 21px;
            }

            .login-card h2 {
                font-size: 31px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="brand">GHOST <span>HEIST</span> 🎭</a>

    <a href="index.php" class="home-link">← Back to Home</a>
</header>

<main class="login-layout">

    <section class="welcome-section">
        <span class="welcome-tag">🥷 YOUR NEXT ADVENTURE</span>

        <h1>
            WELCOME<br>
            <span>BACK!</span>
        </h1>

        <p>
            Your heist adventure is waiting! Log in, sneak past the guards,
            solve tricky Banana puzzles and get one step closer to the treasure.
        </p>

        <div class="character-scene">
            <span class="floating-star star-one">✨</span>
            <span class="character">🥷</span>
            <span class="speech-bubble">Psst... Ready?</span>
            <span class="floating-star star-two">💰</span>
        </div>
    </section>

    <section class="login-card">
        <div class="card-icon">🔐</div>

        <h2>Player Login</h2>

        <p class="card-description">
            Enter your account details to continue your adventure.
        </p>

        <?php if ($error !== ''): ?>
            <div class="error-message" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <div class="field">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    autocomplete="username"
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="password">Password</label>

                <div class="password-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                        aria-pressed="false"
                    >
                        SHOW
                    </button>
                </div>
            </div>

            <button type="submit" class="submit-button">
                Let's Play! 🚀
            </button>

        </form>

        <p class="register-prompt">
            Don't have an account?
            <a href="register.php">Create one here</a>
        </p>

        <div class="security-note">
            🔒 Your player account is protected.
        </div>
    </section>

</main>

<footer>
    © <?php echo date('Y'); ?> <span>Ghost Heist</span> · Sneak smart. Solve fast. Get rich!
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

</body>
</html>
