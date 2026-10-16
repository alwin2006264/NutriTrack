<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$today = date('Y-m-d');

$report_date = $_GET['report_date'] ?? $today;
$date_object = DateTime::createFromFormat('!Y-m-d', $report_date);

if (
    !$date_object ||
    $date_object->format('Y-m-d') !== $report_date ||
    $report_date > $today
) {
    $report_date = $today;
}

$date_display = date('d M Y', strtotime($report_date));

/* Get the user's calorie goal */
$stmt = $pdo->prepare(
    'SELECT full_name, daily_calorie_goal
     FROM users
     WHERE id = ?'
);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$goal = (int) ($user['daily_calorie_goal'] ?? 2000);

/* Calculate nutrition totals for the selected date */
$stmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_entries,
        COALESCE(SUM(calories), 0) AS total_calories,
        COALESCE(SUM(protein), 0) AS total_protein,
        COALESCE(SUM(carbs), 0) AS total_carbs,
        COALESCE(SUM(fats), 0) AS total_fats
     FROM food_logs
     WHERE user_id = ? AND consumed_at = ?'
);
$stmt->execute([$user_id, $report_date]);
$summary = $stmt->fetch();

$calories = (float) $summary['total_calories'];
$protein = (float) $summary['total_protein'];
$carbs = (float) $summary['total_carbs'];
$fats = (float) $summary['total_fats'];
$total_entries = (int) $summary['total_entries'];

$percentage = $goal > 0
    ? min(100, round(($calories / $goal) * 100))
    : 0;

$remaining = $goal - $calories;

/* Get foods eaten on the selected date */
$stmt = $pdo->prepare(
    'SELECT food_name, meal_type, quantity_grams,
            calories, protein, carbs, fats
     FROM food_logs
     WHERE user_id = ? AND consumed_at = ?
     ORDER BY created_at DESC'
);
$stmt->execute([$user_id, $report_date]);
$foods = $stmt->fetchAll();

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calorie Reports | NutriTrack</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f7f4;
            color: #26352b;
        }

        header {
            background: #176b45;
            color: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header h2 {
            margin: 0;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 14px;
        }

        main {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .panel {
            background: white;
            padding: 24px;
            margin-bottom: 22px;
            border-radius: 14px;
            box-shadow: 0 3px 12px #0000000b;
        }

        .date-form {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="date"] {
            padding: 11px;
            border: 1px solid #ccd6cf;
            border-radius: 7px;
            font-size: 15px;
        }

        button {
            background: #176b45;
            color: white;
            padding: 12px 20px;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-size: 15px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 16px;
        }

        .stat {
            background: #f8fbf8;
            border: 1px solid #e2ebe4;
            padding: 20px;
            border-radius: 12px;
        }

        .stat p {
            margin: 0 0 10px;
            color: #627067;
        }

        .stat h2 {
            margin: 0;
            color: #176b45;
            font-size: 26px;
        }

        .progress {
            height: 13px;
            background: #e3e9e4;
            border-radius: 20px;
            overflow: hidden;
            margin: 15px 0 8px;
        }

        .progress div {
            height: 100%;
            background: #20a464;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th, td {
            padding: 13px 10px;
            border-bottom: 1px solid #e7ece8;
            text-align: left;
        }

        th {
            background: #f0f6f1;
        }

        .muted {
            color: #65736a;
        }

        .empty {
            padding: 20px;
            background: #f8fbf8;
            border-radius: 8px;
        }

        footer {
            text-align: center;
            color: #65736a;
            padding: 20px;
        }

        @media (max-width: 600px) {
            header {
                align-items: flex-start;
            }

            header a {
                display: inline-block;
                margin: 5px 10px 5px 0;
            }

            .panel {
                padding: 17px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>🥗 NutriTrack</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="add_food.php">Add Food</a>
        <a href="history.php">History</a>
        <a href="set_goal.php">Set Goal</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h1>Calorie Reports</h1>
    <p class="muted">
        Hello, <?= h($user['full_name'] ?? 'User') ?>!
        Review your nutrition for any previous date.
    </p>

    <section class="panel">
        <h2>Select Report Date</h2>

        <form method="GET" action="reports.php" class="date-form">
            <div>
                <label for="report_date">Choose a date</label>
                <input
                    type="date"
                    id="report_date"
                    name="report_date"
                    value="<?= h($report_date) ?>"
                    max="<?= h($today) ?>"
                    required
                >
            </div>

            <button type="submit">View Report</button>
        </form>
    </section>

    <section class="panel">
        <h2>Summary for <?= h($date_display) ?></h2>

        <div class="cards">
            <div class="stat">
                <p>Calories Consumed</p>
                <h2><?= number_format($calories, 0) ?> kcal</h2>
            </div>

            <div class="stat">
                <p>Daily Calorie Goal</p>
                <h2><?= number_format($goal) ?> kcal</h2>
            </div>

            <div class="stat">
                <p>Protein</p>
                <h2><?= number_format($protein, 1) ?> g</h2>
            </div>

            <div class="stat">
                <p>Carbohydrates</p>
                <h2><?= number_format($carbs, 1) ?> g</h2>
            </div>

            <div class="stat">
                <p>Fats</p>
                <h2><?= number_format($fats, 1) ?> g</h2>
            </div>

            <div class="stat">
                <p>Food Entries</p>
                <h2><?= $total_entries ?></h2>
            </div>
        </div>

        <h3>Daily Calorie Goal Progress</h3>

        <div class="progress">
            <div style="width: <?= $percentage ?>%;"></div>
        </div>

        <p>
            <?= $percentage ?>% of your daily goal
        </p>

        <?php if ($calories == 0): ?>
            <p class="muted">
                No food has been logged for this date.
            </p>
        <?php elseif ($remaining > 0): ?>
            <p>
                You consumed <?= number_format($remaining, 0) ?>
                kcal below your goal.
            </p>
        <?php elseif ($remaining == 0): ?>
            <p>You reached your daily calorie goal exactly.</p>
        <?php else: ?>
            <p>
                You exceeded your daily goal by
                <?= number_format(abs($remaining), 0) ?> kcal.
            </p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Foods Logged on <?= h($date_display) ?></h2>

        <?php if (empty($foods)): ?>
            <div class="empty">
                No food entries were found for this date.
                <a href="add_food.php">Add food</a> to start tracking.
            </div>
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
                            <td><?= h($food['meal_type']) ?></td>
                            <td>
                                <?= number_format((float) $food['quantity_grams'], 0) ?> g
                            </td>
                            <td>
                                <?= number_format((float) $food['calories'], 0) ?> kcal
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
    </section>

    <p>
        <a href="dashboard.php">← Back to Dashboard</a>
    </p>
</main>

<footer>
    NutriTrack &copy; <?= date('Y') ?> |
    Nutrition values are estimates and may vary by food and preparation.
</footer>

</body>
</html>