<?php
require_once __DIR__ . '/includes/middleware.php';
requireLogin();
$usuario = usuarioAtual();
$isAdmin = ehAdmin();

// Limiares para colorir a tabela (server-side para primeiro render)
$db   = getDB();
$sp0  = $db->query('SELECT limiar_baixo, limiar_alto FROM setpoints WHERE id = 1')->fetch();
$limB = (int)($sp0['limiar_baixo'] ?? 20);
$limA = (int)($sp0['limiar_alto']  ?? 80);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Histórico — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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

    .page{padding:16px 20px;display:grid;grid-template-columns:1fr 380px;gap:14px;align-items:start}

    .card{background:var(--surface);border:1px solid var(--bd);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden}
    .card-hdr{padding:11px 16px;border-bottom:1px solid var(--bd);background:var(--bg2);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
    .card-title{font-family:var(--mono);font-size:9px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--txt3)}

    .count-badge{font-family:var(--mono);font-size:10px;font-weight:600;padding:3px 9px;border-radius:3px;border:1px solid var(--bd2);background:var(--bg2);color:var(--txt2)}

    .filtros{display:flex;gap:6px;align-items:center;flex-wrap:wrap;padding:10px 16px;background:var(--bg2);border-bottom:1px solid var(--bd)}
    .fbtn{font-family:var(--mono);font-size:10px;font-weight:600;padding:4px 10px;border-radius:3px;border:1px solid var(--bd2);background:var(--surface);color:var(--txt3);cursor:pointer;transition:all .15s;letter-spacing:.05em}
    .fbtn:hover{background:#e2e6ea}
    .fbtn.ativo-all {background:var(--txt);border-color:var(--txt);color:#fff}
    .fbtn.ativo-alto {background:var(--red-bg);border-color:var(--red-bd);color:var(--red)}
    .fbtn.ativo-norm {background:var(--grn-bg);border-color:var(--grn-bd);color:var(--grn)}
    .fbtn.ativo-baixo{background:var(--amb-bg);border-color:var(--amb-bd);color:var(--amb)}
    .filtros-label{font-size:11px;color:var(--txt3);font-family:var(--mono)}

    /* Seletor de período */
    .periodo-row{display:flex;gap:4px;padding:10px 16px;background:var(--bg2);border-bottom:1px solid var(--bd);align-items:center}
    .periodo-label{font-family:var(--mono);font-size:10px;color:var(--txt3);margin-right:4px}
    .pbtn{font-family:var(--mono);font-size:10px;font-weight:600;padding:4px 10px;border-radius:3px;border:1px solid var(--bd2);background:var(--surface);color:var(--txt3);cursor:pointer;transition:all .15s}
    .pbtn:hover{background:#e2e6ea}
    .pbtn.ativo{background:var(--sky-bg);border-color:var(--sky-bd);color:var(--sky)}

    .tbl{width:100%;border-collapse:collapse;font-size:12px}
    .tbl th{font-family:var(--mono);font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:var(--txt3);padding:8px 14px;border-bottom:1px solid var(--bd);background:var(--bg2);font-weight:600;text-align:left;white-space:nowrap}
    .tbl td{padding:7px 14px;border-bottom:1px solid var(--bg);font-family:var(--mono);font-size:11px;color:var(--txt2)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--bg2)}
    .t-time{color:var(--txt3)!important}
    .t-hi {color:var(--red)!important;font-weight:600}
    .t-lo {color:var(--amb)!important;font-weight:600}

    /* Válvula pill */
    .valv-open {color:var(--grn);font-weight:600}
    .valv-close{color:var(--red);font-weight:600}

    /* Paginação */
    .pag{display:flex;align-items:center;gap:6px;padding:10px 16px;border-top:1px solid var(--bd);background:var(--bg2)}
    .pag-btn{font-family:var(--mono);font-size:11px;padding:4px 10px;border-radius:4px;border:1px solid var(--bd2);background:var(--surface);color:var(--txt2);cursor:pointer;transition:all .15s}
    .pag-btn:hover:not(:disabled){background:#e2e6ea}
    .pag-btn:disabled{opacity:.4;cursor:not-allowed}
    .pag-info{font-family:var(--mono);font-size:11px;color:var(--txt3);flex:1;text-align:center}

    /* Timeline alarmes */
    .alm-resumo{display:grid;grid-template-columns:repeat(3,1fr);border-bottom:1px solid var(--bd)}
    .alm-res-item{padding:12px 14px;text-align:center;border-right:1px solid var(--bd)}
    .alm-res-item:last-child{border-right:none}
    .alm-res-count{font-family:var(--mono);font-size:22px;font-weight:600}
    .alm-res-label{font-size:10px;color:var(--txt3);margin-top:2px;letter-spacing:.05em}

    .timeline{padding:0}
    .tl-item{display:flex;gap:12px;padding:11px 16px;border-bottom:1px solid var(--bg);transition:background .15s}
    .tl-item:hover{background:var(--bg2)}
    .tl-item:last-child{border-bottom:none}
    .tl-icon{flex-shrink:0;width:28px;height:28px;border-radius:50%;border:1.5px solid;display:flex;align-items:center;justify-content:center;font-size:12px;margin-top:1px}
    .tl-icon-alto {border-color:var(--red-bd);background:var(--red-bg)}
    .tl-icon-norm {border-color:var(--grn-bd);background:var(--grn-bg)}
    .tl-icon-baixo{border-color:var(--amb-bd);background:var(--amb-bg)}
    .tl-body{flex:1}
    .tl-titulo{font-size:12px;font-weight:600;margin-bottom:2px}
    .tl-alto {color:var(--red)}
    .tl-norm {color:var(--grn)}
    .tl-baixo{color:var(--amb)}
    .tl-meta{font-family:var(--mono);font-size:10px;color:var(--txt3)}

    .refresh-dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--grn);animation:pulse 2s ease-in-out infinite;vertical-align:middle;margin-right:4px}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
    .empty-msg{color:var(--txt3);text-align:center;padding:24px;font-size:12px}
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

  <!-- COL 1: Telemetria ────────────────────────────────────────── -->
  <div class="card">
    <div class="card-hdr">
      <span class="card-title">Histórico de Telemetria</span>
      <div style="display:flex;align-items:center;gap:8px">
        <span class="refresh-dot"></span>
        <span style="font-size:11px;color:var(--txt3)">Atualiza em <span id="countdown-t">10</span>s</span>
        <span id="tel-count" class="count-badge">—</span>
      </div>
    </div>

    <!-- Seletor de período -->
    <div class="periodo-row">
      <span class="periodo-label">Período:</span>
      <button class="pbtn ativo" onclick="setTelPeriodo(30,this)">30 min</button>
      <button class="pbtn"       onclick="setTelPeriodo(60,this)">1 h</button>
      <button class="pbtn"       onclick="setTelPeriodo(360,this)">6 h</button>
      <button class="pbtn"       onclick="setTelPeriodo(1440,this)">24 h</button>
    </div>

    <div style="overflow-x:auto">
      <table class="tbl">
        <thead>
          <tr>
            <th>Data / Hora</th>
            <th>Nível</th>
            <th>Válvula</th>
            <th>Hélice</th>
          </tr>
        </thead>
        <tbody id="tel-tbody">
          <tr><td colspan="4" class="empty-msg">Carregando…</td></tr>
        </tbody>
      </table>
    </div>

    <div class="pag">
      <button class="pag-btn" id="tel-prev" onclick="telPagina(-1)" disabled>← Anterior</button>
      <span   class="pag-info" id="tel-pag-info">—</span>
      <button class="pag-btn" id="tel-next" onclick="telPagina(+1)" disabled>Próxima →</button>
    </div>
  </div>

  <!-- COL 2: Alarmes ───────────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:14px">

    <!-- Resumo -->
    <div class="card">
      <div class="card-hdr"><span class="card-title">Resumo de Alarmes</span></div>
      <div class="alm-resumo">
        <div class="alm-res-item">
          <div id="cnt-alto"  class="alm-res-count" style="color:var(--red)">—</div>
          <div class="alm-res-label" style="color:var(--red)">ALTO</div>
        </div>
        <div class="alm-res-item">
          <div id="cnt-norm"  class="alm-res-count" style="color:var(--grn)">—</div>
          <div class="alm-res-label" style="color:var(--grn)">NORMAL</div>
        </div>
        <div class="alm-res-item">
          <div id="cnt-baixo" class="alm-res-count" style="color:var(--amb)">—</div>
          <div class="alm-res-label" style="color:var(--amb)">BAIXO</div>
        </div>
      </div>
    </div>

    <!-- Timeline -->
    <div class="card">
      <div class="card-hdr">
        <span class="card-title">Histórico de Alarmes</span>
        <span id="alm-count" class="count-badge">—</span>
      </div>

      <div class="filtros">
        <span class="filtros-label">Filtro:</span>
        <button class="fbtn ativo-all" onclick="setFiltroAlm('',this)">Todos</button>
        <button class="fbtn" onclick="setFiltroAlm('alto',this)"  >Alto</button>
        <button class="fbtn" onclick="setFiltroAlm('normal',this)">Normal</button>
        <button class="fbtn" onclick="setFiltroAlm('baixo',this)" >Baixo</button>
      </div>

      <div id="alm-timeline" class="timeline">
        <div class="empty-msg">Carregando…</div>
      </div>

      <div class="pag">
        <button class="pag-btn" id="alm-prev" onclick="almPagina(-1)" disabled>← Anterior</button>
        <span   class="pag-info" id="alm-pag-info">—</span>
        <button class="pag-btn" id="alm-next" onclick="almPagina(+1)" disabled>Próxima →</button>
      </div>
    </div>

  </div>
</div>

<script>
const BASE    = '<?= BASE_PATH ?>';
let limBaixo  = <?= $limB ?>;
let limAlto   = <?= $limA ?>;

// ── Telemetria ─────────────────────────────────────────────────
let telPag     = 1;
const TEL_LIM  = 20;
let telTotal   = 0;
let telMinutos = 30;

async function fetchTelemetria() {
  try {
    const r = await fetch(
      `${BASE}/api/historico_telemetria.php?pagina=${telPag}&limite=${TEL_LIM}&minutos=${telMinutos}`
    );
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    const d = await r.json();

    telTotal = d.total;
    document.getElementById('tel-count').textContent = `${telTotal} registros`;

    const body = document.getElementById('tel-tbody');

    if (!d.registros || !d.registros.length) {
      body.innerHTML = '<tr><td colspan="4" class="empty-msg">Nenhum dado no período</td></tr>';
    } else {
      body.innerHTML = d.registros.map(r => {
        const nv   = parseFloat(r.nivel_pct);
        const cls  = nv >= limAlto ? 't-hi' : nv <= limBaixo ? 't-lo' : '';
        const valv = parseInt(r.valvula_aberta);
        const rpm  = parseInt(r.helice_rpm);
        const ts   = r.ts ? r.ts.replace('T',' ').slice(0,19) : '--';
        return `<tr>
          <td class="t-time">${ts}</td>
          <td class="${cls}">${nv.toFixed(1)}%</td>
          <td class="${valv ? 'valv-open' : 'valv-close'}">${valv ? 'ABERTA' : 'FECHADA'}</td>
          <td>${rpm}%</td>
        </tr>`;
      }).join('');
    }

    const totalPags = Math.max(1, Math.ceil(telTotal / TEL_LIM));
    document.getElementById('tel-pag-info').textContent = `Pág ${telPag} / ${totalPags}`;
    document.getElementById('tel-prev').disabled = telPag <= 1;
    document.getElementById('tel-next').disabled = telPag >= totalPags;

  } catch(e) {
    console.error('[TEL]', e);
    document.getElementById('tel-tbody').innerHTML =
      `<tr><td colspan="4" class="empty-msg">Erro ao carregar: ${e.message}</td></tr>`;
  }
}

function telPagina(dir) {
  const totalPags = Math.max(1, Math.ceil(telTotal / TEL_LIM));
  telPag = Math.max(1, Math.min(totalPags, telPag + dir));
  fetchTelemetria();
}

function setTelPeriodo(min, btn) {
  telMinutos = min; telPag = 1;
  document.querySelectorAll('.pbtn').forEach(b => b.classList.remove('ativo'));
  btn.classList.add('ativo');
  fetchTelemetria();
}

// ── Alarmes ────────────────────────────────────────────────────
let almPag    = 1;
const ALM_LIM = 15;
let almTotal  = 0;
let almFiltro = '';

async function fetchAlarmes() {
  try {
    const fp = almFiltro ? `&tipo=${almFiltro}` : '';
    const r  = await fetch(
      `${BASE}/api/alarmes.php?pagina=${almPag}&limite=${ALM_LIM}${fp}`
    );
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    const d = await r.json();

    almTotal = d.total;
    document.getElementById('alm-count').textContent = `${almTotal} eventos`;

    const tl = document.getElementById('alm-timeline');
    if (!d.alarmes || !d.alarmes.length) {
      tl.innerHTML = '<div class="empty-msg">Nenhum alarme registrado</div>';
    } else {
      const cfg = {
        alto:   { icon:'🔴', cls:'tl-alto',  icls:'tl-icon-alto',  txt:'Alarme — Nível Alto'  },
        normal: { icon:'🟢', cls:'tl-norm',  icls:'tl-icon-norm',  txt:'Normalizado'            },
        baixo:  { icon:'🟡', cls:'tl-baixo', icls:'tl-icon-baixo', txt:'Alerta — Nível Baixo' },
      };
      tl.innerHTML = d.alarmes.map(a => {
        const c  = cfg[a.tipo] || { icon:'❔', cls:'', icls:'', txt: a.tipo };
        const ts = a.ts ? a.ts.replace('T',' ').slice(0,19) : '--';
        return `<div class="tl-item">
          <div class="tl-icon ${c.icls}">${c.icon}</div>
          <div class="tl-body">
            <div class="tl-titulo ${c.cls}">${c.txt}</div>
            <div class="tl-meta">${ts} &nbsp;·&nbsp; nível: ${parseFloat(a.nivel_pct).toFixed(1)}%</div>
          </div>
        </div>`;
      }).join('');
    }

    const totalPags = Math.max(1, Math.ceil(almTotal / ALM_LIM));
    document.getElementById('alm-pag-info').textContent = `Pág ${almPag} / ${totalPags}`;
    document.getElementById('alm-prev').disabled = almPag <= 1;
    document.getElementById('alm-next').disabled = almPag >= totalPags;

  } catch(e) {
    console.error('[ALM]', e);
    document.getElementById('alm-timeline').innerHTML =
      `<div class="empty-msg">Erro: ${e.message}</div>`;
  }
}

async function fetchResumoAlarmes() {
  try {
    const [ra, rn, rb] = await Promise.all([
      fetch(`${BASE}/api/alarmes.php?tipo=alto&limite=1`).then(r=>r.json()),
      fetch(`${BASE}/api/alarmes.php?tipo=normal&limite=1`).then(r=>r.json()),
      fetch(`${BASE}/api/alarmes.php?tipo=baixo&limite=1`).then(r=>r.json()),
    ]);
    document.getElementById('cnt-alto' ).textContent = ra.total ?? '—';
    document.getElementById('cnt-norm' ).textContent = rn.total ?? '—';
    document.getElementById('cnt-baixo').textContent = rb.total ?? '—';
  } catch(e) { console.error('[ALM-RESUMO]', e); }
}

function almPagina(dir) {
  const totalPags = Math.max(1, Math.ceil(almTotal / ALM_LIM));
  almPag = Math.max(1, Math.min(totalPags, almPag + dir));
  fetchAlarmes();
}

function setFiltroAlm(filtro, btn) {
  almFiltro = filtro; almPag = 1;
  document.querySelectorAll('.fbtn').forEach(b => b.className = 'fbtn');
  btn.classList.add(filtro ? `ativo-${filtro}` : 'ativo-all');
  fetchAlarmes();
}

// ── Auto-refresh ───────────────────────────────────────────────
let cdT = 10;
setInterval(() => {
  cdT--;
  document.getElementById('countdown-t').textContent = cdT;
  if (cdT <= 0) {
    cdT = 10;
    fetchTelemetria();
    fetchAlarmes();
    fetchResumoAlarmes();
  }
}, 1000);
setInterval(() => {
  document.getElementById('ts-clk').textContent = new Date().toLocaleTimeString('pt-BR');
}, 1000);

// ── Init ───────────────────────────────────────────────────────
fetchTelemetria();
fetchAlarmes();
fetchResumoAlarmes();
</script>
</body>
</html>
