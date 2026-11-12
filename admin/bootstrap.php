<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/DataRepository.php';
require_once __DIR__ . '/validasi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$config = require __DIR__ . '/entitas.php';

$repo = new DataRepository($pdo, $config);
