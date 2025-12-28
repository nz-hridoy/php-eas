<?php
require_once 'config/database.php';

// Redirect if already logged in - ALWAYS redirect, no matter what
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    
    // Redirect based on role
    if ($role === 'admin' || $role === 'manager') {
        header('Location: admin/dashboard.php');
        exit();
    } elseif ($role === 'employee') {
        header('Location: user/dashboard.php');
        exit();
    } else {
        // Unknown role, redirect to user dashboard as fallback
        header('Location: user/dashboard.php');
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['username'] ?? ''); // Form field is 'username' but we treat it as email
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, email, password, role, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Verify password using password_verify (bcrypt hash)
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['name'];

                // Update last login time
                $updateStmt = $conn->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
                $updateStmt->close();

                // Redirect based on role
                if ($user['role'] === 'admin' || $user['role'] === 'manager') {
                    header('Location: admin/dashboard.php');
                } elseif ($user['role'] === 'employee') {
                    header('Location: user/dashboard.php');
                } else {
                    // Fallback redirect to user dashboard
                    header('Location: user/dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }

        $stmt->close();
        $conn->close();
    } else {
        $error = 'Please fill in all fields';
    }
}

// Final check before rendering HTML - ensure no logged-in user sees the login page
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin' || $role === 'manager') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NzCoding</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
        <div class="login-container">
            <div class="login-card">
                <h1 class="text-center mb-4 fw-bold" style="letter-spacing:2px;">NzCoding</h1>
                <p class="text-center text-muted mb-4">Employee Attendance System</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" data-validate="true" novalidate autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label">Email</label>
                        <input type="email" class="form-control" id="username" name="username" required data-error-required="Email is required" autocomplete="off" placeholder="Enter your email">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required data-error-required="Password is required" autocomplete="off" placeholder="Enter your password">
                    </div>

                    <button type="submit" class="btn btn-secondary w-100 mb-3">Login</button>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-muted small mb-2">Demo Credentials:</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" style="min-width: 90px;" onclick="fillCredentials('admin@example.com', '12345678')">Admin</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" style="min-width: 90px;" onclick="fillCredentials('user@example.com', '12345678')">Employee</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center">
        <p class="text-muted mb-0">Made with <span class="heart">❤️</span> by nzcoding</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/validation.js"></script>
    <script src="assets/js/autocomplete-off.js"></script>
    <script>
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>

