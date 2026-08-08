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
            // Check if email belongs to another user
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $checkEmail->execute([$email, $parentId]);
            
            if ($checkEmail->fetch()) {
                $flashMessage = 'The email address is already taken by another account.';
                $flashType    = 'danger';
            } else {
                // Split full_name into first_name and last_name
                $parts     = explode(' ', $fullName, 2);
                $firstName = $parts[0] ?? '';
                $lastName  = $parts[1] ?? '';

                $updateParent = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, full_name = ?, email = ? WHERE id = ?");
                if ($updateParent->execute([$firstName, $lastName, $fullName, $email, $parentId])) {
                    // Update session variables
                    $_SESSION['full_name']  = $fullName;
                    $_SESSION['first_name'] = $firstName;
                    $_SESSION['last_name']  = $lastName;
                    $_SESSION['email']      = $email;

                    // Synchronize parent_email for linked children in users table
                    $syncChild = $pdo->prepare("UPDATE users SET parent_email = ? WHERE parent_id = ?");
                    $syncChild->execute([$email, $parentId]);

                    $flashMessage = 'Profile information updated successfully!';
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
            // Retrieve current password hash from DB
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
    // Action C: Update Child Learner Settings
    elseif ($action === 'update_child') {
        $childId   = intval($_POST['child_id'] ?? 0);
        $childName = trim($_POST['child_name'] ?? '');

        if ($childId > 0 && !empty($childName)) {
            $parts          = explode(' ', $childName, 2);
            $childFirstName = $parts[0] ?? '';
            $childLastName  = $parts[1] ?? '';

            // Update child name in users table (ensuring child belongs to logged-in parent)
            $updateChild = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, full_name = ? WHERE id = ? AND parent_id = ?");
            if ($updateChild->execute([$childFirstName, $childLastName, $childName, $childId, $parentId])) {
                $flashMessage = 'Child learner settings updated successfully!';
                $flashType    = 'success';
            } else {
                $flashMessage = 'Could not update child details.';
                $flashType    = 'danger';
            }
        } else {
            $flashMessage = 'Please select a valid student and enter a name.';
            $flashType    = 'warning';
        }
    } 
    // Action D: Notification Settings
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

// 3. Fetch Linked Child Accounts from DB (where parent_id = current logged in parent)
$childStmt = $pdo->prepare("SELECT id, first_name, last_name, full_name, email, created_at FROM users WHERE parent_id = ? AND role = 'student'");
$childStmt->execute([$parentId]);
$linkedChildren = $childStmt->fetchAll(PDO::FETCH_ASSOC);

// Primary linked child data
$primaryChild = $linkedChildren[0] ?? null;
$primaryChildName = $primaryChild ? (!empty($primaryChild['full_name']) ? $primaryChild['full_name'] : trim($primaryChild['first_name'] . ' ' . $primaryChild['last_name'])) : 'No Linked Child';
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
        <p class="text-muted fs-5">Manage your parent account, child learner preferences, and notification reports.</p>
    </div>

    <!-- FLASH FEEDBACK BANNER -->
    <?php if (!empty($flashMessage)): ?>
        <div class="alert alert-<?php echo $flashType; ?> alert-dismissible fade show rounded-4 mb-4 fw-semibold shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2 fs-5"></i> <?php echo htmlspecialchars($flashMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- SIDEBAR NAVIGATION TABS -->
        <div class="col-lg-4">
            <div class="profile-card text-center mb-4">
                <div class="avatar-circle">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <h4 class="brand-font fw-bold text-dark mb-1"><?php echo htmlspecialchars($parentName); ?></h4>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold mb-3">Primary Guardian</span>
                
                <div class="border-top pt-3 text-start">
                    <div class="d-flex align-items-center justify-content-between text-muted small mb-2">
                        <span><i class="fa-solid fa-child me-2 text-primary"></i>Linked Child:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($primaryChildName); ?></strong>
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
                        <i class="fa-solid fa-child-reaching me-2 fs-5"></i> Child Learner Profile
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

                <!-- TAB 2: CHILD LEARNER PROFILE & ACCESSIBILITY -->
                <div class="tab-pane fade" id="v-pills-child" role="tabpanel">
                    <div class="profile-card">
                        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                            <h4 class="brand-font fw-bold text-dark m-0">Child Learner Settings</h4>
                            <a href="add_child.php" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold">
                                <i class="fa-solid fa-user-plus me-1"></i> Link Another Child
                            </a>
                        </div>

                        <?php if (empty($linkedChildren)): ?>
                            <div class="alert alert-info rounded-4 text-center py-4">
                                <i class="fa-solid fa-circle-info fs-3 d-block mb-2 text-primary"></i>
                                <h5>No child profiles linked yet</h5>
                                <p class="text-muted small mb-3">Link your child's student account to view and manage their settings here.</p>
                                <a href="add_child.php" class="btn btn-primary rounded-pill px-4">Link Student Account</a>
                            </div>
                        <?php else: ?>
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="update_child">

                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Select Student Account</label>
                                        <select name="child_id" class="form-select" id="childSelect" onchange="updateChildNameInput(this)">
                                            <?php foreach ($linkedChildren as $child): ?>
                                                <?php 
                                                    $cName = !empty($child['full_name']) ? $child['full_name'] : trim($child['first_name'] . ' ' . $child['last_name']);
                                                ?>
                                                <option value="<?php echo $child['id']; ?>" data-name="<?php echo htmlspecialchars($cName); ?>">
                                                    <?php echo htmlspecialchars($cName); ?> (<?php echo htmlspecialchars($child['email']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Child's Display Name</label>
                                        <input type="text" name="child_name" id="child_name_input" class="form-control" value="<?php echo htmlspecialchars($primaryChildName); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">AI Voice Speed (Speech Lab)</label>
                                        <select name="speech_rate" class="form-select">
                                            <option value="0.75">0.75x (Slower & Very Clear)</option>
                                            <option value="0.85" selected>0.85x (Recommended Natural)</option>
                                            <option value="1.0">1.0x (Standard Normal Speed)</option>
                                        </select>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <h5 class="brand-font fw-bold text-dark mb-3"><i class="fa-solid fa-eye text-primary me-2"></i>Sensory & Visual Preferences</h5>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="reducedMotion" checked>
                                            <label class="form-check-label fw-semibold text-dark" for="reducedMotion">
                                                Enable Reduced Motion & Soft Transitions
                                            </label>
                                            <div class="small text-muted">Minimizes flashing animations across games and sensory modules.</div>
                                        </div>

                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="autoAudioFeedback" checked>
                                            <label class="form-check-label fw-semibold text-dark" for="autoAudioFeedback">
                                                Auto-read word cards aloud on tap
                                            </label>
                                            <div class="small text-muted">Plays natural audio pronunciation whenever visual cards are selected.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Child Profile
                                    </button>
                                </div>
                            </form>
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

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function updateChildNameInput(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const name = selectedOption.getAttribute('data-name');
    const input = document.getElementById('child_name_input');
    if (input && name) {
        input.value = name;
    }
}
</script>
</body>
</html>