<?php
/**
 * api/heartbeat.php — Exclusivo para o ESP32
 *
 * POST (X-API-Key): chamado a cada ~5 s pelo firmware.
 *   - Se a última linha for 'online'  → UPDATE ts (mesma linha)
 *   - Se a última linha for 'offline' ou não existir → INSERT nova linha 'online'
 *
 * GET (sessão): retorna histórico de status (opcional, para debug)
 */
require_once __DIR__ . '/../includes/middleware.php';
header('Content-Type: application/json; charset=utf-8');

// ── GET: debug / histórico ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireLogin();
    $rows = getDB()->query(
        'SELECT * FROM esp32_status ORDER BY id DESC LIMIT 20'
    )->fetchAll();
    jsonResp(['historico' => $rows]);
}

// ── POST: heartbeat do ESP32 ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireApiKey();

    $db   = getDB();
    $last = $db->query(
        'SELECT id, status FROM esp32_status ORDER BY id DESC LIMIT 1'
    )->fetch();

    if (!$last || $last['status'] === 'offline') {
        // Transição → nova linha 'online'
        $db->exec("INSERT INTO esp32_status (status) VALUES ('online')");
        jsonResp(['ok' => true, 'acao' => 'insert_online']);
    } else {
        // Já online → apenas atualiza o timestamp da mesma linha
        $db->prepare("UPDATE esp32_status SET ts = NOW() WHERE id = ?")
           ->execute([$last['id']]);
        jsonResp(['ok' => true, 'acao' => 'update_ts']);
    }
}

jsonResp(['erro' => 'Método não suportado'], 405);
