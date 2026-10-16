<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash)
                 VALUES (?, ?, ?)'
            );
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $success = 'Account created successfully! You can now log in.';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'This email is already registered.';
            } else {
                error_log($e->getMessage());
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | NutriTrack</title>
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
            max-width: 430px;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 8px 30px #163b1c12;
        }
        h1 { color: #21834a; margin-bottom: 8px; }
        p { color: #68736a; }
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
    <p>Create your account to start tracking nutrition.</p>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
        <a href="login.php">Go to Login</a>
    <?php else: ?>
        <form method="POST">
            <label for="full_name">Full Name</label>
            <input id="full_name" name="full_name"
                   maxlength="100" required>

            <label for="email">Email Address</label>
            <input id="email" type="email" name="email"
                   maxlength="150" required>

            <label for="password">Password (minimum 8 characters)</label>
            <input id="password" type="password" name="password"
                   minlength="8" required>

            <label for="confirm_password">Confirm Password</label>
            <input id="confirm_password" type="password"
                   name="confirm_password" minlength="8" required>

            <button type="submit">Create Account</button>
        </form>
    <?php endif; ?>

    <p>Already registered? <a href="login.php">Log in</a></p>
</div>
</body>
</html>