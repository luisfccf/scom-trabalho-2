<?php

declare(strict_types=1);

final class TokenPersistenteRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function limparExpirados(): void
    {
        $consulta = $this->pdo->prepare(
            'DELETE FROM tokens_persistentes
             WHERE expira_em < NOW()'
        );

        $consulta->execute();
    }

    public function removerPorTreinadorId(
        int $treinadorId
    ): void {
        $consulta = $this->pdo->prepare(
            'DELETE FROM tokens_persistentes
             WHERE treinador_id = :treinador_id'
        );

        $consulta->execute([
            ':treinador_id' => $treinadorId
        ]);
    }

    public function salvar(
        int $treinadorId,
        string $selector,
        string $tokenHash,
        string $expiraEm
    ): void {
        $consulta = $this->pdo->prepare(
            'INSERT INTO tokens_persistentes (
                treinador_id,
                selector,
                token_hash,
                expira_em
            ) VALUES (
                :treinador_id,
                :selector,
                :token_hash,
                :expira_em
            )'
        );

        $consulta->execute([
            ':treinador_id' => $treinadorId,
            ':selector' => $selector,
            ':token_hash' => $tokenHash,
            ':expira_em' => $expiraEm
        ]);
    }

    public function buscarPorSelector(
        string $selector
    ): ?array {
        $consulta = $this->pdo->prepare(
            'SELECT
                id,
                treinador_id,
                selector,
                token_hash,
                expira_em,
                criado_em
             FROM tokens_persistentes
             WHERE selector = :selector
             LIMIT 1'
        );

        $consulta->execute([
            ':selector' => $selector
        ]);

        $token = $consulta->fetch();

        return $token ?: null;
    }

    public function removerPorSelector(
        string $selector
    ): void {
        $consulta = $this->pdo->prepare(
            'DELETE FROM tokens_persistentes
             WHERE selector = :selector'
        );

        $consulta->execute([
            ':selector' => $selector
        ]);
    }

    public function removerPorId(
        int $id
    ): void {
        $consulta = $this->pdo->prepare(
            'DELETE FROM tokens_persistentes
             WHERE id = :id'
        );

        $consulta->execute([
            ':id' => $id
        ]);
    }
}