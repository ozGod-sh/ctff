<?php
/**
 * API Admin - Opération PHÉNIX
 * Endpoints d'administration avec CRUD complet et sécurité avancée
 * 
 * @version 1.0
 * @author Agent PHP Senior
 */

header('Content-Type: application/json');
header('X-Powered-By: Operation-PHENIX-CTF');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Admin.php';

// Configuration CORS pour admin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Démarrer session et vérifier admin
session_start();
Security::setSecurityHeaders();

try {
    $user = new User();
    $admin = new Admin();
    
    // Vérifier l'authentification admin
    if (!$user->isLoggedIn() || !$user->isAdmin()) {
        Security::logSecurityEvent('ADMIN_API_UNAUTHORIZED', 'Tentative accès API admin non autorisé');
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Accès refusé : privilèges administrateur requis'
        ]);
        exit;
    }
    
    // Vérifier le token CSRF pour les actions modificatrices
    $method = $_SERVER['REQUEST_METHOD'];
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $headers = getallheaders();
        $csrfToken = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? null;
        
        if (!Security::validateCSRFToken($csrfToken)) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Token CSRF invalide'
            ]);
            exit;
        }
    }
    
    // Router principal
    $action = $_GET['action'] ?? '';
    $entity = $_GET['entity'] ?? '';
    
    switch ($entity) {
        case 'users':
            handleUsersAPI($admin, $action);
            break;
            
        case 'challenges':
            handleChallengesAPI($admin, $action);
            break;
            
        case 'stats':
            handleStatsAPI($admin, $action);
            break;
            
        case 'logs':
            handleLogsAPI($admin, $action);
            break;
            
        case 'system':
            handleSystemAPI($admin, $action);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Entité invalide'
            ]);
    }
    
} catch (Exception $e) {
    logEvent('ERROR', 'ADMIN_API_ERROR', $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur serveur'
    ]);
}

// ===== GESTION UTILISATEURS =====

function handleUsersAPI($admin, $action) {
    switch ($action) {
        case 'list':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $search = $_GET['search'] ?? '';
            
            $users = $admin->getAllUsers($limit, $offset, $search);
            $total = $admin->getUsersCount($search);
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'users' => $users,
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ]);
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $result = $admin->createUser(
                $data['pseudo'] ?? '',
                $data['email'] ?? '',
                $data['password'] ?? '',
                $data['role'] ?? 'user'
            );
            
            http_response_code($result['success'] ? 201 : 400);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => $result['success'] ? ['user_id' => $result['user_id']] : null
            ]);
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $userId = (int)$_GET['id'];
            $data = json_decode(file_get_contents('php://input'), true);
            
            $result = $admin->updateUser($userId, $data);
            
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $userId = (int)$_GET['id'];
            $result = $admin->deleteUser($userId);
            
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'reset_password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $userId = (int)$_GET['id'];
            $data = json_decode(file_get_contents('php://input'), true);
            $newPassword = $data['password'] ?? null;
            
            $result = $admin->resetUserPassword($userId, $newPassword);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => $result['success'] ? ['new_password' => $result['new_password']] : null
            ]);
            break;
            
        case 'export':
            $csv = $admin->exportUsersData();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
            echo $csv;
            return;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action invalide']);
    }
}

// ===== GESTION DÉFIS =====

