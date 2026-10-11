<?php

require_once __DIR__ . '/../config/config.php';

/**
 * Cria o estado inicial usado quando ainda não há dados utilizáveis no arquivo.
 * Não recebe parâmetros e não grava o arquivo por conta própria.
 *
 * @return array Lista users vazia e nextId igual a 1.
 */
function emptyData(): array
{
    return ['users' => [], 'nextId' => 1];
}

/**
 * Lê DATA_FILE, decodifica o JSON e verifica a presença de users e nextId.
 * Se o arquivo não existe, não pode ser lido ou tem estrutura inválida, usa emptyData().
 *
 * @return array Estado completo da persistência: usuários e contador do próximo id.
 */
function loadData(): array
{
    if (!is_file(DATA_FILE)) {
        return emptyData();
    }

    $content = file_get_contents(DATA_FILE);

    if ($content === false) {
        return emptyData();
    }

    $data = json_decode($content, true);

    if (!is_array($data) || !isset($data['users'], $data['nextId'])) {
        return emptyData();
    }

    return $data;
}

/**
 * Serializa o estado completo e grava o JSON no caminho definido por DATA_FILE.
 * A formatação facilita a leitura e JSON_UNESCAPED_UNICODE mantém os acentos legíveis.
 *
 * @param array $data Estado contendo users e nextId que substituirá o conteúdo do arquivo.
 * @return void Não retorna os dados nem verifica o resultado de file_put_contents().
 */
function saveData(array $data): void
{
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Serializa um ciclo ler → alterar → gravar.
 *
 * Sem isso, duas requisições simultâneas leem o mesmo estado e a segunda
 * gravação apaga a primeira. O flock faz a segunda requisição esperar.
 * O callback roda depois de obter o bloqueio; finally libera o arquivo mesmo se ocorrer erro.
 *
 * @param callable $operation Função sem argumentos que lê, altera e grava o estado.
 * @return mixed Valor devolvido pelo callback (usuário, null ou outro resultado).
 * @throws RuntimeException Se não for possível abrir o arquivo ou obter o bloqueio.
 */
function withDataLock(callable $operation): mixed
{
    // 'c' cria o arquivo se não existir e não apaga o conteúdo.
    $lock = fopen(DATA_FILE, 'c');

    if ($lock === false) {
        throw new RuntimeException('Could not open the data file');
    }

    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RuntimeException('Could not lock the data file');
        }

        return $operation();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/**
 * Executa, sob bloqueio, a leitura do estado, a inclusão do usuário e a gravação.
 * Usa nextId como id do novo registro e incrementa o contador para a próxima inclusão.
 *
 * @param array $user Campos name, age e email já validados pelo serviço.
 * @return array Usuário persistido, incluindo o id gerado.
 */
function insertUser(array $user): array
{
    // use captura $user; o callback sem argumentos devolve o novo usuário com id.
    return withDataLock(function () use ($user): array {
        $data = loadData();

        $id = $data['nextId'];
        $data['nextId'] = $id + 1;

        $user['id'] = $id;
        $data['users'][] = $user;

        saveData($data);

        return $user;
    });
}

/**
 * Procura o usuário pelo id e mescla somente os campos recebidos com o registro atual.
 * O ciclo de leitura, alteração e gravação acontece dentro de withDataLock().
 *
 * @param int $id Identificador do usuário procurado.
 * @param array $fields Campos já validados que serão substituídos.
 * @return array|null Usuário atualizado, ou null se nenhum registro tiver esse id.
 */
function updateUser(int $id, array $fields): ?array
{
    // use captura id e campos; o callback devolve o registro atualizado ou null.
    return withDataLock(function () use ($id, $fields): ?array {
        $data = loadData();
        $users = $data['users'];

        for ($i = 0; $i < count($users); $i++) {
            if ($users[$i]['id'] === $id) {
                $data['users'][$i] = array_merge($users[$i], $fields);
                saveData($data);

                return $data['users'][$i];
            }
        }

        return null;
    });
}

/**
 * Procura o usuário pelo id, remove sua posição da lista e grava o novo estado.
 * array_splice reorganiza os índices da lista; nextId não é reduzido nem reutilizado.
 *
 * @param int $id Identificador do usuário a excluir.
 * @return array|null Cópia do usuário excluído, ou null se o id não for encontrado.
 */
function deleteUser(int $id): ?array
{
    // use captura o id; o callback devolve o registro removido ou null.
    return withDataLock(function () use ($id): ?array {
        $data = loadData();
        $users = $data['users'];

        for ($i = 0; $i < count($users); $i++) {
            if ($users[$i]['id'] === $id) {
                $user = $users[$i];
                array_splice($users, $i, 1);
                $data['users'] = $users;
                saveData($data);

                return $user;
            }
        }

        return null;
    });
}
