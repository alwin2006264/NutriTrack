<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT food_name, meal_type, quantity_grams,
            calories, protein, carbs, fats, consumed_at
     FROM food_logs
     WHERE user_id = ?
     ORDER BY consumed_at DESC, created_at DESC'
);
$stmt->execute([$userId]);
$foods = $stmt->fetchAll();

$totalCalories = 0;
$totalProtein = 0;
$totalCarbs = 0;
$totalFats = 0;

foreach ($foods as $food) {
    $totalCalories += (float) $food['calories'];
    $totalProtein += (float) $food['protein'];
    $totalCarbs += (float) $food['carbs'];
    $totalFats += (float) $food['fats'];
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Food History | NutriTrack</title>
<style>
* { box-sizing: border-box; }
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
    gap: 12px;
    box-shadow: 0 2px 10px #00000008;
}
.logo { color: #21834a; font-size: 24px; font-weight: bold; }
header a { color: #21834a; text-decoration: none; font-weight: bold; }
main { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
.muted { color: #718075; }
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin: 24px 0;
}
.card {
    background: white;
    padding: 22px;
    border-radius: 15px;
    box-shadow: 0 5px 20px #173b1d08;
}
.card h3 { color: #718075; font-size: 14px; margin-top: 0; }
.number { color: #21834a; font-size: 26px; font-weight: bold; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 700px; }
th, td { padding: 13px 10px; text-align: left; border-bottom: 1px solid #edf1ed; }
th { color: #718075; font-size: 13px; }
.btn {
    display: inline-block;
    margin: 10px 8px 10px 0;
    padding: 12px 16px;
    background: #21834a;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
}
.btn.secondary { background: #e5f1e7; color: #21834a; }
.empty { padding: 20px 0; color: #718075; }
</style>
</head>
<body>
<header>
    <div class="logo">🥗 NutriTrack</div>
    <a href="logout.php">Log Out</a>
</header>

<main>
    <h1>Food History</h1>
    <p class="muted">
        Review your recorded meals and nutrition information.
    </p>

    <a class="btn" href="add_food.php">+ Add Food</a>
    <a class="btn secondary" href="dashboard.php">Dashboard</a>

    <div class="grid">
        <section class="card">
            <h3>Total Food Entries</h3>
            <div class="number"><?= count($foods) ?></div>
        </section>
        <section class="card">
            <h3>Total Calories Recorded</h3>
            <div class="number"><?= number_format($totalCalories) ?></div>
            <p class="muted">kcal across all recorded dates</p>
        </section>
        <section class="card">
            <h3>Protein</h3>
            <div class="number"><?= number_format($totalProtein, 1) ?> g</div>
        </section>
        <section class="card">
            <h3>Carbohydrates</h3>
            <div class="number"><?= number_format($totalCarbs, 1) ?> g</div>
        </section>
    </div>

    <section class="card">
        <h2>All Recorded Meals</h2>

        <?php if (!$foods): ?>
            <p class="empty">No food history yet. Add your first meal to get started.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
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
                            <td><?= h($food['consumed_at']) ?></td>
                            <td><?= h($food['food_name']) ?></td>
                            <td><?= h($food['meal_type']) ?></td>
                            <td><?= number_format((float)$food['quantity_grams'], 0) ?> g</td>
                            <td><?= number_format((float)$food['calories'], 1) ?></td>
                            <td><?= number_format((float)$food['protein'], 1) ?> g</td>
                            <td><?= number_format((float)$food['carbs'], 1) ?> g</td>
                            <td><?= number_format((float)$food['fats'], 1) ?> g</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>