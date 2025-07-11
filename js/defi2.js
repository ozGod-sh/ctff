import { setCopyrightYear } from './dynamic-dates.js';
import { showToast } from './ui.js';

// --- UI Functions specific to this challenge ---
function showSuccessAnimation() {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-green-900/20 z-[9999] flex items-center justify-center backdrop-blur-sm';
    overlay.innerHTML = `<div class="text-center font-orbitron text-3xl md:text-5xl text-primary animate-pulse bg-black/80 px-10 py-8 rounded-lg border-4 border-primary/40 shadow-2xl flex flex-col items-center">
      <i class="ri-shield-check-line text-6xl mb-4 animate-bounce"></i>
      <span>INFORMATION RÉVÉLÉE !</span>
    </div>`;
    document.body.appendChild(overlay);
    setTimeout(() => {
        overlay.style.transition = 'opacity 0.7s';
        overlay.style.opacity = 0;
        setTimeout(() => overlay.remove(), 700);
    }, 1500);
}

function showMotivationalAlert(message, hint) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-red-900/20 z-[9999] flex items-center justify-center backdrop-blur-sm p-4';
    overlay.innerHTML = `
      <div class="max-w-md w-full bg-black/90 border-2 border-red-500/60 rounded-xl p-8 shadow-2xl text-center flex flex-col items-center animate-pulse">
        <i class="ri-emotion-laugh-line text-6xl text-red-400 mb-4 animate-bounce"></i>
        <div class="font-orbitron text-xl text-red-400 mb-4 font-bold">BL4CKH4T ricane...</div>
        <div class="text-gray-200 mb-4 text-base">${message}</div>
        ${hint ? `<div class="text-xs text-yellow-400 mb-6 bg-yellow-400/10 p-3 rounded border border-yellow-400/30">
          <strong>Indice :</strong> ${hint}
        </div>` : ''}
        <button class="px-6 py-3 bg-red-600 text-white font-bold rounded transition hover:bg-red-700 flex items-center justify-center text-base">
          <i class="ri-sword-line mr-2"></i>Relever le défi !
        </button>
      </div>`;
    overlay.querySelector('button').addEventListener('click', () => overlay.remove());
    document.body.appendChild(overlay);
}

function showGoogleRedirect(googleUrl, searchQuery) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-blue-900/20 z-[9999] flex items-center justify-center backdrop-blur-sm p-4';
    overlay.innerHTML = `
      <div class="max-w-md w-full bg-black/90 border-2 border-blue-500/60 rounded-xl p-8 shadow-2xl text-center flex flex-col items-center">
        <i class="ri-search-line text-6xl text-blue-400 mb-4"></i>
        <div class="font-orbitron text-xl text-blue-400 mb-4 font-bold">Recherche normale détectée</div>
        <div class="text-gray-200 mb-4 text-base">"${searchQuery}" sera recherché sur Google</div>
        <div class="text-xs text-gray-400 mb-6">Ce serveur ne traite que les requêtes... spéciales 😏</div>
        <div class="flex gap-3">
          <button id="openGoogleBtn" class="px-4 py-2 bg-blue-600 text-white font-bold rounded transition hover:bg-blue-700 flex items-center justify-center text-sm">
            <i class="ri-external-link-line mr-2"></i>Ouvrir Google
          </button>
          <button id="closeRedirectBtn" class="px-4 py-2 bg-gray-600 text-white font-bold rounded transition hover:bg-gray-700 flex items-center justify-center text-sm">
            <i class="ri-close-line mr-2"></i>Fermer
          </button>
        </div>
      </div>`;
    overlay.querySelector('#openGoogleBtn').addEventListener('click', () => { window.open(googleUrl, '_blank'); overlay.remove(); });
    overlay.querySelector('#closeRedirectBtn').addEventListener('click', () => overlay.remove());
    document.body.appendChild(overlay);
}

document.addEventListener('DOMContentLoaded', () => {
    setCopyrightYear();
    const form = document.getElementById('searchForm');
    if (!form) return; // Exit if the main form isn't on the page

    // Set pseudo (static for demo)
    const pseudoSpan = document.getElementById('agentPseudo');
    if (pseudoSpan) pseudoSpan.textContent = 'Agent Spectre';

    // Form submission logic
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('searchInput');
        const query = input.value.trim();

        if (!query) {
            showToast('Veuillez entrer une requête de recherche.', 'error');
            return;
        }

        try {
            // Simulate backend response
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            // Mock validation - replace with actual query validation logic
            const mockResponse = {
                success: query.toLowerCase().includes('admin') || query.toLowerCase().includes('password'),
                error: 'too_simple',
                message: 'Trop facile ! BL4CKH4T se moque de vous.',
                hint: 'Pensez aux requêtes SQL...'
            };

            if (mockResponse.success) {
                document.getElementById('searchFlag')?.classList.remove('hidden');
                showSuccessAnimation();
                setTimeout(() => { window.location.href = 'challenges.html'; }, 2000);
            } else {
                showMotivationalAlert(mockResponse.message, mockResponse.hint);
            }
        } catch (error) {
            showToast('Une erreur est survenue.', 'error');
        }
    });

    // Hint popups logic
    const setupHintPopup = (btnId, popupId) => {
        const showBtn = document.getElementById(btnId);
        const popup = document.getElementById(popupId);

        if (showBtn && popup) {
            showBtn.addEventListener('click', (e) => {
                e.preventDefault();
                popup.style.display = 'flex';
            });

            const closeBtn = popup.querySelector('button');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    popup.style.display = 'none';
                });
            }
        }
    };

    setupHintPopup('showHintBtn1', 'popupIndice1');
    setupHintPopup('showHintBtn2', 'popupIndice2');
});
