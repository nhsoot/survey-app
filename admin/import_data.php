<?php
// File: admin/import_data.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Catatan: Nanti kita bisa tambahkan logika pengecekan login Admin di sini
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data Survei - Admin Panel</title>
    <!-- Memanggil Bootstrap 5 seperti halaman utama -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    
    <!-- Navbar Admin Sederhana -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-lock"></i> Panel Admin Survei</a>
            <div class="d-flex">
                <!-- Tombol kembali ke halaman publik -->
                <a href="../index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-house-door"></i> Lihat Web Publik</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <!-- Card Form Import -->
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-cloud-upload"></i> Import Data Survei (Format CSV)</h5>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <p class="text-muted mb-4">
                            Unggah file rekapitulasi kepuasan (.csv). Pastikan baris pertama di file Anda adalah judul kolom, dan urutan datanya sesuai dengan format standar.
                        </p>
                        
                        <!-- PENTING: enctype="multipart/form-data" wajib ada untuk upload file -->
                        <form action="proses_import.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="fileCsv" class="form-label fw-bold">Pilih File CSV dari Komputer</label>
                                <!-- accept=".csv" mengunci jendela pemilihan file hanya untuk file CSV -->
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
                
                <!-- Bantuan Panduan Format File -->
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