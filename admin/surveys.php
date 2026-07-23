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
?>
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
                /* === Kolom Aksi: rapi & jelas === */
        .btn-aksi {
            white-space: nowrap;          /* teks tidak pecah ke 2 baris */
        }
        .btn-aksi .label-aksi {
            margin-left: 2px;
        }
        /* 📱 Di layar kecil (HP), sembunyikan teks → tampil ikon saja (tooltip tetap jalan) */
        @media (max-width: 768px) {
            .btn-aksi .label-aksi {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php $active_menu = 'surveys'; $show_export_diagram = true; ?>
    <?php include __DIR__ . '/includes/navbar_admin.php'; ?>

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
                                            <div class="d-flex flex-wrap gap-1">
                                                <!-- 1. Link Survei -->
                                                <a href="../responden/survey.php?token=<?php echo md5($survey['id'] . 'secret'); ?>"
                                                target="_blank"
                                                class="btn btn-sm btn-success btn-aksi"
                                                title="Salin / Bagikan Link Survei">
                                                    <i class="bi bi-link-45deg"></i> <span class="label-aksi">Link</span>
                                                </a>

                                                <!-- 2. Kelola Pertanyaan -->
                                                <a href="questions.php?survey_id=<?php echo $survey['id']; ?>"
                                                class="btn btn-sm btn-secondary btn-aksi"
                                                title="Kelola Pertanyaan">
                                                    <i class="bi bi-list-ul"></i> <span class="label-aksi">Pertanyaan</span>
                                                </a>

                                                <!-- 3. Lihat Hasil -->
                                                <a href="results.php?survey_id=<?php echo $survey['id']; ?>"
                                                class="btn btn-sm btn-info btn-aksi"
                                                title="Lihat Hasil &amp; Grafik">
                                                    <i class="bi bi-graph-up"></i> <span class="label-aksi">Hasil</span>
                                                </a>

                                                <!-- 4. Edit -->
                                                <a href="survey_edit.php?id=<?php echo $survey['id']; ?>"
                                                class="btn btn-sm btn-warning btn-aksi"
                                                title="Edit Survei">
                                                    <i class="bi bi-pencil"></i> <span class="label-aksi">Edit</span>
                                                </a>

                                                <!-- 5. Hapus -->
                                                <a href="?delete=1&id=<?php echo $survey['id']; ?>"
                                                class="btn btn-sm btn-danger btn-aksi btn-hapus"
                                                title="Hapus Survei"
                                                data-title="<?php echo htmlspecialchars($survey['title'], ENT_QUOTES); ?>">
                                                    <i class="bi bi-trash"></i> <span class="label-aksi">Hapus</span>
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

    <!-- ✅ TEMPEL SCRIPT KONFIRMASI HAPUS DI SINI -->
    <script>
    document.querySelectorAll('.btn-hapus').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var judul = btn.getAttribute('data-title');
            if (!confirm('Hapus survei "' + judul + '"?\nSemua data responden ikut terhapus!')) {
                e.preventDefault();
            }
        });
    });
    </script>

</body>
</html>