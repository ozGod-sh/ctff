import { setCopyrightYear } from './dynamic-dates.js';

document.addEventListener('DOMContentLoaded', () => {
    setCopyrightYear();
    // --- Elements ---
    const flagForm = document.getElementById('flagForm');
    if (!flagForm) return;

    const flagInput = document.getElementById('flagInput');
    const flagError = document.getElementById('flagError');
    const flagSuccess = document.getElementById('flagSuccess');
    const submitButton = flagForm.querySelector('button[type="submit"]');
    const showHintBtn = document.getElementById('showHintBtn');
    const popupIndice = document.getElementById('popupIndice');

    // --- Hint Popup Logic ---
    if (showHintBtn && popupIndice) {
        showHintBtn.addEventListener('click', (e) => {
            e.preventDefault();
            popupIndice.style.display = 'flex';
        });

        const closeHintBtn = popupIndice.querySelector('button');
        if (closeHintBtn) {
            closeHintBtn.addEventListener('click', () => {
                popupIndice.style.display = 'none';
            });
        }
    }

    // --- Form Submission Logic ---
    flagForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const flag = flagInput.value.trim();
        if (!flag) return;

        // Reset UI state
        flagError.classList.add('hidden');
        flagSuccess.classList.add('hidden');
        submitButton.disabled = true;
        submitButton.innerHTML = `<i class="ri-loader-4-line animate-spin mr-2"></i> Vérification...`;

        try {
            // Simulate backend response
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            // Mock validation - replace with actual flag validation logic
            const mockResponse = {
                success: flag.toLowerCase() === 'flag{reconnaissance_reussie}',
                message: 'Défi 1 terminé avec succès ! Redirection vers le dashboard...',
                error: 'Drapeau incorrect. Vérifiez votre réponse.'
            };

            if (mockResponse.success) {
                flagSuccess.classList.remove('hidden');
                const successMessageSpan = flagSuccess.querySelector('span.text-primary');
                if(successMessageSpan) {
                    successMessageSpan.textContent = mockResponse.message;
                }
                flagInput.disabled = true;
                submitButton.innerHTML = `<i class="ri-check-double-line mr-2"></i> Défi validé`;
                setTimeout(() => window.location.href = 'challenges.html', 3000);
            } else {
                flagError.textContent = mockResponse.error;
                flagError.classList.remove('hidden');
                submitButton.disabled = false;
                submitButton.innerHTML = `<i class="ri-check-double-line mr-2"></i> Valider`;
            }
        } catch (error) {
            flagError.textContent = 'Une erreur est survenue.';
            flagError.classList.remove('hidden');
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="ri-check-double-line mr-2"></i> Valider`;
        }
    });
});
