<?php
/**
 * API Leaderboard - Opération PHÉNIX
 * Endpoints pour classements temps réel et analytics
 * 
 * @version 1.0
 * @author Agent PHP Senior + SQLite Expert
 */

header('Content-Type: application/json');
header('X-Powered-By: Operation-PHENIX-CTF');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Leaderboard.php';

// Configuration CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Démarrer session et vérifier authentification
session_start();
Security::setSecurityHeaders();

try {
    $user = new User();
    $leaderboard = new Leaderboard();
    
    // Vérifier l'authentification pour l'accès au leaderboard
    if (!$user->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Authentification requise'
        ]);
        exit;
    }
    
    // Router principal
    $action = $_GET['action'] ?? 'main';
    
    switch ($action) {
        case 'main':
            handleMainLeaderboard($leaderboard);
            break;
            
        case 'filtered':
            handleFilteredLeaderboard($leaderboard);
            break;
            
        case 'challenge':
            handleChallengeLeaderboard($leaderboard);
            break;
            
        case 'user_progression':
            handleUserProgression($leaderboard, $user);
            break;
            
        case 'search':
            handleUserSearch($leaderboard);
            break;
            
        case 'context':
            handleUserContext($leaderboard, $user);
            break;
            
        case 'stats':
            handleLeaderboardStats($leaderboard);
            break;
            
        case 'analytics':
            handleAnalytics($leaderboard);
            break;
            
        case 'records':
            handleRecords($leaderboard);
            break;
            
        case 'recent_changes':
            handleRecentChanges($leaderboard);
            break;
            
        case 'export':
            handleExport($leaderboard);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Action invalide'
            ]);
    }
    
} catch (Exception $e) {
    logEvent('ERROR', 'LEADERBOARD_API_ERROR', $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur serveur'
    ]);
}

// ===== ENDPOINTS =====

/**
 * Classement principal
 */
function handleMainLeaderboard($leaderboard) {
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Validation des paramètres
    if ($limit > 100) $limit = 100;
    if ($limit < 1) $limit = 50;
    if ($offset < 0) $offset = 0;
    
    $data = $leaderboard->getMainLeaderboard($limit, $offset);
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'metadata' => [
            'limit' => $limit,
            'offset' => $offset,
            'count' => count($data)
        ]
    ]);
}

/**
 * Classement avec filtres
 */
function handleFilteredLeaderboard($leaderboard) {
    $filters = [
        'period' => $_GET['period'] ?? null,
        'min_challenges' => $_GET['min_challenges'] ?? null,
        'min_points' => $_GET['min_points'] ?? null,
        'search' => $_GET['search'] ?? null,
        'limit' => (int)($_GET['limit'] ?? 50),
        'offset' => (int)($_GET['offset'] ?? 0)
    ];
    
    // Nettoyer les filtres vides
    $filters = array_filter($filters, function($value) {
        return $value !== null && $value !== '';
    });
    
    $data = $leaderboard->getFilteredLeaderboard($filters);
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'filters' => $filters,
        'metadata' => [
            'count' => count($data)
        ]
    ]);
}

/**
 * Classement par défi spécifique
 */
function handleChallengeLeaderboard($leaderboard) {
    $challengeId = (int)($_GET['challenge_id'] ?? 0);
    $limit = (int)($_GET['limit'] ?? 20);
    
    if ($challengeId <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'ID de défi invalide'
        ]);
        return;
    }
    
    $data = $leaderboard->getChallengeLeaderboard($challengeId, $limit);
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'challenge_id' => $challengeId
    ]);
}

/**
 * Progression d'un utilisateur
 */
function handleUserProgression($leaderboard, $user) {
    $userId = (int)($_GET['user_id'] ?? $_SESSION['user_id']);
    $period = $_GET['period'] ?? '30d';
    
    // Vérifier les permissions
    if ($userId !== $_SESSION['user_id'] && !$user->isAdmin()) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Accès refusé'
        ]);
        return;
    }
    
    $data = $leaderboard->getUserProgression($userId, $period);
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'user_id' => $userId,
        'period' => $period
    ]);
}

/**
 * Recherche d'utilisateur
 */
function handleUserSearch($leaderboard) {
    $query = $_GET['query'] ?? '';
    
    if (strlen($query) < 2) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Requête trop courte (minimum 2 caractères)'
        ]);
        return;
    }
    
    $data = $leaderboard->searchUser($query);
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'query' => $query
    ]);
}

/**
 * Contexte autour d'un utilisateur
 */
function handleUserContext($leaderboard, $user) {
    $userId = (int)($_GET['user_id'] ?? $_SESSION['user_id']);
    $radius = (int)($_GET['radius'] ?? 5);
    
    if ($radius > 10) $radius = 10;
    if ($radius < 1) $radius = 5;
    
    $data = $leaderboard->getUserContext($userId, $radius);
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'user_id' => $userId,
        'radius' => $radius
    ]);
}

/**
 * Statistiques du leaderboard
 */
function handleLeaderboardStats($leaderboard) {
    // Cache les stats pendant 5 minutes
    $cacheFile = __DIR__ . '/../cache/leaderboard_stats.json';
    $cacheTime = 300; // 5 minutes
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $stats = json_decode(file_get_contents($cacheFile), true);
    } else {
        $stats = $leaderboard->getLeaderboardStats();
        
        // Créer le dossier cache s'il n'existe pas
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($cacheFile, json_encode($stats));
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $stats,
        'cached_at' => filemtime($cacheFile)
    ]);
}

/**
 * Données analytics
 */
function handleAnalytics($leaderboard) {
    $period = $_GET['period'] ?? '7d';
    
    if (!in_array($period, ['24h', '7d', '30d'])) {
        $period = '7d';
    }
    
    $data = $leaderboard->getAnalyticsData($period);
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'period' => $period
    ]);
}

/**
 * Records et achievements
 */
function handleRecords($leaderboard) {
    $records = $leaderboard->getRecords();
    
    echo json_encode([
        'status' => 'success',
        'data' => $records
    ]);
}

/**
 * Changements récents dans le classement
 */
function handleRecentChanges($leaderboard) {
    $limit = (int)($_GET['limit'] ?? 10);
    
    if ($limit > 50) $limit = 50;
    
    $changes = $leaderboard->getRecentRankingChanges($limit);
    
    echo json_encode([
        'status' => 'success',
        'data' => $changes,
        'limit' => $limit
    ]);
}

/**
 * Export du leaderboard
 */
function handleExport($leaderboard) {
    $format = $_GET['format'] ?? 'csv';
    
    if ($format === 'csv') {
        $csv = $leaderboard->exportLeaderboard('csv');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="leaderboard_' . date('Y-m-d_H-i-s') . '.csv"');
        echo $csv;
        return;
    }
    
    // Format JSON par défaut
    $data = $leaderboard->exportLeaderboard('json');
    
    header('Content-Disposition: attachment; filename="leaderboard_' . date('Y-m-d_H-i-s') . '.json"');
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'exported_at' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}