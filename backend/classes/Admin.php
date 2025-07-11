<?php
/**
 * Classe Admin - Opération PHÉNIX
 * Gestion administrative complète de la plateforme CTF
 * 
 * @version 1.0
 * @author Agents PHP Senior + SQLite Expert
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Challenge.php';

class Admin {
    private $db;
    private $user;
    private $challenge;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->user = new User();
        $this->challenge = new Challenge();
    }
    
    /**
     * Vérifier si l'utilisateur actuel est admin
     */
    public function requireAdmin() {
        if (!$this->user->isAdmin()) {
            Security::logSecurityEvent('ADMIN_ACCESS_DENIED', 'Tentative accès admin non autorisé');
            throw new Exception('Accès refusé : privilèges administrateur requis');
        }
    }
    
    // ===== GESTION UTILISATEURS =====
    
    /**
     * Obtenir tous les utilisateurs avec statistiques
     */
    public function getAllUsers($limit = 100, $offset = 0, $search = '') {
        $this->requireAdmin();
        
        $searchCondition = '';
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = "WHERE u.pseudo LIKE ? OR u.email LIKE ?";
            $params = ["%$search%", "%$search%"];
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.pseudo,
                u.email,
                u.role,
                u.is_active,
                u.created_at,
                u.last_login,
                u.login_attempts,
                l.total_points,
                l.challenges_solved,
                l.rank_position,
                COUNT(s.id) as total_submissions,
                COUNT(CASE WHEN s.is_correct = 1 THEN 1 END) as correct_submissions
            FROM users u
            LEFT JOIN leaderboard_cache l ON u.id = l.user_id
            LEFT JOIN submissions s ON u.id = s.user_id
            $searchCondition
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir le nombre total d'utilisateurs
     */
    public function getUsersCount($search = '') {
        $searchCondition = '';
        $params = [];
        
        if (!empty($search)) {
            $searchCondition = "WHERE pseudo LIKE ? OR email LIKE ?";
            $params = ["%$search%", "%$search%"];
        }
        
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users $searchCondition");
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Créer un nouvel utilisateur (admin)
     */
    public function createUser($pseudo, $email, $password, $role = 'user') {
        $this->requireAdmin();
        
        try {
            // Validation des données
            if (!Security::validatePseudo($pseudo)) {
                return ['success' => false, 'message' => 'Pseudo invalide'];
            }
            
            if (!Security::validateEmail($email)) {
                return ['success' => false, 'message' => 'Email invalide'];
            }
            
            if (!in_array($role, ['user', 'admin'])) {
                return ['success' => false, 'message' => 'Rôle invalide'];
            }
            
            // Vérifier l'unicité
            $stmt = $this->db->prepare("SELECT id FROM users WHERE pseudo = ? OR email = ?");
            $stmt->execute([$pseudo, $email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Pseudo ou email déjà utilisé'];
            }
            
            // Créer l'utilisateur
            $passwordHash = Security::hashPassword($password);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (pseudo, email, password_hash, role, created_at, is_active)
                VALUES (?, ?, ?, ?, datetime('now'), 1)
            ");
            $stmt->execute([$pseudo, $email, $passwordHash, $role]);
            $userId = $this->db->lastInsertId();
            
            // Initialiser le cache leaderboard
            $stmt = $this->db->prepare("
                INSERT INTO leaderboard_cache (user_id, total_points, challenges_solved)
                VALUES (?, 0, 0)
            ");
            $stmt->execute([$userId]);
            
            $this->logAdminAction('USER_CREATE', $userId, "Utilisateur créé: $pseudo");
            
            return ['success' => true, 'message' => 'Utilisateur créé avec succès', 'user_id' => $userId];
            
        } catch (Exception $e) {
            logEvent('ERROR', 'ADMIN_USER_CREATE_ERROR', $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création'];
        }
    }
    
    /**
     * Modifier un utilisateur
     */
    public function updateUser($userId, $data) {
        $this->requireAdmin();
        
        try {
            $allowedFields = ['pseudo', 'email', 'role', 'is_active'];
            $setClause = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    if ($field === 'pseudo' && !Security::validatePseudo($value)) {
                        return ['success' => false, 'message' => 'Pseudo invalide'];
                    }
                    if ($field === 'email' && !Security::validateEmail($value)) {
                        return ['success' => false, 'message' => 'Email invalide'];
                    }
                    if ($field === 'role' && !in_array($value, ['user', 'admin'])) {
                        return ['success' => false, 'message' => 'Rôle invalide'];
                    }
                    
                    $setClause[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($setClause)) {
                return ['success' => false, 'message' => 'Aucune donnée valide à modifier'];
            }
            
            $params[] = $userId;
            $stmt = $this->db->prepare("
                UPDATE users 
                SET " . implode(', ', $setClause) . "
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            $this->logAdminAction('USER_UPDATE', $userId, "Utilisateur modifié");
            
            return ['success' => true, 'message' => 'Utilisateur modifié avec succès'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de la modification'];
        }
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($userId) {
        $this->requireAdmin();
        
        try {
            // Ne pas permettre l'auto-suppression
            if ($userId == $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'Impossible de supprimer votre propre compte'];
            }
            
            // Supprimer en cascade
            $this->db->beginTransaction();
            
            // Supprimer les soumissions
            $stmt = $this->db->prepare("DELETE FROM submissions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Supprimer du leaderboard
            $stmt = $this->db->prepare("DELETE FROM leaderboard_cache WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Supprimer les sessions
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Supprimer l'utilisateur
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $this->db->commit();
            
            $this->logAdminAction('USER_DELETE', $userId, "Utilisateur supprimé");
            
            return ['success' => true, 'message' => 'Utilisateur supprimé avec succès'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }
    
    /**
     * Réinitialiser le mot de passe d'un utilisateur
     */
    public function resetUserPassword($userId, $newPassword = null) {
        $this->requireAdmin();
        
        try {
            if ($newPassword === null) {
                $newPassword = Security::generateRandomPassword();
            }
            
            if (!Security::validatePassword($newPassword)) {
                return ['success' => false, 'message' => 'Mot de passe trop faible'];
            }
            
            $passwordHash = Security::hashPassword($newPassword);
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password_hash = ?, login_attempts = 0, last_attempt = NULL
                WHERE id = ?
            ");
            $stmt->execute([$passwordHash, $userId]);
            
            // Supprimer toutes les sessions de cet utilisateur
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $this->logAdminAction('USER_PASSWORD_RESET', $userId, "Mot de passe réinitialisé");
            
            return [
                'success' => true, 
                'message' => 'Mot de passe réinitialisé avec succès',
                'new_password' => $newPassword
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de la réinitialisation'];
        }
    }
    
    // ===== GESTION DÉFIS =====
    
    /**
     * Obtenir tous les défis avec statistiques admin
     */
    public function getAllChallengesAdmin() {
        $this->requireAdmin();
        
        return $this->challenge->getAllChallengeStats();
    }
    
    /**
     * Activer/Désactiver un défi
     */
    public function toggleChallenge($challengeId, $isActive) {
        $this->requireAdmin();
        
        $result = $this->challenge->toggleChallengeStatus($challengeId, $isActive);
        
        if ($result['success']) {
            $status = $isActive ? 'activé' : 'désactivé';
            $this->logAdminAction('CHALLENGE_TOGGLE', $challengeId, "Défi $status");
        }
        
        return $result;
    }
    
    /**
     * Modifier les points d'un défi
     */
    public function updateChallengePoints($challengeId, $points) {
        $this->requireAdmin();
        
        try {
            if ($points < 0 || $points > 1000) {
                return ['success' => false, 'message' => 'Points invalides (0-1000)'];
            }
            
            $stmt = $this->db->prepare("UPDATE challenges SET points = ? WHERE id = ?");
            $stmt->execute([$points, $challengeId]);
            
            $this->logAdminAction('CHALLENGE_UPDATE_POINTS', $challengeId, "Points modifiés: $points");
            
            return ['success' => true, 'message' => 'Points modifiés avec succès'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur lors de la modification'];
        }
    }
    
    // ===== STATISTIQUES GLOBALES =====
    
    /**
     * Obtenir les statistiques globales de la plateforme
     */
    public function getGlobalStats() {
        $this->requireAdmin();
        
        $stats = [];
        
        // Statistiques utilisateurs
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
                COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
                COUNT(CASE WHEN last_login > datetime('now', '-24 hours') THEN 1 END) as active_24h,
                COUNT(CASE WHEN created_at > datetime('now', '-7 days') THEN 1 END) as new_this_week
            FROM users
        ");
        $stmt->execute();
        $stats['users'] = $stmt->fetch();
        
        // Statistiques défis
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_challenges,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_challenges,
                SUM(points) as total_points_available
            FROM challenges
        ");
        $stmt->execute();
        $stats['challenges'] = $stmt->fetch();
        
        // Statistiques soumissions
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN is_correct = 1 THEN 1 END) as correct_submissions,
                COUNT(CASE WHEN submitted_at > datetime('now', '-24 hours') THEN 1 END) as submissions_24h,
                ROUND(COUNT(CASE WHEN is_correct = 1 THEN 1 END) * 100.0 / COUNT(*), 2) as success_rate
            FROM submissions
        ");
        $stmt->execute();
        $stats['submissions'] = $stmt->fetch();
        
        // Top utilisateurs
        $stmt = $this->db->prepare("
            SELECT u.pseudo, l.total_points, l.challenges_solved, l.rank_position
            FROM users u
            JOIN leaderboard_cache l ON u.id = l.user_id
            WHERE l.total_points > 0
            ORDER BY l.total_points DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['top_users'] = $stmt->fetchAll();
        
        // Défis les plus difficiles
        $stmt = $this->db->prepare("
            SELECT 
                c.name,
                c.points,
                COUNT(s.id) as total_attempts,
                COUNT(CASE WHEN s.is_correct = 1 THEN 1 END) as successful_attempts,
                ROUND(COUNT(CASE WHEN s.is_correct = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(s.id), 0), 2) as success_rate
            FROM challenges c
            LEFT JOIN submissions s ON c.id = s.challenge_id
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY success_rate ASC, total_attempts DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['hardest_challenges'] = $stmt->fetchAll();
        
        // Activité récente
        $stmt = $this->db->prepare("
            SELECT 
                'submission' as type,
                u.pseudo,
                c.name as target,
                s.is_correct,
                s.submitted_at as timestamp
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            JOIN challenges c ON s.challenge_id = c.id
            WHERE s.submitted_at > datetime('now', '-24 hours')
            ORDER BY s.submitted_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $stats['recent_activity'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Obtenir les données pour les graphiques
     */
    public function getAnalyticsData($period = '7d') {
        $this->requireAdmin();
        
        $dateCondition = match($period) {
            '24h' => "datetime('now', '-1 day')",
            '7d' => "datetime('now', '-7 days')",
            '30d' => "datetime('now', '-30 days')",
            default => "datetime('now', '-7 days')"
        };
        
        $analytics = [];
        
        // Soumissions par jour
        $stmt = $this->db->prepare("
            SELECT 
                DATE(submitted_at) as date,
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN is_correct = 1 THEN 1 END) as correct_submissions
            FROM submissions
            WHERE submitted_at > $dateCondition
            GROUP BY DATE(submitted_at)
            ORDER BY date ASC
        ");
        $stmt->execute();
        $analytics['submissions_timeline'] = $stmt->fetchAll();
        
        // Inscriptions par jour
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as new_users
            FROM users
            WHERE created_at > $dateCondition
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute();
        $analytics['registrations_timeline'] = $stmt->fetchAll();
        
        return $analytics;
    }
    
    // ===== LOGS ET SÉCURITÉ =====
    
    /**
     * Enregistrer une action admin
     */
    private function logAdminAction($action, $targetId = null, $details = '') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO admin_logs (admin_id, action, target_user, details, timestamp)
                VALUES (?, ?, ?, ?, datetime('now'))
            ");
            $stmt->execute([$_SESSION['user_id'], $action, $targetId, $details]);
        } catch (Exception $e) {
            logEvent('ERROR', 'ADMIN_LOG_ERROR', $e->getMessage());
        }
    }
    
    /**
     * Obtenir les logs admin récents
     */
    public function getAdminLogs($limit = 50) {
        $this->requireAdmin();
        
        $stmt = $this->db->prepare("
            SELECT 
                al.action,
                al.target_user,
                al.details,
                al.timestamp,
                u.pseudo as admin_pseudo
            FROM admin_logs al
            JOIN users u ON al.admin_id = u.id
            ORDER BY al.timestamp DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir les événements de sécurité récents
     */
    public function getSecurityEvents($limit = 50) {
        $this->requireAdmin();
        
        // Cette méthode nécessiterait une table security_logs
        // Pour l'instant, on retourne les tentatives de connexion échouées
        $stmt = $this->db->prepare("
            SELECT 
                u.pseudo,
                u.email,
                u.login_attempts,
                u.last_attempt,
                'login_attempts' as event_type
            FROM users u
            WHERE u.login_attempts > 0
            ORDER BY u.last_attempt DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Recalculer tous les scores (maintenance)
     */
    public function recalculateAllScores() {
        $this->requireAdmin();
        
        $result = $this->challenge->recalculateAllScores();
        
        if ($result['success']) {
            $this->logAdminAction('SYSTEM_RECALC_SCORES', null, 'Scores recalculés');
        }
        
        return $result;
    }
    
    /**
     * Nettoyer les sessions expirées
     */
    public function cleanupSessions() {
        $this->requireAdmin();
        
        $cleaned = $this->user->cleanExpiredSessions();
        
        $this->logAdminAction('SYSTEM_CLEANUP_SESSIONS', null, "Sessions nettoyées: $cleaned");
        
        return ['success' => true, 'message' => "$cleaned sessions nettoyées"];
    }
    
    /**
     * Exporter les données utilisateurs (CSV)
     */
    public function exportUsersData() {
        $this->requireAdmin();
        
        $users = $this->getAllUsers(1000);
        
        $csv = "ID,Pseudo,Email,Role,Points,Defis resolus,Rang,Date inscription,Derniere connexion\n";
        
        foreach ($users as $user) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%d,%d,%d,%s,%s\n",
                $user['id'],
                $user['pseudo'],
                $user['email'],
                $user['role'],
                $user['total_points'] ?? 0,
                $user['challenges_solved'] ?? 0,
                $user['rank_position'] ?? 0,
                $user['created_at'],
                $user['last_login'] ?? 'Jamais'
            );
        }
        
        $this->logAdminAction('DATA_EXPORT_USERS', null, 'Export utilisateurs CSV');
        
        return $csv;
    }
}