<?php

declare(strict_types=1);

require __DIR__ . '/config/conexao.php';
require __DIR__ . '/includes/logger.php';

/**
 * Escapa textos antes de exibi-los no HTML,
 * protegendo a página contra ataques XSS.
 */
function escapar(string $valor): string
{
    return htmlspecialchars(
        $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
 * Impede o acesso direto à página sem o envio
 * do formulário pelo método POST.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

/*
 * Popula as variáveis com os valores enviados
 * pelo formulário da página principal.
 */
$nome = trim($_POST['nome'] ?? '');
$senha = $_POST['senha'] ?? '';

$nivel = filter_input(
    INPUT_POST,
    'nivel',
    FILTER_VALIDATE_INT
);

$email = trim($_POST['email'] ?? '');
$perfilUrl = trim($_POST['perfil'] ?? '');
$motivacao = trim($_POST['mensagem'] ?? '');

$novidades = isset($_POST['novidades']);

$experiencia = trim(
    $_POST['experiencia'] ?? ''
);

$regiao = trim($_POST['regiao'] ?? '');

$dataNascimento = trim(
    $_POST['data-nascimento'] ?? ''
);

$horario = trim($_POST['horario'] ?? '');
$cor = trim($_POST['cor'] ?? '');

$termosAceitos = isset(
    $_POST['termos']
);

/*
 * Arrays com as opções aceitas pelo sistema.
 */
$experienciasPermitidas = [
    'iniciante',
    'intermediario',
    'avancado'
];

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

/*
 * Array utilizado para armazenar
 * as mensagens de validação.
 */
$erros = [];

if (
    mb_strlen($nome) < 3 ||
    mb_strlen($nome) > 80
) {
    $erros[] =
        'O nome deve possuir entre 3 e 80 caracteres.';
}

if (
    mb_strlen($senha) < 8 ||
    mb_strlen($senha) > 30
) {
    $erros[] =
        'A senha deve possuir entre 8 e 30 caracteres.';
}

if (
    $nivel === false ||
    $nivel === null ||
    $nivel < 1 ||
    $nivel > 100
) {
    $erros[] =
        'O nível deve estar entre 1 e 100.';
}

if ($email !== '') {
    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) ||
        mb_strlen($email) > 150
    ) {
        $erros[] =
            'Informe um e-mail válido.';
    }
}

if ($novidades && $email === '') {
    $erros[] =
        'O e-mail é obrigatório para receber novidades.';
}

if ($perfilUrl !== '') {
    $urlValida = filter_var(
        $perfilUrl,
        FILTER_VALIDATE_URL
    );

    $usaHttps = str_starts_with(
        strtolower($perfilUrl),
        'https://'
    );

    if (!$urlValida || !$usaHttps) {
        $erros[] =
            'O link deve ser uma URL válida iniciada por https://.';
    }
}

if (
    mb_strlen($motivacao) < 10 ||
    mb_strlen($motivacao) > 500
) {
    $erros[] =
        'A motivação deve possuir entre 10 e 500 caracteres.';
}

if (
    !in_array(
        $experiencia,
        $experienciasPermitidas,
        true
    )
) {
    $erros[] =
        'Selecione uma experiência válida.';
}

if (
    !in_array(
        $regiao,
        $regioesPermitidas,
        true
    )
) {
    $erros[] =
        'Selecione uma região válida.';
}

/*
 * Validação da data de nascimento e da idade.
 */
$dataObjeto = DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $dataNascimento
);

$dataValida =
    $dataObjeto !== false &&
    $dataObjeto->format('Y-m-d') ===
        $dataNascimento;

if (!$dataValida) {
    $erros[] =
        'Informe uma data de nascimento válida.';
} else {
    $hoje = new DateTimeImmutable('today');

    if ($dataObjeto > $hoje) {
        $erros[] =
            'A data de nascimento não pode estar no futuro.';
    } else {
        $idade = $dataObjeto->diff($hoje)->y;

        if ($idade < 10 || $idade > 120) {
            $erros[] =
                'O treinador deve ter entre 10 e 120 anos.';
        }
    }
}

if (
    !preg_match(
        '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
        $horario
    )
) {
    $erros[] =
        'Informe um horário válido.';
}

if (
    !preg_match(
        '/^#[0-9a-fA-F]{6}$/',
        $cor
    )
) {
    $erros[] =
        'Informe uma cor válida.';
}

if (!$termosAceitos) {
    $erros[] =
        'É necessário confirmar as informações.';
}

$cadastroRealizado = false;

