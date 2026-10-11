import { getUsers } from "../api/read.js";

let usersCache = [];

/**
 * Busca na lista em memória que foi atualizada pela última chamada de renderUsers().
 * Evita outra consulta HTTP quando o usuário clica em editar ou excluir um card.
 *
 * @param {number} id Identificador numérico do usuário procurado.
 * @returns {Object|undefined} Usuário encontrado no cache, ou undefined se o id não existir.
 */
export function findUserById(id) {
  return usersCache.find((user) => user.id === id);
}

// Nunca interpole dado do usuário direto no innerHTML: um nome como
// <img src=x onerror=alert(1)> viraria código executável na página.
/**
 * Converte o valor em texto e escapa caracteres com significado especial em HTML.
 * Assim os valores do usuário aparecem como conteúdo, sem serem interpretados como tags.
 *
 * @param {*} value Valor que será colocado no HTML do card.
 * @returns {string} Texto com &, <, >, aspas duplas e simples convertidos em entidades HTML.
 */
function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

/**
 * Consulta a API, atualiza o cache e substitui o conteúdo da seção de usuários.
 * Exibe uma mensagem para lista vazia ou cria um card com botões para cada usuário.
 *
 * @param {string} apiUrl URL usada por getUsers() para consultar o backend.
 * @returns {Promise<void>} Conclui após atualizar o DOM; não retorna a lista.
 * @throws {Error} Propaga falhas da consulta para quem chamou a função.
 */
export async function renderUsers(apiUrl) {
  const users = await getUsers(apiUrl);
  usersCache = users;
  const usersSection = document.getElementById("users");

  if (users.length === 0) {
    usersSection.innerHTML = '<p class="text-muted">No users found.</p>';
    return;
  }

  usersSection.innerHTML = "";

  // O callback recebe cada usuário, monta seu card com valores escapados e o anexa ao DOM.
  users.forEach((user) => {
    const userDiv = document.createElement("div");
    userDiv.classList.add("col-md-3");

    userDiv.innerHTML = `
            <div class="card user-card h-100" id="${escapeHtml(user.id)}">
                <div class="card-body">
                    <h5 class="card-title">${escapeHtml(user.name)}</h5>
                    <p class="card-text mb-1"><strong>Age:</strong> ${escapeHtml(user.age)}</p>
                    <p class="card-text"><strong>Email:</strong> ${escapeHtml(user.email)}</p>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-sm btn-outline-dark flex-fill" data-action="edit">Edit</button>
                    <button class="btn btn-sm btn-outline-danger flex-fill" data-action="delete">Delete</button>
                </div>
            </div>
        `;

    usersSection.appendChild(userDiv);
  });
}
