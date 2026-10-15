<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/repositories/TokenPersistenteRepository.php';

$tokenRepository =
    new TokenPersistenteRepository($pdo);

/*
|--------------------------------------------------------------------------
| Remove o token persistente do banco
|--------------------------------------------------------------------------
*/

$cookiePersistente =
    $_COOKIE['lembrar_login'] ?? '';

if (
    is_string($cookiePersistente) &&
    $cookiePersistente !== ''
) {
    $partes = explode(
        ':',
        $cookiePersistente,
        2
    );

    if (count($partes) === 2) {

        $selector = $partes[0];

        if (
            preg_match(
                '/^[a-f0-9]{24}$/',
                $selector
            )
        ) {
            try {

                $tokenRepository
                    ->removerPorSelector(
                        $selector
                    );

            } catch (PDOException $erro) {

                /*
                 * O logout deve continuar mesmo
                 * se a remoção do token falhar.
                 */
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Apaga o cookie "Manter-me conectado"
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Limpa a sessão
|--------------------------------------------------------------------------
*/

$_SESSION = [];

/*
|--------------------------------------------------------------------------
| Apaga o cookie da sessão PHP
|--------------------------------------------------------------------------
*/

if (ini_get('session.use_cookies')) {

    $parametros =
        session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' =>
                time() - 42000,

            'path' =>
                $parametros['path'],

            'domain' =>
                $parametros['domain'],

            'secure' =>
                $parametros['secure'],

            'httponly' =>
                $parametros['httponly'],

            'samesite' =>
                $parametros['samesite']
                ?? 'Lax'
        ]
    );
}

/*
|--------------------------------------------------------------------------
| Destrói a sessão
|--------------------------------------------------------------------------
*/

session_destroy();

/*
|--------------------------------------------------------------------------
| Redireciona para o login
|--------------------------------------------------------------------------
*/

header('Location: login.php');
exit;