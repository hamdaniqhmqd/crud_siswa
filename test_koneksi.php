<?php
require __DIR__ . '/koneksi.php'; // jika gagal, pesan error muncul dari koneksi.php

$jumlah = $pdo->query('SELECT COUNT(*) FROM siswa')->fetchColumn(); // hitung baris siswa di tabel

echo "Koneksi berhasil. Jumlah siswa: " . $jumlah;