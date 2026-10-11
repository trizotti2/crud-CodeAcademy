import axios from "axios";

/**
 * Envia DELETE com o id na query string para remover o usuário escolhido.
 *
 * @param {string} apiUrl URL da rota de usuários.
 * @param {number} id Identificador do usuário a excluir.
 * @returns {Promise<{deleted: Object}>} Corpo JSON contendo o registro excluído.
 * @throws {Error} Mensagem retornada pela API ou mensagem padrão se a exclusão falhar.
 */
export async function deleteUser(apiUrl, id) {
  try {
    const response = await axios.delete(`${apiUrl}?id=${id}`);
    return response.data;
  } catch (error) {
    const message = error.response?.data?.error || "Failed to delete user";
    throw new Error(message);
  }
}
