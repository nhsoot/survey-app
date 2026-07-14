<?php
require_once 'config/database.php';

echo "<h2>Setup Admin</h2>";

try {
    // Cek apakah admin sudah ada
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "<p style='color:orange;'>⚠️ Admin sudah ada. Menghapus admin lama...</p>";
        $pdo->exec("DELETE FROM admins");
    }
    
    // Buat admin baru dengan password hash
    $username = 'admin';
    $password = 'admin123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $email = 'admin@survey.com';
    
    $stmt = $pdo->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $email]);
    
    echo "<p style='color:green;'>✅ Admin berhasil dibuat!</p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><a href='admin/login.php' class='btn btn-primary'>Login Sekarang</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Pastikan database sudah dibuat dan koneksi benar.</p>";
}
?>