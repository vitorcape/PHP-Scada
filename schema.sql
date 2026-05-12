-- ================================================================
-- schema.sql v2 — DROP + RECREATE telemetria e setpoints
-- usuarios é mantida intacta
-- ================================================================
CREATE DATABASE IF NOT EXISTS scada_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scada_db;

-- Usuários (inalterada)
CREATE TABLE IF NOT EXISTS usuarios (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nome         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  senha_hash   VARCHAR(255) NOT NULL,
  role         ENUM('admin','visitor') NOT NULL DEFAULT 'visitor',
  ativo        TINYINT(1)  NOT NULL DEFAULT 1,
  criado_em    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_login TIMESTAMP   NULL,
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Telemetria — append-only, mantém 500 registros
DROP TABLE IF EXISTS telemetria;
CREATE TABLE telemetria (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nivel_pct     FLOAT      NOT NULL COMMENT '0–100 % do potenciômetro',
  valvula_aberta TINYINT(1) NOT NULL DEFAULT 0,
  helice_rpm    INT        NOT NULL DEFAULT 0 COMMENT '0–100 % de velocidade',
  btn1          TINYINT(1) NOT NULL DEFAULT 0,
  ts            TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ts (ts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Setpoints — linha única id=1
DROP TABLE IF EXISTS setpoints;
CREATE TABLE setpoints (
  id              INT PRIMARY KEY DEFAULT 1,
  valvula_aberta  TINYINT(1) NOT NULL DEFAULT 0,
  helice_rpm      INT        NOT NULL DEFAULT 0,
  modo            ENUM('manual','auto') NOT NULL DEFAULT 'manual',
  -- limiares de nível (%)
  limiar_baixo    INT        NOT NULL DEFAULT 20  COMMENT 'abaixo → alarme baixo',
  limiar_normal   INT        NOT NULL DEFAULT 40  COMMENT 'acima → normal',
  limiar_alto     INT        NOT NULL DEFAULT 80  COMMENT 'acima → alarme alto',
  limiar_valvula  INT        NOT NULL DEFAULT 30  COMMENT 'auto: ≤ esse valor → abre válvula',
  silenciar       TINYINT(1) NOT NULL DEFAULT 0,
  atualizado_por  INT        NULL,
  atualizado_em   TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sp_usr FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO setpoints
  (id, valvula_aberta, helice_rpm, modo, limiar_baixo, limiar_normal, limiar_alto, limiar_valvula, silenciar)
VALUES (1, 0, 0, 'manual', 20, 40, 80, 30, 0);
