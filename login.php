<?php

declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Gera token CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

/*
|--------------------------------------------------------------------------
| Recupera mensagem de erro do login
|--------------------------------------------------------------------------
*/

$erroLogin = $_SESSION['erro_login'] ?? '';

unset($_SESSION['erro_login']);

$tituloPagina = 'Login - Mundo Pokémon';

require __DIR__ . '/includes/cabecalho.php';

?>

<main id="conteudo-principal">
    <section>
        <h2>Login do Treinador</h2>

        <p>
            Entre com seu e-mail e senha para acessar
            sua área de treinador.
        </p>

        <?php if ($erroLogin !== ''): ?>
            <div
                role="alert"
                aria-live="assertive"
            >
                <?= htmlspecialchars(
                    $erroLogin,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>

        <form
            action="processar-login.php"
            method="post"
            class="formulario-treinador"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'],
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ) ?>"
            >

            <div class="campo-form">
                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="campo-form">
                <label for="senha">
                    Senha
                </label>

                <input
                    type="password"
                    id="senha"
                    name="senha"
                    minlength="8"
                    maxlength="72"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="campo-manter-conectado">
                <div class="linha-checkbox">

                    <input
                        type="checkbox"
                        id="manter-conectado"
                        name="manter_conectado"
                        value="1"
                    >

                    <label for="manter-conectado">
                        Manter-me conectado
                    </label>

                </div>

                <small>
                    Permite continuar conectado mesmo após
                    fechar o navegador.
                </small>
            </div>

            <button type="submit">
                Entrar
            </button>
        </form>

        <p>
            Ainda não possui cadastro?
            <a href="index.html#cadastro">
                Cadastre-se como treinador
            </a>
        </p>
    </section>
</main>

<?php

require __DIR__ . '/includes/rodape.php';

?>