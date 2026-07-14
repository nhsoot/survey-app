<?php
// File: admin/survey_create.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

 $page_title = 'Buat Survei';
 $admin_id = $_SESSION['admin_id'];

 $error = '';
 $success = '';

// Ambil daftar mahasiswa untuk ditampilkan di form
try {
    $stmtStudents = $pdo->query("SELECT id, nim, name FROM students ORDER BY name ASC");
    $students = $stmtStudents->fetchAll();
} catch (PDOException $e) {
    $students = [];
    $error = "Gagal memuat data mahasiswa: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'draft';
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $selected_students = $_POST['student_ids'] ?? []; // Array mahasiswa terpilih
    
    if (empty($title)) {
        $error = 'Judul survei harus diisi!';
    } else {
        try {
            // Gunakan transaction agar jika gagal di tengah, data tidak tersimpan setengah
            $pdo->beginTransaction();
            
            // 1. Simpan data survei beserta deadline (end_date)
            $stmt = $pdo->prepare("INSERT INTO surveys (admin_id, title, description, status, end_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$admin_id, $title, $description, $status, $end_date]);
            $surveyId = $pdo->lastInsertId();
            
            // 2. Simpan mahasiswa yang terpilih ke tabel survey_assignments
            if (!empty($selected_students)) {
                $assignStmt = $pdo->prepare("INSERT INTO survey_assignments (survey_id, student_id) VALUES (?, ?)");
                foreach ($selected_students as $student_id) {
                    $assignStmt->execute([$surveyId, $student_id]);
                }
            }
            
            $pdo->commit();
            
            $success = 'Survei berhasil dibuat!';
            $success .= ' <a href="questions.php?survey_id=' . $surveyId . '" class="btn btn-sm btn-primary">Tambahkan Pertanyaan</a>';
            $success .= ' <a href="surveys.php" class="btn btn-sm btn-secondary">Lihat Daftar Survei</a>';
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal membuat survei: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Survei - Survey App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { background: white; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.5rem; font-weight: 600; border-radius: 12px 12px 0 0 !important; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .form-label { font-weight: 600; color: #2d3748; }
        .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
        /* Styling untuk multi-select */
        select[multiple] { min-height: 150px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-clipboard-data"></i> Survey App</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="surveys.php"><i class="bi bi-file-earmark-text"></i> Survei</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-plus-circle"></i> Buat Survei Baru
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Judul Survei <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="Masukkan judul survei" required autofocus>
                                <small class="text-muted">Contoh: Survey Kepuasan Dosen 2024</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Masukkan deskripsi survei"></textarea>
                                <small class="text-muted">Jelaskan tujuan survei ini (opsional)</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="draft">Draft (Belum Aktif)</option>
                                        <option value="active">Aktif (Bisa Diisi)</option>
                                        <option value="inactive">Tidak Aktif</option>
                                    </select>
                                    <small class="text-muted">Pilih status survei.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Deadline Pengisian</label>
                                    <input type="date" name="end_date" class="form-control">
                                    <small class="text-muted">Kosongkan jika tidak ada deadline.</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pilih Mahasiswa yang Boleh Mengisi</label>
                                <select name="student_ids[]" class="form-select" multiple>
                                    <?php if (empty($students)): ?>
                                        <option disabled>Belum ada data mahasiswa. Silakan tambahkan mahasiswa terlebih dahulu.</option>
                                    <?php else: ?>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?= $student['id'] ?>">
                                                <?= htmlspecialchars($student['nim'] . ' - ' . $student['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">Tahan <strong>Ctrl</strong> (Windows) atau <strong>Cmd</strong> (Mac) untuk memilih lebih dari satu mahasiswa. Jika tidak dipilih, survei tidak bisa diisi siapa pun.</small>
                            </div>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Survei
                                </button>
                                <a href="surveys.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>