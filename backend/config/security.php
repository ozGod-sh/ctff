<?php
/**
 * Configuration Sécurité - Opération PHÉNIX
 * Gestion des tokens, CSRF, sessions et protection anti-attaques
 */

class Security {
    
    /**
     * Génère un token CSRF sécurisé
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérifie un token CSRF
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Génère un token de session sécurisé
     */
    public static function generateSessionToken() {
        return bin2hex(random_bytes(64));
    }
    
    /**
     * Sécurise les données d'entrée contre XSS
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valide une adresse email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valide un pseudo (lettres, chiffres, tirets, underscores)
     */
    public static function validatePseudo($pseudo) {
        return preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $pseudo);
    }
    
    /**
     * Valide la force d'un mot de passe
     */
    public static function validatePassword($password) {
        // Au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[a-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
    
    /**
     * Hash sécurisé d'un mot de passe
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Vérification d'un mot de passe
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Protection contre les attaques par timing
     */
    public static function constantTimeCompare($str1, $str2) {
        return hash_equals($str1, $str2);
    }
    
    /**
     * Obtient l'adresse IP réelle du client
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Obtient le User-Agent du client
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
    
    /**
     * Headers de sécurité
     */
    public static function setSecurityHeaders() {
        // Protection XSS
        header('X-XSS-Protection: 1; mode=block');
        
        // Empêcher le MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Clickjacking protection
        header('X-Frame-Options: DENY');
        
        // HTTPS strict
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        
        // CSP (Content Security Policy)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://js.hcaptcha.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self';");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Rate limiting basique
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $now = time();
        $key = $identifier;
        
        // Nettoyer les anciennes entrées
        if (isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = array_filter(
                $_SESSION['rate_limit'][$key],
                function($timestamp) use ($now, $timeWindow) {
                    return ($now - $timestamp) < $timeWindow;
                }
            );
        } else {
            $_SESSION['rate_limit'][$key] = [];
        }
        
        // Vérifier la limite
        if (count($_SESSION['rate_limit'][$key]) >= $maxAttempts) {
            return false;
        }
        
        // Ajouter la tentative actuelle
        $_SESSION['rate_limit'][$key][] = $now;
        return true;
    }
    
    /**
     * Validation des flags CTF
     */
    public static function validateCTFFlag($flag, $challengeId) {
        // Nettoyer le flag
        $flag = trim($flag);
        
        // Protection contre les injections
        if (strlen($flag) > 500) {
            return false;
        }
        
        // Validation selon le défi
        switch ($challengeId) {
            case 1:
                // Défi 1: nom exact
                return $flag === 'Zane Cipher';
                
            case 2:
                // Défi 2: patterns XSS valides
                $xssPatterns = [
                    '<img src=x onerror=alert(1)>',
                    '<svg onload=alert(1)>',
                    '<img src="x" onerror="alert(1)">',
                    '<svg onload="alert(1)">',
                    '<img src=x onerror=prompt(1)>',
                    '<svg onload=prompt(1)>'
                ];
                return in_array($flag, $xssPatterns);
                
            case 3:
                // Défi 3: format SSH précis
                return $flag === 'PHENIX{SSH_PORT:2222_IP:192.168.42.1_PASS:Zane<3Phenix_2024!}';
                
            case 4:
                // Défi 4: phrase exacte
                return $flag === 'ELLE-EST-PARTIE-A-JAMAIS-PAR-LEUR-FAUTE';
                
            case 5:
                // Défi 5: flag final
                return $flag === 'PHENIX{ULTIMATE_VICTORY_BL4CKH4T_NEUTRALIZED}';
                
            default:
                return false;
        }
    }
    
    /**
     * Log des tentatives de sécurité
     */
    public static function logSecurityEvent($event, $details = '') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => self::getClientIP(),
            'user_agent' => self::getUserAgent(),
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
}