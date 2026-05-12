<?php
require_once __DIR__ . '/includes/middleware.php';
requireLogin();

$usuario    = usuarioAtual();
$isAdmin    = ehAdmin();
$aviso      = $_GET['aviso'] ?? '';
$db         = getDB();
$sp0        = $db->query('SELECT * FROM setpoints WHERE id = 1')->fetch();
$initValv   = (bool)($sp0['valvula_aberta']  ?? false);
$initRpm    = (int) ($sp0['helice_rpm']      ?? 0);
$initModo   = $sp0['modo']                   ?? 'manual';
$initLBaixo = (int) ($sp0['limiar_baixo']    ?? 20);
$initLNorm  = (int) ($sp0['limiar_normal']   ?? 40);
$initLAlto  = (int) ($sp0['limiar_alto']     ?? 80);
$initLValv  = (int) ($sp0['limiar_valvula']  ?? 30);
$initSil    = (bool)($sp0['silenciar']       ?? false);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= APP_NAME ?> — Supervisório</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#eaecef;--bg2:#f4f5f7;--surface:#fff;
      --bd:#d0d4da;--bd2:#b8bec7;
      --txt:#1a1e24;--txt2:#4a5568;--txt3:#8a97a8;
      --mono:'IBM Plex Mono',monospace;
      --sans:'IBM Plex Sans',sans-serif;
      --red:#c0392b;--red-bg:#fdf2f1;--red-bd:#e8b4b0;
      --grn:#1a7a45;--grn-bg:#f0faf4;--grn-bd:#a8dbbe;
      --amb:#b45309;--amb-bg:#fdf8f0;--amb-bd:#e8d0a0;
      --sky:#1d5fa8;--sky-bg:#f0f5fd;--sky-bd:#a8c4e8;
      --r:6px;--sh:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.04);
      --sh2:0 4px 16px rgba(0,0,0,.14)
    }
    body{background:var(--bg);color:var(--txt);font-family:var(--sans);font-size:13px;min-height:100vh}

    /* ── Topbar ──────────────────────────────────────────────── */
    .topbar{background:var(--surface);border-bottom:2px solid var(--bd2);height:52px;display:flex;align-items:center;justify-content:space-between;padding:0 20px;position:sticky;top:0;z-index:200;box-shadow:var(--sh)}
    .tb-left{display:flex;align-items:center;gap:12px}
    .app-id{font-family:var(--mono);font-size:10px;font-weight:600;letter-spacing:.14em;color:var(--txt3);text-transform:uppercase;border-right:1px solid var(--bd);padding-right:12px}
    .app-name{font-size:15px;font-weight:600}
    .tb-right{display:flex;align-items:center;gap:10px}
    .pill{display:inline-flex;align-items:center;gap:4px;font-family:var(--mono);font-size:9px;font-weight:600;letter-spacing:.08em;padding:3px 8px;border-radius:3px;border:1px solid;white-space:nowrap}
    .pill-grn{border-color:var(--grn-bd);background:var(--grn-bg);color:var(--grn)}
    .pill-red{border-color:var(--red-bd);background:var(--red-bg);color:var(--red)}
    .pill-amb{border-color:var(--amb-bd);background:var(--amb-bg);color:var(--amb)}
    .pill-sky{border-color:var(--sky-bd);background:var(--sky-bg);color:var(--sky)}
    .dot{width:5px;height:5px;border-radius:50%;background:currentColor;animation:blink 2.2s ease-in-out infinite}
    @keyframes blink{0%,100%{opacity:1}50%{opacity:.25}}
    #ts-clk{font-family:var(--mono);font-size:11px;color:var(--txt3);border-left:1px solid var(--bd);padding-left:10px}
    .btn-logout{font-family:var(--sans);font-size:12px;font-weight:500;padding:5px 12px;border-radius:var(--r);border:1px solid var(--bd2);background:var(--bg2);color:var(--txt2);cursor:pointer;transition:all .15s}
    .btn-logout:hover{border-color:var(--red-bd);color:var(--red);background:var(--red-bg)}

    /* ── Layout ──────────────────────────────────────────────── */
    .page{padding:16px 20px;display:grid;gap:12px}
    .row-main{display:grid;gap:12px;grid-template-columns:210px 1fr <?= $isAdmin ? '310px' : '' ?>}
    .card{background:var(--surface);border:1px solid var(--bd);border-radius:var(--r);box-shadow:var(--sh);overflow:hidden}
    .chdr{padding:9px 14px 8px;border-bottom:1px solid var(--bd);background:var(--bg2);display:flex;align-items:center;justify-content:space-between;gap:8px}
    .chtitle{font-family:var(--mono);font-size:9px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--txt3)}
    .cbody{padding:14px}

    /* ── Alerta ──────────────────────────────────────────────── */
    .alert{padding:10px 14px;border-radius:var(--r);border-left:3px solid;font-size:13px;font-weight:500}
    .alert-warn{background:var(--amb-bg);border-color:var(--amb);color:var(--amb)}
    .alert-ok  {background:var(--grn-bg);border-color:var(--grn);color:var(--grn)}

    /* ── Tanque ──────────────────────────────────────────────── */
    .tank-wrap{display:flex;flex-direction:column;align-items:center;gap:10px;padding:14px}
    .tank-outer{width:80px;height:190px;border:2px solid var(--bd2);border-radius:4px;background:var(--bg);position:relative;overflow:hidden}
    #tank-fill{position:absolute;bottom:0;left:0;right:0;height:50%;background:#4a90c4;transition:height .9s cubic-bezier(.4,0,.2,1),background .5s}
    .tank-m{position:absolute;left:0;right:0;border-top:1px dashed}
    #tank-lbl{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--mono);font-size:18px;font-weight:600;color:var(--txt);text-shadow:0 1px 4px rgba(255,255,255,.95)}

    /* ── Indicadores de nível ────────────────────────────────── */
    .nivel-badges{display:flex;flex-direction:column;gap:5px;width:100%}
    .nivel-badge{
      display:flex;align-items:center;gap:6px;
      padding:5px 9px;border-radius:4px;
      font-family:var(--mono);font-size:10px;font-weight:600;letter-spacing:.07em;
      border:1px solid;transition:all .4s;opacity:.35;
    }
    .nivel-badge.ativo{opacity:1}
    .nb-alto {border-color:var(--red-bd);background:var(--red-bg);color:var(--red)}
    .nb-norm {border-color:var(--grn-bd);background:var(--grn-bg);color:var(--grn)}
    .nb-baixo{border-color:var(--amb-bd);background:var(--amb-bg);color:var(--amb)}
    .nb-dot  {width:7px;height:7px;border-radius:50%;background:currentColor}

    /* ── Válvula ─────────────────────────────────────────────── */
    .valve-card{display:flex;flex-direction:column;align-items:center;gap:10px;padding:14px}
    .valve-icon{
      width:64px;height:64px;
      border:3px solid;border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      font-size:28px;transition:all .4s;
    }
    .valve-open  {border-color:var(--grn);background:var(--grn-bg)}
    .valve-closed{border-color:var(--red);background:var(--red-bg)}
    .valve-st{font-family:var(--mono);font-size:11px;font-weight:600;letter-spacing:.1em}
    .btn-valve{
      width:100%;padding:8px;border-radius:var(--r);
      font-family:var(--sans);font-size:12px;font-weight:600;letter-spacing:.04em;
      border:1px solid;cursor:pointer;transition:all .2s;
    }
    .btn-valve-open {border-color:var(--grn-bd);background:var(--grn-bg);color:var(--grn)}
    .btn-valve-open:hover{background:#ddf5e8}
    .btn-valve-close{border-color:var(--red-bd);background:var(--red-bg);color:var(--red)}
    .btn-valve-close:hover{background:#fce6e4}
    .btn-valve:disabled{opacity:.4;cursor:not-allowed}

    /* ── Hélice ──────────────────────────────────────────────── */
    .helice-spin{
      width:62px;height:62px;
      border:3px solid var(--sky-bd);border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      background:var(--sky-bg);position:relative;overflow:hidden;
    }
    .helice-blade{
      width:48px;height:4px;border-radius:2px;
      background:var(--sky);
      position:absolute;
      transform-origin:center;
      animation:spin 2s linear infinite;
      animation-play-state:paused;
    }
    .helice-blade:nth-child(2){transform:rotate(60deg)}
    .helice-blade:nth-child(3){transform:rotate(120deg)}
    @keyframes spin{to{transform:rotate(360deg)}}
    @keyframes spin2{to{transform:rotate(360deg) rotate(60deg)}}
    @keyframes spin3{to{transform:rotate(360deg) rotate(120deg)}}

    /* ── Gauge RPM ───────────────────────────────────────────── */
    .rpm-gauge{width:100%}
    .rpm-val{font-family:var(--mono);font-size:26px;font-weight:600;color:var(--sky);line-height:1}
    .rpm-bar-track{height:5px;background:var(--bd);border-radius:3px;overflow:hidden;margin:7px 0 4px}
    .rpm-bar-fill{height:100%;background:var(--sky);border-radius:3px;transition:width .6s ease}

    /* ── Gauges (válvula display) ────────────────────────────── */
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .gb{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--r);padding:12px}
    .g-lbl{font-family:var(--mono);font-size:9px;letter-spacing:.13em;text-transform:uppercase;color:var(--txt3);margin-bottom:8px}

    /* ── OLED ────────────────────────────────────────────────── */
    .oled{background:#101012;border-radius:4px;padding:9px 11px;font-family:var(--mono);font-size:11px;color:#5b8fff;line-height:1.75;white-space:pre;letter-spacing:.05em}

    /* ── Painel admin ────────────────────────────────────────── */
    .ctrl-body{display:flex;flex-direction:column;gap:12px;padding:14px}
    .section-title{font-family:var(--mono);font-size:9px;letter-spacing:.14em;text-transform:uppercase;color:var(--txt3);margin-bottom:6px}
    .range-row{display:flex;align-items:center;gap:6px}
    .rlim{font-family:var(--mono);font-size:10px;color:var(--txt3)}
    input[type=range]{flex:1;height:3px;accent-color:var(--sky);cursor:pointer}
    .rval{font-family:var(--mono);font-size:15px;font-weight:600;min-width:40px;text-align:right}
    .divider{height:1px;background:var(--bd)}

    /* Limiares */
    .limiar-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .limiar-item{background:var(--bg2);border:1px solid var(--bd);border-radius:var(--r);padding:10px}
    .limiar-label{font-family:var(--mono);font-size:9px;letter-spacing:.1em;text-transform:uppercase;margin-bottom:6px}
    .lb-alto {color:var(--red)}
    .lb-norm {color:var(--grn)}
    .lb-baixo{color:var(--amb)}
    .lb-valv {color:var(--sky)}
    .limiar-range-row{display:flex;align-items:center;gap:5px}
    .limiar-range-row input{flex:1;height:3px;cursor:pointer}
    .limiar-range-row input.acc-red {accent-color:var(--red)}
    .limiar-range-row input.acc-grn {accent-color:var(--grn)}
    .limiar-range-row input.acc-amb {accent-color:var(--amb)}
    .limiar-range-row input.acc-sky {accent-color:var(--sky)}
    .limiar-val{font-family:var(--mono);font-size:13px;font-weight:600;min-width:34px;text-align:right}

    select{width:100%;padding:7px 10px;border:1px solid var(--bd2);border-radius:var(--r);background:var(--bg2);color:var(--txt);font-family:var(--sans);font-size:13px;outline:none;cursor:pointer}
    select:focus{border-color:var(--sky)}

    .btn-apply{width:100%;padding:10px;background:var(--txt);color:#fff;border:none;border-radius:var(--r);font-family:var(--sans);font-size:13px;font-weight:600;letter-spacing:.04em;cursor:pointer;transition:all .15s}
    .btn-apply:hover{background:#000}
    .btn-apply:disabled{background:var(--bd2);cursor:not-allowed}
    .ctrl-fb{font-size:12px;text-align:center;padding:7px;border-radius:var(--r);display:none;border:1px solid;font-weight:500}
    .ctrl-fb.ok {display:block;background:var(--grn-bg);border-color:var(--grn-bd);color:var(--grn)}
    .ctrl-fb.err{display:block;background:var(--red-bg);border-color:var(--red-bd);color:var(--red)}

    /* ── Histórico ───────────────────────────────────────────── */
    .tbl{width:100%;border-collapse:collapse;font-size:12px}
    .tbl th{font-family:var(--mono);font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:var(--txt3);padding:7px 12px;border-bottom:1px solid var(--bd);background:var(--bg2);font-weight:600;text-align:left}
    .tbl td{padding:6px 12px;border-bottom:1px solid var(--bg);font-family:var(--mono);font-size:11px;color:var(--txt2)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--bg2)}
    .tbl .t-time{color:var(--txt3)}
    .t-hi{color:var(--red)!important;font-weight:600}
    .t-lo{color:var(--amb)!important;font-weight:600}

    /* ── Popup de alarme ─────────────────────────────────────── */
    #popup-container{position:fixed;bottom:20px;right:20px;display:flex;flex-direction:column;gap:8px;z-index:9999;pointer-events:none}
    .popup{
      pointer-events:auto;
      min-width:240px;max-width:300px;
      background:var(--surface);
      border:1px solid var(--bd);
      border-radius:var(--r);
      box-shadow:var(--sh2);
      padding:12px 14px;
      display:flex;align-items:flex-start;gap:10px;
      animation:slideIn .3s ease;
      transition:opacity .3s,transform .3s;
    }
    @keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:none}}
    .popup.fading{opacity:0;transform:translateX(10px)}
    .popup-icon{font-size:20px;flex-shrink:0;margin-top:1px}
    .popup-title{font-weight:600;font-size:13px;margin-bottom:2px}
    .popup-msg{font-size:12px;color:var(--txt2)}
    .popup-close{margin-left:auto;flex-shrink:0;background:none;border:none;cursor:pointer;color:var(--txt3);font-size:16px;line-height:1;padding:0 2px}
    .popup-close:hover{color:var(--txt)}
    .popup-alto {border-left:3px solid var(--red)}
    .popup-norm {border-left:3px solid var(--grn)}
    .popup-baixo{border-left:3px solid var(--amb)}

    /* Botão silenciar */
    .btn-mute{
      display:inline-flex;align-items:center;gap:5px;
      font-family:var(--sans);font-size:11px;font-weight:500;
      padding:4px 10px;border-radius:var(--r);
      border:1px solid var(--bd2);background:var(--bg2);color:var(--txt2);
      cursor:pointer;transition:all .15s;
    }
    .btn-mute.muted{border-color:var(--amb-bd);background:var(--amb-bg);color:var(--amb)}
    .btn-mute:hover{background:var(--bg)}
  </style>
</head>
<body>

<!-- ── Topbar ────────────────────────────────────────────────── -->
<header class="topbar">
  <div class="tb-left">
    <span class="app-id">T-01</span>
    <span class="app-name"><?= htmlspecialchars(APP_NAME) ?></span>
    <span id="conn-pill" class="pill pill-grn"><span class="dot" id="conn-dot"></span><span id="conn-lbl">ESP32 ONLINE</span></span>
    <span id="modo-pill" class="pill pill-sky">MANUAL</span>
  </div>
  <div class="tb-right">
    <button id="btn-mute" class="btn-mute" onclick="toggleMute()" title="Silenciar alertas">🔔 Alertas</button>
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

  <?php if ($aviso === 'sem_permissao'): ?>
    <div class="alert alert-warn">Ação negada — apenas administradores podem alterar o sistema.</div>
  <?php elseif ($aviso === 'admin_first'): ?>
    <div class="alert alert-ok">Primeira conta criada com role <strong>admin</strong>.</div>
  <?php endif; ?>

  <!-- ── Linha principal ──────────────────────────────────────── -->
  <div class="row-main">

    <!-- COL 1: tanque + válvula -->
    <div style="display:flex;flex-direction:column;gap:12px">

      <!-- Tanque -->
      <div class="card">
        <div class="chdr"><span class="chtitle">Nível — Tanque</span></div>
        <div class="tank-wrap">
          <div class="tank-outer">
            <div id="tank-fill"></div>
            <div id="tank-m-alto"  class="tank-m" style="top:20%;border-color:rgba(192,57,43,.4)"></div>
            <div id="tank-m-baixo" class="tank-m" style="top:80%;border-color:rgba(180,83,9,.4)"></div>
            <div id="tank-lbl">--%</div>
          </div>
          <!-- Indicadores de nível -->
          <div class="nivel-badges">
            <div id="nb-alto"  class="nivel-badge nb-alto"><div class="nb-dot"></div>NÍVEL ALTO</div>
            <div id="nb-norm"  class="nivel-badge nb-norm"><div class="nb-dot"></div>NORMAL</div>
            <div id="nb-baixo" class="nivel-badge nb-baixo"><div class="nb-dot"></div>NÍVEL BAIXO</div>
          </div>
        </div>
      </div>

      <!-- Válvula -->
      <div class="card">
        <div class="chdr"><span class="chtitle">Válvula — Servo 1 (P26)</span></div>
        <div class="valve-card">
          <div id="valve-icon" class="valve-icon valve-closed">🔒</div>
          <div id="valve-st"   class="valve-st" style="color:var(--red)">FECHADA</div>
          <?php if ($isAdmin): ?>
            <button id="btn-valve" class="btn-valve btn-valve-open" onclick="toggleValve()">
              Abrir válvula
            </button>
            <div id="valve-auto-note" style="display:none;font-size:11px;color:var(--txt3);text-align:center">
              Modo automático — válvula controlada pelos limiares
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /col1 -->

    <!-- COL 2: hélice + oled + histórico -->
    <div style="display:flex;flex-direction:column;gap:12px">

      <!-- Hélice + status -->
      <div class="card">
        <div class="chdr"><span class="chtitle">Processo</span></div>
        <div class="cbody">
          <div class="two-col" style="margin-bottom:12px">

            <!-- Hélice -->
            <div class="gb">
              <div class="g-lbl">Hélice / Agitador (P27)</div>
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                <div class="helice-spin">
                  <div id="hb1" class="helice-blade"></div>
                  <div id="hb2" class="helice-blade" style="transform:rotate(60deg)"></div>
                  <div id="hb3" class="helice-blade" style="transform:rotate(120deg)"></div>
                </div>
                <div class="rpm-gauge">
                  <div id="rpm-val" class="rpm-val">0%</div>
                  <div style="font-size:11px;color:var(--txt3);margin-top:2px" id="rpm-st">PARADO</div>
                </div>
              </div>
              <div class="rpm-bar-track">
                <div id="rpm-bar" class="rpm-bar-fill" style="width:0%"></div>
              </div>
              <div style="font-family:var(--mono);font-size:10px;color:var(--txt3)">
                setpoint: <span id="rpm-sp" style="color:var(--amb)">0%</span>
              </div>
            </div>

            <!-- Resumo setpoints -->
            <div class="gb">
              <div class="g-lbl">Setpoints ativos</div>
              <div style="display:flex;flex-direction:column;gap:7px">
                <div>
                  <div style="font-size:10px;color:var(--txt3)">Modo</div>
                  <div id="sp-modo" style="font-family:var(--mono);font-size:14px;font-weight:600">MANUAL</div>
                </div>
                <div>
                  <div style="font-size:10px;color:var(--txt3)">Lim. válvula (auto)</div>
                  <div id="sp-limvalv" style="font-family:var(--mono);font-size:14px;font-weight:600;color:var(--sky)">30%</div>
                </div>
                <div>
                  <div style="font-size:10px;color:var(--txt3)">Atualizado por</div>
                  <div id="sp-by" style="font-family:var(--mono);font-size:11px;color:var(--txt2)">--</div>
                </div>
              </div>
            </div>

          </div><!-- /two-col -->
        </div>
      </div>

      <!-- OLED -->
      <div class="card">
        <div class="chdr"><span class="chtitle">Preview — OLED SSD1306</span></div>
        <div class="cbody" style="padding:10px">
          <div id="oled-preview" class="oled">NVL:  --%  VLV: --
HELICE:  --%   RPM: --
MODO: <?= strtoupper($initModo) ?>
STATUS: --        </div>
        </div>
      </div>

      <!-- Histórico -->
      <div class="card">
        <div class="chdr"><span class="chtitle">Histórico de telemetria</span></div>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr>
              <th>Hora</th><th>Nível</th><th>Válvula</th><th>Hélice</th>
            </tr></thead>
            <tbody id="hist-tbody">
              <tr><td colspan="4" style="color:var(--txt3);text-align:center;padding:16px">
                Aguardando dados…
              </td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /col2 -->

    <!-- COL 3: controles admin -->
    <?php if ($isAdmin): ?>
    <div class="card" style="overflow:visible">
      <div class="chdr">
        <span class="chtitle">Controles</span>
        <a href="<?= BASE_PATH ?>/admin.php" style="font-size:11px;color:var(--sky);text-decoration:none">Usuários →</a>
      </div>
      <div class="ctrl-body">

        <!-- Modo -->
        <div>
          <div class="section-title">Modo de operação</div>
          <select id="ctrl-modo">
            <option value="manual" <?= $initModo==='manual'?'selected':''?>>Manual</option>
            <option value="auto"   <?= $initModo==='auto'  ?'selected':''?>>Automático</option>
          </select>
        </div>

        <div class="divider"></div>

        <!-- RPM hélice -->
        <div>
          <div class="section-title">RPM — Hélice (Servo 2)</div>
          <div class="range-row">
            <span class="rlim">0%</span>
            <input type="range" id="ctrl-rpm" min="0" max="100" value="<?= $initRpm ?>">
            <span class="rlim">100%</span>
            <span id="ctrl-rpm-val" class="rval" style="color:var(--sky)"><?= $initRpm ?>%</span>
          </div>
        </div>

        <div class="divider"></div>

        <!-- Limiares -->
        <div>
          <div class="section-title">Limiares de nível</div>
          <div class="limiar-grid">
            <div class="limiar-item">
              <div class="limiar-label lb-alto">Alarme alto</div>
              <div class="limiar-range-row">
                <input type="range" id="lim-alto" class="acc-red" min="1" max="100" value="<?= $initLAlto ?>">
                <span id="lim-alto-val" class="limiar-val" style="color:var(--red)"><?= $initLAlto ?>%</span>
              </div>
            </div>
            <div class="limiar-item">
              <div class="limiar-label lb-norm">Normal acima de</div>
              <div class="limiar-range-row">
                <input type="range" id="lim-norm" class="acc-grn" min="1" max="100" value="<?= $initLNorm ?>">
                <span id="lim-norm-val" class="limiar-val" style="color:var(--grn)"><?= $initLNorm ?>%</span>
              </div>
            </div>
            <div class="limiar-item">
              <div class="limiar-label lb-baixo">Alarme baixo</div>
              <div class="limiar-range-row">
                <input type="range" id="lim-baixo" class="acc-amb" min="0" max="99" value="<?= $initLBaixo ?>">
                <span id="lim-baixo-val" class="limiar-val" style="color:var(--amb)"><?= $initLBaixo ?>%</span>
              </div>
            </div>
            <div class="limiar-item">
              <div class="limiar-label lb-valv">Abre válvula (auto)</div>
              <div class="limiar-range-row">
                <input type="range" id="lim-valv" class="acc-sky" min="0" max="100" value="<?= $initLValv ?>">
                <span id="lim-valv-val" class="limiar-val" style="color:var(--sky)"><?= $initLValv ?>%</span>
              </div>
            </div>
          </div>
        </div>

        <button class="btn-apply" id="btn-apply" onclick="aplicarSetpoints()">
          Aplicar configurações
        </button>
        <div id="ctrl-fb" class="ctrl-fb"></div>

        <!-- Info -->
        <div style="font-size:11px;color:var(--txt3);line-height:2;padding-top:4px;border-top:1px solid var(--bd)">
          <div>Telemetria: <span style="font-family:var(--mono);color:var(--txt2)">2 s</span></div>
          <div>Setpoints: <span style="font-family:var(--mono);color:var(--txt2)">1 s</span></div>
          <div>Última sync: <span id="upd-ts" style="font-family:var(--mono);color:var(--txt2)">--</span></div>
        </div>

      </div>
    </div>
    <?php endif; ?>

  </div><!-- /row-main -->
</div><!-- /page -->

<!-- ── Popup container ───────────────────────────────────────── -->
<div id="popup-container"></div>

<script>
const BASE     = '<?= BASE_PATH ?>';
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const el = id => document.getElementById(id);

// ── Estado local ───────────────────────────────────────────────
let estadoNivel   = null;   // 'alto' | 'normal' | 'baixo'
let valvulaAberta = <?= $initValv ? 'true' : 'false' ?>;
let modoAtual     = '<?= $initModo ?>';
let mutado        = <?= $initSil ? 'true' : 'false' ?>;
let limiares      = {
  baixo:  <?= $initLBaixo ?>,
  normal: <?= $initLNorm ?>,
  alto:   <?= $initLAlto ?>,
  valvula:<?= $initLValv ?>,
};

// ── Clock ──────────────────────────────────────────────────────
setInterval(() => el('ts-clk').textContent = new Date().toLocaleTimeString('pt-BR'), 1000);

// ── Sliders live ───────────────────────────────────────────────
if (IS_ADMIN) {
  el('ctrl-rpm').addEventListener('input', e => {
    el('ctrl-rpm-val').textContent = e.target.value + '%';
  });
  [
    ['lim-alto',  'lim-alto-val',  '%'],
    ['lim-norm',  'lim-norm-val',  '%'],
    ['lim-baixo', 'lim-baixo-val', '%'],
    ['lim-valv',  'lim-valv-val',  '%'],
  ].forEach(([id, vid, suf]) => {
    el(id).addEventListener('input', e => el(vid).textContent = e.target.value + suf);
  });
}

// ── Hélice: animação de rotação ────────────────────────────────
function setRpmAnimation(pct) {
  const dur = pct <= 0 ? 'none' : (2 - pct / 100 * 1.7) + 's';
  const state = pct <= 0 ? 'paused' : 'running';
  ['hb1','hb2','hb3'].forEach((id, i) => {
    const blade = el(id);
    blade.style.animationDuration = dur;
    blade.style.animationPlayState = state;
  });
}

// ── Válvula: UI state ──────────────────────────────────────────
function setValveUI(aberta, modo) {
  const icon = el('valve-icon');
  const st   = el('valve-st');
  if (icon) {
    icon.className = 'valve-icon ' + (aberta ? 'valve-open' : 'valve-closed');
    icon.textContent = aberta ? '🔓' : '🔒';
  }
  if (st) {
    st.textContent = aberta ? 'ABERTA' : 'FECHADA';
    st.style.color = aberta ? 'var(--grn)' : 'var(--red)';
  }
  if (IS_ADMIN) {
    const btn  = el('btn-valve');
    const note = el('valve-auto-note');
    const isAuto = modo === 'auto';
    btn.style.display  = isAuto ? 'none' : 'block';
    note.style.display = isAuto ? 'block' : 'none';
    if (!isAuto) {
      btn.textContent  = aberta ? 'Fechar válvula' : 'Abrir válvula';
      btn.className    = 'btn-valve ' + (aberta ? 'btn-valve-close' : 'btn-valve-open');
    }
  }
  valvulaAberta = aberta;
}

// ── Toggle válvula (manual) ────────────────────────────────────
async function toggleValve() {
  const nova = !valvulaAberta;
  try {
    const r = await fetch(BASE + '/api/setpoints.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ valvula_aberta: nova })
    });
    const d = await r.json();
    if (d.ok) setValveUI(nova, modoAtual);
  } catch { showFeedback('err', 'Falha ao acionar válvula'); }
}

