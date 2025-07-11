document.addEventListener('DOMContentLoaded', () => {
    const challengeContent = document.querySelector('main'); // Cible le contenu principal de la page
    // createCtfBanner();
    // fetchCtfStatus(challengeContent);
});

/**
 * Crée et injecte la bannière du CTF en haut de la page.
 */
function createCtfBanner() {
    const banner = document.createElement('div');
    banner.id = 'ctf-banner';
    banner.className = 'ctf-banner-common';
    banner.innerHTML = `
        <p id="ctf-banner-message"></p>
        <p id="ctf-banner-countdown" class="font-orbitron"></p>
    `;
    document.body.prepend(banner);

    const style = document.createElement('style');
    style.textContent = `
        .ctf-banner-common {
            padding: 1rem;
            text-align: center;
            color: white;
            background: linear-gradient(90deg, rgba(0,255,231,0.1) 0%, rgba(0,0,0,0) 50%, rgba(0,255,231,0.1) 100%);
            border-bottom: 1px solid rgba(0, 255, 231, 0.3);
            font-size: 1.1rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        #ctf-banner-countdown { 
            font-size: 1.5rem; 
            color: #00ffe7; 
            text-shadow: 0 0 10px rgba(0, 255, 231, 0.7);
            margin-top: 0.5rem;
        }
        .content-locked { 
            filter: blur(5px);
            pointer-events: none;
            opacity: 0.6;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Récupère l'état du CTF depuis l'API et met à jour l'UI.
 * @param {HTMLElement} contentEl - L'élément de contenu à verrouiller/déverrouiller.
 */
function fetchCtfStatus(contentEl) {
    // Le backend est supprimé. Nous simulons un état 'en cours'.
    const bannerMessage = document.getElementById('ctf-banner-message');
    
    if (bannerMessage) {
        // bannerMessage.textContent = 'Mission en cours. Neutralisez la cible.';
    }

    // S'assurer que le contenu n'est jamais verrouillé.
    contentEl?.classList.remove('content-locked');
    
    // Définir une heure de fin fixe pour le compte à rebours (par exemple, 24 heures à partir de maintenant).
    const endTime = new Date(Date.now() + 24 * 60 * 60 * 1000);
    
    startClientCountdown(endTime.toISOString(), 'Temps restant');
}

let countdownInterval = null;

/**
 * Démarre un compte à rebours côté client simplifié.
 * @param {string} targetTimeString - La date/heure cible (ISO format).
 * @param {string} prefix - Le texte à afficher avant le décompte.
 */
function startClientCountdown(targetTimeString, prefix) {
    if (!targetTimeString) {
        document.getElementById('ctf-banner-countdown').textContent = '';
        return;
    }

    clearInterval(countdownInterval);

    const targetTime = new Date(targetTimeString).getTime();

    countdownInterval = setInterval(() => {
        const now = Date.now();
        const distance = targetTime - now;
        const countdownEl = document.getElementById('ctf-banner-countdown');

        if (distance < 0) {
            clearInterval(countdownInterval);
            countdownEl.textContent = 'Terminé';
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        countdownEl.textContent = `${prefix}: ${days}j ${hours}h ${minutes}m ${seconds}s`;
    }, 1000);
}
