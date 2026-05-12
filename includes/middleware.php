<?php
require_once __DIR__ . '/auth.php';

function requireLogin(): void
{
    if (!estaLogado()) {
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}

/**
 * Em contexto de API (apiContext=true) retorna JSON;
 * caso contrário redireciona para a página web.
 */
function requireAdmin(bool $apiContext = false): void
{
    if (!estaLogado()) {
        if ($apiContext) jsonResp(['erro' => 'Não autenticado'], 401);
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
    if (!ehAdmin()) {
        if ($apiContext) jsonResp(['erro' => 'Permissão negada — apenas admins'], 403);
        header('Location: ' . BASE_PATH . '/index.php?aviso=sem_permissao');
        exit;
    }
}

/**
 * Valida a API key do ESP32.
 * No firmware Arduino:  http.addHeader("X-API-Key", "scada_esp32_2024_secret");
 * Também aceita ?api_key= na query string.
 */
function requireApiKey(): void
{
    $chave = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
    if ($chave !== ESP32_API_KEY)
        jsonResp(['erro' => 'API key inválida ou ausente'], 401);
}

function jsonResp(array $dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
