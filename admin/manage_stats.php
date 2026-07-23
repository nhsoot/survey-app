<?php
// File: admin/manage_stats.php  (Pusat kelola data grafik: lihat + hapus + gateway import)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Guard login admin (WAJIB - celah keamanan kalau tidak ada)
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once '../config/database.php';

$page_title = 'Kelola Data Statistik';
$admin_id   = $_SESSION['admin_id'];
$error      = '';
$success    = '';

// 1. Handle HAPUS data statistik
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $del_id = $_GET['id'];
    if ($del_id === '' || !ctype_digit((string)$del_id)) {
        $error = 'ID baris tidak valid.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM survey_stats WHERE id = ?");
            $stmt->execute([$del_id]);
            if ($stmt->rowCount() > 0) {
                header('Location: manage_stats.php?deleted=1');
                exit();
            } else {
                $error = 'Data tidak ditemukan (mungkin sudah dihapus).';
            }
        } catch (PDOException $e) {
            // Kalau tabel belum punya kolom `id`, pesan error akan mengarah ke sini
            $error = 'Gagal menghapus data: ' . $e->getMessage()
                   . ' <br><small>Jika pesan menyebut kolom "id" tidak ditemukan, jalankan perintah ALTER TABLE pada catatan dokumentasi.</small>';
        }
    }
}
if (isset($_GET['deleted'])) {
    $success = 'Data statistik berhasil dihapus!';
}

// 2. Ambil semua data statistik
try {
    // ORDER BY tidak bergantung pada kolom id (aman walau id belum ada)
    $stmt  = $pdo->query("SELECT * FROM survey_stats ORDER BY stakeholder ASC, periode DESC, kategori ASC");
    $stats = $stmt->fetchAll();
} catch (PDOException $e) {
    $stats = [];
    $error = 'Gagal memuat data statistik: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Data Statistik - Survey App</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
body { background: #f0f2f5; }
.navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
.table th { border-top: none; font-weight: 600; color: #4a5568; }
/* Tombol Aksi: senada dengan halaman admin lain */
.btn-aksi { white-space: nowrap; }
.btn-aksi .label-aksi { margin-left: 2px; }
@media (max-width: 768px) {
    .btn-aksi .label-aksi { display: none; } /* di HP jadi ikon saja + tooltip */
}
</style>
</head>
<body>
<?php $active_menu = 'stats'; ?>
<?php include __DIR__ . '/includes/navbar_admin.php'; ?>

<div class="container mt-4">
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
<h4 class="mb-0"><i class="bi bi-database"></i> Kelola Data Statistik</h4>
<div class="d-flex gap-2">
<a href="import_data.php" class="btn btn-success">
<i class="bi bi-cloud-upload"></i> Import CSV
</a>
<a href="import_manual.php" class="btn btn-primary">
<i class="bi bi-keyboard"></i> Input Manual
</a>
</div>
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

<div class="card">
<div class="card-body">
<?php if (count($stats) > 0): ?>
<div class="table-responsive">
<table class="table table-hover align-middle">
<thead>
<tr>
<th>#</th>
<th>Stakeholder</th>
<th>Periode</th>
<th>Kategori</th>
<th>Nilai Aktual</th>
<th>Target</th>
<th>Peringkat</th>
<th class="text-end">Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($stats as $i => $s): ?>
<?php
// Warna nilai aktual: merah kalau di bawah target, hijau kalau mencapai target
$aktual = $s['nilai_aktual'] ?? '';
$target = $s['nilai_target'] ?? null;
$warn   = '';
if ($target !== null && $target !== '' && is_numeric($aktual) && is_numeric($target)) {
    $warn = ((float)$aktual < (float)$target) ? 'text-danger fw-bold' : 'text-success fw-bold';
}
?>
<tr>
<td><?php echo $i + 1; ?></td>
<td><strong><?php echo htmlspecialchars($s['stakeholder']); ?></strong></td>
<td><?php echo htmlspecialchars($s['periode']); ?></td>
<td><?php echo htmlspecialchars($s['kategori']); ?></td>
<td class="<?php echo $warn; ?>"><?php echo htmlspecialchars($aktual); ?>%</td>
<td><?php echo ($target !== null && $target !== '') ? htmlspecialchars($target) . '%' : '<span class="text-muted">-</span>'; ?></td>
<td><?php echo ($s['peringkat'] !== null && $s['peringkat'] !== '') ? htmlspecialchars($s['peringkat']) : '<span class="text-muted">-</span>'; ?></td>
<td class="text-end">
<a href="?delete=1&id=<?php echo $s['id']; ?>"
   class="btn btn-sm btn-danger btn-aksi btn-hapus"
   title="Hapus Data"
   data-info="<?php echo htmlspecialchars($s['stakeholder'] . ' | ' . $s['periode'] . ' | ' . $s['kategori'], ENT_QUOTES); ?>">
    <i class="bi bi-trash"></i> <span class="label-aksi">Hapus</span>
</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<div class="text-center py-5">
<i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e0;"></i>
<h5 class="mt-3 text-muted">Belum ada data statistik</h5>
<p class="text-muted">Mulai dengan mengimpor file CSV atau input manual.</p>
<div class="d-flex justify-content-center gap-2">
<a href="import_data.php" class="btn btn-success"><i class="bi bi-cloud-upload"></i> Import CSV</a>
<a href="import_manual.php" class="btn btn-primary"><i class="bi bi-keyboard"></i> Input Manual</a>
</div>
</div>
<?php endif; ?>
</div>
</div>
</div>

<!-- Konfirmasi hapus yang aman (data dibaca via data-info, bukan literal JS) -->
<script>
document.querySelectorAll('.btn-hapus').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        var info = btn.getAttribute('data-info');
        if (!confirm('Hapus data statistik ini?\n' + info)) {
            e.preventDefault();
        }
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>