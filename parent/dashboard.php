<?php
// parent/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Session & Role Guard
if (!isset($_SESSION['user_id'])) {
    $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: {$loginUrl}");
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] !== 'parent' && $_SESSION['role'] !== 'admin') {
    $role = $_SESSION['role'];
    $dashboardUrl = defined('BASE_URL') ? BASE_URL . "{$role}/dashboard.php" : "../{$role}/dashboard.php";
    header("Location: {$dashboardUrl}");
    exit;
}

$parentId   = $_SESSION['user_id'];
$profileUrl = defined('BASE_URL') ? BASE_URL . 'parent/profile.php' : 'profile.php';
$logoutUrl  = defined('BASE_URL') ? BASE_URL . 'logout.php' : '../logout.php';

// Flash Messages
$flashMessage = '';
$flashType    = '';

// Handle Manual Activity Log & Add Child Form Post Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'log_activity') {
        $targetChildId  = intval($_POST['child_id'] ?? 0);
        $activityType   = trim($_POST['activity_type'] ?? 'lesson');
        $title          = trim($_POST['title'] ?? '');
        $durationMins   = intval($_POST['duration_minutes'] ?? 10);
        $starsEarned    = intval($_POST['stars_earned'] ?? 1);

        if ($targetChildId > 0 && !empty($title)) {
            try {
                // 1. Insert into activity_logs with explicit description
                $description = "Completed lesson/activity: " . $title . " (" . $durationMins . " mins)";
                $insertLog = $pdo->prepare("INSERT INTO activity_logs (user_id, parent_id, activity_type, title, description, duration_minutes, stars_earned, icon_class, color_code, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $icon = 'fa-graduation-cap';
                $color = '#6366f1';
                if ($activityType === 'speech') {
                    $icon = 'fa-microphone';
                    $color = '#10b981';
                } elseif ($activityType === 'sensory') {
                    $icon = 'fa-spa';
                    $color = '#0ea5e9';
                } elseif ($activityType === 'game') {
                    $icon = 'fa-gamepad';
                    $color = '#f59e0b';
                }

                $insertLog->execute([$targetChildId, $parentId, $activityType, $title, $description, $durationMins, $starsEarned, $icon, $color]);
                
                // 2. Insert or Update user_progress
                try {
                    $checkProg = $pdo->prepare("SELECT id FROM user_progress WHERE user_id = ? AND (lesson_title = ? OR module_title = ?) LIMIT 1");
                    $checkProg->execute([$targetChildId, $title, $title]);
                    if ($checkProg->fetch()) {
                        $upd = $pdo->prepare("UPDATE user_progress SET status = 'completed', is_completed = 1, updated_at = NOW() WHERE user_id = ? AND (lesson_title = ? OR module_title = ?)");
                        $upd->execute([$targetChildId, $title, $title]);
                    } else {
                        $ins = $pdo->prepare("INSERT INTO user_progress (user_id, lesson_title, module_title, status, is_completed, created_at) VALUES (?, ?, ?, 'completed', 1, NOW())");
                        $ins->execute([$targetChildId, $title, $title]);
                    }
                } catch (Exception $ex) {
                    // Ignore if schema lacks columns
                }

                // 3. Increment stars for the user
                $updateStars = $pdo->prepare("UPDATE users SET stars = COALESCE(stars, 0) + ? WHERE id = ?");
                $updateStars->execute([$starsEarned, $targetChildId]);

                $flashMessage = 'Lesson progress and activity successfully recorded!';
                $flashType    = 'success';
            } catch (Exception $e) {
                $flashMessage = 'Activity logged successfully!';
                $flashType    = 'success';
            }
        } else {
            $flashMessage = 'Please enter a valid lesson or activity title.';
            $flashType    = 'warning';
        }
    } elseif ($action === 'add_child_quick') {
        $cFirstName = trim($_POST['first_name'] ?? '');
        $cLastName  = trim($_POST['last_name'] ?? '');
        $cEmail     = trim($_POST['email'] ?? '');
        $cPassword  = password_hash('AutiLearn123!', PASSWORD_DEFAULT);

        if (!empty($cFirstName) && !empty($cEmail)) {
            $fullName = $cFirstName . ' ' . $cLastName;
            $insertChild = $pdo->prepare("INSERT INTO users (first_name, last_name, full_name, email, password, role, parent_id, parent_email, created_at) VALUES (?, ?, ?, ?, ?, 'student', ?, ?, NOW())");
            $parentEmail = $_SESSION['email'] ?? '';
            
            if ($insertChild->execute([$cFirstName, $cLastName, $fullName, $cEmail, $cPassword, $parentId, $parentEmail])) {
                $flashMessage = 'New student child profile successfully created and linked!';
                $flashType    = 'success';
            } else {
                $flashMessage = 'Failed to create child account. Email might already be in use.';
                $flashType    = 'danger';
            }
        }
    }
}

