<?php
/**
 * Classe Challenge - Opération PHÉNIX
 * Gestion des défis, validation des flags et scoring
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';

class Challenge {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtenir tous les défis actifs
     */
    public function getAllChallenges() {
        $stmt = $this->db->prepare("
            SELECT id, name, description, points, difficulty, category, is_active
            FROM challenges
            WHERE is_active = 1
            ORDER BY difficulty ASC, id ASC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir un défi spécifique
     */
    public function getChallenge($challengeId) {
        $stmt = $this->db->prepare("
            SELECT id, name, description, points, difficulty, category, is_active
            FROM challenges
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$challengeId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Obtenir les défis avec leur statut pour un utilisateur
     */
    public function getChallengesForUser($userId) {
        $stmt = $this->db->prepare("
            SELECT 
                c.id,
                c.name,
                c.description,
                c.points,
                c.difficulty,
                c.category,
                c.is_active,
                CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as is_solved,
                s.submitted_at as solved_at,
                s.points_earned
            FROM challenges c
            LEFT JOIN (
                SELECT challenge_id, submitted_at, points_earned, id
                FROM submissions 
                WHERE user_id = ? AND is_correct = 1
            ) s ON c.id = s.challenge_id
            WHERE c.is_active = 1
            ORDER BY c.difficulty ASC, c.id ASC
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Valider un flag soumis par un utilisateur
     */
    public function submitFlag($userId, $challengeId, $submittedFlag) {
        try {
            $ip = Security::getClientIP();
            
            // Rate limiting pour les soumissions
            if (!Security::checkRateLimit("flag_submit_$userId", RATE_LIMIT_ATTEMPTS, RATE_LIMIT_WINDOW)) {
                Security::logSecurityEvent('RATE_LIMIT_FLAG', "User: $userId, Challenge: $challengeId");
                return [
                    'success' => false, 
                    'message' => MESSAGES['rate_limit_exceeded']
                ];
            }
            
            // Vérifier que le défi existe et est actif
            $challenge = $this->getChallenge($challengeId);
            if (!$challenge) {
                return [
                    'success' => false,
                    'message' => 'Défi invalide ou inactif'
                ];
            }
            
            // Vérifier si l'utilisateur a déjà résolu ce défi
            $stmt = $this->db->prepare("
                SELECT id FROM submissions 
                WHERE user_id = ? AND challenge_id = ? AND is_correct = 1
                LIMIT 1
            ");
            $stmt->execute([$userId, $challengeId]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Défi déjà résolu'
                ];
            }
            
            // Valider le flag selon le défi
            $isCorrect = Security::validateCTFFlag($submittedFlag, $challengeId);
            $pointsEarned = $isCorrect ? $challenge['points'] : 0;
            
            // Enregistrer la soumission
            $stmt = $this->db->prepare("
                INSERT INTO submissions (user_id, challenge_id, submitted_flag, is_correct, points_earned, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $challengeId,
                $submittedFlag,
                $isCorrect ? 1 : 0,
                $pointsEarned,
                $ip
            ]);
            
            // Si correct, mettre à jour le cache leaderboard
            if ($isCorrect) {
                $this->updateUserScore($userId, $pointsEarned);
                
                logEvent('INFO', LOG_EVENTS['CHALLENGE_SOLVED'], "Défi $challengeId résolu par user $userId", [
                    'challenge_id' => $challengeId,
                    'points_earned' => $pointsEarned,
                    'user_id' => $userId
                ]);
                
                return [
                    'success' => true,
                    'message' => MESSAGES['flag_correct'],
                    'points_earned' => $pointsEarned,
                    'total_points' => $this->getUserTotalPoints($userId),
                    'challenge_name' => $challenge['name']
                ];
            } else {
                logEvent('INFO', LOG_EVENTS['CHALLENGE_ATTEMPT'], "Tentative incorrecte défi $challengeId par user $userId", [
                    'challenge_id' => $challengeId,
                    'submitted_flag' => substr($submittedFlag, 0, 50), // Limiter pour les logs
                    'user_id' => $userId
                ]);
                
                return [
                    'success' => false,
                    'message' => MESSAGES['flag_incorrect']
                ];
            }
            
        } catch (Exception $e) {
            logEvent('ERROR', 'CHALLENGE_SUBMIT_ERROR', "Erreur soumission: " . $e->getMessage(), [
                'user_id' => $userId,
                'challenge_id' => $challengeId
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la soumission'
            ];
        }
    }
    
    /**
     * Obtenir les statistiques d'un défi
     */
    public function getChallengeStats($challengeId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                COUNT(CASE WHEN is_correct = 1 THEN 1 END) as successful_attempts,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT CASE WHEN is_correct = 1 THEN user_id END) as users_solved
            FROM submissions
            WHERE challenge_id = ?
        ");
        $stmt->execute([$challengeId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Obtenir toutes les statistiques des défis (admin)
     */
    public function getAllChallengeStats() {
        $stmt = $this->db->prepare("
            SELECT 
                c.id,
                c.name,
                c.difficulty,
                c.points,
                COUNT(s.id) as total_attempts,
                COUNT(CASE WHEN s.is_correct = 1 THEN 1 END) as successful_attempts,
                COUNT(DISTINCT s.user_id) as unique_users,
                COUNT(DISTINCT CASE WHEN s.is_correct = 1 THEN s.user_id END) as users_solved,
                ROUND(
                    COUNT(CASE WHEN s.is_correct = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(s.id), 0), 
                    2
                ) as success_rate
            FROM challenges c
            LEFT JOIN submissions s ON c.id = s.challenge_id
            WHERE c.is_active = 1
            GROUP BY c.id, c.name, c.difficulty, c.points
            ORDER BY c.difficulty ASC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir le classement pour un défi spécifique (premiers à résoudre)
     */
    public function getChallengeLeaderboard($challengeId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT 
                u.pseudo,
                s.submitted_at,
                s.points_earned,
                ROW_NUMBER() OVER (ORDER BY s.submitted_at ASC) as rank
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            WHERE s.challenge_id = ? AND s.is_correct = 1
            ORDER BY s.submitted_at ASC
            LIMIT ?
        ");
        $stmt->execute([$challengeId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir l'historique des soumissions pour un défi (admin)
     */
    public function getChallengeSubmissions($challengeId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT 
                u.pseudo,
                s.submitted_flag,
                s.is_correct,
                s.submitted_at,
                s.ip_address
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            WHERE s.challenge_id = ?
            ORDER BY s.submitted_at DESC
            LIMIT ?
        ");
        $stmt->execute([$challengeId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Mettre à jour le score d'un utilisateur dans le cache
     */
    private function updateUserScore($userId, $pointsToAdd) {
        // Mettre à jour le cache leaderboard
        $stmt = $this->db->prepare("
            INSERT INTO leaderboard_cache (user_id, total_points, challenges_solved, last_update)
            VALUES (?, ?, 1, datetime('now'))
            ON CONFLICT(user_id) DO UPDATE SET
                total_points = total_points + ?,
                challenges_solved = challenges_solved + 1,
                last_update = datetime('now')
        ");
        $stmt->execute([$userId, $pointsToAdd, $pointsToAdd]);
        
        // Recalculer les rangs
        $this->updateRankings();
    }
    
    /**
     * Recalculer les rangs du leaderboard
     */
    private function updateRankings() {
        $stmt = $this->db->prepare("
            UPDATE leaderboard_cache 
            SET rank_position = (
                SELECT COUNT(*) + 1 
                FROM leaderboard_cache l2 
                WHERE l2.total_points > leaderboard_cache.total_points
            )
        ");
        $stmt->execute();
    }
    
    /**
     * Obtenir le total des points d'un utilisateur
     */
    private function getUserTotalPoints($userId) {
        $stmt = $this->db->prepare("
            SELECT total_points FROM leaderboard_cache WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result ? $result['total_points'] : 0;
    }
    
    /**
     * Obtenir les soumissions récentes globales
     */
    public function getRecentSubmissions($limit = 20) {
        $stmt = $this->db->prepare("
            SELECT 
                u.pseudo,
                c.name as challenge_name,
                s.is_correct,
                s.points_earned,
                s.submitted_at
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            JOIN challenges c ON s.challenge_id = c.id
            WHERE s.is_correct = 1
            ORDER BY s.submitted_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Créer un nouveau défi (admin)
     */
    public function createChallenge($name, $description, $flag, $points, $difficulty, $category) {
        try {
            $flagHash = password_hash($flag, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO challenges (name, description, flag_hash, points, difficulty, category)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $flagHash, $points, $difficulty, $category]);
            
            return [
                'success' => true,
                'message' => 'Défi créé avec succès',
                'challenge_id' => $this->db->lastInsertId()
            ];
            
        } catch (Exception $e) {
            logEvent('ERROR', 'CHALLENGE_CREATE_ERROR', "Erreur création défi: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du défi'
            ];
        }
    }
    
    /**
     * Activer/Désactiver un défi (admin)
     */
    public function toggleChallengeStatus($challengeId, $isActive) {
        try {
            $stmt = $this->db->prepare("
                UPDATE challenges 
                SET is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$isActive ? 1 : 0, $challengeId]);
            
            $status = $isActive ? 'activé' : 'désactivé';
            return [
                'success' => true,
                'message' => "Défi $status avec succès"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la modification'
            ];
        }
    }
    
    /**
     * Recalculer tous les scores (maintenance)
     */
    public function recalculateAllScores() {
        try {
            // Vider le cache
            $this->db->exec("DELETE FROM leaderboard_cache");
            
            // Recalculer pour chaque utilisateur
            $stmt = $this->db->prepare("
                INSERT INTO leaderboard_cache (user_id, total_points, challenges_solved)
                SELECT 
                    user_id,
                    COALESCE(SUM(points_earned), 0) as total_points,
                    COUNT(DISTINCT challenge_id) as challenges_solved
                FROM submissions
                WHERE is_correct = 1
                GROUP BY user_id
            ");
            $stmt->execute();
            
            // Recalculer les rangs
            $this->updateRankings();
            
            return ['success' => true, 'message' => 'Scores recalculés'];
            
        } catch (Exception $e) {
            logEvent('ERROR', 'SCORE_RECALC_ERROR', $e->getMessage());
            return ['success' => false, 'message' => 'Erreur recalcul'];
        }
    }
}