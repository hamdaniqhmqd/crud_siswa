<?php
$host = 'localhost'; // MySQL berjalan di komputer sendiri
$dbname = 'db_sekolah'; // nama database yang akan dipakai
$user = 'root'; // nama pengguna MySQL pada XAMPP
$pass = ''; // password MySQL; sesuaikan dengan pengaturan komputermu

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", // informasi jenis database, alamat, nama DB, dan karakter
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // PHP memberi tahu jika ada kesalahan database
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // hasil dibaca sebagai pasangan nama kolom dan nilai
        ]
    );
} catch (PDOException $e) {
    exit('Koneksi database gagal: ' . $e->getMessage()); // pesan membantu kita mencari penyebab saat latihan
}
