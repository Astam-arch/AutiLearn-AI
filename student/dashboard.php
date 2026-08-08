<?php
// student/dashboard.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// =====================================================
// 1. SESSION & ROLE GUARD
// =====================================================
if (!isset($_SESSION['user_id'])) {
    $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: {$loginUrl}");
    exit;
}

// Redirect non-student roles to their appropriate dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'student') {
    $role = $_SESSION['role'];
    $dashboardUrl = defined('BASE_URL')
        ? BASE_URL . "{$role}/dashboard.php"
        : "../{$role}/dashboard.php";

    header("Location: {$dashboardUrl}");
    exit;
}

$studentName = $_SESSION['full_name'] ?? 'Learner';
$studentId   = $_SESSION['user_id'];

// =====================================================
// 2. FETCH OR DEFAULT GAMIFICATION STATS
// =====================================================
$starsEarned = 24;
$streakDays = 5;
$completedActivities = 3;
$totalActivities = 6;

try {
    $stmt = $pdo->prepare(
        "SELECT stars_earned, streak_days FROM users WHERE id = ?"
    );

    $stmt->execute([$studentId]);
    $userStats = $stmt->fetch();

    if ($userStats) {
        $starsEarned = $userStats['stars_earned'] ?? $starsEarned;
        $streakDays = $userStats['streak_days'] ?? $streakDays;
    }
} catch (PDOException $e) {
    // Soft fallback to defaults
}

$progressPercentage = round(
    ($completedActivities / $totalActivities) * 100
);

$logoutUrl = defined('BASE_URL')
    ? BASE_URL . 'logout.php'
    : '../logout.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Student Dashboard |
        <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?>
    </title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet"
    >

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {
            --bg-calm: #f0fdfa;
            --card-radius: 24px;
            --primary-blue: #2563eb;
            --soft-purple: #8b5cf6;
            --gentle-teal: #0d9488;
            --warm-amber: #f59e0b;
            --transition-smooth:
                all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-calm);
            font-family: 'Poppins', sans-serif;
            color: #334155;
            padding-bottom: 60px;
        }

        h1,
        h2,
        h3,
        h4,
        .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }

        /* =========================================
           NAVBAR
        ========================================= */

        .navbar-student {
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            padding: 15px 0;
        }

        /* =========================================
           STAT BADGES
        ========================================= */

        .stat-badge {
            background: #ffffff;
            border-radius: 20px;
            padding: 12px 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);

            display: inline-flex;
            align-items: center;
            gap: 12px;

            font-weight: 600;
        }

        /* =========================================
           ACTIVITY CARDS
        ========================================= */

        .activity-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 28px;

            border: 2px solid transparent;

            transition: var(--transition-smooth);

            height: 100%;

            text-decoration: none;
            color: inherit;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.03);
        }

        .activity-card:hover {
            transform: translateY(-8px);

            box-shadow:
                0 20px 35px rgba(37, 99, 235, 0.1);

            color: inherit;
        }

        .card-pecs {
            border-color: #dbeafe;
        }

        .card-pecs:hover {
            border-color: #2563eb;
        }

        .card-speech {
            border-color: #dcfce7;
        }

        .card-speech:hover {
            border-color: #16a34a;
        }

        .card-calm {
            border-color: #fef9c3;
        }

        .card-calm:hover {
            border-color: #d97706;
        }

        .card-quests {
            border-color: #f3e8ff;
        }

        .card-quests:hover {
            border-color: #9333ea;
        }

        .card-games {
            border-color: #ffedd5;
        }

        .card-games:hover {
            border-color: #ea580c;
        }

        .card-grammar {
            border-color: #ccfbf1;
        }

        .card-grammar:hover {
            border-color: #0d9488;
        }

        /* =========================================
           ICON CIRCLE
        ========================================= */

        .icon-circle {
            width: 65px;
            height: 65px;

            border-radius: 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 1.8rem;

            margin-bottom: 20px;
        }

        /* =========================================
           PROGRESS
        ========================================= */

        .progress-pill {
            height: 12px;
            border-radius: 10px;

            background-color: #e2e8f0;

            overflow: hidden;
        }

        .progress-fill {
            height: 100%;

            background:
                linear-gradient(
                    90deg,
                    #2563eb,
                    #3b82f6
                );

            border-radius: 10px;

            transition: width 1s ease-in-out;
        }

        /* =========================================
           SUPPORT ALERT
        ========================================= */

        .alert-support {
            background: rgba(13, 148, 136, 0.08);

            border:
                1px solid rgba(13, 148, 136, 0.2);

            border-radius: 18px;

            padding: 16px 20px;

            color: #0f766e;
        }

        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 576px) {

            .activity-card {
                padding: 22px;
            }

            .display-4 {
                font-size: 2.3rem;
            }

            .stat-badge {
                padding: 10px 16px;
            }
        }

    </style>
