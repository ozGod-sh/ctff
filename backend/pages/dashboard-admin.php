<?php
/**
 * Dashboard Admin - Opération PHÉNIX
 * Interface administrative complète avec gestion utilisateurs et analytics
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Admin.php';

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
$admin = new Admin();

// Vérifier l'authentification et les droits admin
if (!$user->isLoggedIn() || !$user->isAdmin()) {
    header('Location: /backend/pages/login.php');
    exit;
}

// Obtenir les données admin
$currentUser = $user->getCurrentUser();
$globalStats = $admin->getGlobalStats();
$analyticsData = $admin->getAnalyticsData('7d');
$recentLogs = $admin->getAdminLogs(10);

// Générer token CSRF pour les actions
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin – Opération PHÉNIX</title>
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
      background: rgba(0, 0, 0, 0.6);
    }
    
    .card-glow:hover {
      border-color: #00ffe7;
      box-shadow: 0 0 20px rgba(0, 255, 231, 0.3);
      transform: translateY(-2px);
    }
    
    .stat-card {
      background: linear-gradient(135deg, rgba(0, 255, 231, 0.1), rgba(0, 0, 0, 0.8));
      border: 1px solid rgba(0, 255, 231, 0.4);
    }
    
    .stat-card:hover {
      box-shadow: 0 0 30px rgba(0, 255, 231, 0.4);
    }
    
    .admin-button {
      background: linear-gradient(45deg, #00ffe7, #00d4b8);
      color: #000;
      font-weight: bold;
      transition: all 0.3s ease;
    }
    
    .admin-button:hover {
      background: linear-gradient(45deg, #00d4b8, #00ffe7);
      transform: scale(1.05);
    }
    
    .danger-button {
      background: linear-gradient(45deg, #ef4444, #dc2626);
      color: white;
    }
    
    .danger-button:hover {
      background: linear-gradient(45deg, #dc2626, #b91c1c);
    }
    
    .data-table {
      background: rgba(0, 0, 0, 0.8);
      border: 1px solid rgba(0, 255, 231, 0.3);
    }
    
    .data-table th {
      background: rgba(0, 255, 231, 0.1);
      color: #00ffe7;
      border-bottom: 1px solid rgba(0, 255, 231, 0.3);
    }
    
    .data-table tr:hover {
      background: rgba(0, 255, 231, 0.05);
    }
    
    .modal {
      background: rgba(0, 0, 0, 0.9);
      backdrop-filter: blur(10px);
    }
    
    .modal-content {
      background: linear-gradient(135deg, rgba(10, 14, 23, 0.95), rgba(0, 0, 0, 0.9));
      border: 1px solid rgba(0, 255, 231, 0.4);
    }
    
    .blink { animation: blink 1.2s steps(1) infinite; }
    @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0; } }
    
    .pulse-glow {
      animation: pulse-glow 2s infinite;
    }
    @keyframes pulse-glow {
      0%, 100% { box-shadow: 0 0 5px rgba(0, 255, 231, 0.3); }
      50% { box-shadow: 0 0 20px rgba(0, 255, 231, 0.6); }
    }
    
    /* Styles spécifiques pour les graphiques */
    .chart-container {
      background: rgba(0, 0, 0, 0.7);
      border: 1px solid rgba(0, 255, 231, 0.3);
      border-radius: 8px;
      padding: 1rem;
    }
  </style>
