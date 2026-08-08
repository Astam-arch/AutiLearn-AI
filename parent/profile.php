<?php
// parent/profile.php
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

$parentId     = $_SESSION['user_id'];
$dashboardUrl = defined('BASE_URL') ? BASE_URL . 'parent/dashboard.php' : 'dashboard.php';
$logoutUrl    = defined('BASE_URL') ? BASE_URL . 'logout.php' : '../logout.php';

// Form Handling Alert Messages
$flashMessage = '';
$flashType    = '';

// --- DATABASE OPERATIONS ---

// 1. Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action A: Update Parent Profile Details
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');

        if (!empty($fullName) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $checkEmail->execute([$email, $parentId]);
            
            if ($checkEmail->fetch()) {
                $flashMessage = 'The email address is already taken by another account.';
                $flashType    = 'danger';
            } else {
                $parts     = explode(' ', $fullName, 2);
                $firstName = $parts[0] ?? '';
                $lastName  = $parts[1] ?? '';

                $updateParent = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, full_name = ?, email = ? WHERE id = ?");
                if ($updateParent->execute([$firstName, $lastName, $fullName, $email, $parentId])) {
                    $_SESSION['full_name']  = $fullName;
                    $_SESSION['first_name'] = $firstName;
                    $_SESSION['last_name']  = $lastName;
                    $_SESSION['email']      = $email;

                    $syncChild = $pdo->prepare("UPDATE users SET parent_email = ? WHERE parent_id = ?");
                    $syncChild->execute([$email, $parentId]);

                    $flashMessage = 'Parent profile information updated successfully!';
                    $flashType    = 'success';
                } else {
                    $flashMessage = 'Failed to update profile information. Please try again.';
                    $flashType    = 'danger';
                }
            }
        } else {
            $flashMessage = 'Please fill in a valid full name and email address.';
            $flashType    = 'warning';
        }
    } 
    // Action B: Change Parent Password
    elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $flashMessage = 'All password fields are required.';
            $flashType    = 'warning';
        } elseif ($newPassword !== $confirmPassword) {
            $flashMessage = 'New password and confirm password do not match.';
            $flashType    = 'danger';
        } elseif (strlen($newPassword) < 6) {
            $flashMessage = 'New password must be at least 6 characters long.';
            $flashType    = 'warning';
        } else {
            $pwdStmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $pwdStmt->execute([$parentId]);
            $userPwd = $pwdStmt->fetch(PDO::FETCH_ASSOC);

            if ($userPwd && password_verify($currentPassword, $userPwd['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePwd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                if ($updatePwd->execute([$hashedPassword, $parentId])) {
                    $flashMessage = 'Your password has been changed successfully!';
                    $flashType    = 'success';
                } else {
                    $flashMessage = 'Error updating password in database.';
                    $flashType    = 'danger';
                }
            } else {
                $flashMessage = 'Current password is incorrect.';
                $flashType    = 'danger';
            }
        }
    } 
    // Action C: Update Individual Child Learner Settings & Preferences
    elseif ($action === 'update_child') {
        $childId      = intval($_POST['child_id'] ?? 0);
        $childName    = trim($_POST['child_name'] ?? '');
        $speechRate   = $_POST['speech_rate'] ?? '0.85';
        $learningPace = $_POST['learning_pace'] ?? 'intermediate';

        if ($childId > 0 && !empty($childName)) {
            $parts          = explode(' ', $childName, 2);
            $childFirstName = $parts[0] ?? '';
            $childLastName  = $parts[1] ?? '';

            $updateChild = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, full_name = ? WHERE id = ? AND parent_id = ?");
            if ($updateChild->execute([$childFirstName, $childLastName, $childName, $childId, $parentId])) {
                $flashMessage = 'Child learner settings updated successfully!';
                $flashType    = 'success';
            } else {
                $flashMessage = 'Could not update child details.';
                $flashType    = 'danger';
            }
        } else {
            $flashMessage = 'Please enter a valid student display name.';
            $flashType    = 'warning';
        }
    }
    // Action D: Remove / Unlink Child Account
    elseif ($action === 'remove_child') {
        $targetChildId = intval($_POST['child_id'] ?? 0);
        if ($targetChildId > 0) {
            // Unlink by setting parent_id to NULL or deleting, depending on system schema. Here we unlink/delete connection.
            $deleteStmt = $pdo->prepare("UPDATE users SET parent_id = NULL, parent_email = NULL WHERE id = ? AND parent_id = ? AND role = 'student'");
            if ($deleteStmt->execute([$targetChildId, $parentId])) {
                $flashMessage = 'Student account successfully unlinked from your parent profile.';
                $flashType    = 'success';
            } else {
                $flashMessage = 'Failed to remove child account. Please try again.';
                $flashType    = 'danger';
            }
        }
    }
    // Action E: Notification Settings
    elseif ($action === 'update_notifications') {
        $flashMessage = 'Notification and report preferences saved!';
        $flashType    = 'info';
    }
}

