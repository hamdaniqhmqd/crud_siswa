<?php
require __DIR__ . '/koneksi.php';
require __DIR__ . '/helper.php';
require __DIR__ . '/SiswaRepository.php';
$repo = new SiswaRepository($pdo);
// Nilai awal form kosong
$data = ['nama' => '', 'email' => '', 'jenis_kelamin' => '', 'tanggal_lahir' => '', 'id_jurusan' => ''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // proses hanya saat form dikirim
    $data = bersihkanInputSiswa($_POST); // rapikan input
    $errors = validasiSiswa($data); // periksa aturan
    if (!$errors) { // lanjut jika tidak ada kesalahan
        try {
            $repo->tambah($data); // repository membungkus INSERT dan parameter query
            header('Location: index.php?status=tambah'); // arahkan ke daftar setelah sukses
            exit; // hentikan skrip setelah redirect
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // 23000 = pelanggaran constraint (UNIQUE/FK)
                $errors[] = 'Email sudah dipakai atau jurusan tidak ditemukan.';
            } else {
                $errors[] = 'Terjadi kesalahan saat menyimpan data.';
            }
        }
    }
}
$daftarJurusan = ambilJurusan($pdo);
$labelTombol = 'Simpan';
$judul = 'Tambah Siswa';
require __DIR__ . '/header.php';
require __DIR__ . '/form_siswa.php';
require __DIR__ . '/footer.php';