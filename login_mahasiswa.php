<?php
// File: login_mahasiswa.php (root)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Jika mahasiswa sudah login, arahkan ke beranda
if (isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

 $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = $_POST['nim'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Cari mahasiswa berdasarkan NIM
        $stmt = $pdo->prepare("SELECT * FROM students WHERE nim = ?");
        $stmt->execute([$nim]);
        $student = $stmt->fetch();
        
        // Verifikasi password (asumsi password di database sudah di-hash menggunakan password_hash)
        if ($student && $password === $student['password']) {
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_name'] = $student['name'];
            $_SESSION['student_nim'] = $student['nim'];
            header('Location: index.php');
            exit();
        }
        $error = 'NIM atau password salah!';
    } catch (PDOException $e) {
        $error = 'Error database: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Mahasiswa - Survey App</title>
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
        .login-card .card-body { padding: 2.5rem; }
        .login-logo { text-align: center; margin-bottom: 1.5rem; }
        .login-logo i { font-size: 3.5rem; color: #667eea; }
        .login-logo h3 { color: #333; font-weight: 700; margin-top: 0.5rem; }
        .login-logo p { color: #666; font-size: 0.9rem; }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none; padding: 12px; font-weight: 600;
        }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        .input-group-text { background: #f8f9fa; border-right: none; }
        .form-control { border-left: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card card">
            <div class="card-body">
                <div class="login-logo">
                    <i class="bi bi-person-badge"></i>
                    <h3>Login Mahasiswa</h3>
                    <p>Silakan login untuk mengisi survei</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">NIM</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="nim" class="form-control" placeholder="Masukkan NIM" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <a href="index.php" class="text-muted text-decoration-none small">
                        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>