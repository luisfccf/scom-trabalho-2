<?php

declare(strict_types=1);

session_start();

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

if (
    !isset($_SESSION['perfil_acesso']) ||
    $_SESSION['perfil_acesso'] !== 'admin'
) {
    http_response_code(403);
    exit('Acesso negado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: area-admin.php');
    exit;
}

require __DIR__ . '/config/conexao.php';

/*
 * Validação CSRF.
 */
$csrfRecebido = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfRecebido) ||
    !hash_equals($_SESSION['csrf_token'], $csrfRecebido)
) {
    http_response_code(403);
    exit('Token CSRF inválido.');
}

/*
 * Dados recebidos.
 */
$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');

$nivel = filter_input(
    INPUT_POST,
    'nivel',
    FILTER_VALIDATE_INT
);

$regiao = $_POST['regiao'] ?? '';
$perfilAcesso = $_POST['perfil_acesso'] ?? '';

/*
 * Validações.
 */
if (!$id || $id <= 0) {
    http_response_code(422);
    exit('ID inválido.');
}

if (
    $nome === '' ||
    mb_strlen($nome) > 80
) {
    http_response_code(422);
    exit('Nome inválido.');
}

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    mb_strlen($email) > 150
) {
    http_response_code(422);
    exit('E-mail inválido.');
}

if (
    !$nivel ||
    $nivel < 1 ||
    $nivel > 100
) {
    http_response_code(422);
    exit('Nível inválido.');
}

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

$perfisPermitidos = [
    'treinador',
    'admin'
];

if (!in_array(
    $perfilAcesso,
    $perfisPermitidos,
    true
)) {
    http_response_code(422);
    exit('Perfil de acesso inválido.');
}

try {
    /*
     * Atualização segura com prepared statement.
     */
    $comando = $pdo->prepare(
        'UPDATE treinadores
        SET
            nome = :nome,
            email = :email,
            nivel = :nivel,
            regiao = :regiao,
            perfil_acesso = :perfil_acesso
        WHERE id = :id'
    );

    $comando->execute([
        'nome' => $nome,
        'email' => $email,
        'nivel' => $nivel,
        'regiao' => $regiao,
        'perfil_acesso' => $perfilAcesso,
        'id' => $id
    ]);

    /*
     * Renova o token após a operação.
     */
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));

    header('Location: area-admin.php');
    exit;

} catch (PDOException $erro) {

    /*
     * Código 23000 normalmente indica
     * violação de constraint, como e-mail duplicado.
     */
    if ($erro->getCode() === '23000') {
        http_response_code(409);
        exit('Já existe um treinador com esse e-mail.');
    }

    http_response_code(500);
    exit('Não foi possível atualizar o treinador.');
}