function handleChallengesAPI($admin, $action) {
    switch ($action) {
        case 'list':
            $challenges = $admin->getAllChallengesAdmin();
            
            echo json_encode([
                'status' => 'success',
                'data' => $challenges
            ]);
            break;
            
        case 'toggle':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $challengeId = (int)$_GET['id'];
            $data = json_decode(file_get_contents('php://input'), true);
            $isActive = (bool)$data['is_active'];
            
            $result = $admin->toggleChallenge($challengeId, $isActive);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'update_points':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $challengeId = (int)$_GET['id'];
            $data = json_decode(file_get_contents('php://input'), true);
            $points = (int)$data['points'];
            
            $result = $admin->updateChallengePoints($challengeId, $points);
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'submissions':
            $challengeId = (int)$_GET['id'];
            $limit = (int)($_GET['limit'] ?? 50);
            
            $challenge = new Challenge();
            $submissions = $challenge->getChallengeSubmissions($challengeId, $limit);
            
            echo json_encode([
                'status' => 'success',
                'data' => $submissions
            ]);
            break;
            
        case 'leaderboard':
            $challengeId = (int)$_GET['id'];
            $limit = (int)($_GET['limit'] ?? 20);
            
            $challenge = new Challenge();
            $leaderboard = $challenge->getChallengeLeaderboard($challengeId, $limit);
            
            echo json_encode([
                'status' => 'success',
                'data' => $leaderboard
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action invalide']);
    }
}

// ===== STATISTIQUES =====

function handleStatsAPI($admin, $action) {
    switch ($action) {
        case 'global':
            $stats = $admin->getGlobalStats();
            
            echo json_encode([
                'status' => 'success',
                'data' => $stats
            ]);
            break;
            
        case 'analytics':
            $period = $_GET['period'] ?? '7d';
            $analytics = $admin->getAnalyticsData($period);
            
            echo json_encode([
                'status' => 'success',
                'data' => $analytics,
                'period' => $period
            ]);
            break;
            
        case 'realtime':
            // Statistiques temps réel (cache 30 secondes)
            $cacheFile = __DIR__ . '/../cache/realtime_stats.json';
            $cacheTime = 30; // secondes
            
            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
                $stats = json_decode(file_get_contents($cacheFile), true);
            } else {
                $stats = [
                    'timestamp' => time(),
                    'online_users' => getOnlineUsersCount(),
                    'active_sessions' => getActiveSessionsCount(),
                    'submissions_last_hour' => getSubmissionsLastHour(),
                    'new_users_today' => getNewUsersToday()
                ];
                
                // Créer le dossier cache s'il n'existe pas
                $cacheDir = dirname($cacheFile);
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                
                file_put_contents($cacheFile, json_encode($stats));
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $stats
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action invalide']);
    }
}

// ===== LOGS =====

function handleLogsAPI($admin, $action) {
    switch ($action) {
        case 'admin':
            $limit = (int)($_GET['limit'] ?? 50);
            $logs = $admin->getAdminLogs($limit);
            
            echo json_encode([
                'status' => 'success',
                'data' => $logs
            ]);
            break;
            
        case 'security':
            $limit = (int)($_GET['limit'] ?? 50);
            $events = $admin->getSecurityEvents($limit);
            
            echo json_encode([
                'status' => 'success',
                'data' => $events
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action invalide']);
    }
}

// ===== SYSTÈME =====

function handleSystemAPI($admin, $action) {
    switch ($action) {
        case 'recalculate_scores':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $result = $admin->recalculateAllScores();
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'cleanup_sessions':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $result = $admin->cleanupSessions();
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ]);
            break;
            
        case 'backup_db':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
                return;
            }
            
            $result = createDatabaseBackup();
            
            echo json_encode([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'data' => $result['success'] ? ['backup_file' => $result['file']] : null
            ]);
            break;
            
        case 'system_info':
            $info = [
                'php_version' => PHP_VERSION,
                'server_time' => date('Y-m-d H:i:s'),
                'uptime' => getServerUptime(),
                'memory_usage' => formatBytes(memory_get_usage(true)),
                'memory_peak' => formatBytes(memory_get_peak_usage(true)),
                'database_size' => getDatabaseSize()
            ];
            
            echo json_encode([
                'status' => 'success',
                'data' => $info
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action invalide']);
    }
}

// ===== FONCTIONS UTILITAIRES =====

function getOnlineUsersCount() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT user_id) 
        FROM sessions 
        WHERE expires_at > datetime('now')
    ");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getActiveSessionsCount() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM sessions 
        WHERE expires_at > datetime('now')
    ");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getSubmissionsLastHour() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM submissions 
        WHERE submitted_at > datetime('now', '-1 hour')
    ");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getNewUsersToday() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM users 
        WHERE DATE(created_at) = DATE('now')
    ");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function createDatabaseBackup() {
    try {
        $backupDir = __DIR__ . '/../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.db';
        $backupPath = $backupDir . '/' . $filename;
        $dbPath = __DIR__ . '/../database/ctf_database.db';
        
        if (copy($dbPath, $backupPath)) {
            return [
                'success' => true,
                'message' => 'Sauvegarde créée avec succès',
                'file' => $filename
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erreur : ' . $e->getMessage()
        ];
    }
}

function getServerUptime() {
    if (PHP_OS_FAMILY === 'Linux') {
        $uptime = shell_exec('uptime -p');
        return trim($uptime ?: 'Inconnu');
    }
    return 'Inconnu';
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

function getDatabaseSize() {
    $dbPath = __DIR__ . '/../database/ctf_database.db';
    if (file_exists($dbPath)) {
        return formatBytes(filesize($dbPath));
    }
    return 'Inconnu';
}