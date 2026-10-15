<?php

declare(strict_types=1);

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/validacoes.php';
require_once __DIR__ . '/repositories/TreinadorRepository.php';

$repositorio = new TreinadorRepository($pdo);

function escapar(string $valor): string
{
    return htmlspecialchars(
        $valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

/*
|--------------------------------------------------------------------------
| Dados recebidos do formulário
|--------------------------------------------------------------------------
*/

$nome = trim(
    (string) ($_POST['nome'] ?? '')
);

$senha = (string) ($_POST['senha'] ?? '');

$nivelRecebido = filter_input(
    INPUT_POST,
    'nivel',
    FILTER_VALIDATE_INT
);

$email = trim(
    (string) ($_POST['email'] ?? '')
);

$perfilUrl = trim(
    (string) ($_POST['perfil_url'] ?? '')
);

/*
| No HTML este campo se chama "mensagem"
*/

$motivacao = trim(
    (string) ($_POST['mensagem'] ?? '')
);

$novidades =
    isset($_POST['novidades']) ? 1 : 0;

$experiencia = strtolower(
    trim(
        (string) ($_POST['experiencia'] ?? '')
    )
);

$regiao = strtolower(
    trim(
        (string) ($_POST['regiao'] ?? '')
    )
);

/*
| No HTML este campo se chama "data-nascimento"
*/

$dataNascimento = trim(
    (string) ($_POST['data-nascimento'] ?? '')
);

/*
| No HTML este campo se chama "horario"
*/

$horarioPreferido = trim(
    (string) ($_POST['horario'] ?? '')
);

$corUniforme = trim(
    (string) (
        $_POST['cor_uniforme'] ?? '#c51d1d'
    )
);

/*
| No HTML este checkbox se chama "termos"
*/

$termosAceitos =
    isset($_POST['termos']) ? 1 : 0;

/*
|--------------------------------------------------------------------------
| Validações
|--------------------------------------------------------------------------
*/

$erros = [];

if (!validarNome($nome)) {
    $erros[] =
        'O nome deve possuir entre 2 e 80 caracteres.';
}

$tamanhoSenha = strlen($senha);

if (
    $tamanhoSenha < 8 ||
    $tamanhoSenha > 72
) {
    $erros[] =
        'A senha deve possuir entre 8 e 72 caracteres.';
}

if (
    $nivelRecebido === false ||
    $nivelRecebido === null ||
    !validarNivel((int) $nivelRecebido)
) {
    $erros[] =
        'O nível deve estar entre 1 e 100.';
}

$nivel = (int) $nivelRecebido;

if (
    $email !== '' &&
    !validarEmail($email)
) {
    $erros[] =
        'O e-mail informado é inválido.';
}

if (
    $novidades === 1 &&
    $email === ''
) {
    $erros[] =
        'Informe um e-mail para receber novidades.';
}

if ($perfilUrl !== '') {

    $urlValida = filter_var(
        $perfilUrl,
        FILTER_VALIDATE_URL
    );

    $esquema = parse_url(
        $perfilUrl,
        PHP_URL_SCHEME
    );

    if (
        $urlValida === false ||
        strtolower(
            (string) $esquema
        ) !== 'https'
    ) {
        $erros[] =
            'A URL do perfil deve ser válida e utilizar HTTPS.';
    }
}

$tamanhoMotivacao =
    mb_strlen($motivacao);

if (
    $tamanhoMotivacao < 1 ||
    $tamanhoMotivacao > 500
) {
    $erros[] =
        'A motivação deve possuir entre 1 e 500 caracteres.';
}

if (
    !validarExperiencia(
        $experiencia
    )
) {
    $erros[] =
        'Nível de experiência inválido.';
}

$regioesPermitidas = [
    'kanto',
    'johto',
    'hoenn',
    'sinnoh',
    'unova',
    'kalos',
    'alola',
    'galar',
    'paldea'
];

if (
    !in_array(
        $regiao,
        $regioesPermitidas,
        true
    )
) {
    $erros[] =
        'Região inválida.';
}

/*
|--------------------------------------------------------------------------
| Data de nascimento
|--------------------------------------------------------------------------
*/

$dataNascimentoValida = false;

if (
    preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $dataNascimento
    )
) {
    $data =
        DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $dataNascimento
        );

    if (
        $data !== false &&
        $data->format('Y-m-d')
        === $dataNascimento
    ) {
        $hoje =
            new DateTimeImmutable('today');

        if ($data <= $hoje) {

            $idade =
                $data->diff($hoje)->y;

            if (
                $idade >= 10 &&
                $idade <= 120
            ) {
                $dataNascimentoValida = true;
            }
        }
    }
}

