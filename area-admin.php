<?php

declare(strict_types=1);

session_start();

/*
 * Verifica se o usuário está autenticado.
 */
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

/*
 * Verifica se o usuário possui perfil de administrador.
 */
if (
    !isset($_SESSION['perfil_acesso']) ||
    $_SESSION['perfil_acesso'] !== 'admin'
) {
    http_response_code(403);

    $tituloPagina = 'Acesso negado - Mundo Pokémon';

    require __DIR__ . '/includes/cabecalho.php';
    ?>

    <main id="conteudo-principal">
        <section>
            <h2>Acesso negado</h2>

            <p>
                Você não possui permissão para acessar
                a área administrativa.
            </p>

            <p>
                <a href="area-treinador.php">
                    Voltar para minha área
                </a>
            </p>
        </section>
    </main>

    <?php

    require __DIR__ . '/includes/rodape.php';
    exit;
}

/*
 * Conexão com o banco.
 */
require __DIR__ . '/config/conexao.php';

$tituloPagina = 'Área Administrativa - Mundo Pokémon';

$treinadores = [];
$erroConsulta = '';

try {
    /*
     * Consulta todos os treinadores cadastrados.
     */
    $comando = $pdo->prepare(
        'SELECT
            id,
            nome,
            email,
            perfil_acesso,
            nivel,
            regiao,
            criado_em
        FROM treinadores
        ORDER BY id ASC'
    );

    $comando->execute();

    $treinadores = $comando->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $erro) {
    http_response_code(500);

    $erroConsulta =
        'Não foi possível carregar os treinadores.';
}

require __DIR__ . '/includes/cabecalho.php';

?>

<main id="conteudo-principal">

    <section>

        <h2>Área Administrativa</h2>

        <p>
            Acesso permitido apenas para administradores.
        </p>

        <p>
            <a href="area-treinador.php">
                Minha área
            </a>

            |

            <a href="logout.php">
                Sair
            </a>
        </p>

        <h3>Treinadores cadastrados</h3>

        <?php if ($erroConsulta !== ''): ?>

            <div
                role="alert"
                aria-live="assertive"
            >
                <?= htmlspecialchars(
                    $erroConsulta,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php elseif (empty($treinadores)): ?>

            <p>
                Nenhum treinador encontrado.
            </p>

        <?php else: ?>

            <div class="tabela-responsiva">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>Nível</th>
                            <th>Região</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($treinadores as $treinador): ?>

                            <tr>

                                <td>
                                    <?= (int) $treinador['id'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $treinador['nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $treinador['email'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $treinador['perfil_acesso'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int) $treinador['nivel'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $treinador['regiao'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $treinador['criado_em'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <a
                                        href="editar-treinador.php?id=<?= (int) $treinador['id'] ?>"
                                    >
                                        Editar
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php

require __DIR__ . '/includes/rodape.php';

?>