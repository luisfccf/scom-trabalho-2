<?php

declare(strict_types=1);

final class TreinadorRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Busca treinador por ID
    |--------------------------------------------------------------------------
    */

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT *
             FROM treinadores
             WHERE id = :id
             LIMIT 1'
        );

        $consulta->execute([
            ':id' => $id
        ]);

        $treinador = $consulta->fetch();

        return $treinador ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Atualiza dados administrativos
    |--------------------------------------------------------------------------
    */

    public function atualizarDadosAdministrativos(
        int $id,
        string $nome,
        string $email,
        int $nivel,
        string $regiao,
        string $perfilAcesso
    ): void {
        $consulta = $this->pdo->prepare(
            'UPDATE treinadores
             SET
                nome = :nome,
                email = :email,
                nivel = :nivel,
                regiao = :regiao,
                perfil_acesso = :perfil_acesso
             WHERE id = :id'
        );

        $consulta->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':nivel' => $nivel,
            ':regiao' => $regiao,
            ':perfil_acesso' => $perfilAcesso,
            ':id' => $id
        ]);
    }
}