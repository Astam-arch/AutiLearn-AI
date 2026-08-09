<?php
// teacher/speech_logs.php
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
$dashboardUrl = defined('BASE_URL') ? BASE_URL . 'teacher/dashboard.php' : 'dashboard.php';
$studentsUrl = defined('BASE_URL') ? BASE_URL . 'teacher/students.php' : 'students.php';

$speechLogs = [];
$searchQuery = trim($_GET['search'] ?? '');
$dbError = null;

try {
    if (isset($pdo)) {
        // Fetch table columns for speech_logs to safely check schema
        $columnsStmt = $pdo->query("SHOW COLUMNS FROM speech_logs");
        $tableColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

        // Strictly identify the correct foreign key linking speech_logs to users (user_id or student_id)
        $fkCol = 'user_id';
        if (in_array('student_id', $tableColumns)) {
            $fkCol = 'student_id';
        } elseif (in_array('user_id', $tableColumns)) {
            $fkCol = 'user_id';
        }

        // Build robust query joining speech_logs with users table
        $sql = "
            SELECT sl.*, 
                   COALESCE(u.full_name, CONCAT(u.first_name, ' ', u.last_name), CONCAT('User #', sl.{$fkCol})) AS student_full_name,
                   u.first_name AS student_first_name, 
                   u.last_name AS student_last_name, 
                   COALESCE(u.email, 'No email linked') AS student_email,
                   u.role AS user_role
            FROM speech_logs sl
            LEFT JOIN users u ON sl.{$fkCol} = u.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($searchQuery)) {
            $sql .= " AND (
                u.full_name LIKE :s_query 
                OR u.email LIKE :s_query 
                OR u.first_name LIKE :s_query 
                OR u.last_name LIKE :s_query";
            
            foreach (['target_word', 'phrase', 'transcript', 'spoken_text', 'text', 'word'] as $col) {
                if (in_array($col, $tableColumns)) {
                    $sql .= " OR sl.{$col} LIKE :s_query";
                }
            }

            $sql .= ")";
            $params['s_query'] = '%' . $searchQuery . '%';
        }

        $sql .= " ORDER BY sl.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $speechLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $dbError = "Database connection object (\$pdo) is not initialized.";
    }
} catch (PDOException $e) {
    $dbError = "Speech logs error: " . $e->getMessage();
    $speechLogs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Speech Logs & Exercises | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
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
            <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-house me-1"></i> Dashboard
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
                        <i class="fa-solid fa-microphone-lines me-1"></i> AI Speech Monitoring
                    </span>
                    <h1 class="brand-font text-success mb-1">Student Speech & Exercise Logs</h1>
                    <p class="text-secondary mb-0">Review actual pronunciation practice attempts, error flags, transcripts, and audio recordings from your registered users.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <span class="badge bg-light text-dark p-3 rounded-4 border fs-6">
                        <i class="fa-solid fa-file-waveform text-success me-2"></i> Total Logs: <?php echo count($speechLogs); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- DATABASE NOTICE -->
    <?php if ($dbError): ?>
        <div class="alert alert-warning rounded-4 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <strong>Database Notice:</strong> <?php echo htmlspecialchars($dbError); ?>
        </div>
    <?php endif; ?>

    <!-- SEARCH & FILTER BAR -->
    <div class="dashboard-section py-4 mb-4">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-lg-9">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-success ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-2 shadow-none py-2" placeholder="Search by student name, email, target word, or transcript..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-success flex-grow-1 fw-semibold rounded-pill py-2">
                    <i class="fa-solid fa-filter me-1"></i> Search Logs
                </button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="speech_logs.php" class="btn btn-outline-secondary rounded-pill py-2 px-3" title="Reset Search">
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
                <i class="fa-solid fa-list-check text-success me-2"></i>Recorded Practice History
            </h4>
            <?php if (!empty($searchQuery)): ?>
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">
                    Search filter applied: "<?php echo htmlspecialchars($searchQuery); ?>"
                </span>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Target Word</th>
                        <th>Student Transcript</th>
                        <th>Status / Mistake Check</th>
                        <th>Audio Recording</th>
                        <th class="text-end">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($speechLogs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="py-3">
                                    <i class="fa-solid fa-microphone-slash text-secondary fs-2 mb-2 d-block"></i>
                                    <h5 class="fw-bold text-dark">No speech logs found</h5>
                                    <p class="small mb-0">No practice records currently exist in your database table.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($speechLogs as $log): ?>
                            <?php 
                                // Resolve real student name and email from users table
                                $studentName = trim($log['student_full_name'] ?? '');
                                if (empty($studentName) || strpos($studentName, 'User #') === 0) {
                                    $fName = trim($log['student_first_name'] ?? '');
                                    $lName = trim($log['student_last_name'] ?? '');
                                    if (!empty($fName) || !empty($lName)) {
                                        $studentName = trim($fName . ' ' . $lName);
                                    } else {
                                        $studentName = 'Registered Student (ID #' . ($log[$fkCol] ?? 'N/A') . ')';
                                    }
                                }

                                $studentEmail = trim($log['student_email'] ?? 'No email linked');

                                $targetWord = trim($log['target_word'] ?? $log['phrase'] ?? $log['word'] ?? 'N/A');
                                $transcript = trim($log['transcript'] ?? $log['spoken_text'] ?? $log['text'] ?? $log['spoken_word'] ?? '');
                                $score = $log['score'] ?? $log['accuracy'] ?? null;

                                // Handle empty transcripts cleanly
                                $displayTranscript = !empty($transcript) ? $transcript : '<span class="text-muted fst-italic">No transcript captured</span>';

                                // Automatic mistake comparison: check score or string mismatch
                                $hasMistake = false;
                                if (empty($transcript) || (is_numeric($score) && $score < 80)) {
                                    $hasMistake = true;
                                } elseif (!empty($targetWord) && !empty($transcript) && strcasecmp($targetWord, $transcript) !== 0) {
                                    $hasMistake = true;
                                }

                                $audioFile = $log['audio_path'] ?? $log['file_path'] ?? $log['audio_url'] ?? $log['recording'] ?? '';
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.95rem;">
                                            <?php echo strtoupper(substr($studentName, 0, 1)); ?>
                                        </div>
                                        <div>
                                            <a href="students.php" class="fw-semibold text-dark text-decoration-none d-block">
                                                <?php echo htmlspecialchars($studentName); ?>
                                            </a>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($studentEmail); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-success border fw-bold px-3 py-2 fs-6">
                                        <?php echo htmlspecialchars($targetWord); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $hasMistake ? 'text-danger fw-semibold' : 'text-success fw-medium'; ?>">
                                        <?php echo $displayTranscript; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($hasMistake): ?>
                                        <span class="badge bg-danger-subtle text-danger fw-bold px-3 py-2 rounded-pill">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Mistake / Mismatch
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill">
                                            <i class="fa-solid fa-check me-1"></i> Correct / Match
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($audioFile)): ?>
                                        <audio controls class="custom-audio" style="height: 36px; width: 180px;">
                                            <source src="<?php echo htmlspecialchars($audioFile); ?>" type="audio/webm">
                                            <source src="<?php echo htmlspecialchars($audioFile); ?>" type="audio/mp3">
                                            Your browser does not support audio.
                                        </audio>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">No audio recorded</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-light text-dark fw-semibold px-2 py-1 border">
                                        <?php echo !empty($log['created_at']) ? date('M j, Y, g:i a', strtotime($log['created_at'])) : 'N/A'; ?>
                                    </span>
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