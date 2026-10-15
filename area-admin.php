<?php

declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Verifica autenticação
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Verifica permissão de administrador
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Gera token CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}

/*
|--------------------------------------------------------------------------
| Dependências
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/repositories/TreinadorRepository.php';

/*
|--------------------------------------------------------------------------
| Configuração da página
|--------------------------------------------------------------------------
*/

$tituloPagina =
    'Área Administrativa - Mundo Pokémon';

$treinadores = [];
$erroConsulta = '';

/*
|--------------------------------------------------------------------------
| Busca treinadores através do Repository
|--------------------------------------------------------------------------
*/

try {
    $treinadorRepository =
        new TreinadorRepository($pdo);

    $treinadores =
        $treinadorRepository
            ->listarParaApi();

} catch (PDOException $erro) {

    http_response_code(500);

    $erroConsulta =
        'Não foi possível carregar os treinadores.';
}

/*
|--------------------------------------------------------------------------
| Cabeçalho
|--------------------------------------------------------------------------
*/

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
                                        (string) (
                                            $treinador['nome'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $treinador['email'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $treinador['perfil_acesso'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= (int) (
                                        $treinador['nivel'] ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $treinador['regiao'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $treinador['criado_em'] ?? ''
                                        ),
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

                                    <?php if (
                                        (int) $treinador['id'] !==
                                        (int) $_SESSION['usuario_id']
                                    ): ?>

                                        <form
                                            action="processar-exclusao.php"
                                            method="post"
                                            style="display: inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= htmlspecialchars(
                                                    (string) $_SESSION['csrf_token'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $treinador['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                onclick="return confirm('Tem certeza que deseja excluir este treinador?');"
                                            >
                                                Excluir
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <span>
                                            Usuário atual
                                        </span>

                                    <?php endif; ?>

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