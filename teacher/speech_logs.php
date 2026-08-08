<?php
// teacher/speech_logs.php
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

$logs = [];
$searchQuery = trim($_GET['search'] ?? '');
$dbError = null;

try {
    if (isset($pdo)) {
        // Automatically create or update the speech_logs table structure safely with all required columns
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

        // Safely check and add 'confidence_score' column if it's missing in an existing table structure
        $checkCol = $pdo->query("SHOW COLUMNS FROM speech_logs LIKE 'confidence_score'");
        if ($checkCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE speech_logs ADD COLUMN confidence_score DECIMAL(5,2) DEFAULT 0.00 AFTER transcript");
        }

        // Fetch speech logs joining with users table safely
        $sql = "
            SELECT l.id, 
                   l.user_id, 
                   l.transcript, 
                   l.confidence_score, 
                   l.status, 
                   l.created_at,
                   u.first_name, 
                   u.last_name, 
                   u.full_name, 
                   u.email
            FROM speech_logs l
            JOIN users u ON l.user_id = u.id
        ";

        $params = [];

        if (!empty($searchQuery)) {
            $sql .= " WHERE (u.email LIKE :s_email OR u.full_name LIKE :s_fullname OR u.first_name LIKE :s_firstname OR u.last_name LIKE :s_lastname OR l.transcript LIKE :s_transcript)";
            $searchTerm = '%' . $searchQuery . '%';
            $params = [
                's_email' => $searchTerm,
                's_fullname' => $searchTerm,
                's_firstname' => $searchTerm,
                's_lastname' => $searchTerm,
                's_transcript' => $searchTerm
            ];
        }

        $sql .= " ORDER BY l.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Speech Lab Logs | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
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

        .search-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 6px;
            display: block;
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
                <i class="fa-solid fa-users me-1"></i> Students
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
                        <i class="fa-solid fa-microphone-lines me-1"></i> Speech Monitoring
                    </span>
                    <h1 class="brand-font text-success mb-1">Student Speech Activity Logs</h1>
                    <p class="text-secondary mb-0">Inspect real-time student speech transcripts, confidence scores, and lab session tracking data.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <span class="badge bg-light text-dark p-3 rounded-4 border fs-6">
                        <i class="fa-solid fa-file-waveform text-success me-2"></i> Total Logs: <?php echo count($logs); ?>
                    </span>
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

    <!-- SMART SEARCH BAR -->
    <div class="dashboard-section py-4 mb-4">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-lg-9">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-success ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-2 shadow-none py-2" placeholder="Search by student email, name, or speech transcript keyword..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <span class="search-hint">
                    <i class="fa-solid fa-lightbulb text-warning me-1"></i> Tip: Enter student email fragments or words spoken in speech activities to filter results.
                </span>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-success flex-grow-1 fw-semibold rounded-pill py-2">
                    <i class="fa-solid fa-filter me-1"></i> Filter Logs
                </button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="speech_logs.php" class="btn btn-outline-secondary rounded-pill py-2 px-3" title="Clear Filter">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- SPEECH LOGS TABLE -->
    <div class="dashboard-section">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
            <h4 class="fw-bold text-dark mb-0 brand-font">
                <i class="fa-solid fa-wave-square text-success me-2"></i>Recorded Speech Sessions
            </h4>
            <?php if (!empty($searchQuery)): ?>
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">
                    Active Query: "<?php echo htmlspecialchars($searchQuery); ?>"
                </span>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student Details</th>
                        <th>Speech Transcript</th>
                        <th>Confidence Score</th>
                        <th>Timestamp</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <div class="py-3">
                                    <i class="fa-solid fa-microphone-slash text-secondary fs-2 mb-2 d-block"></i>
                                    <h5 class="fw-bold text-dark">No speech logs found</h5>
                                    <p class="small mb-0">No records match your query "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>".</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php 
                                $studentName = trim($log['full_name'] ?? '');
                                if (empty($studentName)) {
                                    $studentName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''));
                                }
                                if (empty($studentName)) {
                                    $studentName = 'Student #' . $log['user_id'];
                                }

                                $score = floatval($log['confidence_score'] ?? 0);
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 0.9rem;">
                                            <?php echo strtoupper(substr($studentName, 0, 1)); ?>
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($studentName); ?></span>
                                            <small class="text-secondary" style="font-size: 0.75rem;"><?php echo htmlspecialchars($log['email'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark fst-italic" style="max-width: 350px; display: inline-block;">
                                        "<?php echo htmlspecialchars($log['transcript']); ?>"
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success fw-bold px-3 py-2">
                                        <?php echo number_format($score, 1); ?>% Accuracy
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark fw-semibold px-2 py-1 border">
                                        <?php echo !empty($log['created_at']) ? date('M j, Y, g:i a', strtotime($log['created_at'])) : 'N/A'; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="student_profile.php?id=<?php echo $log['user_id']; ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                                        <i class="fa-solid fa-eye me-1"></i> Profile
                                    </a>
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