// ── Toggle mute ────────────────────────────────────────────────
function toggleMute() {
  mutado = !mutado;
  const btn = el('btn-mute');
  btn.textContent = mutado ? '🔕 Silenciado' : '🔔 Alertas';
  btn.className   = 'btn-mute' + (mutado ? ' muted' : '');
  if (IS_ADMIN) {
    fetch(BASE + '/api/setpoints.php', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ silenciar: mutado })
    });
  }
}

// ── Nível: calcular estado ─────────────────────────────────────
function calcEstadoNivel(nv, lim) {
  if (nv >= lim.alto)  return 'alto';
  if (nv >= lim.normal) return 'normal';
  return 'baixo';
}

// ── Atualizar indicadores de nível ────────────────────────────
function updateNivelUI(nv, lim) {
  const estado = calcEstadoNivel(nv, lim);

  // Posição das marcas no tanque
  el('tank-m-alto' ).style.top = (100 - lim.alto)  + '%';
  el('tank-m-baixo').style.top = (100 - lim.baixo) + '%';

  // Badges
  el('nb-alto' ).classList.toggle('ativo', estado === 'alto');
  el('nb-norm' ).classList.toggle('ativo', estado === 'normal');
  el('nb-baixo').classList.toggle('ativo', estado === 'baixo');

  // Cor do tanque
  const cor = estado === 'alto' ? '#c74b4b' : estado === 'baixo' ? '#b87333' : '#4a90c4';
  el('tank-fill').style.background = cor;

  // Popup se mudou de estado
  if (estadoNivel !== null && estadoNivel !== estado && !mutado) {
    mostrarPopup(estado, nv);
  }

  const novo = estadoNivel !== estado;
  estadoNivel = estado;
  return novo;
}

