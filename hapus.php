<?php
require __DIR__ . '/koneksi.php';
require __DIR__ . '/SiswaRepository.php';
$repo = new SiswaRepository($pdo);
// Hanya menerima permintaan POST; akses langsung lewat URL ditolak
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // 405 = metode tidak diizinkan
    exit('Metode tidak diizinkan.');
}
$id = filter_input(INPUT_POST, 'id_siswa', FILTER_VALIDATE_INT); // ambil dan validasi ID dari form
if (!$id || $id < 1) {
    header('Location: index.php?status=gagal');
    exit;
}
// Repository menghapus berdasarkan primary key dan mengembalikan true jika ada baris
$status = $repo->hapus($id) ? 'hapus' : 'gagal';
header('Location: index.php?status=' . $status);
exit;
