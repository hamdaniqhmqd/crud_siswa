<?php
require __DIR__ . '/koneksi.php';
require __DIR__ . '/helper.php';
require __DIR__ . '/SiswaRepository.php';
require __DIR__ . '/NilaiRepository.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID siswa tidak valid.');
}
$siswaRepo = new SiswaRepository($pdo);
$siswa = $siswaRepo->berdasarkanId($id);
if (!$siswa) {
    http_response_code(404);
    exit('Data siswa tidak ditemukan.');
}
$ambilTeks = static function (string $kunci): string {
    $nilai = $_GET[$kunci] ?? '';
    return is_string($nilai) ? trim($nilai) : '';
};
$ambilNilai = static function (string $teks, string $label, array &$errors): ?float {
    if ($teks === '') {
        return null;
    }
    $angka = filter_var($teks, FILTER_VALIDATE_FLOAT);
    if ($angka === false || $angka < 0 || $angka > 100) {
        $errors[] = "$label harus berupa angka dari 0 sampai 100.";
        return null;
    }
    return (float) $angka;
};
$errors = [];
$dariInput = $ambilTeks('nilai_dari');
$sampaiInput = $ambilTeks('nilai_sampai');
$nilaiDari = $ambilNilai($dariInput, 'Nilai minimum', $errors);
$nilaiSampai = $ambilNilai($sampaiInput, 'Nilai maksimum', $errors);
if ($nilaiDari !== null && $nilaiSampai !== null && $nilaiDari > $nilaiSampai) {
    $errors[] = 'Nilai minimum tidak boleh lebih besar daripada nilai maksimum.';
    $nilaiDari = null;
    $nilaiSampai = null;
}
if ($errors) {
    $nilaiDari = null;
    $nilaiSampai = null;
}
$urutInput = $ambilTeks('urut');
$urut = in_array($urutInput, ['nilai', 'mapel', 'semester'], true) ? $urutInput : 'semester';
$arahInput = strtoupper($ambilTeks('arah'));
$arah = in_array($arahInput, ['ASC', 'DESC'], true) ? $arahInput : 'ASC';
$nilaiRepo = new NilaiRepository($pdo);
$rapor = $nilaiRepo->rapor($id, $nilaiDari, $nilaiSampai, $urut, $arah);
$judul = 'Rapor: ' . $siswa['nama'];
require __DIR__ . '/header.php';
?>
<p><a class="btn btn-abu" href="index.php">Kembali ke daftar siswa</a></p>
<?php foreach ($errors as $error): ?>
<div class="pesan gagal"><?= e($error) ?></div>
<?php endforeach; ?>

<form class="filters" method="get" action="detail.php">
    <input type="hidden" name="id_siswa" value="<?= (int) $id ?>">
    <div>
        <label for="nilai_dari">Nilai dari (0-100)</label>
        <input type="number" id="nilai_dari" name="nilai_dari" min="0" max="100" step="0.01"
            value="<?= e($dariInput) ?>">
    </div>
    <div>
        <label for="nilai_sampai">Nilai sampai (0-100)</label>
        <input type="number" id="nilai_sampai" name="nilai_sampai" min="0" max="100" step="0.01"
            value="<?= e($sampaiInput) ?>">
    </div>
    <div>
        <label for="urut">Urutkan berdasarkan</label>
        <select id="urut" name="urut">
            <option value="semester" <?= $urut === 'semester' ? 'selected' : '' ?>>Semester</option>
            <option value="mapel" <?= $urut === 'mapel' ? 'selected' : '' ?>>Mata pelajaran</option>
            <option value="nilai" <?= $urut === 'nilai' ? 'selected' : '' ?>>Nilai</option>
        </select>
    </div>
    <div>
        <label for="arah">Arah pengurutan</label>
        <select id="arah" name="arah">
            <option value="ASC" <?= $arah === 'ASC' ? 'selected' : '' ?>>Menaik</option>
            <option value="DESC" <?= $arah === 'DESC' ? 'selected' : '' ?>>Menurun</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn">Terapkan</button>
        <a class="btn btn-abu" href="detail.php?id=<?= (int) $id ?>">Atur ulang</a>
    </div>
</form>
<div class="tabel-wrap">
    <table class="tabel" id="tabel-rapor">
        <thead>
            <tr>
                <!-- <th>Id</th> -->
                <th>Semester</th>
                <th>Mata Pelajaran</th>
                <th>Nilai</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rapor): ?>
            <tr>
                <td colspan="3">Tidak ada nilai yang cocok dengan rentang ini.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($rapor as $baris): ?>
            <tr>
                <!-- <td data-label=""><?= (int) $baris['id_nilai'] ?></td> -->
                <td data-label="Semester"><?= (int) $baris['semester'] ?></td>
                <td data-label="Mata Pelajaran"><?= e($baris['nama_mapel']) ?></td>
                <td data-label="Nilai"><?= e($baris['nilai_angka']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/footer.php'; ?>