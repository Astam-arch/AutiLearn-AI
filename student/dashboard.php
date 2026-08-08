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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Student Dashboard |
        <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?>
    </title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-gradient: radial-gradient(circle at 10% 20%, rgb(238, 242, 255) 0%, rgb(245, 243, 255) 90%);
            --card-radius: 30px;
            --primary-blue: #4f46e5;
            --soft-purple: #8b5cf6;
            --gentle-teal: #0d9488;
            --warm-amber: #f59e0b;
            --transition-smooth: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Poppins', sans-serif;
            color: #1e293b;
            min-height: 100vh;
            padding-bottom: 90px;
            overflow-x: hidden;
            position: relative;
        }

        /* =========================================
            BACKGROUND AMBIENT BLOBS
        ========================================= */
        .ambient-blob {
            position: absolute;
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            animation: blobFloat 10s ease-in-out infinite alternate;
        }
        .blob-1 { top: -100px; left: -100px; }
        .blob-2 { bottom: 10%; right: -100px; animation-delay: 5s; }

        @keyframes blobFloat {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, 50px) scale(1.1); }
        }

        h1, h2, h3, h4, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }

        /* =========================================
            ANIMATIONS & STAGGER EFFECTS
        ========================================= */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounceGentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        .animate-fade-in {
            animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .delay-1 { animation-delay: 0.1s; opacity: 0; }
        .delay-2 { animation-delay: 0.2s; opacity: 0; }
        .delay-3 { animation-delay: 0.3s; opacity: 0; }

        /* =========================================
            NAVBAR
        ========================================= */
        .navbar-student {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 16px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
        }

        .brand-icon-box {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            color: #fff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
            transition: transform 0.3s ease;
        }
        .brand-icon-box:hover {
            transform: rotate(10deg) scale(1.05);
        }

        /* =========================================
            STAT BADGES (GAMIFIED)
        ========================================= */
        .stat-badge {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 22px;
            padding: 16px 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            display: inline-flex;
            align-items: center;
            gap: 16px;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.8);
            transition: var(--transition-smooth);
            animation: bounceGentle 4s ease-in-out infinite;
        }

        .stat-badge:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.08);
            border-color: #c7d2fe;
        }

        .stat-badge:nth-child(2) {
            animation-delay: 2s;
        }

        /* =========================================
            ACTIVITY CARDS (GLASSMORPHIC & HOVER)
        ========================================= */
        .activity-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: var(--card-radius);
            padding: 32px;
            border: 2px solid rgba(255, 255, 255, 0.8);
            transition: var(--transition-smooth);
            height: 100%;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }

        .activity-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.4), transparent);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }

        .activity-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(79, 70, 229, 0.12);
            color: inherit;
            border-color: rgba(255, 255, 255, 1);
        }

        .activity-card:hover::before {
            opacity: 1;
        }

        /* Card theme variants */
        .card-pecs { border-bottom: 6px solid #4f46e5; }
        .card-speech { border-bottom: 6px solid #10b981; }
        .card-grammar { border-bottom: 6px solid #0d9488; }
        .card-games { border-bottom: 6px solid #f97316; }
        .card-calm { border-bottom: 6px solid #f59e0b; }

        /* =========================================
            ICON CIRCLES
        ========================================= */
        .icon-circle {
            width: 75px;
            height: 75px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.1rem;
            margin-bottom: 24px;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 20px rgba(0,0,0,0.03);
        }

        .activity-card:hover .icon-circle {
            transform: scale(1.12) rotate(8deg);
        }

        /* =========================================
            PROGRESS BAR
        ========================================= */
        .progress-container-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-radius: var(--card-radius);
            padding: 30px;
            border: 2px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.03);
        }

        .progress-pill {
            height: 16px;
            border-radius: 20px;
            background-color: #f1f5f9;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
            padding: 2px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4f46e5, #8b5cf6, #ec4899);
            border-radius: 20px;
            background-size: 200% 100%;
            animation: gradientShift 4s ease infinite;
            transition: width 1.5s cubic-bezier(0.1, 1, 0.1, 1);
            position: relative;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* =========================================
            SUPPORT ALERT BANNER
        ========================================= */
        .alert-support {
            background: linear-gradient(135deg, rgba(13, 148, 136, 0.08), rgba(20, 184, 166, 0.03));
            border: 1px solid rgba(13, 148, 136, 0.2);
            border-radius: 24px;
            padding: 20px 26px;
            color: #0f766e;
            backdrop-filter: blur(10px);
        }

        /* Arrow animation inside card footers */
        .activity-card .fa-circle-arrow-right {
            transition: transform 0.3s ease;
        }
        .activity-card:hover .fa-circle-arrow-right {
            transform: translateX(6px);
        }

        /* =========================================
            RESPONSIVE ADJUSTMENTS
        ========================================= */
        @media (max-width: 576px) {
            .activity-card { padding: 24px; }
            .display-4 { font-size: 2.2rem; }
        }
    </style>
</head>

<body>

<!-- Decorative Background Ambient Blobs -->
<div class="ambient-blob blob-1"></div>
<div class="ambient-blob blob-2"></div>

<!-- =====================================================
    STUDENT NAVIGATION
===================================================== -->
<nav class="navbar navbar-student sticky-top">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-dark d-flex align-items-center gap-3 text-decoration-none" href="dashboard.php">
            <div class="brand-icon-box">
                <i class="fa-solid fa-brain fs-4"></i>
            </div>
            <span>Auti<span class="text-primary">Learn</span></span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="d-none d-md-inline fw-medium text-secondary">
                Hello, 👋 <strong class="text-dark fw-semibold"><?php echo htmlspecialchars($studentName); ?></strong>
            </span>
            <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="btn btn-outline-danger rounded-pill px-4 fw-semibold shadow-sm transition-all" style="border-radius: 50px;">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Exit
            </a>
        </div>
    </div>
</nav>

<!-- =====================================================
    STUDENT HERO & STATS
===================================================== -->
<div class="container mt-4">

    <div class="row align-items-center g-4 mb-4 animate-fade-in">
        <div class="col-lg-7">
            <span class="badge bg-indigo-subtle text-indigo rounded-pill px-3.5 py-2 mb-3 fw-semibold fs-6" style="background-color: #ede9fe; color: #4f46e5;">
                <i class="fa-solid fa-wand-magic-sparkles me-1 text-primary"></i> Ready to learn & play today?
            </span>
            <h1 class="display-4 fw-bold text-dark mb-2" style="letter-spacing: -0.5px;">
                Welcome Back, <?php echo htmlspecialchars($studentName); ?>! 🌟
            </h1>
            <p class="text-secondary fs-5" style="max-width: 550px;">
                Pick an activity, practice speech with friendly real-time guidance, or play interactive learning games.
            </p>
        </div>

        <div class="col-lg-5">
            <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                <!-- STARS -->
                <div class="stat-badge">
                    <div class="fs-2 text-warning">
                        <i class="fa-solid fa-star fa-beat" style="--fa-animation-duration: 2.5s;"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-dark mb-0"><?php echo (int)$starsEarned; ?></div>
                        <small class="text-muted fw-medium">Stars Earned</small>
                    </div>
                </div>

                <!-- STREAK -->
                <div class="stat-badge">
                    <div class="fs-2 text-danger">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <div>
                        <div class="fs-3 fw-bold text-dark mb-0"><?php echo (int)$streakDays; ?> Days</div>
                        <small class="text-muted fw-medium">Daily Streak</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =================================================
         SUPPORT NOTICE
    ================================================== -->
    <div class="alert-support d-flex align-items-center gap-3 mb-4 shadow-sm animate-fade-in delay-1">
        <i class="fa-solid fa-circle-info fs-3 text-teal" style="color: #0d9488;"></i>
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
    <div class="progress-container-card mb-5 animate-fade-in delay-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold fs-5 text-dark d-flex align-items-center gap-2">
                <i class="fa-solid fa-trophy text-warning"></i> Daily Goal Progress
            </span>
            <span class="fw-bold text-primary bg-primary-subtle px-3 py-1 rounded-pill fs-6" style="background-color: #e0e7ff; color: #4f46e5;">
                <?php echo $completedActivities; ?> / <?php echo $totalActivities; ?> Done
            </span>
        </div>
        <div class="progress-pill">
            <div class="progress-fill" style="width: <?php echo $progressPercentage; ?>%;"></div>
        </div>
    </div>

    <!-- =================================================
         MAIN MODULES & GAMES
    ================================================== -->
    <div class="d-flex align-items-center justify-content-between mb-4 animate-fade-in delay-3">
        <h3 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-gamepad text-primary me-2"></i> Learning Activities & Games
        </h3>
    </div>

    <div class="row g-4 animate-fade-in delay-3">

        <!-- 1. PECS VISUAL BOARD -->
        <div class="col-md-6 col-lg-4">
            <a href="pecs.php" class="activity-card card-pecs">
                <div>
                    <div class="icon-circle" style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5;">
                        <i class="fa-solid fa-icons"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Visual PECS Board</h4>
                    <p class="text-muted small">
                        Tap pictures to build sentences, hear words spoken aloud, and view automatic sentence suggestions.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light mt-3">
                    <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: #e0e7ff; color: #4f46e5;">Communication</span>
                    <i class="fa-solid fa-circle-arrow-right fs-4 text-primary"></i>
                </div>
            </a>
        </div>

        <!-- 2. AI SPEECH LAB -->
        <div class="col-md-6 col-lg-4">
            <a href="speech.php" class="activity-card card-speech">
                <div>
                    <div class="icon-circle" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669;">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">AI Speech & Grammar Lab</h4>
                    <p class="text-muted small">
                        Speak into your mic. Get real-time supportive alerts and word suggestions to polish phrasing without pressure.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light mt-3">
                    <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: #d1fae5; color: #059669;">Voice & Syntax</span>
                    <i class="fa-solid fa-circle-arrow-right fs-4 text-success"></i>
                </div>
            </a>
        </div>

        <!-- 3. SENTENCE BUILDER -->
        <div class="col-md-6 col-lg-4">
            <a href="grammar.php" class="activity-card card-grammar">
                <div>
                    <div class="icon-circle" style="background: linear-gradient(135deg, #ccfbf1, #99f6e4); color: #0d9488;">
                        <i class="fa-solid fa-spell-check"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Sentence Builder & Check</h4>
                    <p class="text-muted small">
                        Type or arrange words into sentences. Receive friendly tips for missing words or punctuation safely.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light mt-3">
                    <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: #ccfbf1; color: #0d9488;">Grammar Helper</span>
                    <i class="fa-solid fa-circle-arrow-right fs-4" style="color: #0d9488;"></i>
                </div>
            </a>
        </div>

        <!-- 4. EMOTION MATCH GAME -->
        <div class="col-md-6 col-lg-4">
            <a href="emotions.php" class="activity-card card-games" aria-label="Open Emotion Match Game">
                <div>
                    <div class="icon-circle" style="background: linear-gradient(135deg, #ffedd5, #fed7aa); color: #ea580c;">
                        <i class="fa-solid fa-face-smile-beam"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Emotion Match Game</h4>
                    <p class="text-muted small">
                        Match friendly facial expressions with feelings to build emotional recognition skills easily.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light mt-3">
                    <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: #ffedd5; color: #ea580c;">New Game</span>
                    <i class="fa-solid fa-circle-arrow-right fs-4" style="color: #ea580c;"></i>
                </div>
            </a>
        </div>

        <!-- 5. CALM SENSORY ZONE -->
        <div class="col-md-6 col-lg-4">
            <a href="calm.php" class="activity-card card-calm">
                <div>
                    <div class="icon-circle" style="background: linear-gradient(135deg, #fef9c3, #fef08a); color: #ca8a04;">
                        <i class="fa-solid fa-cloud-sun"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Calm Sensory Zone</h4>
                    <p class="text-muted small">
                        Relaxing visuals, soothing ambient background sounds, and guided breathing exercises.
                    </p>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light mt-3">
                    <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: #fef9c3; color: #854d0e;">Relaxation</span>
                    <i class="fa-solid fa-circle-arrow-right fs-4 text-warning"></i>
                </div>
            </a>
        </div>

    </div>

</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>