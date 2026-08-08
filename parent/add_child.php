<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = '';
$parentId = $_SESSION['user_id'];

// Fetch the logged-in parent's email address
$parentStmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
$parentStmt->execute([$parentId]);
$parentUser = $parentStmt->fetch(PDO::FETCH_ASSOC);
$parentEmail = $parentUser['email'] ?? '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $childEmail = trim($_POST['child_email'] ?? '');

    if (!empty($childEmail) && filter_var($childEmail, FILTER_VALIDATE_EMAIL)) {
        
        // 1. Find the student in the 'users' table by email
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, role, parent_id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$childEmail]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Optional: Ensure the user being linked is a student
            if (isset($student['role']) && $student['role'] !== 'student') {
                $message = '<div class="alert alert-warning">The email entered belongs to a account with role: <strong>' . htmlspecialchars($student['role']) . '</strong>. Only student accounts can be linked.</div>';
            } 
            // 2. Check if already linked to this parent
            elseif (!empty($student['parent_id']) && $student['parent_id'] == $parentId) {
                $message = '<div class="alert alert-info">This student profile is already linked to your account. <a href="dashboard.php">Go to Dashboard</a></div>';
            } 
            // 3. Check if linked to a different parent
            elseif (!empty($student['parent_id']) && $student['parent_id'] != $parentId) {
                $message = '<div class="alert alert-danger">This student is already linked to another parent account.</div>';
            } 
            // 4. Link the student to the current parent in the 'users' table
            else {
                $updateStmt = $pdo->prepare("UPDATE users SET parent_id = ?, parent_email = ? WHERE id = ?");
                
                if ($updateStmt->execute([$parentId, $parentEmail, $student['id']])) {
                    $_SESSION['selected_child_id'] = $student['id'];
                    $message = '<div class="alert alert-success">Student profile linked successfully! <a href="dashboard.php">Go to Dashboard</a></div>';
                } else {
                    $message = '<div class="alert alert-danger">Database error. Could not link profile.</div>';
                }
            }
        } else {
            $message = '<div class="alert alert-warning">No registered user found with email: ' . htmlspecialchars($childEmail) . '</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Please enter a valid email address.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <title>Link Student Profile</title>
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <div class="card p-4 shadow-sm border-0 rounded-4">
        <div class="text-center mb-3">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-2" style="width: 50px; height: 50px;">
                <i class="fa-solid fa-link fs-4"></i>
            </div>
            <h3 class="fw-bold mb-1">Link Student Profile</h3>
            <p class="text-muted small">Enter your child's email address registered in the system to link their account.</p>
        </div>

        <?php echo $message; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold small">Student's Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" name="child_email" class="form-control" placeholder="admin@gmail.com" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2"><i class="fa-solid fa-user-plus me-1"></i> Link Student</button>
            <a href="dashboard.php" class="btn btn-link w-100 mt-2 text-decoration-none text-muted">Back to Dashboard</a>
        </form>
    </div>
</div>
</body>
</html>