<?php
// teacher/dashboard.php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Session & Role Guard for Teachers
if (!isset($_SESSION['user_id'])) {
    $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: {$loginUrl}");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] !== 'teacher') {
    $role = $_SESSION['role'];
    $redirectUrl = defined('BASE_URL') ? BASE_URL . "{$role}/dashboard.php" : "../{$role}/dashboard.php";
    header("Location: {$redirectUrl}");
    exit;
}

$teacherName = $_SESSION['full_name'] ?? 'Instructor';
$logoutUrl = defined('BASE_URL') ? BASE_URL . 'logout.php' : '../logout.php';

// Initialize real metric variables
$totalStudents = 0;
$activeSessionsToday = 0;
$onlineNowCount = 0;
$avgSpeechAccuracy = '0%';
$pendingReviews = 0;
$totalSpeechLabs = 0;
$recentStudents = [];

try {
    if (isset($pdo)) {
        // 1. Total Enrolled Students Count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student'");
        $stmt->execute();
        $totalStudents = (int)$stmt->fetchColumn();

        // 2. Active Sessions Today
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM student_activity WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        $activeSessionsToday = (int)$stmt->fetchColumn();

        // 3. Students Online Right Now (active within last 15 minutes)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND (last_active >= NOW() - INTERVAL 15 MINUTE OR updated_at >= NOW() - INTERVAL 15 MINUTE)");
        $stmt->execute();
        $onlineNowCount = (int)$stmt->fetchColumn();

        // 4. Average Speech Accuracy & Total Speech Lab Records
        $stmt = $pdo->prepare("SELECT AVG(accuracy_score), COUNT(*) FROM speech_labs");
        $stmt->execute();
        $speechData = $stmt->fetch(PDO::FETCH_NUM);
        if ($speechData) {
            $avgScore = $speechData[0];
            $totalSpeechLabs = (int)$speechData[1];
            if ($avgScore !== false && $avgScore !== null) {
                $avgSpeechAccuracy = round($avgScore) . '%';
            }
        }

        // 5. Pending Submissions/Reviews Count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE status = 'pending'");
        $stmt->execute();
        $pendingReviews = (int)$stmt->fetchColumn();

        // 6. Recent Student Activity Feed with real user name resolution
        $activityQuery = "
            SELECT u.id as student_id,
                   COALESCE(u.full_name, CONCAT(u.first_name, ' ', u.last_name), CONCAT('Student #', u.id)) as name, 
                   COALESCE(u.last_active, u.updated_at) as last_seen,
                   COALESCE(s.module_name, 'Learning Module') as module, 
                   COALESCE(s.accuracy_score, 0) as accuracy_val, 
                   s.created_at, 
                   COALESCE(s.status, 'Completed') as status
            FROM users u
            LEFT JOIN speech_labs s ON u.id = s.user_id
            WHERE u.role = 'student'
            ORDER BY s.created_at DESC, u.last_active DESC
            LIMIT 15
        ";
        $stmt = $pdo->query($activityQuery);
        $fetchedActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($fetchedActivity)) {
            foreach ($fetchedActivity as $row) {
                $timeAgo = "Recently";
                if (!empty($row['created_at'])) {
                    $timeDiff = time() - strtotime($row['created_at']);
                    if ($timeDiff < 60) {
                        $timeAgo = "Just now";
                    } elseif ($timeDiff < 3600) {
                        $timeAgo = floor($timeDiff / 60) . " mins ago";
                    } elseif ($timeDiff < 86400) {
                        $timeAgo = floor($timeDiff / 3600) . " hours ago";
                    } else {
                        $timeAgo = floor($timeDiff / 86400) . " days ago";
                    }
                }

                $isOnline = false;
                if (!empty($row['last_seen'])) {
                    $isOnline = (time() - strtotime($row['last_seen'])) < 900;
                }

                $recentStudents[] = [
                    'id' => $row['student_id'],
                    'name' => $row['name'],
                    'module' => $row['module'],
                    'accuracy' => $row['accuracy_val'],
                    'time' => $timeAgo,
                    'status' => $row['status'],
                    'is_online' => $isOnline
                ];
            }
        }
    }
} catch (PDOException $e) {
    error_log("Teacher Dashboard Real Data Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Portal | <?php echo defined('SITE_NAME') ? SITE_NAME : 'Spark Steps'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Calm, professional palette — teal + soft blue, built for readability */
            --bg-main: #f4f7fb;
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #dbeafe;
            --primary-glow: rgba(37, 99, 235, 0.18);
            --teal: #0d9488;
            --teal-light: #ccfbf1;

            --card-radius: 18px;
            --border-color: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;

            --success: #16a34a;
            --success-light: #dcfce7;
            --warning: #d97706;
            --warning-light: #fef3c7;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --info: #0284c7;
            --info-light: #e0f2fe;

            --grad-primary: linear-gradient(135deg, #2563eb 0%, #0d9488 100%);
        }

        body {
            background-color: var(--bg-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            padding-bottom: 80px;
            font-size: 1rem;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, .brand-font {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.02em;
        }

        a { color: var(--primary-color); }

        /* Subtle ambient background — kept light so it never competes with content */
        .ambient-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: -1;
            overflow: hidden;
        }
        .ambient-circle {
            position: absolute;
            filter: blur(110px);
            opacity: 0.10;
            border-radius: 50%;
        }
        .c1 { width: 420px; height: 420px; background: var(--primary-color); top: -120px; left: -100px; }
        .c2 { width: 420px; height: 420px; background: var(--teal); bottom: -120px; right: -100px; }

        /* NAVIGATION */
        .navbar-teacher {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
        }

        .brand-icon-box {
            background: var(--grad-primary);
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
        }

        .brand-title { font-size: 1.3rem; }
        .brand-subtitle { font-size: 0.72rem; letter-spacing: 1px; }

        .online-badge {
            background: var(--success-light);
            border: 1px solid #bbf7d0;
            border-radius: 50rem;
            padding: 8px 16px;
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            background: var(--primary-light);
            color: var(--primary-hover);
        }

        /* HERO BANNER */
        .hero-banner {
            background: var(--grad-primary);
            border-radius: var(--card-radius);
            box-shadow: 0 12px 30px -10px rgba(37, 99, 235, 0.35);
            position: relative;
            overflow: hidden;
            padding: 40px !important;
            color: #ffffff;
        }
        .hero-banner::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            pointer-events: none;
        }
        .hero-badge {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .hero-banner p { color: rgba(255, 255, 255, 0.92); }
        .hero-cta {
            background: #ffffff;
            color: var(--primary-hover) !important;
            border: none;
            font-weight: 700;
            border-radius: 50rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        .hero-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(0,0,0,0.2);
            color: var(--primary-hover) !important;
        }

        /* STAT CARDS */
        .stat-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 24px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.05);
            border: 1px solid var(--border-color);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 26px -8px rgba(15, 23, 42, 0.12);
        }
        .stat-label {
            font-size: 0.82rem;
            letter-spacing: 0.04em;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
        }
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        /* SECTION CONTAINER */
        .dashboard-section {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 32px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 28px;
        }

        /* QUICK ACTION CARDS */
        .quick-action-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 26px 20px;
            text-align: center;
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            height: 100%;
        }
        .quick-action-card:hover {
            color: var(--primary-hover);
            border-color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: 0 10px 24px -8px var(--primary-glow);
        }
        .quick-action-card .qa-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        /* TABLE */
        .table-custom th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-top: none;
            border-bottom: 2px solid var(--border-color);
            padding: 14px 18px;
            white-space: nowrap;
        }
        .table-custom td {
            padding: 16px 18px;
            vertical-align: middle;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        /* SEARCH & FILTERS */
        .search-box {
            border-radius: 50rem;
            border: 1px solid var(--border-color);
            padding: 10px 18px;
            background-color: #f8fafc;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.9rem;
        }
        .search-box:focus {
            background-color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .filter-pill {
            border-radius: 50rem;
            font-size: 0.85rem;
            padding: 7px 18px;
            font-weight: 700;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }
        .filter-pill.active, .filter-pill:hover {
            background: var(--primary-color);
            color: #ffffff;
        }

        /* ONLINE PULSE */
        .online-pulse {
            width: 9px;
            height: 9px;
            background-color: var(--success);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.6);
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.6); }
            70% { box-shadow: 0 0 0 8px rgba(22, 163, 74, 0); }
            100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
        }

        /* BUTTONS */
        .btn-gradient {
            background: var(--grad-primary);
            color: #ffffff;
            border: none;
            border-radius: 50rem;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }
        .btn-gradient:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
            filter: brightness(1.05);
        }
        .btn-gradient:active {
            transform: translateY(0);
        }

        /* MODALS */
        .modal-content {
            border-radius: var(--card-radius);
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 50px -15px rgba(0, 0, 0, 0.2);
        }

        /* PROGRESS BAR */
        .progress-bar {
            transition: width 0.8s ease;
            background: var(--grad-primary) !important;
        }

        /* Badges */
        .badge-soft-success { background: var(--success-light); color: var(--success); }
        .badge-soft-info { background: var(--info-light); color: var(--info); }
        .badge-soft-warning { background: var(--warning-light); color: var(--warning); }

        /* RESPONSIVE ADJUSTMENTS */
        @media (max-width: 991.98px) {
            .hero-banner { padding: 32px !important; text-align: left; }
            .hero-banner .display-5 { font-size: 1.8rem; }
        }

        @media (max-width: 767.98px) {
            body { padding-bottom: 40px; }
            .dashboard-section { padding: 22px; }
            .hero-banner { padding: 26px !important; }
            .hero-banner .display-5 { font-size: 1.5rem; }
            .stat-value { font-size: 1.6rem; }
            .stat-icon { width: 48px; height: 48px; font-size: 1.2rem; }
            .quick-action-card { padding: 20px 14px; font-size: 0.95rem; }
            .table-custom td, .table-custom th { padding: 12px; font-size: 0.85rem; }
            .search-box { width: 100% !important; }
        }

        @media (max-width: 575.98px) {
            .hero-banner .btn-gradient,
            .hero-banner .hero-cta { width: 100%; }
            .navbar .fw-bold.fs-6 { font-size: 0.85rem !important; }
        }
    </style>
