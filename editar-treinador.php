<?php

declare(strict_types=1);

session_start();

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

if (
    !isset($_SESSION['perfil_acesso']) ||
    $_SESSION['perfil_acesso'] !== 'admin'
) {
    http_response_code(403);
    exit('Acesso negado.');
}

require __DIR__ . '/config/conexao.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('ID de treinador inválido.');
}

/*
 * Gera token CSRF caso ainda não exista.
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

try {
    $comando = $pdo->prepare(
        'SELECT
            id,
            nome,
            email,
            perfil_acesso,
            nivel,
            regiao
        FROM treinadores
        WHERE id = :id
        LIMIT 1'
    );

    $comando->execute([
        'id' => $id
    ]);

    $treinador = $comando->fetch(PDO::FETCH_ASSOC);

    if (!$treinador) {
        http_response_code(404);
        exit('Treinador não encontrado.');
    }

} catch (PDOException $erro) {
    http_response_code(500);
    exit('Erro ao consultar treinador.');
}

$tituloPagina = 'Editar Treinador - Mundo Pokémon';

require __DIR__ . '/includes/cabecalho.php';

?>

<main id="conteudo-principal">
    <section>
        <h2>Editar Treinador</h2>

        <form
            action="processar-edicao.php"
            method="post"
            class="formulario-treinador"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <input
                type="hidden"
                name="id"
                value="<?= (int) $treinador['id'] ?>"
            >

            <div class="campo-form">
                <label for="nome">
                    Nome
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="80"
                    required
                    value="<?= htmlspecialchars(
                        $treinador['nome'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="campo-form">
                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    required
                    value="<?= htmlspecialchars(
                        $treinador['email'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="campo-form">
                <label for="nivel">
                    Nível
                </label>

                <input
                    type="number"
                    id="nivel"
                    name="nivel"
                    min="1"
                    max="100"
                    required
                    value="<?= (int) $treinador['nivel'] ?>"
                >
            </div>

            <div class="campo-form">
                <label for="regiao">
                    Região
                </label>

                <select
                    id="regiao"
                    name="regiao"
                    required
                >
                    <?php
                    $regioes = [
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

                    foreach ($regioes as $regiao):
                    ?>
                        <option
                            value="<?= $regiao ?>"
                            <?= (
                                $treinador['regiao'] === $regiao
                            ) ? 'selected' : '' ?>
                        >
                            <?= ucfirst($regiao) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo-form">
                <label for="perfil_acesso">
                    Perfil de acesso
                </label>

                <select
                    id="perfil_acesso"
                    name="perfil_acesso"
                    required
                >
                    <option
                        value="treinador"
                        <?= (
                            $treinador['perfil_acesso'] ===
                            'treinador'
                        ) ? 'selected' : '' ?>
                    >
                        Treinador
                    </option>

                    <option
                        value="admin"
                        <?= (
                            $treinador['perfil_acesso'] ===
                            'admin'
                        ) ? 'selected' : '' ?>
                    >
                        Administrador
                    </option>
                </select>
            </div>

            <button type="submit">
                Salvar alterações
            </button>

        </form>

        <p>
            <a href="area-admin.php">
                Voltar para área administrativa
            </a>
        </p>
    </section>
</main>

<?php

require __DIR__ . '/includes/rodape.php';

?>