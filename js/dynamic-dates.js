// La logique de sécurité CSRF a été supprimée car le backend n'existe plus.

export function setCopyrightYear() {
    const yearSpan = document.querySelector('.copyright-year');
    if (yearSpan) {
        yearSpan.textContent = new Date().getFullYear();
    }
}

// Initialize the copyright year
setCopyrightYear();
