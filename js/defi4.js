import { setCopyrightYear } from './dynamic-dates.js';

document.addEventListener('DOMContentLoaded', () => {
    setCopyrightYear();
    
    const infiltrationForm = document.getElementById('infiltrationForm');
    if (!infiltrationForm) return;

    const commandInput = document.getElementById('commandInput');
    const outputDiv = document.getElementById('commandOutput');
    const submitButton = infiltrationForm.querySelector('button[type="submit"]');
    const showHintBtn = document.getElementById('showHintBtn');
    const popupIndice = document.getElementById('popupIndice');

    // Hint popup logic
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

    // Form submission logic
    infiltrationForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const command = commandInput.value.trim();
        if (!command) return;

        submitButton.disabled = true;
        submitButton.innerHTML = `<i class="ri-loader-4-line animate-spin mr-2"></i> Exécution...`;

        try {
            // Simulate backend response
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            // Mock command execution - replace with actual command validation logic
            const mockResponse = {
                success: command.toLowerCase().includes('ls') || command.toLowerCase().includes('cat'),
                output: command.toLowerCase().includes('ls') ? 
                    'flag{exfiltration_reussie}\nsecret.txt\nconfig.php' : 
                    command.toLowerCase().includes('cat') ? 
                    'flag{exfiltration_reussie}' : 
                    'Commande non autorisée ou invalide.',
                error: 'Commande non autorisée. Essayez une autre approche.'
            };

            if (mockResponse.success) {
                outputDiv.innerHTML = `<div class="text-green-400 font-mono text-sm">${mockResponse.output}</div>`;
                outputDiv.classList.remove('hidden');
                
                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'mt-4 p-4 bg-green-500/20 border border-green-500/30 rounded-lg text-center';
                successDiv.innerHTML = `
                    <i class="ri-shield-check-line text-green-400 text-2xl mb-2"></i>
                    <p class="text-green-400 font-bold">Exfiltration réussie !</p>
                    <p class="text-sm text-gray-300 mt-2">Redirection vers le dashboard...</p>
                `;
                outputDiv.appendChild(successDiv);
                
                setTimeout(() => window.location.href = 'challenges.html', 3000);
            } else {
                outputDiv.innerHTML = `<div class="text-red-400 font-mono text-sm">${mockResponse.error}</div>`;
                outputDiv.classList.remove('hidden');
                submitButton.disabled = false;
                submitButton.innerHTML = `<i class="ri-terminal-line mr-2"></i> Exécuter`;
            }
        } catch (error) {
            outputDiv.innerHTML = `<div class="text-red-400 font-mono text-sm">Erreur d'exécution.</div>`;
            outputDiv.classList.remove('hidden');
            submitButton.disabled = false;
            submitButton.innerHTML = `<i class="ri-terminal-line mr-2"></i> Exécuter`;
        }
    });
});
