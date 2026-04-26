<?php
/**
 * Albion P2P Trade — Conexão com banco de dados (PDO)
 * Cria o banco e as tabelas automaticamente na primeira execução.
 *
 * Em produção, substitua as constantes por variáveis de ambiente:
 *   getenv('DB_HOST'), getenv('DB_PASS'), etc.
 */

require_once dirname(__DIR__, 2) . '/config/app.php';

/**
 * Retorna a instância PDO (singleton).
 * Na primeira chamada cria o banco e as tabelas se necessário.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Conecta sem selecionar banco para poder criar se não existir
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET),
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        $pdo->exec(
            'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'
        );
        $pdo->exec('USE `' . DB_NAME . '`');

        _criar_tabelas($pdo);

    } catch (PDOException $e) {
        http_response_code(503);
        // Em produção, logue o erro e exiba mensagem genérica
        die('<pre style="color:#e74c3c">Erro de conexão: ' . htmlspecialchars($e->getMessage()) . '</pre>');
    }

    return $pdo;
}

/**
 * Cria as tabelas caso ainda não existam.
 */
function _criar_tabelas(PDO $pdo): void
{
    // ── Usuários ──────────────────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `usuarios` (
            `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            `email`         VARCHAR(180)  NOT NULL,
            `senha_hash`    VARCHAR(255)  NOT NULL,
            `nick`          VARCHAR(60)   NOT NULL,
            `nome`          VARCHAR(80)   NULL DEFAULT NULL,
            `sobrenome`     VARCHAR(80)   NULL DEFAULT NULL,
            `servidor`      ENUM('americas','europe','asia') NULL DEFAULT NULL,
            `newsletter`    TINYINT(1)    NOT NULL DEFAULT 1,
            `ativo`         TINYINT(1)    NOT NULL DEFAULT 1,
            `criado_em`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_email` (`email`),
            UNIQUE KEY `uq_nick`  (`nick`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // ── Sessões (remember me) ─────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sessoes` (
            `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            `usuario_id`  INT UNSIGNED  NOT NULL,
            `token_hash`  VARCHAR(64)   NOT NULL,
            `ip`          VARCHAR(45)   NULL DEFAULT NULL,
            `user_agent`  VARCHAR(255)  NULL DEFAULT NULL,
            `expira_em`   DATETIME      NOT NULL,
            `criado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_token` (`token_hash`),
            KEY `fk_sessao_usuario` (`usuario_id`),
            CONSTRAINT `fk_sessao_usuario`
                FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // ── Anúncios ──────────────────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `anuncios` (
            `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
            `usuario_id`    INT UNSIGNED   NOT NULL,
            `tipo`          ENUM('venda','compra') NOT NULL DEFAULT 'venda',
            `titulo`        VARCHAR(120)   NOT NULL,
            `categoria`     ENUM(
                                'armas','armadura-peitoral','armadura-capacete',
                                'armadura-calcado','mao-secundaria','capas',
                                'bolsas','montarias','consumiveis','equip-coleta',
                                'fabricacao','artefatos','cultivos','mobilia',
                                'vaidades','outros'
                            ) NOT NULL DEFAULT 'armas',
            `subcategoria`  VARCHAR(40) NULL DEFAULT NULL,
            `item_nome`     VARCHAR(80) NULL DEFAULT NULL,
            `item_sub`      VARCHAR(40) NULL DEFAULT NULL,
            `grau`          TINYINT UNSIGNED NOT NULL DEFAULT 4,
            `encantamento`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `qualidade`     ENUM('normal','bom','excepcional','excelente','obra-prima') NOT NULL DEFAULT 'normal',
            `preco`         BIGINT UNSIGNED NOT NULL DEFAULT 0,
            `descricao`     TEXT           NULL,
            `servidor`      ENUM('americas','europe','asia') NOT NULL DEFAULT 'americas',
            `cidade`        ENUM('caerleon','bridgewatch','fort sterling','lymhurst','martlock','thetford','brecilien') NULL DEFAULT NULL,
            `ativo`         TINYINT(1)     NOT NULL DEFAULT 1,
            `expira_em`     DATETIME       NOT NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL 60 DAY),
            `criado_em`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_tipo`        (`tipo`),
            KEY `idx_categoria`   (`categoria`),
            KEY `idx_subcategoria`(`subcategoria`),
            KEY `idx_item_nome` (`item_nome`),
            KEY `idx_grau`      (`grau`),
            KEY `idx_encant`    (`encantamento`),
            KEY `idx_qualidade` (`qualidade`),
            KEY `idx_servidor`  (`servidor`),
            KEY `idx_cidade`    (`cidade`),
            KEY `idx_usuario`   (`usuario_id`),
            CONSTRAINT `fk_anuncio_usuario`
                FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // ── Migração: subcategoria ────────────────────────────────────────────
    $existe = $pdo->query("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'anuncios'
          AND COLUMN_NAME  = 'subcategoria'
    ")->fetchColumn();
    if (!$existe) {
        $pdo->exec("ALTER TABLE anuncios ADD COLUMN subcategoria VARCHAR(40) NULL DEFAULT NULL AFTER categoria");
        $pdo->exec("ALTER TABLE anuncios ADD INDEX idx_subcategoria (subcategoria)");
    }

    // ── Migração: item_nome ───────────────────────────────────────────────
    $existe = $pdo->query("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'anuncios'
          AND COLUMN_NAME  = 'item_nome'
    ")->fetchColumn();
    if (!$existe) {
        $pdo->exec("ALTER TABLE anuncios ADD COLUMN item_nome VARCHAR(80) NULL DEFAULT NULL AFTER subcategoria");
        $pdo->exec("ALTER TABLE anuncios ADD INDEX idx_item_nome (item_nome)");
    }

    // ── Migração: expira_em ───────────────────────────────────────────────
    $existe = $pdo->query("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'anuncios'
          AND COLUMN_NAME  = 'expira_em'
    ")->fetchColumn();
    if (!$existe) {
        $pdo->exec("ALTER TABLE anuncios ADD COLUMN expira_em DATETIME NOT NULL DEFAULT '2099-12-31 23:59:59' AFTER ativo");
        $pdo->exec("UPDATE anuncios SET expira_em = DATE_ADD(criado_em, INTERVAL 60 DAY)");
        $pdo->exec("ALTER TABLE anuncios ADD INDEX idx_expira_em (expira_em)");
    }

    // ── Migração: ampliar ENUM de categoria ──────────────────────────────
    $enumCurrent = $pdo->query("
        SELECT COLUMN_TYPE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'anuncios'
          AND COLUMN_NAME  = 'categoria'
    ")->fetchColumn();
    if ($enumCurrent && strpos($enumCurrent, 'outros') === false) {
        $pdo->exec("
            ALTER TABLE anuncios
            MODIFY COLUMN `categoria` ENUM(
                'armas','armadura-peitoral','armadura-capacete',
                'armadura-calcado','mao-secundaria','capas',
                'bolsas','montarias','consumiveis','equip-coleta',
                'fabricacao','artefatos','cultivos','mobilia',
                'vaidades','outros'
            ) NOT NULL DEFAULT 'armas'
        ");
    }

    // ── Tokens de redefinição de senha ────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `senha_resets` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email`      VARCHAR(180) NOT NULL,
            `token_hash` VARCHAR(64)  NOT NULL,
            `expira_em`  DATETIME     NOT NULL,
            `usado`      TINYINT(1)   NOT NULL DEFAULT 0,
            `criado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_token` (`token_hash`),
            KEY `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
}
