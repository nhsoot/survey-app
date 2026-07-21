<?php
// Start session hanya di sini
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Auto setup if no admin exists
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Create default admin
        $username = 'admin';
        $password = 'admin123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $email = 'admin@survey.com';
        
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $email]);
        
        $setupMessage = '✅ Admin default dibuat! Username: admin, Password: admin123';
    }
} catch (PDOException $e) {
    // Table might not exist
    die('Error: Database belum disiapkan. Jalankan file sql/database.sql terlebih dahulu.');
}

// Cek login
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = $setupMessage ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: index.php');
            exit();
        }
        $error = 'Username atau password salah!';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Survey App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            background: white;
        }
        .login-card .card-body {
            padding: 2.5rem;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .login-logo i {
            font-size: 3.5rem;
            color: #667eea;
        }
        .login-logo h3 {
            color: #333;
            font-weight: 700;
            margin-top: 0.5rem;
        }
        .login-logo p {
            color: #666;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card card mx-auto">
            <div class="card-body">
                <div class="login-logo">
                    <i class="bi bi-clipboard-data"></i>
                    <h3>Survey App</h3>
                    <p>Silakan login untuk mengelola survei</p>
                </div>
                
                <?php if ($error && $error != 'Username atau password salah!'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error && $error == 'Username atau password salah!'): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>

                    <!-- TOMBOL PERALIHAN KE LOGIN MAHASISWA -->
                    <a href="../login_mahasiswa.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-person-badge"></i> Login sebagai Mahasiswa
                    </a>

                    <!-- TOMBOL KEMBALI KE BERANDA -->
                    <a href="../index.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                    </a>
                </form>
                
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Default: <strong>admin</strong> / <strong>admin123</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>