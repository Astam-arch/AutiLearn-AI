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

$parentName   = $_SESSION['full_name'] ?? 'Sarah Johnson';
$parentEmail  = $_SESSION['email'] ?? 'sarah.johnson@example.com';
$parentPhone  = $_SESSION['phone'] ?? '+1 (555) 234-5678';
$dashboardUrl = defined('BASE_URL') ? BASE_URL . 'parent/dashboard.php' : 'dashboard.php';
$logoutUrl    = defined('BASE_URL') ? BASE_URL . 'logout.php' : '../logout.php';

// Form Handling Alert Messages
$flashMessage = '';
$flashType    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $parentName  = trim($_POST['full_name'] ?? $parentName);
        $parentEmail = trim($_POST['email'] ?? $parentEmail);
        $parentPhone = trim($_POST['phone'] ?? $parentPhone);
        
        // Update session
        $_SESSION['full_name'] = $parentName;
        $_SESSION['email']     = $parentEmail;
        $_SESSION['phone']     = $parentPhone;

        $flashMessage = 'Profile information updated successfully!';
        $flashType    = 'success';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_child') {
        $flashMessage = 'Child learner settings and sensory preferences saved!';
        $flashType    = 'success';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_notifications') {
        $flashMessage = 'Notification and weekly progress summary preferences saved!';
        $flashType    = 'info';
    }
}

// Child Mock Data (Or fetched from DB)
$childData = [
    'name'           => 'Alex Johnson',
    'dob'            => '2018-05-14',
    'age'            => '8 Years',
    'pin'            => '1234',
    'speech_rate'    => '0.85',
    'sensory_theme'  => 'teal',
    'reduced_motion' => true
];
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
                        <strong class="text-dark"><?php echo htmlspecialchars($childData['name']); ?></strong>
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
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($parentPhone); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••" disabled>
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
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">Active Student</span>
                        </div>

                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update_child">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Child's Name</label>
                                    <input type="text" name="child_name" class="form-control" value="<?php echo htmlspecialchars($childData['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="child_dob" class="form-control" value="<?php echo htmlspecialchars($childData['dob']); ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Quick-Switch Security PIN (4 Digits)</label>
                                    <input type="text" name="child_pin" class="form-control" maxlength="4" value="<?php echo htmlspecialchars($childData['pin']); ?>">
                                    <span class="small text-muted">Used to exit student mode back to parent portal.</span>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">AI Voice Speed (Speech Lab)</label>
                                    <select name="speech_rate" class="form-select">
                                        <option value="0.75" <?php echo ($childData['speech_rate'] == '0.75') ? 'selected' : ''; ?>>0.75x (Slower & Very Clear)</option>
                                        <option value="0.85" <?php echo ($childData['speech_rate'] == '0.85') ? 'selected' : ''; ?>>0.85x (Recommended Natural)</option>
                                        <option value="1.0" <?php echo ($childData['speech_rate'] == '1.0') ? 'selected' : ''; ?>>1.0x (Standard Normal Speed)</option>
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
                <form id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" required placeholder="Enter current password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" required placeholder="At least 8 characters">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" required placeholder="Re-enter new password">
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
    document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Password updated successfully!');
        const modal = bootstrap.Modal.getInstance(document.getElementById('passwordModal'));
        if (modal) modal.hide();
    });
</script>
</body>
</html>