</head>
<body>

<!-- AMBIENT BACKGROUND GLOWS -->
<div class="ambient-bg">
    <div class="ambient-circle c1"></div>
    <div class="ambient-circle c2"></div>
</div>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-teacher mb-5">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
        <a class="navbar-brand brand-font text-dark fw-bold d-flex align-items-center gap-3 text-decoration-none mb-0" href="#">
            <div class="brand-icon-box">
                <i class="fa-solid fa-brain fs-5"></i>
            </div>
            <div>
                <span class="d-block lh-1 text-dark fw-bold brand-title">Spark Steps</span>
                <span class="text-muted fw-bold brand-subtitle text-uppercase">Instructor Portal</span>
            </div>
        </a>
        <div class="d-flex align-items-center gap-2 gap-sm-3">
            <div class="d-none d-sm-flex align-items-center gap-2 online-badge">
                <span class="online-pulse"></span>
                <span class="fw-bold text-dark" style="font-size:0.9rem;"><?php echo $onlineNowCount; ?> Online Now</span>
            </div>
            <div class="dropdown">
                <button class="btn border dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3 py-2 bg-white" type="button" data-bs-toggle="dropdown">
                    <div class="avatar-circle rounded-circle d-flex align-items-center justify-content-center fw-bold">
                        <?php echo strtoupper(substr($teacherName, 0, 1)); ?>
                    </div>
                    <span class="fw-bold text-dark d-none d-md-inline" style="font-size:0.9rem;"><?php echo htmlspecialchars($teacherName); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border rounded-3 mt-2 p-2">
                    <li><h6 class="dropdown-header text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Signed in as Instructor</h6></li>
                    <li><a class="dropdown-item py-2 px-3 rounded-3 fw-bold text-danger mt-1" href="<?php echo htmlspecialchars($logoutUrl); ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container">

    <!-- WELCOME HERO BANNER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="hero-banner d-md-flex align-items-center justify-content-between">
                <div class="mb-4 mb-md-0 z-1">
                    <span class="badge hero-badge rounded-pill px-3 py-2 fw-bold mb-3" style="font-size:0.8rem;">
                        <i class="fa-solid fa-shield-halved me-2"></i> Live Session Command Center
                    </span>
                    <h1 class="brand-font mb-2 fw-bold display-5">Welcome back, <?php echo htmlspecialchars($teacherName); ?>!</h1>
                    <p class="mb-0 fw-medium" style="max-width: 640px; font-size:1.05rem;">Monitor real-time student audio lab sessions, speech accuracy performance metrics, and learning platform activities seamlessly.</p>
                </div>
                <div class="z-1">
                    <button class="btn hero-cta px-4 py-3 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#broadcastModal">
                        <i class="fa-solid fa-bullhorn"></i> Send Announcement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- STATISTICS CARDS ROW -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted stat-label mb-2 fw-bold text-uppercase">Total Students</p>
                        <h2 class="brand-font text-dark mb-0 stat-value"><?php echo $totalStudents; ?></h2>
                    </div>
                    <div class="stat-icon" style="background: var(--info-light); color: var(--info);">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted stat-label mb-2 fw-bold text-uppercase">Active Today</p>
                        <h2 class="brand-font text-dark mb-0 stat-value"><?php echo $activeSessionsToday; ?></h2>
                    </div>
                    <div class="stat-icon" style="background: var(--primary-light); color: var(--primary-hover);">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted stat-label mb-2 fw-bold text-uppercase">Avg. Accuracy</p>
                        <h2 class="brand-font text-dark mb-0 stat-value"><?php echo $avgSpeechAccuracy; ?></h2>
                    </div>
                    <div class="stat-icon" style="background: var(--warning-light); color: var(--warning);">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted stat-label mb-2 fw-bold text-uppercase">Pending Reviews</p>
                        <h2 class="brand-font text-dark mb-0 stat-value"><?php echo $pendingReviews; ?></h2>
                    </div>
                    <div class="stat-icon" style="background: var(--danger-light); color: var(--danger);">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="row mb-4">
        <div class="col-12 mb-3">
            <h2 class="fw-bold text-dark brand-font fs-4">
                <i class="fa-solid fa-bolt me-2" style="color: var(--warning);"></i>Quick Management Actions
            </h2>
        </div>
        <div class="col-6 col-md-4 mb-3 mb-md-0">
            <a href="students.php" class="quick-action-card">
                <div class="qa-icon" style="background: var(--info-light); color: var(--info);">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                Manage Students
            </a>
        </div>
        <div class="col-6 col-md-4 mb-3 mb-md-0">
            <a href="speech_logs.php" class="quick-action-card">
                <div class="qa-icon" style="background: var(--success-light); color: var(--success);">
                    <i class="fa-solid fa-headphones-simple"></i>
                </div>
                Review Speech Logs
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a href="reports.php" class="quick-action-card" data-bs-toggle="modal" data-bs-target="#reportsModal">
                <div class="qa-icon" style="background: var(--warning-light); color: var(--warning);">
                    <i class="fa-solid fa-file-arrow-down"></i>
                </div>
                Export Reports
            </a>
        </div>
    </div>

    <!-- RECENT STUDENT ACTIVITY TABLE WITH SEARCH & FILTER -->
    <div class="dashboard-section">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1 brand-font fs-4">
                    <i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i>Recent Student Activity
                </h2>
                <p class="text-muted mb-0" style="font-size:0.92rem;">Live tracking of module interactions and student audio precision scores.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 gap-sm-3">
                <!-- Filter Tabs -->
                <div class="btn-group border rounded-pill p-1 bg-white" role="group">
                    <button type="button" class="filter-pill active" onclick="filterTable('all', this)">All</button>
                    <button type="button" class="filter-pill" onclick="filterTable('Completed', this)">Completed</button>
                    <button type="button" class="filter-pill" onclick="filterTable('Needs Review', this)">Needs Review</button>
                </div>
                <!-- Search Box -->
                <div class="input-group" style="max-width: 260px;">
                    <span class="input-group-text bg-white border-end-0 search-box rounded-start-pill ps-3"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="activitySearch" class="form-control border-start-0 search-box rounded-end-pill shadow-none ps-0 fw-semibold" placeholder="Search student...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0" id="activityTable">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Module / Activity</th>
                        <th>Accuracy Score</th>
                        <th>Timestamp</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentStudents)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted fw-bold">
                                <i class="fa-solid fa-circle-info me-2" style="color: var(--primary-color);"></i> No live student activity recorded in the database yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentStudents as $student): ?>
                            <tr data-status="<?php echo htmlspecialchars($student['status']); ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="position-relative">
                                            <div class="avatar-circle rounded-circle d-flex align-items-center justify-content-center fw-bold">
                                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                            </div>
                                            <?php if (!empty($student['is_online'])): ?>
                                                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" title="Online Now"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($student['name']); ?></span>
                                            <?php if (!empty($student['is_online'])): ?>
                                                <small class="text-success fw-bold" style="font-size: 0.72rem;"><i class="fa-solid fa-circle" style="font-size: 5px;"></i> Active Now</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-dark fw-bold"><?php echo htmlspecialchars($student['module']); ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 bg-light border" style="height: 8px; width: 90px;">
                                            <div class="progress-bar rounded-pill" role="progressbar" style="width: <?php echo $student['accuracy']; ?>%;"></div>
                                        </div>
                                        <span class="fw-bold text-dark"><?php echo $student['accuracy']; ?>%</span>
                                    </div>
                                </td>
                                <td class="fw-semibold text-secondary"><?php echo htmlspecialchars($student['time']); ?></td>
                                <td>
                                    <?php if ($student['status'] === 'Excellent' || $student['status'] === 'Completed'): ?>
                                        <span class="badge badge-soft-success fw-bold px-3 py-2 rounded-pill">Completed</span>
                                    <?php elseif ($student['status'] === 'Good'): ?>
                                        <span class="badge badge-soft-info fw-bold px-3 py-2 rounded-pill">Good</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-warning fw-bold px-3 py-2 rounded-pill">Needs Review</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Reports & Export Modal -->
