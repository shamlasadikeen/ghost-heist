
<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}

$error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Ghost Heist</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .auth-card {
            width: 100%;
            max-width: 460px;
            padding: 40px;
            background: #171321;
            border: 1px solid #393047;
            border-radius: 20px;
            box-shadow: 0 20px 60px #0006;
        }

        .auth-card h1 {
            margin: 12px 0;
            font-size: 2.2rem;
        }

        .auth-card p {
            color: #b8b1c6;
            line-height: 1.6;
        }

        .auth-form {
            margin-top: 28px;
        }

        .auth-form label {
            display: block;
            margin: 18px 0 8px;
            color: #eee8f7;
            font-weight: 600;
        }

        .auth-form input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #494057;
            border-radius: 9px;
            background: #100d18;
            color: #fff;
            font: inherit;
            box-sizing: border-box;
        }

        .auth-form input:focus {
            outline: 2px solid #a78bfa;
            border-color: transparent;
        }

        .auth-form button {
            width: 100%;
            margin-top: 26px;
            padding: 14px;
            border: 0;
            border-radius: 9px;
            background: #a78bfa;
            color: #171321;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }

        .auth-form button:hover {
            background: #c4b5fd;
        }

        .auth-error {
            padding: 12px;
            border-radius: 8px;
            background: #49212b;
            color: #ffc6cf;
        }

        .auth-footer {
            margin-top: 24px;
            text-align: center;
        }

        .auth-footer a,
        .back-link {
            color: #c4b5fd;
            text-decoration: none;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 26px 20px;
            }
        }
    </style>
</head>
<body>
    <main class="auth-page">
        <section class="auth-card">
            <a class="back-link" href="index.php">&larr; Back to home</a>

            <div aria-hidden="true">👻</div>
            <h1>Join the Heist</h1>
            <p>Create your player account and prepare to explore the haunted mansion.</p>

            <?php if ($error !== ''): ?>
                <p class="auth-error" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form class="auth-form" action="register.php" method="POST">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    minlength="3"
                    maxlength="50"
                    autocomplete="username"
                    required
                >

                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="100"
                    autocomplete="email"
                    required
                >

                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <button type="submit">Create My Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </section>
    </main>
</body>
</html>
