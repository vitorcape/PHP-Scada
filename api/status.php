<?php
/**
 * api/status.php — Polling do dashboard (a cada 2 s)
 *
 * Lógica de online/offline via esp32_status:
 *   1. Busca última linha da tabela
 *   2. Se 'online' e ts < 15 s atrás → online
 *   3. Se 'online' e ts ≥ 15 s atrás → INSERT 'offline', retorna offline
 *   4. Se 'offline' ou tabela vazia  → offline
 */
require_once __DIR__ . '/../includes/middleware.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResp(['erro' => 'Apenas GET'], 405);
requireLogin();

$db = getDB();

// ── Telemetria + setpoints + histórico ────────────────────────────
$tel  = $db->query('SELECT * FROM telemetria ORDER BY ts DESC LIMIT 1')->fetch() ?: null;
$sp   = $db->query(
    'SELECT s.*, u.nome AS atualizado_por_nome
     FROM setpoints s LEFT JOIN usuarios u ON s.atualizado_por = u.id
     WHERE s.id = 1'
)->fetch() ?: null;
$hist = $db->query(
    'SELECT nivel_pct, valvula_aberta, helice_rpm, ts
     FROM telemetria ORDER BY ts DESC LIMIT 30'
)->fetchAll();

// ── Status ESP32 via tabela dedicada ─────────────────────────────
$TIMEOUT_S = 15;   // segundos sem heartbeat → considera offline

$lastStatus = $db->query(
    'SELECT id, status, ts FROM esp32_status ORDER BY id DESC LIMIT 1'
)->fetch();

$esp32Online  = false;
$esp32Status  = 'offline';

if ($lastStatus) {
    $diff = time() - strtotime($lastStatus['ts']);

    if ($lastStatus['status'] === 'online' && $diff <= $TIMEOUT_S) {
        // Heartbeat recente → online
        $esp32Online = true;
        $esp32Status = 'online';

    } elseif ($lastStatus['status'] === 'online' && $diff > $TIMEOUT_S) {
        // Estava online mas sumiu → registra offline
        $db->exec("INSERT INTO esp32_status (status) VALUES ('offline')");
        $esp32Online = false;
        $esp32Status = 'offline';
        // (próximo heartbeat do ESP32 criará nova linha 'online')
    }
    // else: última linha já é 'offline' → esp32Online=false (já definido)
}

jsonResp([
    'telemetria'   => $tel,
    'setpoints'    => $sp,
    'historico'    => array_reverse($hist),
    'usuario'      => usuarioAtual(),
    'esp32_online' => $esp32Online,
    'esp32_status' => $esp32Status,
    'server_ts'    => date('Y-m-d H:i:s'),
]);
