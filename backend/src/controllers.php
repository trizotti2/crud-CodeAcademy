<?php

require_once __DIR__ . '/services.php';

/**
 * Transforma o resultado de um serviço em uma resposta HTTP com corpo JSON.
 * Recebe status e data (sucesso) ou error (falha); define o status antes de escrever o corpo.
 *
 * @param array $result Resultado produzido por services.php.
 * @return void Não devolve um valor ao PHP; envia o JSON ao cliente com echo.
 */
function respond(array $result): void
{
    http_response_code($result['status']);

    if (isset($result['error'])) {
        echo json_encode(['error' => $result['error']]);
    } else {
        echo json_encode($result['data']);
    }
}

/**
 * Trata uma exceção inesperada sem expor detalhes internos ao navegador.
 * Registra a exceção no log e envia status 500 com uma mensagem genérica em JSON.
 *
 * @param \Throwable $e Exceção ou erro capturado pelo controlador.
 * @return void Envia a resposta de erro ao cliente.
 */
function respondServerError(\Throwable $e): void
{
    // O detalhe do erro vai para o log do servidor, nunca para o cliente.
    error_log((string) $e);

    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Lê o corpo bruto da requisição e decodifica o JSON como array associativo.
 * O fluxo é: php://input → json_decode → verificação do tipo do resultado.
 *
 * @return array|null Array decodificado, ou null se o JSON for inválido ou não resultar em array.
 */
function readJsonBody(): ?array
{
    $input = json_decode(file_get_contents('php://input'), true);

    return is_array($input) ? $input : null;
}

/**
 * Atende GET /api/users: consulta o serviço e envia a lista de usuários.
 * Não recebe parâmetros; eventuais exceções são convertidas em resposta 500.
 *
 * @return void Envia o resultado de getAllUsers() ao cliente.
 */
function handleGet(): void
{
    try {
        respond(getAllUsers());
    } catch (\Throwable $e) {
        respondServerError($e);
    }
}

/**
 * Atende POST /api/users: lê o JSON, pede ao serviço para criar e envia o resultado.
 * Não recebe argumentos; os dados vêm do corpo HTTP por readJsonBody().
 *
 * @return void Envia o usuário criado (201), erro de validação (400) ou falha interna (500).
 */
function handlePost(): void
{
    try {
        respond(createUser(readJsonBody()));
    } catch (\Throwable $e) {
        respondServerError($e);
    }
}

/**
 * Atende PUT /api/users?id=... para atualizar os três campos do usuário.
 * Não recebe argumentos; extrai id da query string e os novos dados do corpo JSON.
 *
 * @return void Envia o resultado de editUser() ou uma resposta 500 em caso de exceção.
 */
function handlePut(): void
{
    try {
        respond(editUser($_GET['id'] ?? null, readJsonBody()));
    } catch (\Throwable $e) {
        respondServerError($e);
    }
}

/**
 * Atende PATCH /api/users?id=... para alterar somente os campos enviados.
 * Extrai id da query string e o JSON do corpo; partial: true dispensa os três campos obrigatórios.
 *
 * @return void Envia o resultado de editUser() ou uma resposta 500 em caso de exceção.
 */
function handlePatch(): void
{
    try {
        respond(editUser($_GET['id'] ?? null, readJsonBody(), partial: true));
    } catch (\Throwable $e) {
        respondServerError($e);
    }
}

/**
 * Atende DELETE /api/users?id=...: lê o id, pede a remoção ao serviço e responde.
 * Não recebe argumentos; o identificador vem de $_GET, sem corpo JSON obrigatório.
 *
 * @return void Envia o resultado de removeUser() ou uma resposta 500 em caso de exceção.
 */
function handleDelete(): void
{
    try {
        respond(removeUser($_GET['id'] ?? null));
    } catch (\Throwable $e) {
        respondServerError($e);
    }
}

/**
 * Responde quando a rota existe, mas o método HTTP não é um dos suportados.
 * Não recebe parâmetros e não chama os serviços de usuários.
 *
 * @return void Envia status 405 e uma mensagem de erro em JSON.
 */
function handleMethodNotAllowed(): void
{
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}