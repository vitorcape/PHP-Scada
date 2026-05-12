<?php
// ================================================================
// config.php — Configurações globais do SCADA
// Coloque em C:\xampp\htdocs\scada\ e ajuste se necessário
// ================================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'scada_db');
define('DB_USER', 'root');
define('DB_PASS', '');              // XAMPP sem senha por padrão

// Mesma string que você vai usar no firmware do ESP32
define('ESP32_API_KEY', 'scada_esp32_2024_secret');

// Caminho base (igual ao nome da pasta em htdocs)
define('BASE_PATH', '/scada');

define('APP_NAME', 'SCADA Sim — T-01');
