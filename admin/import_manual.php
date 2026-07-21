<?php
// File: admin/input_manual.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php'; // Pastikan path ini benar

$pesan = ''; // Variabel untuk menampung notifikasi sukses/gagal

// 1. Logika Pemrosesan (Berjalan ketika tombol Submit ditekan)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_manual'])) {
    // Ambil data dari form
    $stakeholder  = $_POST['stakeholder'];
    $periode      = $_POST['periode'];
    $kategori     = $_POST['kategori'];
    $nilai_aktual = $_POST['nilai_aktual'];
    
    // Nilai target dan peringkat bersifat opsional, jika kosong jadikan NULL
    $nilai_target = !empty($_POST['nilai_target']) ? $_POST['nilai_target'] : null;
    $peringkat    = !empty($_POST['peringkat']) ? $_POST['peringkat'] : null;

    try {
        // Query Insert menggunakan Prepared Statement (Aman dari SQL Injection)
        $stmt = $pdo->prepare("INSERT INTO survey_stats (stakeholder, periode, kategori, nilai_aktual, nilai_target, peringkat) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$stakeholder, $periode, $kategori, $nilai_aktual, $nilai_target, $peringkat]);
        
        // Tampilkan pesan sukses
        $pesan = '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill"></i> Data berhasil ditambahkan ke database!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    } catch (PDOException $e) {
        // Tampilkan pesan error jika gagal
        $pesan = '<div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill"></i> Error: ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Manual - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-lock"></i> Panel Admin Survei</a>
            <div class="d-flex gap-2">
                <a href="import_data.php" class="btn btn-outline-light btn-sm"><i class="bi bi-cloud-upload"></i> Ke Import CSV</a>
                <a href="../index.php" class="btn btn-light btn-sm"><i class="bi bi-house-door"></i> Web Publik</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <!-- Tampilkan Notifikasi di sini -->
                <?= $pesan ?>

                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-keyboard"></i> Input Data Survei Manual</h5>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        
                        <form action="input_manual.php" method="POST">
                            
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
                                    <!-- step="0.01" memungkinkan input angka desimal seperti 95.5 -->
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