if (!$dataNascimentoValida) {
    $erros[] =
        'A data de nascimento deve corresponder a uma idade entre 10 e 120 anos.';
}

/*
|--------------------------------------------------------------------------
| Horário
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
        $horarioPreferido
    )
) {
    $erros[] =
        'Horário preferido inválido.';
}

/*
|--------------------------------------------------------------------------
| Cor
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/^#[0-9A-Fa-f]{6}$/',
        $corUniforme
    )
) {
    $erros[] =
        'Cor do uniforme inválida.';
}

/*
|--------------------------------------------------------------------------
| Termos
|--------------------------------------------------------------------------
*/

if ($termosAceitos !== 1) {
    $erros[] =
        'É necessário aceitar os termos para realizar o cadastro.';
}

/*
|--------------------------------------------------------------------------
| Erros de validação
|--------------------------------------------------------------------------
*/

if ($erros !== []) {

    http_response_code(422);

    require __DIR__
        . '/includes/cabecalho.php';

    ?>

    <main
        id="conteudo-principal"
        class="resultado-cadastro-container"
    >
        <section
            class="cartao-resultado resultado-erro"
            aria-labelledby="titulo-resultado"
        >
            <div class="resultado-conteudo">

                <span
                    class="resultado-etiqueta etiqueta-erro"
                >
                    CADASTRO NÃO REALIZADO
                </span>

                <h2 id="titulo-resultado">
                    Não foi possível concluir seu cadastro
                </h2>

                <p class="resultado-introducao">
                    Confira os itens abaixo e tente novamente.
                </p>

                <ul class="lista-erros">

                    <?php foreach ($erros as $erro): ?>

                        <li>
                            <?= escapar($erro) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

                <a
                    href="index.html#cadastro"
                    class="botao-resultado"
                >
                    Corrigir formulário
                </a>

            </div>
        </section>
    </main>

    <?php

    require __DIR__
        . '/includes/rodape.php';

    exit;
}

/*
|--------------------------------------------------------------------------
| Hash da senha
|--------------------------------------------------------------------------
*/

$senhaHash = password_hash(
    $senha,
    PASSWORD_DEFAULT
);

if ($senhaHash === false) {

    registrarLog(
        'ERRO_INTERNO',
        'Falha ao gerar hash da senha durante cadastro.'
    );

    http_response_code(500);

    exit(
        'Erro interno ao processar o cadastro.'
    );
}

/*
|--------------------------------------------------------------------------
| Cadastro através do Repository
|--------------------------------------------------------------------------
*/

