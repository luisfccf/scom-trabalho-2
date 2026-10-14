<?php

declare(strict_types=1);

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    exit('Arquivo .env não encontrado.');
}

$variaveis = parse_ini_file($envPath);

if ($variaveis === false) {
    exit('Não foi possível carregar o arquivo .env.');
}

$host = $variaveis['DB_HOST'] ?? '';
$banco = $variaveis['DB_NAME'] ?? '';
$usuario = $variaveis['DB_USER'] ?? '';
$senha = $variaveis['DB_PASS'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$banco};charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $erro) {
    exit('Não foi possível conectar ao banco de dados.');
}