<?php

declare(strict_types=1);

function validarNome(string $nome): bool
{
    $nome = trim($nome);
    $tamanho = mb_strlen($nome);

    return $tamanho >= 2 && $tamanho <= 80;
}

function validarEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validarNivel(int $nivel): bool
{
    return $nivel >= 1 && $nivel <= 100;
}

function validarPerfilAcesso(string $perfil): bool
{
    $perfisPermitidos = [
        'treinador',
        'admin'
    ];

    return in_array($perfil, $perfisPermitidos, true);
}

function validarExperiencia(string $experiencia): bool
{
    $experienciasPermitidas = [
        'iniciante',
        'intermediario',
        'avancado'
    ];

    return in_array(
        $experiencia,
        $experienciasPermitidas,
        true
    );
}