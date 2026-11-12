<?php

declare(strict_types=1);

function validasiData(string $entitas, array $input, DataRepository $repo): array
{
    $namaValid = static function (string $nilai, string $label): string {
        $panjang = preg_match_all('/./us', $nilai);
        if ($nilai === '' || $panjang === false || $panjang > 100) {
            throw new InvalidArgumentException($label . ' wajib diisi, maksimal 100 karakter.');
        }
        return $nilai;
    };
    $relasi = static function (string $target, string $nilai) use ($repo): int {
        $id = idPositif($nilai);
        if ($repo->satu($target, $id) === false) {
            throw new InvalidArgumentException('Data ' . $target . ' tidak ditemukan.');
        }
        return $id;
    };
    if ($entitas === 'jurusan' || $entitas === 'mapel') {
        $kolom = $entitas === 'jurusan' ? 'nama_jurusan' : 'nama_mapel';
        return [$kolom => $namaValid(teksInput($input, $kolom), 'Nama')];
    }
    if ($entitas === 'siswa') {
        $nama = $namaValid(teksInput($input, 'nama'), 'Nama siswa');
        $email = teksInput($input, 'email');
        if ($email !== '' && (strlen($email) > 150
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            throw new InvalidArgumentException('Email tidak valid atau terlalu panjang.');
        }
        $jk = teksInput($input, 'jenis_kelamin');
        if (!in_array($jk, ['L', 'P'], true)) {
            throw new InvalidArgumentException('Jenis kelamin harus L atau P.');
        }
        $tanggal = teksInput($input, 'tanggal_lahir');
        if ($tanggal !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
            if (
                !$date || $date->format('Y-m-d') !== $tanggal
                || $tanggal < '1000-01-01' || $tanggal > date('Y-m-d')
            ) {
                throw new InvalidArgumentException('Tanggal lahir tidak valid atau di masa depan.');
            }
        }
        $jurusan = teksInput($input, 'id_jurusan');
        return [
            'nama' => $nama,
            'email' => $email === '' ? null : $email,
            'jenis_kelamin' => $jk,
            'tanggal_lahir' => $tanggal === '' ? null : $tanggal,
            'id_jurusan' => $jurusan === '' ? null : $relasi('jurusan', $jurusan),
        ];
    }
    if ($entitas === 'nilai') {
        $angka = teksInput($input, 'nilai_angka');
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/D', $angka) || (float) $angka > 100) {
            throw new InvalidArgumentException('Nilai harus 0–100, maksimal dua desimal dengan titik.');
        }
        $semester = filter_var(
            teksInput($input, 'semester'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 6]]
        );
        if ($semester === false) {
            throw new InvalidArgumentException('Semester harus bilangan bulat 1–6.');
        }
        return [
            'id_siswa' => $relasi('siswa', teksInput($input, 'id_siswa')),
            'id_mapel' => $relasi('mapel', teksInput($input, 'id_mapel')),
            'nilai_angka' => $angka,
            'semester' => $semester,
        ];
    }
    throw new InvalidArgumentException('Entitas tidak valid.');
}
