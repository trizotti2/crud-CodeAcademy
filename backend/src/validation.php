<?php

/**
 * Confere se o id foi informado como uma string não vazia composta apenas por dígitos.
 * A função valida o formato; não consulta o arquivo para verificar se o usuário existe.
 * Esta implementação aceita "0", apesar de a mensagem de erro mencionar inteiro positivo.
 *
 * @param mixed $id Valor recebido da query string.
 * @return string|null Mensagem de erro, ou null se o formato for aceito.
 */
function validateUserId(mixed $id): ?string
{
    if ($id === null || $id === '') {
        return 'User id is required';
    }

    // Vem sempre da query string, então é string: só aceitamos dígitos.
    if (!is_string($id) || !ctype_digit($id)) {
        return 'User id must be a positive integer';
    }

    return null;
}

/**
 * Identifica campos obrigatórios ausentes ou com valor null usando isset().
 * Reúne os nomes que faltam em uma mensagem; a validação dos valores ocorre separadamente.
 *
 * @param array $input Dados enviados pelo cliente.
 * @param array $fields Nomes dos campos que devem estar presentes.
 * @return string|null Mensagem com os campos faltantes, ou null se todos estiverem presentes.
 */
function validateRequiredFields(array $input, array $fields): ?string
{
    $missing = [];

    foreach ($fields as $field) {
        if (!isset($input[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        $verb = count($missing) === 1 ? ' is required' : ' are required';

        return implode(', ', $missing) . $verb;
    }

    return null;
}

/**
 * Valida somente os campos presentes: nome, idade e formato do e-mail.
 * O nome deve ser texto não vazio com até 100 caracteres; a idade numérica é convertida
 * para inteiro e deve ficar entre 1 e 150; o e-mail deve passar por FILTER_VALIDATE_EMAIL.
 * Não altera o array recebido; devolve o primeiro erro encontrado.
 *
 * @param array $input Campos enviados para criação ou edição.
 * @return string|null Mensagem do primeiro erro, ou null se os valores verificados forem válidos.
 */
function validateUserFields(array $input): ?string
{
    if (isset($input['name'])) {
        if (!is_string($input['name'])) {
            return 'Name must be a string';
        }

        $name = trim($input['name']);

        if ($name === '') {
            return 'Name cannot be empty';
        }

        // mb_strlen conta caracteres; strlen contaria bytes e cortaria acentos cedo demais.
        if (mb_strlen($name) > 100) {
            return 'Name must be at most 100 characters';
        }
    }

    if (isset($input['age'])) {
        if (!is_numeric($input['age'])) {
            return 'Age must be a number';
        }

        $age = (int) $input['age'];

        if ($age < 1 || $age > 150) {
            return 'Age must be between 1 and 150';
        }
    }

    if (isset($input['email'])) {
        if (!is_string($input['email'])) {
            return 'Email must be a string';
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }
    }

    return null;
}