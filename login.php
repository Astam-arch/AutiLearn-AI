<?php
// login.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'student';
    $baseUrl = defined('BASE_URL') ? BASE_URL : '../';
    header("Location: {$baseUrl}{$role}/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Fetch user record
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Security: Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            // Determine redirect URL using BASE_URL if available
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            
            switch ($user['role']) {
                case 'admin':
                    header("Location: {$baseUrl}admin/dashboard.php");
                    break;
                case 'teacher':
                    header("Location: {$baseUrl}teacher/dashboard.php");
                    break;
                case 'parent':
                    header("Location: {$baseUrl}parent/dashboard.php");
                    break;
                case 'student':
                default:
                    header("Location: {$baseUrl}student/dashboard.php");
                    break;
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo defined('SITE_NAME') ? SITE_NAME : 'AutiLearn AI'; ?></title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        h1, h2, h3, h4, .brand-font {
            font-family: 'Fredoka', cursive, sans-serif;
        }
        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: var(--card-radius);
            box-shadow: 0 10px 30px rgba(22, 163, 74, 0.08);
            width: 100%;
            max-width: 460px;
            border: 2px solid #bbf7d0;
        }
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(22, 163, 74, 0.15);
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="login-card">
        <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none h3 fw-bold text-success brand-font">
                <i class="fa-solid fa-brain me-1"></i> AutiLearn
            </a>
            <h4 class="mt-3 fw-bold text-dark">Welcome Back</h4>
            <p class="text-secondary small">Sign in to access your interactive portal</p>
        </div>

        <?php if (!empty($_GET['registered'])): ?>
            <div class="alert alert-success py-2 small rounded-3 mb-3 d-flex align-items-center">
                <i class="fa-solid fa-circle-check me-2"></i>
                <div>Account created successfully! Please log in.</div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small rounded-3 mb-3 d-flex align-items-center">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold small">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                    <input type="email" id="email" name="email" class="form-control form-control-lg fs-6 border-start-0" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" id="password" name="password" class="form-control form-control-lg fs-6 border-start-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold py-3 fs-6 rounded-pill shadow-sm">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top">
            <p class="small text-secondary mb-0">Don't have an account? <a href="register.php" class="text-success fw-bold text-decoration-none">Register here</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>