try {

    $treinadorId =
        $repositorio->cadastrar([
            'nome' => $nome,

            'senha_hash' =>
                $senhaHash,

            'nivel' =>
                $nivel,

            'email' =>
                $email !== ''
                    ? $email
                    : null,

            'perfil_url' =>
                $perfilUrl !== ''
                    ? $perfilUrl
                    : null,

            'motivacao' =>
                $motivacao,

            'novidades' =>
                $novidades,

            'experiencia' =>
                $experiencia,

            'regiao' =>
                $regiao,

            'data_nascimento' =>
                $dataNascimento,

            'horario_preferido' =>
                $horarioPreferido,

            'cor_uniforme' =>
                $corUniforme,

            'termos_aceitos' =>
                $termosAceitos
        ]);

    registrarLog(
        'CRIACAO_ENTIDADE',
        'Treinador cadastrado com sucesso.',
        [
            'treinador_id' =>
                $treinadorId
        ]
    );

    http_response_code(201);

    require __DIR__
        . '/includes/cabecalho.php';

    ?>

    <main
        id="conteudo-principal"
        class="resultado-cadastro-container"
    >
        <section
            class="cartao-resultado resultado-sucesso"
            aria-labelledby="titulo-resultado"
        >

            <div class="resultado-conteudo">

                <span
                    class="resultado-etiqueta etiqueta-sucesso"
                >
                    ✓ CADASTRO CONCLUÍDO
                </span>

                <h2 id="titulo-resultado">
                    Bem-vindo à Liga Pokémon!
                </h2>

                <p class="resultado-introducao">
                    Seu perfil de treinador foi criado com sucesso.
                    Confira os dados:
                </p>

                <dl class="grade-dados-treinador">

                    <div class="dado-treinador dado-nome">
                        <dt>Nome</dt>

                        <dd>
                            <?= escapar($nome) ?>
                        </dd>
                    </div>

                    <div class="dado-treinador">
                        <dt>E-mail</dt>

                        <dd>
                            <?= escapar(
                                $email !== ''
                                    ? $email
                                    : 'Não informado'
                            ) ?>
                        </dd>
                    </div>

                    <div class="dado-treinador">
                        <dt>Nível</dt>

                        <dd>
                            <?= escapar(
                                (string) $nivel
                            ) ?>
                        </dd>
                    </div>

                    <div class="dado-treinador">
                        <dt>Região</dt>

                        <dd>
                            <?= escapar(
                                ucfirst($regiao)
                            ) ?>
                        </dd>
                    </div>

                    <div class="dado-treinador">
                        <dt>Experiência</dt>

                        <dd>
                            <?= escapar(
                                ucfirst($experiencia)
                            ) ?>
                        </dd>
                    </div>

                    <div class="dado-treinador">
                        <dt>Novidades</dt>

                        <dd>
                            <?= $novidades === 1
                                ? 'Sim'
                                : 'Não' ?>
                        </dd>
                    </div>

                </dl>

                <div class="motivacao-resultado">

                    <h3>
                        Motivação da jornada
                    </h3>

                    <p>
                        <?= escapar($motivacao) ?>
                    </p>

                </div>

                <div class="acoes-resultado">

                    <a
                        href="login.php"
                        class="botao-resultado"
                    >
                        Ir para o login
                    </a>

                    <a
                        href="index.html#cadastro"
                        class="botao-resultado-secundario"
                    >
                        Novo cadastro
                    </a>

                </div>

            </div>

            <figure class="resultado-ilustracao">

                <img
                    src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/25.png"
                    alt="Pikachu representando a conclusão do cadastro"
                    width="280"
                    height="280"
                >

                <figcaption>
                    Sua jornada Pokémon começa agora!
                </figcaption>

            </figure>

        </section>
    </main>

    <?php

    require __DIR__
        . '/includes/rodape.php';

    exit;

} catch (PDOException $erro) {

    /*
    |--------------------------------------------------------------------------
    | E-mail duplicado ou constraint
    |--------------------------------------------------------------------------
    */

    if ($erro->getCode() === '23000') {

        http_response_code(409);

        require __DIR__
            . '/includes/cabecalho.php';

        ?>

        <main
            id="conteudo-principal"
            class="resultado-cadastro-container"
        >
            <section
                class="cartao-resultado resultado-erro"
                aria-labelledby="titulo-resultado"
            >

                <div class="resultado-conteudo">

                    <span
                        class="resultado-etiqueta etiqueta-erro"
                    >
                        CADASTRO NÃO REALIZADO
                    </span>

                    <h2 id="titulo-resultado">
                        Não foi possível concluir seu cadastro
                    </h2>

                    <p class="resultado-introducao">
                        O e-mail informado já está sendo utilizado
                        ou algum dado viola uma regra do sistema.
                    </p>

                    <a
                        href="index.html#cadastro"
                        class="botao-resultado"
                    >
                        Voltar ao cadastro
                    </a>

                </div>

            </section>
        </main>

        <?php

        require __DIR__
            . '/includes/rodape.php';

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Erro interno
    |--------------------------------------------------------------------------
    */

    registrarLog(
        'ERRO_INTERNO',
        'Erro interno durante cadastro de treinador.',
        [
            'codigo' =>
                $erro->getCode()
        ]
    );

    http_response_code(500);

    require __DIR__
        . '/includes/cabecalho.php';

    ?>

    <main
        id="conteudo-principal"
        class="resultado-cadastro-container"
    >
        <section
            class="cartao-resultado resultado-erro"
            aria-labelledby="titulo-resultado"
        >

            <div class="resultado-conteudo">

                <span
                    class="resultado-etiqueta etiqueta-erro"
                >
                    ERRO INTERNO
                </span>

                <h2 id="titulo-resultado">
                    Não foi possível concluir o cadastro
                </h2>

                <p class="resultado-introducao">
                    Ocorreu um erro interno.
                    Tente novamente mais tarde.
                </p>

                <a
                    href="index.html#cadastro"
                    class="botao-resultado"
                >
                    Voltar
                </a>

            </div>

        </section>
    </main>

    <?php

    require __DIR__
        . '/includes/rodape.php';

    exit;
}