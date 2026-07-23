<?php
// File: admin/import_manual.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Guard login admin (WAJIB)
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once '../config/database.php'; // Pastikan path ini benar
$pesan = ''; // Variabel untuk menampung notifikasi sukses/gagal

// 1. Logika Pemrosesan (Berjalan ketika tombol Submit ditekan)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_manual'])) {
    $stakeholder  = $_POST['stakeholder'];
    $periode      = $_POST['periode'];
    $kategori     = $_POST['kategori'];
    $nilai_aktual = $_POST['nilai_aktual'];
    $nilai_target = !empty($_POST['nilai_target']) ? $_POST['nilai_target'] : null;
    $peringkat    = !empty($_POST['peringkat']) ? $_POST['peringkat'] : null;
    try {
        $stmt = $pdo->prepare("INSERT INTO survey_stats (stakeholder, periode, kategori, nilai_aktual, nilai_target, peringkat) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$stakeholder, $periode, $kategori, $nilai_aktual, $nilai_target, $peringkat]);
        $pesan = '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill"></i> Data berhasil ditambahkan ke database!
            <a href="manage_stats.php" class="alert-link fw-bold">Lihat daftar data</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
    } catch (PDOException $e) {
        $pesan = '<div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill"></i> Error: ' . $e->getMessage() . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Input Data Manual - Survey App</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
body { background: #f0f2f5; }
.navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.card-header { border-radius: 12px 12px 0 0 !important; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
</style>
</head>
<body>
<?php $active_menu = 'stats'; ?>
<?php include __DIR__ . '/includes/navbar_admin.php'; ?>

<div class="container py-4">
<div class="row justify-content-center">
<div class="col-md-8">
<?= $pesan ?>
<div class="card shadow border-0">
<div class="card-header bg-primary text-white py-3">
<h5 class="mb-0 fw-bold"><i class="bi bi-keyboard"></i> Input Data Survei Manual</h5>
</div>
<div class="card-body p-4 p-md-5">
<!-- Navigasi antar fitur data statistik -->
<div class="d-flex flex-wrap gap-2 mb-4">
<a href="manage_stats.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-database"></i> Kelola Data</a>
<a href="import_data.php" class="btn btn-sm btn-outline-success"><i class="bi bi-cloud-upload"></i> Import CSV</a>
</div>

<!-- PERBAIKAN: action disamakan dengan nama file (import_manual.php) -->
<form action="import_manual.php" method="POST">
<div class="row mb-3">
<div class="col-md-6">
<label for="stakeholder" class="form-label fw-bold">Stakeholder <span class="text-danger">*</span></label>
<select class="form-select" id="stakeholder" name="stakeholder" required>
<option value="" selected disabled>Pilih Stakeholder...</option>
<option value="Wisudawan">Wisudawan</option>
<option value="Mahasiswa">Mahasiswa (LKMI)</option>
<option value="Dosen">Dosen</option>
<option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
<option value="Mitra Industri">Mitra Industri</option>
<option value="Pengguna Lulusan">Pengguna Lulusan</option>
</select>
</div>
<div class="col-md-6">
<label for="periode" class="form-label fw-bold">Periode <span class="text-danger">*</span></label>
<input type="text" class="form-control" id="periode" name="periode" placeholder="Contoh: 2024 atau Ganjil 2425" required>
</div>
</div>
<div class="mb-3">
<label for="kategori" class="form-label fw-bold">Kategori Layanan <span class="text-danger">*</span></label>
<input type="text" class="form-control" id="kategori" name="kategori" placeholder="Contoh: Akademik, Fasilitas, atau Rata-rata" required>
</div>
<div class="row mb-4">
<div class="col-md-4">
<label for="nilai_aktual" class="form-label fw-bold">Nilai Aktual (%) <span class="text-danger">*</span></label>
<input type="number" step="0.01" min="0" max="100" class="form-control" id="nilai_aktual" name="nilai_aktual" placeholder="0 - 100" required>
</div>
<div class="col-md-4">
<label for="nilai_target" class="form-label fw-bold">Target (%) <span class="text-muted fw-normal">(Opsional)</span></label>
<input type="number" step="0.01" min="0" max="100" class="form-control" id="nilai_target" name="nilai_target" placeholder="0 - 100">
</div>
<div class="col-md-4">
<label for="peringkat" class="form-label fw-bold">Peringkat <span class="text-muted fw-normal">(Opsional)</span></label>
<input type="number" class="form-control" id="peringkat" name="peringkat" placeholder="Khusus EDOM">
</div>
</div>
<hr class="mb-4">
<div class="d-grid gap-2 d-md-flex justify-content-md-end">
<button type="reset" class="btn btn-secondary px-4 py-2"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
<button type="submit" name="simpan_manual" class="btn btn-primary px-4 py-2 fw-bold"><i class="bi bi-save"></i> Simpan Data</button>
</div>
</form>
</div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>