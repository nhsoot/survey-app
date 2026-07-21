<?php
// File: index.php (root)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Ambil data survei yang aktif dan belum melebihi deadline (end_date)
 $stmt = $pdo->query("SELECT * FROM surveys WHERE status = 'active' AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY created_at DESC");
 $surveys = $stmt->fetchAll();

// Cek apakah yang login mahasiswa
$is_student = isset($_SESSION['student_id']);

// TAMBAHKAN KODE INI: Ambil daftar ID survei yang SUDAH diisi oleh mahasiswa ini
$completed_survey_ids = [];
if ($is_student) {
    $stmtCompleted = $pdo->prepare("SELECT survey_id FROM survey_assignments WHERE student_id = ? AND is_completed = TRUE");
    $stmtCompleted->execute([$_SESSION['student_id']]);
    $completed_survey_ids = $stmtCompleted->fetchAll(PDO::FETCH_COLUMN);
}

 // Ambil data untuk Grafik Rekapitulasi (Kita ambil periode terbaru, misal 2024)
$stmtStats = $pdo->query("SELECT stakeholder, nilai_aktual, nilai_target FROM survey_stats WHERE periode = '2024' ORDER BY id ASC");
$statsData = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

// Siapkan array kosong untuk menampung data yang akan dikirim ke JavaScript
$label_grafik = [];
$nilai_aktual_grafik = [];
$nilai_target_grafik = [];

