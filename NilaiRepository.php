<?php
require_once __DIR__ . '/Repository.php';
class NilaiRepository extends Repository
{
    public function cari(string $kata): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT n.id_nilai, s.nama, m.nama_mapel, n.nilai_angka, n.semester
FROM nilai AS n
JOIN siswa AS s ON s.id_siswa = n.id_siswa
JOIN mapel AS m ON m.id_mapel = n.id_mapel
WHERE m.nama_mapel LIKE :kata
ORDER BY m.nama_mapel, s.nama
LIMIT 20'
        );
        $stmt->execute(['kata' => '%' . $kata . '%']);
        return $stmt->fetchAll();
    }
    public function rapor(int $idSiswa, ?float $nilaiDari, ?float $nilaiSampai, string $urut, string $arah): array
    {
        $where = ['n.id_siswa = :id_siswa'];
        $params = ['id_siswa' => $idSiswa];
        if ($nilaiDari !== null) {
            $where[] = 'n.nilai_angka >= :nilai_dari';
            $params['nilai_dari'] = $nilaiDari;
        }
        if ($nilaiSampai !== null) {
            $where[] = 'n.nilai_angka <= :nilai_sampai';
            $params['nilai_sampai'] = $nilaiSampai;
        }
        $kolomUrut = [
            'nilai' => 'n.nilai_angka',
            'mapel' => 'm.nama_mapel',
            'semester' => 'n.semester',
        ];
        $kolom = $kolomUrut[$urut] ?? 'n.semester';
        $arahUrut = $arah === 'DESC' ? 'DESC' : 'ASC';
        $sql = 'SELECT n.id_nilai, n.nilai_angka, n.semester, m.nama_mapel
                FROM nilai AS n
                JOIN mapel AS m ON m.id_mapel = n.id_mapel
                WHERE ' . implode(' AND ', $where) . "
                ORDER BY $kolom $arahUrut, n.id_nilai ASC";

        $stmt = $this->pdo()->prepare($sql);
        foreach ($params as $nama => $nilai) {
            $tipe = is_int($nilai) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $nama, $nilai, $tipe);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
