<?php
// File: index.php (root)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Ambil data survei yang aktif dan belum melebihi deadline (end_date)
 $stmt = $pdo->query("SELECT * FROM surveys WHERE status = 'active' AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY created_at DESC");
 $surveys = $stmt->fetchAll();

// Cek apakah yang login mahasiswa (asumsi session mahasiswa pakai $_SESSION['student_id'])
 $is_student = isset($_SESSION['student_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey App - Beranda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-clipboard-data"></i> Survey App</a>
            <div class="d-flex">
                <?php if ($is_student): ?>
                    <span class="navbar-text text-white me-3">Halo, <?= htmlspecialchars($_SESSION['student_name'] ?? 'Mahasiswa') ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                <?php else: ?>
                    <a href="admin/login.php" class="btn btn-outline-light btn-sm me-2">Login Admin/Dosen</a>
                    <a href="login_mahasiswa.php" class="btn btn-light btn-sm">Login Mahasiswa</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="mb-4 fw-bold">Daftar Survei Tersedia</h2>
        
        <div class="row">
            <?php if (empty($surveys)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Belum ada survei yang aktif saat ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($surveys as $survey): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($survey['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($survey['description'], 0, 100)) ?>...</p>
                                
                                <?php if ($survey['end_date']): ?>
                                    <p class="text-danger small"><i class="bi bi-calendar-event"></i> Deadline: <?= date('d M Y', strtotime($survey['end_date'])) ?></p>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <?php if ($is_student): ?>
                                        <!-- Tombol untuk Mahasiswa yang login -->
                                        <a href="responden/survey.php?id=<?= $survey['id'] ?>" class="btn btn-primary">
                                            <i class="bi bi-pencil-square"></i> Isi Survei
                                        </a>
                                    <?php else: ?>
                                        <!-- Tombol untuk Guest -->
                                        <button class="btn btn-secondary" disabled>
                                            <i class="bi bi-lock-fill"></i> Login untuk Mengisi
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>