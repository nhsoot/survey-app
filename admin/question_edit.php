<?php
// File: admin/question_edit.php  (Pendekatan A: halaman edit pertanyaan terpisah)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once '../config/database.php';

$page_title = 'Edit Pertanyaan';
$admin_id   = $_SESSION['admin_id'];
$survey_id  = $_GET['survey_id'] ?? 0;
$qid        = $_GET['qid'] ?? 0;
$error      = '';

// 1. Ambil & verifikasi survei milik admin ini (penjaga keamanan)
try {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ? AND admin_id = ?");
    $stmt->execute([$survey_id, $admin_id]);
    $survey = $stmt->fetch();
    if (!$survey) {
        header('Location: surveys.php');
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// 2. Ambil & verifikasi pertanyaan benar-benar milik survei ini
try {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND survey_id = ?");
    $stmt->execute([$qid, $survey_id]);
    $question = $stmt->fetch();
    if (!$question) {
        header('Location: questions.php?survey_id=' . $survey_id);
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// 3. Proses UPDATE saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text']);
    $type          = $_POST['type'];
    $options       = $_POST['options'] ?? '';
    $is_required   = isset($_POST['is_required']) ? 1 : 0;

    if (empty($question_text)) {
        $error = 'Teks pertanyaan harus diisi!';
    } else {
        try {
            // Pilihan hanya disimpan untuk tipe multiple_choice / likert (sama seperti form tambah)
            $options_json = null;
            if (in_array($type, ['multiple_choice', 'likert'])) {
                $options_array = array_values(array_filter(array_map('trim', explode("\n", $options))));
                $options_json  = json_encode($options_array);
            }
            // Catatan: order_number sengaja TIDAK diubah -> posisi pertanyaan tetap
            $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, type = ?, options = ?, is_required = ? WHERE id = ? AND survey_id = ?");
            $stmt->execute([$question_text, $type, $options_json, $is_required, $qid, $survey_id]);

            header('Location: questions.php?survey_id=' . $survey_id . '&updated=1');
            exit();
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui pertanyaan: ' . $e->getMessage();
        }
    }
}

// Siapkan isi textarea pilihan: decode JSON -> satu pilihan per baris
$options_for_textarea = '';
if (!empty($question['options'])) {
    $decoded = json_decode($question['options'], true);
    if (is_array($decoded)) {
        $options_for_textarea = implode("\n", $decoded);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Pertanyaan - Survey App</title>
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
</style>
</head>
<body>
<?php $active_menu = 'surveys'; ?>
<?php include __DIR__ . '/includes/navbar_admin.php'; ?>

<div class="container mt-4 mb-5">
<div class="row">
<div class="col-md-8 mx-auto">
<div class="card">
<div class="card-header">
<i class="bi bi-pencil-square"></i> Edit Pertanyaan
</div>
<div class="card-body">
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
<i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
<div class="mb-3">
<label class="form-label">Teks Pertanyaan <span class="text-danger">*</span></label>
<input type="text" name="question_text" class="form-control"
value="<?php echo htmlspecialchars($question['question_text']); ?>" required autofocus>
</div>

<div class="mb-3">
<label class="form-label">Tipe Pertanyaan</label>
<select name="type" class="form-select" id="questionType" onchange="toggleOptions()">
<option value="multiple_choice" <?php echo $question['type'] === 'multiple_choice' ? 'selected' : ''; ?>>Pilihan Ganda</option>
<option value="likert"          <?php echo $question['type'] === 'likert'          ? 'selected' : ''; ?>>Skala Likert</option>
<option value="text"            <?php echo $question['type'] === 'text'            ? 'selected' : ''; ?>>Teks Singkat</option>
<option value="paragraph"       <?php echo $question['type'] === 'paragraph'       ? 'selected' : ''; ?>>Paragraf</option>
</select>
<small class="text-muted">Mengubah tipe ke Teks/Paragraf akan menghapus daftar pilihan.</small>
</div>

<div class="mb-3" id="optionsContainer">
<label class="form-label">Pilihan (satu per baris)</label>
<textarea name="options" id="optionsTextarea" class="form-control" rows="5"><?php echo htmlspecialchars($options_for_textarea); ?></textarea>
<small class="text-muted">Untuk pilihan ganda dan skala likert</small>
</div>

<div class="mb-3 form-check">
<input type="checkbox" name="is_required" class="form-check-input" id="isRequired"
<?php echo $question['is_required'] ? 'checked' : ''; ?>>
<label class="form-check-label" for="isRequired">Wajib diisi</label>
</div>

<div class="d-flex gap-2 mt-4">
<button type="submit" class="btn btn-primary">
<i class="bi bi-save"></i> Update Pertanyaan
</button>
<a href="questions.php?survey_id=<?php echo $survey_id; ?>" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Kembali
</a>
</div>
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
    container.style.display = (type === 'text' || type === 'paragraph') ? 'none' : 'block';
}
// Sesuaikan tampilan container dengan tipe pertanyaan saat halaman pertama dibuka
toggleOptions();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>