<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}
if (!$pdo) {
    die("Database connection error. Please check includes/db.php");
}

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
$parentName = $_SESSION['full_name'] ?? 'Parent';
$logoutUrl  = defined('BASE_URL') ? BASE_URL . 'logout.php' : '../logout.php';
$profileUrl = defined('BASE_URL') ? BASE_URL . 'parent/profile.php' : 'profile.php';

$flashMessage = '';
$flashType    = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE NEW CHILD PROFILE
    if ($action === 'add_child') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $dob       = trim($_POST['date_of_birth'] ?? '');

        if (!empty($firstName) && !empty($lastName) && !empty($dob)) {
            $stmt = $pdo->prepare("INSERT INTO children (parent_id, first_name, last_name, date_of_birth) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$parentId, $firstName, $lastName, $dob])) {
                $newChildId = $pdo->lastInsertId();
                
                // Initialize default skill competencies for the new child
                $initSkills = $pdo->prepare("INSERT INTO skill_competency (child_id, skill_name, score) VALUES 
                    (?, 'Speech & Pronunciation', 60),
                    (?, 'Emotion Recognition', 70),
                    (?, 'Daily Life Routines', 65),
                    (?, 'Sensory Self-Regulation', 75)");
                $initSkills->execute([$newChildId, $newChildId, $newChildId, $newChildId]);

                $_SESSION['selected_child_id'] = $newChildId;
                header("Location: dashboard.php?msg=child_added");
                exit;
            }
        } else {
            $flashMessage = "Please complete all fields to create a child profile.";
            $flashType    = "danger";
        }
    }

    // 2. QUICK LOG ACTIVITY / SPEECH
    if ($action === 'log_activity') {
        $selectedChildId = (int)($_POST['child_id'] ?? 0);
        $activityType    = $_POST['activity_type'] ?? 'lesson';
        $title           = trim($_POST['title'] ?? 'Session Completed');
        $duration        = (int)($_POST['duration_minutes'] ?? 10);
        $stars           = (int)($_POST['stars_earned'] ?? 1);

        if ($selectedChildId > 0) {
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (child_id, activity_type, title, description, duration_minutes, icon_class, color_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $icon  = ($activityType === 'speech') ? 'fa-microphone-lines' : (($activityType === 'sensory') ? 'fa-spa' : 'fa-graduation-cap');
            $color = ($activityType === 'speech') ? '#16a34a' : (($activityType === 'sensory') ? '#0d9488' : '#2563eb');
            $desc  = "Logged " . $duration . " minute " . $activityType . " session.";

            $logStmt->execute([$selectedChildId, $activityType, $title, $desc, $duration, $icon, $color]);

            if ($activityType === 'speech' && !empty($_POST['target_word'])) {
                $targetWord = trim($_POST['target_word']);
                $heardWord  = trim($_POST['heard_word'] ?? $targetWord);
                $accuracy   = (int)($_POST['accuracy_score'] ?? 80);

                $speechStmt = $pdo->prepare("INSERT INTO speech_logs (child_id, target_word, heard_word, accuracy_score, stars_earned) VALUES (?, ?, ?, ?, ?)");
                $speechStmt->execute([$selectedChildId, $targetWord, $heardWord, $accuracy, $stars]);
            }

            header("Location: dashboard.php?msg=activity_logged");
            exit;
        }
    }
}

// Flash Notifications
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'child_added') {
        $flashMessage = "Child profile created and linked successfully!";
    } elseif ($_GET['msg'] === 'activity_logged') {
        $flashMessage = "Activity session logged successfully!";
    }
}

$childrenStmt = $pdo->prepare("SELECT id, first_name, last_name, date_of_birth FROM children WHERE parent_id = ? ORDER BY id ASC");
$childrenStmt->execute([$parentId]);
$childrenList = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);

// Determine active child ID
if (isset($_GET['child_id'])) {
    $activeChildId = (int)$_GET['child_id'];
    $_SESSION['selected_child_id'] = $activeChildId;
} elseif (isset($_SESSION['selected_child_id'])) {
    $activeChildId = (int)$_SESSION['selected_child_id'];
} else {
    $activeChildId = !empty($childrenList) ? (int)$childrenList[0]['id'] : null;
}

$activeChild = null;
foreach ($childrenList as $c) {
    if ((int)$c['id'] === $activeChildId) {
        $activeChild = $c;
        break;
    }
}
if (!$activeChild && !empty($childrenList)) {
    $activeChild   = $childrenList[0];
    $activeChildId = (int)$activeChild['id'];
}

$childName = $activeChild ? htmlspecialchars($activeChild['first_name'] . ' ' . $activeChild['last_name']) : "No Profile Linked";

