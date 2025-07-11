 
document.addEventListener('DOMContentLoaded', function () {
    // La logique de session a été supprimée car le backend n'existe plus.

    // Mettre à jour le pseudo de l'agent avec une valeur par défaut
    const agentPseudoSpan = document.getElementById('agentPseudo');
    if (agentPseudoSpan) {
      agentPseudoSpan.textContent = 'Agent'; // Valeur par défaut
    }
    
    const reportBtn = document.getElementById('successReportBtn');
    if (reportBtn) {
        // Désactiver le bouton de rapport car le backend est supprimé
        reportBtn.disabled = true;
        reportBtn.innerHTML = '<i class="ri-file-excel-2-line mr-2"></i>Rapport non disponible';
        reportBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}); 