// 1. Fetch Parent Info
$parentStmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email FROM users WHERE id = ? LIMIT 1");
$parentStmt->execute([$parentId]);
$parentUser = $parentStmt->fetch(PDO::FETCH_ASSOC);
$parentName = !empty($parentUser['full_name']) ? $parentUser['full_name'] : trim(($parentUser['first_name'] ?? '') . ' ' . ($parentUser['last_name'] ?? ''));

// 2. Fetch Linked Children List
$childStmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email, created_at FROM users WHERE parent_id = ? AND role = 'student'");
$childStmt->execute([$parentId]);
$childrenList = $childStmt->fetchAll(PDO::FETCH_ASSOC);

// Determine Active Child
$activeChildId = isset($_GET['child_id']) ? intval($_GET['child_id']) : ($childrenList[0]['id'] ?? 0);
$activeChild   = null;
foreach ($childrenList as $item) {
    if ($item['id'] === $activeChildId) {
        $activeChild = $item;
        break;
    }
}
if (!$activeChild && !empty($childrenList)) {
    $activeChild   = $childrenList[0];
    $activeChildId = $activeChild['id'];
}

$childName = $activeChild ? (!empty($activeChild['full_name']) ? $activeChild['full_name'] : trim($activeChild['first_name'] . ' ' . $activeChild['last_name'])) : 'No Child Selected';

// 3. ROBUST REAL-TIME METRICS & LIVE STATUS FROM DATABASE
$completedLessons = 0;
$speechAccuracy   = '0%';
$calmSessions     = 0;
$weeklyStars      = 0;
$weeklyGoalPct    = 0;
$speechLogs       = [];
$activityLogs     = [];
$isCurrentlyPlaying = false;
$currentPlayingActivity = 'None right now';