// Calculate Age
$childAge = "N/A";
if ($activeChild && !empty($activeChild['date_of_birth'])) {
    $dob  = new DateTime($activeChild['date_of_birth']);
    $now  = new DateTime();
    $diff = $now->diff($dob);
    $childAge = $diff->y . " Years";
}

$completedLessons = 0;
$speechAccuracy   = "0%";
$calmSessions     = 0;
$weeklyStars      = 0;
$weeklyGoalPct    = 0;

if ($activeChildId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE child_id = ? AND activity_type = 'lesson'");
    $stmt->execute([$activeChildId]);
    $completedLessons = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT AVG(accuracy_score) FROM speech_logs WHERE child_id = ?");
    $stmt->execute([$activeChildId]);
    $avgAccuracy = $stmt->fetchColumn();
    $speechAccuracy = $avgAccuracy ? round($avgAccuracy) . "%" : "0%";

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE child_id = ? AND activity_type = 'sensory'");
    $stmt->execute([$activeChildId]);
    $calmSessions = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(stars_earned), 0) FROM speech_logs WHERE child_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$activeChildId]);
    $weeklyStars = (int)$stmt->fetchColumn();

    $weeklyGoalTarget = 50;
    $weeklyGoalPct = min(100, round(($weeklyStars / $weeklyGoalTarget) * 100));
}

