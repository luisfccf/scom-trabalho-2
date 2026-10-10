<?php

declare(strict_types=1);

function registrarLog(
    string $evento,
    string $mensagem,
    array $contexto = []
): void {
    $diretorioLogs = __DIR__ . '/../logs';

    if (!is_dir($diretorioLogs)) {
        mkdir($diretorioLogs, 0755, true);
    }

    $arquivoLog = $diretorioLogs . '/app.log';

    $registro = [
        'timestamp' => date('Y-m-d H:i:s'),
        'evento' => $evento,
        'mensagem' => $mensagem,
        'contexto' => $contexto
    ];

    $linha = json_encode(
        $registro,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    if ($linha === false) {
        return;
    }

    file_put_contents(
        $arquivoLog,
        $linha . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}