<?php
/**
 * admin.php — Gerenciamento de usuários (somente admin)
 */
require_once __DIR__ . '/includes/middleware.php';
requireAdmin();

$usuarioLogado = usuarioAtual();
$msg           = null;

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao   = $_POST['acao']    ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId === $usuarioLogado['id']) {
        $msg = ['tipo' => 'erro', 'texto' => 'Você não pode alterar sua própria conta por aqui.'];
    } elseif ($acao === 'role' && isset($_POST['nova_role'])) {
        alterarRole($userId, $_POST['nova_role'])
            ? $msg = ['tipo' => 'ok',   'texto' => 'Role atualizada com sucesso.']
            : $msg = ['tipo' => 'erro', 'texto' => 'Role inválida.'];
    } elseif ($acao === 'toggle_ativo' && isset($_POST['ativo'])) {
        alterarAtivo($userId, (bool)(int) $_POST['ativo']);
        $msg = ['tipo' => 'ok', 'texto' => 'Status atualizado.'];
    }
}

$usuarios = listarUsuarios();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Usuários — <?= APP_NAME ?></title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{--bg:#05080c;--card:#0b1520;--bd:#162130;--amber:#f59e0b;--sky:#38bdf8;--grn:#4ade80;--red:#f87171;--txt:#b8cce0;--mut:#364f68}
    body{background:var(--bg);color:var(--txt);font-family:system-ui,sans-serif;font-size:13px;padding:16px}
    .hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--bd)}
    .title{font-size:14px;font-weight:700;letter-spacing:.12em;color:var(--amber);text-transform:uppercase}
    .back{font-size:12px;color:var(--sky);text-decoration:none}
    .card{background:var(--card);border:1px solid var(--bd);border-radius:8px;padding:14px;max-width:820px}
    .lbl{font-size:9px;letter-spacing:.15em;text-transform:uppercase;color:var(--mut);margin-bottom:10px}
    .msg{padding:10px 14px;border-radius:7px;font-size:13px;margin-bottom:14px;max-width:820px}
    .msg-ok{background:#4ade8015;border:1px solid #4ade8033;color:var(--grn)}
    .msg-erro{background:#f8717115;border:1px solid #f8717133;color:var(--red)}
    table{width:100%;border-collapse:collapse;font-size:12px}
    th{text-align:left;color:var(--mut);font-weight:500;padding:6px 10px;border-bottom:1px solid var(--bd);font-size:10px;letter-spacing:.1em}
    td{padding:8px 10px;border-bottom:1px solid #0f1c2a;vertical-align:middle}
    tr:last-child td{border-bottom:none}
    tr:hover td{background:#0d1826}
    .badge-admin{background:#f59e0b18;border:1px solid #f59e0b44;color:var(--amber);padding:2px 8px;border-radius:99px;font-size:10px;font-weight:600}
    .badge-visitor{background:#38bdf818;border:1px solid #38bdf844;color:var(--sky);padding:2px 8px;border-radius:99px;font-size:10px;font-weight:600}
    .badge-inativo{background:#f8717118;border:1px solid #f8717133;color:var(--red);padding:2px 8px;border-radius:99px;font-size:10px}
    select{background:#070b10;border:1px solid var(--bd);border-radius:5px;padding:4px 8px;color:var(--txt);font-size:12px;outline:none}
    .btn-sm{padding:4px 10px;border-radius:5px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid;transition:all .2s;letter-spacing:.04em}
    .btn-apply{background:#f59e0b20;border-color:var(--amber);color:var(--amber)}
    .btn-apply:hover{background:#f59e0b35}
    .btn-toggle-off{background:#f8717115;border-color:var(--red);color:var(--red)}
    .btn-toggle-on{background:#4ade8015;border-color:var(--grn);color:var(--grn)}
    .btn-toggle-off:hover{background:#f8717130}
    .btn-toggle-on:hover{background:#4ade8030}
    .self{color:var(--mut);font-size:11px}
    form{display:inline}
  </style>
</head>
<body>

<div class="hdr">
  <span class="title"><?= APP_NAME ?> — Usuários</span>
  <a class="back" href="<?= BASE_PATH ?>/index.php">← Voltar ao dashboard</a>
</div>

<?php if ($msg): ?>
  <div class="msg <?= $msg['tipo'] === 'ok' ? 'msg-ok' : 'msg-erro' ?>">
    <?= htmlspecialchars($msg['texto']) ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="lbl">Usuários cadastrados (<?= count($usuarios) ?>)</div>
  <table>
    <thead>
      <tr>
        <th>#</th><th>Nome</th><th>E-mail</th><th>Role</th>
        <th>Status</th><th>Cadastro</th><th>Último login</th><th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
      <tr>
        <td style="color:var(--mut)"><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['nome']) ?>
          <?php if ($u['id'] === $usuarioLogado['id']): ?>
            <span class="self">(você)</span>
          <?php endif; ?>
        </td>
        <td style="color:var(--mut)"><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <span class="<?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-visitor' ?>">
            <?= strtoupper($u['role']) ?>
          </span>
        </td>
        <td>
          <?php if ($u['ativo']): ?>
            <span style="color:var(--grn);font-size:11px">● Ativo</span>
          <?php else: ?>
            <span class="badge-inativo">INATIVO</span>
          <?php endif; ?>
        </td>
        <td style="color:var(--mut)"><?= $u['criado_em'] ? substr($u['criado_em'], 0, 10) : '--' ?></td>
        <td style="color:var(--mut)"><?= $u['ultimo_login'] ? substr($u['ultimo_login'], 11, 8) . ' ' . substr($u['ultimo_login'], 0, 10) : '--' ?></td>
        <td>
          <?php if ($u['id'] !== $usuarioLogado['id']): ?>
            <!-- Alterar role -->
            <form method="POST">
              <input type="hidden" name="acao"    value="role">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="nova_role">
                <option value="visitor" <?= $u['role'] === 'visitor' ? 'selected' : '' ?>>visitor</option>
                <option value="admin"   <?= $u['role'] === 'admin'   ? 'selected' : '' ?>>admin</option>
              </select>
              <button class="btn-sm btn-apply" type="submit">Salvar</button>
            </form>
            &nbsp;
            <!-- Ativar / Desativar -->
            <form method="POST">
              <input type="hidden" name="acao"    value="toggle_ativo">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <input type="hidden" name="ativo"   value="<?= $u['ativo'] ? '0' : '1' ?>">
              <button class="btn-sm <?= $u['ativo'] ? 'btn-toggle-off' : 'btn-toggle-on' ?>" type="submit">
                <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
              </button>
            </form>
          <?php else: ?>
            <span class="self">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
