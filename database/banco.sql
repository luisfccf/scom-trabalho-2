CREATE DATABASE IF NOT EXISTS pokemon_scom
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE pokemon_scom;

CREATE TABLE IF NOT EXISTS treinadores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(80) NOT NULL,

    senha_hash VARCHAR(255) NOT NULL,

    perfil_acesso VARCHAR(20) NOT NULL DEFAULT 'treinador',

    nivel TINYINT UNSIGNED NOT NULL DEFAULT 1,

    email VARCHAR(150) NOT NULL UNIQUE,

    perfil_url VARCHAR(255) NULL,

    motivacao VARCHAR(500) NOT NULL,

    novidades BOOLEAN NOT NULL DEFAULT FALSE,

    experiencia VARCHAR(20) NOT NULL,

    regiao VARCHAR(20) NOT NULL,

    data_nascimento DATE NOT NULL,

    horario_preferido TIME NOT NULL,

    cor_uniforme CHAR(7) NOT NULL DEFAULT '#c51d1d',

    termos_aceitos BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_nivel
        CHECK (
            nivel BETWEEN 1 AND 100
        ),

    CONSTRAINT chk_perfil_acesso
        CHECK (
            perfil_acesso IN (
                'treinador',
                'admin'
            )
        ),

    CONSTRAINT chk_experiencia
        CHECK (
            experiencia IN (
                'iniciante',
                'intermediario',
                'avancado'
            )
        ),

    CONSTRAINT chk_termos
        CHECK (
            termos_aceitos = TRUE
        )
);

CREATE TABLE IF NOT EXISTS tokens_persistentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    treinador_id INT UNSIGNED NOT NULL,

    selector CHAR(24) NOT NULL,

    token_hash CHAR(64) NOT NULL,

    expira_em DATETIME NOT NULL,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_token_selector
        UNIQUE (selector),

    CONSTRAINT fk_token_treinador
        FOREIGN KEY (treinador_id)
        REFERENCES treinadores(id)
        ON DELETE CASCADE,

    INDEX idx_token_treinador (
        treinador_id
    ),

    INDEX idx_token_expira (
        expira_em
    )
);