// ── Popup ──────────────────────────────────────────────────────
function mostrarPopup(estado, nv) {
  const cfg = {
    alto:   { cls: 'popup-alto',  icon: '🔴', title: 'Alarme — Nível Alto',   msg: `Nível atingiu ${Math.round(nv)}%` },
    normal: { cls: 'popup-norm',  icon: '🟢', title: 'Normalizado',            msg: `Nível em ${Math.round(nv)}%` },
    baixo:  { cls: 'popup-baixo', icon: '🟡', title: 'Alerta — Nível Baixo',  msg: `Nível em ${Math.round(nv)}%` },
  }[estado];

  const pop = document.createElement('div');
  pop.className = `popup ${cfg.cls}`;
  pop.innerHTML = `
    <span class="popup-icon">${cfg.icon}</span>
    <div>
      <div class="popup-title">${cfg.title}</div>
      <div class="popup-msg">${cfg.msg} — ${new Date().toLocaleTimeString('pt-BR')}</div>
    </div>
    <button class="popup-close" onclick="this.closest('.popup').remove()">✕</button>
  `;
  el('popup-container').appendChild(pop);

  setTimeout(() => {
    pop.classList.add('fading');
    setTimeout(() => pop.remove(), 350);
  }, 6000);
}

// ── Polling ────────────────────────────────────────────────────
async function poll() {
  try {
    const res = await fetch(BASE + '/api/status.php');
    if (res.status === 401) { location = BASE + '/login.php'; return; }
    if (!res.ok) throw new Error();
    const d = await res.json();
    renderUI(d);
    setConn(d.esp32_online);
  } catch { setConn(false); }
}

