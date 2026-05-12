# SCADA Sim — Tanque de Processo T-01

Sistema de supervisão e controle desenvolvido como projeto 1 da disciplina de Sistemas Embarcados no curso de Engenharia de Controle e Automação.

---

## O que o sistema faz

Um potenciômetro simula o nível de um tanque industrial. O ESP32 lê esse valor e envia periodicamente para um servidor PHP, que armazena no banco e disponibiliza para o dashboard web. De lá, o operador pode abrir/fechar uma válvula (Servo 1) e ajustar a velocidade de um agitador (Servo 2).

O sistema opera em dois modos:

- **Manual** — operador controla a válvula livremente pelo dashboard ou pelo botão físico no ESP32
- **Automático** — a válvula abre e fecha sozinha com base nos limiares de nível configurados

Quando o nível cruza um limiar, um popup aparece no dashboard e um alarme é registrado no banco com timestamp (tem um botão para silenciar os alertas sem perder o histórico).

---

## Hardware 🔧

| Componente | Pino | Função |
|---|---|---|
| Potenciômetro | GPIO 35 | Nível do tanque (0–100%) |
| Servo 1 | GPIO 26 | Válvula (0° fechada / 90° aberta) |
| Servo 2 | GPIO 27 | Agitador (rotação contínua, velocidade variável) |
| LED vermelho | GPIO 18 | Alarme nível alto |
| LED verde | GPIO 17 | Operação normal |
| LED branco | GPIO 16 | Alerta nível baixo |
| Botão vermelho | GPIO 32 | Toggle válvula (modo manual) |
| OLED SSD1306 | I2C (SDA 22 / SCL 23) | Display local do ESP32 |

---

## Stack 🗂️

**Firmware**
- ESP32 com `ESP32Servo`, `Adafruit SSD1306` e `ArduinoJson`
- Comunicação HTTP REST com API key no header `X-API-Key`
- Dois timers independentes: telemetria a cada 2 s, heartbeat a cada 5 s

**Backend**
- PHP + PDO (XAMPP / MySQL)
- RBAC com duas roles: `admin` (leitura e escrita) e `visitor` (somente leitura)
- Sessão PHP para o dashboard, API key para o ESP32

**Banco de dados**
- `usuarios` — autenticação e controle de acesso
- `telemetria` — leituras do ESP32 (mantém os últimos 500 registros)
- `setpoints` — configurações ativas: modo, RPM, limiares de nível
- `alarmes` — histórico de transições de estado (alto / normal / baixo)
- `esp32_status` — heartbeat com registro explícito de online/offline

**Frontend**
- PHP server-side rendering + JavaScript puro (sem frameworks)
- Chart.js para os gráficos de tendência

---

## Telas 🖥️

- **Dashboard** — tanque animado, status da válvula, RPM do agitador, preview do OLED e painel de controles (admin)
- **Gráfico — Nível** — série temporal com linhas de limiar configuráveis, seletor de período (30 min a 24 h)
- **Gráfico — Agitador** — histórico de velocidade com indicador de tempo em movimento
- **Histórico** — tabela de telemetria paginada + timeline de alarmes com filtro por tipo

---

## Estrutura do projeto

```
scada/
├── config.php
├── schema.sql
├── index.php
├── grafico_nivel.php
├── grafico_agitador.php
├── historico.php
├── login.php  /  register.php  /  logout.php  /  admin.php
├── api/
│   ├── telemetria.php
│   ├── setpoints.php
│   ├── heartbeat.php
│   ├── status.php
│   ├── grafico.php
│   ├── alarmes.php
│   └── historico_telemetria.php
└── includes/
    ├── db.php  /  auth.php  /  middleware.php  /  nav.php
```

---

## Simulação no Wokwi ⚡

Link do Wokwi: https://wokwi.com/projects/463566046296655873

---

## Autor

Vitor Capelli
