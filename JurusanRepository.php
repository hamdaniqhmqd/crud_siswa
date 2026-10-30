<?php
require_once __DIR__ . '/Repository.php';

class JurusanRepository extends Repository
{
    public function cari(string $kata): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id_jurusan, nama_jurusan
                FROM jurusan
                WHERE nama_jurusan LIKE :kata
                ORDER BY nama_jurusan'
        );
        $stmt->execute(['kata' => '%' . $kata . '%']);
        return $stmt->fetchAll();
    }
}
