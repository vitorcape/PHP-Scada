<?php
/**
 * api/alarmes.php
 * GET ?pagina=1&limite=50  — histórico paginado de alarmes
 */
require_once __DIR__ . '/../includes/middleware.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResp(['erro' => 'Apenas GET'], 405);
requireLogin();

$db     = getDB();
$limite = min(200, max(10, (int)($_GET['limite'] ?? 50)));
$offset = max(0, ((int)($_GET['pagina'] ?? 1) - 1)) * $limite;
$tipo   = $_GET['tipo'] ?? '';

$where = '';
if (in_array($tipo, ['alto','normal','baixo'], true))
    $where = "WHERE tipo = " . $db->quote($tipo);

$total = (int) $db->query("SELECT COUNT(*) FROM alarmes $where")->fetchColumn();
$rows  = $db->query(
    "SELECT id, tipo, nivel_pct, ts FROM alarmes $where
     ORDER BY id DESC LIMIT $limite OFFSET $offset"
)->fetchAll();

jsonResp(['total' => $total, 'alarmes' => $rows]);
