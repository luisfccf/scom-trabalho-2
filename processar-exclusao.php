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

$csrfRecebido = $_POST['csrf_token'] ?? '';

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

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {
    http_response_code(422);
    exit('ID inválido.');
}

/*
 * Impede que o administrador exclua
 * a própria conta enquanto está logado.
 */
if ($id === (int) $_SESSION['usuario_id']) {
    http_response_code(403);
    exit('Você não pode excluir sua própria conta.');
}

require __DIR__ . '/config/conexao.php';

try {
    /*
     * Verifica se o treinador existe.
     */
    $consulta = $pdo->prepare(
        'SELECT id
        FROM treinadores
        WHERE id = :id
        LIMIT 1'
    );

    $consulta->execute([
        'id' => $id
    ]);

    $treinador = $consulta->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$treinador) {
        http_response_code(404);
        exit('Treinador não encontrado.');
    }

    /*
     * Exclusão usando consulta parametrizada.
     */
    $comando = $pdo->prepare(
        'DELETE FROM treinadores
        WHERE id = :id'
    );

    $comando->execute([
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
    http_response_code(500);

    exit(
        'Não foi possível excluir o treinador.'
    );
}