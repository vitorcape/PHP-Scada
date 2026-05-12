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
  <title>Nível — <?= APP_NAME ?></title>
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

    /* Stats */
    .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
    .stat-card{background:var(--surface);border:1px solid var(--bd);border-radius:var(--r);padding:14px 16px;box-shadow:var(--sh)}
    .stat-label{font-family:var(--mono);font-size:9px;letter-spacing:.14em;text-transform:uppercase;color:var(--txt3);margin-bottom:6px}
    .stat-value{font-family:var(--mono);font-size:28px;font-weight:600;line-height:1}
    .stat-unit{font-size:14px;font-weight:400;color:var(--txt3);margin-left:2px}
    .stat-sub{font-size:11px;color:var(--txt3);margin-top:4px}

    /* Gráfico */
    .chart-card{background:var(--surface);border:1px solid var(--bd);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden;flex:1}
    .chart-hdr{padding:12px 16px;border-bottom:1px solid var(--bd);background:var(--bg2);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .chart-title{font-family:var(--mono);font-size:9px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--txt3)}
    .period-btns{display:flex;gap:4px}
    .pbtn{font-family:var(--mono);font-size:10px;font-weight:600;padding:4px 10px;border-radius:4px;border:1px solid var(--bd2);background:var(--bg2);color:var(--txt3);cursor:pointer;transition:all .15s;letter-spacing:.06em}
    .pbtn:hover{background:#e2e6ea;color:var(--txt)}
    .pbtn.ativo{background:var(--sky-bg);border-color:var(--sky-bd);color:var(--sky)}
    .chart-meta{display:flex;align-items:center;gap:10px;font-size:11px;color:var(--txt3)}
    .refresh-dot{width:6px;height:6px;border-radius:50%;background:var(--grn);animation:pulse 2s ease-in-out infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
    .chart-wrap{padding:16px;height:380px;position:relative}
    .chart-legend{display:flex;gap:16px;padding:0 16px 14px;flex-wrap:wrap}
    .lg-item{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--txt2)}
    .lg-line{width:24px;height:2px;border-radius:1px}
    .lg-dashed{border-top:2px dashed;height:0;border-radius:0}

    /* Thresholds info */
    .thresholds{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .thr-badge{display:inline-flex;align-items:center;gap:5px;font-family:var(--mono);font-size:10px;font-weight:600;padding:3px 9px;border-radius:3px;border:1px solid}
    .thr-alto {border-color:var(--red-bd);background:var(--red-bg);color:var(--red)}
    .thr-norm {border-color:var(--grn-bd);background:var(--grn-bg);color:var(--grn)}
    .thr-baixo{border-color:var(--amb-bd);background:var(--amb-bg);color:var(--amb)}
    .thr-dot{width:5px;height:5px;border-radius:50%;background:currentColor}
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
      <div class="stat-label">Nível atual</div>
      <div class="stat-value"><span id="s-atual">--</span><span class="stat-unit">%</span></div>
      <div class="stat-sub" id="s-estado">--</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Mínimo</div>
      <div class="stat-value" style="color:var(--amb)"><span id="s-min">--</span><span class="stat-unit" style="color:var(--amb)">%</span></div>
      <div class="stat-sub">no período</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Máximo</div>
      <div class="stat-value" style="color:var(--red)"><span id="s-max">--</span><span class="stat-unit" style="color:var(--red)">%</span></div>
      <div class="stat-sub">no período</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Média</div>
      <div class="stat-value" style="color:var(--sky)"><span id="s-media">--</span><span class="stat-unit" style="color:var(--sky)">%</span></div>
      <div class="stat-sub"><span id="s-pts">0</span> leituras</div>
    </div>
  </div>

  <!-- Gráfico -->
  <div class="chart-card">
    <div class="chart-hdr">
      <div>
        <div class="chart-title">Nível do Tanque — Histórico</div>
        <div class="thresholds" id="thresholds" style="margin-top:6px">
          <span class="thr-badge thr-alto"><span class="thr-dot"></span>Alto: <span id="lim-alto">--</span>%</span>
          <span class="thr-badge thr-norm"><span class="thr-dot"></span>Normal: <span id="lim-norm">--</span>%</span>
          <span class="thr-badge thr-baixo"><span class="thr-dot"></span>Baixo: <span id="lim-baixo">--</span>%</span>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
        <div class="period-btns">
          <button class="pbtn ativo" onclick="setPeriodo(30,this)">30 min</button>
          <button class="pbtn"       onclick="setPeriodo(60,this)">1 h</button>
          <button class="pbtn"       onclick="setPeriodo(360,this)">6 h</button>
          <button class="pbtn"       onclick="setPeriodo(1440,this)">24 h</button>
        </div>
        <div class="chart-meta">
          <span class="refresh-dot"></span>
          Atualiza em <span id="countdown">5</span>s
          <span id="last-upd" style="margin-left:4px">--:--:--</span>
        </div>
      </div>
    </div>
    <div class="chart-wrap">
      <canvas id="chart"></canvas>
    </div>
    <div class="chart-legend">
      <div class="lg-item"><div class="lg-line" style="background:#1d5fa8"></div>Nível (%)</div>
      <div class="lg-item"><div class="lg-dashed" style="border-color:#c0392b;width:24px"></div>Alarme alto</div>
      <div class="lg-item"><div class="lg-dashed" style="border-color:#b45309;width:24px"></div>Alarme baixo</div>
    </div>
  </div>

</div>

<script>
const BASE = '<?= BASE_PATH ?>';
let periodo = 30;
let limAlto = 80, limBaixo = 20, limNormal = 40;

// ── Chart.js setup ─────────────────────────────────────────────
const ctx = document.getElementById('chart').getContext('2d');
const chart = new Chart(ctx, {
  type: 'line',
  data: { labels: [], datasets: [{
    label: 'Nível (%)',
    data: [],
    borderColor: '#1d5fa8',
    backgroundColor: 'rgba(29,95,168,.07)',
    borderWidth: 2,
    pointRadius: 0,
    pointHoverRadius: 4,
    tension: 0.3,
    fill: true,
  }]},
  options: {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 300 },
    interaction: { intersect: false, mode: 'index' },
    plugins: {
      legend: { display: false },
      annotation: {},   // preenchido via update
      tooltip: {
        backgroundColor: '#1a1e24',
        titleColor: '#b8cce0',
        bodyColor: '#fff',
        padding: 10,
        callbacks: {
          label: ctx => ` ${ctx.parsed.y.toFixed(1)}%`
        }
      }
    },
    scales: {
      x: {
        ticks: {
          font: { family: "'IBM Plex Mono'", size: 10 },
          color: '#8a97a8',
          maxTicksLimit: 10,
          maxRotation: 0,
        },
        grid: { color: '#eaecef' }
      },
      y: {
        min: 0, max: 100,
        ticks: {
          font: { family: "'IBM Plex Mono'", size: 10 },
          color: '#8a97a8',
          callback: v => v + '%',
          stepSize: 20,
        },
        grid: { color: '#eaecef' }
      }
    }
  }
});

// ── Linhas de threshold ────────────────────────────────────────
function atualizarThresholds(alto, baixo) {
  // Remove datasets de threshold antigos, mantém o primeiro (nível)
  chart.data.datasets = [chart.data.datasets[0],
    {
      label: 'Alarme alto',
      data: Array(chart.data.labels.length).fill(alto),
      borderColor: 'rgba(192,57,43,.6)',
      borderWidth: 1.5,
      borderDash: [5,4],
      pointRadius: 0,
      fill: false,
      tension: 0,
    },
    {
      label: 'Alarme baixo',
      data: Array(chart.data.labels.length).fill(baixo),
      borderColor: 'rgba(180,83,9,.6)',
      borderWidth: 1.5,
      borderDash: [5,4],
      pointRadius: 0,
      fill: false,
      tension: 0,
    }
  ];
}

// ── Fetch dados ────────────────────────────────────────────────
async function fetchGrafico() {
  try {
    const r = await fetch(`${BASE}/api/grafico.php?tipo=nivel&minutos=${periodo}`);
    if (!r.ok) return;
    const d = await r.json();

    chart.data.labels          = d.labels;
    chart.data.datasets[0].data = d.values;

    if (d.sp) {
      limAlto   = d.sp.limiar_alto;
      limBaixo  = d.sp.limiar_baixo;
      limNormal = d.sp.limiar_normal;
      document.getElementById('lim-alto' ).textContent = limAlto;
      document.getElementById('lim-baixo').textContent = limBaixo;
      document.getElementById('lim-norm' ).textContent = limNormal;
    }

    atualizarThresholds(limAlto, limBaixo);
    chart.update();

    // Stats
    if (d.stats) {
      const s = d.stats;
      document.getElementById('s-atual').textContent = s.atual ?? '--';
      document.getElementById('s-min'  ).textContent = s.min   ?? '--';
      document.getElementById('s-max'  ).textContent = s.max   ?? '--';
      document.getElementById('s-media').textContent = s.media ?? '--';
      document.getElementById('s-pts'  ).textContent = s.pontos ?? 0;

      const nv = parseFloat(s.atual);
      const estado = nv >= limAlto ? '▲ NÍVEL ALTO' : nv <= limBaixo ? '▼ NÍVEL BAIXO' : '● NORMAL';
      const cor    = nv >= limAlto ? 'var(--red)' : nv <= limBaixo ? 'var(--amb)' : 'var(--grn)';
      const el = document.getElementById('s-estado');
      el.textContent = estado; el.style.color = cor;
    }

    document.getElementById('last-upd').textContent = new Date().toLocaleTimeString('pt-BR');
  } catch (e) { console.error(e); }
}

function setPeriodo(min, btn) {
  periodo = min;
  document.querySelectorAll('.pbtn').forEach(b => b.classList.remove('ativo'));
  btn.classList.add('ativo');
  fetchGrafico();
}

// ── Countdown + auto-refresh ───────────────────────────────────
let cd = 5;
setInterval(() => {
  cd--;
  document.getElementById('countdown').textContent = cd;
  if (cd <= 0) { cd = 5; fetchGrafico(); }
}, 1000);

setInterval(() => {
  document.getElementById('ts-clk').textContent = new Date().toLocaleTimeString('pt-BR');
}, 1000);

fetchGrafico();
</script>
</body>
</html>
