<?php
require __DIR__ . '/koneksi.php';
require __DIR__ . '/helper.php';
require __DIR__ . '/SiswaRepository.php';

$repo = new SiswaRepository($pdo); // repository memusatkan query yang berhubungan dengan siswa

// Baca parameter sebagai teks agar q[]=x tidak menyebabkan TypeError
$teksGet = static function (string $kunci): string {
    $nilai = $_GET[$kunci] ?? '';
    return is_string($nilai) ? trim($nilai) : '';
};
$kata = mb_substr($teksGet('q'), 0, 100);
$jkInput = $teksGet('jk');
$jk = in_array($jkInput, ['L', 'P'], true) ? $jkInput : '';
$jurusanInput = $teksGet('jurusan');
$jurusanId = filter_var($jurusanInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$jurusanId = $jurusanId === false ? null : $jurusanId;
$validasiTanggal = static function (string $tanggal): string {
    $hasil = DateTime::createFromFormat('!Y-m-d', $tanggal);
    return $hasil && $hasil->format('Y-m-d') === $tanggal ? $tanggal : '';
};
$tanggalDariInput = $teksGet('tanggal_dari');
$tanggalSampaiInput = $teksGet('tanggal_sampai');
$tanggalDari = $validasiTanggal($tanggalDariInput);
$tanggalSampai = $validasiTanggal($tanggalSampaiInput);
$errorsFilter = [];
if ($jurusanInput !== '' && $jurusanId === null) {
    $errorsFilter[] = 'Pilihan jurusan tidak valid.';
}
if ($tanggalDariInput !== '' && $tanggalDari === '') {
    $errorsFilter[] = 'Tanggal awal tidak valid.';
}
if ($tanggalSampaiInput !== '' && $tanggalSampai === '') {
    $errorsFilter[] = 'Tanggal akhir tidak valid.';
}
if ($tanggalDari !== '' && $tanggalSampai !== '' && $tanggalDari > $tanggalSampai) {
    $errorsFilter[] = 'Tanggal awal tidak boleh melewati tanggal akhir.';
    $tanggalDari = '';
    $tanggalSampai = '';
}
// Pilihan pengurutan dibatasi lagi di SiswaRepository sebelum disisipkan ke SQL
$kolomUrut = ['id_siswa', 'nama', 'email', 'tanggal_lahir', 'jurusan'];
$urutInput = $teksGet('urut');
$urut = in_array($urutInput, $kolomUrut, true) ? $urutInput : 'id_siswa';
$arahInput = strtoupper($teksGet('arah'));
$arah = in_array($arahInput, ['ASC', 'DESC'], true) ? $arahInput : 'ASC';
$halamanInput = filter_var($teksGet('halaman'), FILTER_VALIDATE_INT);
$halaman = $halamanInput !== false && $halamanInput > 0 ? $halamanInput : 1;
$perHalaman = 5;
$filter = [
    'q' => $kata,
    'jenis_kelamin' => $jk,
    'id_jurusan' => $jurusanId,
    'tanggal_dari' => $tanggalDari,
    'tanggal_sampai' => $tanggalSampai,
    'urut' => $urut,
    'arah' => $arah,
    'halaman' => $halaman,
    'per_halaman' => $perHalaman,
];
$jumlahSiswa = $repo->hitung($filter);
$totalHalaman = max(1, (int) ceil($jumlahSiswa / $perHalaman));
$halaman = min($halaman, $totalHalaman);
$filter['halaman'] = $halaman;
$daftarSiswa = $repo->daftar($filter);
$daftarJurusan = ambilJurusan($pdo);
// Pertahankan filter ketika berpindah halaman
$parameterUrl = [
    'q' => $kata,
    'jk' => $jk,
    'jurusan' => $jurusanId,
    'tanggal_dari' => $tanggalDari,
    'tanggal_sampai' => $tanggalSampai,
    'urut' => $urut,
    'arah' => $arah,
];
$parameterUrl = array_filter($parameterUrl, static fn($nilai) => $nilai !== '' && $nilai !== null);
$urlHalaman = static function (int $nomor) use ($parameterUrl): string {
    return 'index.php?' . http_build_query($parameterUrl + ['halaman' => $nomor]);
};
// Pesan hasil operasi dikirim lewat URL (?status=...); hanya kunci yang dikenal yang ditampilkan
$pesanStatus = [
    'tambah' => 'Data siswa berhasil ditambahkan.',
    'ubah' => 'Data siswa berhasil diubah.',
    'hapus' => 'Data siswa berhasil dihapus.',
    'gagal' => 'Operasi gagal. Data tidak ditemukan atau tidak valid.',
];
$statusInput = $_GET['status'] ?? '';
$status = is_string($statusInput) ? $statusInput : '';
$judul = 'Daftar Siswa';
require __DIR__ . '/header.php';
?>

<?php if (isset($pesanStatus[$status])): ?>
<div class="pesan <?= $status === 'gagal' ? 'gagal' : 'sukses' ?>">
    <?= e($pesanStatus[$status]) ?>
</div>
<?php endif; ?>
<?php foreach ($errorsFilter as $error): ?>
<div class="pesan gagal"><?= e($error) ?></div>
<?php endforeach; ?>
<div class="toolbar">
    <a class="btn" href="tambah.php">+ Tambah Siswa</a>
    <p class="toolbar__info">
        Menampilkan <?= count($daftarSiswa) ?> dari <?= $jumlahSiswa ?> siswa yang cocok.
    </p>
</div>
<form class="filters" method="get" action="index.php">
    <div class="cari-wrap">
        <label for="q">Cari nama atau email</label>
        <input type="text" id="q" name="q" maxlength="100" value="<?= e($kata) ?>" autocomplete="off"
            aria-controls="saran-siswa">
        <ul class="saran" id="saran-siswa" role="listbox"></ul>
    </div>
    <div>
        <label for="jk">Jenis kelamin</label>
        <select id="jk" name="jk">
            <option value="">Semua</option>
            <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select>
    </div>
    <div>
        <label for="jurusan">Jurusan</label>
        <select id="jurusan" name="jurusan">
            <option value="">Semua jurusan</option>
            <?php foreach ($daftarJurusan as $j): ?>
            <option value="<?= (int) $j['id_jurusan'] ?>"
                <?= $jurusanId === (int) $j['id_jurusan'] ? 'selected' : '' ?>>
                <?= e($j['nama_jurusan']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="tanggal_dari">Tanggal lahir dari</label>
        <input type="date" id="tanggal_dari" name="tanggal_dari" value="<?= e($tanggalDariInput) ?>">
    </div>
    <div>
        <label for="tanggal_sampai">Tanggal lahir sampai</label>
        <input type="date" id="tanggal_sampai" name="tanggal_sampai" value="<?= e($tanggalSampaiInput) ?>">
    </div>
    <div>
        <label for="urut">Urutkan berdasarkan</label>
        <select id="urut" name="urut">
            <option value="id_siswa" <?= $urut === 'id_siswa' ? 'selected' : '' ?>>ID</option>
            <option value="nama" <?= $urut === 'nama' ? 'selected' : '' ?>>Nama</option>
            <option value="email" <?= $urut === 'email' ? 'selected' : '' ?>>Email</option>
            <option value="tanggal_lahir" <?= $urut === 'tanggal_lahir' ? 'selected' : '' ?>>Tanggal lahir</option>
            <option value="jurusan" <?= $urut === 'jurusan' ? 'selected' : '' ?>>Jurusan</option>
        </select>
    </div>
    <div>
        <label for="arah">Arah pengurutan</label>
        <select id="arah" name="arah">
            <option value="ASC" <?= $arah === 'ASC' ? 'selected' : '' ?>>Menaik (ASC)</option>
            <option value="DESC" <?= $arah === 'DESC' ? 'selected' : '' ?>>Menurun (DESC)</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn">Cari / Terapkan filter</button>
        <a class="btn btn-abu" href="index.php">Atur ulang</a>
    </div>
</form>

<div class="tabel-wrap">
    <table class="tabel" id="tabel-siswa">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>JK</th>
                <th>Tanggal Lahir</th>
                <th>Jurusan</th>
                <th>Rapor / Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($jumlahSiswa === 0): ?>
            <tr>
                <td colspan="7" class="tabel__kosong">Belum ada siswa yang cocok dengan pencarian atau
                    filter.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($daftarSiswa as $no => $row): ?>
            <tr>
                <td data-label="No"><?= ($halaman - 1) * $perHalaman + $no + 1 ?></td>
                <td data-label="Nama"><?= e($row['nama']) ?></td>
                <td data-label="Email"><?= e($row['email'] ?? '-') ?></td>
                <td data-label="JK">
                    <?php if ($row['jenis_kelamin'] === 'L'): ?>
                    <span class="badge badge--l">Laki-laki</span>
                    <?php else: ?>
                    <span class="badge badge--p">Perempuan</span>
                    <?php endif; ?>
                </td>
                <td data-label="Tanggal Lahir"><?= e($row['tanggal_lahir'] ?? '-') ?></td>
                <td data-label="Jurusan"><?= e($row['nama_jurusan'] ?? 'Belum ada jurusan') ?></td>
                <td data-label="Aksi" class="aksi">
                    <a class="btn" href="detail.php?id=<?= (int) $row['id_siswa'] ?>">Rapor</a>
                    <a class="btn btn-ghost" href="edit.php?id=<?= (int) $row['id_siswa'] ?>">Edit</a>
                    <form class="inline form-hapus" action="hapus.php" method="post" data-nama="<?= e($row['nama']) ?>">
                        <input type="hidden" name="id_siswa" value="<?= (int) $row['id_siswa'] ?>">
                        <button type="submit" class="btn btn-hapus">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalHalaman > 1): ?>
<nav class="pagination" aria-label="Navigasi halaman">
    <a class="btn btn-abu <?= $halaman <= 1 ? 'nonaktif' : '' ?>"
        href="<?= e($urlHalaman(max(1, $halaman - 1))) ?>">Sebelumnya</a>
    <span>Halaman <?= $halaman ?> dari <?= $totalHalaman ?></span>
    <a class="btn btn-abu <?= $halaman >= $totalHalaman ? 'nonaktif' : '' ?>"
        href="<?= e($urlHalaman(min($totalHalaman, $halaman + 1))) ?>">Berikutnya</a>
</nav>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>