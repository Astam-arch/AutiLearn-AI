<?php
// student/link_parent.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/student_guard.php';

$studentId = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parentEmail = filter_input(INPUT_POST, 'parent_email', FILTER_VALIDATE_EMAIL);

    if (!$parentEmail) {
        $error = "Please enter a valid email address.";
    } else {
        // 1. Check if parent exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'parent'");
        $stmt->execute([$parentEmail]);
        $parent = $stmt->fetch();

        if ($parent) {
            // 2. Link existing parent and activate student
            $update = $pdo->prepare("
                UPDATE users 
                SET parent_id = ?, account_status = 'active' 
                WHERE id = ?
            ");
            $update->execute([$parent['id'], $studentId]);

            // Update active session values
            $_SESSION['parent_id'] = $parent['id'];
            $_SESSION['account_status'] = 'active';

            header("Location: dashboard.php?msg=linked_success");
            exit;
        } else {
            // 3. Parent account does not exist -> Send Invitation Email
            // Insert into pending_invites table or trigger PHPMailer invitation
            $error = "No parent account found with that email. An invite link has been sent to them.";
            // sendParentInviteEmail($parentEmail, $studentId);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Mandatory Parent Link | AutiLearn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 p-4 text-center">
                <div class="mb-3">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Action Required</span>
                </div>
                <h3 class="fw-bold">Link Your Parent Account</h3>
                <p class="text-muted small">
                    To access your learning lessons, you must connect your account to a parent or guardian.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger small"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="link_parent.php" class="text-start mt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent's Email Address</label>
                        <input type="email" name="parent_email" class="form-control" placeholder="parent@example.com" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold py-2">
                        Link Account & Continue
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>