foreach ($statsData as $row) {
    // Memecah teks stakeholder menjadi array agar di grafik menjadi 2 baris (opsional, agar rapi)
    $teks_label = explode(' ', $row['stakeholder']); 
    $label_grafik[] = count($teks_label) > 1 ? $teks_label : $row['stakeholder']; 
    
    $nilai_aktual_grafik[] = (float) $row['nilai_aktual'];
    // Jika nilai target di database kosong (NULL), kita beri nilai default 80
    $nilai_target_grafik[] = $row['nilai_target'] ? (float) $row['nilai_target'] : 80; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey App - Beranda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Animation CSS untuk Indikator Live -->
    <style>
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
        .pulse-animation { animation: pulse 1.5s infinite; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-clipboard-data"></i> Survey App</a>
            <div class="d-flex">
                <?php if ($is_student): ?>
                    <span class="navbar-text text-white me-3">Halo, <?= htmlspecialchars($_SESSION['student_name'] ?? 'Mahasiswa') ?></span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                <?php else: ?>
                    <a href="admin/login.php" class="btn btn-outline-light btn-sm me-2">Login Admin/Dosen</a>
                    <a href="login_mahasiswa.php" class="btn btn-light btn-sm">Login Mahasiswa</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- ========================================== -->
        <!-- BAGIAN 1: DAFTAR SURVEI                    -->
        <!-- ========================================== -->
        <h2 class="mb-4 fw-bold">Daftar Survei Tersedia</h2>
        
        <div class="row">
            <?php if (empty($surveys)): ?>
                <div class="col-12">
                    <div class="alert alert-info">Belum ada survei yang aktif saat ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($surveys as $survey): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($survey['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($survey['description'], 0, 100)) ?>...</p>
                                
                                <?php if ($survey['end_date']): ?>
                                    <p class="text-danger small"><i class="bi bi-calendar-event"></i> Deadline: <?= date('d M Y', strtotime($survey['end_date'])) ?></p>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <?php if ($is_student): ?>
                                        <?php if (in_array($survey['id'], $completed_survey_ids)): ?>
                                            <button class="btn btn-success" disabled>
                                                <i class="bi bi-check-circle-fill"></i> Sudah Diisi
                                            </button>
                                        <?php else: ?>
                                            <a href="responden/survey.php?id=<?= $survey['id'] ?>" class="btn btn-primary">
                                                <i class="bi bi-pencil-square"></i> Isi Survei
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Tombol untuk Guest -->
                                        <button class="btn btn-secondary" disabled>
                                            <i class="bi bi-lock-fill"></i> Login untuk Mengisi
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ========================================== -->
        <!-- BAGIAN 1.5: GRAFIK REAL-TIME SURVEI TERBARU-->
        <!-- ========================================== -->
        <!-- BAGIAN 1.5: GRAFIK REAL-TIME SURVEI TERBARU -->
        <div class="card shadow-sm border-0 mt-4 mb-5">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0 flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold text-dark mb-0" id="judulGrafikRealtime">
                        <i class="bi bi-bar-chart-line-fill text-primary"></i> Memuat Hasil Survei Terbaru...
                    </h4>
                    <small class="text-muted"><i class="bi bi-info-circle"></i> Skala Penilaian: 1 (Sangat Kurang) s/d 5 (Sangat Puas)</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Badge Jumlah Responden -->
                    <span class="badge bg-secondary px-3 py-2 fs-6" id="badgeResponden">
                        <i class="bi bi-people-fill"></i> 0 Responden
                    </span>
                    <!-- Badge Live Update -->
                    <span class="badge bg-danger pulse-animation px-3 py-2 fs-6">
                        <i class="bi bi-broadcast"></i> LIVE UPDATE
                    </span>
                </div>
            </div>
            <div class="card-body p-4">
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="realtimeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- BAGIAN 2: GRAFIK DASHBOARD (Sesuai Revisi) -->
        <!-- ========================================== -->
        <div class="card shadow-sm border-0 mt-5">
            <div class="card-body p-4 p-md-5">
                <h4 class="fw-bold text-center text-dark mb-2">
                    Rekap Kepuasan Pemangku Kepentingan Program Studi S1 Teknik Komputer
                </h4>
                <p class="text-center text-muted mb-4">(Periode Terkini)</p>

                <!-- Menghindari overlap dengan memberi height absolut -->
                <div style="position: relative; height: 450px; width: 100%;">
                    <canvas id="rekapChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- BAGIAN 3: GRAFIK TREN & DROPDOWN           -->
        <!-- ========================================== -->
        <div class="card shadow-sm border-0 mt-5 mb-5">
            <div class="card-body p-4 p-md-5">
                <!-- Header dan Dropdown -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Tren Kepuasan Wisudawan</h4>
                        <p class="text-muted small mb-3 mb-md-0">Berdasarkan Kategori Layanan (Program Studi S1 Teknik Komputer)</p>
                    </div>
                    
                    <!-- Filter Dropdown -->
                    <div class="d-flex align-items-center">
                        <label for="filterTahun" class="fw-bold me-2 text-secondary">Periode:</label>
                        <select id="filterTahun" class="form-select border-primary fw-semibold shadow-sm w-auto">
                            <option value="2024" selected>Tahun 2024</option>
                            <option value="2023">Tahun 2023</option>
                        </select>
                    </div>
                </div>

                <!-- Wadah Grafik Tren -->
                <div style="position: relative; height: 400px; width: 100%;">
                    <canvas id="trenChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script Chart.js & Plugin untuk Grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0"></script>

    <script>
        // Registrasi plugin
        Chart.register(ChartDataLabels, window['chartjs-plugin-annotation']);

        // ==========================================
        // LOGIKA REAL-TIME CHART (AJAX POLLING)
        // ==========================================
        const ctxRealtime = document.getElementById('realtimeChart').getContext('2d');
        let realtimeChart;

        function fetchRealtimeData() {
            fetch('api_grafik_realtime.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update Judul & Badge Responden
                        document.getElementById('judulGrafikRealtime').innerHTML = `<i class="bi bi-bar-chart-line-fill text-primary"></i> Live Hasil: ${data.survey_title}`;
                        document.getElementById('badgeResponden').innerHTML = `<i class="bi bi-people-fill"></i> ${data.total_responden} Responden`;

                        // Pewarnaan dinamis berdasarkan rata-rata skor
                        const bgColors = data.data.map(val => {
                            if (val >= 4.0) return 'rgba(46, 204, 113, 0.85)'; // Hijau (Bagus)
                            if (val >= 3.0) return 'rgba(241, 196, 15, 0.85)';  // Kuning/Oranye (Sedang)
                            return 'rgba(231, 76, 60, 0.85)';                 // Merah (Kurang)
                        });

                        if (!realtimeChart) {
                            realtimeChart = new Chart(ctxRealtime, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: 'Rata-rata Skor',
                                        data: data.data,
                                        backgroundColor: bgColors,
                                        borderRadius: 6,
                                        maxBarThickness: 100 // Batas lebar batang agar proporsional
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: { duration: 600 },
                                    scales: {
                                        y: { 
                                            beginAtZero: true, 
                                            max: 5,
                                            ticks: { stepSize: 1 }
                                        }
                                    },
                                    plugins: {
                                        legend: { display: false },
                                        datalabels: {
                                            anchor: 'end',
                                            align: 'top',
                                            font: { weight: 'bold', size: 12 },
                                            formatter: (val) => parseFloat(val).toFixed(2)
                                        }
                                    }
                                }
                            });
                        } else {
                            realtimeChart.data.labels = data.labels;
                            realtimeChart.data.datasets[0].data = data.data;
                            realtimeChart.data.datasets[0].backgroundColor = bgColors;
                            realtimeChart.update();
                        }
                    } else if (data.status === 'empty') {
                        document.getElementById('judulGrafikRealtime').innerHTML = `<i class="bi bi-info-circle text-warning"></i> Belum Ada Survei Aktif`;
                    }
                })
                .catch(err => console.error('Error Polling Realtime:', err));
        }

        // Panggil pertama kali & atur interval 3 detik
        fetchRealtimeData();
        setInterval(fetchRealtimeData, 3000);

        // ==========================================
        // LOGIKA GRAFIK REKAPITULASI (BAR CHART)
        // ==========================================
        const labels = <?php echo json_encode($label_grafik); ?>;
        const nilaiTerkini = <?php echo json_encode($nilai_aktual_grafik); ?>;
        const nilaiTarget = <?php echo json_encode($nilai_target_grafik); ?>;

        const warnaTerkini = nilaiTerkini.map((nilai, index) => {
            if (nilai >= nilaiTarget[index]) return 'rgba(46, 204, 113, 0.9)'; // Hijau
            if (nilai < 60) return 'rgba(231, 76, 60, 0.9)'; // Merah
            return 'rgba(243, 156, 18, 0.9)'; // Oranye
        });

        const ctx = document.getElementById('rekapChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { 
                        label: 'Nilai Terkini (%)', 
                        data: nilaiTerkini, 
                        backgroundColor: warnaTerkini, 
                        borderRadius: 4 
                    },
                    { 
                        label: 'Target (%)', 
                        data: nilaiTarget, 
                        backgroundColor: 'rgba(52, 152, 219, 0.4)', 
                        borderColor: '#3498db', 
                        borderWidth: 1, 
                        borderRadius: 4 
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, max: 110, ticks: { stepSize: 20 } } 
                },
                plugins: {
                    legend: { position: 'top', align: 'end' },
                    datalabels: {
                        anchor: 'end', align: 'bottom', color: '#000', font: { weight: 'bold' },
                        formatter: (value, context) => context.datasetIndex === 0 ? value + '%' : ''
                    },
                    annotation: {
                        annotations: {
                            line1: {
                                type: 'line', yMin: 80, yMax: 80, borderColor: '#27ae60', borderWidth: 2, borderDash: [5, 5],
                                label: { display: true, content: 'Target Minimum 80%', position: 'end', backgroundColor: 'rgba(255,255,255,0.8)', color: '#27ae60', font: { weight: 'bold', size: 11 } }
                            }
                        }
                    }
                }
            }
        });

        // ==========================================
        // LOGIKA GRAFIK TREN (LINE CHART)
        // ==========================================
        const dataTren = {
            "2024": {
                labels: ['Maret 2024', 'Mei 2024', 'Agustus 2024', 'Oktober 2024'],
                akademik: [96.5, 97.1, 97.5, 98.0],
                fasilitas: [94.5, 94.8, 96.9, 97.6],
                layanan: [95.0, 94.2, 97.3, 97.9]
            },
            "2023": {
                labels: ['Maret 2023', 'Mei 2023', 'Agustus 2023', 'Oktober 2023'],
                akademik: [95.5, 96.0, 96.3, 97.0],
                fasilitas: [95.2, 95.5, 96.0, 96.5],
                layanan: [97.6, 97.0, 96.5, 97.1]
            }
        };

        const ctxTren = document.getElementById('trenChart').getContext('2d');
        
        let trenChart = new Chart(ctxTren, {
            type: 'line',
            data: {
                labels: dataTren["2024"].labels,
                datasets: [
                    {
                        label: 'Akademik',
                        data: dataTren["2024"].akademik,
                        borderColor: '#3498db',
                        backgroundColor: '#3498db',
                        borderWidth: 3,
                        tension: 0.1,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Fasilitas',
                        data: dataTren["2024"].fasilitas,
                        borderColor: '#2ecc71',
                        backgroundColor: '#2ecc71',
                        borderWidth: 3,
                        tension: 0.1,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    },
                    {
                        label: 'Layanan',
                        data: dataTren["2024"].layanan,
                        borderColor: '#f39c12',
                        backgroundColor: '#f39c12',
                        borderWidth: 3,
                        tension: 0.1,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        min: 90, 
                        max: 100, 
                        ticks: { stepSize: 2 } 
                    }
                },
                plugins: {
                    legend: { position: 'bottom' },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#333',
                        font: { weight: 'bold', size: 10 },
                        formatter: (value) => value + '%'
                    }
                }
            }
        });

        document.getElementById('filterTahun').addEventListener('change', function() {
            const tahunTerpilih = this.value;
            const newData = dataTren[tahunTerpilih];
            
            trenChart.data.labels = newData.labels;
            trenChart.data.datasets[0].data = newData.akademik;
            trenChart.data.datasets[1].data = newData.fasilitas;
            trenChart.data.datasets[2].data = newData.layanan;
            
            trenChart.update();
        });
    </script>
</body>
</html>