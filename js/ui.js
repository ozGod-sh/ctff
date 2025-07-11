/**
 * Module UI
 * Fournit des fonctions pour manipuler le DOM de manière sécurisée et cohérente.
 */

/**
 * Échappe les caractères HTML spéciaux pour prévenir les attaques XSS.
 * @param {any} text - Le texte à échapper. Si ce n'est pas une chaîne, il est retourné tel quel.
 * @returns {string} - Le texte échappé.
 */
export function escapeHTML(text) {
    if (typeof text !== 'string') return text;
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

const toastElement = document.getElementById('toast');

/**
 * Affiche une notification (toast) à l'écran.
 * @param {string} message - Le message à afficher.
 * @param {'success' | 'error'} type - Le type de notification ('success' ou 'error').
 */
export function showToast(message, type = 'success') {
    if (!toastElement) return;

    toastElement.textContent = message;
    toastElement.style.backgroundColor = type === 'success' ? '#00ffe7' : '#ff3e3e';
    toastElement.style.color = '#000';
    toastElement.classList.remove('hidden');
    toastElement.classList.add('fade-in');

    setTimeout(() => {
        toastElement.classList.add('hidden');
        toastElement.classList.remove('fade-in');
    }, 3000);
}

/**
 * Affiche un modal de confirmation avant d'exécuter une action critique.
 * @param {string} title - Le titre du modal.
 * @param {string} message - Le message de confirmation.
 * @returns {Promise<boolean>} - Une promesse qui se résout à `true` si l'utilisateur confirme, `false` sinon.
 */
export function showConfirmModal(title, message) {
    const modal = document.getElementById('confirmationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const confirmButton = document.getElementById('modalConfirmButton');
    const cancelButton = document.getElementById('modalCancelButton');

    if (!modal || !modalTitle || !modalMessage || !confirmButton || !cancelButton) {
        console.error('Les éléments de la modale de confirmation sont introuvables.');
        return Promise.resolve(false); // Ne pas bloquer l'UI si la modale est cassée
    }

    return new Promise((resolve) => {
        modalTitle.textContent = title;
        modalMessage.textContent = message;

        modal.classList.remove('hidden');

        const cleanup = () => {
            modal.classList.add('hidden');
            confirmButton.replaceWith(confirmButton.cloneNode(true));
            cancelButton.replaceWith(cancelButton.cloneNode(true));
        };

        confirmButton.onclick = () => {
            cleanup();
            resolve(true);
        };

        cancelButton.onclick = () => {
            cleanup();
            resolve(false);
        };
    });
}
