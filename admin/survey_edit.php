<?php
// File: admin/survey_edit.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$page_title = 'Edit Survei';
$admin_id = $_SESSION['admin_id'];
$survey_id = $_GET['id'] ?? 0;

$error = '';
$success = '';

// 1. Ambil data survei yang akan diedit
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

// 2. Ambil daftar kelas unik dan data mahasiswa
try {
    $stmtClasses = $pdo->query("SELECT DISTINCT kelas FROM students WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
    $classes = $stmtClasses->fetchAll(PDO::FETCH_COLUMN);

    $stmtStudents = $pdo->query("SELECT id, nim, name, kelas FROM students ORDER BY kelas ASC, name ASC");
    $students = $stmtStudents->fetchAll();

    // Ambil ID mahasiswa yang SAAT INI sudah ditugaskan mengisi survei ini
    $stmtAssigned = $pdo->prepare("SELECT student_id FROM survey_assignments WHERE survey_id = ?");
    $stmtAssigned->execute([$survey_id]);
    $assigned_student_ids = $stmtAssigned->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $classes = [];
    $students = [];
    $assigned_student_ids = [];
    $error = "Gagal memuat data mahasiswa: " . $e->getMessage();
}

// 3. Proses Update Data saat Form Disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'draft';
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $selected_students = $_POST['student_ids'] ?? [];

    if (empty($title)) {
        $error = 'Judul survei harus diisi!';
    } else {
        try {
            $pdo->beginTransaction();

            // A. Update data survei utama
            $stmt = $pdo->prepare("UPDATE surveys SET title = ?, description = ?, status = ?, end_date = ? WHERE id = ? AND admin_id = ?");
            $stmt->execute([$title, $description, $status, $end_date, $survey_id, $admin_id]);

            // B. Update daftar mahasiswa (Hapus yang lama, masukkan yang baru terpilih)
            $stmtDel = $pdo->prepare("DELETE FROM survey_assignments WHERE survey_id = ?");
            $stmtDel->execute([$survey_id]);

            if (!empty($selected_students)) {
                $assignStmt = $pdo->prepare("INSERT INTO survey_assignments (survey_id, student_id) VALUES (?, ?)");
                foreach ($selected_students as $student_id) {
                    $assignStmt->execute([$survey_id, $student_id]);
                }
            }

            $pdo->commit();

            // Refresh data lokal agar perubahan langsung tercermin di form
            $survey['title'] = $title;
            $survey['description'] = $description;
            $survey['status'] = $status;
            $survey['end_date'] = $end_date;
            $assigned_student_ids = $selected_students;

            $success = 'Survei berhasil diperbarui!';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal memperbarui survei: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Survei - Survey App</title>
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
        select[multiple] { min-height: 180px; }
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
                        <i class="bi bi-pencil-square"></i> Edit Survei
                    </div>
                    <div class="card-body">
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
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Judul Survei <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($survey['title']) ?>" required autofocus>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($survey['description']) ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="draft" <?= $survey['status'] === 'draft' ? 'selected' : '' ?>>Draft (Belum Aktif)</option>
                                        <option value="active" <?= $survey['status'] === 'active' ? 'selected' : '' ?>>Aktif (Bisa Diisi)</option>
                                        <option value="inactive" <?= $survey['status'] === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Deadline Pengisian</label>
                                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($survey['end_date'] ?? '') ?>">
                                    <small class="text-muted">Kosongkan jika tidak ada deadline.</small>
                                </div>
                            </div>

                            <!-- MENU FILTER KELAS -->
                            <div class="mb-3">
                                <label class="form-label text-primary fw-bold"><i class="bi bi-funnel"></i> Filter Berdasarkan Kelas</label>
                                <select class="form-select border-primary text-primary fw-semibold" id="filterKelas">
                                    <option value="all">-- Tampilkan Semua Kelas --</option>
                                    <?php foreach ($classes as $kelas): ?>
                                        <option value="<?= htmlspecialchars($kelas) ?>"><?= htmlspecialchars($kelas) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- DAFTAR MAHASISWA (Gaya Checkbox List Baru) -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-end mb-2">
                                    <label class="form-label mb-0">
                                        Pilih Mahasiswa yang Boleh Mengisi
                                        <!-- Badge Counter Dinamis -->
                                        <span class="badge bg-primary ms-2" id="selectedCount">0 Terpilih</span>
                                    </label>
                                    
                                    <!-- Grup Tombol Aksi -->
                                    <div class="btn-group shadow-sm">
                                        <button type="button" class="btn btn-sm btn-outline-success" id="btnPilihSemua">
                                            <i class="bi bi-check-all"></i> Pilih Semua
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnBatalSemua">
                                            <i class="bi bi-x"></i> Batalkan
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Kotak Scrollable Checkbox -->
                                <div class="border border-secondary-subtle rounded p-3 bg-white shadow-sm" style="max-height: 280px; overflow-y: auto;" id="studentListContainer">
                                    <?php if (empty($students)): ?>
                                        <div class="text-muted text-center py-3">
                                            <i class="bi bi-inbox fs-4 d-block mb-2"></i> Belum ada data mahasiswa.
                                        </div>
                                    <?php else: ?>
                                        <?php $prev_kelas = null; ?>
                                            <?php foreach ($students as $student): ?>
                                            <?php
                                            $is_selected = isset($assigned_student_ids) && in_array($student['id'], $assigned_student_ids) ? 'checked' : '';
                                            $kelas_now = $student['kelas'] ?? '';
                                            ?>
                                                <?php if ($kelas_now !== $prev_kelas): ?>
                                                    <!-- 🏷️ Header pemisah kelas (muncul tiap ganti kelompok kelas) -->
                                                    <div class="kelas-header mt-3 mb-2" data-kelas="<?= htmlspecialchars($kelas_now) ?>">
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fw-semibold">
                                                            <i class="bi bi-mortarboard-fill"></i> Kelas <?= htmlspecialchars($kelas_now !== '' ? $kelas_now : '-') ?>
                                                        </span>
                                                    </div>
                                                    <?php $prev_kelas = $kelas_now; ?>
                                                <?php endif; ?>

                                                <!-- Wrapper tiap mahasiswa (memiliki data-kelas untuk filter) -->
                                                <div class="form-check student-item mb-2 border-bottom pb-2" data-kelas="<?= htmlspecialchars($kelas_now) ?>">
                                                    <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]"
                                                        value="<?= $student['id'] ?>" id="std_<?= $student['id'] ?>" <?= $is_selected ?> style="cursor: pointer;">
                                                    <label class="form-check-label w-100" for="std_<?= $student['id'] ?>" style="cursor: pointer;">
                                                        <span class="fw-medium text-dark"><?= htmlspecialchars($student['nim'] . ' - ' . $student['name']) ?></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Survei
                                </button>
                                <a href="surveys.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT UNTUK FILTER, CHECKBOX, DAN COUNTER -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const filterKelas    = document.getElementById('filterKelas');
        const studentItems   = document.querySelectorAll('.student-item');                 // untuk Pilih/Batalkan
        const filterableItems = document.querySelectorAll('.student-item, .kelas-header'); // untuk show/hide (termasuk header kelas)
        const checkboxes     = document.querySelectorAll('.student-checkbox');
        const btnPilihSemua  = document.getElementById('btnPilihSemua');
        const btnBatalSemua  = document.getElementById('btnBatalSemua');
        const selectedCount  = document.getElementById('selectedCount');

        // 1. Update badge "X Terpilih"
        function updateCounter() {
            let count = 0;
            checkboxes.forEach(cb => { if (cb.checked) count++; });
            selectedCount.innerText = count + " Terpilih";
            if (count === 0) {
                selectedCount.classList.replace('bg-primary', 'bg-secondary');
            } else {
                selectedCount.classList.replace('bg-secondary', 'bg-primary');
            }
        }

        // 2. Filter kelas → sembunyikan/tampilkan mahasiswa DAN header kelasnya
        filterKelas.addEventListener('change', function() {
            const selectedKelas = this.value;
            filterableItems.forEach(item => {
                const optKelas = item.getAttribute('data-kelas');
                item.style.display = (selectedKelas === 'all' || optKelas === selectedKelas) ? '' : 'none';
            });
        });

        // 3. Pilih Semua (hanya yang sedang tampil)
        btnPilihSemua.addEventListener('click', function() {
            studentItems.forEach(item => {
                if (item.style.display !== 'none') {
                    const cb = item.querySelector('.student-checkbox');
                    if (cb) cb.checked = true;
                }
            });
            updateCounter();
        });

        // 4. Batalkan (hanya yang sedang tampil)
        btnBatalSemua.addEventListener('click', function() {
            studentItems.forEach(item => {
                if (item.style.display !== 'none') {
                    const cb = item.querySelector('.student-checkbox');
                    if (cb) cb.checked = false;
                }
            });
            updateCounter();
        });

        // 5. Counter update saat checkbox diklik manual
        checkboxes.forEach(cb => { cb.addEventListener('change', updateCounter); });

        updateCounter();
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>