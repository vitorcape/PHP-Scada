<?php
/**
 * api/historico_telemetria.php
 *
 * Retorna telemetria paginada com TODAS as colunas
 * (nivel_pct, valvula_aberta, helice_rpm, ts).
 *
 * GET ?pagina=1&limite=20&minutos=1440
 */
require_once __DIR__ . '/../includes/middleware.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResp(['erro' => 'Apenas GET'], 405);
requireLogin();

$db      = getDB();
$limite  = min(100, max(5, (int)($_GET['limite'] ?? 20)));
$pagina  = max(1, (int)($_GET['pagina'] ?? 1));
$minutos = in_array((int)($_GET['minutos'] ?? 1440), [30,60,360,1440], true)
           ? (int)$_GET['minutos'] : 1440;
$offset  = ($pagina - 1) * $limite;

$where = "WHERE ts >= NOW() - INTERVAL {$minutos} MINUTE";

$total = (int) $db->query(
    "SELECT COUNT(*) FROM telemetria {$where}"
)->fetchColumn();

$rows = $db->query(
    "SELECT nivel_pct, valvula_aberta, helice_rpm, ts
     FROM telemetria {$where}
     ORDER BY ts DESC
     LIMIT {$limite} OFFSET {$offset}"
)->fetchAll();

jsonResp([
    'total'    => $total,
    'pagina'   => $pagina,
    'limite'   => $limite,
    'registros'=> $rows,
]);
