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

$page_title = 'Kelola Pertanyaan';
$admin_id = $_SESSION['admin_id'];

$survey_id = $_GET['survey_id'] ?? 0;

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
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle delete question
if (isset($_GET['delete_q']) && isset($_GET['qid'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND survey_id = ?");
        $stmt->execute([$_GET['qid'], $survey_id]);
        header('Location: questions.php?survey_id=' . $survey_id . '&deleted=1');
        exit();
    } catch (PDOException $e) {
        $error = 'Gagal menghapus pertanyaan: ' . $e->getMessage();
    }
}

// Handle add question
$error = '';
$success = isset($_GET['deleted']) ? 'Pertanyaan berhasil dihapus!' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $question_text = trim($_POST['question_text']);
    $type = $_POST['type'];
    $options = $_POST['options'] ?? '';
    $is_required = isset($_POST['is_required']) ? 1 : 0;
    
    if (empty($question_text)) {
        $error = 'Teks pertanyaan harus diisi!';
    } else {
        try {
            // Process options for multiple choice or likert
            $options_json = null;
            if (in_array($type, ['multiple_choice', 'likert'])) {
                $options_array = array_filter(array_map('trim', explode("\n", $options)));
                $options_json = json_encode($options_array);
            }
            
            $order = count($questions) + 1;
            $stmt = $pdo->prepare("INSERT INTO questions (survey_id, question_text, type, options, is_required, order_number) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$survey_id, $question_text, $type, $options_json, $is_required, $order]);
            
            header('Location: questions.php?survey_id=' . $survey_id . '&added=1');
            exit();
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan pertanyaan: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['added'])) {
    $success = 'Pertanyaan berhasil ditambahkan!';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pertanyaan - Survey App</title>
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
        .question-type-badge { font-size: 0.75rem; padding: 3px 10px; border-radius: 12px; background: #e9ecef; color: #495057; }
        .list-group-item { border-left: 4px solid #667eea; margin-bottom: 8px; border-radius: 8px !important; }
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="bi bi-list-ul"></i> Pertanyaan: <?php echo htmlspecialchars($survey['title']); ?></h4>
            <a href="surveys.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

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

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><i class="bi bi-question-circle"></i> Daftar Pertanyaan</div>
                    <div class="card-body">
                        <?php if (count($questions) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($questions as $index => $q): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo $index + 1; ?>.</strong>
                                            <?php echo htmlspecialchars($q['question_text']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <span class="question-type-badge"><?php echo str_replace('_', ' ', $q['type']); ?></span>
                                                <?php if ($q['is_required']): ?>
                                                    <span class="text-danger">* Wajib</span>
                                                <?php endif; ?>
                                                <?php if ($q['options']): ?>
                                                    <span class="text-muted">| <?php echo count(json_decode($q['options'], true)); ?> pilihan</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div>
                                            <a href="?survey_id=<?php echo $survey_id; ?>&delete_q=1&qid=<?php echo $q['id']; ?>" 
                                               class="btn btn-sm btn-danger" onclick="return confirm('Hapus pertanyaan ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e0;"></i>
                                <h5 class="mt-3 text-muted">Belum ada pertanyaan</h5>
                                <p class="text-muted">Tambahkan pertanyaan pertama Anda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-plus-circle"></i> Tambah Pertanyaan</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Teks Pertanyaan <span class="text-danger">*</span></label>
                                <input type="text" name="question_text" class="form-control" placeholder="Masukkan pertanyaan" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tipe Pertanyaan</label>
                                <select name="type" class="form-select" id="questionType" onchange="toggleOptions()">
                                    <option value="multiple_choice">Pilihan Ganda</option>
                                    <option value="likert" selected>Skala Likert</option>
                                    <option value="text">Teks Singkat</option>
                                    <option value="paragraph">Paragraf</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="optionsContainer">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0">Pilihan (satu per baris)</label>
                                    <!-- TOMBOL PRESET SAKTI -->
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" onclick="isiPresetKepuasan()">
                                        <i class="bi bi-magic"></i> Preset Kepuasan
                                    </button>
                                </div>
                                <textarea name="options" id="optionsTextarea" class="form-control" rows="5" placeholder="Sangat Puas&#10;Puas&#10;Cukup&#10;Kurang&#10;Sangat Kurang"></textarea>
                                <small class="text-muted">Untuk pilihan ganda dan skala likert</small>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="is_required" class="form-check-input" id="isRequired" checked>
                                <label class="form-check-label" for="isRequired">Wajib diisi</label>
                            </div>
                            
                            <button type="submit" name="add_question" class="btn btn-primary w-100">
                                <i class="bi bi-plus"></i> Tambahkan Pertanyaan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleOptions() {
        const type = document.getElementById('questionType').value;
        const container = document.getElementById('optionsContainer');
        if (type === 'text' || type === 'paragraph') {
            container.style.display = 'none';
        } else {
            container.style.display = 'block';
        }
    }

    // FUNGSI ISI PRESET KEPUSAN OTOMATIS
    function isiPresetKepuasan() {
        const textarea = document.getElementById('optionsTextarea');
        textarea.value = "Sangat Puas\nPuas\nCukup\nKurang\nSangat Kurang";
    }

    toggleOptions();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>