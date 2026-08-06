-- ============================================================
-- MIGRAÇÃO V2 - Separação Dimensão/Fato + Rastreamento de Técnicos
-- ============================================================
-- Data: 2026-07-26
-- A tabela original `preventivas_rede` NÃO é alterada.
-- Este script é idempotente: limpa estado parcial antes de recriar.

USE `tom_preventiva`;

-- ============================================================
-- PHASE 1: Limpeza de estado parcial de execuções anteriores
-- ============================================================

-- Remove FK se existir (via information_schema para compatibilidade)
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'preventivas_arquivos'
    AND CONSTRAINT_NAME = 'fk_arq_atendimento'
);
SET @drop_fk = IF(@fk_exists > 0,
  'ALTER TABLE `preventivas_arquivos` DROP FOREIGN KEY `fk_arq_atendimento`',
  'SELECT 1'
);
PREPARE stmt1 FROM @drop_fk;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Remove índice se existir
DROP INDEX IF EXISTS `idx_atendimento` ON `preventivas_arquivos`;

-- Remove tabela atendimentos se existir (vazia pois INSERT falhou)
DROP TABLE IF EXISTS `atendimentos`;

-- Remove colunas novas se existirem (para recriar limpo)
SET @col_existente = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'preventivas_arquivos'
    AND COLUMN_NAME = 'atendimento_id'
);
SET @sql_drop = IF(@col_existente > 0,
  'ALTER TABLE `preventivas_arquivos`
   DROP COLUMN `atendimento_id`,
   DROP COLUMN `momento`,
   DROP COLUMN `enviado_por`',
  'SELECT 1'
);
PREPARE stmt2 FROM @sql_drop;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- ============================================================
-- PHASE 2: Criar tabela FATO: atendimentos
-- ============================================================
CREATE TABLE IF NOT EXISTS `atendimentos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `preventiva_id` INT NOT NULL,
  `tecnico_analise_id` INT DEFAULT NULL COMMENT 'Quem fez a análise primária',
  `tecnico_execucao_id` INT DEFAULT NULL COMMENT 'Quem executou/concluiu',
  `descricao_analise` TEXT DEFAULT NULL COMMENT 'Descrição da análise/avaliação',
  `descricao_execucao` TEXT DEFAULT NULL COMMENT 'Descrição do que foi executado',
  `latitude_analise` DECIMAL(10,8) DEFAULT NULL COMMENT 'Localização registrada na análise',
  `longitude_analise` DECIMAL(11,8) DEFAULT NULL COMMENT 'Localização registrada na análise',
  `latitude_execucao` DECIMAL(10,8) DEFAULT NULL COMMENT 'Localização registrada na execução',
  `longitude_execucao` DECIMAL(11,8) DEFAULT NULL COMMENT 'Localização registrada na execução',
  `status` VARCHAR(20) NOT NULL DEFAULT 'analise' COMMENT 'analise | execucao | revisao | concluido',
  `iniciado_em` DATETIME DEFAULT NULL,
  `concluido_em` DATETIME DEFAULT NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_preventiva` (`preventiva_id`),
  KEY `idx_tecnico_analise` (`tecnico_analise_id`),
  KEY `idx_tecnico_execucao` (`tecnico_execucao_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHASE 3: Alterar preventivas_arquivos
-- ============================================================
ALTER TABLE `preventivas_arquivos`
  ADD COLUMN `atendimento_id` INT DEFAULT NULL,
  ADD COLUMN `momento` VARCHAR(10) DEFAULT 'depois' COMMENT 'antes | depois',
  ADD COLUMN `enviado_por` INT DEFAULT NULL,
  ADD INDEX `idx_atendimento` (`atendimento_id`),
  ADD CONSTRAINT `fk_arq_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE;

-- ============================================================
-- PHASE 4: Atualizar status na preventivas_rede
-- ============================================================
UPDATE `preventivas_rede` SET `status` = 'aberta' WHERE `status` = 'Triagem';
UPDATE `preventivas_rede` SET `status` = 'em_atendimento' WHERE `status` IN ('Em Execução', 'Em Análise', 'Revisão');
UPDATE `preventivas_rede` SET `status` = 'concluida' WHERE `status` = 'Concluída';

-- ============================================================
-- PHASE 5: Registrar migration
-- ============================================================
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `executada_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`nome`) VALUES ('migracao_v2_separacao_dimensao_fato');
