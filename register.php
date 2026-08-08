<?php
// register.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName   = trim($_POST['first_name']);
    $lastName    = trim($_POST['last_name']);
    $email       = trim($_POST['email']);
    $password    = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role        = $_POST['role']; // 'student', 'parent', or 'teacher'
    $parentEmail = trim($_POST['parent_email'] ?? '');

    if ($role === 'student' && empty($parentEmail)) {
        $error = "Students must provide a parent email address.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "An account with this email already exists.";
        } else {
            $parentId = null;

            if ($role === 'student') {
                // Check if parent already has an account
                $parentStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'parent'");
                $parentStmt->execute([$parentEmail]);
                $parent = $parentStmt->fetch();
                if ($parent) {
                    $parentId = $parent['id'];
                }
            }

            // Insert User (Student, Parent, or Teacher)
            $insert = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, role, parent_id, parent_email) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([$firstName, $lastName, $email, $password, $role, $parentId, $parentEmail]);
            $newUserId = $pdo->lastInsertId();

            // Auto-Link Logic: If a PARENT just registered, link any existing students waiting for this email!
            if ($role === 'parent') {
                $linkPending = $pdo->prepare("
                    UPDATE users 
                    SET parent_id = ? 
                    WHERE role = 'student' AND parent_email = ?
                ");
                $linkPending->execute([$newUserId, $email]);
            }

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
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
        }
        h1, h2, h3, h4, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }
        .card {
            border-radius: var(--card-radius);
            border: 2px solid #bbf7d0 !important;
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.08) !important;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <i class="fa-solid fa-user-plus text-success fs-1 mb-2"></i>
                    <h3 class="fw-bold brand-font text-success">Create Account</h3>
                    <p class="text-secondary small">Join our interactive learning platform</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger small rounded-3"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label fw-semibold small">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label fw-semibold small">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">I am registering as a:</label>
                        <select name="role" id="roleSelect" class="form-select" onchange="toggleParentEmail()" required>
                            <option value="student">Student</option>
                            <option value="parent">Parent</option>
                            <option value="teacher">Teacher / Instructor</option>
                        </select>
                    </div>

                    <!-- Input for Parent Email (Shown ONLY for Students) -->
                    <div class="mb-3" id="parentEmailGroup">
                        <label class="form-label fw-semibold small text-success">Parent's Email Address</label>
                        <input type="email" name="parent_email" id="parentEmailInput" class="form-control" placeholder="parent@example.com" required>
                        <div class="form-text small">Your parent will use this email to view your learning progress.</div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-3 shadow-sm mt-2">
                        <i class="fa-solid fa-user-check me-2"></i> Create Account
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-secondary small mb-0">
                        Already have an account? 
                        <a href="login.php" class="text-success fw-bold text-decoration-none">Log in here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleParentEmail() {
    const role = document.getElementById('roleSelect').value;
    const parentGroup = document.getElementById('parentEmailGroup');
    const parentInput = document.getElementById('parentEmailInput');
    
    if (role === 'student') {
        parentGroup.style.display = 'block';
        parentInput.required = true;
    } else {
        parentGroup.style.display = 'none';
        parentInput.required = false;
    }
}
</script>
</body>
</html>