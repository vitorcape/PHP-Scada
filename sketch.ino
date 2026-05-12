#define MODO_DEMO 0

#define WIFI_SSID  "Wokwi-GUEST"
#define WIFI_PASS  ""
#define API_BASE   "http://php.vitorcape.com.br/scada"
#define API_KEY    "scada_esp32_2024_secret"

#define PIN_POT   35
#define PIN_S1    26
#define PIN_S2    27
#define PIN_LED_R 18
#define PIN_LED_G 17
#define PIN_LED_W 16
#define PIN_BTN1  32
#define PIN_BTN2  33
#define PIN_BTN3  34
#define OLED_SDA  22
#define OLED_SCL  23
#define OLED_ADDR 0x3C

#define T_TEL  2000
#define T_SET  1000
#define T_HB   5000   // heartbeat independente da telemetria
#define T_DISP  400
#define T_ROT    20

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <ESP32Servo.h>
#if !MODO_DEMO
  #include <WiFi.h>
  #include <HTTPClient.h>
  #include <ArduinoJson.h>
#endif

Adafruit_SSD1306 oled(128, 64, &Wire, -1);
Servo servoValv;
Servo servoHelice;

float  nivelPct   = 50.0f;
bool   valvAberta = false;
int    heliceRpm  = 0;
bool   btn1       = false;

bool   cmdValvula   = false;
int    cmdHeliceRpm = 0;
String cmdModo      = "manual";
int    limBaixo = 20, limNormal = 40, limAlto = 80, limValvula = 30;

int   heliceAngle = 0;
unsigned long tmRot = 0;
unsigned long tmTel = 0, tmSet = 0, tmHb = 0, tmDisp = 0;

bool btn1Anterior = HIGH, btn1Estado = false;
unsigned long btn1Db = 0;
bool apiOk = false;

// ════════════════════════════════════════════════════════════════
void setup() {
  Serial.begin(115200); delay(200);
  Serial.println("\n[ SCADA T-01 v3 ]");

  pinMode(PIN_LED_R, OUTPUT); pinMode(PIN_LED_G, OUTPUT); pinMode(PIN_LED_W, OUTPUT);
  pinMode(PIN_BTN1, INPUT_PULLUP); pinMode(PIN_BTN2, INPUT_PULLUP); pinMode(PIN_BTN3, INPUT);

  for (int i = 0; i < 2; i++) {
    setLeds(true,true,true); delay(180); setLeds(false,false,false); delay(180);
  }

  ESP32PWM::allocateTimer(0); ESP32PWM::allocateTimer(1);
  servoValv.setPeriodHertz(50);   servoValv.attach(PIN_S1, 500, 2400);
  servoHelice.setPeriodHertz(50); servoHelice.attach(PIN_S2, 500, 2400);
  atualizarServoValvula(false); servoHelice.write(0);

  Wire.begin(OLED_SDA, OLED_SCL);
  if (oled.begin(SSD1306_SWITCHCAPVCC, OLED_ADDR)) oledBoot();

  #if !MODO_DEMO
    conectarWiFi();
    enviarHeartbeat(); // heartbeat imediato ao conectar
    tmHb = millis();
  #else
    Serial.println("[INFO] MODO_DEMO");
  #endif
}

// ════════════════════════════════════════════════════════════════
void loop() {
  unsigned long now = millis();

  lerPot();
  processarBtn1();
  girarHelice(now);

  #if MODO_DEMO
    logicaDemo();
  #else
    if (now - tmHb  >= T_HB)  { tmHb  = now; enviarHeartbeat();  }
    if (now - tmTel >= T_TEL) { tmTel = now; enviarTelemetria(); }
    if (now - tmSet >= T_SET) { tmSet = now; buscarSetpoints();   }
    aplicarComandos();
  #endif

  if (now - tmDisp >= T_DISP) { tmDisp = now; atualizarOLED(); }
}

// ════════════════════════════════════════════════════════════════
void lerPot() { nivelPct = (analogRead(PIN_POT) / 4095.0f) * 100.0f; }

void processarBtn1() {
  bool leitura = digitalRead(PIN_BTN1);
  unsigned long now = millis();
  if (leitura != btn1Anterior) btn1Db = now;
  if ((now - btn1Db) > 50) {
    bool novo = (leitura == LOW);
    if (novo && !btn1Estado && cmdModo == "manual") {
      cmdValvula = !cmdValvula;
      atualizarServoValvula(cmdValvula);
      Serial.printf("[BTN1] Válvula: %s\n", cmdValvula ? "ABERTA" : "FECHADA");
    }
    btn1Estado = novo;
  }
  btn1Anterior = leitura; btn1 = btn1Estado;
}

void girarHelice(unsigned long now) {
  if (heliceRpm <= 0) { servoHelice.write(0); heliceAngle = 0; return; }
  int step = map(heliceRpm, 1, 100, 1, 12);
  if (now - tmRot >= T_ROT) {
    tmRot = now;
    heliceAngle = (heliceAngle + step) % 181;
    servoHelice.write(heliceAngle);
  }
}

void atualizarServoValvula(bool aberta) { servoValv.write(aberta ? 90 : 0); valvAberta = aberta; }

void setLeds(bool r, bool g, bool w) {
  digitalWrite(PIN_LED_R, r?HIGH:LOW);
  digitalWrite(PIN_LED_G, g?HIGH:LOW);
  digitalWrite(PIN_LED_W, w?HIGH:LOW);
}

void aplicarLedsNivel() {
  bool alto = nivelPct >= limAlto, baixo = nivelPct <= limBaixo;
  setLeds(alto, !alto && !baixo, baixo);
}

