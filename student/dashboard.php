<?php
// student/dashboard.php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    $role = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
    $dashboardUrl = defined('BASE_URL')
        ? BASE_URL . "{$role}/dashboard.php"
        : "../{$role}/dashboard.php";

    header("Location: {$dashboardUrl}");
    exit;
}

$studentName = $_SESSION['full_name'] ?? 'Learner';
$studentId   = (int)($_SESSION['user_id'] ?? 0);

// =====================================================
// 2. FETCH OR DEFAULT GAMIFICATION STATS
// =====================================================
$starsEarned = 24;
$streakDays = 5;
$completedActivities = 3;
$totalActivities = 6;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare(
            "SELECT stars_earned, streak_days FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$studentId]);
        $userStats = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userStats) {
            $starsEarned = isset($userStats['stars_earned']) ? (int)$userStats['stars_earned'] : $starsEarned;
            $streakDays = isset($userStats['streak_days']) ? (int)$userStats['streak_days'] : $streakDays;
        }
    } catch (PDOException $e) {
        // Soft fallback to defaults
    }
}

$safeTotalActivities = ($totalActivities > 0) ? $totalActivities : 1;
$progressPercentage = min(100, max(0, round(($completedActivities / $safeTotalActivities) * 100)));

$logoutUrl = defined('BASE_URL') ? BASE_URL . 'logout.php' : '../logout.php';
$siteName  = defined('SITE_NAME') ? SITE_NAME : 'Spark Steps';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | <?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f4f7fc;
            --card-bg: #ffffff;
            --card-radius: 24px;
            --text-main: #0f172a;
            --text-muted: #334155; /* Darker, high contrast */
            --primary-blue: #4f46e5;
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Poppins', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 80px;
        }

        h1, h2, h3, h4, h5, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }

        /* =========================================
            NAVBAR
        ========================================= */
        .navbar-student {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .brand-icon-box {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        /* =========================================
            STAT BADGES
        ========================================= */
        .stat-badge {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 16px 22px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid #e2e8f0;
            transition: var(--transition-smooth);
        }

        .stat-badge:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.08);
            border-color: #cbd5e1;
        }

        /* =========================================
            ACTIVITY CARDS (ENHANCED DESIGN)
        ========================================= */
        .activity-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            padding: 32px 28px;
            border: 1px solid #e2e8f0;
            transition: var(--transition-smooth);
            height: 100%;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }

        .activity-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: transparent;
            transition: var(--transition-smooth);
        }

        .activity-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 35px rgba(79, 70, 229, 0.12);
            border-color: #cbd5e1;
            color: inherit;
        }

        /* Accent top borders for cards */
        .card-pecs::before { background: #4f46e5; }
        .card-speech::before { background: #059669; }
        .card-grammar::before { background: #0d9488; }
        .card-games::before { background: #ea580c; }
        .card-calm::before { background: #ca8a04; }

        /* =========================================
            ICON BOXES
        ========================================= */
        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        /* Card Text High Visibility Fix */
        .activity-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .activity-desc {
            font-size: 0.98rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 0;
            font-weight: 500;
        }

        /* =========================================
            PROGRESS BAR
        ========================================= */
        .progress-container-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .progress-pill {
            height: 14px;
            border-radius: 20px;
            background-color: #e2e8f0;
            overflow: hidden;
            padding: 2px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            border-radius: 20px;
            transition: width 1s ease-in-out;
        }

        /* =========================================
            SUPPORT ALERT BANNER
        ========================================= */
        .alert-support {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            padding: 18px 22px;
            color: #166534;
        }
    </style>
</head>

<body>

<!-- =====================================================
    STUDENT NAVIGATION
===================================================== -->
<nav class="navbar navbar-student sticky-top">
    <div class="container">
        <a class="navbar-brand brand-font fs-4 text-dark d-flex align-items-center gap-3 text-decoration-none" href="dashboard.php">
            <div class="brand-icon-box">
                <i class="fa-solid fa-brain fs-5"></i>
            </div>
            <span>Spark <span class="text-primary">Steps</span></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="d-none d-md-inline fw-medium text-secondary">
                Hello, 👋 <strong class="text-dark fw-semibold"><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></strong>
            </span>
            <a href="<?php echo htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Exit
            </a>
        </div>
    </div>
</nav>

<!-- =====================================================
    STUDENT HERO & STATS
===================================================== -->
<div class="container mt-4">

    <div class="row align-items-center g-4 mb-4">
        <div class="col-lg-7">
            <span class="badge bg-indigo-subtle text-indigo rounded-pill px-3 py-2 mb-3 fw-semibold" style="background-color: #ede9fe; color: #4f46e5;">
                <i class="fa-solid fa-wand-magic-sparkles me-1 text-primary"></i> Ready to learn & play today?
            </span>
            <h1 class="display-5 fw-bold text-dark mb-2">
                Welcome Back, <?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>! 🌟
            </h1>
            <p class="text-secondary fs-6 mb-0" style="max-width: 550px;">
                Pick an activity, practice speech with friendly real-time guidance, or play interactive learning games.
            </p>
        </div>

        <div class="col-lg-5">
            <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                <!-- STARS -->
                <div class="stat-badge">
                    <div class="fs-2 text-warning">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark mb-0"><?php echo (int)$starsEarned; ?></div>
                        <small class="text-muted fw-semibold">Stars Earned</small>
                    </div>
                </div>

                <!-- STREAK -->
                <div class="stat-badge">
                    <div class="fs-2 text-danger">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark mb-0"><?php echo (int)$streakDays; ?> Days</div>
                        <small class="text-muted fw-semibold">Daily Streak</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================
         SUPPORT NOTICE
    ================================================== -->
    <div class="alert-support d-flex align-items-center gap-3 mb-4 shadow-sm">
        <i class="fa-solid fa-circle-info fs-3 text-success"></i>
        <div>
            <h6 class="fw-bold mb-1">Friendly Pronunciation & Grammar Support Active</h6>
            <p class="mb-0 small text-secondary">
                When you speak or build sentences, our helper gives gentle, encouraging tips without ratings or stress. Take all the time you need!
            </p>
        </div>
    </div>

    <!-- =================================================
         DAILY PROGRESS BAR
    ================================================== -->
    <div class="progress-container-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold fs-6 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-trophy text-warning"></i> Daily Goal Progress
            </span>
            <span class="fw-bold text-primary px-3 py-1 rounded-pill small" style="background-color: #e0e7ff;">
                <?php echo (int)$completedActivities; ?> / <?php echo (int)$totalActivities; ?> Done
            </span>
        </div>
        <div class="progress-pill">
            <div class="progress-fill" style="width: <?php echo $progressPercentage; ?>%;"></div>
        </div>
    </div>

    <!-- =================================================
         MAIN MODULES & GAMES
    ================================================== -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-bold text-dark mb-0 fs-4">
            <i class="fa-solid fa-gamepad text-primary me-2"></i> Learning Activities & Games
        </h3>
    </div>

    <div class="row g-4">

        <!-- 1. PECS VISUAL BOARD -->
        <div class="col-md-6 col-lg-4">
            <a href="pecs.php" class="activity-card card-pecs">
                <div>
                    <div class="icon-circle" style="background: #e0e7ff; color: #4f46e5;">
                        <i class="fa-solid fa-icons"></i>
                    </div>
                    <h4 class="activity-title">Visual PECS Board</h4>
                    <p class="activity-desc">
                        Tap pictures to build sentences, hear words spoken aloud, and view automatic sentence suggestions.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-4">
                    <span class="badge rounded-pill px-3 py-2 fw-bold" style="background-color: #e0e7ff; color: #4f46e5; font-size: 0.85rem;">Communication</span>
                    <i class="fa-solid fa-circle-arrow-right fs-3 text-primary"></i>
                </div>
            </a>
        </div>

        <!-- 2. AI SPEECH LAB -->
        <div class="col-md-6 col-lg-4">
            <a href="speech.php" class="activity-card card-speech">
                <div>
                    <div class="icon-circle" style="background: #d1fae5; color: #059669;">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>
                    <h4 class="activity-title">AI Speech & Grammar Lab</h4>
                    <p class="activity-desc">
                        Speak into your mic. Get real-time supportive alerts and word suggestions to polish phrasing without pressure.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-4">
                    <span class="badge rounded-pill px-3 py-2 fw-bold" style="background-color: #d1fae5; color: #059669; font-size: 0.85rem;">Voice & Syntax</span>
                    <i class="fa-solid fa-circle-arrow-right fs-3 text-success"></i>
                </div>
            </a>
        </div>

        <!-- 3. SENTENCE BUILDER -->
        <div class="col-md-6 col-lg-4">
            <a href="grammar.php" class="activity-card card-grammar">
                <div>
                    <div class="icon-circle" style="background: #ccfbf1; color: #0d9488;">
                        <i class="fa-solid fa-spell-check"></i>
                    </div>
                    <h4 class="activity-title">Sentence Builder & Check</h4>
                    <p class="activity-desc">
                        Type or arrange words into sentences. Receive friendly tips for missing words or punctuation safely.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-4">
                    <span class="badge rounded-pill px-3 py-2 fw-bold" style="background-color: #ccfbf1; color: #0d9488; font-size: 0.85rem;">Grammar Helper</span>
                    <i class="fa-solid fa-circle-arrow-right fs-3" style="color: #0d9488;"></i>
                </div>
            </a>
        </div>

        <!-- 4. EMOTION MATCH GAME -->
        <div class="col-md-6 col-lg-4">
            <a href="emotions.php" class="activity-card card-games" aria-label="Open Emotion Match Game">
                <div>
                    <div class="icon-circle" style="background: #ffedd5; color: #ea580c;">
                        <i class="fa-solid fa-face-smile-beam"></i>
                    </div>
                    <h4 class="activity-title">Emotion Match Game</h4>
                    <p class="activity-desc">
                        Match friendly facial expressions with feelings to build emotional recognition skills easily.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-4">
                    <span class="badge rounded-pill px-3 py-2 fw-bold" style="background-color: #ffedd5; color: #ea580c; font-size: 0.85rem;">New Game</span>
                    <i class="fa-solid fa-circle-arrow-right fs-3" style="color: #ea580c;"></i>
                </div>
            </a>
        </div>

        <!-- 5. CALM SENSORY ZONE -->
        <div class="col-md-6 col-lg-4">
            <a href="calm.php" class="activity-card card-calm">
                <div>
                    <div class="icon-circle" style="background: #fef9c3; color: #ca8a04;">
                        <i class="fa-solid fa-cloud-sun"></i>
                    </div>
                    <h4 class="activity-title">Calm Sensory Zone</h4>
                    <p class="activity-desc">
                        Relaxing visuals, soothing ambient background sounds, and guided breathing exercises.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-4">
                    <span class="badge rounded-pill px-3 py-2 fw-bold" style="background-color: #fef9c3; color: #854d0e; font-size: 0.85rem;">Relaxation</span>
                    <i class="fa-solid fa-circle-arrow-right fs-3 text-warning"></i>
                </div>
            </a>
        </div>

    </div>

</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>