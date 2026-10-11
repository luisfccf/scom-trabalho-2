<?php

declare(strict_types=1);

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/logger.php';
require_once __DIR__ . '/includes/validacoes.php';

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

$novidades = isset($_POST['novidades']) ? 1 : 0;

$experiencia = strtolower(
    trim((string) ($_POST['experiencia'] ?? ''))
);

$regiao = strtolower(
    trim((string) ($_POST['regiao'] ?? ''))
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
    (string) ($_POST['cor_uniforme'] ?? '#c51d1d')
);

/*
| No HTML este checkbox se chama "termos"
*/
$termosAceitos = isset($_POST['termos']) ? 1 : 0;

/*
|--------------------------------------------------------------------------
| Validações
|--------------------------------------------------------------------------
*/

$erros = [];

/*
|--------------------------------------------------------------------------
| Nome
|--------------------------------------------------------------------------
*/

if (!validarNome($nome)) {
    $erros[] = 'O nome deve possuir entre 2 e 80 caracteres.';
}

/*
|--------------------------------------------------------------------------
| Senha
|--------------------------------------------------------------------------
*/

$tamanhoSenha = strlen($senha);

if ($tamanhoSenha < 8 || $tamanhoSenha > 72) {
    $erros[] = 'A senha deve possuir entre 8 e 72 caracteres.';
}

/*
|--------------------------------------------------------------------------
| Nível
|--------------------------------------------------------------------------
*/

if (
    $nivelRecebido === false ||
    $nivelRecebido === null ||
    !validarNivel((int) $nivelRecebido)
) {
    $erros[] = 'O nível deve estar entre 1 e 100.';
}

$nivel = (int) $nivelRecebido;

/*
|--------------------------------------------------------------------------
| E-mail
|--------------------------------------------------------------------------
*/

if ($email !== '' && !validarEmail($email)) {
    $erros[] = 'O e-mail informado é inválido.';
}

/*
|--------------------------------------------------------------------------
| Novidades
|--------------------------------------------------------------------------
*/

if ($novidades === 1 && $email === '') {
    $erros[] = 'Informe um e-mail para receber novidades.';
}

/*
|--------------------------------------------------------------------------
| URL do perfil
|--------------------------------------------------------------------------
*/

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
        strtolower((string) $esquema) !== 'https'
    ) {
        $erros[] = 'A URL do perfil deve ser válida e utilizar HTTPS.';
    }
}

/*
|--------------------------------------------------------------------------
| Motivação
|--------------------------------------------------------------------------
*/

$tamanhoMotivacao = mb_strlen($motivacao);

if (
    $tamanhoMotivacao < 1 ||
    $tamanhoMotivacao > 500
) {
    $erros[] = 'A motivação deve possuir entre 1 e 500 caracteres.';
}

/*
|--------------------------------------------------------------------------
| Experiência
|--------------------------------------------------------------------------
*/

if (!validarExperiencia($experiencia)) {
    $erros[] = 'Nível de experiência inválido.';
}

/*
|--------------------------------------------------------------------------
| Região
|--------------------------------------------------------------------------
*/

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

if (!in_array($regiao, $regioesPermitidas, true)) {
    $erros[] = 'Região inválida.';
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
    $data = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $dataNascimento
    );

    if (
        $data !== false &&
        $data->format('Y-m-d') === $dataNascimento
    ) {
        $hoje = new DateTimeImmutable('today');

        if ($data <= $hoje) {
            $idade = $data->diff($hoje)->y;

            if ($idade >= 10 && $idade <= 120) {
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
    $erros[] = 'Horário preferido inválido.';
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
    $erros[] = 'Cor do uniforme inválida.';
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

    require __DIR__ . '/includes/cabecalho.php';
    ?>

    <main>
        <section>
            <h1>Não foi possível realizar o cadastro</h1>

            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= escapar($erro) ?></li>
                <?php endforeach; ?>
            </ul>

            <p>
                <a href="index.html#cadastro">
                    Voltar ao cadastro
                </a>
            </p>
        </section>
    </main>

    <?php

    require __DIR__ . '/includes/rodape.php';
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

    exit('Erro interno ao processar o cadastro.');
}

/*
|--------------------------------------------------------------------------
| Cadastro no banco
|--------------------------------------------------------------------------
*/

try {
    $sql = '
        INSERT INTO treinadores (
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
        )
    ';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':nome' => $nome,
        ':senha_hash' => $senhaHash,
        ':nivel' => $nivel,
        ':email' => $email !== '' ? $email : null,
        ':perfil_url' => $perfilUrl !== ''
            ? $perfilUrl
            : null,
        ':motivacao' => $motivacao,
        ':novidades' => $novidades,
        ':experiencia' => $experiencia,
        ':regiao' => $regiao,
        ':data_nascimento' => $dataNascimento,
        ':horario_preferido' => $horarioPreferido,
        ':cor_uniforme' => $corUniforme,
        ':termos_aceitos' => $termosAceitos
    ]);

    $treinadorId = (int) $pdo->lastInsertId();

    registrarLog(
        'CRIACAO_ENTIDADE',
        'Treinador cadastrado com sucesso.',
        [
            'treinador_id' => $treinadorId
        ]
    );

    http_response_code(201);

    require __DIR__ . '/includes/cabecalho.php';
    ?>

    <main>
        <section>
            <h1>Cadastro realizado com sucesso!</h1>

            <p>
                O treinador
                <strong><?= escapar($nome) ?></strong>
                foi cadastrado no sistema.
            </p>

            <p>
                <a href="login.php">
                    Ir para o login
                </a>
            </p>

            <p>
                <a href="index.html">
                    Retornar à página inicial
                </a>
            </p>
        </section>
    </main>

    <?php

    require __DIR__ . '/includes/rodape.php';

} catch (PDOException $erro) {

    if ($erro->getCode() === '23000') {
        http_response_code(409);

        require __DIR__ . '/includes/cabecalho.php';
        ?>

        <main>
            <section>
                <h1>Cadastro não realizado</h1>

                <p>
                    O e-mail informado já está sendo utilizado
                    ou algum dado viola uma regra do sistema.
                </p>

                <p>
                    <a href="index.html#cadastro">
                        Voltar ao cadastro
                    </a>
                </p>
            </section>
        </main>

        <?php

        require __DIR__ . '/includes/rodape.php';
        exit;
    }

    registrarLog(
        'ERRO_INTERNO',
        'Erro interno durante cadastro de treinador.',
        [
            'codigo' => $erro->getCode()
        ]
    );

    http_response_code(500);

    require __DIR__ . '/includes/cabecalho.php';
    ?>

    <main>
        <section>
            <h1>Erro interno</h1>

            <p>
                Não foi possível concluir o cadastro.
                Tente novamente mais tarde.
            </p>

            <p>
                <a href="index.html#cadastro">
                    Voltar
                </a>
            </p>
        </section>
    </main>

    <?php

    require __DIR__ . '/includes/rodape.php';
}