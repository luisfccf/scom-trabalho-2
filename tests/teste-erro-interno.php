<?php

declare(strict_types=1);

require __DIR__ . '/../includes/logger.php';

try {
    throw new RuntimeException(
        'Erro interno simulado para teste.'
    );
} catch (Throwable $erro) {
    registrarLog(
        'ERRO_INTERNO',
        'Erro interno simulado durante teste.',
        [
            'tipo' => get_class($erro)
        ]
    );

    http_response_code(500);

    echo 'Teste concluído: erro interno registrado no log.';
}