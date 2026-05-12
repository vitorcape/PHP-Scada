<?php
require_once __DIR__ . '/../includes/middleware.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireApiKey();
    $sp = getDB()->query('SELECT * FROM setpoints WHERE id = 1')->fetch();
    if (!$sp) jsonResp(['erro' => 'Não encontrado'], 404);
    jsonResp([
        'valvula_aberta' => (bool)$sp['valvula_aberta'],
        'helice_rpm'     => (int) $sp['helice_rpm'],
        'modo'           => $sp['modo'],
        'limiar_baixo'   => (int) $sp['limiar_baixo'],
        'limiar_normal'  => (int) $sp['limiar_normal'],
        'limiar_alto'    => (int) $sp['limiar_alto'],
        'limiar_valvula' => (int) $sp['limiar_valvula'],
        'silenciar'      => (bool)$sp['silenciar'],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAdmin(apiContext: true);
    $c   = json_decode(file_get_contents('php://input'), true) ?? [];
    $uid = usuarioAtual()['id'];
    $db  = getDB();
    $at  = $db->query('SELECT * FROM setpoints WHERE id = 1')->fetch();

    $boolF = fn($k) => isset($c[$k]) ? (int)(bool)$c[$k] : (int)$at[$k];
    $intF  = fn($k,$mn,$mx) => isset($c[$k]) ? max($mn,min($mx,(int)$c[$k])) : (int)$at[$k];
    $enumF = fn($k,$vs) => in_array($c[$k]??'',$vs,true) ? $c[$k] : $at[$k];

    $db->prepare(
        'UPDATE setpoints SET valvula_aberta=?,helice_rpm=?,modo=?,
         limiar_baixo=?,limiar_normal=?,limiar_alto=?,limiar_valvula=?,
         silenciar=?,atualizado_por=?,atualizado_em=NOW() WHERE id=1'
    )->execute([
        $boolF('valvula_aberta'),
        $intF('helice_rpm',0,100),
        $enumF('modo',['manual','auto']),
        $intF('limiar_baixo',0,99),
        $intF('limiar_normal',0,99),
        $intF('limiar_alto',0,100),
        $intF('limiar_valvula',0,100),
        $boolF('silenciar'),
        $uid,
    ]);
    jsonResp(['ok' => true]);
}
jsonResp(['erro' => 'Método não suportado'], 405);
