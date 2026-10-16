<?php
require_once 'db.php';

$error = '';

if (isset($_GET['registered'])) {
    $message = 'Registration successful! Please log in.';
} else {
    $message = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, password_hash
             FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NutriTrack</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f0f7f1;
            display: grid;
            place-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 8px 30px #163b1c12;
        }
        h1 { color: #21834a; margin-bottom: 8px; }
        p { color: #68736a; line-height: 1.5; }
        label { display: block; margin: 16px 0 7px; }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d7e2d8;
            border-radius: 8px;
        }
        button {
            width: 100%;
            padding: 13px;
            margin-top: 22px;
            border: 0;
            border-radius: 8px;
            background: #21834a;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        .error { color: #b42318; }
        .success { color: #21834a; }
        a { color: #21834a; }
    </style>
</head>
<body>
<div class="card">
    <h1>🥗 NutriTrack</h1>
    <p>Welcome back! Log in to track your nutrition.</p>

    <?php if ($message): ?>
        <p class="success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Email Address</label>
        <input id="email" type="email" name="email"
               autocomplete="email" required>

        <label for="password">Password</label>
        <input id="password" type="password" name="password"
               autocomplete="current-password" required>

        <button type="submit">Log In</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>