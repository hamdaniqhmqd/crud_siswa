<?php

declare(strict_types=1);
final class DataRepository
{
    private PDO $pdo;
    private array $config;
    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }
    private function aturan(string $entitas): array
    {
        if (!isset($this->config[$entitas])) {
            throw new InvalidArgumentException('Entitas tidak tersedia.');
        }
        return $this->config[$entitas];
    }

    public function hitung(string $entitas, string $q): int
    {
        $c = $this->aturan($entitas);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM `$entitas` WHERE `{$c['cari']}` LIKE :q"
        );
        $stmt->execute(['q' => '%' . $q . '%']);
        return (int) $stmt->fetchColumn();
    }

    public function daftar(string $entitas, string $q, int $limit, int $offset): array
    {
        $c = $this->aturan($entitas);
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `$entitas` WHERE `{$c['cari']}` LIKE :q
ORDER BY `{$c['pk']}` DESC LIMIT :batas OFFSET :offset"
        );
        $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
        $stmt->bindValue(':batas', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function satu(string $entitas, int $id): array|false
    {
        $c = $this->aturan($entitas);
        $stmt = $this->pdo->prepare("SELECT * FROM `$entitas` WHERE `{$c['pk']}` = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    public function simpan(string $entitas, ?int $id, array $data): void
    {
        $c = $this->aturan($entitas);
        $kolom = array_keys($c['fields']);
        $parameter = [];
        foreach ($kolom as $nama) {
            $parameter[$nama] = $data[$nama];
        }
        if ($id === null) {
            $namaKolom = implode(', ', array_map(fn($k) => "`$k`", $kolom));
            $placeholder = implode(', ', array_map(fn($k) => ':' . $k, $kolom));
            $sql = "INSERT INTO `$entitas` ($namaKolom) VALUES ($placeholder)";
        } else {
            $set = implode(', ', array_map(fn($k) => "`$k` = :$k", $kolom));
            $sql = "UPDATE `$entitas` SET $set WHERE `{$c['pk']}` = :id";
            $parameter['id'] = $id;
        }
        $this->pdo->prepare($sql)->execute($parameter);
    }
    public function hapus(string $entitas, int $id): void
    {
        $c = $this->aturan($entitas);
        $stmt = $this->pdo->prepare("DELETE FROM `$entitas` WHERE `{$c['pk']}` = :id");
        $stmt->execute(['id' => $id]);
    }
    public function pilihan(string $entitas): array
    {
        $c = $this->aturan($entitas);
        if (!in_array($entitas, ['siswa', 'jurusan', 'mapel'], true)) {
            throw new InvalidArgumentException('Pilihan tidak tersedia.');
        }
        $stmt = $this->pdo->query(
            "SELECT 
            `{$c['pk']}` AS id, 
            `{$c['cari']}` AS nama
FROM `$entitas` ORDER BY `{$c['cari']}`, `{$c['pk']}`"
        );
        return $stmt->fetchAll();
    }
}
