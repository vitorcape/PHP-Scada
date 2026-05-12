<?php
/**
 * api/telemetria.php — v3.3 (fix definitivo)
 *
 * Mudanças vs versão anterior:
 *  1. Validação trocada de filter_var() para is_numeric() + cast direto
 *     — filter_var(50, FILTER_VALIDATE_FLOAT) retorna FALSE em certos PHP
 *       porque o JSON enviado pelo ArduinoJson omite o ponto decimal em
 *       valores inteiros (50.0 → 50), e o FILTER trata como int inválido.
 *  2. INSERT telemetria é a PRIMEIRA operação — antes de qualquer outra.
 *  3. Detecção de alarme movida para DEPOIS do INSERT, com try/catch próprio.
 *  4. DELETE usa subquery mais simples e segura.
 *  5. Resposta inclui 'nivel_recebido' para debug via Serial Monitor.
 */
require_once __DIR__ . '/../includes/middleware.php';
header('Content-Type: application/json; charset=utf-8');

// ── GET: dashboard lê telemetria ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireLogin();
    $db = getDB();
    $ultima    = $db->query('SELECT * FROM telemetria ORDER BY ts DESC LIMIT 1')->fetch() ?: null;
    $historico = $db->query(
        'SELECT nivel_pct, valvula_aberta, helice_rpm, ts
         FROM telemetria ORDER BY ts DESC LIMIT 30'
    )->fetchAll();
    jsonResp(['telemetria' => $ultima, 'historico' => array_reverse($historico)]);
}

// ── POST: ESP32 envia leituras ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireApiKey();

    $raw = file_get_contents('php://input');
    $c   = json_decode($raw, true);

    // JSON inválido ou vazio
    if (!is_array($c)) {
        error_log('[SCADA TEL] JSON inválido: ' . $raw);
        jsonResp(['erro' => 'JSON inválido'], 400);
    }

    // ── Validação com is_numeric() — aceita int e float ──────────
    // filter_var(FILTER_VALIDATE_FLOAT) falha quando ArduinoJson
    // serializa 50.0 como 50 (sem ponto decimal).
    if (!isset($c['nivel_pct'])     || !is_numeric($c['nivel_pct']))
        jsonResp(['erro' => 'Campo nivel_pct ausente ou inválido'], 400);
    if (!isset($c['valvula_aberta']))
        jsonResp(['erro' => 'Campo valvula_aberta ausente'], 400);
    if (!isset($c['helice_rpm'])    || !is_numeric($c['helice_rpm']))
        jsonResp(['erro' => 'Campo helice_rpm ausente ou inválido'], 400);

    $nivel = (float) $c['nivel_pct'];
    $valv  = (int)   (bool) $c['valvula_aberta'];
    $rpm   = max(0, min(100, (int) $c['helice_rpm']));
    $btn1  = (int)   (bool) ($c['btn1'] ?? 0);

    $db = getDB();

    // ── 1. INSERT telemetria — operação principal, sem try/catch ──
    //    Se isso falhar, o erro real sobe e pode ser visto no log.
    $db->prepare(
        'INSERT INTO telemetria (nivel_pct, valvula_aberta, helice_rpm, btn1)
         VALUES (?, ?, ?, ?)'
    )->execute([round($nivel, 2), $valv, $rpm, $btn1]);

    // ── 2. Limpeza: mantém só os 500 mais recentes ───────────────
    //    Abordagem mais segura: encontra o ID mínimo a manter,
    //    depois deleta os anteriores a ele.
    try {
        $minId = $db->query(
            'SELECT MIN(id) FROM (
               SELECT id FROM telemetria ORDER BY ts DESC LIMIT 500
             ) AS keep'
        )->fetchColumn();

        if ($minId && $minId > 1) {
            $db->prepare('DELETE FROM telemetria WHERE id < ?')
               ->execute([$minId]);
        }
    } catch (\PDOException $e) {
        error_log('[SCADA TEL] Limpeza ignorada: ' . $e->getMessage());
    }

    // ── 3. Detecção de alarme — opcional, não bloqueia resposta ──
    try {
        $sp = $db->query(
            'SELECT limiar_baixo, limiar_alto FROM setpoints WHERE id = 1'
        )->fetch();

        if ($sp) {
            $novoEstado = ($nivel >= (float)$sp['limiar_alto'])
                       ? 'alto'
                       : (($nivel <= (float)$sp['limiar_baixo']) ? 'baixo' : 'normal');

            $ult = $db->query(
                'SELECT tipo FROM alarmes ORDER BY id DESC LIMIT 1'
            )->fetch();

            if (!$ult || $ult['tipo'] !== $novoEstado) {
                $db->prepare('INSERT INTO alarmes (tipo, nivel_pct) VALUES (?, ?)')
                   ->execute([$novoEstado, round($nivel, 2)]);
            }
        }
    } catch (\PDOException $e) {
        error_log('[SCADA TEL] Alarme ignorado: ' . $e->getMessage());
    }

    // Resposta inclui nivel_recebido para debug no Serial Monitor
    jsonResp([
        'ok'              => true,
        'ts'              => date('Y-m-d H:i:s'),
        'nivel_recebido'  => $nivel,
        'rpm_recebido'    => $rpm,
    ]);
}

jsonResp(['erro' => 'Método não suportado'], 405);