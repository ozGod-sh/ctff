<?php
/**
 * Configuration Base de Données SQLite - Opération PHÉNIX
 * Gestionnaire de base de données sécurisé pour le CTF
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $dbPath;
    
    private function __construct() {
        $this->dbPath = __DIR__ . '/../database/phenix_ctf.db';
        $this->connect();
        $this->createTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // Créer le dossier database s'il n'existe pas
            $dbDir = dirname($this->dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            $this->pdo = new PDO(
                'sqlite:' . $this->dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
            
            // Configuration SQLite pour la sécurité
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA journal_mode = WAL');
            $this->pdo->exec('PRAGMA synchronous = NORMAL');
            
        } catch (PDOException $e) {
            error_log("Erreur connexion base de données : " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }
    }
    
    private function createTables() {
        $tables = [
            // Table des utilisateurs
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    pseudo VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(10) CHECK(role IN ('user', 'admin')) DEFAULT 'user',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login DATETIME NULL,
                    is_active BOOLEAN DEFAULT 1,
                    login_attempts INTEGER DEFAULT 0,
                    last_attempt DATETIME NULL
                )",
            
            // Table des défis
            'challenges' => "
                CREATE TABLE IF NOT EXISTS challenges (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(100) NOT NULL,
                    description TEXT NOT NULL,
                    flag_hash VARCHAR(255) NOT NULL,
                    points INTEGER NOT NULL,
                    difficulty INTEGER DEFAULT 1,
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    category VARCHAR(50) DEFAULT 'general'
                )",
            
            // Table des soumissions
            'submissions' => "
                CREATE TABLE IF NOT EXISTS submissions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    challenge_id INTEGER NOT NULL,
                    submitted_flag TEXT NOT NULL,
                    is_correct BOOLEAN DEFAULT 0,
                    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    points_earned INTEGER DEFAULT 0,
                    ip_address VARCHAR(45),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
                )",
            
            // Cache du leaderboard
            'leaderboard_cache' => "
                CREATE TABLE IF NOT EXISTS leaderboard_cache (
                    user_id INTEGER PRIMARY KEY,
                    total_points INTEGER DEFAULT 0,
                    challenges_solved INTEGER DEFAULT 0,
                    last_update DATETIME DEFAULT CURRENT_TIMESTAMP,
                    rank_position INTEGER DEFAULT 0,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )",
            
            // Table des sessions
            'sessions' => "
                CREATE TABLE IF NOT EXISTS sessions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    session_token VARCHAR(255) UNIQUE NOT NULL,
                    expires_at DATETIME NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )",
            
            // Logs administrateur
            'admin_logs' => "
                CREATE TABLE IF NOT EXISTS admin_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    admin_id INTEGER NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    target_user INTEGER NULL,
                    details TEXT NULL,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (target_user) REFERENCES users(id) ON DELETE SET NULL
                )",
            
            // Paramètres système
            'settings' => "
                CREATE TABLE IF NOT EXISTS settings (
                    key VARCHAR(100) PRIMARY KEY,
                    value TEXT NOT NULL,
                    description TEXT NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                error_log("Erreur création table $tableName : " . $e->getMessage());
                throw new Exception("Erreur lors de la création des tables");
            }
        }
        
        // Insérer les défis par défaut
        $this->insertDefaultChallenges();
        
        // Insérer les paramètres par défaut
        $this->insertDefaultSettings();
    }
    
    private function insertDefaultChallenges() {
        $challenges = [
            [
                'name' => 'Défi 1 : L\'identité de l\'ombre',
                'description' => 'Trouvez le premier flag caché dans le code source de la page corrompue',
                'flag_hash' => password_hash('Zane Cipher', PASSWORD_DEFAULT),
                'points' => 100,
                'difficulty' => 1,
                'category' => 'reconnaissance'
            ],
            [
                'name' => 'Défi 2 : Le Mirage Numérique',
                'description' => 'Exploitez une faille XSS non triviale pour obtenir le flag',
                'flag_hash' => password_hash('PHENIX{Zane_IP:192.168.42.1_OS:ArchLinux_Kernel:6.6.6-zen}', PASSWORD_DEFAULT),
                'points' => 200,
                'difficulty' => 2,
                'category' => 'web'
            ],
            [
                'name' => 'Défi 3 : Le Token Oublié',
                'description' => 'Trouvez et décodez un token JWT dissimulé pour obtenir les informations SSH',
                'flag_hash' => password_hash('PHENIX{SSH_PORT:2222_IP:192.168.42.1_PASS:Zane<3Phenix_2024!}', PASSWORD_DEFAULT),
                'points' => 300,
                'difficulty' => 3,
                'category' => 'cryptographie'
            ],
            [
                'name' => 'Défi 4 : Les Fragments d\'un Deuil',
                'description' => 'Connectez-vous via SSH et reconstituez la phrase à partir de 8 fragments',
                'flag_hash' => password_hash('ELLE-EST-PARTIE-A-JAMAIS-PAR-LEUR-FAUTE', PASSWORD_DEFAULT),
                'points' => 400,
                'difficulty' => 4,
                'category' => 'system'
            ],
            [
                'name' => 'Défi 5 : L\'Ascension du Phénix',
                'description' => 'Défi final combinant toutes vos compétences acquises',
                'flag_hash' => password_hash('PHENIX{ULTIMATE_VICTORY_BL4CKH4T_NEUTRALIZED}', PASSWORD_DEFAULT),
                'points' => 500,
                'difficulty' => 5,
                'category' => 'final'
            ]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT OR IGNORE INTO challenges (name, description, flag_hash, points, difficulty, category) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($challenges as $challenge) {
            $stmt->execute([
                $challenge['name'],
                $challenge['description'],
                $challenge['flag_hash'],
                $challenge['points'],
                $challenge['difficulty'],
                $challenge['category']
            ]);
        }
    }
    
    private function insertDefaultSettings() {
        $settings = [
            ['ctf_title', 'Opération PHÉNIX', 'Titre du CTF'],
            ['registration_open', '1', 'Autoriser les nouvelles inscriptions'],
            ['max_login_attempts', '5', 'Nombre maximum de tentatives de connexion'],
            ['session_duration', '7200', 'Durée des sessions en secondes'],
            ['leaderboard_refresh', '30', 'Fréquence de mise à jour du leaderboard en secondes'],
            ['admin_email', 'admin@cybersec-bj.tech', 'Email de l\'administrateur'],
            ['maintenance_mode', '0', 'Mode maintenance activé']
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT OR IGNORE INTO settings (key, value, description) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}