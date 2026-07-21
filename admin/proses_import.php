<?php
// File: proses_import.php
require_once '../config/database.php'; // Sesuaikan dengan lokasi file koneksimu

if (isset($_POST['import'])) {
    // 1. Cek apakah ada file yang diunggah dan tidak ada error
    if (isset($_FILES['fileCsv']) && $_FILES['fileCsv']['error'] == 0) {
        
        $fileTmp = $_FILES['fileCsv']['tmp_name'];
        
        // 2. Buka file CSV-nya (Mode 'r' untuk Read/Membaca)
        if (($handle = fopen($fileTmp, "r")) !== FALSE) {
            
            // Lewati baris pertama jika itu adalah Header (Judul Kolom)
            // Hapus baris ini kalau CSV-mu tidak pakai baris judul
            fgetcsv($handle, 1000, ","); 

            // 3. Looping: Baca baris demi baris sampai habis
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Di dalam CSV, data menjadi array indeks:
                // $data[0] = Stakeholder (misal: 'Wisudawan')
                // $data[1] = Periode (misal: '2024')
                // $data[2] = Kategori (misal: 'Akademik')
                // $data[3] = Nilai Terkini (misal: 95.5)
                
                $stakeholder = $data[0];
                $periode     = $data[1];
                $kategori    = $data[2];
                $nilai       = (float) $data[3]; // Pastikan jadi angka desimal
                
                // 4. Masukkan ke Database (Tabel survey_stats yang kita bahas sebelumnya)
                // Menggunakan Prepared Statement agar aman dari SQL Injection
                $stmt = $pdo->prepare("INSERT INTO survey_stats (stakeholder, periode, kategori, nilai_aktual) VALUES (?, ?, ?, ?)");
                $stmt->execute([$stakeholder, $periode, $kategori, $nilai]);
            }
            
            // Tutup file setelah selesai
            fclose($handle);
            
            // Kembalikan admin ke halaman sebelumnya dengan pesan sukses
            echo "<script>alert('Data CSV berhasil diimpor!'); window.location.href='index.php';</script>";
        } else {
            echo "Gagal membuka file CSV.";
        }
    } else {
        echo "Silakan pilih file CSV yang valid.";
    }
}
?>