void aplicarComandos() {
  if (cmdModo == "auto") {
    bool nova = (nivelPct <= (float)limValvula);
    if (nova != valvAberta) { atualizarServoValvula(nova); Serial.printf("[AUTO] Válvula: %s\n", nova?"ABERTA":"FECHADA"); }
  } else {
    if (cmdValvula != valvAberta) atualizarServoValvula(cmdValvula);
  }
  heliceRpm = cmdHeliceRpm;
  aplicarLedsNivel();
}

void logicaDemo() {
  heliceRpm = 50; aplicarLedsNivel();
  if (cmdModo == "auto") { bool nova = nivelPct <= limValvula; if (nova != valvAberta) atualizarServoValvula(nova); }
}

// ════════════════════════════════════════════════════════════════
#if !MODO_DEMO

void enviarHeartbeat() {
  if (WiFi.status() != WL_CONNECTED) return;
  HTTPClient http;
  http.begin(String(API_BASE) + "/api/heartbeat.php");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-Key", API_KEY);
  http.setTimeout(2000);
  int code = http.POST("{}");
  apiOk = (code == 200);
  Serial.printf("[HB] %d\n", code);
  http.end();
}

void enviarTelemetria() {
  if (WiFi.status() != WL_CONNECTED) { reconectarWiFi(); return; }
  HTTPClient http;
  http.begin(String(API_BASE) + "/api/telemetria.php");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-Key", API_KEY);
  http.setTimeout(5000);          // aumentado de 3000 → 5000 ms

  StaticJsonDocument<256> doc;    // aumentado de 200 → 256
  doc["nivel_pct"]      = round(nivelPct * 10) / 10.0;  // força ponto decimal
  doc["valvula_aberta"] = valvAberta ? 1 : 0;
  doc["helice_rpm"]     = heliceRpm;
  doc["btn1"]           = btn1 ? 1 : 0;

  String body;
  serializeJson(doc, body);
  int code = http.POST(body);

  String resp = http.getString();   // mostra resposta no Serial
  Serial.printf("[TEL] HTTP %d | body=%s | resp=%s\n",
    code, body.c_str(), resp.c_str());

  apiOk = (code == 200);
  http.end();
}

void buscarSetpoints() {
  if (WiFi.status() != WL_CONNECTED) { reconectarWiFi(); return; }
  HTTPClient http;
  http.begin(String(API_BASE) + "/api/setpoints.php");
  http.addHeader("X-API-Key", API_KEY);
  http.setTimeout(2000);
  int code = http.GET();
  if (code == 200) {
    StaticJsonDocument<300> doc;
    if (!deserializeJson(doc, http.getString())) {
      cmdValvula   = doc["valvula_aberta"]|false;
      cmdHeliceRpm = constrain((int)(doc["helice_rpm"]|0),0,100);
      limBaixo     = doc["limiar_baixo"]  |20;
      limNormal    = doc["limiar_normal"] |40;
      limAlto      = doc["limiar_alto"]   |80;
      limValvula   = doc["limiar_valvula"]|30;
      String m = doc["modo"].as<String>(); if(m.length()) cmdModo=m;
    }
  }
  Serial.printf("[SET] %d modo=%s\n", code, cmdModo.c_str());
  http.end();
}

void conectarWiFi() {
  Serial.printf("[WiFi] %s", WIFI_SSID);
  WiFi.mode(WIFI_STA); WiFi.begin(WIFI_SSID, WIFI_PASS);
  int t=0;
  while(WiFi.status()!=WL_CONNECTED && t++<20){delay(500);Serial.print(".");}
  Serial.println(WiFi.status()==WL_CONNECTED?" OK":" FALHOU");
}

void reconectarWiFi() {
  if(WiFi.status()==WL_CONNECTED)return;
  WiFi.disconnect(); WiFi.begin(WIFI_SSID,WIFI_PASS);
  unsigned long t=millis();
  while(WiFi.status()!=WL_CONNECTED&&millis()-t<5000)delay(200);
}
#endif

// ════════════════════════════════════════════════════════════════
void oledBoot() {
  oled.clearDisplay(); oled.setTextColor(SSD1306_WHITE);
  oled.setTextSize(2); oled.setCursor(0,0); oled.println("SCADA");
  oled.setTextSize(1); oled.println("T-01 v3");
  oled.println(""); oled.print("Modo: "); oled.println(MODO_DEMO?"DEMO":"HTTP");
  oled.display(); delay(1500);
}

void atualizarOLED() {
  char buf[22]; oled.clearDisplay(); oled.setTextSize(1); oled.setTextColor(SSD1306_WHITE);
  int nR=(int)nivelPct;
  snprintf(buf,sizeof(buf),"NVL:%3d%%",nR); oled.setCursor(0,0); oled.print(buf);
  int bw=constrain((int)(nivelPct*0.45f),0,45);
  oled.drawRect(70,0,57,8,SSD1306_WHITE); oled.fillRect(71,1,bw,6,SSD1306_WHITE);
  snprintf(buf,sizeof(buf),"VLV: %-8s",valvAberta?"ABERTA":"FECHADA"); oled.setCursor(0,12); oled.print(buf);
  snprintf(buf,sizeof(buf),"HELICE: %3d%%",heliceRpm); oled.setCursor(0,24); oled.print(buf);
  snprintf(buf,sizeof(buf),"MODO: %-6s",MODO_DEMO?"DEMO":cmdModo.c_str()); oled.setCursor(0,36); oled.print(buf);
  oled.setCursor(0,50);
  if(nivelPct>=limAlto)       oled.print("!! NIVEL ALTO !!");
  else if(nivelPct<=limBaixo) oled.print("!! NIVEL BAIXO!!");
  else                         oled.print("STATUS: OK       ");
  #if !MODO_DEMO
    oled.setCursor(90,50); oled.print(apiOk?"[HB]":"[--]");
  #endif
  oled.display();
}
