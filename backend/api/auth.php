<?php
/**
 * API Authentification - Opération PHÉNIX
 * Endpoints pour login, register, logout et gestion de session
 */

// Configuration des headers
header('Content-Type: application/json');
header('X-Powered-By: Opération PHÉNIX CTF');

// Inclure les dépendances
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/User.php';

// Activer les headers de sécurité
Security::setSecurityHeaders();

// Vérifier le mode maintenance
if (isMaintenanceMode()) {
    jsonResponse([
        'status' => 'error',
        'message' => MESSAGES['maintenance_mode']
    ], HTTP_INTERNAL_ERROR);
}

// Obtenir la méthode HTTP et l'action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Démarrer la session
session_start();

// Instancier la classe User
$user = new User();

// Router selon l'action
switch ($action) {
    case 'login':
        handleLogin($user);
        break;
        
    case 'register':
        handleRegister($user);
        break;
        
    case 'logout':
        handleLogout($user);
        break;
        
    case 'status':
        handleStatus($user);
        break;
        
    case 'profile':
        handleProfile($user);
        break;
        
    default:
        jsonResponse([
            'status' => 'error',
            'message' => 'Action non reconnue'
        ], HTTP_BAD_REQUEST);
}

/**
 * Gestion de la connexion
 */
function handleLogin($user) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse([
            'status' => 'error',
            'message' => 'Méthode non autorisée'
        ], HTTP_BAD_REQUEST);
    }
    
    // Vérifier le token CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($csrfToken)) {
        Security::logSecurityEvent('CSRF_VIOLATION', 'Token CSRF invalide sur login');
        jsonResponse([
            'status' => 'error',
            'message' => 'Token de sécurité invalide'
        ], HTTP_FORBIDDEN);
    }
    
    // Récupérer et nettoyer les données
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($email) || empty($password)) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Email et mot de passe requis'
        ], HTTP_BAD_REQUEST);
    }
    
    // Tentative de connexion
    $result = $user->login($email, $password);
    
    if ($result['success']) {
        jsonResponse([
            'status' => 'success',
            'message' => $result['message'],
            'user' => $result['user'],
            'redirect' => $result['user']['role'] === 'admin' ? '/backend/pages/dashboard-admin.php' : '/backend/pages/dashboard-user.php'
        ], HTTP_OK);
    } else {
        jsonResponse([
            'status' => 'error',
            'message' => $result['message']
        ], HTTP_UNAUTHORIZED);
    }
}

/**
 * Gestion de l'inscription
 */
function handleRegister($user) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse([
            'status' => 'error',
            'message' => 'Méthode non autorisée'
        ], HTTP_BAD_REQUEST);
    }
    
    // Vérifier le token CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCSRFToken($csrfToken)) {
        Security::logSecurityEvent('CSRF_VIOLATION', 'Token CSRF invalide sur register');
        jsonResponse([
            'status' => 'error',
            'message' => 'Token de sécurité invalide'
        ], HTTP_FORBIDDEN);
    }
    
    // Récupérer et nettoyer les données
    $pseudo = Security::sanitizeInput($_POST['pseudo'] ?? '');
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($pseudo) || empty($email) || empty($password)) {
        jsonResponse([
            'status' => 'error',
            'message' => 'Tous les champs sont requis'
        ], HTTP_BAD_REQUEST);
    }
    
    // Tentative d'inscription
    $result = $user->register($pseudo, $email, $password);
    
    if ($result['success']) {
        jsonResponse([
            'status' => 'success',
            'message' => $result['message'],
            'redirect' => '/login.html'
        ], HTTP_OK);
    } else {
        jsonResponse([
            'status' => 'error',
            'message' => $result['message']
        ], HTTP_BAD_REQUEST);
    }
}

/**
 * Gestion de la déconnexion
 */
function handleLogout($user) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse([
            'status' => 'error',
            'message' => 'Méthode non autorisée'
        ], HTTP_BAD_REQUEST);
    }
    
    // Déconnexion
    $result = $user->logout();
    
    jsonResponse([
        'status' => 'success',
        'message' => $result['message'],
        'redirect' => '/index.html'
    ], HTTP_OK);
}

/**
 * Vérification du statut de connexion
 */
function handleStatus($user) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonResponse([
            'status' => 'error',
            'message' => 'Méthode non autorisée'
        ], HTTP_BAD_REQUEST);
    }
    
    if ($user->isLoggedIn()) {
        $currentUser = $user->getCurrentUser();
        jsonResponse([
            'status' => 'success',
            'logged_in' => true,
            'user' => [
                'id' => $currentUser['id'],
                'pseudo' => $currentUser['pseudo'],
                'role' => $currentUser['role'],
                'total_points' => $currentUser['total_points'] ?? 0,
                'challenges_solved' => $currentUser['challenges_solved'] ?? 0,
                'rank_position' => $currentUser['rank_position'] ?? 0
            ]
        ], HTTP_OK);
    } else {
        jsonResponse([
            'status' => 'success',
            'logged_in' => false
        ], HTTP_OK);
    }
}

/**
 * Obtenir le profil utilisateur
 */
function handleProfile($user) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonResponse([
            'status' => 'error',
            'message' => 'Méthode non autorisée'
        ], HTTP_BAD_REQUEST);
    }
    
    if (!$user->isLoggedIn()) {
        jsonResponse([
            'status' => 'error',
            'message' => MESSAGES['access_denied']
        ], HTTP_UNAUTHORIZED);
    }
    
    $currentUser = $user->getCurrentUser();
    $stats = $user->getUserStats();
    $recentSubmissions = $user->getRecentSubmissions();
    
    jsonResponse([
        'status' => 'success',
        'user' => [
            'id' => $currentUser['id'],
            'pseudo' => $currentUser['pseudo'],
            'email' => $currentUser['email'],
            'role' => $currentUser['role'],
            'created_at' => $currentUser['created_at'],
            'last_login' => $currentUser['last_login']
        ],
        'stats' => $stats,
        'recent_submissions' => $recentSubmissions
    ], HTTP_OK);
}

/**
 * Générer un token CSRF pour les formulaires
 */
function getCsrfToken() {
    return Security::generateCSRFToken();
}

// Si aucune action spécifique, retourner les informations de base
if (empty($action)) {
    jsonResponse([
        'status' => 'success',
        'message' => 'API Auth Opération PHÉNIX',
        'version' => CTF_VERSION,
        'csrf_token' => Security::generateCSRFToken(),
        'endpoints' => [
            'login' => 'POST /api/auth.php?action=login',
            'register' => 'POST /api/auth.php?action=register', 
            'logout' => 'POST /api/auth.php?action=logout',
            'status' => 'GET /api/auth.php?action=status',
            'profile' => 'GET /api/auth.php?action=profile'
        ]
    ], HTTP_OK);
}