<?php
// teacher/students.php
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

$dbError = null;
$viewUserId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If an ID is provided, fetch a single user's details
$singleUser = null;
$usersList = [];

try {
    if (isset($pdo)) {
        if ($viewUserId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $viewUserId]);
            $singleUser = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Otherwise, fetch the full list for the directory
            $searchQuery = trim($_GET['search'] ?? '');
            $roleFilter = trim($_GET['role_filter'] ?? '');

            $sql = "
                SELECT u.id, 
                       u.first_name, 
                       u.last_name, 
                       u.full_name, 
                       u.email, 
                       u.role, 
                       u.parent_email,
                       u.created_at
                FROM users u
                WHERE 1=1
            ";

            $params = [];

            if (!empty($roleFilter)) {
                $sql .= " AND u.role = :role_filter";
                $params['role_filter'] = $roleFilter;
            }

            if (!empty($searchQuery)) {
                $sql .= " AND (u.email LIKE :s_email OR u.full_name LIKE :s_fullname OR u.first_name LIKE :s_firstname OR u.last_name LIKE :s_lastname)";
                $searchTerm = '%' . $searchQuery . '%';
                $params['s_email'] = $searchTerm;
                $params['s_fullname'] = $searchTerm;
                $params['s_firstname'] = $searchTerm;
                $params['s_lastname'] = $searchTerm;
            }

            $sql .= " ORDER BY u.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title><?php echo $viewUserId > 0 ? 'User Profile' : 'All Users Directory'; ?> | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
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
        <a class="navbar-brand brand-font fs-3 text-success d-flex align-items-center gap-2" href="<?php echo $viewUserId > 0 ? 'students.php' : htmlspecialchars($dashboardUrl); ?>">
            <i class="fa-solid <?php echo $viewUserId > 0 ? 'fa-arrow-left' : 'fa-chalkboard-user'; ?> text-success fs-2"></i> 
            <?php echo $viewUserId > 0 ? 'Back to Directory' : 'Teacher Portal'; ?>
        </a>
        <div class="d-flex align-items-center gap-3">
            <?php if ($viewUserId === 0): ?>
                <a href="speech_logs.php" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                    <i class="fa-solid fa-microphone-lines me-1"></i> Speech Logs
                </a>
            <?php endif; ?>
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
    
    <!-- DATABASE ERROR NOTIFICATION -->
    <?php if ($dbError): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <strong>System Notice:</strong> <?php echo htmlspecialchars($dbError); ?>
        </div>
    <?php endif; ?>

    <?php if ($viewUserId > 0): ?>
        <!-- ================= SINGLE USER PROFILE VIEW ================= -->
        <?php if (!$singleUser): ?>
            <div class="dashboard-section text-center py-5">
                <i class="fa-solid fa-user-slash text-secondary fs-1 mb-3"></i>
                <h3 class="brand-font text-dark">User Not Found</h3>
                <p class="text-muted">The profile you are trying to view does not exist or has been removed.</p>
                <a href="students.php" class="btn btn-success rounded-pill px-4 mt-3">Return to Directory</a>
            </div>
        <?php else: ?>
            <?php 
                $displayName = trim($singleUser['full_name'] ?? '');
                if (empty($displayName)) {
                    $displayName = trim(($singleUser['first_name'] ?? '') . ' ' . ($singleUser['last_name'] ?? ''));
                }
                if (empty($displayName)) {
                    $displayName = 'User #' . $singleUser['id'];
                }

                $roleVal = strtolower($singleUser['role'] ?? 'user');
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
                                <p class="text-secondary mb-0"><i class="fa-regular fa-envelope me-1"></i> <?php echo htmlspecialchars($singleUser['email'] ?? 'No email provided'); ?></p>
                            </div>
                        </div>
                        <div class="mt-3 mt-md-0">
                            <a href="students.php" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Directory
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
                                    <strong class="text-dark"><?php echo htmlspecialchars($singleUser['first_name'] ?? 'N/A'); ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-4 border">
                                    <span class="text-muted small d-block">Last Name</span>
                                    <strong class="text-dark"><?php echo htmlspecialchars($singleUser['last_name'] ?? 'N/A'); ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-4 border">
                                    <span class="text-muted small d-block">Email Address</span>
                                    <strong class="text-dark"><?php echo htmlspecialchars($singleUser['email'] ?? 'N/A'); ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded-4 border">
                                    <span class="text-muted small d-block">System Role</span>
                                    <strong class="text-dark text-uppercase"><?php echo htmlspecialchars($singleUser['role'] ?? 'N/A'); ?></strong>
                                </div>
                            </div>
                            <?php if (!empty($singleUser['parent_email'])): ?>
                            <div class="col-sm-12">
                                <div class="p-3 bg-light rounded-4 border">
                                    <span class="text-muted small d-block">Parent/Guardian Contact</span>
                                    <strong class="text-dark"><?php echo htmlspecialchars($singleUser['parent_email']); ?></strong>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="col-sm-12">
                                <div class="p-3 bg-light rounded-4 border">
                                    <span class="text-muted small d-block">Registration Date</span>
                                    <strong class="text-dark"><?php echo !empty($singleUser['created_at']) ? date('F j, Y, g:i a', strtotime($singleUser['created_at'])) : 'N/A'; ?></strong>
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
                        <?php if ($roleVal === 'student'): ?>
                            <a href="speech_logs.php" class="btn btn-success w-100 rounded-pill py-2.5 mb-3 fw-semibold">
                                <i class="fa-solid fa-microphone-lines me-2"></i> View Speech Logs
                            </a>
                        <?php endif; ?>
                        <a href="students.php" class="btn btn-outline-secondary w-100 rounded-pill py-2.5 fw-semibold">
                            <i class="fa-solid fa-users me-2"></i> All Users Directory
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- ================= FULL USERS DIRECTORY VIEW ================= -->
        
        <!-- HEADER SECTION -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="p-4 p-md-5 rounded-4 bg-white border border-success-subtle shadow-sm d-md-flex align-items-center justify-content-between">
                    <div>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-semibold fs-6 mb-2">
                            <i class="fa-solid fa-users-gear me-1"></i> User Directories
                        </span>
                        <h1 class="brand-font text-success mb-1">System Users Management</h1>
                        <p class="text-secondary mb-0">View all registered accounts (Students, Parents, Teachers, Admins), roles, and contact details.</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <span class="badge bg-light text-dark p-3 rounded-4 border fs-6">
                            <i class="fa-solid fa-user-group text-success me-2"></i> Total Users: <?php echo count($usersList); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMART SEARCH & ROLE FILTER BAR -->
        <div class="dashboard-section py-4 mb-4">
            <form method="GET" action="" class="row g-3 align-items-center">
                <div class="col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-success ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-2 shadow-none py-2" placeholder="Search by email or name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-lg-3">
                    <select name="role_filter" class="form-select shadow-none py-2">
                        <option value="">All Roles</option>
                        <option value="student" <?php echo (($_GET['role_filter'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="parent" <?php echo (($_GET['role_filter'] ?? '') === 'parent') ? 'selected' : ''; ?>>Parent</option>
                        <option value="teacher" <?php echo (($_GET['role_filter'] ?? '') === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                        <option value="admin" <?php echo (($_GET['role_filter'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1 fw-semibold rounded-pill py-2">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <?php if (!empty($_GET['search']) || !empty($_GET['role_filter'])): ?>
                        <a href="students.php" class="btn btn-outline-secondary rounded-pill py-2 px-3" title="Reset Filters">
                            <i class="fa-solid fa-rotate-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- USERS LIST TABLE -->
        <div class="dashboard-section">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-4">
                <h4 class="fw-bold text-dark mb-0 brand-font">
                    <i class="fa-solid fa-id-card text-success me-2"></i>Registered Account Records
                </h4>
                <?php if (!empty($_GET['search']) || !empty($_GET['role_filter'])): ?>
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">
                        Filters applied
                    </span>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Email Address</th>
                            <th>Role</th>
                            <th>Parent Contact</th>
                            <th>Registration Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usersList)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="py-3">
                                        <i class="fa-solid fa-user-slash text-secondary fs-2 mb-2 d-block"></i>
                                        <h5 class="fw-bold text-dark">No user accounts found</h5>
                                        <p class="small mb-0">No records matched your current query criteria. Try clearing filters.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usersList as $user): ?>
                                <?php 
                                    $displayName = trim($user['full_name'] ?? '');
                                    if (empty($displayName)) {
                                        $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                                    }
                                    if (empty($displayName)) {
                                        $displayName = 'User #' . $user['id'];
                                    }

                                    $roleVal = strtolower($user['role'] ?? 'student');
                                    $badgeClass = 'bg-secondary-subtle text-secondary';
                                    if ($roleVal === 'student') $badgeClass = 'bg-success-subtle text-success';
                                    elseif ($roleVal === 'parent') $badgeClass = 'bg-primary-subtle text-primary';
                                    elseif ($roleVal === 'teacher') $badgeClass = 'bg-warning-subtle text-warning text-dark';
                                    elseif ($roleVal === 'admin') $badgeClass = 'bg-danger-subtle text-danger';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.95rem;">
                                                <?php echo strtoupper(substr($displayName, 0, 1)); ?>
                                            </div>
                                            <div>
                                                <span class="fw-semibold text-dark d-block"><?php echo htmlspecialchars($displayName); ?></span>
                                                <small class="text-muted" style="font-size: 0.75rem;">ID: #<?php echo $user['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-secondary fw-medium">
                                            <i class="fa-regular fa-envelope me-1 text-success"></i> <?php echo htmlspecialchars($user['email'] ?? 'No email'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badgeClass; ?> text-uppercase fw-bold px-3 py-2 rounded-pill">
                                            <?php echo htmlspecialchars($user['role'] ?? 'student'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">
                                            <i class="fa-solid fa-user-shield me-1 text-primary"></i> <?php echo htmlspecialchars($user['parent_email'] ?? 'None'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark fw-semibold px-2 py-1 border">
                                            <?php echo !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="students.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                                            <i class="fa-solid fa-eye me-1"></i> View Info
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>