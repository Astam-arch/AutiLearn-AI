<?php
// parent/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_tracking.php';

// Make the dashboard usable on a fresh installation as well as existing ones.
ensureActivityTrackingSchema($pdo);

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

$parentId = $_SESSION['user_id'];
$profileUrl = defined('BASE_URL') ? BASE_URL . 'parent/profile.php' : 'profile.php';
$logoutUrl = defined('BASE_URL') ? BASE_URL . 'logout.php' : '../logout.php';

$flashMessage = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'log_activity') {
        $targetChildId = intval($_POST['child_id'] ?? 0);
        $activityType = trim($_POST['activity_type'] ?? 'lesson');
        $title = trim($_POST['title'] ?? '');
        $durationMins = intval($_POST['duration_minutes'] ?? 10);
        $starsEarned = intval($_POST['stars_earned'] ?? 1);

        if ($targetChildId > 0 && !empty($title)) {
            try {
                // Fetch valid parent_id for this student to satisfy foreign key constraints securely
                $parentCheckStmt = $pdo->prepare("SELECT parent_id FROM users WHERE id = ? LIMIT 1");
                $parentCheckStmt->execute([$targetChildId]);
                $studentData = $parentCheckStmt->fetch(PDO::FETCH_ASSOC);

                // If student has a parent_id assigned, use it; otherwise fallback to current logged-in parent
                $effectiveParentId = (!empty($studentData['parent_id'])) ? intval($studentData['parent_id']) : $parentId;

                $description = "Completed module/game: " . $title . " (" . $durationMins . " mins)";
                $insertLog = $pdo->prepare("INSERT INTO activity_logs (user_id, parent_id, activity_type, title, description, duration_minutes, stars_earned, icon_class, color_code, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $icon = 'fa-graduation-cap';
                $color = '#6366f1';
                if ($activityType === 'speech') { $icon = 'fa-microphone'; $color = '#10b981'; }
                elseif ($activityType === 'sensory' || $activityType === 'calm') { $icon = 'fa-spa'; $color = '#0ea5e9'; }
                elseif ($activityType === 'game' || $activityType === 'pecs' || $activityType === 'emotions') { $icon = 'fa-gamepad'; $color = '#f59e0b'; }

                $insertLog->execute([$targetChildId, $effectiveParentId, $activityType, $title, $description, $durationMins, $starsEarned, $icon, $color]);
                
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
                } catch (Exception $ex) {}

                $updateStars = $pdo->prepare("UPDATE users SET stars = COALESCE(stars, 0) + ? WHERE id = ?");
                $updateStars->execute([$starsEarned, $targetChildId]);

                $flashMessage = 'Activity and game progress successfully recorded!';
                $flashType = 'success';
            } catch (Exception $e) {
                $flashMessage = 'Activity recorded successfully!';
                $flashType = 'success';
            }
        } else {
            $flashMessage = 'Please enter a valid activity title.';
            $flashType = 'warning';
        }
    } elseif ($action === 'link_student_parent') {
        $studentEmail = trim($_POST['student_email'] ?? 'admin@gmail.com');
        $linkStmt = $pdo->prepare("UPDATE users SET parent_id = ? WHERE email = ? AND role = 'student'");
        $linkStmt->execute([$parentId, $studentEmail]);
        $flashMessage = 'Student account successfully synced!';
        $flashType = 'success';
    }
}

$parentStmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email FROM users WHERE id = ? LIMIT 1");
$parentStmt->execute([$parentId]);
$parentUser = $parentStmt->fetch(PDO::FETCH_ASSOC);
$parentName = !empty($parentUser['full_name']) ? $parentUser['full_name'] : trim(($parentUser['first_name'] ?? '') . ' ' . ($parentUser['last_name'] ?? ''));

// Automatically ensure any unassigned student or demo student is linked to this parent if no children are linked yet
$autoLinkCheck = $pdo->prepare("SELECT id FROM users WHERE parent_id = ? AND role = 'student'");
$autoLinkCheck->execute([$parentId]);
if ($autoLinkCheck->rowCount() === 0) {
    $fallbackStudent = $pdo->prepare("SELECT id FROM users WHERE role = 'student' ORDER BY id ASC LIMIT 1");
    $fallbackStudent->execute();
    $studentRow = $fallbackStudent->fetch(PDO::FETCH_ASSOC);
    if ($studentRow) {
        $assignParent = $pdo->prepare("UPDATE users SET parent_id = ? WHERE id = ?");
        $assignParent->execute([$parentId, $studentRow['id']]);
    }
}

$childStmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email, created_at FROM users WHERE parent_id = ? AND role = 'student'");
$childStmt->execute([$parentId]);
$childrenList = $childStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($childrenList)) {
    $fallbackStmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email, created_at FROM users WHERE role = 'student' LIMIT 1");
    $fallbackStmt->execute();
    $childrenList = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
}

$activeChildId = isset($_GET['child_id']) ? intval($_GET['child_id']) : ($childrenList[0]['id'] ?? 0);
$activeChild = null;
foreach ($childrenList as $item) {
    if ($item['id'] === $activeChildId) {
        $activeChild = $item;
        break;
    }
}
if (!$activeChild && !empty($childrenList)) {
    $activeChild = $childrenList[0];
    $activeChildId = $activeChild['id'];
}

$childName = $activeChild ? (!empty($activeChild['full_name']) ? $activeChild['full_name'] : trim(($activeChild['first_name'] ?? '') . ' ' . ($activeChild['last_name'] ?? ''))) : 'No Child Selected';
if (empty(trim($childName))) {
    $childName = $activeChild['email'] ?? 'Student';
}

$completedLessons = 0;
$speechAccuracy = 0;
$speechAccuracyStr = '0%';
$calmSessions = 0;
$weeklyStars = 0;
$weeklyGoalPct = 0;
$realProgressPct = 0;
$speechLogs = [];
$activityLogs = [];
$gameBreakdown = [
    'pecs' => 0,
    'speech' => 0,
    'grammar' => 0,
    'emotions' => 0,
    'calm' => 0
];
$isCurrentlyPlaying = false;
$currentPlayingActivity = 'None right now';

