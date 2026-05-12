<?php
// includes/nav.php — barra de navegação compartilhada
// Requer que BASE_PATH e APP_NAME já estejam definidos (via config.php)
$paginaAtual = basename($_SERVER['PHP_SELF']);
$navItens = [
    'index.php'           => ['label' => 'Dashboard',       'icon' => '▣'],
    'grafico_nivel.php'   => ['label' => 'Gráfico — Nível', 'icon' => '◈'],
    'grafico_agitador.php'=> ['label' => 'Gráfico — RPM',   'icon' => '◎'],
    'historico.php'       => ['label' => 'Histórico',        'icon' => '≡'],
];
?>
<nav class="subnav">
  <?php foreach ($navItens as $href => $item):
    $ativo = ($paginaAtual === $href); ?>
    <a href="<?= BASE_PATH ?>/<?= $href ?>"
       class="snav-item <?= $ativo ? 'snav-ativo' : '' ?>">
      <span class="snav-icon"><?= $item['icon'] ?></span>
      <?= htmlspecialchars($item['label']) ?>
    </a>
  <?php endforeach; ?>
</nav>
<style>
.subnav{
  background:#f4f5f7;
  border-bottom:1px solid #d0d4da;
  display:flex;align-items:center;
  padding:0 20px;gap:2px;
  height:38px;
}
.snav-item{
  display:inline-flex;align-items:center;gap:6px;
  font-family:'IBM Plex Sans',system-ui,sans-serif;
  font-size:12px;font-weight:500;
  padding:6px 14px;border-radius:4px;
  color:#4a5568;text-decoration:none;
  transition:all .15s;white-space:nowrap;
}
.snav-item:hover{background:#e2e6ea;color:#1a1e24}
.snav-item.snav-ativo{background:#fff;border:1px solid #d0d4da;color:#1a1e24;font-weight:600}
.snav-icon{font-size:11px;opacity:.7}
</style>
