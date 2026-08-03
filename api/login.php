<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Agar ALREADY logged in hai, tabhi manage_data.php par bheje
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: manage_data.php");
    exit();
}

require_once 'db.php';

$error_msg = '';
$success_msg = '';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

if (time() - $_SESSION['last_attempt_time'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

// ==========================================
// 1. LOGIN LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'login')) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($_SESSION['login_attempts'] >= 5) {
        $remaining_time = ceil((900 - (time() - $_SESSION['last_attempt_time'])) / 60);
        $error_msg = "Too many failed attempts. Try again after {$remaining_time} minutes.";
    } elseif (empty($username) || empty($password)) {
        $error_msg = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    session_regenerate_id(true);
                    
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id']        = $user['id'];
                    $_SESSION['admin_username']  = $user['username'];
                    $_SESSION['login_attempts']  = 0;

                    // Direct Manage Data Page Par Redirect
                    header("Location: manage_data.php");
                    exit();
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                    $error_msg = "Invalid username or password.";
                }
            } else {
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                $error_msg = "Invalid username or password.";
            }
            $stmt->close();
        } else {
            $error_msg = "Database error. Please try again later.";
        }
    }
}

// ==========================================
// 2. CHANGE PASSWORD LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'change_password')) {
    $cp_username     = trim($_POST['cp_username'] ?? '');
    $cp_old_password = trim($_POST['cp_old_password'] ?? '');
    $cp_new_password = trim($_POST['cp_new_password'] ?? '');

    if (empty($cp_username) || empty($cp_old_password) || empty($cp_new_password)) {
        $error_msg = "Please fill in all fields to change password.";
    } elseif (strlen($cp_new_password) < 6) {
        $error_msg = "New password must be at least 6 characters long.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $cp_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                // Check if old password matches (hash or plain text)
                if (password_verify($cp_old_password, $user['password']) || $cp_old_password === $user['password']) {
                    
                    // Hash the new password securely
                    $hashed_new_password = password_hash($cp_new_password, PASSWORD_BCRYPT);

                    // Update in database
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $hashed_new_password, $user['id']);
                        if ($update_stmt->execute()) {
                            $success_msg = "Password changed successfully! You can now log in.";
                        } else {
                            $error_msg = "Failed to update password. Please try again.";
                        }
                        $update_stmt->close();
                    }
                } else {
                    $error_msg = "Incorrect old password.";
                }
            } else {
                $error_msg = "Username not found.";
            }
            $stmt->close();
        } else {
            $error_msg = "Database query error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Management System</title>
  
  <!-- Bootstrap 5 CSS & FontAwesome Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary-color: #2563eb;
      --primary-hover: #1d4ed8;
      --bg-color: #f8fafc;
      --card-bg: #ffffff;
      --text-dark: #0f172a;
      --text-muted: #64748b;
    }

    body {
      background-color: var(--bg-color);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
      padding: 20px;
    }

    .login-card {
      background: var(--card-bg);
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
      width: 100%;
      max-width: 420px;
      padding: 36px 32px;
    }

    .login-header {
      text-align: center;
      margin-bottom: 24px;
    }

    .login-header .icon-box {
      width: 52px;
      height: 52px;
      background-color: #eff6ff;
      color: var(--primary-color);
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      margin-bottom: 14px;
    }

    .login-header h2 {
      font-size: 1.45rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 4px;
    }

    .login-header p {
      font-size: 0.88rem;
      color: var(--text-muted);
      margin: 0;
    }

    .form-label {
      font-size: 0.88rem;
      font-weight: 600;
      color: #334155;
      margin-bottom: 6px;
    }

    .input-group-text {
      background-color: #ffffff;
      border-right: none;
      color: #94a3b8;
    }

    .form-control {
      border-left: none;
      padding: 11px 14px;
      font-size: 0.93rem;
      border-color: #dee2e6;
    }

    .form-control:focus {
      border-color: #dee2e6;
      box-shadow: none;
    }

    .input-group:focus-within {
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
      border-radius: 8px;
    }

    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control {
      border-color: var(--primary-color);
    }

    .toggle-password {
      cursor: pointer;
      border-left: none;
      background: #ffffff;
      color: #94a3b8;
    }

    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      padding: 12px;
      font-weight: 600;
      font-size: 0.95rem;
      border-radius: 8px;
    }

    .btn-primary:hover {
      background-color: var(--primary-hover);
      border-color: var(--primary-hover);
    }

    .form-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 18px;
    }

    .change-pw-btn {
      color: var(--primary-color);
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 500;
      background: none;
      border: none;
      padding: 0;
    }

    .change-pw-btn:hover {
      text-decoration: underline;
    }

    .back-link {
      display: inline-block;
      text-align: center;
      width: 100%;
      margin-top: 20px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.86rem;
    }

    .modal-content {
      border-radius: 14px;
      border: none;
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="login-header">
    <div class="icon-box">
      <i class="fa-solid fa-user-shield"></i>
    </div>
    <h2>Admin Portal</h2>
    <p>Sign in to access control panel</p>
  </div>

  <!-- Messages -->
  <?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
      <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
      <div><?= htmlspecialchars($error_msg) ?></div>
    </div>
  <?php endif; ?>

  <?php if (!empty($success_msg)): ?>
    <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
      <i class="fa-solid fa-circle-check me-2 fs-5"></i>
      <div><?= htmlspecialchars($success_msg) ?></div>
    </div>
  <?php endif; ?>

  <!-- Login Form -->
  <form action="login.php" method="POST" autocomplete="off">
    <input type="hidden" name="action" value="login">
    
    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
        <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required autofocus>
      </div>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
        <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
        <span class="input-group-text toggle-password" id="togglePassword">
          <i class="fa-regular fa-eye" id="eyeIcon"></i>
        </span>
      </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 mt-2">
      <i class="fa-solid fa-right-to-bracket me-2"></i> Log In
    </button>
  </form>

  <div class="form-actions">
    <button type="button" class="change-pw-btn" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
      <i class="fa-solid fa-lock me-1"></i> Change Password?
    </button>
  </div>

  <a href="index.php" class="back-link">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Main Site
  </a>
</div>

<!-- ========================================== -->
<!-- CHANGE PASSWORD MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="changePasswordModalLabel">
          <i class="fa-solid fa-key me-2 text-primary"></i>Change Password
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form action="login.php" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="change_password">
          
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="cp_username" class="form-control" placeholder="Enter your username" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Old Password</label>
            <input type="password" name="cp_old_password" class="form-control" placeholder="Enter current password" required>
          </div>

          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="cp_new_password" class="form-control" placeholder="Enter new password (min 6 chars)" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS & Show/Hide Password Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');

  togglePassword.addEventListener('click', function () {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    
    if (type === 'text') {
      eyeIcon.classList.remove('fa-eye');
      eyeIcon.classList.add('fa-eye-slash');
    } else {
      eyeIcon.classList.remove('fa-eye-slash');
      eyeIcon.classList.add('fa-eye');
    }
  });
</script>

</body>
</html>