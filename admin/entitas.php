<?php

declare(strict_types=1);
return [
    'siswa' => [
        'pk' => 'id_siswa',
        'label' => 'Siswa',
        'cari' => 'nama',
        'fields' => [
            'nama' => 'Nama',
            'email' => 'Email',
            'jenis_kelamin' => 'Jenis ',
            'tanggal_lahir' => 'Tanggal lahir',
            'id_jurusan' => 'Jurusan',
        ],
    ],
    'jurusan' => [
        'pk' => 'id_jurusan',
        'label' => 'Jurusan',
        'cari' => 'nama_jurusan',
        'fields' => ['nama_jurusan' => 'Nama jurusan'],
    ],
    'mapel' => [
        'pk' => 'id_mapel',
        'label' => 'Mata pelajaran',
        'cari' => 'nama_mapel',
        'fields' => ['nama_mapel' => 'Nama mata pelajaran'],
    ],
    'nilai' => [
        'pk' => 'id_nilai',
        'label' => 'Nilai',
        'cari' => 'semester',
        'fields' => [
            'id_siswa' => 'Siswa',
            'id_mapel' => 'Mata pelajaran',
            'nilai_angka' => 'Nilai angka',
            'semester' => 'Semester',
        ],
    ],
];
