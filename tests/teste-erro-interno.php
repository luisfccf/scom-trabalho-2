<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/logger.php';

/*
|--------------------------------------------------------------------------
| Permite execução apenas pelo terminal
|--------------------------------------------------------------------------
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso não permitido.');
}

/*
|--------------------------------------------------------------------------
| Simulação de erro interno
|--------------------------------------------------------------------------
*/

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

    echo
        'Teste concluído: erro interno registrado no log.'
        . PHP_EOL;
}