</head>
<body class="min-h-screen bg-[#0a0e17] text-gray-200 cyber-grid">
  <!-- Header -->
  <header class="w-full py-4 px-6 border-b border-primary/30 flex justify-between items-center sticky top-0 z-10 backdrop-blur-sm bg-[#0a0e17]/80">
    <div class="flex items-center space-x-6">
      <a href="/index.html" class="orbitron text-sm md:text-base hover:underline">CTF by CYBERSEC-BJ.TECH</a>
      <div class="hidden md:flex items-center space-x-4">
        <i class="ri-shield-star-line text-primary"></i>
        <span class="orbitron text-primary">Admin <?= htmlspecialchars($currentUser['pseudo']) ?></span>
      </div>
    </div>
    <div class="flex items-center space-x-6">
      <div class="hidden md:flex items-center space-x-4">
        <div class="text-red-500 orbitron font-bold blink text-sm md:text-base">CONFIDENTIEL</div>
        <div class="h-4 w-px bg-gray-500"></div>
        <div class="text-primary orbitron text-sm md:text-base">OPÉRATION PHÉNIX - ADMIN</div>
      </div>
      <div class="flex items-center bg-black/50 px-3 py-1 rounded border border-primary/30">
        <i class="ri-time-line mr-2 text-primary"></i>
        <div id="countdown" class="orbitron text-sm md:text-base">--:--:--</div>
      </div>
      <div class="flex items-center space-x-2">
        <a href="/backend/pages/dashboard-user.php" class="text-gray-400 hover:text-primary transition" title="Vue utilisateur">
          <i class="ri-user-line text-xl"></i>
        </a>
        <a href="/backend/pages/leaderboard.php" class="text-primary hover:text-white transition" title="Leaderboard">
          <i class="ri-trophy-line text-xl"></i>
        </a>
        <button onclick="logout()" class="text-gray-400 hover:text-white transition" title="Déconnexion">
          <i class="ri-logout-box-line text-xl"></i>
        </button>
      </div>
    </div>
  </header>

  <main class="container mx-auto px-4 py-8">
    <!-- Titre et navigation -->
    <div class="text-center mb-8">
      <h1 class="orbitron text-3xl md:text-4xl text-primary mb-2">
        Centre de Commandement - Opération PHÉNIX
      </h1>
      <p class="text-gray-400 max-w-3xl mx-auto">
        Interface d'administration pour la gestion complète de la plateforme CTF. Surveillance, contrôle et analytics temps réel.
      </p>
    </div>

    <!-- Navigation Admin -->
    <div class="flex flex-wrap justify-center gap-4 mb-8">
      <button onclick="showSection('overview')" class="admin-button px-6 py-2 rounded transition" id="btn-overview">
        <i class="ri-dashboard-line mr-2"></i>Vue d'ensemble
      </button>
      <button onclick="showSection('users')" class="admin-button px-6 py-2 rounded transition" id="btn-users">
        <i class="ri-user-settings-line mr-2"></i>Gestion Users
      </button>
      <button onclick="showSection('challenges')" class="admin-button px-6 py-2 rounded transition" id="btn-challenges">
        <i class="ri-shield-keyhole-line mr-2"></i>Gestion Défis
      </button>
      <button onclick="showSection('analytics')" class="admin-button px-6 py-2 rounded transition" id="btn-analytics">
        <i class="ri-bar-chart-line mr-2"></i>Analytics
      </button>
      <button onclick="showSection('logs')" class="admin-button px-6 py-2 rounded transition" id="btn-logs">
        <i class="ri-file-list-line mr-2"></i>Logs & Sécurité
      </button>
      <button onclick="showSection('system')" class="admin-button px-6 py-2 rounded transition" id="btn-system">
        <i class="ri-settings-line mr-2"></i>Système
      </button>
    </div>

    <!-- Section Vue d'ensemble -->
    <div id="section-overview" class="admin-section">
      <!-- Statistiques principales -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total utilisateurs -->
        <div class="stat-card rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Total Utilisateurs</p>
              <p class="text-2xl font-bold text-primary orbitron"><?= $globalStats['users']['total_users'] ?></p>
              <p class="text-xs text-green-400">+<?= $globalStats['users']['new_this_week'] ?> cette semaine</p>
            </div>
            <i class="ri-user-3-line text-3xl text-primary"></i>
          </div>
        </div>

        <!-- Défis actifs -->
        <div class="stat-card rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Défis Actifs</p>
              <p class="text-2xl font-bold text-yellow-400 orbitron"><?= $globalStats['challenges']['active_challenges'] ?></p>
              <p class="text-xs text-gray-400"><?= $globalStats['challenges']['total_points_available'] ?> pts total</p>
            </div>
            <i class="ri-shield-check-line text-3xl text-yellow-400"></i>
          </div>
        </div>

        <!-- Soumissions 24h -->
        <div class="stat-card rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Soumissions 24h</p>
              <p class="text-2xl font-bold text-blue-400 orbitron"><?= $globalStats['submissions']['submissions_24h'] ?></p>
              <p class="text-xs text-green-400"><?= $globalStats['submissions']['success_rate'] ?>% taux succès</p>
            </div>
            <i class="ri-send-plane-line text-3xl text-blue-400"></i>
          </div>
        </div>

        <!-- Utilisateurs actifs -->
        <div class="stat-card rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm">Actifs 24h</p>
              <p class="text-2xl font-bold text-green-400 orbitron"><?= $globalStats['users']['active_24h'] ?></p>
              <p class="text-xs text-gray-400"><?= $globalStats['users']['active_users'] ?> comptes actifs</p>
            </div>
            <i class="ri-user-heart-line text-3xl text-green-400"></i>
          </div>
        </div>
      </div>

      <!-- Top utilisateurs et défis difficiles -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Top Utilisateurs -->
        <div class="card-glow rounded-lg p-6">
          <h3 class="orbitron text-xl text-primary mb-4 flex items-center">
            <i class="ri-trophy-line mr-2"></i>Top Utilisateurs
          </h3>
          <div class="space-y-3">
            <?php foreach ($globalStats['top_users'] as $index => $topUser): ?>
            <div class="flex items-center justify-between p-3 rounded bg-black/30">
              <div class="flex items-center space-x-3">
                <span class="w-6 h-6 rounded-full bg-primary text-black text-xs flex items-center justify-center font-bold">
                  <?= $index + 1 ?>
                </span>
                <span class="font-medium"><?= htmlspecialchars($topUser['pseudo']) ?></span>
              </div>
              <div class="text-right">
                <div class="text-primary font-bold"><?= $topUser['total_points'] ?> pts</div>
                <div class="text-xs text-gray-400"><?= $topUser['challenges_solved'] ?> défis</div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Défis les plus difficiles -->
        <div class="card-glow rounded-lg p-6">
          <h3 class="orbitron text-xl text-primary mb-4 flex items-center">
            <i class="ri-skull-line mr-2"></i>Défis les Plus Difficiles
          </h3>
          <div class="space-y-3">
            <?php foreach ($globalStats['hardest_challenges'] as $challenge): ?>
            <div class="p-3 rounded bg-black/30">
              <div class="flex justify-between items-start mb-2">
                <span class="font-medium"><?= htmlspecialchars($challenge['name']) ?></span>
                <span class="text-primary font-bold"><?= $challenge['points'] ?> pts</span>
              </div>
              <div class="flex justify-between text-xs text-gray-400">
                <span><?= $challenge['total_attempts'] ?> tentatives</span>
                <span class="text-red-400"><?= $challenge['success_rate'] ?>% réussite</span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Activité récente -->
      <div class="card-glow rounded-lg p-6">
        <h3 class="orbitron text-xl text-primary mb-4 flex items-center">
          <i class="ri-pulse-line mr-2"></i>Activité Temps Réel
        </h3>
        <div id="recent-activity" class="space-y-2">
          <?php foreach ($globalStats['recent_activity'] as $activity): ?>
          <div class="flex items-center justify-between p-2 rounded bg-black/20 text-sm">
            <div class="flex items-center space-x-3">
              <i class="ri-<?= $activity['is_correct'] ? 'check' : 'close' ?>-line text-<?= $activity['is_correct'] ? 'green' : 'red' ?>-400"></i>
              <span><?= htmlspecialchars($activity['pseudo']) ?></span>
              <span class="text-gray-400"><?= htmlspecialchars($activity['target']) ?></span>
            </div>
            <span class="text-gray-500"><?= date('H:i', strtotime($activity['timestamp'])) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Section Gestion Utilisateurs -->
    <div id="section-users" class="admin-section hidden">
      <div class="flex justify-between items-center mb-6">
        <h2 class="orbitron text-2xl text-primary">Gestion des Utilisateurs</h2>
        <button onclick="openUserModal()" class="admin-button px-4 py-2 rounded">
          <i class="ri-user-add-line mr-2"></i>Nouvel Utilisateur
        </button>
      </div>

      <!-- Filtres et recherche -->
      <div class="card-glow rounded-lg p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-center">
          <input type="text" id="user-search" placeholder="Rechercher un utilisateur..." 
                 class="px-4 py-2 bg-black/50 border border-primary/30 rounded text-white">
          <select id="user-role-filter" class="px-4 py-2 bg-black/50 border border-primary/30 rounded text-white">
            <option value="">Tous les rôles</option>
            <option value="user">Utilisateur</option>
            <option value="admin">Administrateur</option>
          </select>
          <select id="user-status-filter" class="px-4 py-2 bg-black/50 border border-primary/30 rounded text-white">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
          </select>
          <button onclick="loadUsers()" class="admin-button px-4 py-2 rounded">
            <i class="ri-search-line mr-2"></i>Filtrer
          </button>
          <button onclick="exportUsers()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            <i class="ri-download-line mr-2"></i>Export CSV
          </button>
        </div>
      </div>

      <!-- Table des utilisateurs -->
      <div class="data-table rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="text-left">
                <th class="p-4">ID</th>
                <th class="p-4">Pseudo</th>
                <th class="p-4">Email</th>
                <th class="p-4">Rôle</th>
                <th class="p-4">Points</th>
                <th class="p-4">Défis</th>
                <th class="p-4">Statut</th>
                <th class="p-4">Actions</th>
              </tr>
            </thead>
            <tbody id="users-table-body">
              <!-- Chargé dynamiquement -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex justify-center mt-6">
        <div id="users-pagination" class="flex space-x-2">
          <!-- Chargé dynamiquement -->
        </div>
      </div>
    </div>

    <!-- Section Gestion Défis -->
    <div id="section-challenges" class="admin-section hidden">
      <div class="flex justify-between items-center mb-6">
        <h2 class="orbitron text-2xl text-primary">Gestion des Défis</h2>
        <button onclick="recalculateScores()" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
          <i class="ri-refresh-line mr-2"></i>Recalculer Scores
        </button>
      </div>

      <!-- Table des défis -->
      <div class="data-table rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="text-left">
                <th class="p-4">Défi</th>
                <th class="p-4">Difficulté</th>
                <th class="p-4">Points</th>
                <th class="p-4">Tentatives</th>
                <th class="p-4">Réussites</th>
                <th class="p-4">Taux Succès</th>
                <th class="p-4">Statut</th>
                <th class="p-4">Actions</th>
              </tr>
            </thead>
            <tbody id="challenges-table-body">
              <!-- Chargé dynamiquement -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Section Analytics -->
    <div id="section-analytics" class="admin-section hidden">
      <h2 class="orbitron text-2xl text-primary mb-6">Analytics & Rapports</h2>

      <!-- Contrôles période -->
      <div class="card-glow rounded-lg p-4 mb-6">
        <div class="flex items-center space-x-4">
          <label class="text-primary font-bold">Période :</label>
          <select id="analytics-period" onchange="updateAnalytics()" class="px-4 py-2 bg-black/50 border border-primary/30 rounded text-white">
            <option value="24h">Dernières 24h</option>
            <option value="7d" selected>7 derniers jours</option>
            <option value="30d">30 derniers jours</option>
          </select>
          <button onclick="updateAnalytics()" class="admin-button px-4 py-2 rounded">
            <i class="ri-refresh-line mr-2"></i>Actualiser
          </button>
        </div>
      </div>

      <!-- Graphiques -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Graphique soumissions -->
        <div class="chart-container">
          <h3 class="text-primary font-bold mb-4">Soumissions par Jour</h3>
          <canvas id="submissions-chart" width="400" height="200"></canvas>
        </div>

        <!-- Graphique inscriptions -->
        <div class="chart-container">
          <h3 class="text-primary font-bold mb-4">Nouvelles Inscriptions</h3>
          <canvas id="registrations-chart" width="400" height="200"></canvas>
        </div>
      </div>
    </div>

    <!-- Section Logs -->
    <div id="section-logs" class="admin-section hidden">
      <h2 class="orbitron text-2xl text-primary mb-6">Logs & Événements de Sécurité</h2>

      <!-- Tabs logs -->
      <div class="flex space-x-4 mb-6">
        <button onclick="showLogs('admin')" class="admin-button px-4 py-2 rounded" id="tab-admin-logs">
          Logs Admin
        </button>
        <button onclick="showLogs('security')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700" id="tab-security-logs">
          Événements Sécurité
        </button>
      </div>

      <!-- Container logs -->
      <div class="data-table rounded-lg p-4">
        <div id="logs-container">
          <!-- Chargé dynamiquement -->
        </div>
      </div>
    </div>

    <!-- Section Système -->
    <div id="section-system" class="admin-section hidden">
      <h2 class="orbitron text-2xl text-primary mb-6">Administration Système</h2>

      <!-- Actions système -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Maintenance base de données -->
        <div class="card-glow rounded-lg p-6">
          <h3 class="text-primary font-bold mb-4">Base de Données</h3>
          <div class="space-y-3">
            <button onclick="cleanupSessions()" class="w-full admin-button px-4 py-2 rounded">
              <i class="ri-broom-line mr-2"></i>Nettoyer Sessions
            </button>
            <button onclick="backupDatabase()" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              <i class="ri-database-line mr-2"></i>Sauvegarde DB
            </button>
            <button onclick="recalculateScores()" class="w-full px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
              <i class="ri-calculator-line mr-2"></i>Recalculer Scores
            </button>
          </div>
        </div>

        <!-- Informations système -->
        <div class="card-glow rounded-lg p-6">
          <h3 class="text-primary font-bold mb-4">Informations Système</h3>
          <div id="system-info" class="space-y-2 text-sm">
            <!-- Chargé dynamiquement -->
          </div>
        </div>

        <!-- Actions critiques -->
        <div class="card-glow rounded-lg p-6">
          <h3 class="text-red-400 font-bold mb-4">Actions Critiques</h3>
          <div class="space-y-3">
            <button onclick="confirmAction('Mode maintenance', toggleMaintenance)" class="w-full danger-button px-4 py-2 rounded">
              <i class="ri-tools-line mr-2"></i>Mode Maintenance
            </button>
            <button onclick="confirmAction('Reset général', resetSystem)" class="w-full danger-button px-4 py-2 rounded">
              <i class="ri-restart-line mr-2"></i>Reset Système
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal Utilisateur -->
  <div id="user-modal" class="fixed inset-0 modal hidden z-50 flex items-center justify-center">
    <div class="modal-content max-w-md w-full mx-4 rounded-lg p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="orbitron text-xl text-primary" id="user-modal-title">Nouvel Utilisateur</h3>
        <button onclick="closeUserModal()" class="text-gray-400 hover:text-white">
          <i class="ri-close-line text-xl"></i>
        </button>
      </div>
      
      <form id="user-form" onsubmit="saveUser(event)">
        <input type="hidden" id="user-id" name="user_id">
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Pseudo</label>
            <input type="text" id="user-pseudo" name="pseudo" required
                   class="w-full px-4 py-2 bg-black/50 border border-primary/30 rounded text-white">
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-2">Email</label>
            <input type="email" id="user-email" name="email" required
                   class="w-full px-4 py-2 bg-black/50 border border-primary/30 rounded text-white">
          </div>
          
          <div id="password-section">
            <label class="block text-sm font-medium mb-2">Mot de passe</label>
            <input type="password" id="user-password" name="password"
                   class="w-full px-4 py-2 bg-black/50 border border-primary/30 rounded text-white">
          </div>
          
          <div>
            <label class="block text-sm font-medium mb-2">Rôle</label>
            <select id="user-role" name="role" required
                    class="w-full px-4 py-2 bg-black/50 border border-primary/30 rounded text-white">
              <option value="user">Utilisateur</option>
              <option value="admin">Administrateur</option>
            </select>
          </div>
          
          <div>
            <label class="flex items-center">
              <input type="checkbox" id="user-active" name="is_active" checked
                     class="mr-2 rounded bg-black/50 border-primary/30">
              <span class="text-sm">Compte actif</span>
            </label>
          </div>
        </div>
        
        <div class="flex space-x-4 mt-6">
          <button type="submit" class="flex-1 admin-button px-4 py-2 rounded">
            <i class="ri-save-line mr-2"></i>Sauvegarder
          </button>
          <button type="button" onclick="closeUserModal()" 
                  class="flex-1 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Annuler
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Confirmation -->
  <div id="confirm-modal" class="fixed inset-0 modal hidden z-50 flex items-center justify-center">
    <div class="modal-content max-w-sm w-full mx-4 rounded-lg p-6 text-center">
      <i class="ri-alert-line text-5xl text-red-400 mb-4"></i>
      <h3 class="orbitron text-xl text-primary mb-2" id="confirm-title">Confirmation</h3>
      <p class="text-gray-400 mb-6" id="confirm-message">Êtes-vous sûr ?</p>
      
      <div class="flex space-x-4">
        <button onclick="confirmAction()" class="flex-1 danger-button px-4 py-2 rounded" id="confirm-btn">
          Confirmer
        </button>
        <button onclick="closeConfirmModal()" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
          Annuler
        </button>
      </div>
    </div>
  </div>

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
            <i class="ri-shield-star-line text-primary mr-1"></i>
            <span>ADMIN: <?= htmlspecialchars($currentUser['pseudo']) ?></span>
          </div>
          <div class="text-xs px-2 py-1 bg-black/50 border border-primary/20 rounded flex items-center">
            <i class="ri-server-line text-primary mr-1"></i>
            <span>SERVEURS: OPÉRATIONNELS</span>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <script src="/js/ctf-main-timer.js"></script>
  <script>
    // Variables globales
    let currentSection = 'overview';
    let usersCurrentPage = 0;
    let confirmCallback = null;
    let submissionsChart = null;
    let registrationsChart = null;

    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
      showSection('overview');
      loadUsers();
      loadChallenges();
      loadSystemInfo();
      
      // Actualisation automatique des stats temps réel
      setInterval(refreshRealtimeStats, 30000); // 30 secondes
    });

    // Gestion des sections
    function showSection(section) {
      // Cacher toutes les sections
      document.querySelectorAll('.admin-section').forEach(el => el.classList.add('hidden'));
      
      // Réinitialiser les boutons
      document.querySelectorAll('[id^="btn-"]').forEach(btn => {
        btn.classList.remove('bg-primary/20');
        btn.classList.add('admin-button');
      });
      
      // Afficher la section active
      document.getElementById(`section-${section}`).classList.remove('hidden');
      document.getElementById(`btn-${section}`).classList.add('bg-primary/20');
      
      currentSection = section;
      
      // Charger les données spécifiques
      if (section === 'analytics') {
        loadAnalytics();
      } else if (section === 'logs') {
        showLogs('admin');
      }
    }

    // === GESTION UTILISATEURS ===
    
    async function loadUsers(page = 0) {
      try {
        const search = document.getElementById('user-search').value;
        const roleFilter = document.getElementById('user-role-filter').value;
        const statusFilter = document.getElementById('user-status-filter').value;
        
        const params = new URLSearchParams({
          entity: 'users',
          action: 'list',
          limit: 20,
          offset: page * 20,
          search: search
        });
        
        const response = await fetch(`/backend/api/admin.php?${params}`, {
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          displayUsers(result.data.users);
          displayUsersPagination(result.data.total, page);
          usersCurrentPage = page;
        }
      } catch (error) {
        showToast('Erreur de chargement des utilisateurs', 'error');
      }
    }

    function displayUsers(users) {
      const tbody = document.getElementById('users-table-body');
      tbody.innerHTML = '';
      
      users.forEach(user => {
        const row = document.createElement('tr');
        row.className = 'border-b border-primary/20 hover:bg-primary/5';
        
        row.innerHTML = `
          <td class="p-4">${user.id}</td>
          <td class="p-4 font-medium">${user.pseudo}</td>
          <td class="p-4">${user.email}</td>
          <td class="p-4">
            <span class="px-2 py-1 rounded text-xs ${user.role === 'admin' ? 'bg-red-500/20 text-red-400' : 'bg-blue-500/20 text-blue-400'}">
              ${user.role === 'admin' ? 'Admin' : 'User'}
            </span>
          </td>
          <td class="p-4 text-primary font-bold">${user.total_points || 0}</td>
          <td class="p-4">${user.challenges_solved || 0}</td>
          <td class="p-4">
            <span class="px-2 py-1 rounded text-xs ${user.is_active ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'}">
              ${user.is_active ? 'Actif' : 'Inactif'}
            </span>
          </td>
          <td class="p-4">
            <div class="flex space-x-2">
              <button onclick="editUser(${user.id})" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" title="Modifier">
                <i class="ri-edit-line"></i>
              </button>
              <button onclick="resetUserPassword(${user.id})" class="px-2 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700" title="Reset MDP">
                <i class="ri-key-line"></i>
              </button>
              <button onclick="deleteUser(${user.id})" class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700" title="Supprimer">
                <i class="ri-delete-bin-line"></i>
              </button>
            </div>
          </td>
        `;
        
        tbody.appendChild(row);
      });
    }

    function displayUsersPagination(total, currentPage) {
      const totalPages = Math.ceil(total / 20);
      const pagination = document.getElementById('users-pagination');
      pagination.innerHTML = '';
      
      for (let i = 0; i < totalPages; i++) {
        const button = document.createElement('button');
        button.className = `px-3 py-1 rounded ${i === currentPage ? 'bg-primary text-black' : 'bg-gray-600 text-white hover:bg-gray-500'}`;
        button.textContent = i + 1;
        button.onclick = () => loadUsers(i);
        pagination.appendChild(button);
      }
    }

    // Modal utilisateur
    function openUserModal(userId = null) {
      document.getElementById('user-modal').classList.remove('hidden');
      
      if (userId) {
        document.getElementById('user-modal-title').textContent = 'Modifier Utilisateur';
        document.getElementById('password-section').style.display = 'none';
        // Charger les données utilisateur
        // TODO: Implémenter le chargement des données
      } else {
        document.getElementById('user-modal-title').textContent = 'Nouvel Utilisateur';
        document.getElementById('password-section').style.display = 'block';
        document.getElementById('user-form').reset();
      }
    }

    function closeUserModal() {
      document.getElementById('user-modal').classList.add('hidden');
    }

    async function saveUser(event) {
      event.preventDefault();
      
      const formData = new FormData(event.target);
      const userId = formData.get('user_id');
      const isEdit = userId && userId !== '';
      
      const data = {
        pseudo: formData.get('pseudo'),
        email: formData.get('email'),
        role: formData.get('role'),
        is_active: formData.get('is_active') ? 1 : 0
      };
      
      if (!isEdit) {
        data.password = formData.get('password');
      }
      
      try {
        const url = isEdit ? 
          `/backend/api/admin.php?entity=users&action=update&id=${userId}` :
          '/backend/api/admin.php?entity=users&action=create';
          
        const method = isEdit ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $csrfToken ?>'
          },
          body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          showToast(result.message, 'success');
          closeUserModal();
          loadUsers(usersCurrentPage);
        } else {
          showToast(result.message, 'error');
        }
      } catch (error) {
        showToast('Erreur de sauvegarde', 'error');
      }
    }

    async function deleteUser(userId) {
      if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) return;
      
      try {
        const response = await fetch(`/backend/api/admin.php?entity=users&action=delete&id=${userId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          showToast(result.message, 'success');
          loadUsers(usersCurrentPage);
        } else {
          showToast(result.message, 'error');
        }
      } catch (error) {
        showToast('Erreur de suppression', 'error');
      }
    }

    async function resetUserPassword(userId) {
      if (!confirm('Réinitialiser le mot de passe de cet utilisateur ?')) return;
      
      try {
        const response = await fetch(`/backend/api/admin.php?entity=users&action=reset_password&id=${userId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $csrfToken ?>'
          },
          body: JSON.stringify({})
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          showToast(`Nouveau mot de passe: ${result.data.new_password}`, 'success');
        } else {
          showToast(result.message, 'error');
        }
      } catch (error) {
        showToast('Erreur de réinitialisation', 'error');
      }
    }

    // Export utilisateurs
    function exportUsers() {
      window.open('/backend/api/admin.php?entity=users&action=export', '_blank');
    }

    // === GESTION DÉFIS ===
    
    async function loadChallenges() {
      try {
        const response = await fetch('/backend/api/admin.php?entity=challenges&action=list', {
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          displayChallenges(result.data);
        }
      } catch (error) {
        showToast('Erreur de chargement des défis', 'error');
      }
    }

    function displayChallenges(challenges) {
      const tbody = document.getElementById('challenges-table-body');
      tbody.innerHTML = '';
      
      challenges.forEach(challenge => {
        const row = document.createElement('tr');
        row.className = 'border-b border-primary/20 hover:bg-primary/5';
        
        const difficultyColors = {
          1: 'text-green-400',
          2: 'text-yellow-400', 
          3: 'text-orange-400',
          4: 'text-red-400',
          5: 'text-purple-400'
        };
        
        row.innerHTML = `
          <td class="p-4 font-medium">${challenge.name}</td>
          <td class="p-4">
            <span class="${difficultyColors[challenge.difficulty]}">
              ${'★'.repeat(challenge.difficulty)}
            </span>
          </td>
          <td class="p-4 text-primary font-bold">${challenge.points}</td>
          <td class="p-4">${challenge.total_attempts}</td>
          <td class="p-4 text-green-400">${challenge.successful_attempts}</td>
          <td class="p-4">
            <span class="px-2 py-1 rounded text-xs ${challenge.success_rate > 50 ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'}">
              ${challenge.success_rate}%
            </span>
          </td>
          <td class="p-4">
            <span class="px-2 py-1 rounded text-xs ${challenge.is_active ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'}">
              ${challenge.is_active ? 'Actif' : 'Inactif'}
            </span>
          </td>
          <td class="p-4">
            <div class="flex space-x-2">
              <button onclick="toggleChallenge(${challenge.id}, ${!challenge.is_active})" 
                      class="px-2 py-1 ${challenge.is_active ? 'bg-red-600' : 'bg-green-600'} text-white rounded hover:opacity-80">
                <i class="ri-${challenge.is_active ? 'pause' : 'play'}-line"></i>
              </button>
              <button onclick="editChallengePoints(${challenge.id}, ${challenge.points})" 
                      class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700" title="Modifier points">
                <i class="ri-edit-line"></i>
              </button>
            </div>
          </td>
        `;
        
        tbody.appendChild(row);
      });
    }

    async function toggleChallenge(challengeId, isActive) {
      try {
        const response = await fetch(`/backend/api/admin.php?entity=challenges&action=toggle&id=${challengeId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $csrfToken ?>'
          },
          body: JSON.stringify({ is_active: isActive })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          showToast(result.message, 'success');
          loadChallenges();
        } else {
          showToast(result.message, 'error');
        }
      } catch (error) {
        showToast('Erreur de modification', 'error');
      }
    }

    function editChallengePoints(challengeId, currentPoints) {
      const newPoints = prompt('Nouveaux points:', currentPoints);
      if (newPoints && !isNaN(newPoints)) {
        updateChallengePoints(challengeId, parseInt(newPoints));
      }
    }

    async function updateChallengePoints(challengeId, points) {
      try {
        const response = await fetch(`/backend/api/admin.php?entity=challenges&action=update_points&id=${challengeId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $csrfToken ?>'
          },
          body: JSON.stringify({ points: points })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          showToast(result.message, 'success');
          loadChallenges();
        } else {
          showToast(result.message, 'error');
        }
      } catch (error) {
        showToast('Erreur de modification', 'error');
      }
    }

    async function recalculateScores() {
      if (!confirm('Recalculer tous les scores ? Cette opération peut prendre du temps.')) return;
      
      try {
        const response = await fetch('/backend/api/admin.php?entity=system&action=recalculate_scores', {
          method: 'POST',
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
      } catch (error) {
        showToast('Erreur de recalcul', 'error');
      }
    }

    // === ANALYTICS ===
    
    async function loadAnalytics() {
      const period = document.getElementById('analytics-period').value;
      
      try {
        const response = await fetch(`/backend/api/admin.php?entity=stats&action=analytics&period=${period}`, {
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          updateAnalyticsCharts(result.data);
        }
      } catch (error) {
        showToast('Erreur de chargement des analytics', 'error');
      }
    }

    function updateAnalyticsCharts(data) {
      // Graphique soumissions
      const submissionsCtx = document.getElementById('submissions-chart').getContext('2d');
      
      if (submissionsChart) {
        submissionsChart.destroy();
      }
      
      submissionsChart = new Chart(submissionsCtx, {
        type: 'line',
        data: {
          labels: data.submissions_timeline.map(item => item.date),
          datasets: [{
            label: 'Soumissions totales',
            data: data.submissions_timeline.map(item => item.total_submissions),
            borderColor: '#00ffe7',
            backgroundColor: 'rgba(0, 255, 231, 0.1)',
            tension: 0.4
          }, {
            label: 'Soumissions correctes',
            data: data.submissions_timeline.map(item => item.correct_submissions),
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(0, 255, 231, 0.1)' },
              ticks: { color: '#94a3b8' }
            },
            x: {
              grid: { color: 'rgba(0, 255, 231, 0.1)' },
              ticks: { color: '#94a3b8' }
            }
          },
          plugins: {
            legend: {
              labels: { color: '#94a3b8' }
            }
          }
        }
      });

      // Graphique inscriptions
      const registrationsCtx = document.getElementById('registrations-chart').getContext('2d');
      
      if (registrationsChart) {
        registrationsChart.destroy();
      }
      
      registrationsChart = new Chart(registrationsCtx, {
        type: 'bar',
        data: {
          labels: data.registrations_timeline.map(item => item.date),
          datasets: [{
            label: 'Nouvelles inscriptions',
            data: data.registrations_timeline.map(item => item.new_users),
            backgroundColor: 'rgba(0, 255, 231, 0.6)',
            borderColor: '#00ffe7',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(0, 255, 231, 0.1)' },
              ticks: { color: '#94a3b8' }
            },
            x: {
              grid: { color: 'rgba(0, 255, 231, 0.1)' },
              ticks: { color: '#94a3b8' }
            }
          },
          plugins: {
            legend: {
              labels: { color: '#94a3b8' }
            }
          }
        }
      });
    }

    function updateAnalytics() {
      loadAnalytics();
    }

    // === LOGS ===
    
    async function showLogs(type) {
      // Mettre à jour les tabs
      document.getElementById('tab-admin-logs').className = type === 'admin' ? 
        'admin-button px-4 py-2 rounded' : 'px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700';
      document.getElementById('tab-security-logs').className = type === 'security' ? 
        'px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700' : 'px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700';
      
      try {
        const response = await fetch(`/backend/api/admin.php?entity=logs&action=${type}&limit=50`, {
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          displayLogs(result.data, type);
        }
      } catch (error) {
        showToast('Erreur de chargement des logs', 'error');
      }
    }

    function displayLogs(logs, type) {
      const container = document.getElementById('logs-container');
      container.innerHTML = '';
      
      if (logs.length === 0) {
        container.innerHTML = '<p class="text-gray-400 text-center py-8">Aucun log trouvé</p>';
        return;
      }
      
      logs.forEach(log => {
        const div = document.createElement('div');
        div.className = 'p-3 border-b border-primary/20 hover:bg-primary/5';
        
        if (type === 'admin') {
          div.innerHTML = `
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-1">
                  <span class="font-medium text-primary">${log.admin_pseudo}</span>
                  <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded text-xs">${log.action}</span>
                </div>
                <p class="text-sm text-gray-300">${log.details}</p>
                <p class="text-xs text-gray-500 mt-1">${new Date(log.timestamp).toLocaleString('fr-FR')}</p>
              </div>
            </div>
          `;
        } else {
          div.innerHTML = `
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-1">
                  <span class="font-medium text-red-400">${log.pseudo || log.email}</span>
                  <span class="px-2 py-1 bg-red-500/20 text-red-400 rounded text-xs">${log.event_type}</span>
                </div>
                <p class="text-sm text-gray-300">Tentatives de connexion: ${log.login_attempts}</p>
                <p class="text-xs text-gray-500 mt-1">${log.last_attempt ? new Date(log.last_attempt).toLocaleString('fr-FR') : 'N/A'}</p>
              </div>
            </div>
          `;
        }
        
        container.appendChild(div);
      });
    }

    // === SYSTÈME ===
    
    async function loadSystemInfo() {
      try {
        const response = await fetch('/backend/api/admin.php?entity=system&action=system_info', {
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          displaySystemInfo(result.data);
        }
      } catch (error) {
        console.error('Erreur de chargement des infos système');
      }
    }

    function displaySystemInfo(info) {
      const container = document.getElementById('system-info');
      container.innerHTML = `
        <div class="space-y-2">
          <div class="flex justify-between">
            <span>PHP Version:</span>
            <span class="text-primary">${info.php_version}</span>
          </div>
          <div class="flex justify-between">
            <span>Heure serveur:</span>
            <span class="text-primary">${info.server_time}</span>
          </div>
          <div class="flex justify-between">
            <span>Uptime:</span>
            <span class="text-primary">${info.uptime}</span>
          </div>
          <div class="flex justify-between">
            <span>Mémoire utilisée:</span>
            <span class="text-primary">${info.memory_usage}</span>
          </div>
          <div class="flex justify-between">
            <span>Pic mémoire:</span>
            <span class="text-primary">${info.memory_peak}</span>
          </div>
          <div class="flex justify-between">
            <span>Taille DB:</span>
            <span class="text-primary">${info.database_size}</span>
          </div>
        </div>
      `;
    }

    async function cleanupSessions() {
      try {
        const response = await fetch('/backend/api/admin.php?entity=system&action=cleanup_sessions', {
          method: 'POST',
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
      } catch (error) {
        showToast('Erreur de nettoyage', 'error');
      }
    }

    async function backupDatabase() {
      try {
        const response = await fetch('/backend/api/admin.php?entity=system&action=backup_db', {
          method: 'POST',
          headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
          }
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
          showToast(`Sauvegarde créée: ${result.data.backup_file}`, 'success');
        } else {
          showToast(result.message, 'error');
        }
      } catch (error) {
        showToast('Erreur de sauvegarde', 'error');
      }
    }

    // === UTILITAIRES ===
    
    function refreshRealtimeStats() {
      // Actualiser l'activité récente et les stats temps réel
      if (currentSection === 'overview') {
        // TODO: Implémenter l'actualisation temps réel
      }
    }

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

    // Modal de confirmation
    function confirmAction(message, callback) {
      document.getElementById('confirm-message').textContent = message;
      document.getElementById('confirm-modal').classList.remove('hidden');
      confirmCallback = callback;
    }

    function closeConfirmModal() {
      document.getElementById('confirm-modal').classList.add('hidden');
      confirmCallback = null;
    }

    function executeConfirmAction() {
      if (confirmCallback) {
        confirmCallback();
        closeConfirmModal();
      }
    }

    // Toast notifications
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

    // Animations d'entrée
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.card-glow, .stat-card');
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
  </script>
</body>
</html>