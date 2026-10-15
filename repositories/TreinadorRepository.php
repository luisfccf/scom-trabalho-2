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
    | Cadastra treinador
    |--------------------------------------------------------------------------
    */

    public function cadastrar(array $dados): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO treinadores (
                nome,
                senha_hash,
                nivel,
                email,
                perfil_url,
                motivacao,
                novidades,
                experiencia,
                regiao,
                data_nascimento,
                horario_preferido,
                cor_uniforme,
                termos_aceitos
            ) VALUES (
                :nome,
                :senha_hash,
                :nivel,
                :email,
                :perfil_url,
                :motivacao,
                :novidades,
                :experiencia,
                :regiao,
                :data_nascimento,
                :horario_preferido,
                :cor_uniforme,
                :termos_aceitos
            )'
        );

        $consulta->execute([
            ':nome' => $dados['nome'],
            ':senha_hash' => $dados['senha_hash'],
            ':nivel' => $dados['nivel'],
            ':email' => $dados['email'],
            ':perfil_url' => $dados['perfil_url'],
            ':motivacao' => $dados['motivacao'],
            ':novidades' => $dados['novidades'],
            ':experiencia' => $dados['experiencia'],
            ':regiao' => $dados['regiao'],
            ':data_nascimento' => $dados['data_nascimento'],
            ':horario_preferido' => $dados['horario_preferido'],
            ':cor_uniforme' => $dados['cor_uniforme'],
            ':termos_aceitos' => $dados['termos_aceitos']
        ]);

        return (int) $this->pdo->lastInsertId();
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

    /*
    |--------------------------------------------------------------------------
    | Exclui treinador
    |--------------------------------------------------------------------------
    */

    public function excluirPorId(int $id): void
    {
        $comando = $this->pdo->prepare(
            'DELETE FROM treinadores
             WHERE id = :id'
        );

        $comando->execute([
            ':id' => $id
        ]);
    }
}