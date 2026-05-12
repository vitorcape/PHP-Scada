<?php
require_once __DIR__ . '/includes/auth.php';
iniciarSessao();

if (estaLogado()) { header('Location: ' . BASE_PATH . '/index.php'); exit; }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = login(trim($_POST['email'] ?? ''), $_POST['senha'] ?? '');
    if ($res['ok']) { header('Location: ' . BASE_PATH . '/index.php'); exit; }
    $erro = $res['erro'];
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — <?= APP_NAME ?></title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{--bg:#05080c;--card:#0b1520;--bd:#162130;--amber:#f59e0b;--sky:#38bdf8;--red:#f87171;--txt:#b8cce0;--mut:#364f68}
    body{background:var(--bg);color:var(--txt);font-family:system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .card{background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:36px;width:100%;max-width:380px}
    .logo{font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:var(--amber);font-weight:700;margin-bottom:10px}
    h1{font-size:22px;font-weight:600;color:#e2e8f0;margin-bottom:4px}
    .sub{font-size:13px;color:var(--mut);margin-bottom:28px}
    label{display:block;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--mut);margin-bottom:6px;margin-top:14px}
    input{width:100%;background:#070b10;border:1px solid var(--bd);border-radius:6px;padding:10px 13px;color:var(--txt);font-size:14px;outline:none;transition:border-color .2s}
    input:focus{border-color:var(--sky)}
    .erro{background:#f8717118;border:1px solid #f8717133;border-radius:6px;padding:10px 13px;color:var(--red);font-size:13px;margin-bottom:4px}
    .btn{width:100%;margin-top:22px;padding:11px;background:var(--amber);border:none;border-radius:6px;color:#050809;font-weight:700;font-size:14px;letter-spacing:.06em;cursor:pointer;transition:opacity .2s}
    .btn:hover{opacity:.88}
    .footer{text-align:center;margin-top:20px;font-size:13px;color:var(--mut)}
    .footer a{color:var(--sky);text-decoration:none}
  </style>
</head>
<body>
<div class="card">
  <div class="logo"><?= APP_NAME ?></div>
  <h1>Entrar</h1>
  <p class="sub">Sistema de supervisão</p>
  <?php if ($erro): ?>
    <div class="erro"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>
  <form method="POST" novalidate>
    <label>E-mail</label>
    <input type="email" name="email" required autocomplete="email"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    <label>Senha</label>
    <input type="password" name="senha" required autocomplete="current-password">
    <button class="btn" type="submit">Entrar</button>
  </form>
  <div class="footer">Não tem conta? <a href="<?= BASE_PATH ?>/register.php">Criar conta</a></div>
</div>
</body>
</html>