if ($erros === []) {

    /*
     * A senha nunca é armazenada diretamente.
     * Somente o hash seguro é salvo no banco.
     */
    $senhaHash = password_hash(
        $senha,
        PASSWORD_DEFAULT
    );

    try {

        /*
         * Consulta parametrizada para impedir
         * ataques de SQL Injection.
         */
        $comando = $pdo->prepare(
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

        $comando->execute([
            'nome' => $nome,
            'senha_hash' => $senhaHash,
            'nivel' => $nivel,

            'email' => $email !== ''
                ? $email
                : null,

            'perfil_url' => $perfilUrl !== ''
                ? $perfilUrl
                : null,

            'motivacao' => $motivacao,

            'novidades' => $novidades
                ? 1
                : 0,

            'experiencia' => $experiencia,
            'regiao' => $regiao,

            'data_nascimento' =>
                $dataNascimento,

            'horario_preferido' =>
                $horario,

            'cor_uniforme' => $cor,
            'termos_aceitos' => 1
        ]);

        /*
         * Obtém o ID criado pelo banco.
         */
        $treinadorId = (int) $pdo->lastInsertId();

        /*
         * Log obrigatório:
         * criação de entidade.
         *
         * Não registramos senha ou hash.
         */
        registrarLog(
            'CRIACAO_ENTIDADE',
            'Treinador cadastrado com sucesso.',
            [
                'treinador_id' => $treinadorId
            ]
        );

        $cadastroRealizado = true;

        http_response_code(201);

    } catch (PDOException $erroBanco) {

        /*
         * Violação de constraint,
         * como e-mail duplicado.
         */
        if ($erroBanco->getCode() === '23000') {

            $erros[] =
                'O e-mail informado já está cadastrado.';

            http_response_code(409);

        } else {

            /*
             * Log obrigatório:
             * erro interno.
             *
             * Não exibimos detalhes técnicos
             * ao usuário.
             */
            registrarLog(
                'ERRO_INTERNO',
                'Erro interno durante cadastro de treinador.',
                [
                    'codigo' => $erroBanco->getCode()
                ]
            );

            $erros[] =
                'Ocorreu um erro ao salvar o cadastro.';

            http_response_code(500);
        }
    }

} else {
    http_response_code(422);
}

/*
 * Define o título usado pelo cabeçalho reutilizável.
 */
$tituloPagina = $cadastroRealizado
    ? 'Cadastro concluído'
    : 'Erro no cadastro';

/*
 * Inclui o HTML do cabeçalho.
 */
require __DIR__ . '/includes/cabecalho.php';

?>

<main
    id="conteudo-principal"
    class="resultado-cadastro-container"
>
    <section
        class="cartao-resultado <?= $cadastroRealizado
            ? 'resultado-sucesso'
            : 'resultado-erro' ?>"
        aria-labelledby="titulo-resultado"
    >
        <div class="resultado-conteudo">

            <?php if (!$cadastroRealizado): ?>

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

                    <?php foreach (
                        $erros as $mensagemErro
                    ): ?>

                        <li>
                            <?= escapar($mensagemErro) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

                <a
                    href="index.html#cadastro"
                    class="botao-resultado"
                >
                    Corrigir formulário
                </a>

            <?php else: ?>

                <span
                    class="resultado-etiqueta etiqueta-sucesso"
                >
                    ✓ CADASTRO CONCLUÍDO
                </span>

                <h2 id="titulo-resultado">
                    Bem-vindo à Liga Pokémon!
                </h2>

                <p class="resultado-introducao">
                    Seu perfil de treinador foi criado
                    com sucesso. Confira os dados:
                </p>

                <dl class="grade-dados-treinador">

                    <div
                        class="dado-treinador dado-nome"
                    >
                        <dt>
                            <span aria-hidden="true">
                                👤
                            </span>
                            Nome
                        </dt>

                        <dd>
                            <?= escapar($nome) ?>
                        </dd>
                    </div>

                    <div
                        class="dado-treinador dado-nivel"
                    >
                        <dt>
                            <span aria-hidden="true">
                                ⭐
                            </span>
                            Nível
                        </dt>

                        <dd>
                            <?= escapar(
                                (string) $nivel
                            ) ?>
                        </dd>
                    </div>

                    <div
                        class="dado-treinador dado-email"
                    >
                        <dt>
                            <span aria-hidden="true">
                                ✉️
                            </span>
                            E-mail
                        </dt>

                        <dd>
                            <?= escapar(
                                $email !== ''
                                    ? $email
                                    : 'Não informado'
                            ) ?>
                        </dd>
                    </div>

                    <div
                        class="dado-treinador dado-experiencia"
                    >
                        <dt>
                            <span aria-hidden="true">
                                🏆
                            </span>
                            Experiência
                        </dt>

                        <dd>
                            <?= escapar(
                                ucfirst($experiencia)
                            ) ?>
                        </dd>
                    </div>

                    <div
                        class="dado-treinador dado-regiao"
                    >
                        <dt>
                            <span aria-hidden="true">
                                🗺️
                            </span>
                            Região
                        </dt>

                        <dd>
                            <?= escapar(
                                ucfirst($regiao)
                            ) ?>
                        </dd>
                    </div>

                    <div
                        class="dado-treinador dado-novidades"
                    >
                        <dt>
                            <span aria-hidden="true">
                                📢
                            </span>
                            Novidades
                        </dt>

                        <dd>
                            <?= $novidades
                                ? 'Sim'
                                : 'Não' ?>
                        </dd>
                    </div>

                </dl>

                <div class="motivacao-resultado">

                    <h3>
                        <span aria-hidden="true">
                            💬
                        </span>
                        Motivação da jornada
                    </h3>

                    <p>
                        <?= escapar($motivacao) ?>
                    </p>

                </div>

                <div class="acoes-resultado">

                    <a
                        href="index.html"
                        class="botao-resultado"
                    >
                        Voltar à página inicial
                    </a>

                    <a
                        href="index.html#cadastro"
                        class="botao-resultado-secundario"
                    >
                        Novo cadastro
                    </a>

                </div>

            <?php endif; ?>

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

/*
 * Inclui o HTML do rodapé.
 */
require __DIR__ . '/includes/rodape.php';

?>