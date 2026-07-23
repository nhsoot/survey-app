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

$page_title = 'Dashboard';
$admin_id = $_SESSION['admin_id'];

// Get statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM surveys WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $surveyCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM surveys WHERE status = 'active' AND admin_id = ?");
    $stmt->execute([$admin_id]);
    $activeSurveys = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM responses r JOIN surveys s ON r.survey_id = s.id WHERE s.admin_id = ?");
    $stmt->execute([$admin_id]);
    $totalResponses = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM responses WHERE survey_id = s.id) as response_count FROM surveys s WHERE s.admin_id = ? ORDER BY s.created_at DESC LIMIT 5");
    $stmt->execute([$admin_id]);
    $recentSurveys = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Survey App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f0f2f5;
        }
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-stat {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
        }
        .card-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2.5rem;
            color: #667eea;
            opacity: 0.8;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
        }
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
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
    </style>
</head>
<body>
<?php $active_menu = 'dashboard'; ?>
<?php include __DIR__ . '/includes/navbar_admin.php'; ?>

    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Total Survei</div>
                                <div class="stat-number"><?php echo $surveyCount; ?></div>
                            </div>
                            <i class="bi bi-file-earmark-text stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Survei Aktif</div>
                                <div class="stat-number"><?php echo $activeSurveys; ?></div>
                            </div>
                            <i class="bi bi-check-circle stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Total Responden</div>
                                <div class="stat-number"><?php echo $totalResponses; ?></div>
                            </div>
                            <i class="bi bi-people stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Rata-rata per Survei</div>
                                <div class="stat-number"><?php echo $surveyCount > 0 ? round(($totalResponses / $surveyCount), 1) : 0; ?></div>
                            </div>
                            <i class="bi bi-graph-up stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Survei Terbaru
            </div>
            <div class="card-body">
                <?php if (count($recentSurveys) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Judul</th>
                                    <th>Status</th>
                                    <th>Responden</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSurveys as $survey): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($survey['title']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $survey['status'] == 'active' ? 'bg-success' : ($survey['status'] == 'inactive' ? 'bg-danger' : 'bg-warning'); ?>">
                                                <?php echo ucfirst($survey['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $survey['response_count'] ?? 0; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($survey['created_at'])); ?></td>
                                        <td>
                                            <a href="results.php?survey_id=<?php echo $survey['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-graph-up"></i>
                                            </a>
                                            <a href="survey_edit.php?id=<?php echo $survey['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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