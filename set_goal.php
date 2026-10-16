<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal = filter_input(INPUT_POST, 'daily_calorie_goal', FILTER_VALIDATE_INT);

    if ($goal === false || $goal === null || $goal < 500 || $goal > 10000) {
        $message = 'Please enter a whole number between 500 and 10000.';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET daily_calorie_goal = ?
             WHERE id = ?'
        );

        $stmt->execute([$goal, $userId]);

        $message = 'Your daily calorie goal has been saved successfully!';
        $messageType = 'success';
    }
}

$stmt = $pdo->prepare(
    'SELECT full_name, daily_calorie_goal
     FROM users
     WHERE id = ?'
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$currentGoal = (int) $user['daily_calorie_goal'];

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Calorie Goal | NutriTrack</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f7f3;
            color: #26352a;
        }

        header {
            background: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px #00000008;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #21834a;
        }

        header a {
            color: #21834a;
            text-decoration: none;
            font-weight: bold;
        }

        main {
            max-width: 600px;
            margin: 55px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 8px 30px #173b1d0a;
        }

        h1 {
            margin-top: 0;
            font-size: 29px;
        }

        .muted {
            color: #718075;
            line-height: 1.6;
        }

        label {
            display: block;
            margin: 24px 0 9px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #dce5dc;
            border-radius: 9px;
            font-size: 17px;
        }

        input:focus {
            outline: 2px solid #21834a;
            border-color: transparent;
        }

        button {
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            background: #21834a;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #176b39;
        }

        .message {
            padding: 13px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .success {
            background: #e2f5e7;
            color: #176b39;
        }

        .error {
            background: #fde9e7;
            color: #a52b24;
        }

        .links {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .links a {
            color: #21834a;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

<header>
    <div class="logo">🥗 NutriTrack</div>
    <a href="logout.php">Log Out</a>
</header>

<main>
    <section class="card">

        <h1>Set Your Daily Calorie Goal</h1>

        <p class="muted">
            Hello <?= h($user['full_name']) ?>! Choose a daily
            calorie target that suits your individual needs.
        </p>

        <?php if ($message !== ''): ?>
            <div class="message <?= h($messageType) ?>">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="set_goal.php">

            <label for="daily_calorie_goal">
                Daily calorie goal (kcal)
            </label>

            <input
                type="number"
                id="daily_calorie_goal"
                name="daily_calorie_goal"
                min="500"
                max="10000"
                step="1"
                value="<?= h($currentGoal) ?>"
                required
            >

            <p class="muted">
                Enter a whole number between 500 and 10,000 kcal.
                Your saved goal will be used on your dashboard.
            </p>

            <button type="submit">Save My Calorie Goal</button>

        </form>

        <div class="links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="history.php">View Food History</a>
        </div>

    </section>
</main>

</body>
</html>