/**
 * Gestion des éléments statiques (remplace les données du backend)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Remplacer les agents actifs par une valeur statique
    const activeAgentsElements = document.querySelectorAll('#activeAgents');
    activeAgentsElements.forEach(element => {
        element.textContent = '1';
    });
}); 