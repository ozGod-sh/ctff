<?php
/**
 * Page de Connexion - Opération PHÉNIX
 * Version PHP dynamique avec authentification backend
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/User.php';

// Activer les headers de sécurité
Security::setSecurityHeaders();

// Démarrer la session
session_start();

// Vérifier le mode maintenance
if (isMaintenanceMode()) {
    header('Location: /maintenance.html');
    exit;
}

// Instancier la classe User
$user = new User();

// Rediriger si déjà connecté
if ($user->isLoggedIn()) {
    $currentUser = $user->getCurrentUser();
    $redirectUrl = $currentUser['role'] === 'admin' ? '/backend/pages/dashboard-admin.php' : '/backend/pages/dashboard-user.php';
    header("Location: $redirectUrl");
    exit;
}

// Générer token CSRF
$csrfToken = Security::generateCSRFToken();

// Variables pour les messages
$errorMessage = '';
$successMessage = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $submittedCsrfToken = $_POST['csrf_token'] ?? '';
    
    // Vérifier le token CSRF
    if (!Security::verifyCSRFToken($submittedCsrfToken)) {
        $errorMessage = 'Token de sécurité invalide';
        Security::logSecurityEvent('CSRF_VIOLATION', 'Token CSRF invalide sur login');
    } else {
        // Tentative de connexion
        $result = $user->login($email, $password);
        
        if ($result['success']) {
            // Redirection selon le rôle
            $redirectUrl = $result['user']['role'] === 'admin' ? 
                '/backend/pages/dashboard-admin.php' : 
                '/backend/pages/dashboard-user.php';
            
            header("Location: $redirectUrl");
            exit;
        } else {
            $errorMessage = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion Agent – Opération PHÉNIX</title>
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="/css/index.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
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
    
    .input-field {
      background: rgba(0, 0, 0, 0.5);
      border: 1px solid rgba(0, 255, 231, 0.3);
      transition: all 0.3s ease;
    }
    
    .input-field:focus {
      border-color: #00ffe7;
      box-shadow: 0 0 10px rgba(0, 255, 231, 0.3);
      outline: none;
    }
    
    .btn-primary {
      background: #ffffff;
      color: #000;
      font-weight: 700;
      transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
      background: #f0f0f0;
      transform: translateY(-1px);
    }
    
    .btn-secondary {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.2);
      border-color: rgba(255, 255, 255, 0.3);
    }
    
    .alert-error {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #f87171;
    }
    
    .alert-success {
      background: rgba(34, 197, 94, 0.1);
      border: 1px solid rgba(34, 197, 94, 0.3);
      color: #4ade80;
    }
  </style>
</head>
<body class="min-h-screen bg-[#0a0e17] text-gray-200 flex flex-col cyber-grid">
  <header class="w-full py-4 px-6 border-b border-primary/30 flex justify-between items-center sticky top-0 z-10 backdrop-blur-sm bg-[#0a0e17]/80">
    <div class="flex items-center">
      <a href="/index.html" class="orbitron text-sm md:text-base hover:underline">CTF by CYBERSEC-BJ.TECH</a>
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
    </div>
  </header>

  <main class="flex-1 flex flex-col items-center justify-center px-4 py-10 relative z-10">
    <div class="max-w-md w-full bg-black/70 border border-primary/30 rounded-lg p-8 shadow-lg">
      <div class="flex flex-col items-center mb-8">
        <i class="ri-user-line text-primary text-4xl mb-3"></i>
        <h2 class="orbitron text-2xl text-primary mb-2">Connexion Agent</h2>
        <span class="text-xs bg-primary/20 text-primary px-3 py-1 rounded">Authentification Sécurisée</span>
      </div>
      
      <div class="mb-6 text-xs text-gray-400 text-center">
        <span>STATUT : <span class="text-green-400 font-bold">Opérationnel</span></span> •
        <span>ACCÈS : <span class="text-primary">Autorisé</span></span> •
        <span><?= date('d/m/Y H:i') ?></span>
      </div>

      <?php if ($errorMessage): ?>
      <div class="alert-error p-3 rounded mb-4 text-sm">
        <i class="ri-error-warning-line mr-2"></i><?= htmlspecialchars($errorMessage) ?>
      </div>
      <?php endif; ?>

      <?php if ($successMessage): ?>
      <div class="alert-success p-3 rounded mb-4 text-sm">
        <i class="ri-check-line mr-2"></i><?= htmlspecialchars($successMessage) ?>
      </div>
      <?php endif; ?>

      <form method="POST" id="loginForm" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        
        <div>
          <label for="email" class="text-primary font-bold text-sm mb-2 block">
            <i class="ri-mail-line mr-1"></i>Email
          </label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="agent@cybersec.bj" 
            class="w-full px-4 py-3 rounded input-field text-white placeholder-gray-400" 
            required 
            autocomplete="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          >
        </div>
        
        <div>
          <label for="password" class="text-primary font-bold text-sm mb-2 block">
            <i class="ri-lock-line mr-1"></i>Mot de passe
          </label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            placeholder="••••••••" 
            class="w-full px-4 py-3 rounded input-field text-white placeholder-gray-400" 
            required 
            autocomplete="current-password"
          >
        </div>

        <div class="flex justify-center">
          <div class="h-captcha" data-sitekey="10000000-ffff-ffff-ffff-000000000001"></div>
        </div>

        <button type="submit" class="w-full btn-primary px-6 py-3 rounded font-bold flex items-center justify-center">
          <i class="ri-login-box-line mr-2"></i>Se connecter
        </button>
      </form>
      
      <div class="mt-6 pt-6 border-t border-gray-700 text-center">
        <p class="text-xs text-gray-400 mb-3">Pas encore d'accès ?</p>
        <a href="/backend/pages/register.php" class="btn-secondary px-6 py-2 rounded text-sm font-bold inline-flex items-center">
          <i class="ri-user-add-line mr-2"></i>Créer un compte
        </a>
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
        </div>
      </div>
    </div>
  </footer>

  <script src="/js/dynamic-dates.js"></script>
  <script src="/js/ctf-main-timer.js"></script>
  <script>
    // Amélioration de l'UX du formulaire
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      
      submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin mr-2"></i>Connexion...';
      submitBtn.disabled = true;
      
      // Si c'est une soumission AJAX qui échoue, restaurer le bouton
      setTimeout(() => {
        if (submitBtn.disabled) {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        }
      }, 10000);
    });
    
    // Auto-focus sur le premier champ si vide
    document.addEventListener('DOMContentLoaded', function() {
      const emailField = document.getElementById('email');
      if (!emailField.value) {
        emailField.focus();
      }
    });
  </script>
</body>
</html>