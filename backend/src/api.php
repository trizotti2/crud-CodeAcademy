<?php

require_once __DIR__ . '/controllers.php';

// A rota já foi escolhida em public/index.php; o método HTTP seleciona o controlador.
// Fluxo: controlador → serviço (validação) → persistência JSON → resposta do controlador.
$method = $_SERVER['REQUEST_METHOD'];

match ($method) {
    'GET' => handleGet(),
    'POST' => handlePost(),
    'PUT' => handlePut(),
    'PATCH' => handlePatch(),
    'DELETE' => handleDelete(),
    default => handleMethodNotAllowed(),
};
