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
    <title>Instructor Portal | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #f8fafc;
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #e0e7ff;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --card-radius: 24px;
            --border-color: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            
            --grad-1: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            --grad-2: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --grad-3: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --grad-4: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --grad-5: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
        }

        body {
            background-color: var(--bg-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            padding-bottom: 120px;
            font-size: 1rem;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, .brand-font {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.025em;
        }

        /* Ambient Background Glows */
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
            filter: blur(120px);
            opacity: 0.12;
            border-radius: 50%;
            animation: floatOrb 10s ease-in-out infinite alternate;
        }
        .c1 { width: 450px; height: 450px; background: #6366f1; top: -100px; left: -100px; }
        .c2 { width: 500px; height: 500px; background: #ec4899; bottom: -100px; right: -100px; animation-delay: -5s; }

        @keyframes floatOrb {
            0% { transform: translateY(0px) scale(1); }
            100% { transform: translateY(30px) scale(1.08); }
        }

        /* Glassmorphism Navigation */
        .navbar-teacher {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .brand-icon-box {
            background: var(--grad-1);
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .navbar-brand:hover .brand-icon-box {
            transform: rotate(10deg) scale(1.1);
        }

        /* Vibrant Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%);
            border-radius: var(--card-radius);
            border: 1px solid rgba(99, 102, 241, 0.2);
            box-shadow: 0 20px 50px -15px rgba(99, 102, 241, 0.12);
            position: relative;
            overflow: hidden;
            padding: 50px !important;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
        }
        .hero-banner:hover {
            transform: translateY(-3px);
            box-shadow: 0 25px 60px -15px rgba(99, 102, 241, 0.2);
        }
        .hero-banner::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 350px;
            height: 100%;
            background: radial-gradient(circle at right, rgba(168, 85, 247, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Modern Elevated Stat Cards with Hover Animations */
        .stat-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 28px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--grad-1);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: 0 20px 40px -10px var(--primary-glow);
            border-color: #a5b4fc;
        }
        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.15) rotate(8deg);
        }

        /* Dashboard Section Container */
        .dashboard-section {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 40px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
            margin-bottom: 35px;
            transition: box-shadow 0.3s ease;
        }
        .dashboard-section:hover {
            box-shadow: 0 15px 40px -8px rgba(0, 0, 0, 0.07);
        }

        /* Interactive Quick Action Cards with Vivid Gradients */
        .quick-action-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 32px 24px;
            text-align: center;
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            display: block;
            text-decoration: none;
            height: 100%;
            box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .quick-action-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--grad-1);
            opacity: 0;
            z-index: -1;
            transition: opacity 0.4s ease;
        }
        .quick-action-card:hover {
            color: #ffffff;
            border-color: transparent;
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.4);
        }
        .quick-action-card:hover::before {
            opacity: 1;
        }
        .quick-action-card i {
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.3s ease;
        }
        .quick-action-card:hover i {
            transform: scale(1.25) rotate(10deg);
            color: #ffffff !important;
        }

        /* Polished Table Styling */
        .table-custom th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-top: none;
            border-bottom: 2px solid var(--border-color);
            padding: 18px 24px;
        }
        .table-custom td {
            padding: 20px 24px;
            vertical-align: middle;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tbody tr {
            transition: all 0.25s ease;
        }
        .table-custom tbody tr:hover {
            background-color: #f8fafc;
            transform: scale(1.002);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        /* Search Bar & Pill Filters */
        .search-box {
            border-radius: 50rem;
            border: 1px solid var(--border-color);
            padding: 12px 20px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            background-color: #f8fafc;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }
        .search-box:focus {
            background-color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 5px var(--primary-glow);
            transform: scale(1.01);
        }

        .filter-pill {
            border-radius: 50rem;
            font-size: 0.9rem;
            padding: 8px 22px;
            font-weight: 700;
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-muted);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .filter-pill.active, .filter-pill:hover {
            background: var(--grad-1);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.35);
            transform: translateY(-2px);
        }

        /* Glowing Pulse Badge */
        .online-pulse {
            width: 10px;
            height: 10px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 2s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Modern Animated Buttons */
        .btn-gradient {
            background: var(--grad-1);
            color: #ffffff;
            border: none;
            border-radius: 50rem;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        .btn-gradient:hover {
            color: #ffffff;
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.5);
            filter: brightness(1.05);
        }
        .btn-gradient:active {
            transform: translateY(-1px) scale(1.01);
        }

        /* Modern Modals */
        .modal-content {
            border-radius: var(--card-radius);
            border: 1px solid var(--border-color);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.2);
            padding: 10px;
            animation: modalFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Progress Bar Animation */
        .progress-bar {
            transition: width 1s cubic-bezier(0.16, 1, 0.3, 1);
            background: var(--grad-1) !important;
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
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-dark fw-bold d-flex align-items-center gap-3 text-decoration-none" href="#">
            <div class="brand-icon-box">
                <i class="fa-solid fa-brain fs-4"></i>
            </div>
            <div>
                <span class="d-block lh-1 text-dark fw-extrabold" style="font-size: 1.4rem;">AutiLearn</span>
                <span class="text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 1.2px;">INSTRUCTOR PORTAL</span>
            </div>
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-sm-flex align-items-center gap-2 bg-white border px-3.5 py-2 rounded-pill shadow-sm">
                <span class="online-pulse"></span>
                <span class="fw-bold text-dark fs-6"><?php echo $onlineNowCount; ?> Online Now</span>
            </div>
            <div class="dropdown">
                <button class="btn btn-white border dropdown-toggle d-flex align-items-center gap-3 rounded-pill px-3.5 py-2 shadow-sm bg-white" type="button" data-bs-toggle="dropdown">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; background: var(--primary-light); color: var(--primary-hover); font-size: 1rem;">
                        <?php echo strtoupper(substr($teacherName, 0, 1)); ?>
                    </div>
                    <span class="fw-bold text-dark fs-6 d-none d-md-inline"><?php echo htmlspecialchars($teacherName); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border rounded-4 mt-3 p-2">
                    <li><h6 class="dropdown-header text-uppercase text-muted fw-bold" style="font-size: 0.75rem;">Signed in as Instructor</h6></li>
                    <li><a class="dropdown-item py-2 px-3 rounded-3 fw-bold text-danger mt-1 fs-6 transition-all" href="<?php echo htmlspecialchars($logoutUrl); ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    
    <!-- WELCOME HERO BANNER -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-banner d-md-flex align-items-center justify-content-between">
                <div class="mb-4 mb-md-0 z-1">
                    <span class="badge rounded-pill px-3.5 py-2 fw-bold fs-6 mb-3 shadow-sm" style="background-color: var(--primary-light); color: var(--primary-hover);">
                        <i class="fa-solid fa-shield-halved me-2"></i> Live Session Command Center
                    </span>
                    <h1 class="brand-font text-dark mb-2 fw-extrabold display-5">Welcome back, <?php echo htmlspecialchars($teacherName); ?>!</h1>
                    <p class="text-muted mb-0 fs-5 fw-medium" style="max-width: 700px;">Monitor real-time student audio lab sessions, speech accuracy performance metrics, and learning platform activities seamlessly.</p>
                </div>
                <div class="z-1">
                    <button class="btn btn-gradient px-4 py-3 fw-bold shadow-lg d-flex align-items-center gap-2 fs-6" data-bs-toggle="modal" data-bs-target="#broadcastModal">
                        <i class="fa-solid fa-bullhorn text-warning"></i> Send Announcement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- STATISTICS CARDS ROW -->
    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted fs-6 mb-2 fw-bold text-uppercase tracking-wider" style="letter-spacing: 0.05em;">Total Students</p>
                        <h2 class="brand-font text-dark mb-0 fw-extrabold display-5"><?php echo $totalStudents; ?></h2>
                    </div>
                    <div class="stat-icon shadow-sm" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb;">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted fs-6 mb-2 fw-bold text-uppercase tracking-wider" style="letter-spacing: 0.05em;">Active Today</p>
                        <h2 class="brand-font text-dark mb-0 fw-extrabold display-5"><?php echo $activeSessionsToday; ?></h2>
                    </div>
                    <div class="stat-icon shadow-sm" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4f46e5;">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted fs-6 mb-2 fw-bold text-uppercase tracking-wider" style="letter-spacing: 0.05em;">Avg. Accuracy</p>
                        <h2 class="brand-font text-dark mb-0 fw-extrabold display-5"><?php echo $avgSpeechAccuracy; ?></h2>
                    </div>
                    <div class="stat-icon shadow-sm" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706;">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted fs-6 mb-2 fw-bold text-uppercase tracking-wider" style="letter-spacing: 0.05em;">Pending Reviews</p>
                        <h2 class="brand-font text-dark mb-0 fw-extrabold display-5"><?php echo $pendingReviews; ?></h2>
                    </div>
                    <div class="stat-icon shadow-sm" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="row mb-5">
        <div class="col-12 mb-3">
            <h2 class="fw-extrabold text-dark brand-font fs-3">
                <i class="fa-solid fa-bolt me-2 text-warning"></i>Quick Management Actions
            </h2>
        </div>
        <div class="col-md-4 mb-4">
            <a href="students.php" class="quick-action-card">
                <i class="fa-solid fa-users-gear display-6 mb-3 d-block text-primary"></i>
                Manage Enrolled Students
            </a>
        </div>
        <div class="col-md-4 mb-4">
            <a href="speech_logs.php" class="quick-action-card">
                <i class="fa-solid fa-headphones-simple display-6 mb-3 d-block text-success"></i>
                Review Speech Audio Logs
            </a>
        </div>
        <div class="col-md-4 mb-4">
            <a href="reports.php" class="quick-action-card" data-bs-toggle="modal" data-bs-target="#reportsModal">
                <i class="fa-solid fa-file-arrow-down display-6 mb-3 d-block text-warning"></i>
                Export Progress Reports
            </a>
        </div>
    </div>

    <!-- RECENT STUDENT ACTIVITY TABLE WITH SEARCH & FILTER -->
    <div class="dashboard-section">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-4">
            <div>
                <h2 class="fw-extrabold text-dark mb-1 brand-font fs-3">
                    <i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i>Real-Time Student Activity Feed
                </h2>
                <p class="text-muted mb-0 fs-6 fw-medium">Live tracking module interactions and student audio precision scores.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <!-- Filter Tabs -->
                <div class="btn-group shadow-sm rounded-pill border p-1 bg-white" role="group">
                    <button type="button" class="filter-pill active border-0" onclick="filterTable('all', this)">All</button>
                    <button type="button" class="filter-pill border-0" onclick="filterTable('Completed', this)">Completed</button>
                    <button type="button" class="filter-pill border-0" onclick="filterTable('Needs Review', this)">Needs Review</button>
                </div>
                <!-- Search Box -->
                <div class="input-group shadow-sm" style="width: 260px;">
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
                            <td colspan="5" class="text-center py-5 text-muted fw-bold fs-6">
                                <i class="fa-solid fa-circle-info me-2 text-teal" style="color: var(--primary-color);"></i> No live student activity recorded in the database yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentStudents as $student): ?>
                            <tr data-status="<?php echo htmlspecialchars($student['status']); ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="position-relative">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 44px; height: 44px; font-size: 1rem; background: var(--primary-light); color: var(--primary-hover);">
                                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                            </div>
                                            <?php if (!empty($student['is_online'])): ?>
                                                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" title="Online Now"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark fs-6 d-block"><?php echo htmlspecialchars($student['name']); ?></span>
                                            <?php if (!empty($student['is_online'])): ?>
                                                <small class="text-success fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-circle" style="font-size: 5px;"></i> Active Now</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-dark fw-bold fs-6"><?php echo htmlspecialchars($student['module']); ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="progress flex-grow-1 bg-light border" style="height: 10px; width: 100px;">
                                            <div class="progress-bar rounded-pill" role="progressbar" style="width: <?php echo $student['accuracy']; ?>%;"></div>
                                        </div>
                                        <span class="fw-bold text-dark fs-6"><?php echo $student['accuracy']; ?>%</span>
                                    </div>
                                </td>
                                <td class="fw-semibold text-secondary fs-6"><?php echo htmlspecialchars($student['time']); ?></td>
                                <td>
                                    <?php if ($student['status'] === 'Excellent' || $student['status'] === 'Completed'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill fs-6">Completed</span>
                                    <?php elseif ($student['status'] === 'Good'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info fw-bold px-3 py-2 rounded-pill fs-6">Good</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-3 py-2 rounded-pill fs-6">Needs Review</span>
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
        <div class="modal-content shadow-lg p-3">
            <div class="modal-header border-bottom px-3 py-3">
                <h4 class="modal-title brand-font fw-bold text-dark"><i class="fa-solid fa-file-arrow-down me-2 text-warning"></i> Export Progress Report</h4>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form action="reports.php" method="GET">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-uppercase fs-6">Report Format</label>
                        <select name="format" class="form-select rounded-3 border fw-semibold py-2">
                            <option value="csv">CSV Spreadsheet</option>
                            <option value="pdf">PDF Document Summary</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-uppercase fs-6">Date Range</label>
                        <select name="range" class="form-select rounded-3 border fw-semibold py-2">
                            <option value="today">Today</option>
                            <option value="week" selected>Past 7 Days</option>
                            <option value="month">Past 30 Days</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-gradient w-100 rounded-pill py-3 mt-3 fw-bold shadow-sm"><i class="fa-solid fa-download me-2"></i>Download Report</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 2. Announcement Modal -->
<div class="modal fade" id="broadcastModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg p-3">
            <div class="modal-header border-bottom px-3 py-3">
                <h4 class="modal-title brand-font fw-bold text-dark"><i class="fa-solid fa-bullhorn me-2 text-warning"></i> Broadcast Announcement</h4>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form action="broadcast.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-uppercase fs-6">Message Title</label>
                        <input type="text" name="title" class="form-control rounded-3 border fw-semibold py-2" placeholder="e.g. Weekly Speech Challenge" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark text-uppercase fs-6">Announcement Details</label>
                        <textarea name="message" class="form-control rounded-3 border fw-medium" rows="4" placeholder="Write your message to all enrolled students here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-gradient w-100 rounded-pill py-3 fw-bold shadow-sm">Send to All Students</button>
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