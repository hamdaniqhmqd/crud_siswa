<?php
// Mengubah karakter khusus agar teks dapat ditampilkan sebagai isi HTML
function e(string $teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8'); // ubah karakter khusus menjadi bentuk teks HTML
}
// Mengambil daftar jurusan untuk pilihan dropdown
function ambilJurusan(PDO $pdo): array
{
    $sql = "SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan"; // DQL: urut A-Z
    return $pdo->query($sql)->fetchAll(); // ambil semua baris
}
// Merapikan data dari form
function bersihkanInputSiswa(array $post): array
{
    $teks = static function (string $kunci) use ($post): string {
        $nilai = $post[$kunci] ?? '';
        return is_string($nilai) || is_numeric($nilai) ? trim((string) $nilai) : '';
    };
    return [
        'nama' => $teks('nama'), // trim membuang spasi di awal/akhir
        'email' => $teks('email'),
        'jenis_kelamin' => $teks('jenis_kelamin'),
        'tanggal_lahir' => $teks('tanggal_lahir'),
        'id_jurusan' => $teks('id_jurusan'),
    ];
}
// Memeriksa data; hasilnya daftar pesan kesalahan (kosong = valid)
function validasiSiswa(array $d): array
{
    $errors = [];
    if ($d['nama'] === '') {
        $errors[] = 'Nama wajib diisi.';
    } elseif (mb_strlen($d['nama']) > 100) {
        $errors[] = 'Nama maksimal 100 karakter.'; // sesuai VARCHAR(100)
    }
    if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (mb_strlen($d['email']) > 150) {
        $errors[] = 'Email maksimal 150 karakter.';
    }
    if (!in_array($d['jenis_kelamin'], ['L', 'P'], true)) { // cocokkan dengan ENUM('L','P')
        $errors[] = 'Jenis kelamin harus L atau P.';
    }
    if ($d['tanggal_lahir'] !== '') {
        $tgl = DateTime::createFromFormat('!Y-m-d', $d['tanggal_lahir']);
        if (!$tgl || $tgl->format('Y-m-d') !== $d['tanggal_lahir']) {
            $errors[] = 'Tanggal lahir tidak valid (format YYYY-MM-DD).';
        }
    }
    if ($d['id_jurusan'] !== '') {
        $idJurusan = filter_var($d['id_jurusan'], FILTER_VALIDATE_INT);
        if ($idJurusan === false || $idJurusan < 1) {
            $errors[] = 'Jurusan tidak valid.';
        }
    }
    return $errors;
}