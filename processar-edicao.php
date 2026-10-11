<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/validacoes.php';

/*
|--------------------------------------------------------------------------
| Verifica autenticação
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Verifica permissão de administrador
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['perfil_acesso']) ||
    $_SESSION['perfil_acesso'] !== 'admin'
) {
    http_response_code(403);
    exit('Acesso negado.');
}

/*
|--------------------------------------------------------------------------
| Apenas requisições POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

/*
|--------------------------------------------------------------------------
| Validação CSRF
|--------------------------------------------------------------------------
*/

$csrfRecebido = $_POST['csrf_token'] ?? '';

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($csrfRecebido) ||
    !hash_equals($_SESSION['csrf_token'], $csrfRecebido)
) {
    http_response_code(403);
    exit('Token de segurança inválido.');
}

/*
|--------------------------------------------------------------------------
| Recebimento dos dados
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

$nome = trim(
    (string) ($_POST['nome'] ?? '')
);

$email = trim(
    (string) ($_POST['email'] ?? '')
);

$nivelRecebido = filter_input(
    INPUT_POST,
    'nivel',
    FILTER_VALIDATE_INT
);

$regiao = strtolower(
    trim((string) ($_POST['regiao'] ?? ''))
);

$perfilAcesso = trim(
    (string) ($_POST['perfil_acesso'] ?? '')
);

/*
|--------------------------------------------------------------------------
| Validação do ID
|--------------------------------------------------------------------------
*/

if ($id === false || $id === null || $id <= 0) {
    http_response_code(422);
    exit('ID de treinador inválido.');
}

/*
|--------------------------------------------------------------------------
| Validação do nome
|--------------------------------------------------------------------------
*/

if (!validarNome($nome)) {
    http_response_code(422);
    exit('O nome deve possuir entre 2 e 80 caracteres.');
}

/*
|--------------------------------------------------------------------------
| Validação do e-mail
|--------------------------------------------------------------------------
*/

if (!validarEmail($email)) {
    http_response_code(422);
    exit('E-mail inválido.');
}

/*
|--------------------------------------------------------------------------
| Validação do nível
|--------------------------------------------------------------------------
*/

if (
    $nivelRecebido === false ||
    $nivelRecebido === null ||
    !validarNivel($nivelRecebido)
) {
    http_response_code(422);
    exit('O nível deve estar entre 1 e 100.');
}

$nivel = (int) $nivelRecebido;

/*
|--------------------------------------------------------------------------
| Validação da região
|--------------------------------------------------------------------------
*/

$regioesPermitidas = [
    'kanto',
    'johto',
    'hoenn',
    'sinnoh',
    'unova',
    'kalos',
    'alola',
    'galar',
    'paldea'
];

if (!in_array($regiao, $regioesPermitidas, true)) {
    http_response_code(422);
    exit('Região inválida.');
}

/*
|--------------------------------------------------------------------------
| Validação do perfil
|--------------------------------------------------------------------------
*/

if (!validarPerfilAcesso($perfilAcesso)) {
    http_response_code(422);
    exit('Perfil de acesso inválido.');
}

/*
|--------------------------------------------------------------------------
| Atualização no banco de dados
|--------------------------------------------------------------------------
*/

try {
    /*
    |--------------------------------------------------------------------------
    | Verifica se o treinador existe
    |--------------------------------------------------------------------------
    */

    $consulta = $pdo->prepare(
        'SELECT id
         FROM treinadores
         WHERE id = :id
         LIMIT 1'
    );

    $consulta->execute([
        ':id' => $id
    ]);

    if (!$consulta->fetch()) {
        http_response_code(404);
        exit('Treinador não encontrado.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE parametrizado
    |--------------------------------------------------------------------------
    */

    $sql = '
        UPDATE treinadores
        SET
            nome = :nome,
            email = :email,
            nivel = :nivel,
            regiao = :regiao,
            perfil_acesso = :perfil_acesso
        WHERE id = :id
    ';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':nivel' => $nivel,
        ':regiao' => $regiao,
        ':perfil_acesso' => $perfilAcesso,
        ':id' => $id
    ]);

    /*
    |--------------------------------------------------------------------------
    | Renova token CSRF
    |--------------------------------------------------------------------------
    */

    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );

    /*
    |--------------------------------------------------------------------------
    | Retorna para área administrativa
    |--------------------------------------------------------------------------
    */

    header('Location: area-admin.php');
    exit;

} catch (PDOException $erro) {

    /*
    |--------------------------------------------------------------------------
    | E-mail duplicado / violação de UNIQUE
    |--------------------------------------------------------------------------
    */

    if ($erro->getCode() === '23000') {
        http_response_code(409);
        exit('Este e-mail já está sendo utilizado.');
    }

    /*
    |--------------------------------------------------------------------------
    | Erro interno
    |--------------------------------------------------------------------------
    */

    http_response_code(500);
    exit('Erro interno ao atualizar o treinador.');
}