function renderUI(d) {
  const t = d.telemetria, s = d.setpoints;

  if (s) {
    modoAtual = s.modo;
    mutado    = !!parseInt(s.silenciar);

    limiares = {
      baixo:   parseInt(s.limiar_baixo),
      normal:  parseInt(s.limiar_normal),
      alto:    parseInt(s.limiar_alto),
      valvula: parseInt(s.limiar_valvula),
    };

    // Atualizar pill de modo
    const mp = el('modo-pill');
    mp.textContent = s.modo.toUpperCase();
    mp.className   = 'pill ' + (s.modo === 'auto' ? 'pill-grn' : 'pill-sky');

    // Sumário setpoints
    el('sp-modo').textContent   = s.modo.toUpperCase();
    el('sp-limvalv').textContent = s.limiar_valvula + '%';
    el('sp-by').textContent      = s.atualizado_por_nome || '--';

    // RPM
    const rpm = parseInt(s.helice_rpm);
    el('rpm-val').textContent   = rpm + '%';
    el('rpm-bar').style.width   = rpm + '%';
    el('rpm-sp' ).textContent   = rpm + '%';
    el('rpm-st' ).textContent   = rpm <= 0 ? 'PARADO' : rpm < 40 ? 'BAIXA VELOCIDADE' : rpm < 70 ? 'VELOCIDADE MÉDIA' : 'ALTA VELOCIDADE';
    setRpmAnimation(rpm);

    // Sliders admin (se existem)
    if (IS_ADMIN) {
      const ctrl = v => el(v);
      if (ctrl('ctrl-rpm')) { ctrl('ctrl-rpm').value = rpm; ctrl('ctrl-rpm-val').textContent = rpm + '%'; }
      if (ctrl('lim-alto'))  { ctrl('lim-alto').value  = s.limiar_alto;    ctrl('lim-alto-val').textContent  = s.limiar_alto  + '%'; }
      if (ctrl('lim-norm'))  { ctrl('lim-norm').value  = s.limiar_normal;  ctrl('lim-norm-val').textContent  = s.limiar_normal+ '%'; }
      if (ctrl('lim-baixo')) { ctrl('lim-baixo').value = s.limiar_baixo;   ctrl('lim-baixo-val').textContent = s.limiar_baixo + '%'; }
      if (ctrl('lim-valv'))  { ctrl('lim-valv').value  = s.limiar_valvula; ctrl('lim-valv-val').textContent  = s.limiar_valvula+'%'; }
      if (ctrl('ctrl-modo')) ctrl('ctrl-modo').value = s.modo;
    }

    // Botão mute
    el('btn-mute').textContent = mutado ? '🔕 Silenciado' : '🔔 Alertas';
    el('btn-mute').className   = 'btn-mute' + (mutado ? ' muted' : '');

    // Válvula
    let vAberta = !!parseInt(s.valvula_aberta);

    // Auto: verifica limiar_valvula contra nível atual
    if (s.modo === 'auto' && t) {
      const nv = parseFloat(t.nivel_pct);
      vAberta = nv <= limiares.valvula;
      // Se diferente do DB, atualiza (apenas admin client-side — o ESP32 faz o mesmo)
    }

    setValveUI(vAberta, s.modo);
  }

  if (t) {
    const nv = parseFloat(t.nivel_pct);
    el('tank-lbl').textContent   = Math.round(nv) + '%';
    el('tank-fill').style.height = nv + '%';
    updateNivelUI(nv, limiares);

    // OLED
    el('oled-preview').textContent =
      `NVL: ${String(Math.round(nv)).padStart(3)}%  VLV: ${valvulaAberta ? 'ABERTA' : 'FECHADA'}\n` +
      `HELICE: ${String(parseInt(t.helice_rpm ?? 0)).padStart(3)}%\n` +
      `MODO: ${modoAtual.toUpperCase()}\n` +
      (nv >= limiares.alto ? 'STATUS: !! ALTO !!' : nv <= limiares.baixo ? 'STATUS: !! BAIXO !!' : 'STATUS: OK        ');

    // Histórico
    if (d.historico?.length) renderHist(d.historico);
  }

  if (el('upd-ts')) el('upd-ts').textContent = new Date().toLocaleTimeString('pt-BR');
}

