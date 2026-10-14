<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/repositories/TreinadorRepository.php';

$repositorio = new TreinadorRepository($pdo);

/*
|--------------------------------------------------------------------------
| Autenticação
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Autorização
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
| Apenas POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: area-admin.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validação CSRF
|--------------------------------------------------------------------------
*/

$csrfRecebido =
    $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfRecebido) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $csrfRecebido
    )
) {
    http_response_code(403);
    exit('Token CSRF inválido.');
}

/*
|--------------------------------------------------------------------------
| ID
|--------------------------------------------------------------------------
*/

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (
    $id === false ||
    $id === null ||
    $id <= 0
) {
    http_response_code(422);
    exit('ID inválido.');
}

/*
|--------------------------------------------------------------------------
| Impede exclusão da própria conta
|--------------------------------------------------------------------------
*/

if (
    (int) $id ===
    (int) $_SESSION['usuario_id']
) {
    http_response_code(403);

    exit(
        'Você não pode excluir sua própria conta.'
    );
}

/*
|--------------------------------------------------------------------------
| Exclusão através do Repository
|--------------------------------------------------------------------------
*/

try {

    $treinador =
        $repositorio->buscarPorId(
            (int) $id
        );

    if ($treinador === null) {
        http_response_code(404);

        exit(
            'Treinador não encontrado.'
        );
    }

    $repositorio->excluirPorId(
        (int) $id
    );

    /*
    |--------------------------------------------------------------------------
    | Renova CSRF
    |--------------------------------------------------------------------------
    */

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );

    header(
        'Location: area-admin.php'
    );

    exit;

} catch (PDOException $erro) {

    http_response_code(500);

    exit(
        'Não foi possível excluir o treinador.'
    );
}