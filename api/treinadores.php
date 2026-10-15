<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/validacoes.php';
require_once __DIR__ . '/../repositories/TreinadorRepository.php';

$treinadorRepository =
    new TreinadorRepository($pdo);


/*
|--------------------------------------------------------------------------
| Resposta JSON padronizada
|--------------------------------------------------------------------------
*/

function responderJson(
    int $status,
    array $dados
): never {
    http_response_code($status);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Validação CSRF para métodos que alteram dados
|--------------------------------------------------------------------------
*/

function validarCsrfApi(): void
{
    $csrfRecebido =
        $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    $csrfSessao =
        $_SESSION['csrf_token'] ?? '';

    if (
        $csrfRecebido === '' ||
        $csrfSessao === '' ||
        !hash_equals(
            $csrfSessao,
            $csrfRecebido
        )
    ) {
        responderJson(
            403,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Token CSRF inválido.'
            ]
        );
    }
}


/*
|--------------------------------------------------------------------------
| Ler JSON enviado
|--------------------------------------------------------------------------
*/

function lerJson(): array
{
    $conteudo =
        file_get_contents(
            'php://input'
        );

    $dados =
        json_decode(
            $conteudo ?: '',
            true
        );

    if (!is_array($dados)) {
        responderJson(
            400,
            [
                'sucesso' => false,
                'mensagem' =>
                    'JSON inválido.'
            ]
        );
    }

    return $dados;
}


/*
|--------------------------------------------------------------------------
| Autenticação
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['usuario_id'])) {
    responderJson(
        401,
        [
            'sucesso' => false,
            'mensagem' =>
                'Usuário não autenticado.'
        ]
    );
}


/*
|--------------------------------------------------------------------------
| Autorização
|--------------------------------------------------------------------------
*/

if (
    ($_SESSION['perfil_acesso'] ?? '')
    !== 'admin'
) {
    responderJson(
        403,
        [
            'sucesso' => false,
            'mensagem' =>
                'Acesso negado.'
        ]
    );
}


$metodo =
    $_SERVER['REQUEST_METHOD'];


/*
|--------------------------------------------------------------------------
| GET
|--------------------------------------------------------------------------
|
| GET /api/treinadores.php
| GET /api/treinadores.php?id=20
|
*/

