import axios from "axios";

// PUT — substituição completa
/**
 * Envia PUT com name, age e email para atualizar todos os campos editáveis.
 * Converte a idade em número e identifica o usuário pelo id na query string.
 *
 * @param {string} apiUrl URL da rota de usuários.
 * @param {number} id Identificador do usuário em edição.
 * @param {{name: string, age: string|number, email: string}} user Novos valores completos.
 * @returns {Promise<Object>} Usuário após a atualização.
 * @throws {Error} Mensagem retornada pela API ou mensagem padrão se a atualização falhar.
 */
export async function updateUser(apiUrl, id, { name, age, email }) {
  try {
    const response = await axios.put(`${apiUrl}?id=${id}`, {
      name,
      age: Number(age),
      email,
    });
    return response.data;
  } catch (error) {
    const message = error.response?.data?.error || "Failed to update user";
    throw new Error(message);
  }
}

// PATCH — atualização parcial
/**
 * Envia PATCH com apenas os campos alterados, preservando os demais no backend.
 * Se age estiver presente, converte esse campo em número no próprio objeto recebido.
 *
 * @param {string} apiUrl URL da rota de usuários.
 * @param {number} id Identificador do usuário em edição.
 * @param {Object} fields Subconjunto de name, age e email que deve ser alterado.
 * @returns {Promise<Object>} Usuário completo após a atualização parcial.
 * @throws {Error} Mensagem retornada pela API ou mensagem padrão se a atualização falhar.
 */
export async function patchUser(apiUrl, id, fields) {
  if (fields.age !== undefined) {
    fields.age = Number(fields.age);
  }

  try {
    const response = await axios.patch(`${apiUrl}?id=${id}`, fields);
    return response.data;
  } catch (error) {
    const message = error.response?.data?.error || "Failed to patch user";
    throw new Error(message);
  }
}
