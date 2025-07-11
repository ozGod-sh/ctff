<?php
/**
 * Classe User - Opération PHÉNIX
 * Gestion complète des utilisateurs, authentification et sessions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';

class User {
    private $db;
    private $userData;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->userData = null;
    }
    
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register($pseudo, $email, $password) {
        try {
            // Validation des données
            if (!Security::validatePseudo($pseudo)) {
                return ['success' => false, 'message' => 'Pseudo invalide (3-30 caractères, lettres, chiffres, tirets)'];
            }
            
            if (!Security::validateEmail($email)) {
                return ['success' => false, 'message' => 'Email invalide'];
            }
            
            if (!Security::validatePassword($password)) {
                return ['success' => false, 'message' => 'Mot de passe trop faible (min 8 caractères, majuscule, minuscule, chiffre)'];
            }
            
            // Vérifier si l'utilisateur existe déjà
            $stmt = $this->db->prepare("SELECT id FROM users WHERE pseudo = ? OR email = ?");
            $stmt->execute([$pseudo, $email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Pseudo ou email déjà utilisé'];
            }
            
            // Hash du mot de passe
            $passwordHash = Security::hashPassword($password);
            
            // Insertion du nouvel utilisateur
            $stmt = $this->db->prepare("
                INSERT INTO users (pseudo, email, password_hash, created_at) 
                VALUES (?, ?, ?, datetime('now'))
            ");
            
            $stmt->execute([$pseudo, $email, $passwordHash]);
            $userId = $this->db->lastInsertId();
            
            // Initialiser le cache leaderboard
            $stmt = $this->db->prepare("
                INSERT INTO leaderboard_cache (user_id, total_points, challenges_solved) 
                VALUES (?, 0, 0)
            ");
            $stmt->execute([$userId]);
            
            // Log de l'événement
            logEvent('INFO', LOG_EVENTS['USER_REGISTER'], "Nouveau utilisateur inscrit: $pseudo", [
                'user_id' => $userId,
                'email' => $email
            ]);
            
            return ['success' => true, 'message' => MESSAGES['registration_success']];
            
        } catch (Exception $e) {
            logEvent('ERROR', LOG_EVENTS['USER_REGISTER'], "Erreur inscription: " . $e->getMessage());
            return ['success' => false, 'message' => MESSAGES['registration_failed']];
        }
    }
    
    /**
     * Connexion d'un utilisateur
     */
    public function login($email, $password) {
        try {
            $ip = Security::getClientIP();
            
            // Rate limiting
            if (!Security::checkRateLimit("login_$ip", MAX_LOGIN_ATTEMPTS, LOGIN_COOLDOWN)) {
                Security::logSecurityEvent('RATE_LIMIT_LOGIN', "IP: $ip");
                return ['success' => false, 'message' => MESSAGES['rate_limit_exceeded']];
            }
            
            // Récupérer l'utilisateur
            $stmt = $this->db->prepare("
                SELECT id, pseudo, email, password_hash, role, is_active, login_attempts, last_attempt
                FROM users WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Security::logSecurityEvent('LOGIN_FAILED', "Email inexistant: $email");
                return ['success' => false, 'message' => MESSAGES['login_failed']];
            }
            
            // Vérifier si le compte est actif
            if (!$user['is_active']) {
                Security::logSecurityEvent('LOGIN_DISABLED_ACCOUNT', "Compte désactivé: $email");
                return ['success' => false, 'message' => 'Compte désactivé'];
            }
            
            // Vérifier les tentatives de connexion
            if ($user['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $lastAttempt = strtotime($user['last_attempt']);
                if (time() - $lastAttempt < LOGIN_COOLDOWN) {
                    return ['success' => false, 'message' => 'Compte temporairement bloqué'];
                } else {
                    // Réinitialiser les tentatives après cooldown
                    $this->resetLoginAttempts($user['id']);
                }
            }
            
            // Vérification du mot de passe
            if (!Security::verifyPassword($password, $user['password_hash'])) {
                $this->incrementLoginAttempts($user['id']);
                Security::logSecurityEvent('LOGIN_FAILED', "Mot de passe incorrect pour: $email");
                return ['success' => false, 'message' => MESSAGES['login_failed']];
            }
            
            // Connexion réussie
            $this->resetLoginAttempts($user['id']);
            $this->updateLastLogin($user['id']);
            
            // Créer la session
            $sessionToken = Security::generateSessionToken();
            $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
            
            $stmt = $this->db->prepare("
                INSERT INTO sessions (user_id, session_token, expires_at, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'],
                $sessionToken,
                $expiresAt,
                $ip,
                Security::getUserAgent()
            ]);
            
            // Démarrer la session PHP
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_pseudo'] = $user['pseudo'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['session_token'] = $sessionToken;
            
            $this->userData = $user;
            
            logEvent('INFO', LOG_EVENTS['USER_LOGIN'], "Connexion réussie pour: " . $user['pseudo'], [
                'user_id' => $user['id']
            ]);
            
            return ['success' => true, 'message' => MESSAGES['login_success'], 'user' => [
                'id' => $user['id'],
                'pseudo' => $user['pseudo'],
                'role' => $user['role']
            ]];
            
        } catch (Exception $e) {
            logEvent('ERROR', LOG_EVENTS['USER_LOGIN'], "Erreur connexion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur de connexion'];
        }
    }
    
    /**
     * Déconnexion d'un utilisateur
     */
    public function logout() {
        session_start();
        
        if (isset($_SESSION['session_token'])) {
            // Supprimer la session de la base
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE session_token = ?");
            $stmt->execute([$_SESSION['session_token']]);
        }
        
        if (isset($_SESSION['user_pseudo'])) {
            logEvent('INFO', LOG_EVENTS['USER_LOGOUT'], "Déconnexion: " . $_SESSION['user_pseudo']);
        }
        
        // Détruire la session PHP
        session_destroy();
        
        return ['success' => true, 'message' => 'Déconnexion réussie'];
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn() {
        session_start();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        // Vérifier la validité de la session
        $stmt = $this->db->prepare("
            SELECT u.id, u.pseudo, u.role, s.expires_at 
            FROM users u 
            JOIN sessions s ON u.id = s.user_id 
            WHERE s.session_token = ? AND s.expires_at > datetime('now')
        ");
        $stmt->execute([$_SESSION['session_token']]);
        $session = $stmt->fetch();
        
        if (!$session) {
            $this->logout();
            return false;
        }
        
        $this->userData = $session;
        return true;
    }
    
    /**
     * Obtenir les données de l'utilisateur connecté
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->db->prepare("
            SELECT u.*, l.total_points, l.challenges_solved, l.rank_position
            FROM users u
            LEFT JOIN leaderboard_cache l ON u.id = l.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        return $stmt->fetch();
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Obtenir les statistiques utilisateur
     */
    public function getUserStats($userId = null) {
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        if (!$userId) return null;
        
        $stmt = $this->db->prepare("
            SELECT 
                u.pseudo,
                u.created_at,
                l.total_points,
                l.challenges_solved,
                l.rank_position,
                (SELECT COUNT(*) FROM submissions WHERE user_id = ? AND is_correct = 1) as solved_challenges,
                (SELECT COUNT(*) FROM submissions WHERE user_id = ?) as total_attempts
            FROM users u
            LEFT JOIN leaderboard_cache l ON u.id = l.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId, $userId, $userId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Obtenir les soumissions récentes de l'utilisateur
     */
    public function getRecentSubmissions($userId = null, $limit = 10) {
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        if (!$userId) return [];
        
        $stmt = $this->db->prepare("
            SELECT 
                s.submitted_at,
                s.is_correct,
                s.points_earned,
                c.name as challenge_name,
                c.difficulty
            FROM submissions s
            JOIN challenges c ON s.challenge_id = c.id
            WHERE s.user_id = ?
            ORDER BY s.submitted_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Vérifier si un utilisateur a résolu un défi
     */
    public function hasUserSolvedChallenge($userId, $challengeId) {
        $stmt = $this->db->prepare("
            SELECT id FROM submissions 
            WHERE user_id = ? AND challenge_id = ? AND is_correct = 1
            LIMIT 1
        ");
        $stmt->execute([$userId, $challengeId]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Méthodes privées pour la gestion des tentatives de connexion
     */
    private function incrementLoginAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET login_attempts = login_attempts + 1, last_attempt = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    private function resetLoginAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET login_attempts = 0, last_attempt = NULL
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login = datetime('now')
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Nettoyer les sessions expirées (méthode utilitaire)
     */
    public function cleanExpiredSessions() {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE expires_at < datetime('now')");
        $stmt->execute();
        
        return $stmt->rowCount();
    }
}