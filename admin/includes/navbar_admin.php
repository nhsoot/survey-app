<?php
// File: admin/includes/navbar_admin.php
// Navbar admin PUSAT — dipakai semua halaman admin via include.
// Ubah menu di SINI SAJA, semua halaman otomatis ikut.

// Variabel pengendali (aman walau halaman lupa men-set-nya)
$active_menu         = $active_menu ?? '';          // 'dashboard' | 'surveys' | 'stats'
$show_export_diagram = $show_export_diagram ?? false; // true hanya di halaman yang butuh
$admin_label         = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-clipboard-data"></i> Survey App</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- KIRI: menu utama (class "active" otomatis sesuai $active_menu) -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_menu === 'surveys' ? 'active' : '' ?>" href="surveys.php">
                        <i class="bi bi-file-earmark-text"></i> Survei
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $active_menu === 'stats' ? 'active' : '' ?>" href="manage_stats.php">
                        <i class="bi bi-bar-chart-line"></i> Data Statistik
                    </a>
                </li>
            </ul>

            <!-- KANAN: dropdown user (1 tombol, tidak memadatkan navbar) -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#"
                       id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-sm-inline"><?= htmlspecialchars($admin_label) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userMenu">
                        <li>
                            <a class="dropdown-item" href="../index.php" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-globe2 me-2"></i> Web Publik
                            </a>
                        </li>
                        <?php if ($show_export_diagram): ?>
                        <li>
                            <a class="dropdown-item" href="export_diagram.php">
                                <i class="bi bi-image me-2"></i> Ekspor Diagram
                            </a>
                        </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>