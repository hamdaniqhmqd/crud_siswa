<?php
abstract class Repository
{
    private PDO $pdo; // enkapsulasi: detail koneksi tidak bisa dibaca langsung dari luar
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo; // simpan koneksi yang diterima dari constructor
    }
    protected function pdo(): PDO
    {
        return $this->pdo; // hanya kelas turunan yang dapat memakai koneksi
    }
    abstract public function cari(string $kata): array; // kontrak yang wajib diisi kelas turunan
}