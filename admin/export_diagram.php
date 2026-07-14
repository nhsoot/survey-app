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

$page_title = 'Ekspor Diagram';
$admin_id = $_SESSION['admin_id'];

// Get all surveys for this admin
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

// Get questions for selected survey
$selected_survey_id = $_GET['survey_id'] ?? 0;
$questions = [];
$responses_count = 0;

if ($selected_survey_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_number");
        $stmt->execute([$selected_survey_id]);
        $questions = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM responses WHERE survey_id = ?");
        $stmt->execute([$selected_survey_id]);
        $responses_count = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Ignore
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ekspor Diagram - Survey App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
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
        .btn-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            border: none;
            color: #1a202c;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 233, 123, 0.4);
            color: #1a202c;
        }
        .btn-warning-custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
        }
        .btn-warning-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
            color: white;
        }
        .chart-container {
            height: 250px;
            width: 100%;
            padding: 10px;
        }
        .canvas-container {
            background: white;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            min-height: 500px;
            padding: 20px;
            position: relative;
        }
        .canvas-container .chart-wrapper {
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            background: white;
            position: relative;
        }
        .canvas-container .chart-wrapper .chart-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2d3748;
            font-size: 14px;
        }
        .canvas-container .chart-wrapper .remove-chart {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #f56565;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .canvas-container .chart-wrapper .remove-chart:hover {
            transform: scale(1.1);
            background: #e53e3e;
        }
        .chart-item {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .chart-item:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .chart-item.selected {
            border-color: #667eea;
            background: #f7fafc;
        }
        .preview-chart-container {
            height: 180px;
            width: 100%;
        }
        .selected-charts-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .selected-charts-list .list-group-item {
            border-left: 4px solid #667eea;
            margin-bottom: 5px;
            border-radius: 6px !important;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loading-overlay.show {
            display: flex;
        }
        .loading-spinner {
            background: white;
            padding: 30px 50px;
            border-radius: 12px;
            text-align: center;
        }
        .loading-spinner .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        .chart-type-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            background: #e9ecef;
            color: #495057;
        }
        .chart-type-badge.satisfaction {
            background: #d4edda;
            color: #155724;
        }
        .btn-group-chart {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-group-chart .btn {
            flex: 1;
            font-size: 0.85rem;
        }
        @media (max-width: 768px) {
            .btn-group-chart .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
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
                    <li class="nav-item">
                        <a class="nav-link active" href="export_diagram.php"><i class="bi bi-image"></i> Ekspor Diagram</a>
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
        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 mb-0">Sedang memproses gambar...</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-image"></i> Ekspor Diagram
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Left Panel - Selection -->
                            <div class="col-md-5">
                                <h6 class="fw-bold mb-3"><i class="bi bi-gear"></i> Pilih Data</h6>
                                
                                <!-- Pilih Survei -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Pilih Survei</label>
                                    <select class="form-select" id="surveySelect" onchange="loadQuestions(this.value)">
                                        <option value="0">-- Pilih Survei --</option>
                                        <?php foreach ($surveys as $survey): ?>
                                            <option value="<?php echo $survey['id']; ?>" <?php echo $selected_survey_id == $survey['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($survey['title']); ?> 
                                                (<?php echo $survey['response_count']; ?> responden)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Pilih Pertanyaan -->
                                <div class="mb-3" id="questionsContainer">
                                    <?php if ($selected_survey_id > 0 && count($questions) > 0): ?>
                                        <label class="form-label fw-semibold">Pilih Pertanyaan</label>
                                        <select class="form-select" id="questionSelect">
                                            <option value="0">-- Pilih Pertanyaan --</option>
                                            <?php foreach ($questions as $q): ?>
                                                <option value="<?php echo $q['id']; ?>">
                                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($responses_count == 0): ?>
                                            <div class="text-warning mt-2">
                                                <i class="bi bi-exclamation-triangle"></i> Belum ada responden untuk survei ini.
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($selected_survey_id > 0 && count($questions) == 0): ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> Survei ini belum memiliki pertanyaan.
                                            <a href="questions.php?survey_id=<?php echo $selected_survey_id; ?>">Tambahkan pertanyaan</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted">Pilih survei terlebih dahulu</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Pilih Jenis Diagram -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Jenis Diagram</label>
                                    <div class="btn-group-chart" role="group">
                                        <button type="button" class="btn btn-outline-primary active" data-chart="bar" onclick="selectChartType('bar')">
                                            📊 Batang
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" data-chart="line" onclick="selectChartType('line')">
                                            📈 Garis
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" data-chart="pie" onclick="selectChartType('pie')">
                                            🍩 Lingkaran
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" data-chart="doughnut" onclick="selectChartType('doughnut')">
                                            ⭕ Doughnut
                                        </button>
                                    </div>
                                    <input type="hidden" id="chartTypeSelect" value="bar">
                                </div>

                                <!-- Opsi Diagram Kepuasan -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="satisfactionMode" onchange="toggleSatisfactionMode()">
                                        <label class="form-check-label fw-semibold" for="satisfactionMode">
                                            <i class="bi bi-star-fill text-warning"></i> Mode Kepuasan (Hitung % Puas/Sangat Puas)
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-info-circle"></i> 
                                        Aktifkan untuk menghitung persentase responden yang menjawab "Puas" atau "Sangat Puas". 
                                        Jawaban lain (Cukup, Kurang, dll) akan diabaikan.
                                    </small>
                                </div>

                                <!-- Preview Diagram -->
                                <div class="mb-3" id="previewContainer" style="display: none;">
                                    <label class="form-label fw-semibold">Preview</label>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="preview-chart-container">
                                                <canvas id="previewChart"></canvas>
                                            </div>
                                            <div id="previewTitle" class="text-muted small mt-2"></div>
                                            <div id="previewStats" class="text-muted small"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tombol Tambah -->
                                <button class="btn btn-primary w-100" id="addChartBtn" onclick="addChart()" disabled>
                                    <i class="bi bi-plus-circle"></i> Tambahkan ke Canvas
                                </button>

                                <hr>

                                <!-- Daftar Diagram yang Ditambahkan -->
                                <div class="mt-3">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-list"></i> Diagram Terpilih (<span id="chartCount">0</span>)</h6>
                                    <div class="selected-charts-list" id="selectedChartsList">
                                        <p class="text-muted text-center small">Belum ada diagram yang ditambahkan</p>
                                    </div>
                                    
                                    <!-- Tombol Bersihkan -->
                                    <button class="btn btn-outline-danger btn-sm w-100 mt-2" onclick="clearCanvas()">
                                        <i class="bi bi-trash"></i> Bersihkan Semua
                                    </button>
                                </div>
                            </div>

                            <!-- Right Panel - Canvas -->
                            <div class="col-md-7">
                                <h6 class="fw-bold mb-3"><i class="bi bi-layout-three-columns"></i> Canvas</h6>
                                
                                <!-- Tombol Simpan -->
                                <div class="mb-3 d-flex gap-2 flex-wrap">
                                    <button class="btn btn-success" onclick="saveCanvas()" id="saveBtn" disabled>
                                        <i class="bi bi-download"></i> 📥 Simpan PNG
                                    </button>
                                    <button class="btn btn-info" onclick="saveCanvas('jpg')" id="saveJpgBtn" disabled>
                                        <i class="bi bi-download"></i> 📥 Simpan JPG
                                    </button>
                                    <button class="btn btn-secondary" onclick="clearCanvas()">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset Canvas
                                    </button>
                                </div>

                                <!-- Canvas Area -->
                                <div class="canvas-container" id="canvasContainer">
                                    <div id="chartsWrapper">
                                        <!-- Chart akan ditambahkan di sini via JavaScript -->
                                        <div class="text-center text-muted py-5" id="emptyCanvasMessage">
                                            <i class="bi bi-image" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                                            <p>Belum ada diagram. Pilih survei, pertanyaan, dan jenis diagram,<br> lalu klik "Tambahkan ke Canvas"</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Informasi -->
                                <div class="mt-2 text-muted small">
                                    <i class="bi bi-info-circle"></i> 
                                    Tips: Tambahkan beberapa diagram dari pertanyaan yang berbeda. 
                                    Gunakan tombol <i class="bi bi-x-circle"></i> untuk menghapus diagram individual.
                                    <br>
                                    <i class="bi bi-star-fill text-warning"></i> 
                                    Mode Kepuasan hanya menghitung responden dengan jawaban "Puas" atau "Sangat Puas".
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Global variables
    let selectedCharts = [];
    let chartInstances = {};
    let previewChartInstance = null;
    let currentChartType = 'bar';
    let isSatisfactionMode = false;

    // Function to select chart type
    function selectChartType(type) {
        currentChartType = type;
        document.getElementById('chartTypeSelect').value = type;
        
        // Update button styles
        document.querySelectorAll('.btn-group-chart .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`.btn-group-chart .btn[data-chart="${type}"]`).classList.add('active');
        
        // Refresh preview
        previewChart();
    }

    // Function to toggle satisfaction mode
    function toggleSatisfactionMode() {
        isSatisfactionMode = document.getElementById('satisfactionMode').checked;
        previewChart();
    }

    // Function to load questions based on selected survey
    function loadQuestions(surveyId) {
        if (surveyId > 0) {
            window.location.href = '?survey_id=' + surveyId;
        } else {
            window.location.href = '?';
        }
    }

    // Function to preview chart
    function previewChart() {
        const surveyId = document.getElementById('surveySelect').value;
        const questionId = document.getElementById('questionSelect').value;
        const chartType = currentChartType;
        const previewContainer = document.getElementById('previewContainer');
        const previewTitle = document.getElementById('previewTitle');
        const previewStats = document.getElementById('previewStats');
        const addBtn = document.getElementById('addChartBtn');

        if (surveyId == 0 || questionId == 0) {
            previewContainer.style.display = 'none';
            addBtn.disabled = true;
            return;
        }

        // Fetch data for preview
        let url = 'export_diagram_action.php?action=get_data&survey_id=' + surveyId + '&question_id=' + questionId;
        if (isSatisfactionMode) {
            url += '&mode=satisfaction';
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    previewContainer.style.display = 'block';
                    previewTitle.textContent = data.question_text;
                    
                    if (data.mode === 'satisfaction') {
                        previewStats.innerHTML = `
                            <span class="badge bg-success">✓ Mode Kepuasan</span>
                            Total Puas: ${data.satisfied_count || 0} dari ${data.total_respondents || 0} responden
                            (${data.percentage || 0}%)
                            <br>
                            <small class="text-muted">* Hanya menghitung jawaban "Puas" dan "Sangat Puas"</small>
                        `;
                    } else {
                        previewStats.innerHTML = `Total data: ${data.data.length} kategori`;
                    }
                    
                    addBtn.disabled = false;

                    // Destroy existing preview chart
                    if (previewChartInstance) {
                        previewChartInstance.destroy();
                    }

                    const ctx = document.getElementById('previewChart').getContext('2d');
                    const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#fee140'];
                    
                    // For satisfaction mode, use green colors
                    const chartColors = isSatisfactionMode ? 
                        ['#28a745'] : 
                        colors.slice(0, data.data.length);
                    
                    previewChartInstance = new Chart(ctx, {
                        type: chartType,
                        data: {
                            labels: data.data.map(d => d.label),
                            datasets: [{
                                label: isSatisfactionMode ? 'Persentase Puas (%)' : 'Jumlah Responden',
                                data: data.data.map(d => d.count),
                                backgroundColor: chartColors,
                                borderColor: '#667eea',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: chartType === 'pie' || chartType === 'doughnut',
                                    position: 'bottom',
                                    labels: {
                                        font: { size: 10 }
                                    }
                                }
                            },
                            scales: chartType === 'pie' || chartType === 'doughnut' ? undefined : {
                                y: {
                                    beginAtZero: true,
                                    ticks: { 
                                        stepSize: isSatisfactionMode ? 10 : 1,
                                        callback: function(value) {
                                            return isSatisfactionMode ? value + '%' : value;
                                        }
                                    }
                                }
                            }
                        }
                    });
                } else {
                    previewContainer.style.display = 'none';
                    addBtn.disabled = true;
                    if (data.message) {
                        previewStats.innerHTML = '<span class="text-warning">' + data.message + '</span>';
                        previewContainer.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                previewContainer.style.display = 'none';
                addBtn.disabled = true;
            });
    }

    // Function to add chart to canvas
    function addChart() {
        const surveyId = document.getElementById('surveySelect').value;
        const questionId = document.getElementById('questionSelect').value;
        const chartType = currentChartType;
        const questionSelect = document.getElementById('questionSelect');
        const questionText = questionSelect.options[questionSelect.selectedIndex]?.text || 'Pertanyaan';
        const isSatMode = isSatisfactionMode;

        if (surveyId == 0 || questionId == 0) {
            alert('Pilih survei dan pertanyaan terlebih dahulu!');
            return;
        }

        // Check if chart already added
        if (selectedCharts.some(c => c.questionId == questionId && c.isSatisfaction == isSatMode)) {
            alert('Pertanyaan ini sudah ditambahkan ke canvas!');
            return;
        }

        // Show loading
        document.getElementById('loadingOverlay').classList.add('show');

        // Fetch data
        let url = 'export_diagram_action.php?action=get_data&survey_id=' + surveyId + '&question_id=' + questionId;
        if (isSatMode) {
            url += '&mode=satisfaction';
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    // Add to selected charts
                    selectedCharts.push({
                        id: Date.now(),
                        surveyId: surveyId,
                        questionId: questionId,
                        questionText: data.question_text,
                        chartType: chartType,
                        data: data.data,
                        isSatisfaction: isSatMode,
                        percentage: data.percentage || 0,
                        satisfied_count: data.satisfied_count || 0,
                        total_respondents: data.total_respondents || 0
                    });

                    // Update UI
                    updateSelectedChartsList();
                    renderCanvas();
                    updateButtons();

                    // Reset preview
                    document.getElementById('previewContainer').style.display = 'none';
                    document.getElementById('addChartBtn').disabled = true;
                    if (previewChartInstance) {
                        previewChartInstance.destroy();
                        previewChartInstance = null;
                    }

                    // Clear question selection
                    document.getElementById('questionSelect').value = '0';
                } else {
                    alert(data.message || 'Gagal menambahkan diagram. Tidak ada data.');
                }
                document.getElementById('loadingOverlay').classList.remove('show');
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loadingOverlay').classList.remove('show');
                alert('Gagal menambahkan diagram. Silakan coba lagi.');
            });
    }

    // Function to update selected charts list
    function updateSelectedChartsList() {
        const list = document.getElementById('selectedChartsList');
        const count = document.getElementById('chartCount');

        count.textContent = selectedCharts.length;

        if (selectedCharts.length === 0) {
            list.innerHTML = '<p class="text-muted text-center small">Belum ada diagram yang ditambahkan</p>';
            return;
        }

        let html = '<div class="list-group">';
        selectedCharts.forEach((chart, index) => {
            const typeLabels = {
                'bar': '📊 Batang',
                'line': '📈 Garis',
                'pie': '🍩 Lingkaran',
                'doughnut': '⭕ Doughnut'
            };
            const satBadge = chart.isSatisfaction ? 
                '<span class="badge bg-success ms-1">Kepuasan</span>' : 
                '';
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2">${index + 1}</span>
                        <span>${chart.questionText.substring(0, 30)}${chart.questionText.length > 30 ? '...' : ''}</span>
                        ${satBadge}
                        <br>
                        <small class="text-muted">${typeLabels[chart.chartType] || chart.chartType}</small>
                        ${chart.isSatisfaction ? `<small class="text-success ms-2">${chart.percentage}% Puas</small>` : ''}
                    </div>
                    <button class="btn btn-sm btn-danger" onclick="removeChart(${chart.id})">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
        });
        html += '</div>';
        list.innerHTML = html;
    }

    // Function to remove chart
    function removeChart(id) {
        selectedCharts = selectedCharts.filter(c => c.id !== id);
        updateSelectedChartsList();
        renderCanvas();
        updateButtons();
    }

    // Function to clear all charts
    function clearCanvas() {
        if (selectedCharts.length === 0) return;
        if (confirm('Hapus semua diagram dari canvas?')) {
            selectedCharts = [];
            updateSelectedChartsList();
            renderCanvas();
            updateButtons();
        }
    }

    // Function to render canvas
    function renderCanvas() {
        const wrapper = document.getElementById('chartsWrapper');
        const emptyMessage = document.getElementById('emptyCanvasMessage');

        if (selectedCharts.length === 0) {
            wrapper.innerHTML = `
                <div class="text-center text-muted py-5" id="emptyCanvasMessage">
                    <i class="bi bi-image" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                    <p>Belum ada diagram. Pilih survei, pertanyaan, dan jenis diagram,<br> lalu klik "Tambahkan ke Canvas"</p>
                </div>
            `;
            return;
        }

        let html = '';
        selectedCharts.forEach((chart, index) => {
            const chartId = 'chart_' + chart.id;
            const satBadge = chart.isSatisfaction ? 
                '<span class="badge bg-success ms-2">Mode Kepuasan - ' + chart.percentage + '% Puas</span>' : 
                '';
            html += `
                <div class="chart-wrapper" id="wrapper_${chart.id}">
                    <button class="remove-chart" onclick="removeChart(${chart.id})">
                        <i class="bi bi-x"></i>
                    </button>
                    <div class="chart-title">
                        ${index + 1}. ${chart.questionText}
                        ${satBadge}
                    </div>
                    <div class="chart-container">
                        <canvas id="${chartId}"></canvas>
                    </div>
                </div>
            `;
        });

        wrapper.innerHTML = html;

        // Render charts after DOM update
        setTimeout(() => {
            selectedCharts.forEach((chart) => {
                const chartId = 'chart_' + chart.id;
                const ctx = document.getElementById(chartId);
                if (ctx) {
                    const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#fee140'];
                    
                    // For satisfaction mode, use green colors
                    const chartColors = chart.isSatisfaction ? 
                        ['#28a745'] : 
                        colors.slice(0, chart.data.length);
                    
                    // Destroy existing chart instance if any
                    if (chartInstances[chartId]) {
                        chartInstances[chartId].destroy();
                    }

                    chartInstances[chartId] = new Chart(ctx.getContext('2d'), {
                        type: chart.chartType,
                        data: {
                            labels: chart.data.map(d => d.label),
                            datasets: [{
                                label: chart.isSatisfaction ? 'Persentase Puas (%)' : 'Jumlah Responden',
                                data: chart.data.map(d => d.count),
                                backgroundColor: chartColors,
                                borderColor: chart.isSatisfaction ? '#28a745' : '#667eea',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: chart.chartType === 'pie' || chart.chartType === 'doughnut',
                                    position: 'bottom',
                                    labels: {
                                        font: { size: 11 }
                                    }
                                }
                            },
                            scales: chart.chartType === 'pie' || chart.chartType === 'doughnut' ? undefined : {
                                y: {
                                    beginAtZero: true,
                                    ticks: { 
                                        stepSize: chart.isSatisfaction ? 10 : 1,
                                        callback: function(value) {
                                            return chart.isSatisfaction ? value + '%' : value;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            });
        }, 100);
    }

    // Function to update buttons
    function updateButtons() {
        const saveBtn = document.getElementById('saveBtn');
        const saveJpgBtn = document.getElementById('saveJpgBtn');
        const hasCharts = selectedCharts.length > 0;
        saveBtn.disabled = !hasCharts;
        saveJpgBtn.disabled = !hasCharts;
    }

    // Function to save canvas
    function saveCanvas(format = 'png') {
        if (selectedCharts.length === 0) {
            alert('Tidak ada diagram untuk disimpan. Tambahkan diagram terlebih dahulu!');
            return;
        }

        // Show loading
        document.getElementById('loadingOverlay').classList.add('show');

        // Use html2canvas to capture the canvas container
        const container = document.getElementById('canvasContainer');
        
        html2canvas(container, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            logging: false
        }).then(canvas => {
            const link = document.createElement('a');
            const timestamp = new Date().toISOString().slice(0, 19).replace(/[:-]/g, '');
            const mode = isSatisfactionMode ? '_kepuasan' : '';
            
            if (format === 'png') {
                link.download = `diagram_survei${mode}_${timestamp}.png`;
                link.href = canvas.toDataURL('image/png');
            } else {
                link.download = `diagram_survei${mode}_${timestamp}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
            }
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            document.getElementById('loadingOverlay').classList.remove('show');
            showToast('Gambar berhasil disimpan di folder Download!', 'success');
        }).catch(error => {
            console.error('Error saving canvas:', error);
            document.getElementById('loadingOverlay').classList.remove('show');
            alert('Gagal menyimpan gambar. Silakan coba lagi.');
        });
    }

    // Simple toast notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.style.maxWidth = '400px';
        toast.innerHTML = `
            <i class="bi bi-check-circle"></i> ${message}
            <button type="button" class="btn-close float-end" onclick="this.parentElement.remove()"></button>
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, 5000);
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('questionSelect')?.addEventListener('change', previewChart);

        <?php if ($selected_survey_id > 0 && count($questions) > 0): ?>
        setTimeout(previewChart, 500);
        <?php endif; ?>

        updateButtons();
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>