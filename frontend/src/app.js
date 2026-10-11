import { renderUsers, findUserById } from "./scripts/dom/render.js";
import { createUser } from "./scripts/api/create.js";
import { deleteUser } from "./scripts/api/delete.js";
import { updateUser, patchUser } from "./scripts/api/update.js";

// Fluxo da interface: evento → função HTTP com Axios → API PHP → nova renderização.
// VITE_API_URL permite configurar a rota; sem ela, usa a API local na porta 8000.
const apiUrl =
  import.meta.env.VITE_API_URL ?? "http://localhost:8000/api/users";

// Referências do DOM
const form = document.getElementById("create-user-form");
const formError = document.getElementById("form-error");
const formTitle = document.getElementById("form-title");
const submitBtn = form.querySelector('button[type="submit"]');
const cancelBtn = document.getElementById("cancel-edit");
const usersSection = document.getElementById("users");

// Estado de edição
let editingId = null;
let originalUser = null;

// --- Helpers de erro ---
/**
 * Exibe uma mensagem na área de erro do formulário usando textContent.
 *
 * @param {string} message Texto do erro a apresentar ao usuário.
 * @returns {void} Atualiza o DOM; não devolve um valor.
 */
function showError(message) {
  formError.textContent = message;
  formError.classList.remove("d-none");
}

/**
 * Oculta a área de erro e apaga a mensagem anterior antes de um novo envio.
 * Não recebe parâmetros.
 *
 * @returns {void} Atualiza o DOM; não devolve um valor.
 */
function hideError() {
  formError.classList.add("d-none");
  formError.textContent = "";
}

// --- Helpers de edição ---
/**
 * Localiza o card do botão clicado e busca seus dados no cache de usuários.
 * Converte o id do elemento HTML, que é texto, em número antes da busca.
 *
 * @param {HTMLElement} button Botão de edição ou exclusão dentro de um card.
 * @returns {Object|undefined} Usuário correspondente ao card, conforme findUserById().
 */
function getUserFromCard(button) {
  const card = button.closest(".user-card");
  return findUserById(Number(card.id));
}

/**
 * Preenche o formulário com o usuário escolhido e ativa o estado de edição.
 * Guarda uma cópia dos valores originais para identificar os campos alterados no envio.
 *
 * @param {Object} user Usuário selecionado, com id, name, age e email.
 * @returns {void} Atualiza estado, campos, título, botões e foco do formulário.
 */
function enterEditMode(user) {
  editingId = user.id;
  originalUser = { ...user };
  document.getElementById("name").value = user.name;
  document.getElementById("age").value = user.age;
  document.getElementById("email").value = user.email;
  formTitle.textContent = "Edit User";
  submitBtn.textContent = "Update";
  cancelBtn.style.display = "";
  document.getElementById("name").focus();
}

/**
 * Limpa o estado de edição e devolve o formulário ao modo de criação.
 * Não recebe parâmetros; também é usada como callback do botão de cancelar.
 *
 * @returns {void} Restaura título e botões e limpa os campos do formulário.
 */
function exitEditMode() {
  editingId = null;
  originalUser = null;
  formTitle.textContent = "Create User";
  submitBtn.textContent = "Create";
  cancelBtn.style.display = "none";
  form.reset();
}

cancelBtn.addEventListener("click", exitEditMode);

// --- Delegação de eventos nos cards ---
/**
 * Callback de clique: recebe um MouseEvent e identifica a ação por data-action.
 * Editar preenche o formulário; excluir pede confirmação, aguarda DELETE e recarrega os cards.
 * Retorna Promise<void>; os erros da exclusão são exibidos por showError().
 * A escuta no contêiner também atende os botões recriados por renderUsers().
 */
usersSection.addEventListener("click", async (event) => {
  const { target } = event;

  if (target.dataset.action === "edit") {
    enterEditMode(getUserFromCard(target));
  }

  if (target.dataset.action === "delete") {
    const user = getUserFromCard(target);
    if (!confirm("Are you sure you want to delete this user?")) return;

    try {
      await deleteUser(apiUrl, user.id);
      if (editingId === user.id) exitEditMode();
      await renderUsers(apiUrl);
    } catch (error) {
      showError(error.message);
    }
  }
});

// --- Submit: cria ou edita ---
/**
 * Callback de envio: recebe um SubmitEvent e impede o recarregamento padrão da página.
 * Sem editingId, cria via POST. Em edição, compara os valores com originalUser:
 * três campos alterados usam PUT; parte deles usa PATCH; nenhuma mudança evita a requisição.
 * Retorna Promise<void>; após salvar, limpa o formulário e consulta a lista novamente.
 * Falhas são capturadas e exibidas na área de erro.
 */
form.addEventListener("submit", async (event) => {
  event.preventDefault();

  const name = document.getElementById("name").value;
  const age = document.getElementById("age").value;
  const email = document.getElementById("email").value;

  hideError();

  try {
    if (editingId !== null) {
      // MODO EDIÇÃO — descobre o que mudou
      const changed = {};
      if (name !== originalUser.name) changed.name = name;
      if (Number(age) !== originalUser.age) changed.age = age;
      if (email !== originalUser.email) changed.email = email;

      if (Object.keys(changed).length === 0) {
        exitEditMode();
        return;
      }

      const allChanged = Object.keys(changed).length === 3;
      if (allChanged) {
        await updateUser(apiUrl, editingId, { name, age, email });
      } else {
        await patchUser(apiUrl, editingId, changed);
      }
    } else {
      // MODO CRIAÇÃO
      await createUser(apiUrl, { name, age, email });
    }

    exitEditMode();
    await renderUsers(apiUrl);
  } catch (error) {
    showError(error.message);
  }
});

// --- Primeira renderização ---
/**
 * Callback executado quando o HTML foi carregado e analisado pelo navegador.
 * Não utiliza o evento recebido; consulta a API para montar os cards iniciais.
 * Retorna Promise<void> e exibe um erro no formulário se a primeira consulta falhar.
 */
document.addEventListener("DOMContentLoaded", async () => {
  try {
    await renderUsers(apiUrl);
  } catch (error) {
    showError(error.message);
  }
});
