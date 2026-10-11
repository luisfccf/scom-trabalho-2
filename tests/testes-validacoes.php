<?php

declare(strict_types=1);

require __DIR__ . '/../includes/validacoes.php';

$aprovados = 0;
$reprovados = 0;

function testar(
    string $descricao,
    bool $resultadoEsperado,
    bool $resultadoObtido
): void {
    global $aprovados, $reprovados;

    if ($resultadoEsperado === $resultadoObtido) {
        echo "[OK] $descricao" . PHP_EOL;
        $aprovados++;
        return;
    }

    echo "[FALHOU] $descricao" . PHP_EOL;
    $reprovados++;
}

/*
|--------------------------------------------------------------------------
| Testes de nome
|--------------------------------------------------------------------------
*/

testar(
    'Nome valido',
    true,
    validarNome('Luis Felipe')
);

testar(
    'Nome muito curto deve ser invalido',
    false,
    validarNome('L')
);

/*
|--------------------------------------------------------------------------
| Testes de e-mail
|--------------------------------------------------------------------------
*/

testar(
    'E-mail valido',
    true,
    validarEmail('treinador@pokemon.com')
);

testar(
    'E-mail invalido',
    false,
    validarEmail('email-invalido')
);

/*
|--------------------------------------------------------------------------
| Testes de nivel
|--------------------------------------------------------------------------
*/

testar(
    'Nivel dentro do intervalo',
    true,
    validarNivel(50)
);

testar(
    'Nivel acima de 100 deve ser invalido',
    false,
    validarNivel(150)
);

/*
|--------------------------------------------------------------------------
| Testes de perfil
|--------------------------------------------------------------------------
*/

testar(
    'Perfil treinador permitido',
    true,
    validarPerfilAcesso('treinador')
);

testar(
    'Perfil desconhecido deve ser invalido',
    false,
    validarPerfilAcesso('hacker')
);

/*
|--------------------------------------------------------------------------
| Testes de experiencia
|--------------------------------------------------------------------------
*/

testar(
    'Experiencia avancado permitida',
    true,
    validarExperiencia('avancado')
);

testar(
    'Experiencia desconhecida deve ser invalida',
    false,
    validarExperiencia('mestre-pokemon')
);

echo PHP_EOL;
echo "Testes aprovados: $aprovados" . PHP_EOL;
echo "Testes reprovados: $reprovados" . PHP_EOL;

exit($reprovados === 0 ? 0 : 1);