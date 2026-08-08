<?php
// teacher/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Session & Role Guard for Teachers
if (!isset($_SESSION['user_id'])) {
    $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : '../login.php';
    header("Location: {$loginUrl}");
    exit;
}

if (isset($_SESSION['role'] ) && $_SESSION['role'] !== 'teacher') {
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
$avgSpeechAccuracy = '0%';
$pendingReviews = 0;
$recentStudents = [];

try {
    if (isset($pdo)) {
        // 1. Total Enrolled Students Count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student'");
        $stmt->execute();
        $totalStudents = (int)$stmt->fetchColumn();

        // 2. Active Sessions Today (Students who performed an action or logged in today)
        // Adjust column name if your activity/users table uses 'last_active' or 'created_at'
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM student_activity WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        $activeSessionsToday = (int)$stmt->fetchColumn();

        // 3. Average Speech Accuracy from real speech lab records
        $stmt = $pdo->prepare("SELECT AVG(accuracy_score) FROM speech_labs");
        $stmt->execute();
        $avgScore = $stmt->fetchColumn();
        if ($avgScore !== false && $avgScore !== null) {
            $avgSpeechAccuracy = round($avgScore) . '%';
        }

        // 4. Pending Submissions/Reviews Count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE status = 'pending'");
        $stmt->execute();
        $pendingReviews = (int)$stmt->fetchColumn();

        // 5. Recent Student Activity Feed (Real database records joined with users)
        $activityQuery = "
            SELECT u.id as student_id,
                   u.full_name as name, 
                   COALESCE(u.last_active, u.updated_at) as last_seen,
                   COALESCE(s.module_name, 'Learning Module') as module, 
                   COALESCE(s.accuracy_score, 0) as accuracy_val, 
                   s.created_at, 
                   COALESCE(s.status, 'Completed') as status
            FROM users u
            LEFT JOIN speech_labs s ON u.id = s.user_id
            WHERE u.role = 'student'
            ORDER BY s.created_at DESC, u.last_active DESC
            LIMIT 10
        ";
        $stmt = $pdo->query($activityQuery);
        $fetchedActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($fetchedActivity)) {
            foreach ($fetchedActivity as $row) {
                // Format relative timestamp
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

                // Determine if user is currently online (active within the last 15 minutes)
                $isOnline = false;
                if (!empty($row['last_seen'])) {
                    $isOnline = (time() - strtotime($row['last_seen'])) < 900;
                }

                $recentStudents[] = [
                    'id' => $row['student_id'],
                    'name' => $row['name'],
                    'module' => $row['module'],
                    'accuracy' => $row['accuracy_val'] . '%',
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
    <title>Teacher Dashboard | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-soft: #f0fdf4;
            --primary-green: #16a34a;
            --card-radius: 24px;
        }

        body {
            background-color: var(--bg-soft);
            font-family: 'Poppins', sans-serif;
            color: #1e293b;
            padding-bottom: 80px;
        }

        h1, h2, h3, h4, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }

        .navbar-teacher {
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 0;
        }

        .stat-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 25px;
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.05);
            border: 2px solid #bbf7d0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(22, 163, 74, 0.1);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .dashboard-section {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.06);
            border: 2px solid #bbf7d0;
            margin-bottom: 30px;
        }

        .table-custom th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            border-top: none;
            padding: 14px 16px;
        }

        .table-custom td {
            padding: 14px 16px;
            vertical-align: middle;
        }

        .quick-action-btn {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 18px;
            padding: 22px 20px;
            text-align: center;
            color: #334155;
            font-weight: 600;
            transition: all 0.2s ease;
            display: block;
            text-decoration: none;
            height: 100%;
        }

        .quick-action-btn:hover {
            background: #f0fdf4;
            border-color: #16a34a;
            color: #15803d;
            transform: translateY(-2px);
        }

        .online-dot {
            width: 10px;
            height: 100px;
            border-radius: 50%;
            display: inline-block;
        }
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-teacher sticky-top mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-success d-flex align-items-center gap-2" href="#">
            <i class="fa-solid fa-chalkboard-user text-success fs-2"></i> Teacher Portal
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-secondary fw-semibold small d-none d-md-inline">
                <i class="fa-solid fa-user-tie me-1 text-success"></i> <?php echo htmlspecialchars($teacherName); ?>
            </span>
            <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container">
    
    <!-- WELCOME HERO BANNER -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-4 p-md-5 rounded-4 bg-white border border-success-subtle shadow-sm d-md-flex align-items-center justify-content-between">
                <div class="mb-3 mb-md-0">
                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-semibold fs-6 mb-2">
                        <i class="fa-solid fa-shield-halved me-1"></i> Instructor Control Center
                    </span>
                    <h1 class="brand-font text-success mb-1">Welcome back, <?php echo htmlspecialchars($teacherName); ?>!</h1>
                    <p class="text-secondary mb-0">Monitor live student progress, analyze speech clarity metrics, and manage interactive learning modules in real-time.</p>
                </div>
                <div>
                    <span class="badge bg-light text-dark p-3 rounded-4 border fs-6 d-inline-flex align-items-center">
                        <i class="fa-solid fa-calendar-day text-success me-2 fs-5"></i> <?php echo date('F j, Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- STATISTICS CARDS ROW -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1 fw-semibold">Total Students</p>
                        <h2 class="brand-font text-dark mb-0"><?php echo $totalStudents; ?></h2>
                    </div>
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1 fw-semibold">Active Sessions Today</p>
                        <h2 class="brand-font text-dark mb-0"><?php echo $activeSessionsToday; ?></h2>
                    </div>
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1 fw-semibold">Avg. Speech Accuracy</p>
                        <h2 class="brand-font text-dark mb-0"><?php echo $avgSpeechAccuracy; ?></h2>
                    </div>
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1 fw-semibold">Pending Reviews</p>
                        <h2 class="brand-font text-dark mb-0"><?php echo $pendingReviews; ?></h2>
                    </div>
                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold text-dark mb-3 brand-font">
                <i class="fa-solid fa-bolt text-success me-2"></i>Quick Management Tools
            </h4>
        </div>
        <div class="col-md-4 mb-3">
            <a href="students.php" class="quick-action-btn">
                <i class="fa-solid fa-users-gear fs-3 text-success mb-2 d-block"></i>
                Manage Enrolled Students
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="speech_logs.php" class="quick-action-btn">
                <i class="fa-solid fa-waveform fs-3 text-primary mb-2 d-block"></i>
                Review Speech Lab Audio Logs
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="reports.php" class="quick-action-btn">
                <i class="fa-solid fa-chart-pie fs-3 text-warning mb-2 d-block"></i>
                Generate Progress Reports
            </a>
        </div>
    </div>

    <!-- RECENT STUDENT ACTIVITY TABLE -->
    <div class="dashboard-section">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <h4 class="fw-bold text-dark mb-0 brand-font">
                <i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Real-Time Student Activity
            </h4>
            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold align-self-start align-self-md-auto">
                <i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i> Live Feed Connected
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
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
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-circle-info me-2 text-success"></i> No live student activity recorded in the database yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentStudents as $student): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative">
                                            <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 0.9rem;">
                                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                            </div>
                                            <?php if (!empty($student['is_online'])): ?>
                                                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" title="Online Now"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($student['name']); ?></span>
                                            <?php if (!empty($student['is_online'])): ?>
                                                <small class="text-success" style="font-size: 0.75rem;"><i class="fa-solid fa-circle" style="font-size: 6px;"></i> Active Now</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-secondary"><?php echo htmlspecialchars($student['module']); ?></span></td>
                                <td><span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1"><?php echo htmlspecialchars($student['accuracy']); ?></span></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($student['time']); ?></td>
                                <td>
                                    <?php if ($student['status'] === 'Excellent' || $student['status'] === 'Completed'): ?>
                                        <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1"><?php echo htmlspecialchars($student['status']); ?></span>
                                    <?php elseif ($student['status'] === 'Good'): ?>
                                        <span class="badge bg-info-subtle text-info fw-semibold px-2 py-1">Good</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning fw-semibold px-2 py-1"><?php echo htmlspecialchars($student['status']); ?></span>
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

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>