<?php
// teacher/reports.php
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
$dashboardUrl = defined('BASE_URL') ? BASE_URL . 'teacher/dashboard.php' : 'dashboard.php';

$totalUsers = 0;
$totalStudents = 0;
$totalParents = 0;
$totalTeachers = 0;
$totalSpeechLogs = 0;
$avgConfidence = 0.0;
$recentUsers = [];
$dbError = null;

try {
    if (isset($pdo)) {
        // 1. Create table if it completely lacks
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS speech_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                transcript TEXT NOT NULL,
                confidence_score DECIMAL(5,2) DEFAULT 0.00,
                status VARCHAR(50) DEFAULT 'Completed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Safely check if columns exist in an older existing table, and add them if missing
        $columnsResult = $pdo->query("SHOW COLUMNS FROM speech_logs");
        $existingColumns = $columnsResult->fetchAll(PDO::FETCH_COLUMN, 0);

        if (!in_array('transcript', $existingColumns)) {
            $pdo->exec("ALTER TABLE speech_logs ADD COLUMN transcript TEXT NOT NULL AFTER user_id");
        }
        if (!in_array('confidence_score', $existingColumns)) {
            $pdo->exec("ALTER TABLE speech_logs ADD COLUMN confidence_score DECIMAL(5,2) DEFAULT 0.00 AFTER transcript");
        }
        if (!in_array('status', $existingColumns)) {
            $pdo->exec("ALTER TABLE speech_logs ADD COLUMN status VARCHAR(50) DEFAULT 'Completed' AFTER confidence_score");
        }

        // Fetch user stats safely
        $stmtUsers = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $roleCounts = $stmtUsers->fetchAll(PDO::FETCH_KEY_PAIR);

        $totalStudents = intval($roleCounts['student'] ?? 0);
        $totalParents = intval($roleCounts['parent'] ?? 0);
        $totalTeachers = intval($roleCounts['teacher'] ?? 0);
        $totalUsers = array_sum($roleCounts);

        // Fetch speech log metrics safely
        $stmtLogs = $pdo->query("SELECT COUNT(*) as total_logs, AVG(confidence_score) as avg_score FROM speech_logs");
        $logData = $stmtLogs->fetch(PDO::FETCH_ASSOC);

        $totalSpeechLogs = intval($logData['total_logs'] ?? 0);
        $avgConfidence = floatval($logData['avg_score'] ?? 0.0);

        // Fetch recent activities across system
        $stmtRecent = $pdo->query("
            SELECT u.id, u.full_name, u.first_name, u.last_name, u.email, u.role, u.created_at 
            FROM users u 
            ORDER BY u.created_at DESC 
            LIMIT 5
        ");
        $recentUsers = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $dbError = "Database connection object (\$pdo) is not initialized.";
    }
} catch (PDOException $e) {
    $dbError = "Database Query Error: " . $e->getMessage();
    error_log($dbError);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports & Analytics | <?php echo defined('SITE_NAME') ? SITE_NAME : 'Spark Steps'; ?></title>
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

        .dashboard-section {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.06);
            border: 2px solid #bbf7d0;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 24px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            border-color: var(--primary-green);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.08);
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
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-teacher sticky-top mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-success d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>">
            <i class="fa-solid fa-chalkboard-user text-success fs-2"></i> Teacher Portal
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="students.php" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-users me-1"></i> Users Directory
            </a>
            <a href="speech_logs.php" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-microphone-lines me-1"></i> Speech Logs
            </a>
            <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
            </a>
            <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container">
    
    <!-- HEADER SECTION -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-4 p-md-5 rounded-4 bg-white border border-success-subtle shadow-sm d-md-flex align-items-center justify-content-between">
                <div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-semibold fs-6 mb-2">
                        <i class="fa-solid fa-chart-pie me-1"></i> Analytics & Reports
                    </span>
                    <h1 class="brand-font text-success mb-1">System Overview & Statistics</h1>
                    <p class="text-secondary mb-0">Monitor aggregate statistics, user engagement distribution, and speech laboratory activity summaries.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button onclick="window.print();" class="btn btn-success rounded-pill px-4 fw-semibold py-2">
                        <i class="fa-solid fa-print me-1"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DATABASE ERROR NOTIFICATION -->
    <?php if ($dbError): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <strong>System Notice:</strong> <?php echo htmlspecialchars($dbError); ?>
        </div>
    <?php endif; ?>

    <!-- STATISTICS CARDS GRID -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.25rem;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <span class="badge bg-success-subtle text-success fw-bold">Active</span>
                </div>
                <h3 class="brand-font text-dark mb-1"><?php echo $totalStudents; ?></h3>
                <p class="text-secondary small mb-0 fw-medium">Enrolled Students</p>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.25rem;">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <span class="badge bg-primary-subtle text-primary fw-bold">Linked</span>
                </div>
                <h3 class="brand-font text-dark mb-1"><?php echo $totalParents; ?></h3>
                <p class="text-secondary small mb-0 fw-medium">Registered Parents</p>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.25rem;">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <span class="badge bg-warning-subtle text-dark fw-bold">Staff</span>
                </div>
                <h3 class="brand-font text-dark mb-1"><?php echo $totalTeachers; ?></h3>
                <p class="text-secondary small mb-0 fw-medium">Instructors & Teachers</p>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.25rem;">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </div>
                    <span class="badge bg-info-subtle text-info fw-bold">Logs</span>
                </div>
                <h3 class="brand-font text-dark mb-1"><?php echo $totalSpeechLogs; ?></h3>
                <p class="text-secondary small mb-0 fw-medium">Speech Lab Activities</p>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE & METRICS SECTION -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="dashboard-section h-100 mb-0">
                <h4 class="fw-bold text-dark mb-3 brand-font">
                    <i class="fa-solid fa-gauge-high text-success me-2"></i>Speech Laboratory Metrics
                </h4>
                <p class="text-secondary small mb-4">Average overall student articulation accuracy recorded across speech practice sessions.</p>
                
                <div class="p-4 rounded-4 bg-light border text-center my-3">
                    <span class="display-5 brand-font text-success fw-bold d-block mb-1"><?php echo number_format($avgConfidence, 1); ?>%</span>
                    <span class="text-muted fw-semibold small">Average System Confidence Score</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <span class="text-secondary fw-medium">Total Registered Users:</span>
                    <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-bold"><?php echo $totalUsers; ?> Accounts</span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dashboard-section h-100 mb-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-dark mb-0 brand-font">
                        <i class="fa-solid fa-user-clock text-success me-2"></i>Recently Registered Accounts
                    </h4>
                    <a href="students.php" class="btn btn-outline-success btn-sm rounded-pill px-3">View All</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentUsers)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No recent records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $u): ?>
                                    <?php 
                                        $dName = trim($u['full_name'] ?? '');
                                        if (empty($dName)) {
                                            $dName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                                        }
                                        if (empty($dName)) {
                                            $dName = 'User #' . $u['id'];
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($dName); ?></span>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success text-uppercase px-2.5 py-1 rounded-pill fw-bold">
                                                <?php echo htmlspecialchars($u['role'] ?? 'student'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-secondary fw-semibold">
                                                <?php echo !empty($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : 'N/A'; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>