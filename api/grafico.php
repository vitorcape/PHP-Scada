<?php
/**
 * api/grafico.php — v2
 *
 * FIX: removido o padrão @row := @row + 1 (user-variables MySQL)
 * que não funciona de forma confiável no MySQL 8 / MariaDB.
 * Substituído por ROW_NUMBER() com fallback para LIMIT simples.
 *
 * GET ?tipo=nivel|agitador&minutos=30|60|360|1440
 */
require_once __DIR__ . '/../includes/middleware.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResp(['erro' => 'Apenas GET'], 405);
requireLogin();

$tipo    = in_array($_GET['tipo'] ?? '', ['nivel','agitador'], true)
           ? $_GET['tipo'] : 'nivel';
$minutos = in_array((int)($_GET['minutos'] ?? 30), [30,60,360,1440], true)
           ? (int)$_GET['minutos'] : 30;

$campo = ($tipo === 'nivel') ? 'nivel_pct' : 'helice_rpm';

$db = getDB();

// ── Conta registros no período ────────────────────────────────────
$total = (int) $db->query(
    "SELECT COUNT(*) FROM telemetria
     WHERE ts >= NOW() - INTERVAL {$minutos} MINUTE"
)->fetchColumn();

// ── Busca série temporal ──────────────────────────────────────────
// Máximo 200 pontos — com decimação via ROW_NUMBER() se necessário.
// Para ≤ 200 registros retorna tudo; acima, pega 1 de cada N.

$rows = [];

if ($total > 0) {
    $nth = max(1, (int) ceil($total / 200));

    if ($nth === 1) {
        // Sem decimação — retorna todos (até 200)
        $stmt = $db->prepare(
            "SELECT {$campo} AS valor, ts
             FROM telemetria
             WHERE ts >= NOW() - INTERVAL {$minutos} MINUTE
             ORDER BY ts ASC
             LIMIT 200"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

    } else {
        // Com decimação via ROW_NUMBER() (MySQL 8+ / MariaDB 10.2+)
        // Se o banco não suportar, cai no fallback abaixo.
        try {
            $stmt = $db->prepare(
                "SELECT valor, ts FROM (
                    SELECT {$campo} AS valor, ts,
                           ROW_NUMBER() OVER (ORDER BY ts ASC) AS rn
                    FROM telemetria
                    WHERE ts >= NOW() - INTERVAL {$minutos} MINUTE
                 ) ranked
                 WHERE MOD(rn, :nth) = 1
                 ORDER BY ts ASC
                 LIMIT 200"
            );
            $stmt->execute([':nth' => $nth]);
            $rows = $stmt->fetchAll();

        } catch (\PDOException $e) {
            // Fallback: sem decimação, apenas limita a 200 registros espaçados
            error_log('[SCADA GRF] ROW_NUMBER não disponível: ' . $e->getMessage());
            $stmt = $db->prepare(
                "SELECT {$campo} AS valor, ts
                 FROM telemetria
                 WHERE ts >= NOW() - INTERVAL {$minutos} MINUTE
                 ORDER BY ts ASC
                 LIMIT 200"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();
        }
    }
}

// ── Formata saída ─────────────────────────────────────────────────
$labels = [];
$values = [];
foreach ($rows as $r) {
    $labels[] = substr($r['ts'], 11, 8);       // HH:MM:SS
    $values[] = round((float) $r['valor'], 1);
}

// ── Setpoints para linhas de threshold ───────────────────────────
$sp = $db->query(
    'SELECT limiar_baixo, limiar_normal, limiar_alto FROM setpoints WHERE id = 1'
)->fetch() ?: null;

// ── Estatísticas ──────────────────────────────────────────────────
$stats = null;
if ($values) {
    $stats = [
        'atual'  => end($values),
        'min'    => min($values),
        'max'    => max($values),
        'media'  => round(array_sum($values) / count($values), 1),
        'pontos' => count($values),
    ];
}

jsonResp([
    'tipo'    => $tipo,
    'minutos' => $minutos,
    'total'   => $total,
    'labels'  => $labels,
    'values'  => $values,
    'stats'   => $stats,
    'sp'      => $sp,
]);
