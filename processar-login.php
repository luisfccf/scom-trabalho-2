<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    header('Location: login.php');
    exit;
}

/*
 * Validação do token CSRF.
 */
$csrfRecebido = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfRecebido) ||
    !hash_equals($_SESSION['csrf_token'], $csrfRecebido)
) {
    http_response_code(403);

    $_SESSION['erro_login'] =
        'A sessão do formulário expirou. Tente novamente.';

    header('Location: login.php');
    exit;
}

/*
 * Validação do e-mail.
 */
$email = filter_input(
    INPUT_POST,
    'email',
    FILTER_VALIDATE_EMAIL
);

$senha = $_POST['senha'] ?? '';

if (
    $email === false ||
    !is_string($senha) ||
    strlen($senha) < 8 ||
    strlen($senha) > 72
) {
    http_response_code(422);

    $_SESSION['erro_login'] =
        'E-mail ou senha inválidos.';

    header('Location: login.php');
    exit;
}

try {
    /*
     * Busca segura utilizando prepared statement.
     */
    $comando = $pdo->prepare(
        'SELECT
            id,
            nome,
            email,
            senha_hash,
            perfil_acesso
        FROM treinadores
        WHERE email = :email
        LIMIT 1'
    );

    $comando->execute([
        'email' => $email
    ]);

    $treinador = $comando->fetch(PDO::FETCH_ASSOC);

    /*
     * Não informa se o problema foi e-mail ou senha.
     * Isso evita revelar usuários cadastrados.
     */
    if (
        !$treinador ||
        !password_verify(
            $senha,
            $treinador['senha_hash']
        )
    ) {
        http_response_code(401);

        $_SESSION['erro_login'] =
            'E-mail ou senha inválidos.';

        header('Location: login.php');
        exit;
    }

    /*
     * Evita reutilização do ID de sessão anterior.
     */
    session_regenerate_id(true);

    $_SESSION['usuario_id'] =
        (int) $treinador['id'];

    $_SESSION['usuario_nome'] =
        $treinador['nome'];

    $_SESSION['usuario_email'] =
        $treinador['email'];

    $_SESSION['perfil_acesso'] =
        $treinador['perfil_acesso'];

    /*
     * Renova o token CSRF após o login.
     */
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));

    header('Location: area-treinador.php');
    exit;

} catch (PDOException $erro) {
    http_response_code(500);

    /*
     * Não mostramos detalhes do banco para o usuário.
     */
    $_SESSION['erro_login'] =
        'Não foi possível realizar o login no momento.';

    header('Location: login.php');
    exit;
}