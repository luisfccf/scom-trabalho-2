<?php

declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Login persistente
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/autenticacao-persistente.php';

tentarRestaurarLoginPersistente($pdo);

/*
|--------------------------------------------------------------------------
| Proteção da rota
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$tituloPagina = 'Área do Treinador - Mundo Pokémon';

require __DIR__ . '/includes/cabecalho.php';

$nomeUsuario =
    $_SESSION['usuario_nome'] ?? 'Treinador';

$perfilAcesso =
    $_SESSION['perfil_acesso'] ?? 'treinador';

?>

<main id="conteudo-principal">
    <section>
        <h2>Área do Treinador</h2>

        <p>
            Bem-vindo,
            <strong>
                <?= htmlspecialchars(
                    $nomeUsuario,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ) ?>
            </strong>.
        </p>

        <p>
            Perfil de acesso:
            <strong>
                <?= htmlspecialchars(
                    $perfilAcesso,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ) ?>
            </strong>
        </p>

        <p>
            Você está autenticado no sistema.
        </p>

        <?php if ($perfilAcesso === 'admin'): ?>
            <p>
                <a href="area-admin.php">
                    Área administrativa
                </a>
            </p>
        <?php endif; ?>

        <p>
            <a href="logout.php">
                Sair da conta
            </a>
        </p>
    </section>
</main>

<?php

require __DIR__ . '/includes/rodape.php';

?>