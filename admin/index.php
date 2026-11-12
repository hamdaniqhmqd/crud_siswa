<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

try {
    $entitas = teksInput($_GET, 'entitas') ?: 'siswa';
    $aksi = teksInput($_GET, 'aksi') ?: 'daftar';
    if (!isset($config[$entitas]) || !in_array($aksi, ['daftar', 'form', 'simpan', 'hapus'], true)) {
        http_response_code(404);
        exit('Halaman tidak ditemukan.');
    }
    // AUTH_ROLE_CHECK: Modul 7 menambahkan pemeriksaan hak akses di sini.
    $c = $config[$entitas];
    $url = 'index.php?entitas=' . urlencode($entitas);
    $error = '';
    $data = [];
    $id = null;
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($aksi, ['simpan', 'hapus'], true)) {
        if ($method !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            exit('Gunakan POST untuk perubahan data.');
        }
        verifyCsrf();
        $idTeks = teksInput($_POST, 'id');
        $id = $idTeks === '' ? null : idPositif($idTeks);
        if (($aksi === 'hapus' && $id === null)
            || ($id !== null && $repo->satu($entitas, $id) === false)
        ) {
            http_response_code(404);
            exit('Data tidak ditemukan.');
        }
        try {
            if ($aksi === 'hapus') {
                $repo->hapus($entitas, $id);
            } else {
                $data = validasiData($entitas, $_POST, $repo);
                $repo->simpan($entitas, $id, $data);
            }
            $_SESSION['pesan_admin'] = 'Perubahan berhasil disimpan.';
            redirectAdmin($url);
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
            http_response_code(422);
        } catch (PDOException $e) {
            $kode = (int) ($e->errorInfo[1] ?? 0);
            $error = $kode === 1062
                ? 'Data duplikat: periksa email, nama, atau kombinasi siswa–mapel–semester.'
                : ($kode === 1451 || $kode === 1452
                    ? 'Relasi data berubah atau tidak valid. Muat ulang dan periksa pilihan.'
                    : 'Penyimpanan gagal. Hubungi pengelola aplikasi.');
            if (!in_array($kode, [1062, 1451, 1452], true)) {
                error_log('CRUD gagal, kode database: ' . $kode);
            }
            http_response_code(in_array($kode, [1062, 1451, 1452], true) ? 409 : 500);
        }
        $aksi = $aksi === 'hapus' ? 'daftar' : 'form';
        foreach ($c['fields'] as $field => $label) {
            $data[$field] = is_string($_POST[$field] ?? null) ? $_POST[$field] : '';
        }
    } elseif ($method !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        exit('Metode tidak tersedia.');
    } elseif ($aksi === 'form') {
        $idTeks = teksInput($_GET, 'id');
        if ($idTeks !== '') {
            $id = idPositif($idTeks);
            $data = $repo->satu($entitas, $id);
            if ($data === false) {
                http_response_code(404);
                exit('Data tidak ditemukan.');
            }
        }
    }
    $pilihan = [];
    foreach (['siswa', 'jurusan', 'mapel'] as $target) {
        $pilihan['id_' . $target] = $repo->pilihan($target);
    }
    $labelRelasi = [];
    foreach ($pilihan as $field => $items) {
        $labelRelasi[$field] = array_column($items, 'nama', 'id');
    }
    $q = teksInput($_GET, 'q');
    $halamanTeks = teksInput($_GET, 'halaman');
    $halaman = $halamanTeks === '' ? 1 : idPositif($halamanTeks);
    $total = $repo->hitung($entitas, $q);
    $maksHalaman = max(1, (int) ceil($total / 10));
    $halaman = min($halaman, $maksHalaman);
    $baris = $repo->daftar($entitas, $q, 10, ($halaman - 1) * 10);
    $pesan = $_SESSION['pesan_admin'] ?? '';
    unset($_SESSION['pesan_admin']);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    exit(eAdmin($e->getMessage()));
} catch (PDOException $e) {
    error_log('Pembacaan panel gagal; SQLSTATE: ' . $e->getCode());
    http_response_code(500);
    exit('Data tidak dapat dimuat. Periksa layanan database.');
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola <?= eAdmin($c['label']) ?></title>
    <style>
        body {
            font: 16px system-ui;
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        nav a {
            margin-right: 1rem;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            border: 1px solid #bbb;
            padding: .5rem;
            text-align: left;
        }

        label {
            display: block;
            margin: .8rem 0;
        }

        input,
        select,
        button {
            font: inherit;
            padding: .35rem;
        }

        .error {
            color: #a00000;
        }

        .pesan {
            color: #075c20;
        }

        .scroll {
            overflow-x: auto;
        }

        .inline {
            display: inline;
        }
    </style>
</head>

<body>
    <nav aria-label="Data sekolah">
        <?php foreach ($config as $key => $item): ?>
            <a href="index.php?entitas=<?= eAdmin($key) ?>"><?= eAdmin($item['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <h1>Kelola <?= eAdmin($c['label']) ?></h1>
    <?php if ($pesan !== ''): ?><p class="pesan" role="status"><?= eAdmin($pesan) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="error" role="alert"><?= eAdmin($error) ?></p><?php endif; ?>
    <?php if ($aksi === 'form'): ?>
        <h2><?= $id === null ? 'Tambah' : 'Edit' ?> data</h2>
        <form method="post" action="<?= eAdmin($url . '&aksi=simpan') ?>">
            <input type="hidden" name="csrf_token" value="<?= eAdmin(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= eAdmin($id ?? '') ?>">
            <?php foreach ($c['fields'] as $field => $label): ?>
                <label><?= eAdmin($label) ?>
                    <?php if (isset($pilihan[$field])): ?>
                        <select name="<?= eAdmin($field) ?>" <?= $field === 'id_jurusan' ? '' : 'required' ?>>
                            <option value="">— Pilih —</option>
                            <?php foreach ($pilihan[$field] as $option): ?>
                                <option value="<?= eAdmin($option['id']) ?>"
                                    <?= (string) ($data[$field] ?? '') === (string) $option['id'] ? 'selected' : '' ?>>
                                    <?= eAdmin($option['nama'] . ' (#' . $option['id'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field === 'jenis_kelamin'): ?>
                        <select name="jenis_kelamin" required>
                            <option value="">— Pilih —</option>
                            <?php foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $key => $labelJk): ?>
                                <option value="<?= eAdmin($key) ?>" <?= ($data[$field] ?? '') === $key ? 'selected' : '' ?>>
                                    <?= eAdmin($labelJk) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else:
                        $type = $field === 'email' ? 'email' : ($field === 'tanggal_lahir' ? 'date'
                            : (in_array($field, ['nilai_angka', 'semester'], true) ? 'number' : 'text'));
                    ?>
                        <input name="<?= eAdmin($field) ?>" type="<?= eAdmin($type) ?>" value="<?= eAdmin($data[$field] ?? '') ?>"
                            <?= in_array($field, ['email', 'tanggal_lahir'], true) ? '' : 'required' ?>
                            <?= $field === 'nilai_angka' ? 'min="0" max="100" step="0.01"' : '' ?>
                            <?= $field === 'semester' ? 'min="1" max="6" step="1"' : '' ?>
                            <?= $type === 'text' ? 'maxlength="100"' : '' ?> <?= $field === 'email' ? 'maxlength="150"' : '' ?>>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
            <button type="submit">Simpan</button>
            <a href="<?= eAdmin($url) ?>">Batal</a>
        </form>
    <?php else: ?>
        <p><a href="<?= eAdmin($url . '&aksi=form') ?>">Tambah <?= eAdmin($c['label']) ?></a></p>
        <form method="get">
            <input type="hidden" name="entitas" value="<?= eAdmin($entitas) ?>">
            <label>Cari <?= $entitas === 'nilai' ? 'semester' : 'nama' ?>
                <input name="q" value="<?= eAdmin($q) ?>">
            </label>
            <button type="submit">Cari</button>
        </form>
        <p><?= eAdmin($total) ?> data. Halaman <?= eAdmin($halaman) ?> dari <?= eAdmin($maksHalaman) ?>.</p>
        <p>Perhatian: hapus siswa/mapel juga menghapus nilai terkait; hapus jurusan mengosongkan jurusan siswa.</p>
        <div class="scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php foreach ($c['fields'] as $label): ?><th><?= eAdmin($label) ?></th><?php endforeach; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($baris as $row): ?>
                        <tr>
                            <td><?= eAdmin($row[$c['pk']]) ?></td>
                            <?php foreach ($c['fields'] as $field => $label): ?>
                                <td><?= eAdmin(isset($labelRelasi[$field])
                                        ? ($labelRelasi[$field][$row[$field] ?? ''] ?? '—') : ($row[$field] ?? '—')) ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <a href="<?= eAdmin($url . '&aksi=form&id=' . $row[$c['pk']]) ?>">Edit</a>
                                <form class="inline" method="post" action="<?= eAdmin($url . '&aksi=hapus') ?>"
                                    onsubmit="return confirm('Hapus data beserta dampak relasinya?')">
                                    <input type="hidden" name="csrf_token" value="<?= eAdmin(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= eAdmin($row[$c['pk']]) ?>">
                                    <button type="submit">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($baris === []): ?><tr>
                            <td colspan="<?= count($c['fields']) + 2 ?>">Data tidak ditemukan.</td>
                        </tr><?php endif;
                                ?>
                </tbody>
            </table>
        </div>
        <nav aria-label="Halaman daftar">
            <?php if ($halaman > 1): ?>
                <a href="<?= eAdmin($url . '&' . http_build_query(['q' => $q, 'halaman' => $halaman - 1])) ?>">Sebelumnya</a>
            <?php endif; ?>
            <?php if ($halaman < $maksHalaman): ?>
                <a href="<?= eAdmin($url . '&' . http_build_query(['q' => $q, 'halaman' => $halaman + 1])) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</body>

</html>