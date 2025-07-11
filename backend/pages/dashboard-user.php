<?php
/**
 * Dashboard Utilisateur - Opération PHÉNIX
 * Interface principale pour les agents avec progression et défis
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Challenge.php';

// Activer les headers de sécurité
Security::setSecurityHeaders();

// Démarrer la session
session_start();

// Vérifier le mode maintenance
if (isMaintenanceMode()) {
    header('Location: /maintenance.html');
    exit;
}

// Instancier les classes
$user = new User();
$challenge = new Challenge();

// Vérifier l'authentification
if (!$user->isLoggedIn()) {
    header('Location: /backend/pages/login.php');
    exit;
}

// Obtenir les données utilisateur
$currentUser = $user->getCurrentUser();
$userStats = $user->getUserStats();
$recentSubmissions = $user->getRecentSubmissions(5);
$userChallenges = $challenge->getChallengesForUser($currentUser['id']);
$recentGlobalSubmissions = $challenge->getRecentSubmissions(10);

// Calculer la progression
$totalChallenges = count($userChallenges);
$solvedChallenges = count(array_filter($userChallenges, function($c) { return $c['is_solved']; }));
$progressPercentage = $totalChallenges > 0 ? round(($solvedChallenges / $totalChallenges) * 100) : 0;

// Générer token CSRF pour les actions
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Agent – Opération PHÉNIX</title>
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="/css/index.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .cyber-grid {
      background-image: 
        linear-gradient(rgba(0, 255, 231, 0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 255, 231, 0.1) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: grid-move 20s linear infinite;
    }
    @keyframes grid-move {
      0% { background-position: 0 0; }
      100% { background-position: 50px 50px; }
    }
    
    .card-glow {
      transition: all 0.3s ease;
      border: 1px solid rgba(0, 255, 231, 0.3);
    }
    
    .card-glow:hover {
      border-color: #00ffe7;
      box-shadow: 0 0 20px rgba(0, 255, 231, 0.3);
      transform: translateY(-2px);
    }
    
    .progress-ring {
      transform: rotate(-90deg);
    }
    
    .challenge-solved {
      background: rgba(34, 197, 94, 0.1);
      border-color: rgba(34, 197, 94, 0.3);
    }
    
    .challenge-locked {
      opacity: 0.6;
      background: rgba(107, 114, 128, 0.1);
      border-color: rgba(107, 114, 128, 0.3);
    }
    
    .difficulty-1 { border-left: 4px solid #22c55e; }
    .difficulty-2 { border-left: 4px solid #eab308; }
    .difficulty-3 { border-left: 4px solid #f97316; }
    .difficulty-4 { border-left: 4px solid #ef4444; }
    .difficulty-5 { border-left: 4px solid #8b5cf6; }
    
    .blink { animation: blink 1.2s steps(1) infinite; }
    @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0; } }
    
    .pulse-glow {
      animation: pulse-glow 2s infinite;
    }
    @keyframes pulse-glow {
      0%, 100% { box-shadow: 0 0 5px rgba(0, 255, 231, 0.3); }
      50% { box-shadow: 0 0 20px rgba(0, 255, 231, 0.6); }
    }
  </style>
</head>
<body class="min-h-screen bg-[#0a0e17] text-gray-200 cyber-grid">
  <!-- Header -->
  <header class="w-full py-4 px-6 border-b border-primary/30 flex justify-between items-center sticky top-0 z-10 backdrop-blur-sm bg-[#0a0e17]/80">
    <div class="flex items-center space-x-6">
      <a href="/index.html" class="orbitron text-sm md:text-base hover:underline">CTF by CYBERSEC-BJ.TECH</a>
      <div class="hidden md:flex items-center space-x-4">
        <i class="ri-user-line text-primary"></i>
        <span class="orbitron text-primary">Agent <?= htmlspecialchars($currentUser['pseudo']) ?></span>
      </div>
    </div>
    <div class="flex items-center space-x-6">
      <div class="hidden md:flex items-center space-x-4">
        <div class="text-red-500 orbitron font-bold blink text-sm md:text-base">CONFIDENTIEL</div>
        <div class="h-4 w-px bg-gray-500"></div>
        <div class="text-primary orbitron text-sm md:text-base">OPÉRATION PHÉNIX</div>
      </div>
      <div class="flex items-center bg-black/50 px-3 py-1 rounded border border-primary/30">
        <i class="ri-time-line mr-2 text-primary"></i>
        <div id="countdown" class="orbitron text-sm md:text-base">--:--:--</div>
      </div>
      <div class="flex items-center space-x-2">
        <a href="/backend/pages/leaderboard.php" class="text-primary hover:text-white transition">
          <i class="ri-trophy-line text-xl"></i>
        </a>
        <button onclick="logout()" class="text-gray-400 hover:text-white transition">
          <i class="ri-logout-box-line text-xl"></i>
        </button>
      </div>
    </div>
  </header>

  <main class="container mx-auto px-4 py-8">
    <!-- Titre et accueil -->
    <div class="text-center mb-8">
      <h1 class="orbitron text-3xl md:text-4xl text-primary mb-2">
        Mission en cours, Agent <?= htmlspecialchars($currentUser['pseudo']) ?>
      </h1>
      <p class="text-gray-400 max-w-2xl mx-auto">
        Votre mission : neutraliser BL4CKH4T et sauver le Bénin. Chaque défi résolu vous rapproche de la vérité.
      </p>
    </div>

    <!-- Statistiques principales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <!-- Points totaux -->
      <div class="card-glow bg-black/60 rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-400 text-sm">Points totaux</p>
            <p class="text-2xl font-bold text-primary orbitron"><?= $userStats['total_points'] ?? 0 ?></p>
          </div>
          <i class="ri-medal-line text-3xl text-primary"></i>
        </div>
      </div>

      <!-- Défis résolus -->
      <div class="card-glow bg-black/60 rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-400 text-sm">Défis résolus</p>
            <p class="text-2xl font-bold text-green-400 orbitron"><?= $solvedChallenges ?>/<?= $totalChallenges ?></p>
          </div>
          <i class="ri-check-double-line text-3xl text-green-400"></i>
        </div>
      </div>

      <!-- Rang -->
      <div class="card-glow bg-black/60 rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-400 text-sm">Classement</p>
            <p class="text-2xl font-bold text-yellow-400 orbitron"><?= $userStats['rank_position'] ?? '-' ?></p>
          </div>
          <i class="ri-trophy-line text-3xl text-yellow-400"></i>
        </div>
      </div>

      <!-- Progression -->
      <div class="card-glow bg-black/60 rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-400 text-sm">Progression</p>
            <p class="text-2xl font-bold text-blue-400 orbitron"><?= $progressPercentage ?>%</p>
          </div>
          <div class="relative w-12 h-12">
            <svg class="w-12 h-12 progress-ring">
              <circle cx="24" cy="24" r="20" fill="none" stroke="rgba(59, 130, 246, 0.3)" stroke-width="4"/>
              <circle cx="24" cy="24" r="20" fill="none" stroke="#3b82f6" stroke-width="4"
                      stroke-dasharray="<?= 2 * 3.14159 * 20 ?>"
                      stroke-dashoffset="<?= 2 * 3.14159 * 20 * (1 - $progressPercentage / 100) ?>"
                      class="transition-all duration-1000"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Défis disponibles -->
      <div class="lg:col-span-2">
        <div class="card-glow bg-black/60 rounded-lg p-6">
          <h2 class="orbitron text-xl text-primary mb-6 flex items-center">
            <i class="ri-shield-line mr-2"></i>Défis Opérationnels
          </h2>
          
          <div class="space-y-4">
            <?php foreach ($userChallenges as $ch): ?>
            <div class="card-glow <?= $ch['is_solved'] ? 'challenge-solved' : '' ?> bg-black/40 rounded-lg p-4 difficulty-<?= $ch['difficulty'] ?>">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center mb-2">
                    <h3 class="font-bold text-primary mr-2"><?= htmlspecialchars($ch['name']) ?></h3>
                    <?php if ($ch['is_solved']): ?>
                    <span class="text-xs bg-green-500/20 text-green-400 px-2 py-1 rounded">
                      <i class="ri-check-line mr-1"></i>RÉSOLU
                    </span>
                    <?php endif; ?>
                  </div>
                  
                  <p class="text-gray-400 text-sm mb-3"><?= htmlspecialchars($ch['description']) ?></p>
                  
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4 text-xs">
                      <span class="text-gray-500">
                        <i class="ri-star-line mr-1"></i><?= $ch['points'] ?> points
                      </span>
                      <span class="text-gray-500">
                        <i class="ri-bar-chart-line mr-1"></i><?= CHALLENGE_DIFFICULTY[$ch['difficulty']] ?>
                      </span>
                      <span class="text-gray-500">
                        <i class="ri-folder-line mr-1"></i><?= htmlspecialchars($ch['category']) ?>
                      </span>
                    </div>
                    
                    <?php if (!$ch['is_solved']): ?>
                    <a href="/backend/pages/challenges/defi<?= $ch['id'] ?>.php" 
                       class="px-4 py-2 bg-primary text-black font-bold rounded hover:bg-primary/80 transition text-sm">
                      <i class="ri-play-line mr-1"></i>Commencer
                    </a>
                    <?php else: ?>
                    <span class="text-green-400 text-xs">
                      Résolu le <?= date('d/m/Y', strtotime($ch['solved_at'])) ?>
                    </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar avec activités -->
      <div class="space-y-6">
        <!-- Activité récente -->
        <div class="card-glow bg-black/60 rounded-lg p-6">
          <h3 class="orbitron text-lg text-primary mb-4 flex items-center">
            <i class="ri-history-line mr-2"></i>Activité récente
          </h3>
          
          <?php if (empty($recentSubmissions)): ?>
          <p class="text-gray-400 text-sm">Aucune activité récente</p>
          <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($recentSubmissions as $submission): ?>
            <div class="flex items-center space-x-3 text-sm">
              <i class="ri-<?= $submission['is_correct'] ? 'check' : 'close' ?>-line text-<?= $submission['is_correct'] ? 'green' : 'red' ?>-400"></i>
              <div class="flex-1">
                <p class="text-gray-300"><?= htmlspecialchars($submission['challenge_name']) ?></p>
                <p class="text-gray-500 text-xs"><?= date('d/m/Y H:i', strtotime($submission['submitted_at'])) ?></p>
              </div>
              <?php if ($submission['is_correct']): ?>
              <span class="text-primary font-bold">+<?= $submission['points_earned'] ?></span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Activité globale -->
        <div class="card-glow bg-black/60 rounded-lg p-6">
          <h3 class="orbitron text-lg text-primary mb-4 flex items-center">
            <i class="ri-global-line mr-2"></i>Résolutions récentes
          </h3>
          
          <div class="space-y-3">
            <?php foreach ($recentGlobalSubmissions as $submission): ?>
            <div class="flex items-center space-x-3 text-sm">
              <i class="ri-shield-check-line text-green-400"></i>
              <div class="flex-1">
                <p class="text-gray-300"><?= htmlspecialchars($submission['pseudo']) ?></p>
                <p class="text-gray-500 text-xs"><?= htmlspecialchars($submission['challenge_name']) ?></p>
              </div>
              <span class="text-primary text-xs">+<?= $submission['points_earned'] ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Actions rapides -->
        <div class="card-glow bg-black/60 rounded-lg p-6">
          <h3 class="orbitron text-lg text-primary mb-4 flex items-center">
            <i class="ri-settings-line mr-2"></i>Actions
          </h3>
          
          <div class="space-y-3">
            <a href="/backend/pages/leaderboard.php" 
               class="block w-full px-4 py-2 bg-primary/10 border border-primary/30 text-primary rounded hover:bg-primary/20 transition text-center">
              <i class="ri-trophy-line mr-2"></i>Voir le classement
            </a>
            
            <a href="/backend/pages/profile.php" 
               class="block w-full px-4 py-2 bg-gray-500/10 border border-gray-500/30 text-gray-300 rounded hover:bg-gray-500/20 transition text-center">
              <i class="ri-user-line mr-2"></i>Mon profil
            </a>
            
            <button onclick="refreshData()" 
                    class="block w-full px-4 py-2 bg-blue-500/10 border border-blue-500/30 text-blue-400 rounded hover:bg-blue-500/20 transition text-center">
              <i class="ri-refresh-line mr-2"></i>Actualiser
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="w-full py-6 px-6 border-t border-primary/30 mt-auto text-gray-400">
    <div class="container mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-center text-center md:text-left">
        <div class="mb-4 md:mb-0">
          <p class="text-sm">&copy; <?= date('Y') ?> ctf by CYBERSEC-BJ.TECH</p>
          <div class="text-xs text-gray-500 flex items-center justify-center md:justify-start gap-2 mt-2">
            <i class="ri-git-branch-line"></i>
            <span>Créé par <a href="https://github.com/ozGod" target="_blank" rel="noopener noreferrer" class="text-primary/80 hover:text-primary transition-colors"><strong>ozGod</strong></a> pour <a href="#" class="text-primary/80 hover:text-primary font-bold transition-colors">BJWHITEHATs</a></span>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <div class="text-xs px-2 py-1 bg-black/50 border border-primary/20 rounded flex items-center">
            <i class="ri-server-line text-primary mr-1"></i>
            <span>SERVEURS: OPÉRATIONNELS</span>
          </div>
          <div class="text-xs px-2 py-1 bg-black/50 border border-primary/20 rounded flex items-center">
            <i class="ri-user-3-line text-primary mr-1"></i>
            <span>AGENT: <?= htmlspecialchars($currentUser['pseudo']) ?></span>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <script src="/js/ctf-main-timer.js"></script>
  <script>
    // Déconnexion
    async function logout() {
      try {
        const response = await fetch('/backend/api/auth.php?action=logout', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        const data = await response.json();
        if (data.status === 'success') {
          window.location.href = '/index.html';
        }
      } catch (error) {
        console.error('Erreur de déconnexion:', error);
      }
    }

    // Actualiser les données
    function refreshData() {
      window.location.reload();
    }

    // Animation d'entrée
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.card-glow');
      cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
          card.style.transition = 'all 0.6s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, index * 100);
      });
    });

    // Notification toast simple
    function showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
      } text-white font-bold`;
      toast.textContent = message;
      
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
  </script>
</body>
</html>