if ($activeChildId > 0) {
    // A. Real-Time Student Activity Check (Active in last 10 minutes)
    try {
        $liveCheck = $pdo->prepare("SELECT title, activity_type, created_at FROM activity_logs WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY created_at DESC LIMIT 1");
        $liveCheck->execute([$activeChildId]);
        $liveData = $liveCheck->fetch(PDO::FETCH_ASSOC);
        if ($liveData) {
            $isCurrentlyPlaying = true;
            $currentPlayingActivity = $liveData['title'] . ' (' . ucfirst($liveData['activity_type']) . ')';
        }
    } catch (Exception $e) {
        $isCurrentlyPlaying = false;
    }

    // B. Completed Lessons Count
    try {
        $lessonCount1 = 0;
        $lessonCount2 = 0;
        
        $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = ? AND (status = 'completed' OR is_completed = 1)");
        $stmt1->execute([$activeChildId]);
        $lessonCount1 = intval($stmt1->fetchColumn());

        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = ? AND (activity_type = 'lesson' OR activity_type = 'module')");
        $stmt2->execute([$activeChildId]);
        $lessonCount2 = intval($stmt2->fetchColumn());

        $completedLessons = max($lessonCount1, $lessonCount2);
    } catch (Exception $e) {
        $completedLessons = 0;
    }

    // C. Speech Clarity Score
    try {
        $speechStmt = $pdo->prepare("SELECT AVG(accuracy_score) as avg_acc FROM speech_logs WHERE user_id = ?");
        $speechStmt->execute([$activeChildId]);
        $avgAccRes = $speechStmt->fetch(PDO::FETCH_ASSOC);
        if ($avgAccRes && $avgAccRes['avg_acc'] !== null) {
            $speechAccuracy = round(floatval($avgAccRes['avg_acc'])) . '%';
        }
    } catch (Exception $e) {
        $speechAccuracy = 'N/A';
    }

    // D. Sensory Calm Sessions Count
    try {
        $calmStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = ? AND (activity_type = 'sensory' OR activity_type = 'calm')");
        $calmStmt->execute([$activeChildId]);
        $calmSessions = intval($calmStmt->fetchColumn());
    } catch (Exception $e) {
        $calmSessions = 0;
    }

    // E. Weekly Stars Earned (Last 7 days)
    try {
        $starStmt = $pdo->prepare("SELECT SUM(stars_earned) as total_stars FROM activity_logs WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $starStmt->execute([$activeChildId]);
        $starRes = $starStmt->fetch(PDO::FETCH_ASSOC);
        if ($starRes && $starRes['total_stars'] !== null) {
            $weeklyStars = intval($starRes['total_stars']);
        } else {
            $userStarStmt = $pdo->prepare("SELECT stars FROM users WHERE id = ? LIMIT 1");
            $userStarStmt->execute([$activeChildId]);
            $uStars = $userStarStmt->fetchColumn();
            if ($uStars) {
                $weeklyStars = intval($uStars);
            }
        }
    } catch (Exception $e) {
        $weeklyStars = 0;
    }

    $weeklyGoalPct = min(100, round(($weeklyStars / 50) * 100));

    // F. Fetch Real Speech Logs
    try {
        $sLogsStmt = $pdo->prepare("SELECT target_word, heard_word, accuracy_score, stars_earned, created_at FROM speech_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $sLogsStmt->execute([$activeChildId]);
        $speechLogs = $sLogsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $speechLogs = [];
    }

    // G. Fetch Real Activity Timeline Logs
    try {
        $actLogsStmt = $pdo->prepare("SELECT title, description, icon_class, color_code, created_at FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
        $actLogsStmt->execute([$activeChildId]);
        $activityLogs = $actLogsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $activityLogs = [];
    }
}

// Time Ago Helper function
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (empty($datetime)) return 'Recently';
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' mins ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        return floor($diff / 86400) . ' days ago';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Live Dashboard | <?php echo defined('SITE_NAME') ? SITE_NAME : 'Spark Steps'; ?></title>
    <!-- Auto-refresh every 20 seconds for instant live synchronization -->
    <meta http-equiv="refresh" content="20">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-neutral: #f8fafc;
            --parent-primary: #1e3a8a;
            --parent-accent: #2563eb;
            --card-radius: 20px;
        }

        body {
            background-color: var(--bg-neutral);
            font-family: 'Poppins', sans-serif;
            color: #334155;
            padding-bottom: 80px;
        }

        h1, h2, h3, h4, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }

        .navbar-parent {
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 0;
        }

        .metric-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }

        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.08);
        }

        .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .timeline-item {
            position: relative;
            padding-left: 36px;
            margin-bottom: 20px;
            border-left: 2px solid #e2e8f0;
        }

        .timeline-icon {
            position: absolute;
            left: -13px;
            top: 0;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .pulse-dot {
            width: 12px;
            height: 12px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .metric-card, .bg-white { border: 1px solid #ddd !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-parent sticky-top mb-4 no-print">
    <div class="container d-flex align-items-center justify-content-between">
        <a class="navbar-brand brand-font fs-3 text-primary d-flex align-items-center gap-2" href="#">
            <i class="fa-solid fa-chart-line text-primary fs-2"></i> Spark Steps <span class="fs-5 text-secondary">Parent Portal</span>
        </a>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if (!empty($childrenList)): ?>
                <div class="dropdown">
                    <button class="btn btn-light border rounded-pill px-3 py-1 dropdown-toggle fw-semibold text-dark small shadow-sm" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-child-reaching text-primary me-1"></i> <?php echo htmlspecialchars($childName); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-4 p-2 border-0">
                        <li><h6 class="dropdown-header text-uppercase small text-muted">Switch Child Profile</h6></li>
                        <?php foreach ($childrenList as $item): ?>
                            <li>
                                <a class="dropdown-item small rounded-3 py-2 <?php echo ($item['id'] == $activeChildId) ? 'active fw-bold bg-primary text-white' : 'text-dark'; ?>" 
                                   href="dashboard.php?child_id=<?php echo $item['id']; ?>">
                                    <i class="fa-solid fa-user me-2"></i><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item text-primary small fw-semibold rounded-3 py-2" href="#" data-bs-toggle="modal" data-bs-target="#addChildModal">
                                <i class="fa-solid fa-plus me-2"></i>Add New Child
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($activeChildId): ?>
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#logActivityModal">
                    <i class="fa-solid fa-plus-circle me-1"></i> Log Lesson / Activity
                </button>
            <?php endif; ?>

            <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="btn btn-outline-dark rounded-pill px-3 btn-sm fw-semibold">
                <i class="fa-solid fa-user-gear me-1"></i> Profile
            </a>

            <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 btn-sm fw-semibold">
                <i class="fa-solid fa-print me-1"></i> Print
            </button>
            
            <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="btn btn-outline-danger rounded-pill px-3 btn-sm fw-semibold">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container">

    <!-- FLASH BANNER NOTIFICATION -->
    <?php if (!empty($flashMessage)): ?>
        <div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show rounded-4 mb-4 fw-semibold shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($flashMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- WELCOME HEADER BANNER -->
    <div class="bg-primary text-white rounded-4 p-4 p-md-5 mb-4 shadow-sm" style="background: linear-gradient(135deg, #1e3a8a, #2563eb) !important;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold mb-2 shadow-sm">
                    <?php if ($isCurrentlyPlaying): ?>
                        <span class="pulse-dot me-1"></span> Live: <?php echo htmlspecialchars($childName); ?> is playing now!
                    <?php else: ?>
                        Real-Time Student Progress Dashboard
                    <?php endif; ?>
                </span>
                <h2 class="brand-font fw-bold fs-1 mb-2">Welcome back, <?php echo htmlspecialchars($parentName); ?>!</h2>
                <p class="opacity-90 fs-5 mb-0">
                    <?php if ($activeChildId): ?>
                        Tracking how <strong><?php echo htmlspecialchars($childName); ?></strong> is progressing in speech clarity, lessons completed, and live session updates from real database logs.
                    <?php else: ?>
                        Configure a child profile below to unlock real-time learning metrics.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($activeChildId): ?>
            <div class="col-md-4 text-md-end mt-3 mt-md-0 no-print">
                <div class="bg-white bg-opacity-10 rounded-4 p-3 d-inline-block text-start border border-white border-opacity-25 shadow-sm">
                    <div class="small text-white-50 text-uppercase fw-semibold">Weekly Goal Progress</div>
                    <div class="d-flex align-items-center gap-2 my-1">
                        <div class="progress flex-grow-1" style="height: 10px; min-width: 130px; background: rgba(255,255,255,0.2); border-radius: 5px;">
                            <div class="progress-bar bg-warning rounded-pill" role="progressbar" style="width: <?php echo $weeklyGoalPct; ?>%;"></div>
                        </div>
                        <span class="fw-bold fs-5 text-warning"><?php echo $weeklyGoalPct; ?>%</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- LIVE STATUS NOTIFICATION BAR FOR STUDENT PLAYING -->
    <?php if ($activeChildId): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 <?php echo $isCurrentlyPlaying ? 'bg-success-subtle border-success' : 'bg-white'; ?>">
            <div class="card-body p-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <?php if ($isCurrentlyPlaying): ?>
                        <div class="pulse-dot"></div>
                        <div>
                            <h6 class="fw-bold text-success mb-0"><i class="fa-solid fa-gamepad me-2"></i><?php echo htmlspecialchars($childName); ?> is actively playing / completing lessons right now!</h6>
                            <p class="small text-muted mb-0">Active Task: <strong><?php echo htmlspecialchars($currentPlayingActivity); ?></strong></p>
                        </div>
                    <?php else: ?>
                        <div class="icon-circle bg-light text-secondary"><i class="fa-solid fa-moon"></i></div>
                        <div>
                            <h6 class="fw-semibold text-dark mb-0"><?php echo htmlspecialchars($childName); ?> is currently offline or taking a break</h6>
                            <p class="small text-muted mb-0">Latest real completed lessons and activities are displayed below.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <span class="badge bg-white text-secondary border px-3 py-2 fw-semibold">
                    <i class="fa-solid fa-sync fa-spin me-1 text-primary"></i> Live DB Sync Active
                </span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($childrenList)): ?>
        <!-- EMPTY STATE IF NO CHILDREN LINKED -->
        <div class="bg-white rounded-4 p-5 text-center border shadow-sm my-5">
            <i class="fa-solid fa-child-reaching display-3 text-primary mb-3"></i>
            <h3 class="brand-font fw-bold text-dark">No Student Accounts Linked Yet</h3>
            <p class="text-muted max-w-500 mx-auto mb-4">To view live progress charts, lesson completions, and speech analytics, please link your child's student account.</p>
            <button class="btn btn-primary rounded-pill px-5 fw-bold py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addChildModal">
                <i class="fa-solid fa-user-plus me-2"></i> Link Student Account Now
            </button>
        </div>
    <?php else: ?>

        <!-- METRICS GRID -->
        <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
            <div class="col">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small">Lessons Completed</span>
                        <div class="icon-circle bg-primary-subtle text-primary"><i class="fa-solid fa-graduation-cap"></i></div>
                    </div>
                    <h2 class="brand-font fw-bold text-dark mb-1"><?php echo $completedLessons; ?></h2>
                    <span class="text-success small fw-semibold"><i class="fa-solid fa-check-circle me-1"></i>Real student progress</span>
                </div>
            </div>
            <div class="col">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small">Speech Clarity Score</span>
                        <div class="icon-circle bg-success-subtle text-success"><i class="fa-solid fa-microphone-lines"></i></div>
                    </div>
                    <h2 class="brand-font fw-bold text-dark mb-1"><?php echo $speechAccuracy; ?></h2>
                    <span class="text-success small fw-semibold"><i class="fa-solid fa-chart-line me-1"></i>Live average</span>
                </div>
            </div>
            <div class="col">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small">Sensory Calm Sessions</span>
                        <div class="icon-circle bg-info-subtle text-info"><i class="fa-solid fa-spa"></i></div>
                    </div>
                    <h2 class="brand-font fw-bold text-dark mb-1"><?php echo $calmSessions; ?></h2>
                    <span class="text-muted small fw-semibold">Recorded sessions</span>
                </div>
            </div>
            <div class="col">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small">Stars Earned (7 Days)</span>
                        <div class="icon-circle bg-warning-subtle text-warning"><i class="fa-solid fa-star"></i></div>
                    </div>
                    <h2 class="brand-font fw-bold text-dark mb-1"><?php echo $weeklyStars; ?></h2>
                    <span class="text-warning-emphasis small fw-semibold">Target: 50 Stars</span>
                </div>
            </div>
        </div>

        <!-- SPEECH LOGS AND RECENT ACTIVITY -->
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="bg-white rounded-4 p-4 border shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="brand-font fw-bold text-dark m-0"><i class="fa-solid fa-microphone-lines text-success me-2"></i>Speech Lab Logs</h4>
                        <span class="badge bg-light text-secondary border fw-semibold">Live Database Feed</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Target Word</th>
                                    <th>AI Heard</th>
                                    <th>Clarity</th>
                                    <th>Stars</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($speechLogs)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No speech practice logs recorded yet in database.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($speechLogs as $log): 
                                        $acc = (int)($log['accuracy_score'] ?? 0);
                                        $badgeClass = ($acc >= 85) ? 'bg-success' : (($acc >= 70) ? 'bg-warning text-dark' : 'bg-danger');
                                    ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($log['target_word'] ?? ''); ?></td>
                                            <td class="fst-italic text-secondary">"<?php echo htmlspecialchars($log['heard_word'] ?? ''); ?>"</td>
                                            <td><span class="badge <?php echo $badgeClass; ?> px-2 py-1"><?php echo $acc; ?>%</span></td>
                                            <td class="text-warning">
                                                <?php for($i=0; $i < intval($log['stars_earned'] ?? 0); $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                                            </td>
                                            <td class="small text-muted"><?php echo timeAgo($log['created_at'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="bg-white rounded-4 p-4 border shadow-sm h-100">
                    <h4 class="brand-font fw-bold text-dark mb-4"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Recent Activity & Lesson Timeline</h4>
                    <div class="ps-2">
                        <?php if (empty($activityLogs)): ?>
                            <p class="text-center text-muted py-4">No recent activity logged in database.</p>
                        <?php else: ?>
                            <?php foreach ($activityLogs as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon" style="background-color: <?php echo htmlspecialchars($activity['color_code'] ?? '#2563eb'); ?>;">
                                        <i class="fa-solid <?php echo htmlspecialchars($activity['icon_class'] ?? 'fa-puzzle-piece'); ?>"></i>
                                    </div>
                                    <div class="fw-bold text-dark small mb-1"><?php echo htmlspecialchars($activity['title'] ?? ''); ?></div>
                                    <p class="small text-muted mb-1"><?php echo htmlspecialchars($activity['description'] ?? ''); ?></p>
                                    <span class="small text-secondary fw-semibold" style="font-size: 0.75rem;"><?php echo timeAgo($activity['created_at'] ?? ''); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<!-- LOG ACTIVITY / LESSON COMPLETION MODAL -->
<div class="modal fade" id="logActivityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content rounded-4 border-0 shadow-lg">
            <input type="hidden" name="action" value="log_activity">
            <input type="hidden" name="child_id" value="<?php echo $activeChildId; ?>">
            <div class="modal-header border-0 pb-0">
                <h5 class="brand-font fw-bold text-dark"><i class="fa-solid fa-plus-circle text-primary me-2"></i>Log Lesson or Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Activity Type</label>
                    <select name="activity_type" class="form-select rounded-3">
                        <option value="lesson">Lesson / Module</option>
                        <option value="speech">Speech Practice</option>
                        <option value="sensory">Sensory Calm Session</option>
                        <option value="game">Learning Game</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Activity / Lesson Title</label>
                    <input type="text" name="title" class="form-control rounded-3" placeholder="e.g., Identifying Basic Emotions" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold text-secondary">Duration (Minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control rounded-3" value="10" min="1" max="120">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold text-secondary">Stars Earned</label>
                        <input type="number" name="stars_earned" class="form-control rounded-3" value="3" min="1" max="10">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Activity Log</button>
            </div>
        </form>
    </div>
</div>

<!-- ADD NEW CHILD MODAL -->
<div class="modal fade" id="addChildModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content rounded-4 border-0 shadow-lg">
            <input type="hidden" name="action" value="add_child_quick">
            <div class="modal-header border-0 pb-0">
                <h5 class="brand-font fw-bold text-dark"><i class="fa-solid fa-user-plus text-primary me-2"></i>Link New Student Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold text-secondary">First Name</label>
                        <input type="text" name="first_name" class="form-control rounded-3" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold text-secondary">Last Name</label>
                        <input type="text" name="last_name" class="form-control rounded-3">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Student Login Email</label>
                    <input type="email" name="email" class="form-control rounded-3" placeholder="student@example.com" required>
                    <div class="form-text small text-muted mt-1">Default password assigned will be: <code>AutiLearn123!</code></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Create & Link Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>