<?php
require_once __DIR__ . '/includes/middleware.php';
requireLogin();
$usuario = usuarioAtual();
$isAdmin = ehAdmin();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Agitador — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#eaecef;--bg2:#f4f5f7;--surface:#fff;
      --bd:#d0d4da;--bd2:#b8bec7;
      --txt:#1a1e24;--txt2:#4a5568;--txt3:#8a97a8;
      --mono:'IBM Plex Mono',monospace;--sans:'IBM Plex Sans',sans-serif;
      --red:#c0392b;--red-bg:#fdf2f1;--red-bd:#e8b4b0;
      --grn:#1a7a45;--grn-bg:#f0faf4;--grn-bd:#a8dbbe;
      --amb:#b45309;--amb-bg:#fdf8f0;--amb-bd:#e8d0a0;
      --sky:#1d5fa8;--sky-bg:#f0f5fd;--sky-bd:#a8c4e8;
      --prp:#5b21b6;--prp-bg:#f5f3ff;--prp-bd:#c4b5fd;
      --r:6px;--sh:0 1px 3px rgba(0,0,0,.08)
    }
    body{background:var(--bg);color:var(--txt);font-family:var(--sans);font-size:13px;min-height:100vh}
    .topbar{background:var(--surface);border-bottom:2px solid var(--bd2);height:52px;display:flex;align-items:center;justify-content:space-between;padding:0 20px;position:sticky;top:0;z-index:200;box-shadow:var(--sh)}
    .tb-left{display:flex;align-items:center;gap:12px}
    .app-id{font-family:var(--mono);font-size:10px;font-weight:600;letter-spacing:.14em;color:var(--txt3);text-transform:uppercase;border-right:1px solid var(--bd);padding-right:12px}
    .app-name{font-size:15px;font-weight:600}
    .tb-right{display:flex;align-items:center;gap:10px}
    .pill{display:inline-flex;align-items:center;gap:4px;font-family:var(--mono);font-size:9px;font-weight:600;letter-spacing:.08em;padding:3px 8px;border-radius:3px;border:1px solid}
    .pill-amb{border-color:var(--amb-bd);background:var(--amb-bg);color:var(--amb)}
    .pill-sky{border-color:var(--sky-bd);background:var(--sky-bg);color:var(--sky)}
    #ts-clk{font-family:var(--mono);font-size:11px;color:var(--txt3);border-left:1px solid var(--bd);padding-left:10px}
    .btn-logout{font-family:var(--sans);font-size:12px;font-weight:500;padding:5px 12px;border-radius:var(--r);border:1px solid var(--bd2);background:var(--bg2);color:var(--txt2);cursor:pointer;transition:all .15s}
    .btn-logout:hover{border-color:var(--red-bd);color:var(--red);background:var(--red-bg)}
    .page{padding:16px 20px;display:flex;flex-direction:column;gap:14px}
    .stats-row{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}
    .stat-card{background:var(--surface);border:1px solid var(--bd);border-radius:var(--r);padding:14px 16px;box-shadow:var(--sh)}
    .stat-label{font-family:var(--mono);font-size:9px;letter-spacing:.14em;text-transform:uppercase;color:var(--txt3);margin-bottom:6px}
    .stat-value{font-family:var(--mono);font-size:28px;font-weight:600;line-height:1}
    .stat-unit{font-size:14px;font-weight:400;color:var(--txt3);margin-left:2px}
    .stat-sub{font-size:11px;color:var(--txt3);margin-top:4px}
    .chart-card{background:var(--surface);border:1px solid var(--bd);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden}
    .chart-hdr{padding:12px 16px;border-bottom:1px solid var(--bd);background:var(--bg2);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .chart-title{font-family:var(--mono);font-size:9px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--txt3)}
    .chart-sub{font-size:11px;color:var(--txt3);margin-top:3px}
    .period-btns{display:flex;gap:4px}
    .pbtn{font-family:var(--mono);font-size:10px;font-weight:600;padding:4px 10px;border-radius:4px;border:1px solid var(--bd2);background:var(--bg2);color:var(--txt3);cursor:pointer;transition:all .15s;letter-spacing:.06em}
    .pbtn:hover{background:#e2e6ea;color:var(--txt)}
    .pbtn.ativo{background:var(--prp-bg);border-color:var(--prp-bd);color:var(--prp)}
    .chart-meta{display:flex;align-items:center;gap:8px;font-size:11px;color:var(--txt3);margin-top:6px}
    .refresh-dot{width:6px;height:6px;border-radius:50%;background:var(--grn);animation:pulse 2s ease-in-out infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
    .chart-wrap{padding:16px;height:380px}
    .chart-legend{display:flex;gap:16px;padding:0 16px 14px;flex-wrap:wrap}
    .lg-item{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--txt2)}
    .lg-line{width:24px;height:2px;border-radius:1px}

    /* Painel de zonas de velocidade */
    .zonas{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
    .zona{border:1px solid var(--bd);border-radius:var(--r);padding:10px 12px;background:var(--bg2)}
    .zona-label{font-family:var(--mono);font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:var(--txt3);margin-bottom:4px}
    .zona-range{font-family:var(--mono);font-size:12px;font-weight:600}
  </style>
</head>
<body>

<header class="topbar">
  <div class="tb-left">
    <span class="app-id">T-01</span>
    <span class="app-name"><?= APP_NAME ?></span>
  </div>
  <div class="tb-right">
    <span id="ts-clk">--:--:--</span>
    <span class="pill <?= $isAdmin ? 'pill-amb' : 'pill-sky' ?>"><?= $isAdmin ? 'ADMIN' : 'VISITOR' ?></span>
    <span style="font-size:12px;color:var(--txt2)"><?= htmlspecialchars($usuario['nome']) ?></span>
    <form action="<?= BASE_PATH ?>/logout.php" method="POST">
      <button class="btn-logout" type="submit">Sair</button>
    </form>
  </div>
</header>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page">

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-label">RPM atual</div>
      <div class="stat-value" style="color:var(--prp)"><span id="s-atual">--</span><span class="stat-unit" style="color:var(--prp)">%</span></div>
      <div class="stat-sub" id="s-zona">--</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Mínimo</div>
      <div class="stat-value"><span id="s-min">--</span><span class="stat-unit">%</span></div>
      <div class="stat-sub">no período</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Máximo</div>
      <div class="stat-value"><span id="s-max">--</span><span class="stat-unit">%</span></div>
      <div class="stat-sub">no período</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Média</div>
      <div class="stat-value"><span id="s-media">--</span><span class="stat-unit">%</span></div>
      <div class="stat-sub">no período</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Tempo em movimento</div>
      <div class="stat-value"><span id="s-pct-mov">--</span><span class="stat-unit">%</span></div>
      <div class="stat-sub"><span id="s-pts">0</span> leituras</div>
    </div>
  </div>

  <!-- Gráfico -->
  <div class="chart-card">
    <div class="chart-hdr">
      <div>
        <div class="chart-title">Velocidade do Agitador — Histórico</div>
        <div class="chart-sub">Servo 2 · Pino 27 · Rotação contínua simulada</div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
        <div class="period-btns">
          <button class="pbtn ativo" onclick="setPeriodo(30,this)">30 min</button>
          <button class="pbtn"       onclick="setPeriodo(60,this)">1 h</button>
          <button class="pbtn"       onclick="setPeriodo(360,this)">6 h</button>
          <button class="pbtn"       onclick="setPeriodo(1440,this)">24 h</button>
        </div>
        <div class="chart-meta">
          <span class="refresh-dot"></span>
          Atualiza em <span id="countdown">5</span>s &nbsp;
          <span id="last-upd">--:--:--</span>
        </div>
      </div>
    </div>
    <div class="chart-wrap">
      <canvas id="chart"></canvas>
    </div>
    <div class="chart-legend">
      <div class="lg-item"><div class="lg-line" style="background:#5b21b6"></div>Velocidade (%)</div>
    </div>
  </div>

  <!-- Zonas de velocidade -->
  <div class="zonas">
    <div class="zona">
      <div class="zona-label">Parado</div>
      <div class="zona-range" style="color:var(--txt3)">0%</div>
    </div>
    <div class="zona">
      <div class="zona-label">Baixa velocidade</div>
      <div class="zona-range" style="color:var(--sky)">1% – 39%</div>
    </div>
    <div class="zona">
      <div class="zona-label">Velocidade média</div>
      <div class="zona-range" style="color:var(--amb)">40% – 69%</div>
    </div>
    <div class="zona">
      <div class="zona-label">Alta velocidade</div>
      <div class="zona-range" style="color:var(--red)">70% – 100%</div>
    </div>
  </div>

</div>

<script>
const BASE = '<?= BASE_PATH ?>';
let periodo = 30;

const ctx = document.getElementById('chart').getContext('2d');
const chart = new Chart(ctx, {
  type: 'line',
  data: { labels: [], datasets: [{
    label: 'RPM (%)',
    data: [],
    borderColor: '#5b21b6',
    backgroundColor: 'rgba(91,33,182,.06)',
    borderWidth: 2,
    pointRadius: 0,
    pointHoverRadius: 4,
    tension: 0.25,
    fill: true,
    stepped: 'before',  // RPM muda em degraus
  }]},
  options: {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 300 },
    interaction: { intersect: false, mode: 'index' },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1a1e24',
        titleColor: '#b8cce0',
        bodyColor: '#fff',
        padding: 10,
        callbacks: {
          label: ctx => {
            const v = ctx.parsed.y;
            const zona = v === 0 ? 'PARADO' : v < 40 ? 'BAIXA' : v < 70 ? 'MÉDIA' : 'ALTA';
            return ` ${v}%  [${zona}]`;
          }
        }
      }
    },
    scales: {
      x: {
        ticks: { font:{ family:"'IBM Plex Mono'",size:10 }, color:'#8a97a8', maxTicksLimit:10, maxRotation:0 },
        grid: { color:'#eaecef' }
      },
      y: {
        min: 0, max: 100,
        ticks: { font:{ family:"'IBM Plex Mono'",size:10 }, color:'#8a97a8', callback: v=>v+'%', stepSize:20 },
        grid: { color:'#eaecef' }
      }
    }
  }
});

