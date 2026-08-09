<?php
// teacher/user_profile.php
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
$studentsUrl = 'students.php';

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userProfile = null;
$dbError = null;

try {
    if (isset($pdo)) {
        if ($userId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        }
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
    <title>User Profile | <?php echo defined('SITE_NAME') ? SITE_NAME : 'Spark Steps'; ?></title>
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
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-teacher sticky-top mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-success d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($studentsUrl); ?>">
            <i class="fa-solid fa-arrow-left text-success fs-4"></i> Back to Directory
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container">
    
    <!-- DATABASE ERROR NOTIFICATION -->
    <?php if ($dbError): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <strong>System Notice:</strong> <?php echo htmlspecialchars($dbError); ?>
        </div>
    <?php endif; ?>

    <?php if (!$userProfile): ?>
        <div class="dashboard-section text-center py-5">
            <i class="fa-solid fa-user-slash text-secondary fs-1 mb-3"></i>
            <h3 class="brand-font text-dark">User Not Found</h3>
            <p class="text-muted">The user profile you are looking for does not exist or has been removed.</p>
            <a href="students.php" class="btn btn-success rounded-pill px-4 mt-3">Return to Directory</a>
        </div>
    <?php else: ?>
        <?php 
            $displayName = trim($userProfile['full_name'] ?? '');
            if (empty($displayName)) {
                $displayName = trim(($userProfile['first_name'] ?? '') . ' ' . ($userProfile['last_name'] ?? ''));
            }
            if (empty($displayName)) {
                $displayName = 'User #' . $userProfile['id'];
            }

            $roleVal = strtolower($userProfile['role'] ?? 'user');
            $badgeClass = 'bg-success-subtle text-success';
            if ($roleVal === 'parent') $badgeClass = 'bg-primary-subtle text-primary';
            elseif ($roleVal === 'teacher') $badgeClass = 'bg-warning-subtle text-dark';
            elseif ($roleVal === 'admin') $badgeClass = 'bg-danger-subtle text-danger';
        ?>

        <!-- PROFILE HEADER -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="p-4 p-md-5 rounded-4 bg-white border border-success-subtle shadow-sm d-md-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-4">
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2" style="width: 80px; height: 80px;">
                            <?php echo strtoupper(substr($displayName, 0, 1)); ?>
                        </div>
                        <div>
                            <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-1 fw-semibold mb-2 text-uppercase">
                                <i class="fa-solid fa-user-tag me-1"></i> <?php echo htmlspecialchars($roleVal); ?> Account
                            </span>
                            <h1 class="brand-font text-success mb-1"><?php echo htmlspecialchars($displayName); ?></h1>
                            <p class="text-secondary mb-0"><i class="fa-regular fa-envelope me-1"></i> <?php echo htmlspecialchars($userProfile['email'] ?? 'No email provided'); ?></p>
                        </div>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <a href="students.php" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROFILE DETAILS -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="dashboard-section h-100 mb-0">
                    <h4 class="brand-font text-dark mb-4">
                        <i class="fa-solid fa-circle-info text-success me-2"></i>Account Information
                    </h4>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border">
                                <span class="text-muted small d-block">First Name</span>
                                <strong class="text-dark"><?php echo htmlspecialchars($userProfile['first_name'] ?? 'N/A'); ?></strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border">
                                <span class="text-muted small d-block">Last Name</span>
                                <strong class="text-dark"><?php echo htmlspecialchars($userProfile['last_name'] ?? 'N/A'); ?></strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border">
                                <span class="text-muted small d-block">Email Address</span>
                                <strong class="text-dark"><?php echo htmlspecialchars($userProfile['email'] ?? 'N/A'); ?></strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-4 border">
                                <span class="text-muted small d-block">System Role</span>
                                <strong class="text-dark text-uppercase"><?php echo htmlspecialchars($userProfile['role'] ?? 'N/A'); ?></strong>
                            </div>
                        </div>
                        <?php if (!empty($userProfile['parent_email'])): ?>
                        <div class="col-sm-12">
                            <div class="p-3 bg-light rounded-4 border">
                                <span class="text-muted small d-block">Parent/Guardian Contact</span>
                                <strong class="text-dark"><?php echo htmlspecialchars($userProfile['parent_email']); ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-sm-12">
                            <div class="p-3 bg-light rounded-4 border">
                                <span class="text-muted small d-block">Registration Date</span>
                                <strong class="text-dark"><?php echo !empty($userProfile['created_at']) ? date('F j, Y, g:i a', strtotime($userProfile['created_at'])) : 'N/A'; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="dashboard-section h-100 mb-0 text-center">
                    <h4 class="brand-font text-dark mb-3">
                        <i class="fa-solid fa-bolt text-warning me-2"></i>Quick Actions
                    </h4>
                    <p class="text-muted small mb-4">Manage directory records or jump back to the control panel.</p>
                    <a href="students.php" class="btn btn-success w-100 rounded-pill py-2.5 mb-3 fw-semibold">
                        <i class="fa-solid fa-users me-2"></i> All Users Directory
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary w-100 rounded-pill py-2.5 fw-semibold">
                        <i class="fa-solid fa-house me-2"></i> Teacher Dashboard
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>