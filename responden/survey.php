<?php
// File: responden/survey.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// 1. CEK LOGIN MAHASISWA
if (!isset($_SESSION['student_id'])) {
    header('Location: ../login_mahasiswa.php');
    exit();
}

 $student_id = $_SESSION['student_id'];
 $survey_id = $_GET['id'] ?? 0;

// 2. AMBIL DATA SURVEI
 $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND status = 'active'");
 $stmt->execute([$survey_id]);
 $survey = $stmt->fetch();

if (!$survey) {
    die('Survei tidak ditemukan atau tidak aktif.');
}

// 3. CEK DEADLINE
 $today = date('Y-m-d');
if (!empty($survey['end_date']) && $today > $survey['end_date']) {
    die('Mohon maaf, deadline pengisian survei ini telah berakhir.');
}

// 4. CEK IZIN MAHASISWA (Apakah di-assign ke survei ini?)
 $stmt = $pdo->prepare("SELECT * FROM survey_assignments WHERE survey_id = ? AND student_id = ?");
 $stmt->execute([$survey_id, $student_id]);
 $assignment = $stmt->fetch();

if (!$assignment) {
    die('Anda tidak memiliki izin untuk mengisi survei ini.');
}

// 5. CEK APAKAH SUDAH PERNAH MENGISI (Batas 1x)
if ($assignment['is_completed']) {
    die('Anda sudah pernah mengisi survei ini. Terima kasih.');
}

// Ambil daftar pertanyaan
 $questions = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_number");
 $questions->execute([$survey['id']]);
 $questions = $questions->fetchAll();

 $error = '';
 $success = '';

// 6. PROSES PENGIRIMAN JAWABAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Simpan ke tabel responses (sekarang pakai student_id, bukan token)
        $stmt = $pdo->prepare("INSERT INTO responses (survey_id, student_id) VALUES (?, ?)");
        $stmt->execute([$survey['id'], $student_id]);
        $response_id = $pdo->lastInsertId();
        
        // Simpan jawaban
        foreach ($questions as $q) {
            $answer = $_POST['question_' . $q['id']] ?? '';
            if (!empty($answer) || !$q['is_required']) {
                $stmt = $pdo->prepare("INSERT INTO answers (response_id, question_id, answer_value) VALUES (?, ?, ?)");
                $stmt->execute([$response_id, $q['id'], $answer]);
            }
        }

        // Update tabel assignment menjadi is_completed = TRUE
        $stmt = $pdo->prepare("UPDATE survey_assignments SET is_completed = TRUE, completed_at = NOW() WHERE survey_id = ? AND student_id = ?");
        $stmt->execute([$survey['id'], $student_id]);

        $pdo->commit();
        $success = true;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Terjadi kesalahan saat menyimpan jawaban: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey: <?php echo htmlspecialchars($survey['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php"><i class="bi bi-clipboard-data"></i> Survey App</a>
            <span class="text-white">Login sebagai: <strong><?= htmlspecialchars($_SESSION['student_name']) ?></strong></span>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($success): ?>
                    <div class="card shadow border-0">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">Terima Kasih!</h3>
                            <p class="text-muted">Jawaban Anda telah berhasil disimpan.</p>
                            <a href="../index.php" class="btn btn-primary">
                                <i class="bi bi-house"></i> Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow border-0">
                        <div class="card-header bg-primary text-white" style="border-radius: 12px 12px 0 0;">
                            <h4 class="mb-0"><?php echo htmlspecialchars($survey['title']); ?></h4>
                            <?php if ($survey['description']): ?>
                                <p class="mb-0 text-light small mt-2"><?php echo htmlspecialchars($survey['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" id="surveyForm">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Nama dan Email tidak perlu diisi lagi karena Anda sudah login sebagai <strong><?= htmlspecialchars($_SESSION['student_name']) ?></strong>.
                                </div>
                                
                                <hr>
                                
                                <?php if (empty($questions)): ?>
                                    <p class="text-center text-danger">Survei ini belum memiliki pertanyaan.</p>
                                <?php else: ?>
                                    <?php foreach ($questions as $index => $q): ?>
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">
                                                <?php echo $index + 1; ?>. <?php echo htmlspecialchars($q['question_text']); ?>
                                                <?php if ($q['is_required']): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                            </label>
                                            
                                            <?php if ($q['type'] == 'multiple_choice'): ?>
                                                <?php $options = json_decode($q['options'], true); ?>
                                                <?php if(is_array($options)): foreach ($options as $option): ?>
                                                    <div class="form-check">
                                                        <input type="radio" name="question_<?php echo $q['id']; ?>" 
                                                               value="<?php echo htmlspecialchars($option); ?>" 
                                                               class="form-check-input" 
                                                               <?php echo $q['is_required'] ? 'required' : ''; ?>>
                                                        <label class="form-check-label"><?php echo htmlspecialchars($option); ?></label>
                                                    </div>
                                                <?php endforeach; endif; ?>
                                                
                                            <?php elseif ($q['type'] == 'likert'): ?>
                                                <?php $options = json_decode($q['options'], true); ?>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered text-center">
                                                        <thead>
                                                            <tr>
                                                                <?php if(is_array($options)): foreach ($options as $option): ?>
                                                                    <th><?php echo htmlspecialchars($option); ?></th>
                                                                <?php endforeach; endif; ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <?php if(is_array($options)): foreach ($options as $option): ?>
                                                                    <td>
                                                                        <input type="radio" name="question_<?php echo $q['id']; ?>" 
                                                                               value="<?php echo htmlspecialchars($option); ?>" 
                                                                               class="form-check-input" 
                                                                               <?php echo $q['is_required'] ? 'required' : ''; ?>>
                                                                    </td>
                                                                <?php endforeach; endif; ?>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                            <?php elseif ($q['type'] == 'text'): ?>
                                                <input type="text" name="question_<?php echo $q['id']; ?>" 
                                                       class="form-control" <?php echo $q['is_required'] ? 'required' : ''; ?>>
                                                       
                                            <?php elseif ($q['type'] == 'paragraph'): ?>
                                                <textarea name="question_<?php echo $q['id']; ?>" 
                                                          class="form-control" rows="3" <?php echo $q['is_required'] ? 'required' : ''; ?>></textarea>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-send"></i> Kirim Jawaban
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('surveyForm')?.addEventListener('submit', function(e) {
        const required = this.querySelectorAll('[required]');
        let valid = true;
        required.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Mohon isi semua pertanyaan yang wajib (ditandai *).');
        }
    });
    </script>
</body>
</html>