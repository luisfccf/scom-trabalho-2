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
    | Busca treinador por e-mail
    |--------------------------------------------------------------------------
    */

    public function buscarPorEmail(
        string $email
    ): ?array {
        $consulta = $this->pdo->prepare(
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

        $consulta->execute([
            ':email' => $email
        ]);

        $treinador = $consulta->fetch();

        return $treinador ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se um e-mail já existe
    |--------------------------------------------------------------------------
    */

    public function existeEmail(
        string $email
    ): bool {
        $consulta = $this->pdo->prepare(
            'SELECT id
             FROM treinadores
             WHERE email = :email
             LIMIT 1'
        );

        $consulta->execute([
            ':email' => $email
        ]);

        return (bool) $consulta->fetch();
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se e-mail pertence a outro treinador
    |--------------------------------------------------------------------------
    */

    public function existeEmailParaOutroTreinador(
        string $email,
        int $id
    ): bool {
        $consulta = $this->pdo->prepare(
            'SELECT id
             FROM treinadores
             WHERE email = :email
               AND id <> :id
             LIMIT 1'
        );

        $consulta->execute([
            ':email' => $email,
            ':id' => $id
        ]);

        return (bool) $consulta->fetch();
    }

    /*
    |--------------------------------------------------------------------------
    | Lista treinadores para a API
    |--------------------------------------------------------------------------
    */

    public function listarParaApi(): array
    {
        $consulta = $this->pdo->query(
            'SELECT
                id,
                nome,
                email,
                perfil_acesso,
                nivel,
                regiao,
                experiencia,
                criado_em
             FROM treinadores
             ORDER BY id ASC'
        );

        return $consulta->fetchAll();
    }

    /*
    |--------------------------------------------------------------------------
    | Busca detalhes de um treinador para a API
    |--------------------------------------------------------------------------
    */

    public function buscarDetalhesParaApi(
        int $id
    ): ?array {
        $consulta = $this->pdo->prepare(
            'SELECT
                id,
                nome,
                email,
                perfil_acesso,
                nivel,
                regiao,
                experiencia,
                data_nascimento,
                horario_preferido,
                motivacao,
                criado_em
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

    public function cadastrar(
        array $dados
    ): int {
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
            ':nome' =>
                $dados['nome'],

            ':senha_hash' =>
                $dados['senha_hash'],

            ':nivel' =>
                $dados['nivel'],

            ':email' =>
                $dados['email'],

            ':perfil_url' =>
                $dados['perfil_url'],

            ':motivacao' =>
                $dados['motivacao'],

            ':novidades' =>
                $dados['novidades'],

            ':experiencia' =>
                $dados['experiencia'],

            ':regiao' =>
                $dados['regiao'],

            ':data_nascimento' =>
                $dados['data_nascimento'],

            ':horario_preferido' =>
                $dados['horario_preferido'],

            ':cor_uniforme' =>
                $dados['cor_uniforme'],

            ':termos_aceitos' =>
                $dados['termos_aceitos']
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
    | Atualiza dados através da API
    |--------------------------------------------------------------------------
    */

    public function atualizarDadosApi(
        int $id,
        string $nome,
        string $email,
        int $nivel,
        string $regiao,
        string $experiencia,
        string $dataNascimento,
        string $horarioPreferido,
        string $motivacao
    ): void {
        $consulta = $this->pdo->prepare(
            'UPDATE treinadores
             SET
                nome = :nome,
                email = :email,
                nivel = :nivel,
                regiao = :regiao,
                experiencia = :experiencia,
                data_nascimento = :data_nascimento,
                horario_preferido = :horario_preferido,
                motivacao = :motivacao
             WHERE id = :id'
        );

        $consulta->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':nivel' => $nivel,
            ':regiao' => $regiao,
            ':experiencia' => $experiencia,
            ':data_nascimento' => $dataNascimento,
            ':horario_preferido' => $horarioPreferido,
            ':motivacao' => $motivacao,
            ':id' => $id
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Exclui treinador
    |--------------------------------------------------------------------------
    */

    public function excluirPorId(
        int $id
    ): void {
        $comando = $this->pdo->prepare(
            'DELETE FROM treinadores
             WHERE id = :id'
        );

        $comando->execute([
            ':id' => $id
        ]);
    }
}