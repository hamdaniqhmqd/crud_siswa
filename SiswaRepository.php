<?php
require_once __DIR__ . '/Repository.php';

class SiswaRepository extends Repository
{
    public function cari(string $kata): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT id_siswa, nama, email
FROM siswa
WHERE nama LIKE :nama OR email LIKE :email
ORDER BY nama, id_siswa"
        );
        $pola = '%' . $kata . '%';
        $stmt->execute(['nama' => $pola, 'email' => $pola]);
        return $stmt->fetchAll();
    }

    private function kondisi(array $filter): array
    {
        $where = [];
        $params = [];
        if ($filter['q'] !== '') {
            $where[] = '(s.nama LIKE :nama OR s.email LIKE :email)';
            $pola = '%' . $filter['q'] . '%';
            $params['nama'] = $pola;
            $params['email'] = $pola;
        }
        if (in_array($filter['jenis_kelamin'], ['L', 'P'], true)) {
            $where[] = 's.jenis_kelamin = :jk';
            $params['jk'] = $filter['jenis_kelamin'];
        }
        if ($filter['id_jurusan'] !== null) {
            $where[] = 's.id_jurusan = :jurusan';
            $params['jurusan'] = $filter['id_jurusan'];
        }
        foreach (['tanggal_dari' => '>=', 'tanggal_sampai' => '<='] as $key => $operator) {
            if ($filter[$key] !== '') {
                $where[] = "s.tanggal_lahir $operator :$key";
                $params[$key] = $filter[$key];
            }
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        return [$sqlWhere, $params];
    }
    private function urutan(array $filter): string
    {
        $kolomUrut = [
            'id_siswa' => 's.id_siswa',
            'nama' => 's.nama',
            'email' => 's.email',
            'tanggal_lahir' => 's.tanggal_lahir',
            'jurusan' => 'j.nama_jurusan',
        ];
        $kolom = $kolomUrut[$filter['urut']] ?? 's.id_siswa';
        $arah = $filter['arah'] === 'DESC' ? 'DESC' : 'ASC';
        return "$kolom $arah, s.id_siswa ASC";
    }
    public function hitung(array $filter): int
    {
        [$where, $params] = $this->kondisi($filter);
        $stmt = $this->pdo()->prepare("SELECT COUNT(*) FROM siswa AS s$where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
    public function daftar(array $filter): array
    {
        [$where, $params] = $this->kondisi($filter);
        $sql = "SELECT s.id_siswa, s.nama, s.email, s.jenis_kelamin,
s.tanggal_lahir, j.nama_jurusan
FROM siswa AS s
LEFT JOIN jurusan AS j ON s.id_jurusan = j.id_jurusan
$where
ORDER BY " . $this->urutan($filter) . "
LIMIT :batas OFFSET :offset";
        $stmt = $this->pdo()->prepare($sql);
        foreach ($params as $nama => $nilai) {
            $tipe = is_int($nilai) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $nama, $nilai, $tipe);
        }
        $stmt->bindValue(':batas', $filter['per_halaman'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($filter['halaman'] - 1) * $filter['per_halaman'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function berdasarkanId(int $id): array|false
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id_siswa, nama, email, jenis_kelamin, tanggal_lahir, id_jurusan
FROM siswa WHERE id_siswa = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function tambah(array $data): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO siswa (nama, email, jenis_kelamin, tanggal_lahir, id_jurusan)
VALUES (:nama, :email, :jk, :tgl, :jrsn)'
        );
        $stmt->execute($this->parameterSiswa($data));
    }
    public function ubah(int $id, array $data): void
    {
        $params = $this->parameterSiswa($data);
        $params['id'] = $id;
        $stmt = $this->pdo()->prepare(
            'UPDATE siswa
SET nama = :nama, email = :email, jenis_kelamin = :jk,
tanggal_lahir = :tgl, id_jurusan = :jrsn
WHERE id_siswa = :id'
        );
        $stmt->execute($params);
    }
    private function parameterSiswa(array $data): array
    {
        return [
            'nama' => $data['nama'],
            'email' => $data['email'] === '' ? null : $data['email'],
            'jk' => $data['jenis_kelamin'],
            'tgl' => $data['tanggal_lahir'] === '' ? null : $data['tanggal_lahir'],
            'jrsn' => $data['id_jurusan'] === '' ? null : (int) $data['id_jurusan'],
        ];
    }
    public function hapus(int $id): bool // true dan false, 1 atau 0
    {
        $stmt = $this->pdo()->prepare('DELETE FROM siswa WHERE id_siswa = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