function renderHist(hist) {
  const rows = hist.slice(-10).reverse().map(r => {
    const nv  = parseFloat(r.nivel_pct);
    const lim = limiares;
    const cls = nv >= lim.alto ? 't-hi' : nv <= lim.baixo ? 't-lo' : '';
    return `<tr>
      <td class="t-time">${r.ts?.slice(11,19)||'--'}</td>
      <td class="${cls}">${nv.toFixed(1)}%</td>
      <td>${parseInt(r.valvula_aberta) ? 'ABERTA' : 'FECHADA'}</td>
      <td>${r.helice_rpm ?? 0}%</td>
    </tr>`;
  }).join('');
  el('hist-tbody').innerHTML = rows || '<tr><td colspan="4" style="color:var(--txt3);text-align:center;padding:12px">Sem dados</td></tr>';
}

function setConn(online) {
  const pill = el('conn-pill'), dot = el('conn-dot'), lbl = el('conn-lbl');
  pill.className  = 'pill ' + (online ? 'pill-grn' : 'pill-red');
  dot.style.animationPlayState = online ? 'running' : 'paused';
  lbl.textContent = online ? 'ESP32 ONLINE' : 'ESP32 OFFLINE';
}

// ── Aplicar setpoints ──────────────────────────────────────────
async function aplicarSetpoints() {
  const btn = el('btn-apply'), fb = el('ctrl-fb');
  btn.disabled = true; btn.textContent = 'Enviando…';
  try {
    const payload = {
      helice_rpm:     +el('ctrl-rpm').value,
      modo:           el('ctrl-modo').value,
      limiar_baixo:   +el('lim-baixo').value,
      limiar_normal:  +el('lim-norm').value,
      limiar_alto:    +el('lim-alto').value,
      limiar_valvula: +el('lim-valv').value,
      silenciar: mutado,
    };
    const r = await fetch(BASE + '/api/setpoints.php', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload),
    });
    const d = await r.json();
    if (d.ok) { fb.textContent = '✓ Configurações aplicadas.'; fb.className = 'ctrl-fb ok'; }
    else       { fb.textContent = '✗ ' + (d.erro||'Erro');     fb.className = 'ctrl-fb err'; }
  } catch {
    fb.textContent = '✗ Falha de conexão.'; fb.className = 'ctrl-fb err';
  } finally {
    btn.disabled = false; btn.textContent = 'Aplicar configurações';
    setTimeout(() => fb.className = 'ctrl-fb', 5000);
  }
}

function showFeedback(tipo, msg) {
  const fb = el('ctrl-fb');
  if (!fb) return;
  fb.textContent = (tipo==='ok'?'✓ ':'✗ ') + msg;
  fb.className   = 'ctrl-fb ' + tipo;
  setTimeout(() => fb.className = 'ctrl-fb', 4000);
}

// ── Init ───────────────────────────────────────────────────────
poll();
setInterval(poll, 2000);
</script>
</body>
</html>
