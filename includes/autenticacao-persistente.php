<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../repositories/TreinadorRepository.php';
require_once __DIR__ . '/../repositories/TokenPersistenteRepository.php';

function tentarRestaurarLoginPersistente(PDO $pdo): void
{
    /*
    |--------------------------------------------------------------------------
    | Se já existe sessão, não precisa restaurar
    |--------------------------------------------------------------------------
    */

    if (!empty($_SESSION['usuario_id'])) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Recupera cookie persistente
    |--------------------------------------------------------------------------
    */

    $cookie =
        $_COOKIE['lembrar_login'] ?? '';

    if (
        !is_string($cookie) ||
        $cookie === ''
    ) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Separa selector e token
    |--------------------------------------------------------------------------
    */

    $partes =
        explode(
            ':',
            $cookie,
            2
        );

    if (count($partes) !== 2) {
        return;
    }

    [$selector, $token] = $partes;

    /*
    |--------------------------------------------------------------------------
    | Valida formato
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            '/^[a-f0-9]{24}$/',
            $selector
        ) ||
        !preg_match(
            '/^[a-f0-9]{64}$/',
            $token
        )
    ) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Repositories
    |--------------------------------------------------------------------------
    */

    $tokenRepository =
        new TokenPersistenteRepository($pdo);

    $treinadorRepository =
        new TreinadorRepository($pdo);

    try {

        /*
        |--------------------------------------------------------------------------
        | Busca token através do Repository
        |--------------------------------------------------------------------------
        */

        $registroToken =
            $tokenRepository
                ->buscarPorSelector(
                    $selector
                );

        if ($registroToken === null) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verifica expiração
        |--------------------------------------------------------------------------
        */

        if (
            strtotime(
                $registroToken['expira_em']
            ) < time()
        ) {
            $tokenRepository
                ->removerPorId(
                    (int) $registroToken['id']
                );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verifica token secreto
        |--------------------------------------------------------------------------
        */

        $tokenHashRecebido =
            hash(
                'sha256',
                $token
            );

        if (
            !hash_equals(
                $registroToken['token_hash'],
                $tokenHashRecebido
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Busca treinador associado ao token
        |--------------------------------------------------------------------------
        */

        $treinador =
            $treinadorRepository
                ->buscarPorId(
                    (int) $registroToken['treinador_id']
                );

        if ($treinador === null) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Recria sessão
        |--------------------------------------------------------------------------
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

        $_SESSION['csrf_token'] =
            bin2hex(
                random_bytes(32)
            );

    } catch (PDOException $erro) {

        /*
         * Se houver erro no banco, simplesmente
         * não restaura o login persistente.
         */

        return;
    }
}