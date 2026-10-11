import axios from "axios";

/**
 * Envia GET e extrai a lista users do JSON devolvido pelo backend.
 *
 * @param {string} apiUrl URL da rota de usuários.
 * @returns {Promise<Array<Object>>} Lista dos usuários cadastrados.
 * @throws {Error} Mensagem retornada pela API ou mensagem padrão se a consulta falhar.
 */
export async function getUsers(apiUrl) {
  try {
    const response = await axios.get(apiUrl);
    return response.data.users;
  } catch (error) {
    const message = error.response?.data?.error || "Failed to load users";
    throw new Error(message);
  }
}
