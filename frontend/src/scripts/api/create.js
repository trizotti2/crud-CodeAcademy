import axios from "axios";

/**
 * Envia POST para cadastrar um usuário e converte a idade do formulário em número.
 * O Axios serializa os dados em JSON; await aguarda a resposta antes de continuar.
 *
 * @param {string} apiUrl URL da rota de usuários.
 * @param {{name: string, age: string|number, email: string}} user Dados do formulário.
 * @returns {Promise<Object>} Usuário criado pelo backend, incluindo seu id.
 * @throws {Error} Mensagem retornada pela API ou mensagem padrão se a requisição falhar.
 */
export async function createUser(apiUrl, { name, age, email }) {
  try {
    const response = await axios.post(apiUrl, {
      name,
      age: Number(age),
      email,
    });
    return response.data;
  } catch (error) {
    const message = error.response?.data?.error || "Failed to create user";
    throw new Error(message);
  }
}