</head>

<body>

<!-- =====================================================
     STUDENT NAVIGATION
===================================================== -->

<nav class="navbar navbar-student sticky-top">

    <div class="container">

        <a
            class="navbar-brand brand-font fs-3 text-primary d-flex align-items-center gap-2"
            href="dashboard.php"
        >
            <i class="fa-solid fa-brain fs-2"></i>
            AutiLearn
        </a>

        <div class="d-flex align-items-center gap-3">

            <span class="d-none d-md-inline fw-semibold text-secondary">
                Hello, 👋
                <strong class="text-dark">
                    <?php echo htmlspecialchars($studentName); ?>
                </strong>
            </span>

            <a
                href="<?php echo htmlspecialchars($logoutUrl); ?>"
                class="btn btn-outline-danger rounded-pill px-4 fw-semibold"
            >
                <i class="fa-solid fa-right-from-bracket me-1"></i>
                Exit
            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     STUDENT HERO & STATS
===================================================== -->

<div class="container mt-5">

    <div class="row align-items-center g-4 mb-4">

        <div class="col-lg-7">

            <span
                class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2 fw-semibold fs-6"
            >
                <i class="fa-solid fa-sparkles me-1"></i>
                Ready to learn & play?
            </span>

            <h1 class="display-4 fw-bold text-dark mb-2">
                Welcome Back,
                <?php echo htmlspecialchars($studentName); ?>! 🌟
            </h1>

            <p class="text-muted fs-5">
                Pick an activity, practice speech with helpful guidance,
                or play interactive learning games below.
            </p>

        </div>


        <div class="col-lg-5">

            <div class="d-flex flex-wrap gap-3 justify-content-lg-end">

                <!-- STARS -->

                <div class="stat-badge">

                    <i class="fa-solid fa-star text-warning fs-3"></i>

                    <div>

                        <div class="fs-4 fw-bold text-dark mb-0">
                            <?php echo (int)$starsEarned; ?>
                        </div>

                        <small class="text-muted">
                            Stars Earned
                        </small>

                    </div>

                </div>


                <!-- STREAK -->

                <div class="stat-badge">

                    <i class="fa-solid fa-fire text-danger fs-3"></i>

                    <div>

                        <div class="fs-4 fw-bold text-dark mb-0">
                            <?php echo (int)$streakDays; ?> Days
                        </div>

                        <small class="text-muted">
                            Daily Streak
                        </small>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         SUPPORT NOTICE
    ================================================== -->

    <div
        class="alert-support d-flex align-items-center gap-3 mb-4 shadow-sm"
    >

        <i class="fa-solid fa-circle-info fs-4 text-teal"></i>

        <div>

            <h6 class="fw-bold mb-1">
                Friendly Pronunciation & Grammar Support Active
            </h6>

            <p class="mb-0 small">
                When you speak or build sentences, our helper gives gentle,
                encouraging corrections without ratings or stress.
                Take all the time you need!
            </p>

        </div>

    </div>


    <!-- =================================================
         DAILY PROGRESS BAR
    ================================================== -->

    <div
        class="card border-0 rounded-4 shadow-sm p-4 mb-5 bg-white"
    >

        <div
            class="d-flex justify-content-between align-items-center mb-2"
        >

            <span class="fw-bold fs-5">

                <i class="fa-solid fa-trophy text-warning me-2"></i>

                Daily Goal Progress

            </span>

            <span class="fw-bold text-primary">

                <?php echo $completedActivities; ?>

                /

                <?php echo $totalActivities; ?>

                Activities Done

            </span>

        </div>


        <div class="progress-pill">

            <div
                class="progress-fill"
                style="width: <?php echo $progressPercentage; ?>%;"
            ></div>

        </div>

    </div>


    <!-- =================================================
         MAIN MODULES & GAMES
    ================================================== -->

    <h3 class="fw-bold mb-4 text-dark">

        <i class="fa-solid fa-gamepad text-primary me-2"></i>

        Learning Activities & Games

    </h3>


    <div class="row g-4">


        <!-- =================================================
             1. PECS VISUAL BOARD
        ================================================== -->

        <div class="col-md-6 col-lg-4">

            <a
                href="pecs.php"
                class="activity-card card-pecs"
            >

                <div>

                    <div
                        class="icon-circle bg-primary-subtle text-primary"
                    >
                        <i class="fa-solid fa-icons"></i>
                    </div>

                    <h4 class="fw-bold text-dark">
                        Visual PECS Board
                    </h4>

                    <p class="text-muted small">
                        Tap pictures to build sentences, hear words spoken
                        aloud, and view automatic sentence suggestions.
                    </p>

                </div>


                <div
                    class="d-flex align-items-center justify-content-between pt-3 border-top"
                >

                    <span
                        class="badge bg-primary rounded-pill px-3"
                    >
                        Communication
                    </span>

                    <i
                        class="fa-solid fa-circle-arrow-right fs-4 text-primary"
                    ></i>

                </div>

            </a>

        </div>


        <!-- =================================================
             2. AI SPEECH LAB
        ================================================== -->

        <div class="col-md-6 col-lg-4">

            <a
                href="speech.php"
                class="activity-card card-speech"
            >

                <div>

                    <div
                        class="icon-circle bg-success-subtle text-success"
                    >
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>

                    <h4 class="fw-bold text-dark">
                        AI Speech & Grammar Lab
                    </h4>

                    <p class="text-muted small">
                        Speak into your mic. Get real-time supportive alerts
                        and word suggestions to polish phrasing without pressure.
                    </p>

                </div>


                <div
                    class="d-flex align-items-center justify-content-between pt-3 border-top"
                >

                    <span
                        class="badge bg-success rounded-pill px-3"
                    >
                        Voice & Syntax
                    </span>

                    <i
                        class="fa-solid fa-circle-arrow-right fs-4 text-success"
                    ></i>

                </div>

            </a>

        </div>


        <!-- =================================================
             3. GRAMMAR & SENTENCE BUILDER
        ================================================== -->

        <div class="col-md-6 col-lg-4">

            <a
                href="grammar.php"
                class="activity-card card-grammar"
            >

                <div>

                    <div
                        class="icon-circle text-teal"
                        style="background-color: #ccfbf1;"
                    >

                        <i class="fa-solid fa-spell-check"></i>

                    </div>

                    <h4 class="fw-bold text-dark">
                        Sentence Builder & Check
                    </h4>

                    <p class="text-muted small">
                        Type or arrange words into sentences.
                        Receive friendly tips for missing words
                        or punctuation safely.
                    </p>

                </div>


                <div
                    class="d-flex align-items-center justify-content-between pt-3 border-top"
                >

                    <span
                        class="badge text-white rounded-pill px-3"
                        style="background-color: #0d9488;"
                    >
                        Grammar Helper
                    </span>

                    <i
                        class="fa-solid fa-circle-arrow-right fs-4"
                        style="color: #0d9488;"
                    ></i>

                </div>

            </a>

        </div>


        <!-- =================================================
             4. EMOTION MATCH GAME
             
             IMPORTANT:
             emotions.php is in the SAME student folder
             as dashboard.php.

             Therefore:
             href="emotions.php"

             This will correctly open:

             http://localhost/AutiLearn%20AI/student/emotions.php
        ================================================== -->

        <div class="col-md-6 col-lg-4">

            <a
                href="emotions.php"
                class="activity-card card-games"
                aria-label="Open Emotion Match Game"
            >

                <div>

                    <div
                        class="icon-circle"
                        style="
                            background-color: #ffedd5;
                            color: #ea580c;
                        "
                    >

                        <i class="fa-solid fa-face-smile-beam"></i>

                    </div>

                    <h4 class="fw-bold text-dark">
                        Emotion Match Game
                    </h4>

                    <p class="text-muted small">
                        Match friendly facial expressions with feelings
                        to build emotional recognition skills easily.
                    </p>

                </div>


                <div
                    class="d-flex align-items-center justify-content-between pt-3 border-top"
                >

                    <span
                        class="badge text-white rounded-pill px-3"
                        style="background-color: #ea580c;"
                    >
                        New Game
                    </span>

                    <i
                        class="fa-solid fa-circle-arrow-right fs-4"
                        style="color: #ea580c;"
                    ></i>

                </div>

            </a>

        </div>





        <!-- =================================================
             6. CALM SENSORY ZONE
        ================================================== -->

        <div class="col-md-6 col-lg-4">

            <a
                href="calm.php"
                class="activity-card card-calm"
            >

                <div>

                    <div
                        class="icon-circle bg-warning-subtle text-warning"
                    >

                        <i class="fa-solid fa-cloud-sun"></i>

                    </div>

                    <h4 class="fw-bold text-dark">
                        Calm Sensory Zone
                    </h4>

                    <p class="text-muted small">
                        Relaxing visuals, soothing ambient background sounds,
                        and guided breathing exercises.
                    </p>

                </div>


                <div
                    class="d-flex align-items-center justify-content-between pt-3 border-top"
                >

                    <span
                        class="badge bg-warning text-dark rounded-pill px-3"
                    >
                        Relaxation
                    </span>

                    <i
                        class="fa-solid fa-circle-arrow-right fs-4 text-warning"
                    ></i>

                </div>

            </a>

        </div>

    </div>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>
