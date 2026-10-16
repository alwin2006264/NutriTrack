<?php
require_once 'db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

// Get user details and saved calorie goal
$stmt = $pdo->prepare(
    'SELECT full_name, email, daily_calorie_goal
     FROM users
     WHERE id = ?'
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$calorieGoal = (int) $user['daily_calorie_goal'];

// Get today's nutrition totals
$stmt = $pdo->prepare(
    'SELECT
        COALESCE(SUM(calories), 0) AS total_calories,
        COALESCE(SUM(protein), 0) AS total_protein,
        COALESCE(SUM(carbs), 0) AS total_carbs,
        COALESCE(SUM(fats), 0) AS total_fats,
        COUNT(*) AS food_count
     FROM food_logs
     WHERE user_id = ?
       AND consumed_at = ?'
);
$stmt->execute([$userId, $today]);
$totals = $stmt->fetch();

$totalCalories = (float) $totals['total_calories'];
$totalProtein = (float) $totals['total_protein'];
$totalCarbs = (float) $totals['total_carbs'];
$totalFats = (float) $totals['total_fats'];
$foodCount = (int) $totals['food_count'];

// Calculate progress safely
$progress = $calorieGoal > 0
    ? ($totalCalories / $calorieGoal) * 100
    : 0;

$progressWidth = min(100, max(0, $progress));
$remainingCalories = max(0, $calorieGoal - $totalCalories);

// Get today's food entries
$stmt = $pdo->prepare(
    'SELECT food_name, meal_type, quantity_grams,
            calories, protein, carbs, fats, consumed_at
     FROM food_logs
     WHERE user_id = ?
       AND consumed_at = ?
     ORDER BY created_at DESC'
);
$stmt->execute([$userId, $today]);
$foods = $stmt->fetchAll();

// Escape output safely
function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | NutriTrack</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f7f3;
            color: #26352a;
        }

        header {
            background: #ffffff;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 10px #00000008;
        }

        .logo {
            color: #21834a;
            font-size: 25px;
            font-weight: bold;
        }

        .header-right {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
        }

        header a {
            color: #21834a;
            text-decoration: none;
            font-weight: bold;
        }

        main {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        h2 {
            font-size: 21px;
            margin-top: 0;
        }

        .muted {
            color: #718075;
            line-height: 1.5;
        }

        .date {
            color: #718075;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 17px;
            background: #21834a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #176b39;
        }

        .btn.secondary {
            background: #e1eee4;
            color: #21834a;
        }

        .btn.secondary:hover {
            background: #d1e6d6;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 23px;
            border-radius: 15px;
            box-shadow: 0 5px 20px #173b1d08;
            min-width: 0;
        }

        .card-label {
            color: #718075;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .number {
            color: #21834a;
            font-size: 27px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .unit {
            font-size: 14px;
            font-weight: normal;
        }

        .calorie-card {
            margin-bottom: 25px;
        }

        .calorie-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            flex-wrap: wrap;
        }

        .calorie-number {
            font-size: 36px;
            color: #21834a;
            font-weight: bold;
            margin: 10px 0;
        }

        .progress-track {
            width: 100%;
            height: 12px;
            background: #e4eee5;
            border-radius: 20px;
            overflow: hidden;
            margin: 15px 0 10px;
        }

        .progress-fill {
            height: 100%;
            background: #21834a;
            border-radius: 20px;
            transition: width 0.3s;
        }

        .goal-note {
            font-size: 14px;
            color: #718075;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 750px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf1ed;
            text-align: left;
        }

        th {
            color: #718075;
            font-size: 13px;
        }

        td {
            font-size: 14px;
        }

        .meal-badge {
            display: inline-block;
            background: #e8f5eb;
            color: #21834a;
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .empty {
            padding: 20px 0;
            color: #718075;
        }

        .report-link {
            display: inline-block;
            margin-top: 18px;
            color: #21834a;
            text-decoration: none;
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 25px 15px;
            color: #718075;
            font-size: 13px;
        }

        @media (max-width: 850px) {
            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 500px) {
            header {
                padding: 17px 5%;
            }

            .header-right {
                gap: 12px;
                font-size: 14px;
            }

            main {
                margin-top: 25px;
                padding: 0 14px;
            }

            h1 {
                font-size: 25px;
            }

            .grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .card {
                padding: 17px;
            }

            .number {
                font-size: 22px;
            }

            .calorie-number {
                font-size: 31px;
            }

            .actions .btn {
                flex: 1;
                text-align: center;
                font-size: 13px;
                padding: 12px 10px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="logo">🥗 NutriTrack</div>

    <div class="header-right">
        <span>Hi, <?= h($user['full_name']) ?></span>
        <a href="index.php">Home</a>
        <a href="logout.php">Log Out</a>
    </div>
</header>

<main>

    <!-- Welcome section -->
    <section class="welcome">
        <div>
            <h1>Your Nutrition Dashboard</h1>
            <p class="muted">
                Track your meals and monitor your daily nutrition.
            </p>
        </div>

        <div class="date">
            <?= date('l, d F Y') ?>
        </div>
    </section>

    <!-- Navigation buttons -->
    <div class="actions">
        <a class="btn" href="add_food.php">+ Add Food</a>

        <a class="btn secondary" href="set_goal.php">
            🎯 Set Calorie Goal
        </a>

        <a class="btn secondary" href="history.php">
            📋 Food History
        </a>

        <a class="btn secondary" href="reports.php">
            📊 Calorie Reports
        </a>
    </div>

    <!-- Calorie goal card -->
    <section class="card calorie-card">

        <div class="calorie-top">
            <div>
                <div class="card-label">Today's Calorie Intake</div>

                <div class="calorie-number">
                    <?= number_format($totalCalories, 1) ?>
                    <span class="unit">kcal</span>
                </div>

                <div class="muted">
                    of <?= number_format($calorieGoal) ?> kcal daily goal
                </div>
            </div>

            <div>
                <div class="card-label">Daily progress</div>
                <div class="number">
                    <?= number_format($progress, 1) ?>%
                </div>
            </div>
        </div>

        <div class="progress-track">
            <div
                class="progress-fill"
                style="width: <?= number_format($progressWidth, 2, '.', '') ?>%;"
            ></div>
        </div>

        <p class="goal-note">
            <?php if ($totalCalories < $calorieGoal): ?>
                <?= number_format($remainingCalories, 1) ?>
                kcal remaining to reach your saved goal.
            <?php elseif ($totalCalories == $calorieGoal): ?>
                You have reached your daily calorie goal.
            <?php else: ?>
                You are <?= number_format($totalCalories - $calorieGoal, 1) ?>
                kcal above your daily calorie goal.
            <?php endif; ?>
        </p>

        <a class="report-link" href="set_goal.php">
            Change my daily goal →
        </a>

    </section>

    <!-- Nutrition summary cards -->
    <section class="grid">

        <div class="card">
            <div class="card-label">🔥 Calories</div>
            <div class="number">
                <?= number_format($totalCalories, 1) ?>
            </div>
            <p class="muted">kcal consumed today</p>
        </div>

        <div class="card">
            <div class="card-label">🥚 Protein</div>
            <div class="number">
                <?= number_format($totalProtein, 1) ?>
                <span class="unit">g</span>
            </div>
            <p class="muted">today's total</p>
        </div>

        <div class="card">
            <div class="card-label">🍚 Carbohydrates</div>
            <div class="number">
                <?= number_format($totalCarbs, 1) ?>
                <span class="unit">g</span>
            </div>
            <p class="muted">today's total</p>
        </div>

        <div class="card">
            <div class="card-label">🥑 Fats</div>
            <div class="number">
                <?= number_format($totalFats, 1) ?>
                <span class="unit">g</span>
            </div>
            <p class="muted">today's total</p>
        </div>

    </section>

    <!-- Today's food list -->
    <section class="card">

        <h2>Today's Food Entries</h2>

        <p class="muted">
            You have recorded <?= $foodCount ?>
            food <?= $foodCount === 1 ? 'entry' : 'entries' ?> today.
        </p>

        <?php if (empty($foods)): ?>

            <p class="empty">
                No food recorded for today yet. Add your first meal
                to see your nutrition summary here.
            </p>

            <a class="btn" href="add_food.php">Add Your First Food</a>

        <?php else: ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Food</th>
                            <th>Meal</th>
                            <th>Quantity</th>
                            <th>Calories</th>
                            <th>Protein</th>
                            <th>Carbs</th>
                            <th>Fats</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($foods as $food): ?>
                            <tr>
                                <td><?= h($food['food_name']) ?></td>

                                <td>
                                    <span class="meal-badge">
                                        <?= h($food['meal_type']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= number_format((float) $food['quantity_grams'], 0) ?> g
                                </td>

                                <td>
                                    <?= number_format((float) $food['calories'], 1) ?> kcal
                                </td>

                                <td>
                                    <?= number_format((float) $food['protein'], 1) ?> g
                                </td>

                                <td>
                                    <?= number_format((float) $food['carbs'], 1) ?> g
                                </td>

                                <td>
                                    <?= number_format((float) $food['fats'], 1) ?> g
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

        <a class="report-link" href="history.php">
            View all food history →
        </a>

    </section>

</main>

<footer>
    © <?= date('Y') ?> NutriTrack — Smart Diet and Nutrition Tracking System
</footer>

</body>
</html>