<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/repositories/TreinadorRepository.php';
require_once __DIR__ . '/repositories/TokenPersistenteRepository.php';

$treinadorRepository =
    new TreinadorRepository($pdo);

$tokenRepository =
    new TokenPersistenteRepository($pdo);

/*
|--------------------------------------------------------------------------
| Apenas POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: login.php');
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

    registrarLog(
        'FALHA_AUTENTICACAO',
        'Tentativa de login com token CSRF inválido.'
    );

    $_SESSION['erro_login'] =
        'A sessão do formulário expirou. Tente novamente.';

    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Dados recebidos
|--------------------------------------------------------------------------
*/

$email = filter_input(
    INPUT_POST,
    'email',
    FILTER_VALIDATE_EMAIL
);

$senha =
    $_POST['senha'] ?? '';

$manterConectado =
    isset($_POST['manter_conectado']) &&
    $_POST['manter_conectado'] === '1';

/*
|--------------------------------------------------------------------------
| Validação
|--------------------------------------------------------------------------
*/

if (
    $email === false ||
    $email === null ||
    !is_string($senha) ||
    strlen($senha) < 8 ||
    strlen($senha) > 72
) {
    http_response_code(422);

    registrarLog(
        'FALHA_AUTENTICACAO',
        'Tentativa de login com dados inválidos.'
    );

    $_SESSION['erro_login'] =
        'E-mail ou senha inválidos.';

    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Autenticação
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Busca usuário através do Repository
    |--------------------------------------------------------------------------
    */

    $treinador =
        $treinadorRepository->buscarPorEmail(
            $email
        );

    /*
    |--------------------------------------------------------------------------
    | Verifica senha
    |--------------------------------------------------------------------------
    */

    if (
        $treinador === null ||
        !password_verify(
            $senha,
            $treinador['senha_hash']
        )
    ) {
        http_response_code(401);

        registrarLog(
            'FALHA_AUTENTICACAO',
            'Falha de autenticação.',
            [
                'email' => $email
            ]
        );

        $_SESSION['erro_login'] =
            'E-mail ou senha inválidos.';

        header('Location: login.php');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Proteção contra session fixation
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);

    /*
    |--------------------------------------------------------------------------
    | Criação da sessão
    |--------------------------------------------------------------------------
    */

    $_SESSION['usuario_id'] =
        (int) $treinador['id'];

    $_SESSION['usuario_nome'] =
        $treinador['nome'];

    $_SESSION['usuario_email'] =
        $treinador['email'];

    $_SESSION['perfil_acesso'] =
        $treinador['perfil_acesso'];

    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );

    /*
    |--------------------------------------------------------------------------
    | Remove tokens expirados
    |--------------------------------------------------------------------------
    */

    $tokenRepository->limparExpirados();

    /*
    |--------------------------------------------------------------------------
    | Manter-me conectado
    |--------------------------------------------------------------------------
    */

    if ($manterConectado) {

        /*
         * Selector público usado para localizar
         * rapidamente o token no banco.
         */

        $selector =
            bin2hex(
                random_bytes(12)
            );

        /*
         * Token secreto.
         */

        $token =
            bin2hex(
                random_bytes(32)
            );

        /*
         * No banco é armazenado somente o hash.
         */

        $tokenHash =
            hash(
                'sha256',
                $token
            );

        /*
         * Validade de 30 dias.
         */

        $expiraTimestamp =
            time() +
            (60 * 60 * 24 * 30);

        $timezone =
            new DateTimeZone(
                'America/Sao_Paulo'
            );

        $expiracao =
            new DateTimeImmutable(
                '@' . $expiraTimestamp
            );

        $expiracao =
            $expiracao->setTimezone(
                $timezone
            );

        $expiraBanco =
            $expiracao->format(
                'Y-m-d H:i:s'
            );

        /*
        |--------------------------------------------------------------------------
        | Remove tokens anteriores do usuário
        |--------------------------------------------------------------------------
        */

        $tokenRepository
            ->removerPorTreinadorId(
                (int) $treinador['id']
            );

        /*
        |--------------------------------------------------------------------------
        | Salva token persistente
        |--------------------------------------------------------------------------
        */

        $tokenRepository->salvar(
            (int) $treinador['id'],
            $selector,
            $tokenHash,
            $expiraBanco
        );

        /*
        |--------------------------------------------------------------------------
        | Cookie persistente
        |--------------------------------------------------------------------------
        */

        $cookieSeguro =
            !empty($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] !== 'off';

        setcookie(
            'lembrar_login',
            $selector . ':' . $token,
            [
                'expires' =>
                    $expiraTimestamp,

                'path' =>
                    '/',

                'secure' =>
                    $cookieSeguro,

                'httponly' =>
                    true,

                'samesite' =>
                    'Lax'
            ]
        );

    } else {

        /*
        |--------------------------------------------------------------------------
        | Remove eventual cookie persistente antigo
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $_COOKIE['lembrar_login']
            )
        ) {
            $cookieAtual =
                (string)
                $_COOKIE['lembrar_login'];

            $partes =
                explode(
                    ':',
                    $cookieAtual,
                    2
                );

            if (
                count($partes) === 2
            ) {
                $selectorAnterior =
                    $partes[0];

                if (
                    preg_match(
                        '/^[a-f0-9]{24}$/',
                        $selectorAnterior
                    )
                ) {
                    $tokenRepository
                        ->removerPorSelector(
                            $selectorAnterior
                        );
                }
            }

            $cookieSeguro =
                !empty($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] !== 'off';

            setcookie(
                'lembrar_login',
                '',
                [
                    'expires' =>
                        time() - 3600,

                    'path' =>
                        '/',

                    'secure' =>
                        $cookieSeguro,

                    'httponly' =>
                        true,

                    'samesite' =>
                        'Lax'
                ]
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Log
    |--------------------------------------------------------------------------
    */

    registrarLog(
        'LOGIN_SUCESSO',
        'Login realizado com sucesso.',
        [
            'usuario_id' =>
                (int) $treinador['id'],

            'perfil' =>
                $treinador['perfil_acesso'],

            'persistente' =>
                $manterConectado
        ]
    );

    header(
        'Location: area-treinador.php'
    );

    exit;

} catch (PDOException $erro) {

    http_response_code(500);

    registrarLog(
        'ERRO_INTERNO',
        'Erro interno durante autenticação.',
        [
            'codigo' =>
                $erro->getCode()
        ]
    );

    $_SESSION['erro_login'] =
        'Não foi possível realizar o login no momento.';

    header('Location: login.php');
    exit;
}