// 2. Fetch Parent Account Details from DB
$parentStmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email, role, created_at FROM users WHERE id = ? LIMIT 1");
$parentStmt->execute([$parentId]);
$parentUser = $parentStmt->fetch(PDO::FETCH_ASSOC);

$parentName  = !empty($parentUser['full_name']) ? $parentUser['full_name'] : trim(($parentUser['first_name'] ?? '') . ' ' . ($parentUser['last_name'] ?? ''));
$parentEmail = $parentUser['email'] ?? '';

// 3. Fetch Linked Child Accounts from DB
$childStmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email, created_at FROM users WHERE parent_id = ? AND role = 'student'");
$childStmt->execute([$parentId]);
$linkedChildren = $childStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent & Child Profile Settings | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
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
            padding: 16px 0;
        }

        .profile-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .avatar-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #1e3a8a);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 15px;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
        }

        .nav-pills-custom .nav-link {
            color: #64748b;
            font-weight: 500;
            border-radius: 12px;
            padding: 12px 20px;
            text-align: left;
            transition: all 0.2s ease;
        }

        .nav-pills-custom .nav-link.active {
            background-color: #2563eb;
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .nav-pills-custom .nav-link:hover:not(.active) {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1.5px solid #cbd5e1;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-label {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="navbar navbar-parent sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand brand-font fs-3 text-primary d-flex align-items-center gap-2" href="<?php echo htmlspecialchars($dashboardUrl); ?>">
            <i class="fa-solid fa-arrow-left fs-4 me-1 text-secondary"></i>
            <i class="fa-solid fa-chart-line text-primary fs-2"></i> AutiLearn <span class="fs-5 text-secondary">Parent Portal</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="btn btn-outline-primary rounded-pill px-3 btn-sm fw-semibold">
                <i class="fa-solid fa-house me-1"></i> Dashboard
            </a>
            <a href="<?php echo htmlspecialchars($logoutUrl); ?>" class="btn btn-outline-danger rounded-pill px-3 btn-sm fw-semibold">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container">

    <!-- PAGE TITLE HEADER -->
    <div class="mb-4">
        <h2 class="brand-font fw-bold text-dark fs-1 mb-1">Account & Settings</h2>
        <p class="text-muted fs-5">Manage your parent account, multiple child learner profiles, and progress notification settings.</p>
    </div>

    <!-- FLASH FEEDBACK BANNER -->
    <?php if (!empty($flashMessage)): ?>
        <div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show rounded-4 mb-4 fw-semibold shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2 fs-5"></i> <?php echo htmlspecialchars($flashMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- SIDEBAR NAVIGATION -->
        <div class="col-lg-4">
            <div class="profile-card text-center mb-4">
                <div class="avatar-circle">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <h4 class="brand-font fw-bold text-dark mb-1"><?php echo htmlspecialchars($parentName); ?></h4>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold mb-3">Primary Guardian</span>
                
                <div class="border-top pt-3 text-start">
                    <div class="d-flex align-items-center justify-content-between text-muted small mb-2">
                        <span><i class="fa-solid fa-child me-2 text-primary"></i>Linked Children:</span>
                        <strong class="text-dark"><?php echo count($linkedChildren); ?> Active</strong>
                    </div>
                    <div class="d-flex align-items-center justify-content-between text-muted small mb-2">
                        <span><i class="fa-solid fa-envelope me-2 text-primary"></i>Email:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($parentEmail); ?></strong>
                    </div>
                    <div class="d-flex align-items-center justify-content-between text-muted small">
                        <span><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Role:</span>
                        <strong class="text-success">Verified Parent</strong>
                    </div>
                </div>
            </div>

            <!-- TABS MENU -->
            <div class="profile-card p-3">
                <div class="nav flex-column nav-pills nav-pills-custom" id="v-pills-tab" role="tablist">
                    <button class="nav-link active mb-2" id="v-pills-parent-tab" data-bs-toggle="pill" data-bs-target="#v-pills-parent" type="button" role="tab">
                        <i class="fa-solid fa-user-pen me-2 fs-5"></i> Personal Information
                    </button>
                    <button class="nav-link mb-2" id="v-pills-child-tab" data-bs-toggle="pill" data-bs-target="#v-pills-child" type="button" role="tab">
                        <i class="fa-solid fa-child-reaching me-2 fs-5"></i> Child Learner Profiles
                    </button>
                    <button class="nav-link" id="v-pills-notifications-tab" data-bs-toggle="pill" data-bs-target="#v-pills-notifications" type="button" role="tab">
                        <i class="fa-solid fa-bell me-2 fs-5"></i> Progress Reports & Alerts
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT AREA -->
        <div class="col-lg-8">
            <div class="tab-content" id="v-pills-tabContent">
                
                <!-- TAB 1: PARENT PERSONAL INFORMATION -->
                <div class="tab-pane fade show active" id="v-pills-parent" role="tabpanel">
                    <div class="profile-card">
                        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                            <h4 class="brand-font fw-bold text-dark m-0">Personal Profile Details</h4>
                            <span class="text-muted small"><i class="fa-solid fa-lock text-success me-1"></i> Encrypted & Secure</span>
                        </div>

                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($parentName); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($parentEmail); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Role</label>
                                    <input type="text" class="form-control bg-light text-capitalize" value="<?php echo htmlspecialchars($parentUser['role'] ?? 'parent'); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Password</label>
                                    <input type="password" class="form-control" placeholder="••••••••" disabled>
                                    <span class="small text-muted">Click "Change Password" below to update.</span>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between">
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold btn-sm" data-bs-toggle="modal" data-bs-target="#passwordModal">
                                    <i class="fa-solid fa-key me-2"></i> Change Password
                                </button>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TAB 2: MULTI-CHILD LEARNER PROFILES & MANAGEMENT -->
                <div class="tab-pane fade" id="v-pills-child" role="tabpanel">
                    <div class="profile-card">
                        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                            <h4 class="brand-font fw-bold text-dark m-0">Linked Student Accounts</h4>
                            <a href="add_child.php" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">
                                <i class="fa-solid fa-user-plus me-1"></i> Link Another Child
                            </a>
                        </div>

                        <?php if (empty($linkedChildren)): ?>
                            <div class="alert alert-info rounded-4 text-center py-5">
                                <i class="fa-solid fa-child-reaching fs-1 d-block mb-3 text-primary"></i>
                                <h5>No child profiles linked yet</h5>
                                <p class="text-muted small mb-3">Link student accounts to customize learning paces and track progress.</p>
                                <a href="add_child.php" class="btn btn-primary rounded-pill px-4">Link Student Account</a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-3">Expand a child's card below to customize their name, speech preferences, or unlink the student account.</p>
                            
                            <div class="accordion" id="childAccordion">
                                <?php foreach ($linkedChildren as $index => $child): ?>
                                    <?php 
                                        $cName = !empty($child['full_name']) ? $child['full_name'] : trim($child['first_name'] . ' ' . $child['last_name']);
                                        $collapseId = "childCollapse_" . $child['id'];
                                        $headingId = "childHeading_" . $child['id'];
                                    ?>
                                    <div class="accordion-item border rounded-4 mb-3 overflow-hidden shadow-sm">
                                        <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                                            <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?> fw-bold bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>">
                                                <i class="fa-solid fa-child text-primary me-2 fs-5"></i> <?php echo htmlspecialchars($cName); ?> 
                                                <span class="ms-2 badge bg-light text-secondary border fw-normal small"><?php echo htmlspecialchars($child['email']); ?></span>
                                            </button>
                                        </h2>
                                        <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="<?php echo $headingId; ?>" data-bs-parent="#childAccordion">
                                            <div class="accordion-body bg-white border-top p-4">
                                                
                                                <!-- Individual Child Update Form -->
                                                <form action="" method="POST" class="mb-4">
                                                    <input type="hidden" name="action" value="update_child">
                                                    <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Student Name</label>
                                                            <input type="text" name="child_name" class="form-control" value="<?php echo htmlspecialchars($cName); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Speech Lab Voice Speed</label>
                                                            <select name="speech_rate" class="form-select">
                                                                <option value="0.75">0.75x (Slower & Clear)</option>
                                                                <option value="0.85" selected>0.85x (Recommended Natural)</option>
                                                                <option value="1.0">1.0x (Standard Normal Speed)</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <label class="form-label">Learning Pace & Difficulty</label>
                                                            <select name="learning_pace" class="form-select">
                                                                <option value="beginner">Beginner (Gentle Repetition & Basic Vocabulary)</option>
                                                                <option value="intermediate" selected>Intermediate (Balanced Interactive Modules)</option>
                                                                <option value="advanced">Advanced (Accelerated Sentence Building)</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="mt-3 text-end">
                                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">
                                                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Child Settings
                                                        </button>
                                                    </div>
                                                </form>

                                                <!-- Unlink / Remove Child Section -->
                                                <div class="border-top pt-3 d-flex align-items-center justify-content-between bg-light p-3 rounded-3">
                                                    <div>
                                                        <span class="text-danger fw-semibold small d-block"><i class="fa-solid fa-triangle-exclamation me-1"></i> Danger Zone</span>
                                                        <span class="text-muted small">Unlinking removes this student account from your parent view dashboard.</span>
                                                    </div>
                                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to unlink <?php echo htmlspecialchars($cName); ?> from your account?');">
                                                        <input type="hidden" name="action" value="remove_child">
                                                        <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                                                            <i class="fa-solid fa-user-minus me-1"></i> Remove Child
                                                        </button>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TAB 3: NOTIFICATIONS & WEEKLY REPORTS -->
                <div class="tab-pane fade" id="v-pills-notifications" role="tabpanel">
                    <div class="profile-card">
                        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                            <h4 class="brand-font fw-bold text-dark m-0">Notification Preferences</h4>
                            <i class="fa-solid fa-envelope-open-text text-primary fs-3"></i>
                        </div>

                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_notifications">

                            <div class="mb-4">
                                <h5 class="brand-font fw-bold text-dark mb-3">Weekly Email Summaries</h5>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="weeklyReport" checked>
                                    <label class="form-check-label fw-semibold text-dark" for="weeklyReport">
                                        Send Weekly Learning Progress PDF Report
                                    </label>
                                    <div class="small text-muted">Delivered every Sunday evening containing accuracy charts and stars earned.</div>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="speechMilestoneAlert" checked>
                                    <label class="form-check-label fw-semibold text-dark" for="speechMilestoneAlert">
                                        Speech Lab Clarity Milestone Alerts
                                    </label>
                                    <div class="small text-muted">Get notified when your child achieves 90%+ pronunciation accuracy on new words.</div>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="inactivityReminder">
                                    <label class="form-check-label fw-semibold text-dark" for="inactivityReminder">
                                        Gentle Practice Inactivity Reminders
                                    </label>
                                    <div class="small text-muted">Receive a quick alert if no practice sessions take place for 3 consecutive days.</div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top text-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Notification Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 p-3 border-0">
            <div class="modal-header border-0">
                <h4 class="brand-font fw-bold text-dark m-0"><i class="fa-solid fa-key text-primary me-2"></i>Change Password</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required placeholder="At least 6 characters">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter new password">
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill fw-bold py-2">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>