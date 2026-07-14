<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$page_title = 'Hasil Survei';
$admin_id = $_SESSION['admin_id'];

$survey_id = $_GET['survey_id'] ?? 0;
$chart_type = $_GET['chart_type'] ?? 'bar';

// Get survey data
try {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND admin_id = ?");
    $stmt->execute([$survey_id, $admin_id]);
    $survey = $stmt->fetch();
    
    if (!$survey) {
        header('Location: surveys.php');
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_number");
    $stmt->execute([$survey_id]);
    $questions = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM responses WHERE survey_id = ?");
    $stmt->execute([$survey_id]);
    $totalResponses = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Survei - Survey App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .card-stat {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
        }
        .chart-container {
            height: 300px;
            margin: 20px 0;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
        }
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
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
                        <a class="nav-link" href="surveys.php"><i class="bi bi-file-earmark-text"></i> Survei</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="bi bi-graph-up"></i> Hasil Survei: <?php echo htmlspecialchars($survey['title']); ?>
            </h4>
            <div>
                <a href="export.php?survey_id=<?php echo $survey_id; ?>" class="btn btn-success">
                    <i class="bi bi-download"></i> Ekspor CSV
                </a>
                <a href="surveys.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body p-4">
                        <div class="stat-label">Total Responden</div>
                        <div class="stat-number"><?php echo $totalResponses; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat">
                    <div class="card-body p-4">
                        <div class="stat-label">Total Pertanyaan</div>
                        <div class="stat-number"><?php echo count($questions); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <label class="form-label fw-semibold">Pilih Jenis Grafik</label>
                        <select class="form-select" id="chartTypeSelector" onchange="changeChartType(this.value)">
                            <option value="bar" <?php echo $chart_type == 'bar' ? 'selected' : ''; ?>>📊 Diagram Batang</option>
                            <option value="line" <?php echo $chart_type == 'line' ? 'selected' : ''; ?>>📈 Grafik Garis</option>
                            <option value="pie" <?php echo $chart_type == 'pie' ? 'selected' : ''; ?>>🍩 Diagram Lingkaran</option>
                            <option value="doughnut" <?php echo $chart_type == 'doughnut' ? 'selected' : ''; ?>>⭕ Doughnut</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <?php if (count($questions) > 0 && $totalResponses > 0): ?>
            <?php foreach ($questions as $question): ?>
                <?php
                // Get answers for this question
                try {
                    $stmt = $pdo->prepare("
                        SELECT a.answer_value, COUNT(*) as count 
                        FROM answers a 
                        JOIN responses r ON a.response_id = r.id 
                        WHERE a.question_id = ? AND r.survey_id = ? 
                        GROUP BY a.answer_value
                    ");
                    $stmt->execute([$question['id'], $survey_id]);
                    $answerData = $stmt->fetchAll();
                } catch (PDOException $e) {
                    continue;
                }
                
                if (count($answerData) == 0) continue;
                
                $labels = [];
                $data = [];
                $colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#fee140'];
                
                foreach ($answerData as $row) {
                    $labels[] = $row['answer_value'];
                    $data[] = $row['count'];
                }
                ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-question-circle"></i> <?php echo htmlspecialchars($question['question_text']); ?>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chart_<?php echo $question['id']; ?>"></canvas>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pilihan</th>
                                        <th>Jumlah</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($answerData as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['answer_value']); ?></td>
                                            <td><?php echo $row['count']; ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo round(($row['count'] / $totalResponses) * 100, 1); ?>%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"
                                                         aria-valuenow="<?php echo round(($row['count'] / $totalResponses) * 100, 1); ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo round(($row['count'] / $totalResponses) * 100, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('chart_<?php echo $question['id']; ?>').getContext('2d');
                    const chartType = '<?php echo $chart_type; ?>';
                    
                    new Chart(ctx, {
                        type: chartType,
                        data: {
                            labels: <?php echo json_encode($labels); ?>,
                            datasets: [{
                                label: 'Jumlah Responden',
                                data: <?php echo json_encode($data); ?>,
                                backgroundColor: <?php echo json_encode(array_slice($colors, 0, count($labels))); ?>,
                                borderColor: '#667eea',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        font: {
                                            size: 12
                                        }
                                    }
                                }
                            },
                            scales: chartType === 'pie' || chartType === 'doughnut' ? undefined : {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                });
                </script>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e0;"></i>
                    <h5 class="mt-3 text-muted">Belum ada data</h5>
                    <p class="text-muted">
                        <?php if (count($questions) == 0): ?>
                            Belum ada pertanyaan. <a href="questions.php?survey_id=<?php echo $survey_id; ?>">Tambahkan pertanyaan</a>
                        <?php else: ?>
                            Belum ada responden yang mengisi survei ini.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function changeChartType(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('chart_type', value);
        window.location.href = url.toString();
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>