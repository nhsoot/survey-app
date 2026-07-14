<?php
// Start session hanya di sini
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$page_title = 'Kelola Survei';
$admin_id = $_SESSION['admin_id'];

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM surveys WHERE id = ? AND admin_id = ?");
        $stmt->execute([$_GET['id'], $admin_id]);
        header('Location: surveys.php?deleted=1');
        exit();
    } catch (PDOException $e) {
        $error = 'Gagal menghapus survei: ' . $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare("SELECT s.*, 
        (SELECT COUNT(*) FROM responses WHERE survey_id = s.id) as response_count 
        FROM surveys s 
        WHERE s.admin_id = ? 
        ORDER BY s.created_at DESC");
    $stmt->execute([$admin_id]);
    $surveys = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?> nav
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Survei - Survey App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f0f2f5;
        }
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #4a5568;
        }
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-clipboard-data"></i> Survey App
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="surveys.php"><i class="bi bi-file-earmark-text"></i> Survei</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                                        <!-- Tambahkan di menu navbar -->
                    <li class="nav-item">
                        <a class="nav-link" href="export_diagram.php"><i class="bi bi-image"></i> Ekspor Diagram</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="bi bi-file-earmark-text"></i> Daftar Survei</h4>
            <a href="survey_create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Buat Survei Baru
            </a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> Survei berhasil dihapus!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <?php if (count($surveys) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Judul</th>
                                    <th>Status</th>
                                    <th>Responden</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($surveys as $key => $survey): ?>
                                    <tr>
                                        <td><?php echo $key + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($survey['title']); ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $survey['status'] == 'active' ? 'bg-success' : ($survey['status'] == 'inactive' ? 'bg-danger' : 'bg-warning'); ?>">
                                                <?php echo ucfirst($survey['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $survey['response_count']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($survey['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="../responden/survey.php?token=<?php echo md5($survey['id'] . 'secret'); ?>" 
                                                   target="_blank" class="btn btn-sm btn-success" title="Link Survei">
                                                    <i class="bi bi-link-45deg"></i>
                                                </a>
                                                <a href="questions.php?survey_id=<?php echo $survey['id']; ?>" 
                                                   class="btn btn-sm btn-secondary" title="Kelola Pertanyaan">
                                                    <i class="bi bi-list-ul"></i>
                                                </a>
                                                <a href="results.php?survey_id=<?php echo $survey['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="Lihat Hasil">
                                                    <i class="bi bi-graph-up"></i>
                                                </a>
                                                <a href="survey_edit.php?id=<?php echo $survey['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=1&id=<?php echo $survey['id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Hapus"
                                                   onclick="return confirm('Hapus survei ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e0;"></i>
                        <h5 class="mt-3 text-muted">Belum ada survei</h5>
                        <p class="text-muted">Mulai buat survei pertama Anda sekarang</p>
                        <a href="survey_create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Buat Survei Baru
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>