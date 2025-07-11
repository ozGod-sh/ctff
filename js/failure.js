
document.addEventListener('DOMContentLoaded', function () {
  // La logique de session a été supprimée car le backend n'existe plus.

  // Mettre à jour le pseudo de l'agent avec une valeur par défaut
  const agentPseudoSpan = document.getElementById('agentPseudo');
  if (agentPseudoSpan) {
    agentPseudoSpan.textContent = 'Agent'; // Valeur par défaut
  }
  
  const sound = document.getElementById('failureSound');
  if (sound) {
    sound.volume = 0.25;
    sound.play().catch(()=>{});
  }
}); 
