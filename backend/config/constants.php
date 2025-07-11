<?php
/**
 * Constantes Globales - Opération PHÉNIX
 * Définition des paramètres système et configuration
 */

// Configuration du CTF
define('CTF_NAME', 'Opération PHÉNIX');
define('CTF_DESCRIPTION', 'CTF de cybersécurité - Neutralisez BL4CKH4T');
define('CTF_VERSION', '1.0.0');

// Points par défi
define('CHALLENGE_POINTS', [
    1 => 100,   // Défi 1: Code Analysis
    2 => 200,   // Défi 2: XSS
    3 => 300,   // Défi 3: JWT
    4 => 400,   // Défi 4: SSH Fragments
    5 => 500    // Défi 5: Final Challenge
]);

// Difficulté des défis
define('CHALLENGE_DIFFICULTY', [
    1 => 'Débutant',
    2 => 'Intermédiaire', 
    3 => 'Avancé',
    4 => 'Expert',
    5 => 'Maître'
]);

// Rôles utilisateurs
define('USER_ROLES', [
    'user' => 'Utilisateur',
    'admin' => 'Administrateur'
]);

// Paramètres de sécurité
define('SESSION_LIFETIME', 7200); // 2 heures
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_COOLDOWN', 300); // 5 minutes
define('CSRF_TOKEN_LIFETIME', 3600); // 1 heure

// Paramètres de rate limiting
define('RATE_LIMIT_ATTEMPTS', 10);
define('RATE_LIMIT_WINDOW', 300); // 5 minutes

// Configuration email (pour notifications)
define('ADMIN_EMAIL', 'admin@cybersec-bj.tech');
define('FROM_EMAIL', 'noreply@cybersec-bj.tech');

// Chemins du système
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('API_PATH', ROOT_PATH . '/api');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('DATABASE_PATH', ROOT_PATH . '/database');

// Configuration base de données
define('DB_FILE', DATABASE_PATH . '/phenix_ctf.db');

// Messages système
define('MESSAGES', [
    'login_success' => 'Connexion réussie, agent !',
    'login_failed' => 'Identifiants incorrects',
    'registration_success' => 'Inscription réussie ! Vous pouvez maintenant vous connecter',
    'registration_failed' => 'Erreur lors de l\'inscription',
    'flag_correct' => 'Flag correct ! Défi validé avec succès',
    'flag_incorrect' => 'Flag incorrect, essayez encore',
    'access_denied' => 'Accès refusé',
    'session_expired' => 'Session expirée, veuillez vous reconnecter',
    'rate_limit_exceeded' => 'Trop de tentatives, veuillez patienter',
    'maintenance_mode' => 'Site en maintenance, revenez plus tard'
]);

// Configuration JSON responses
define('JSON_SUCCESS', ['status' => 'success']);
define('JSON_ERROR', ['status' => 'error']);

// Statuts HTTP
define('HTTP_OK', 200);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_TOO_MANY_REQUESTS', 429);
define('HTTP_INTERNAL_ERROR', 500);

// Configuration du leaderboard
define('LEADERBOARD_REFRESH_INTERVAL', 30); // secondes
define('LEADERBOARD_MAX_ENTRIES', 100);

// Configuration des logs
define('LOG_LEVEL', [
    'DEBUG' => 0,
    'INFO' => 1,
    'WARNING' => 2,
    'ERROR' => 3,
    'CRITICAL' => 4
]);

// Types d'événements pour les logs
define('LOG_EVENTS', [
    'USER_LOGIN' => 'user_login',
    'USER_LOGOUT' => 'user_logout',
    'USER_REGISTER' => 'user_register',
    'CHALLENGE_ATTEMPT' => 'challenge_attempt',
    'CHALLENGE_SOLVED' => 'challenge_solved',
    'ADMIN_ACTION' => 'admin_action',
    'SECURITY_ALERT' => 'security_alert',
    'RATE_LIMIT_HIT' => 'rate_limit_hit'
]);

// Configuration des challenges
define('CHALLENGE_CONFIG', [
    1 => [
        'name' => 'Défi 1 : L\'identité de l\'ombre',
        'category' => 'reconnaissance',
        'difficulty' => 1,
        'points' => 100,
        'hint' => 'Inspectez le code source et cherchez dans l\'encodage Base64',
        'description' => 'BL4CKH4T a laissé sa signature sur une page gouvernementale. Trouvez son identité réelle.'
    ],
    2 => [
        'name' => 'Défi 2 : Le Mirage Numérique', 
        'category' => 'web',
        'difficulty' => 2,
        'points' => 200,
        'hint' => 'Les filtres XSS basiques peuvent être contournés avec des vecteurs alternatifs',
        'description' => 'Exploitez une faille XSS dans le moteur de recherche de BL4CKH4T.'
    ],
    3 => [
        'name' => 'Défi 3 : Le Token Oublié',
        'category' => 'cryptographie', 
        'difficulty' => 3,
        'points' => 300,
        'hint' => 'Cherchez dans les attributs data du DOM et décodez le JWT',
        'description' => 'Un token JWT contient les informations de connexion SSH de BL4CKH4T.'
    ],
    4 => [
        'name' => 'Défi 4 : Les Fragments d\'un Deuil',
        'category' => 'system',
        'difficulty' => 4, 
        'points' => 400,
        'hint' => 'Connectez-vous en SSH et reconstituez la phrase en 8 fragments',
        'description' => 'Infiltrez le système de BL4CKH4T et découvrez la véritable motivation.'
    ],
    5 => [
        'name' => 'Défi 5 : L\'Ascension du Phénix',
        'category' => 'final',
        'difficulty' => 5,
        'points' => 500, 
        'hint' => 'Combinez toutes vos compétences pour le défi ultime',
        'description' => 'Confrontation finale avec BL4CKH4T. Sauvez le Bénin.'
    ]
]);

// Types de réponses API
define('API_RESPONSE_SUCCESS', 'success');
define('API_RESPONSE_ERROR', 'error');
define('API_RESPONSE_WARNING', 'warning');

// Configuration timer CTF (timestamp de fin)
define('CTF_END_TIME', strtotime('2025-12-31 23:59:59'));

// Configuration maintenance
define('MAINTENANCE_FILE', ROOT_PATH . '/.maintenance');

// Fonction utilitaire pour les réponses JSON
function jsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Fonction pour vérifier si on est en mode maintenance
function isMaintenanceMode() {
    return file_exists(MAINTENANCE_FILE);
}

// Fonction pour loguer les événements
function logEvent($level, $event, $message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => $level,
        'event' => $event,
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    $logFile = LOGS_PATH . '/application.log';
    if (!is_dir(LOGS_PATH)) {
        mkdir(LOGS_PATH, 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}