if ($metodo === 'GET') {

    try {

        /*
        |--------------------------------------------------------------------------
        | Busca por ID
        |--------------------------------------------------------------------------
        */

        if (isset($_GET['id'])) {

            $id = filter_var(
                $_GET['id'],
                FILTER_VALIDATE_INT
            );

            if (
                $id === false ||
                $id < 1
            ) {
                responderJson(
                    400,
                    [
                        'sucesso' => false,
                        'mensagem' =>
                            'ID inválido.'
                    ]
                );
            }

            $treinador =
                $treinadorRepository
                    ->buscarDetalhesParaApi(
                        (int) $id
                    );

            if ($treinador === null) {
                responderJson(
                    404,
                    [
                        'sucesso' => false,
                        'mensagem' =>
                            'Treinador não encontrado.'
                    ]
                );
            }

            responderJson(
                200,
                [
                    'sucesso' => true,
                    'dados' => $treinador
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Listagem
        |--------------------------------------------------------------------------
        */

        $treinadores =
            $treinadorRepository
                ->listarParaApi();

        responderJson(
            200,
            [
                'sucesso' => true,
                'total' =>
                    count($treinadores),

                'dados' =>
                    $treinadores
            ]
        );

    } catch (PDOException $erro) {

        responderJson(
            500,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Erro interno do servidor.'
            ]
        );
    }
}


/*
|--------------------------------------------------------------------------
| POST - Criar treinador
|--------------------------------------------------------------------------
|
| POST /api/treinadores.php
|
*/

if ($metodo === 'POST') {

    validarCsrfApi();

    $dados =
        lerJson();

    $nome =
        trim(
            (string) (
                $dados['nome'] ?? ''
            )
        );

    $email =
        trim(
            (string) (
                $dados['email'] ?? ''
            )
        );

    $senha =
        (string) (
            $dados['senha'] ?? ''
        );

    $nivel =
        filter_var(
            $dados['nivel'] ?? null,
            FILTER_VALIDATE_INT
        );

    $regiao =
        trim(
            (string) (
                $dados['regiao'] ?? ''
            )
        );

    $experiencia =
        trim(
            (string) (
                $dados['experiencia'] ?? ''
            )
        );

    $dataNascimento =
        trim(
            (string) (
                $dados['data_nascimento']
                ?? ''
            )
        );

    $horario =
        trim(
            (string) (
                $dados['horario_preferido']
                ?? ''
            )
        );

    $motivacao =
        trim(
            (string) (
                $dados['motivacao'] ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Validações
    |--------------------------------------------------------------------------
    */

    $erros = [];

    if (!validarNome($nome)) {
        $erros[] =
            'Nome deve possuir entre 2 e 80 caracteres.';
    }

    if (!validarEmail($email)) {
        $erros[] =
            'E-mail inválido.';
    }

    if (mb_strlen($senha) < 6) {
        $erros[] =
            'Senha deve possuir pelo menos 6 caracteres.';
    }

    if (
        $nivel === false ||
        !validarNivel(
            (int) $nivel
        )
    ) {
        $erros[] =
            'Nível deve estar entre 1 e 100.';
    }

    if (
        $regiao === '' ||
        mb_strlen($regiao) > 20
    ) {
        $erros[] =
            'Região inválida.';
    }

    if (
        !validarExperiencia(
            $experiencia
        )
    ) {
        $erros[] =
            'Experiência inválida.';
    }

    if ($dataNascimento === '') {
        $erros[] =
            'Data de nascimento é obrigatória.';
    }

    if ($horario === '') {
        $erros[] =
            'Horário preferido é obrigatório.';
    }

    if (
        $motivacao === '' ||
        mb_strlen($motivacao) > 500
    ) {
        $erros[] =
            'Motivação deve possuir entre 1 e 500 caracteres.';
    }

    if (!empty($erros)) {
        responderJson(
            422,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Existem dados inválidos.',

                'erros' =>
                    $erros
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cadastro
    |--------------------------------------------------------------------------
    */

    try {

        /*
        |--------------------------------------------------------------------------
        | Verifica e-mail duplicado
        |--------------------------------------------------------------------------
        */

        if (
            $treinadorRepository
                ->existeEmail($email)
        ) {
            responderJson(
                409,
                [
                    'sucesso' => false,
                    'mensagem' =>
                        'E-mail já cadastrado.'
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Cria treinador através do Repository
        |--------------------------------------------------------------------------
        */

        $novoId =
            $treinadorRepository
                ->cadastrar(
                    [
                        'nome' =>
                            $nome,

                        'senha_hash' =>
                            password_hash(
                                $senha,
                                PASSWORD_DEFAULT
                            ),

                        'nivel' =>
                            (int) $nivel,

                        'email' =>
                            $email,

                        'perfil_url' =>
                            null,

                        'motivacao' =>
                            $motivacao,

                        'novidades' =>
                            0,

                        'experiencia' =>
                            $experiencia,

                        'regiao' =>
                            $regiao,

                        'data_nascimento' =>
                            $dataNascimento,

                        'horario_preferido' =>
                            $horario,

                        'cor_uniforme' =>
                            '#c51d1d',

                        'termos_aceitos' =>
                            1
                    ]
                );


        responderJson(
            201,
            [
                'sucesso' => true,

                'mensagem' =>
                    'Treinador criado com sucesso.',

                'dados' => [
                    'id' =>
                        $novoId,

                    'nome' =>
                        $nome,

                    'email' =>
                        $email,

                    'perfil_acesso' =>
                        'treinador',

                    'nivel' =>
                        (int) $nivel,

                    'regiao' =>
                        $regiao,

                    'experiencia' =>
                        $experiencia
                ]
            ]
        );

    } catch (PDOException $erro) {

        responderJson(
            500,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Erro interno do servidor.'
            ]
        );
    }
}


/*
|--------------------------------------------------------------------------
| PUT - Atualizar treinador
|--------------------------------------------------------------------------
|
| PUT /api/treinadores.php?id=20
|
*/

if ($metodo === 'PUT') {

    validarCsrfApi();


    /*
    |--------------------------------------------------------------------------
    | ID
    |--------------------------------------------------------------------------
    */

    $id =
        filter_var(
            $_GET['id'] ?? null,
            FILTER_VALIDATE_INT
        );

    if (
        $id === false ||
        $id === null ||
        $id < 1
    ) {
        responderJson(
            400,
            [
                'sucesso' => false,
                'mensagem' =>
                    'ID inválido.'
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Verifica existência
    |--------------------------------------------------------------------------
    */

    try {

        $treinadorExistente =
            $treinadorRepository
                ->buscarPorId(
                    (int) $id
                );

        if (
            $treinadorExistente === null
        ) {
            responderJson(
                404,
                [
                    'sucesso' => false,
                    'mensagem' =>
                        'Treinador não encontrado.'
                ]
            );
        }

    } catch (PDOException $erro) {

        responderJson(
            500,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Erro interno do servidor.'
            ]
        );
    }


    $dados =
        lerJson();

    $nome =
        trim(
            (string) (
                $dados['nome'] ?? ''
            )
        );

    $email =
        trim(
            (string) (
                $dados['email'] ?? ''
            )
        );

    $nivel =
        filter_var(
            $dados['nivel'] ?? null,
            FILTER_VALIDATE_INT
        );

    $regiao =
        trim(
            (string) (
                $dados['regiao'] ?? ''
            )
        );

    $experiencia =
        trim(
            (string) (
                $dados['experiencia'] ?? ''
            )
        );

    $dataNascimento =
        trim(
            (string) (
                $dados['data_nascimento']
                ?? ''
            )
        );

    $horario =
        trim(
            (string) (
                $dados['horario_preferido']
                ?? ''
            )
        );

    $motivacao =
        trim(
            (string) (
                $dados['motivacao'] ?? ''
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Validação
    |--------------------------------------------------------------------------
    */

    $erros = [];

    if (!validarNome($nome)) {
        $erros[] =
            'Nome deve possuir entre 2 e 80 caracteres.';
    }

    if (!validarEmail($email)) {
        $erros[] =
            'E-mail inválido.';
    }

    if (
        $nivel === false ||
        !validarNivel(
            (int) $nivel
        )
    ) {
        $erros[] =
            'Nível deve estar entre 1 e 100.';
    }

    if (
        $regiao === '' ||
        mb_strlen($regiao) > 20
    ) {
        $erros[] =
            'Região inválida.';
    }

    if (
        !validarExperiencia(
            $experiencia
        )
    ) {
        $erros[] =
            'Experiência inválida.';
    }

    if ($dataNascimento === '') {
        $erros[] =
            'Data de nascimento é obrigatória.';
    }

    if ($horario === '') {
        $erros[] =
            'Horário preferido é obrigatório.';
    }

    if (
        $motivacao === '' ||
        mb_strlen($motivacao) > 500
    ) {
        $erros[] =
            'Motivação deve possuir entre 1 e 500 caracteres.';
    }

    if (!empty($erros)) {
        responderJson(
            422,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Existem dados inválidos.',

                'erros' =>
                    $erros
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Atualização
    |--------------------------------------------------------------------------
    */

    try {

        /*
        |--------------------------------------------------------------------------
        | Verifica e-mail duplicado
        |--------------------------------------------------------------------------
        */

        if (
            $treinadorRepository
                ->existeEmailParaOutroTreinador(
                    $email,
                    (int) $id
                )
        ) {
            responderJson(
                409,
                [
                    'sucesso' => false,
                    'mensagem' =>
                        'E-mail já cadastrado por outro treinador.'
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Atualiza através do Repository
        |--------------------------------------------------------------------------
        */

        $treinadorRepository
            ->atualizarDadosApi(
                (int) $id,
                $nome,
                $email,
                (int) $nivel,
                $regiao,
                $experiencia,
                $dataNascimento,
                $horario,
                $motivacao
            );


        responderJson(
            200,
            [
                'sucesso' => true,

                'mensagem' =>
                    'Treinador atualizado com sucesso.',

                'dados' => [
                    'id' =>
                        (int) $id,

                    'nome' =>
                        $nome,

                    'email' =>
                        $email,

                    'nivel' =>
                        (int) $nivel,

                    'regiao' =>
                        $regiao,

                    'experiencia' =>
                        $experiencia
                ]
            ]
        );

    } catch (PDOException $erro) {

        responderJson(
            500,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Erro interno do servidor.'
            ]
        );
    }
}


/*
|--------------------------------------------------------------------------
| DELETE - Excluir treinador
|--------------------------------------------------------------------------
|
| DELETE /api/treinadores.php?id=20
|
*/

if ($metodo === 'DELETE') {

    validarCsrfApi();

    $id =
        filter_var(
            $_GET['id'] ?? null,
            FILTER_VALIDATE_INT
        );

    if (
        $id === false ||
        $id === null ||
        $id < 1
    ) {
        responderJson(
            400,
            [
                'sucesso' => false,
                'mensagem' =>
                    'ID inválido.'
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Impede administrador de excluir a própria conta
    |--------------------------------------------------------------------------
    */

    if (
        (int) $_SESSION['usuario_id']
        === (int) $id
    ) {
        responderJson(
            403,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Não é permitido excluir o próprio usuário.'
            ]
        );
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | Verifica se existe
        |--------------------------------------------------------------------------
        */

        $treinador =
            $treinadorRepository
                ->buscarPorId(
                    (int) $id
                );

        if ($treinador === null) {
            responderJson(
                404,
                [
                    'sucesso' => false,
                    'mensagem' =>
                        'Treinador não encontrado.'
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Exclusão através do Repository
        |--------------------------------------------------------------------------
        */

        $treinadorRepository
            ->excluirPorId(
                (int) $id
            );


        responderJson(
            200,
            [
                'sucesso' => true,

                'mensagem' =>
                    'Treinador excluído com sucesso.',

                'dados' => [
                    'id' =>
                        (int) $id,

                    'nome' =>
                        $treinador['nome']
                ]
            ]
        );

    } catch (PDOException $erro) {

        responderJson(
            500,
            [
                'sucesso' => false,
                'mensagem' =>
                    'Erro interno do servidor.'
            ]
        );
    }
}


/*
|--------------------------------------------------------------------------
| Método não permitido
|--------------------------------------------------------------------------
*/

responderJson(
    405,
    [
        'sucesso' => false,

        'mensagem' =>
            'Método não permitido.',

        'metodos_permitidos' => [
            'GET',
            'POST',
            'PUT',
            'DELETE'
        ]
    ]
);