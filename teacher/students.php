<?php
// teacher/students.php
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

$students = [];
$searchQuery = trim($_GET['search'] ?? '');

try {
    if (isset($pdo)) {
        // Fetch real students with aggregated metrics (e.g., average speech accuracy and total sessions)
        $sql = "
            SELECT u.id, u.full_name, u.email, 
                   COALESCE(u.last_active, u.updated_at) as last_seen,
                   COUNT(s.id) as total_sessions,
                   AVG(s.accuracy_score) as avg_accuracy
            FROM users u
            LEFT JOIN speech_labs s ON u.id = s.user_id
            WHERE u.role = 'student'
        ";

        if (!empty($searchQuery)) {
            $sql .= " AND (u.full_name LIKE :search OR u.email LIKE :search)";
        }

        $sql .= " GROUP BY u.id, u.full_name, u.email, u.last_active, u.updated_at ORDER BY u.full_name ASC";

        $stmt = $pdo->prepare($sql);
        
        if (!empty($searchQuery)) {
            $stmt->execute(['search' => '%' . $searchQuery . '%']);
        } else {
            $stmt->execute();
        }

        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Teacher Students Management DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Enrolled Students | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
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
                        <i class="fa-solid fa-users me-1"></i> Student Directory
                    </span>
                    <h1 class="brand-font text-success mb-1">Enrolled Students Management</h1>
                    <p class="text-secondary mb-0">View student profiles, track individual lab module activity, and inspect speech performance accuracy.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <span class="badge bg-light text-dark p-3 rounded-4 border fs-6">
                        <i class="fa-solid fa-user-group text-success me-2"></i> Total Enrolled: <?php echo count($students); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- SEARCH & FILTER BAR -->
    <div class="dashboard-section py-4 mb-4">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-success"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0 shadow-none" placeholder="Search student by name or email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-success flex-grow-1 fw-semibold rounded-pill py-2">
                    <i class="fa-solid fa-filter me-1"></i> Filter Students
                </button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="students.php" class="btn btn-outline-secondary rounded-pill py-2 px-3" title="Reset Search">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- STUDENTS LIST TABLE -->
    <div class="dashboard-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark mb-0 brand-font">
                <i class="fa-solid fa-address-book text-success me-2"></i>Registered Student Records
            </h4>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student Full Name</th>
                        <th>Email Address</th>
                        <th>Status / Presence</th>
                        <th>Completed Sessions</th>
                        <th>Avg. Accuracy</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-info me-2 text-success fs-5"></i> No student accounts found matching your query.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <?php 
                                // Check online presence (active within last 15 minutes)
                                $isOnline = false;
                                if (!empty($student['last_seen'])) {
                                    $isOnline = (time() - strtotime($student['last_seen'])) < 900;
                                }

                                $avgAcc = isset($student['avg_accuracy']) && $student['avg_accuracy'] !== null 
                                          ? round($student['avg_accuracy']) . '%' 
                                          : 'N/A';
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative">
                                            <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.95rem;">
                                                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                            </div>
                                            <?php if ($isOnline): ?>
                                                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" title="Online Now"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($student['full_name']); ?></span>
                                            <?php if ($isOnline): ?>
                                                <small class="text-success" style="font-size: 0.75rem;"><i class="fa-solid fa-circle" style="font-size: 6px;"></i> Online Now</small>
                                            <?php else: ?>
                                                <small class="text-muted" style="font-size: 0.75rem;">Last active: <?php echo !empty($student['last_seen']) ? date('M j, g:i a', strtotime($student['last_seen'])) : 'Never'; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-secondary"><?php echo htmlspecialchars($student['email']); ?></span></td>
                                <td>
                                    <?php if ($isOnline): ?>
                                        <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1">Active Now</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary fw-semibold px-2 py-1 border">Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark fw-bold px-2 py-1 border"><?php echo (int)$student['total_sessions']; ?> Sessions</span></td>
                                <td><span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1"><?php echo $avgAcc; ?></span></td>
                                <td class="text-end">
                                    <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                                        <i class="fa-solid fa-eye me-1"></i> View Progress
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