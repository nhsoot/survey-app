<?php
require_once '../config/database.php';

$token = $_GET['token'] ?? '';

// Find survey by token (simple token generation: md5(id + 'secret'))
$survey = null;
if ($token) {
    $surveys = $pdo->query("SELECT * FROM surveys WHERE status = 'active'")->fetchAll();
    foreach ($surveys as $s) {
        if (md5($s['id'] . 'secret') === $token) {
            $survey = $s;
            break;
        }
    }
}

if (!$survey) {
    die('Survei tidak ditemukan atau tidak aktif.');
}

$questions = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_number");
$questions->execute([$survey['id']]);
$questions = $questions->fetchAll();

$error = '';
$success = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate respondent token
    $respondent_token = md5(uniqid() . $survey['id']);
    $respondent_name = $_POST['respondent_name'] ?? '';
    $respondent_email = $_POST['respondent_email'] ?? '';
    
    // Save response
    $stmt = $pdo->prepare("INSERT INTO responses (survey_id, respondent_token, respondent_name, respondent_email) VALUES (?, ?, ?, ?)");
    $stmt->execute([$survey['id'], $respondent_token, $respondent_name, $respondent_email]);
    $response_id = $pdo->lastInsertId();
    
    // Save answers
    foreach ($questions as $q) {
        $answer = $_POST['question_' . $q['id']] ?? '';
        if (!empty($answer) || !$q['is_required']) {
            $stmt = $pdo->prepare("INSERT INTO answers (response_id, question_id, answer_value) VALUES (?, ?, ?)");
            $stmt->execute([$response_id, $q['id'], $answer]);
        }
    }
    
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey: <?php echo htmlspecialchars($survey['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($success): ?>
                    <div class="card shadow">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">Terima Kasih!</h3>
                            <p class="text-muted">Jawaban Anda telah berhasil disimpan.</p>
                            <a href="<?php echo $_SERVER['PHP_SELF'] . '?token=' . $token; ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-repeat"></i> Kembali ke Survei
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4><?php echo htmlspecialchars($survey['title']); ?></h4>
                            <?php if ($survey['description']): ?>
                                <p class="mb-0 text-light"><?php echo htmlspecialchars($survey['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" id="surveyForm">
                                <div class="mb-3">
                                    <label class="form-label">Nama (Opsional)</label>
                                    <input type="text" name="respondent_name" class="form-control">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Email (Opsional)</label>
                                    <input type="email" name="respondent_email" class="form-control">
                                </div>
                                
                                <hr>
                                
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
                                            <?php foreach ($options as $option): ?>
                                                <div class="form-check">
                                                    <input type="radio" name="question_<?php echo $q['id']; ?>" 
                                                           value="<?php echo htmlspecialchars($option); ?>" 
                                                           class="form-check-input" 
                                                           <?php echo $q['is_required'] ? 'required' : ''; ?>>
                                                    <label class="form-check-label"><?php echo htmlspecialchars($option); ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                        <?php elseif ($q['type'] == 'likert'): ?>
                                            <?php $options = json_decode($q['options'], true); ?>
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center">
                                                    <thead>
                                                        <tr>
                                                            <?php foreach ($options as $option): ?>
                                                                <th><?php echo htmlspecialchars($option); ?></th>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <?php foreach ($options as $option): ?>
                                                                <td>
                                                                    <input type="radio" name="question_<?php echo $q['id']; ?>" 
                                                                           value="<?php echo htmlspecialchars($option); ?>" 
                                                                           class="form-check-input" 
                                                                           <?php echo $q['is_required'] ? 'required' : ''; ?>>
                                                                </td>
                                                            <?php endforeach; ?>
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
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3 text-muted small">
                        <i class="bi bi-shield-lock"></i> Data Anda aman dan hanya digunakan untuk keperluan survei ini.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
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