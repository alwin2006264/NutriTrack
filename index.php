<?php
require_once 'db.php';

// Redirect logged-in users to the dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NutriTrack | Smart Diet and Nutrition Tracking</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f7f3;
            color: #26352a;
            line-height: 1.6;
        }

        header {
            background: #ffffff;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 10px #00000008;
        }

        .logo {
            color: #21834a;
            font-size: 26px;
            font-weight: bold;
        }

        nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        nav a {
            text-decoration: none;
            color: #26352a;
            font-weight: bold;
        }

        nav a:hover {
            color: #21834a;
        }

        nav .signup {
            background: #21834a;
            color: white;
            padding: 11px 18px;
            border-radius: 8px;
        }

        nav .signup:hover {
            background: #176b39;
            color: white;
        }

        .hero {
            max-width: 1200px;
            margin: auto;
            padding: 80px 25px 70px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 50px;
            align-items: center;
        }

        .tag {
            display: inline-block;
            color: #21834a;
            background: #e2f1e5;
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: clamp(38px, 5vw, 62px);
            line-height: 1.13;
            margin-bottom: 22px;
            color: #1e3023;
        }

        .hero-description {
            color: #718075;
            font-size: 18px;
            margin-bottom: 30px;
            max-width: 550px;
        }

        .btn {
            display: inline-block;
            padding: 14px 22px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin: 0 8px 10px 0;
            transition: 0.2s;
        }

        .primary {
            background: #21834a;
            color: white;
        }

        .primary:hover {
            background: #176b39;
            transform: translateY(-2px);
        }

        .secondary {
            background: #e1eee4;
            color: #21834a;
        }

        .secondary:hover {
            background: #d1e6d6;
        }

        .preview {
            background: white;
            padding: 30px;
            border-radius: 22px;
            box-shadow: 0 12px 35px #173b1d10;
        }

        .preview h2 {
            margin-bottom: 5px;
        }

        .muted {
            color: #718075;
            font-size: 14px;
        }

        .calories {
            background: #e8f5eb;
            padding: 23px;
            border-radius: 15px;
            margin: 23px 0;
        }

        .calories-label {
            color: #526857;
            font-size: 14px;
        }

        .calories strong {
            color: #21834a;
            font-size: 34px;
            display: block;
            margin: 5px 0;
        }

        .progress {
            background: #d1e5d5;
            height: 8px;
            border-radius: 10px;
            margin-top: 15px;
            overflow: hidden;
        }

        .progress-fill {
            width: 62.5%;
            height: 100%;
            background: #21834a;
            border-radius: 10px;
        }

        .preview h3 {
            margin-bottom: 8px;
        }

        .meal {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 0;
            border-bottom: 1px solid #edf1ed;
        }

        .meal:last-of-type {
            border-bottom: none;
        }

        .meal strong {
            color: #21834a;
        }

        .features {
            padding: 65px 7% 80px;
            background: white;
            text-align: center;
        }

        .section-label {
            color: #21834a;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .features h2 {
            font-size: clamp(27px, 4vw, 36px);
            margin-bottom: 12px;
        }

        .section-description {
            color: #718075;
            max-width: 650px;
            margin: auto;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            max-width: 1100px;
            margin: 40px auto 0;
            text-align: left;
        }

        .feature {
            background: #f7faf7;
            border: 1px solid #e9f0e9;
            padding: 27px;
            border-radius: 16px;
            transition: 0.2s;
        }

        .feature:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px #173b1d0c;
        }

        .emoji {
            font-size: 34px;
            margin-bottom: 12px;
        }

        .feature h3 {
            margin-bottom: 9px;
            font-size: 20px;
        }

        .feature p {
            color: #718075;
            font-size: 15px;
        }

        .cta {
            background: #21834a;
            color: white;
            text-align: center;
            padding: 55px 20px;
        }

        .cta h2 {
            font-size: clamp(27px, 4vw, 36px);
            margin-bottom: 12px;
        }

        .cta p {
            margin-bottom: 24px;
            color: #e4f3e8;
        }

        .cta .btn {
            background: white;
            color: #21834a;
        }

        .cta .btn:hover {
            background: #eaf5ec;
        }

        footer {
            background: #ffffff;
            text-align: center;
            padding: 25px 15px;
            color: #718075;
            font-size: 14px;
        }

        @media (max-width: 800px) {
            .hero {
                grid-template-columns: 1fr;
                padding-top: 50px;
                gap: 35px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            header {
                padding: 18px 5%;
            }
        }

        @media (max-width: 480px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            nav {
                gap: 13px;
            }

            nav a {
                font-size: 14px;
            }

            .hero {
                padding: 40px 18px;
            }

            .preview {
                padding: 22px;
            }

            .features {
                padding: 45px 18px;
            }
        }
    </style>
</head>

<body>

<!-- Navigation -->
<header>
    <div class="logo">🥗 NutriTrack</div>

    <nav>
        <a href="#home">Home</a>
        <a href="#features">Features</a>
        <a href="login.php">Login</a>
        <a class="signup" href="register.php">Get Started</a>
    </nav>
</header>

<!-- Hero Section -->
<section class="hero" id="home">

    <div>
        <div class="tag">YOUR PERSONAL NUTRITION COMPANION</div>

        <h1>
            Eat better.<br>
            Feel better.<br>
            Live healthier.
        </h1>

        <p class="hero-description">
            Take control of your eating habits with NutriTrack.
            Track daily calories, monitor nutrients, and keep
            your food history organized in one simple place.
        </p>

        <a class="btn primary" href="register.php">
            Start Tracking Free →
        </a>

        <a class="btn secondary" href="login.php">
            Login to Your Account
        </a>
    </div>

    <!-- Sample dashboard preview -->
    <div class="preview">

        <h2>Today's Nutrition</h2>
        <p class="muted">A preview of your nutrition dashboard</p>

        <div class="calories">
            <div class="calories-label">Daily calorie goal</div>

            <strong>1,250 kcal</strong>

            <div class="muted">of 2,000 kcal · Example data</div>

            <div class="progress">
                <div class="progress-fill"></div>
            </div>
        </div>

        <h3>Nutrition at a glance</h3>

        <div class="meal">
            <span>🥚 Protein</span>
            <strong>45 g</strong>
        </div>

        <div class="meal">
            <span>🍚 Carbohydrates</span>
            <strong>150 g</strong>
        </div>

        <div class="meal">
            <span>🥑 Fats</span>
            <strong>35 g</strong>
        </div>

        <p class="muted" style="margin-top: 15px;">
            These are illustrative values, not actual user data.
        </p>

    </div>

</section>

<!-- Features Section -->
<section class="features" id="features">

    <div class="section-label">OUR FEATURES</div>

    <h2>Everything you need to eat smarter</h2>

    <p class="section-description">
        Understand your daily food habits and keep track
        of the nutrients in your meals with easy-to-use tools.
    </p>

    <div class="feature-grid">

        <div class="feature">
            <div class="emoji">🔥</div>

            <h3>Calorie Tracking</h3>

            <p>
                Record your meals and monitor your calorie intake
                against your daily calorie goal.
            </p>
        </div>

        <div class="feature">
            <div class="emoji">🥗</div>

            <h3>Nutrition Monitoring</h3>

            <p>
                View estimated protein, carbohydrates, and fats
                for the foods you record.
            </p>
        </div>

        <div class="feature">
            <div class="emoji">📊</div>

            <h3>Food History</h3>

            <p>
                Review your previous food entries and see
                nutrition totals across recorded meals.
            </p>
        </div>

        <div class="feature">
            <div class="emoji">👤</div>

            <h3>Personal Account</h3>

            <p>
                Register and log in to keep your food entries
                associated with your own account.
            </p>
        </div>

        <div class="feature">
            <div class="emoji">🍎</div>

            <h3>Multiple Food Options</h3>

            <p>
                Select from the available foods and enter
                the quantity you consumed.
            </p>
        </div>

        <div class="feature">
            <div class="emoji">📅</div>

            <h3>Daily Meal Records</h3>

            <p>
                Organize food entries by date and meal type
                to make reviewing your diet easier.
            </p>
        </div>

    </div>

</section>

<!-- Call to Action -->
<section class="cta">

    <h2>Start your nutrition journey today</h2>

    <p>
        Create your account and begin recording your meals with NutriTrack.
    </p>

    <a class="btn" href="register.php">
        Create Your Free Account →
    </a>

</section>

<!-- Footer -->
<footer>
    <p>
        © <?= date('Y') ?> NutriTrack.
        Smart Diet and Nutrition Tracking System.
    </p>
</footer>

</body>
</html>