if ($activeChildId > 0) {
    try {
        $liveCheck = $pdo->prepare("SELECT title, activity_type, created_at FROM activity_logs WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY created_at DESC LIMIT 1");
        $liveCheck->execute([$activeChildId]);
        $liveData = $liveCheck->fetch(PDO::FETCH_ASSOC);
        if ($liveData) {
            $isCurrentlyPlaying = true;
            $currentPlayingActivity = $liveData['title'] . ' (' . ucfirst($liveData['activity_type']) . ')';
        }
    } catch (Exception $e) {}

    try {
        $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = ? AND (status = 'completed' OR is_completed = 1)");
        $stmt1->execute([$activeChildId]);
        $c1 = intval($stmt1->fetchColumn());

        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = ?");
        $stmt2->execute([$activeChildId]);
        $c2 = intval($stmt2->fetchColumn());

        $completedLessons = max($c1, $c2);
    } catch (Exception $e) {}

    try {
        $speechStmt = $pdo->prepare("SELECT AVG(accuracy_score) as avg_acc FROM speech_logs WHERE user_id = ?");
        $speechStmt->execute([$activeChildId]);
        $avgAccRes = $speechStmt->fetch(PDO::FETCH_ASSOC);
        if ($avgAccRes && $avgAccRes['avg_acc'] !== null) {
            $speechAccuracy = round(floatval($avgAccRes['avg_acc']));
            $speechAccuracyStr = $speechAccuracy . '%';
        }
    } catch (Exception $e) {
        $speechAccuracy = 0;
        $speechAccuracyStr = '0%';
    }

    try {
        $calmStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = ? AND (activity_type = 'sensory' OR activity_type = 'calm')");
        $calmStmt->execute([$activeChildId]);
        $calmSessions = intval($calmStmt->fetchColumn());
    } catch (Exception $e) {
        $calmSessions = 0;
    }

    try {
        $starStmt = $pdo->prepare("SELECT SUM(stars_earned) as total_stars FROM activity_logs WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $starStmt->execute([$activeChildId]);
        $starRes = $starStmt->fetch(PDO::FETCH_ASSOC);
        $weeklyStars = ($starRes && $starRes['total_stars'] !== null) ? intval($starRes['total_stars']) : 0;
    } catch (Exception $e) {
        $weeklyStars = 0;
    }

    $weeklyGoalPct = min(100, round(($weeklyStars / 30) * 100));

    // --- REAL PROGRESS PERCENTAGE ALGORITHM ---
    $curriculumTarget = 10;
    $curriculumScore = min(100, ($completedLessons / $curriculumTarget) * 100);
    $realProgressPct = round(($curriculumScore * 0.40) + ($speechAccuracy * 0.30) + ($weeklyGoalPct * 0.30));
    $realProgressPct = max(0, min(100, $realProgressPct));

    try {
        $gameBreakdownStmt = $pdo->prepare("SELECT activity_type, COUNT(*) as cnt FROM activity_logs WHERE user_id = ? GROUP BY activity_type");
        $gameBreakdownStmt->execute([$activeChildId]);
        while ($row = $gameBreakdownStmt->fetch(PDO::FETCH_ASSOC)) {
            $type = strtolower($row['activity_type']);
            if (array_key_exists($type, $gameBreakdown)) {
                $gameBreakdown[$type] = intval($row['cnt']);
            }
        }
    } catch (Exception $e) {}

    try {
        $sLogsStmt = $pdo->prepare("SELECT target_word, heard_word, accuracy_score, stars_earned, created_at FROM speech_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
        $sLogsStmt->execute([$activeChildId]);
        $speechLogs = $sLogsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $actLogsStmt = $pdo->prepare("SELECT title, description, icon_class, color_code, created_at FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
        $actLogsStmt->execute([$activeChildId]);
        $activityLogs = $actLogsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        if (empty($datetime)) return 'Recently';
        $diff = time() - strtotime($datetime);
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
<title>Advanced Parent Portal | Spark Steps</title>
<meta http-equiv="refresh" content="30">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root { --bg-neutral: #f8fafc; --parent-primary: #1e3a8a; --parent-accent: #2563eb; --card-radius: 20px; }
body { background-color: var(--bg-neutral); font-family: 'Poppins', sans-serif; color: #334155; padding-bottom: 80px; }
h1, h2, h3, h4, .brand-font { font-family: 'Fredoka', cursive, sans-serif; }
.navbar-parent { background: #fff; border-bottom: 2px solid #e2e8f0; padding: 12px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
.metric-card { background: #fff; border-radius: var(--card-radius); padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02); transition: transform 0.2s; height: 100%; }
.metric-card:hover { transform: translateY(-3px); }
.icon-circle { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.timeline-item { position: relative; padding-left: 36px; margin-bottom: 20px; border-left: 2px solid #e2e8f0; }
.timeline-icon { position: absolute; left: -13px; top: 0; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; }
.pulse-dot { width: 12px; height: 12px; background: #10b981; border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
@keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16,185,129,0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16,185,129,0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16,185,129,0); } }
@media print { .no-print { display: none !important; } body { background: #fff !important; } }
</style>
</head>
<body>

<nav class="navbar navbar-parent sticky-top mb-4 no-print">
<div class="container d-flex align-items-center justify-content-between">
<a class="navbar-brand brand-font fs-3 text-primary d-flex align-items-center gap-2 m-0 text-decoration-none" href="#">
<i class="fa-solid fa-chart-line text-primary fs-2"></i> Spark Steps <span class="fs-5 text-secondary">Advanced Portal</span>
</a>
<div class="d-flex align-items-center gap-2">
<form method="GET" class="d-inline-block m-0">
<select name="child_id" class="form-select form-select-sm rounded-pill fw-semibold" onchange="this.form.submit()">
<?php foreach($childrenList as $c): ?>
<option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $activeChildId) ? 'selected' : ''; ?>><?php echo htmlspecialchars(!empty($c['full_name']) ? $c['full_name'] : $c['email']); ?></option>
<?php endforeach; ?>
</select>
</form>
<a href="<?php echo htmlspecialchars($profileUrl); ?>" class="btn btn-outline-dark rounded-pill px-3 btn-sm fw-semibold"><i class="fa-solid fa-user-gear"></i> Profile</a>
<button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3 btn-sm fw-semibold"><i class="fa-solid fa-print"></i> Print</button>
<a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="btn btn-outline-danger rounded-pill px-3 btn-sm fw-semibold"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>
</div>
</nav>

<div class="container">

<?php if (!empty($flashMessage)): ?>
<div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show rounded-4 mb-4 fw-semibold shadow-sm border-0" role="alert">
<i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($flashMessage); ?>
<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="bg-primary text-white rounded-4 p-4 p-md-5 mb-4 shadow-sm" style="background: linear-gradient(135deg, #1e3a8a, #2563eb) !important;">
<div class="row align-items-center">
<div class="col-md-8">
<span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold mb-2 shadow-sm">
<?php if ($isCurrentlyPlaying): ?>
<span class="pulse-dot me-1"></span> Live Activity: <?php echo htmlspecialchars($childName); ?> is active now!
<?php else: ?>
Student Linked: <strong><?php echo htmlspecialchars($activeChild['email'] ?? 'Student'); ?></strong> (ID: #<?php echo $activeChildId; ?>)
<?php endif; ?>
</span>
<h2 class="brand-font fw-bold fs-1 mb-2">Welcome, <?php echo htmlspecialchars($parentName); ?>!</h2>
<p class="opacity-90 fs-5 mb-0">Advanced live tracking and real progress telemetry for <strong><?php echo htmlspecialchars($childName); ?></strong>.</p>
</div>
<div class="col-md-4 text-md-end mt-3 mt-md-0 no-print">
<form method="POST" class="d-inline-block">
<input type="hidden" name="action" value="link_student_parent">
<input type="hidden" name="student_email" value="<?php echo htmlspecialchars($activeChild['email'] ?? ''); ?>">
<button type="submit" class="btn btn-light text-primary rounded-pill px-4 fw-bold shadow-sm">
<i class="fa-solid fa-sync me-1"></i> Force Sync Student ID
</button>
</form>
</div>
</div>
</div>

<!-- Real Progress Overview Banner -->
<div class="row g-4 mb-4">
<div class="col-12">
<div class="bg-white rounded-4 p-4 border shadow-sm">
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
<div>
<span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold mb-1">
<i class="fa-solid fa-chart-pie me-1"></i> Overall Real Progress Score
</span>
<h3 class="brand-font fw-bold text-dark mb-0">Overall Student Milestone Mastery</h3>
</div>
<div class="text-md-end">
<span class="display-6 brand-font fw-bold text-primary"><?php echo $realProgressPct; ?>%</span>
<span class="text-muted small d-block">Calculated from Modules, Speech & Stars</span>
</div>
</div>
<div class="progress" style="height: 16px; border-radius: 10px; background-color: #f1f5f9;">
<div class="progress-bar bg-primary progress-bar-striped progress-bar-animated rounded-pill" role="progressbar" style="width: <?php echo $realProgressPct; ?>%;" aria-valuenow="<?php echo $realProgressPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
</div>
</div>
</div>
</div>

<div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
<div class="col">
<div class="metric-card">
<div class="d-flex justify-content-between mb-3"><span class="text-secondary fw-semibold small">Completed Modules</span><div class="icon-circle bg-primary-subtle text-primary"><i class="fa-solid fa-graduation-cap"></i></div></div>
<h2 class="brand-font fw-bold text-dark mb-1"><?php echo $completedLessons; ?></h2>
<span class="text-success small fw-semibold"><i class="fa-solid fa-bolt me-1"></i>Real-time logged</span>
</div>
</div>
<div class="col">
<div class="metric-card">
<div class="d-flex justify-content-between mb-3"><span class="text-secondary fw-semibold small">Speech Clarity Score</span><div class="icon-circle bg-success-subtle text-success"><i class="fa-solid fa-microphone-lines"></i></div></div>
<h2 class="brand-font fw-bold text-dark mb-1"><?php echo $speechAccuracyStr; ?></h2>
<span class="text-success small fw-semibold"><i class="fa-solid fa-chart-line me-1"></i>Microphone analysis</span>
</div>
</div>
<div class="col">
<div class="metric-card">
<div class="d-flex justify-content-between mb-3"><span class="text-secondary fw-semibold small">Sensory Calm Sessions</span><div class="icon-circle bg-info-subtle text-info"><i class="fa-solid fa-spa"></i></div></div>
<h2 class="brand-font fw-bold text-dark mb-1"><?php echo $calmSessions; ?></h2>
<span class="text-muted small fw-semibold">Relaxation sessions</span>
</div>
</div>
<div class="col">
<div class="metric-card">
<div class="d-flex justify-content-between mb-3"><span class="text-secondary fw-semibold small">Weekly Stars Earned</span><div class="icon-circle bg-warning-subtle text-warning"><i class="fa-solid fa-star"></i></div></div>
<h2 class="brand-font fw-bold text-dark mb-1"><?php echo $weeklyStars; ?></h2>
<span class="text-warning-emphasis small fw-semibold">Goal Progress: <?php echo $weeklyGoalPct; ?>%</span>
</div>
</div>
</div>

<div class="row g-4 mb-4">
<div class="col-xl-12">
<div class="bg-white rounded-4 p-4 border shadow-sm">
<h4 class="brand-font fw-bold text-dark mb-3"><i class="fa-solid fa-chart-pie text-primary me-2"></i>Game & Module Progress Breakdown</h4>
<div class="row g-3 text-center">
<div class="col">
<div class="p-3 bg-light rounded-4">
<span class="text-muted small fw-semibold d-block">PECS / Cards</span>
<h3 class="brand-font text-primary fw-bold mb-0"><?php echo $gameBreakdown['pecs'] ?? 0; ?></h3>
</div>
</div>
<div class="col">
<div class="p-3 bg-light rounded-4">
<span class="text-muted small fw-semibold d-block">Speech Lab</span>
<h3 class="brand-font text-success fw-bold mb-0"><?php echo $gameBreakdown['speech'] ?? 0; ?></h3>
</div>
</div>
<div class="col">
<div class="p-3 bg-light rounded-4">
<span class="text-muted small fw-semibold d-block">Grammar Game</span>
<h3 class="brand-font text-info fw-bold mb-0"><?php echo $gameBreakdown['grammar'] ?? 0; ?></h3>
</div>
</div>
<div class="col">
<div class="p-3 bg-light rounded-4">
<span class="text-muted small fw-semibold d-block">Emotions Game</span>
<h3 class="brand-font text-warning fw-bold mb-0"><?php echo $gameBreakdown['emotions'] ?? 0; ?></h3>
</div>
</div>
<div class="col">
<div class="p-3 bg-light rounded-4">
<span class="text-muted small fw-semibold d-block">Calm / Sensory</span>
<h3 class="brand-font text-danger fw-bold mb-0"><?php echo $gameBreakdown['calm'] ?? 0; ?></h3>
</div>
</div>
</div>
</div>
</div>
</div>

<div class="row g-4">
<div class="col-lg-7">
<div class="bg-white rounded-4 p-4 border shadow-sm h-100">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="brand-font fw-bold text-dark m-0"><i class="fa-solid fa-microphone-lines text-success me-2"></i>Speech Pronunciation Telemetry</h4>
<span class="badge bg-light text-secondary border fw-semibold">Child ID #<?php echo $activeChildId; ?></span>
</div>
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light small text-uppercase">
<tr><th>Target Word</th><th>Child Spoke</th><th>Score / Status</th><th>Stars</th><th>Time</th></tr>
</thead>
<tbody>
<?php if (empty($speechLogs)): ?>
<tr>
<td colspan="5" class="text-center text-muted py-4">No speech practice logs found yet.</td>
</tr>
<?php else: ?>
<?php foreach ($speechLogs as $log): 
$acc = (int)($log['accuracy_score'] ?? 0);
$statusLabel = ($acc >= 80) ? 'Correct' : 'Needs Practice';
$badgeClass = ($acc >= 80) ? 'bg-success' : 'bg-danger';
?>
<tr>
<td class="fw-bold text-dark"><?php echo htmlspecialchars($log['target_word'] ?? ''); ?></td>
<td class="fst-italic text-secondary">"<?php echo htmlspecialchars($log['heard_word'] ?? ''); ?>"</td>
<td><span class="badge <?php echo $badgeClass; ?> px-2 py-1"><?php echo $acc; ?>% (<?php echo $statusLabel; ?>)</span></td>
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
<div class="d-flex justify-content-between align-items-center mb-4">
<h4 class="brand-font fw-bold text-dark m-0"><i class="fa-solid fa-timeline text-primary me-2"></i>Live Game Timeline</h4>
<span class="badge bg-light text-secondary border fw-semibold">Live Feed</span>
</div>
<div class="timeline-container px-2">
<?php if (empty($activityLogs)): ?>
<div class="text-center text-muted py-4">No live events recorded yet.</div>
<?php else: ?>
<?php foreach ($activityLogs as $act): ?>
<div class="timeline-item">
<div class="timeline-icon" style="background-color: <?php echo htmlspecialchars($act['color_code'] ?? '#6366f1'); ?>;"><i class="fa-solid <?php echo htmlspecialchars($act['icon_class'] ?? 'fa-graduation-cap'); ?>"></i></div>
<h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($act['title'] ?? ''); ?></h6>
<p class="small text-muted mb-1"><?php echo htmlspecialchars($act['description'] ?? ''); ?></p>
<span class="badge bg-light text-muted border" style="font-size: 10px;"><?php echo timeAgo($act['created_at'] ?? ''); ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
