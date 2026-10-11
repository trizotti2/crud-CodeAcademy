<?php

require_once __DIR__ . '/../config/config.php';

// Entrada da API: configura CORS e o formato JSON antes de encaminhar a requisição.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

in_array($origin, $allowedOrigins) ?
    header("Access-Control-Allow-Origin: $origin") : null;
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// OPTIONS é a consulta prévia de CORS do navegador: responde sem executar o CRUD.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Retira a query string para comparar apenas o caminho; o id permanece disponível em $_GET.
$uri = strtok($_SERVER['REQUEST_URI'], '?');

match ($uri) {
    '/api/users' => require __DIR__ . '/../src/api.php',
    default => notFound(),
};

/**
 * Responde quando o caminho solicitado não corresponde à rota /api/users.
 * Não recebe parâmetros e não acessa a camada de persistência.
 *
 * @return void Envia status 404 e a mensagem Not found em JSON.
 */
function notFound(): void
{
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}