$speechLogs = [];
$activityLogs = [];
if ($activeChildId) {
    $stmt = $pdo->prepare("SELECT target_word, heard_word, accuracy_score, stars_earned, created_at FROM speech_logs WHERE child_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$activeChildId]);
    $speechLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT title, description, created_at, icon_class, color_code FROM activity_logs WHERE child_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$activeChildId]);
    $activityLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    if ($difference < 60) return 'Just now';
    if ($difference < 3600) return floor($difference / 60) . ' mins ago';
    if ($difference < 86400) return floor($difference / 3600) . ' hours ago';
    if ($difference < 172800) return 'Yesterday';
    return date('M j, Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Analytics & Dashboard | AutiLearn AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
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
            padding-bottom: 60px;
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: all 0.25s ease;
        }
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06);
        }
        .icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .timeline-item {
            position: relative;
            padding-left: 32px;
            padding-bottom: 24px;
            border-left: 2px solid #e2e8f0;
        }
        .timeline-item:last-child {
            border-left-color: transparent;
            padding-bottom: 0;
        }
        .timeline-icon {
            position: absolute;
            left: -15px;
            top: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.85rem;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff; }
        }
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-parent sticky-top mb-4 no-print">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-primary d-flex align-items-center gap-2" href="#">
            <i class="fa-solid fa-chart-line text-primary fs-2"></i> AutiLearn <span class="fs-5 text-secondary">Parent Portal</span>
        </a>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if (!empty($childrenList)): ?>
                <div class="dropdown">
                    <button class="btn btn-light border rounded-pill px-3 py-1 dropdown-toggle fw-semibold text-dark small" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-child-reaching text-primary me-1"></i> <?php echo $childName; ?> (<?php echo $childAge; ?>)
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><h6 class="dropdown-header">Switch Child Profile</h6></li>
                        <?php foreach ($childrenList as $item): ?>
                            <li>
                                <a class="dropdown-item small <?php echo ($item['id'] == $activeChildId) ? 'active fw-bold' : ''; ?>" 
                                   href="dashboard.php?child_id=<?php echo $item['id']; ?>">
                                    <i class="fa-solid fa-user me-2"></i><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-primary small fw-semibold" href="#" data-bs-toggle="modal" data-bs-target="#addChildModal">
                                <i class="fa-solid fa-plus me-2"></i>Add New Child
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($activeChildId): ?>
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#logActivityModal">
                    <i class="fa-solid fa-plus-circle me-1"></i> Log Activity
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
    <?php if (!empty($flashMessage)): ?>
        <div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show rounded-4 mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $flashMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- WELCOME HEADER -->
    <div class="bg-primary text-white rounded-4 p-4 p-md-5 mb-4 shadow-sm" style="background: linear-gradient(135deg, #1e3a8a, #2563eb) !important;">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold mb-2">Live Insights Dashboard</span>
                <h2 class="brand-font fw-bold fs-1 mb-2">Welcome back, <?php echo htmlspecialchars($parentName); ?>!</h2>
                <p class="opacity-90 fs-5 mb-0">
                    <?php if ($activeChildId): ?>
                        Tracking how <strong><?php echo $childName; ?></strong> is progressing in speech clarity, lessons, and sensory regulation.
                    <?php else: ?>
                        Configure a child profile below to unlock real-time learning metrics.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($activeChildId): ?>
            <div class="col-md-4 text-md-end mt-3 mt-md-0 no-print">
                <div class="bg-white bg-opacity-10 rounded-4 p-3 d-inline-block text-start border border-white border-opacity-25">
                    <div class="small text-white-50 text-uppercase fw-semibold">Weekly Goal Progress</div>
                    <div class="d-flex align-items-center gap-2 my-1">
                        <div class="progress flex-grow-1" style="height: 10px; min-width: 120px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $weeklyGoalPct; ?>%;"></div>
                        </div>
                        <span class="fw-bold fs-5 text-warning"><?php echo $weeklyGoalPct; ?>%</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- METRICS GRID -->
    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
        <div class="col">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary fw-semibold small">Lessons Completed</span>
                    <div class="icon-circle bg-primary-subtle text-primary"><i class="fa-solid fa-graduation-cap"></i></div>
                </div>
                <h2 class="brand-font fw-bold text-dark mb-1"><?php echo $completedLessons; ?></h2>
                <span class="text-success small fw-semibold"><i class="fa-solid fa-check me-1"></i>Verified Total</span>
            </div>
        </div>
        <div class="col">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary fw-semibold small">Speech Clarity Score</span>
                    <div class="icon-circle bg-success-subtle text-success"><i class="fa-solid fa-microphone-lines"></i></div>
                </div>
                <h2 class="brand-font fw-bold text-dark mb-1"><?php echo $speechAccuracy; ?></h2>
                <span class="text-success small fw-semibold"><i class="fa-solid fa-chart-line me-1"></i>Avg accuracy</span>
            </div>
        </div>
        <div class="col">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary fw-semibold small">Sensory Calm Sessions</span>
                    <div class="icon-circle bg-info-subtle text-info"><i class="fa-solid fa-spa"></i></div>
                </div>
                <h2 class="brand-font fw-bold text-dark mb-1"><?php echo $calmSessions; ?></h2>
                <span class="text-muted small fw-semibold">Completed sessions</span>
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
                                <tr><td colspan="5" class="text-center text-muted py-4">No speech practice logs recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($speechLogs as $log): 
                                    $acc = (int)$log['accuracy_score'];
                                    $badgeClass = ($acc >= 85) ? 'bg-success' : (($acc >= 70) ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($log['target_word']); ?></td>
                                        <td class="fst-italic text-secondary">"<?php echo htmlspecialchars($log['heard_word']); ?>"</td>
                                        <td><span class="badge <?php echo $badgeClass; ?> px-2 py-1"><?php echo $acc; ?>%</span></td>
                                        <td class="text-warning">
                                            <?php for($i=0; $i < (int)$log['stars_earned']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                                        </td>
                                        <td class="small text-muted"><?php echo timeAgo($log['created_at']); ?></td>
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
                <h4 class="brand-font fw-bold text-dark mb-4"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Recent Activity</h4>
                <div class="ps-2">
                    <?php if (empty($activityLogs)): ?>
                        <p class="text-center text-muted py-4">No recent activity logged.</p>
                    <?php else: ?>
                        <?php foreach ($activityLogs as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon" style="background-color: <?php echo htmlspecialchars($activity['color_code']); ?>;">
                                    <i class="fa-solid <?php echo htmlspecialchars($activity['icon_class']); ?>"></i>
                                </div>
                                <div class="fw-bold text-dark small mb-1"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <p class="small text-muted mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <span class="small text-secondary fw-semibold" style="font-size: 0.75rem;"><?php echo timeAgo($activity['created_at']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD CHILD MODAL -->
<div class="modal fade" id="addChildModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title brand-font fw-bold"><i class="fa-solid fa-child-reaching me-2"></i>Link New Child Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="dashboard.php">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add_child">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">First Name</label>
                            <input type="text" name="first_name" class="form-control rounded-3" placeholder="e.g. Alex" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Last Name</label>
                            <input type="text" name="last_name" class="form-control rounded-3" placeholder="e.g. Smith" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control rounded-3" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="fa-solid fa-save me-1"></i> Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- LOG ACTIVITY MODAL -->
<?php if ($activeChildId): ?>
<div class="modal fade" id="logActivityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title brand-font fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>Log Practice Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="dashboard.php">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="log_activity">
                    <input type="hidden" name="child_id" value="<?php echo $activeChildId; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Activity Type</label>
                        <select name="activity_type" class="form-select rounded-3">
                            <option value="lesson">Lesson / Module</option>
                            <option value="speech">Speech Practice</option>
                            <option value="sensory">Sensory Relaxation</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Title / Topic</label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Emotion Matching Game" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Duration (Mins)</label>
                            <input type="number" name="duration_minutes" class="form-control rounded-3" value="10" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Stars Earned</label>
                            <input type="number" name="stars_earned" class="form-control rounded-3" value="1" min="1" max="5" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4"><i class="fa-solid fa-check me-1"></i> Log Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>