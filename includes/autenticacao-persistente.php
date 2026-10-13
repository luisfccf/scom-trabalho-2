<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';

function tentarRestaurarLoginPersistente(PDO $pdo): void
{
    if (!empty($_SESSION['usuario_id'])) {
        return;
    }

    $cookie = $_COOKIE['lembrar_login'] ?? '';

    if (!is_string($cookie) || $cookie === '') {
        return;
    }

    $partes = explode(':', $cookie, 2);

    if (count($partes) !== 2) {
        return;
    }

    [$selector, $token] = $partes;

    if (
        !preg_match('/^[a-f0-9]{24}$/', $selector) ||
        !preg_match('/^[a-f0-9]{64}$/', $token)
    ) {
        return;
    }

    try {
        $consulta = $pdo->prepare(
            'SELECT
                tp.id AS token_id,
                tp.token_hash,
                tp.expira_em,
                t.id AS treinador_id,
                t.nome,
                t.email,
                t.perfil_acesso
            FROM tokens_persistentes tp
            INNER JOIN treinadores t
                ON t.id = tp.treinador_id
            WHERE tp.selector = :selector
            LIMIT 1'
        );

        $consulta->execute([
            ':selector' => $selector
        ]);

        $registro = $consulta->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$registro) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verifica expiração
        |--------------------------------------------------------------------------
        */

        if (
            strtotime($registro['expira_em']) < time()
        ) {
            $remover = $pdo->prepare(
                'DELETE FROM tokens_persistentes
                 WHERE id = :id'
            );

            $remover->execute([
                ':id' => (int) $registro['token_id']
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verifica token
        |--------------------------------------------------------------------------
        */

        $tokenHashRecebido = hash(
            'sha256',
            $token
        );

        if (
            !hash_equals(
                $registro['token_hash'],
                $tokenHashRecebido
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Recria sessão
        |--------------------------------------------------------------------------
        */

        session_regenerate_id(true);

        $_SESSION['usuario_id'] =
            (int) $registro['treinador_id'];

        $_SESSION['usuario_nome'] =
            $registro['nome'];

        $_SESSION['usuario_email'] =
            $registro['email'];

        $_SESSION['perfil_acesso'] =
            $registro['perfil_acesso'];

        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));

    } catch (PDOException $erro) {
        return;
    }
}