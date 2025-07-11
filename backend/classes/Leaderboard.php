<?php
/**
 * Classe Leaderboard - Opération PHÉNIX
 * Gestion des classements temps réel et statistiques avancées
 * 
 * @version 1.0
 * @author Agents SQLite Expert + PHP Senior
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/constants.php';

class Leaderboard {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtenir le classement général principal
     */
    public function getMainLeaderboard($limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.pseudo,
                u.created_at,
                l.total_points,
                l.challenges_solved,
                l.rank_position,
                l.last_update,
                CASE 
                    WHEN u.last_login > datetime('now', '-1 hour') THEN 'online'
                    WHEN u.last_login > datetime('now', '-24 hours') THEN 'recent'
                    ELSE 'offline'
                END as status,
                -- Calculer la progression récente (dernières 24h)
                COALESCE((
                    SELECT SUM(points_earned) 
                    FROM submissions s 
                    WHERE s.user_id = u.id 
                    AND s.is_correct = 1 
                    AND s.submitted_at > datetime('now', '-24 hours')
                ), 0) as points_24h
            FROM users u
            JOIN leaderboard_cache l ON u.id = l.user_id
            WHERE u.is_active = 1 AND l.total_points > 0
            ORDER BY l.rank_position ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir le classement avec filtres avancés
     */
    public function getFilteredLeaderboard($filters = []) {
        $whereConditions = ['u.is_active = 1'];
        $params = [];
        
        // Filtre par période
        if (!empty($filters['period'])) {
            $dateCondition = match($filters['period']) {
                'today' => "datetime('now', '-1 day')",
                'week' => "datetime('now', '-7 days')",
                'month' => "datetime('now', '-30 days')",
                default => null
            };
            
            if ($dateCondition) {
                $whereConditions[] = "EXISTS (
                    SELECT 1 FROM submissions s 
                    WHERE s.user_id = u.id 
                    AND s.is_correct = 1 
                    AND s.submitted_at > $dateCondition
                )";
            }
        }
        
        // Filtre par nombre de défis résolus
        if (!empty($filters['min_challenges'])) {
            $whereConditions[] = "l.challenges_solved >= ?";
            $params[] = (int)$filters['min_challenges'];
        }
        
        // Filtre par points minimum
        if (!empty($filters['min_points'])) {
            $whereConditions[] = "l.total_points >= ?";
            $params[] = (int)$filters['min_points'];
        }
        
        // Recherche par pseudo
        if (!empty($filters['search'])) {
            $whereConditions[] = "u.pseudo LIKE ?";
            $params[] = "%{$filters['search']}%";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.pseudo,
                u.created_at,
                l.total_points,
                l.challenges_solved,
                l.rank_position,
                l.last_update,
                -- Calculer les points pour la période si spécifiée
                " . (isset($filters['period']) && $dateCondition ? "
                COALESCE((
                    SELECT SUM(points_earned) 
                    FROM submissions s 
                    WHERE s.user_id = u.id 
                    AND s.is_correct = 1 
                    AND s.submitted_at > $dateCondition
                ), 0) as period_points" : "0 as period_points") . "
            FROM users u
            JOIN leaderboard_cache l ON u.id = l.user_id
            WHERE $whereClause
            ORDER BY " . (isset($filters['period']) && $dateCondition ? 'period_points DESC' : 'l.rank_position ASC') . "
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir le classement par défi spécifique
     */
    public function getChallengeLeaderboard($challengeId, $limit = 20) {
        $stmt = $this->db->prepare("
            SELECT 
                u.pseudo,
                s.submitted_at,
                s.points_earned,
                ROW_NUMBER() OVER (ORDER BY s.submitted_at ASC) as solve_rank,
                -- Temps depuis le début du CTF
                ROUND((julianday(s.submitted_at) - julianday('2025-01-01 00:00:00')) * 24 * 60, 0) as solve_time_minutes
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
     * Obtenir les statistiques de progression d'un utilisateur
     */
    public function getUserProgression($userId, $period = '30d') {
        $dateCondition = match($period) {
            '24h' => "datetime('now', '-1 day')",
            '7d' => "datetime('now', '-7 days')",
            '30d' => "datetime('now', '-30 days')",
            default => "datetime('now', '-30 days')"
        };
        
        // Progression des points par jour
        $stmt = $this->db->prepare("
            SELECT 
                DATE(submitted_at) as date,
                SUM(points_earned) as points_earned,
                COUNT(*) as challenges_solved,
                -- Cumul des points
                SUM(SUM(points_earned)) OVER (ORDER BY DATE(submitted_at)) as cumulative_points
            FROM submissions
            WHERE user_id = ? 
            AND is_correct = 1 
            AND submitted_at > $dateCondition
            GROUP BY DATE(submitted_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$userId]);
        $progression = $stmt->fetchAll();
        
        // Statistiques comparatives
        $stmt = $this->db->prepare("
            SELECT 
                l.rank_position,
                l.total_points,
                l.challenges_solved,
                (SELECT COUNT(*) FROM leaderboard_cache WHERE total_points > 0) as total_ranked_users,
                -- Percentile
                ROUND(
                    (1.0 - (CAST(l.rank_position AS FLOAT) / (SELECT COUNT(*) FROM leaderboard_cache WHERE total_points > 0))) * 100, 
                    1
                ) as percentile,
                -- Défis restants
                (SELECT COUNT(*) FROM challenges WHERE is_active = 1) - l.challenges_solved as remaining_challenges
            FROM leaderboard_cache l
            WHERE l.user_id = ?
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();
        
        return [
            'progression' => $progression,
            'stats' => $stats
        ];
    }
    
    /**
     * Obtenir les changements récents dans le classement
     */
    public function getRecentRankingChanges($limit = 10) {
        // Simulation des changements de rang (nécessiterait une table d'historique)
        // Pour l'instant, on retourne les dernières résolutions
        $stmt = $this->db->prepare("
            SELECT 
                u.pseudo,
                c.name as challenge_name,
                s.points_earned,
                s.submitted_at,
                l.rank_position as current_rank,
                'solve' as change_type
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            JOIN challenges c ON s.challenge_id = c.id
            JOIN leaderboard_cache l ON u.id = l.user_id
            WHERE s.is_correct = 1
            ORDER BY s.submitted_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir les statistiques globales du leaderboard
     */
    public function getLeaderboardStats() {
        $stats = [];
        
        // Statistiques générales
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN total_points > 0 THEN 1 END) as active_users,
                MAX(total_points) as highest_score,
                AVG(total_points) as average_score,
                MAX(challenges_solved) as max_challenges_solved,
                AVG(challenges_solved) as avg_challenges_solved
            FROM leaderboard_cache
        ");
        $stmt->execute();
        $stats['general'] = $stmt->fetch();
        
        // Distribution des scores par tranches
        $stmt = $this->db->prepare("
            SELECT 
                CASE 
                    WHEN total_points = 0 THEN '0'
                    WHEN total_points <= 100 THEN '1-100'
                    WHEN total_points <= 300 THEN '101-300'
                    WHEN total_points <= 600 THEN '301-600'
                    WHEN total_points <= 1000 THEN '601-1000'
                    ELSE '1000+'
                END as score_range,
                COUNT(*) as user_count
            FROM leaderboard_cache
            GROUP BY score_range
            ORDER BY 
                CASE score_range
                    WHEN '0' THEN 0
                    WHEN '1-100' THEN 1
                    WHEN '101-300' THEN 2
                    WHEN '301-600' THEN 3
                    WHEN '601-1000' THEN 4
                    ELSE 5
                END
        ");
        $stmt->execute();
        $stats['score_distribution'] = $stmt->fetchAll();
        
        // Activité récente (dernières 24h)
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT s.user_id) as active_users_24h,
                COUNT(*) as submissions_24h,
                COUNT(CASE WHEN s.is_correct = 1 THEN 1 END) as successful_submissions_24h
            FROM submissions s
            WHERE s.submitted_at > datetime('now', '-24 hours')
        ");
        $stmt->execute();
        $stats['activity_24h'] = $stmt->fetch();
        
        return $stats;
    }
    
    /**
     * Obtenir les données pour graphiques temps réel
     */
    public function getAnalyticsData($period = '7d') {
        $dateCondition = match($period) {
            '24h' => "datetime('now', '-1 day')",
            '7d' => "datetime('now', '-7 days')",
            '30d' => "datetime('now', '-30 days')",
            default => "datetime('now', '-7 days')"
        };
        
        $analytics = [];
        
        // Evolution des scores dans le temps
        $stmt = $this->db->prepare("
            SELECT 
                DATE(submitted_at) as date,
                SUM(points_earned) as total_points_earned,
                COUNT(DISTINCT user_id) as active_users,
                COUNT(*) as total_submissions
            FROM submissions
            WHERE is_correct = 1 AND submitted_at > $dateCondition
            GROUP BY DATE(submitted_at)
            ORDER BY date ASC
        ");
        $stmt->execute();
        $analytics['points_timeline'] = $stmt->fetchAll();
        
        // Défis les plus populaires
        $stmt = $this->db->prepare("
            SELECT 
                c.name,
                c.points,
                COUNT(s.id) as total_attempts,
                COUNT(CASE WHEN s.is_correct = 1 THEN 1 END) as successful_solves,
                ROUND(COUNT(CASE WHEN s.is_correct = 1 THEN 1 END) * 100.0 / COUNT(s.id), 1) as success_rate
            FROM challenges c
            LEFT JOIN submissions s ON c.id = s.challenge_id
            WHERE c.is_active = 1 AND s.submitted_at > $dateCondition
            GROUP BY c.id
            ORDER BY total_attempts DESC
            LIMIT 5
        ");
        $stmt->execute();
        $analytics['popular_challenges'] = $stmt->fetchAll();
        
        return $analytics;
    }
    
    /**
     * Rechercher un utilisateur dans le classement
     */
    public function searchUser($query) {
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.pseudo,
                l.total_points,
                l.challenges_solved,
                l.rank_position,
                -- Contexte (utilisateurs autour)
                (SELECT COUNT(*) FROM leaderboard_cache WHERE rank_position < l.rank_position AND total_points > 0) as users_above,
                (SELECT COUNT(*) FROM leaderboard_cache WHERE rank_position > l.rank_position AND total_points > 0) as users_below
            FROM users u
            JOIN leaderboard_cache l ON u.id = l.user_id
            WHERE u.pseudo LIKE ? AND u.is_active = 1
            ORDER BY l.rank_position ASC
            LIMIT 10
        ");
        $stmt->execute(["%$query%"]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtenir le contexte autour d'un utilisateur dans le classement
     */
    public function getUserContext($userId, $radius = 5) {
        // Obtenir le rang de l'utilisateur
        $stmt = $this->db->prepare("
            SELECT rank_position FROM leaderboard_cache WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $userRank = $stmt->fetchColumn();
        
        if (!$userRank) {
            return [];
        }
        
        // Obtenir les utilisateurs autour
        $stmt = $this->db->prepare("
            SELECT 
                u.id,
                u.pseudo,
                l.total_points,
                l.challenges_solved,
                l.rank_position,
                CASE WHEN u.id = ? THEN 1 ELSE 0 END as is_current_user
            FROM users u
            JOIN leaderboard_cache l ON u.id = l.user_id
            WHERE l.rank_position BETWEEN ? AND ?
            AND u.is_active = 1
            ORDER BY l.rank_position ASC
        ");
        
        $minRank = max(1, $userRank - $radius);
        $maxRank = $userRank + $radius;
        
        $stmt->execute([$userId, $minRank, $maxRank]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Mettre à jour le cache du leaderboard
     */
    public function updateLeaderboardCache() {
        try {
            // Recalculer tous les totaux
            $stmt = $this->db->prepare("
                UPDATE leaderboard_cache 
                SET 
                    total_points = (
                        SELECT COALESCE(SUM(points_earned), 0) 
                        FROM submissions 
                        WHERE user_id = leaderboard_cache.user_id AND is_correct = 1
                    ),
                    challenges_solved = (
                        SELECT COUNT(DISTINCT challenge_id) 
                        FROM submissions 
                        WHERE user_id = leaderboard_cache.user_id AND is_correct = 1
                    ),
                    last_update = datetime('now')
            ");
            $stmt->execute();
            
            // Recalculer les rangs
            $stmt = $this->db->prepare("
                UPDATE leaderboard_cache 
                SET rank_position = (
                    SELECT COUNT(*) + 1 
                    FROM leaderboard_cache l2 
                    WHERE l2.total_points > leaderboard_cache.total_points
                )
            ");
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Cache leaderboard mis à jour'];
            
        } catch (Exception $e) {
            logEvent('ERROR', 'LEADERBOARD_CACHE_UPDATE_ERROR', $e->getMessage());
            return ['success' => false, 'message' => 'Erreur mise à jour cache'];
        }
    }
    
    /**
     * Obtenir les records et achievements
     */
    public function getRecords() {
        $records = [];
        
        // Premier à résoudre chaque défi
        $stmt = $this->db->prepare("
            SELECT DISTINCT
                c.name as challenge_name,
                u.pseudo as first_solver,
                s.submitted_at as solve_time,
                s.points_earned
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            JOIN challenges c ON s.challenge_id = c.id
            WHERE s.is_correct = 1
            AND s.submitted_at = (
                SELECT MIN(submitted_at) 
                FROM submissions s2 
                WHERE s2.challenge_id = s.challenge_id AND s2.is_correct = 1
            )
            ORDER BY s.submitted_at ASC
        ");
        $stmt->execute();
        $records['first_solvers'] = $stmt->fetchAll();
        
        // Résolution la plus rapide (hypothétique basé sur timestamp)
        $stmt = $this->db->prepare("
            SELECT 
                u.pseudo,
                COUNT(*) as challenges_solved,
                SUM(s.points_earned) as total_points,
                MIN(s.submitted_at) as first_solve,
                MAX(s.submitted_at) as last_solve,
                ROUND(
                    (julianday(MAX(s.submitted_at)) - julianday(MIN(s.submitted_at))) * 24 * 60, 
                    0
                ) as completion_time_minutes
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            WHERE s.is_correct = 1
            GROUP BY s.user_id
            HAVING challenges_solved = (SELECT COUNT(*) FROM challenges WHERE is_active = 1)
            ORDER BY completion_time_minutes ASC
            LIMIT 5
        ");
        $stmt->execute();
        $records['fastest_completion'] = $stmt->fetchAll();
        
        return $records;
    }
    
    /**
     * Exporter le leaderboard (CSV)
     */
    public function exportLeaderboard($format = 'csv') {
        $leaderboard = $this->getMainLeaderboard(1000);
        
        if ($format === 'csv') {
            $csv = "Rang,Pseudo,Points,Defis resolus,Derniere activite\n";
            
            foreach ($leaderboard as $user) {
                $csv .= sprintf(
                    "%d,%s,%d,%d,%s\n",
                    $user['rank_position'],
                    $user['pseudo'],
                    $user['total_points'],
                    $user['challenges_solved'],
                    $user['last_update']
                );
            }
            
            return $csv;
        }
        
        return $leaderboard;
    }
}