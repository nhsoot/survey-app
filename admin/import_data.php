<?php
// File: admin/import_data.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Guard login admin (WAJIB)
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Import Data Survei - Survey App</title>
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
<div class="card shadow-sm border-0">
<div class="card-header bg-primary text-white py-3">
<h5 class="mb-0 fw-bold"><i class="bi bi-cloud-upload"></i> Import Data Survei (Format CSV)</h5>
</div>
<div class="card-body p-4 p-md-5">
<!-- Navigasi antar fitur data statistik -->
<div class="d-flex flex-wrap gap-2 mb-4">
<a href="manage_stats.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-database"></i> Kelola Data</a>
<a href="import_manual.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-keyboard"></i> Input Manual</a>
</div>

<p class="text-muted mb-4">
Unggah file rekapitulasi kepuasan (.csv). Pastikan baris pertama di file Anda adalah judul kolom, dan urutan datanya sesuai dengan format standar.
</p>
<!-- enctype="multipart/form-data" wajib untuk upload file -->
<form action="proses_import.php" method="POST" enctype="multipart/form-data">
<div class="mb-4">
<label for="fileCsv" class="form-label fw-bold">Pilih File CSV dari Komputer</label>
<input class="form-control form-control-lg" type="file" id="fileCsv" name="fileCsv" accept=".csv" required>
<div class="form-text">Hanya menerima ekstensi .csv yang dipisahkan dengan koma (Comma delimited).</div>
</div>
<div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
<button type="submit" name="import" class="btn btn-success px-4 py-2 fw-bold">
<i class="bi bi-upload"></i> Unggah & Simpan ke Database
</button>
</div>
</form>
</div>
</div>

<div class="alert alert-info mt-4 shadow-sm">
<h6 class="alert-heading fw-bold"><i class="bi bi-info-circle"></i> Panduan Format File CSV</h6>
<hr>
<p class="mb-2">Jika Anda membuat file menggunakan Microsoft Excel, buatlah 4 kolom berurutan dan simpan (Save As) menggunakan format <strong>CSV (Comma delimited)</strong>. Contoh isinya:</p>
<div class="bg-white p-3 rounded border" style="font-family: monospace;">
Stakeholder,Periode,Kategori,Nilai<br>
Wisudawan,2024,Akademik,96.5<br>
Wisudawan,2024,Fasilitas,94.5<br>
Dosen,2024,Rata-rata,93.4
</div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>