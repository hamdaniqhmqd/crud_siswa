<?php
require __DIR__ . '/koneksi.php';
require __DIR__ . '/SiswaRepository.php';
header('Content-Type: application/json; charset=utf-8'); // beri tahu browser: isinya JSON
$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim($q) : '';
// Minimal 2 karakter agar tidak mengambil seluruh tabel
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}
$repo = new SiswaRepository($pdo);
$hasil = array_slice($repo->cari(mb_substr($q, 0, 100)), 0, 3); // maksimal 8 saran
echo json_encode($hasil, JSON_UNESCAPED_UNICODE);
