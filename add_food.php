<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$error = '';

/*
 * Approximate nutrition values per 100 grams.
 * Values are illustrative and may vary by brand and preparation.
 */
$foodDatabase = [
    'Rice (cooked)'       => ['calories' => 130, 'protein' => 2.7,  'carbs' => 28.2, 'fats' => 0.3],
    'Chapati'             => ['calories' => 297, 'protein' => 9.0,  'carbs' => 50.0, 'fats' => 7.5],
    'Boiled egg'          => ['calories' => 155, 'protein' => 12.6, 'carbs' => 1.1,  'fats' => 10.6],
    'Banana'              => ['calories' => 89,  'protein' => 1.1,  'carbs' => 22.8, 'fats' => 0.3],
    'Apple'               => ['calories' => 52,  'protein' => 0.3,  'carbs' => 13.8, 'fats' => 0.2],
    'Chicken breast'      => ['calories' => 165, 'protein' => 31.0, 'carbs' => 0.0,  'fats' => 3.6],
    'Paneer'              => ['calories' => 265, 'protein' => 18.3, 'carbs' => 1.2,  'fats' => 20.8],
    'Dal (cooked)'        => ['calories' => 116, 'protein' => 9.0,  'carbs' => 20.1, 'fats' => 0.4],
    'Milk (whole)'        => ['calories' => 61,  'protein' => 3.2,  'carbs' => 4.8,  'fats' => 3.3],
    'Oats (dry)'          => ['calories' => 389, 'protein' => 16.9, 'carbs' => 66.3, 'fats' => 6.9],
    'Almonds'             => ['calories' => 579, 'protein' => 21.2, 'carbs' => 21.6, 'fats' => 49.9],
    'Potato (boiled)'     => ['calories' => 87,  'protein' => 1.9,  'carbs' => 20.1, 'fats' => 0.1],
    'Orange'              => ['calories' => 47,  'protein' => 0.9,  'carbs' => 11.8, 'fats' => 0.1],
    'Yogurt (plain)'      => ['calories' => 61,  'protein' => 3.5,  'carbs' => 4.7,  'fats' => 3.3],
    'Peanuts'             => ['calories' => 567, 'protein' => 25.8, 'carbs' => 16.1, 'fats' => 49.2]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foodName = $_POST['food_name'] ?? '';
    $mealType = $_POST['meal_type'] ?? '';
    $quantity = filter_var(
        $_POST['quantity_grams'] ?? null,
        FILTER_VALIDATE_FLOAT
    );
    $consumedAt = $_POST['consumed_at'] ?? '';

    $validMeals = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];
    $date = DateTime::createFromFormat('!Y-m-d', $consumedAt);
    $validDate = $date && $date->format('Y-m-d') === $consumedAt;

    if (!isset($foodDatabase[$foodName])) {
        $error = 'Please select a valid food.';
    } elseif (!in_array($mealType, $validMeals, true)) {
        $error = 'Please select a valid meal type.';
    } elseif ($quantity === false || $quantity <= 0 || $quantity > 5000) {
        $error = 'Quantity must be between 0 and 5000 grams.';
    } elseif (!$validDate) {
        $error = 'Please select a valid date.';
    } else {
        $nutrition = $foodDatabase[$foodName];
        $factor = $quantity / 100;

        $calories = round($nutrition['calories'] * $factor, 2);
        $protein  = round($nutrition['protein'] * $factor, 2);
        $carbs    = round($nutrition['carbs'] * $factor, 2);
        $fats     = round($nutrition['fats'] * $factor, 2);

        $stmt = $pdo->prepare(
            'INSERT INTO food_logs
             (user_id, food_name, meal_type, quantity_grams,
              calories, protein, carbs, fats, consumed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $userId, $foodName, $mealType, $quantity,
            $calories, $protein, $carbs, $fats, $consumedAt
        ]);

        header('Location: dashboard.php?food_added=1');
        exit;
    }
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
<title>Add Food | NutriTrack</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    padding: 24px;
    font-family: Arial, sans-serif;
    background: #f0f7f1;
    color: #26352a;
}
.card {
    max-width: 600px;
    margin: 25px auto;
    background: white;
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 8px 30px #163b1c10;
}
h1 { color: #21834a; }
label { display: block; margin: 18px 0 7px; font-weight: bold; }
input, select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d7e2d8;
    border-radius: 8px;
    background: white;
    font-size: 15px;
}
button, .back {
    display: inline-block;
    border: 0;
    border-radius: 8px;
    padding: 13px 18px;
    margin-top: 22px;
    background: #21834a;
    color: white;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
}
.back { background: #e9f3eb; color: #21834a; margin-left: 8px; }
.error { color: #b42318; }
.hint { color: #718075; font-size: 13px; line-height: 1.5; }
</style>
</head>
<body>
<div class="card">
    <h1>🥗 Add Food</h1>
    <p>Record a meal and calculate its estimated nutrition.</p>

    <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="food_name">Choose Food</label>
        <select id="food_name" name="food_name" required>
            <option value="">-- Select a food --</option>
            <?php foreach ($foodDatabase as $name => $nutrition): ?>
                <option value="<?= h($name) ?>"
                    <?= (($_POST['food_name'] ?? '') === $name) ? 'selected' : '' ?>>
                    <?= h($name) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="meal_type">Meal Type</label>
        <select id="meal_type" name="meal_type" required>
            <?php foreach (['Breakfast', 'Lunch', 'Dinner', 'Snack'] as $meal): ?>
                <option value="<?= h($meal) ?>"
                    <?= (($_POST['meal_type'] ?? '') === $meal) ? 'selected' : '' ?>>
                    <?= h($meal) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="quantity_grams">Quantity (grams)</label>
        <input id="quantity_grams" type="number"
               name="quantity_grams" min="1" max="5000"
               step="1" value="<?= h($_POST['quantity_grams'] ?? '100') ?>"
               required>
        <p class="hint">Nutrition is calculated proportionally from the selected food's values per 100 grams.</p>

        <label for="consumed_at">Date Consumed</label>
        <input id="consumed_at" type="date" name="consumed_at"
               value="<?= h($_POST['consumed_at'] ?? date('Y-m-d')) ?>"
               required>

        <button type="submit">Save Food Entry</button>
        <a class="back" href="dashboard.php">Back to Dashboard</a>
    </form>
</div>
</body>
</html>