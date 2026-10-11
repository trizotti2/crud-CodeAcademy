<?php

require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/data.php';

/**
 * Consulta a persistência e prepara o resultado que o controlador deve enviar.
 * Não recebe parâmetros; loadData() fornece o estado salvo no arquivo JSON.
 *
 * @return array Resultado com status 200 e data contendo a lista users.
 */
function getAllUsers(): array
{
    $data = loadData();

    return ['data' => ['users' => $data['users']], 'status' => 200];
}

/**
 * Valida os campos obrigatórios e seus valores antes de persistir um usuário.
 * Remove espaços nas pontas do nome, converte a idade para inteiro e chama insertUser().
 *
 * @param array|null $input Corpo JSON decodificado, com name, age e email.
 * @return array Status 201 e data com o usuário e seu id, ou status 400 e error.
 */
function createUser(?array $input): array
{
    if (!is_array($input)) {
        return ['error' => 'Invalid JSON body', 'status' => 400];
    }

    $error = validateRequiredFields($input, ['name', 'age', 'email']);
    if ($error) {
        return ['error' => $error, 'status' => 400];
    }

    $error = validateUserFields($input);
    if ($error) {
        return ['error' => $error, 'status' => 400];
    }

    $user = insertUser([
        'name' => trim($input['name']),
        'age' => (int) $input['age'],
        'email' => $input['email'],
    ]);

    return ['data' => $user, 'status' => 201];
}

/**
 * Valida o id e os dados, filtra os campos permitidos e atualiza o registro.
 * PUT exige name, age e email; PATCH aceita parte desses campos, mas exige ao menos um.
 * O id não pode ser alterado: campos fora da lista permitida são descartados.
 *
 * @param mixed $id Identificador recebido da query string, esperado como string de dígitos.
 * @param array|null $input Campos enviados no corpo JSON.
 * @param bool $partial false para PUT completo; true para PATCH parcial.
 * @return array Status 200 e data com o usuário atualizado, ou error com status 400/404.
 */
function editUser(mixed $id, ?array $input, bool $partial = false): array
{
    $error = validateUserId($id);
    if ($error) {
        return ['error' => $error, 'status' => 400];
    }

    if (!is_array($input)) {
        return ['error' => 'Invalid JSON body', 'status' => 400];
    }

    if (!$partial) {
        $error = validateRequiredFields($input, ['name', 'age', 'email']);
        if ($error) {
            return ['error' => $error, 'status' => 400];
        }
    }

    $error = validateUserFields($input);
    if ($error) {
        return ['error' => $error, 'status' => 400];
    }

    $allowed = ['name', 'age', 'email'];
    $fields = array_intersect_key($input, array_flip($allowed));

    if (empty($fields)) {
        return ['error' => 'At least one of name, age or email must be sent', 'status' => 400];
    }

    if (isset($fields['name'])) {
        $fields['name'] = trim($fields['name']);
    }

    if (isset($fields['age'])) {
        $fields['age'] = (int) $fields['age'];
    }

    $user = updateUser((int) $id, $fields);

    if ($user === null) {
        return ['error' => 'User not found', 'status' => 404];
    }

    return ['data' => $user, 'status' => 200];
}

/**
 * Valida o identificador e solicita a exclusão na camada de persistência.
 * Distingue entrada inválida (400) de um identificador sem usuário correspondente (404).
 *
 * @param mixed $id Identificador recebido da query string.
 * @return array Status 200 e data.deleted com o usuário removido, ou status 400/404 e error.
 */
function removeUser(mixed $id): array
{
    $error = validateUserId($id);
    if ($error) {
        return ['error' => $error, 'status' => 400];
    }

    $user = deleteUser((int) $id);

    if ($user === null) {
        return ['error' => 'User not found', 'status' => 404];
    }

    return ['data' => ['deleted' => $user], 'status' => 200];
}