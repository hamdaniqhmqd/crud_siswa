<?php

declare(strict_types=1);

function eAdmin(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (
        !is_string($token) || !isset($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Token formulir tidak valid. Muat ulang halaman.');
    }
}
function redirectAdmin(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}
function teksInput(array $input, string $key): string
{
    $value = $input[$key] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Bentuk input tidak valid: ' . $key);
    }
    return trim($value);
}
function idPositif(string $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('ID harus berupa bilangan bulat positif.');
    }
    return $id;
}