<div class="modal fade" id="reportsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow p-3">
            <div class="modal-header border-bottom px-3 py-3">
                <h4 class="modal-title brand-font fw-bold text-dark"><i class="fa-solid fa-file-arrow-down me-2" style="color: var(--warning);"></i> Export Progress Report</h4>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form action="reports.php" method="GET">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-uppercase" style="font-size:0.8rem;">Report Format</label>
                        <select name="format" class="form-select rounded-3 border fw-semibold py-2">
                            <option value="csv">CSV Spreadsheet</option>
                            <option value="pdf">PDF Document Summary</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-uppercase" style="font-size:0.8rem;">Date Range</label>
                        <select name="range" class="form-select rounded-3 border fw-semibold py-2">
                            <option value="today">Today</option>
                            <option value="week" selected>Past 7 Days</option>
                            <option value="month">Past 30 Days</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-gradient w-100 rounded-pill py-3 mt-3 fw-bold"><i class="fa-solid fa-download me-2"></i>Download Report</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 2. Announcement Modal -->
<div class="modal fade" id="broadcastModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow p-3">
            <div class="modal-header border-bottom px-3 py-3">
                <h4 class="modal-title brand-font fw-bold text-dark"><i class="fa-solid fa-bullhorn me-2" style="color: var(--warning);"></i> Broadcast Announcement</h4>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form action="broadcast.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-uppercase" style="font-size:0.8rem;">Message Title</label>
                        <input type="text" name="title" class="form-control rounded-3 border fw-semibold py-2" placeholder="e.g. Weekly Speech Challenge" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-uppercase" style="font-size:0.8rem;">Announcement Details</label>
                        <textarea name="message" class="form-control rounded-3 border fw-medium" rows="4" placeholder="Write your message to all enrolled students here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-gradient w-100 rounded-pill py-3 fw-bold">Send to All Students</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Instant client-side search filtering
    document.getElementById('activitySearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#activityTable tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // Filter table by status tab
    function filterTable(status, btnElement) {
        document.querySelectorAll('.filter-pill').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');

        let rows = document.querySelectorAll('#activityTable tbody tr');
        rows.forEach(row => {
            let rowStatus = row.getAttribute('data-status');
            if (status === 'all' || rowStatus === status || (status === 'Completed' && (rowStatus === 'Completed' || rowStatus === 'Excellent'))) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
</body>
</html>