async function fetchGrafico() {
  try {
    const r = await fetch(`${BASE}/api/grafico.php?tipo=agitador&minutos=${periodo}`);
    if (!r.ok) return;
    const d = await r.json();

    chart.data.labels           = d.labels;
    chart.data.datasets[0].data = d.values;
    chart.update();

    if (d.stats) {
      const s = d.stats;
      document.getElementById('s-atual').textContent = s.atual ?? '--';
      document.getElementById('s-min'  ).textContent = s.min   ?? '--';
      document.getElementById('s-max'  ).textContent = s.max   ?? '--';
      document.getElementById('s-media').textContent = s.media ?? '--';
      document.getElementById('s-pts'  ).textContent = s.pontos ?? 0;

      // % do tempo em movimento
      const moving = d.values.filter(v => v > 0).length;
      const pct    = d.values.length ? Math.round(moving / d.values.length * 100) : 0;
      document.getElementById('s-pct-mov').textContent = pct;

      const v = parseFloat(s.atual);
      const zona = v === 0 ? 'PARADO' : v < 40 ? 'BAIXA VELOCIDADE' : v < 70 ? 'VELOCIDADE MÉDIA' : 'ALTA VELOCIDADE';
      const cor  = v === 0 ? 'var(--txt3)' : v < 40 ? 'var(--sky)' : v < 70 ? 'var(--amb)' : 'var(--red)';
      const el = document.getElementById('s-zona');
      el.textContent = zona; el.style.color = cor;
    }

    document.getElementById('last-upd').textContent = new Date().toLocaleTimeString('pt-BR');
  } catch(e) { console.error(e); }
}

function setPeriodo(min, btn) {
  periodo = min;
  document.querySelectorAll('.pbtn').forEach(b => b.classList.remove('ativo'));
  btn.classList.add('ativo');
  fetchGrafico();
}

let cd = 5;
setInterval(() => {
  cd--; document.getElementById('countdown').textContent = cd;
  if (cd <= 0) { cd = 5; fetchGrafico(); }
}, 1000);
setInterval(() => document.getElementById('ts-clk').textContent = new Date().toLocaleTimeString('pt-BR'), 1000);
fetchGrafico();
</script>
</body>
</html>
