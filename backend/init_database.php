<?php
/**
 * Script d'Initialisation - Opération PHÉNIX
 * Test et configuration de la base de données
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Challenge.php';

echo "🔥 INITIALISATION OPÉRATION PHÉNIX 🔥\n";
echo "=====================================\n\n";

try {
    // Test de connexion à la base de données
    echo "📊 Test de connexion à la base de données...\n";
    $db = Database::getInstance();
    echo "✅ Base de données connectée avec succès\n\n";
    
    // Créer un utilisateur admin par défaut
    echo "👤 Création de l'utilisateur administrateur...\n";
    $user = new User();
    
    // Vérifier si admin existe déjà
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@cybersec-bj.tech']);
    
    if (!$stmt->fetch()) {
        $adminResult = $user->register('Admin', 'admin@cybersec-bj.tech', 'AdminPhoenix2024!');
        
        if ($adminResult['success']) {
            // Mettre à jour le rôle en admin
            $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
            $stmt->execute(['admin@cybersec-bj.tech']);
            echo "✅ Utilisateur admin créé avec succès\n";
            echo "   📧 Email: admin@cybersec-bj.tech\n";
            echo "   🔑 Mot de passe: AdminPhoenix2024!\n\n";
        } else {
            echo "❌ Erreur création admin: " . $adminResult['message'] . "\n\n";
        }
    } else {
        echo "ℹ️  Utilisateur admin déjà existant\n\n";
    }
    
    // Créer un utilisateur test
    echo "🧪 Création d'un utilisateur de test...\n";
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['test@cybersec-bj.tech']);
    
    if (!$stmt->fetch()) {
        $testResult = $user->register('TestUser', 'test@cybersec-bj.tech', 'TestUser123!');
        
        if ($testResult['success']) {
            echo "✅ Utilisateur test créé avec succès\n";
            echo "   📧 Email: test@cybersec-bj.tech\n";
            echo "   🔑 Mot de passe: TestUser123!\n\n";
        } else {
            echo "❌ Erreur création utilisateur test: " . $testResult['message'] . "\n\n";
        }
    } else {
        echo "ℹ️  Utilisateur test déjà existant\n\n";
    }
    
    // Vérification des défis
    echo "🎯 Vérification des défis...\n";
    $challenge = new Challenge();
    $challenges = $challenge->getAllChallenges();
    echo "✅ " . count($challenges) . " défis configurés\n";
    
    foreach ($challenges as $ch) {
        echo "   - " . $ch['name'] . " (" . $ch['points'] . " points)\n";
    }
    echo "\n";
    
    // Test des fonctions de sécurité
    echo "🛡️  Test des fonctions de sécurité...\n";
    $testToken = Security::generateCSRFToken();
    echo "✅ Génération token CSRF: " . substr($testToken, 0, 16) . "...\n";
    
    $testHash = Security::hashPassword('test123');
    $testVerify = Security::verifyPassword('test123', $testHash);
    echo "✅ Hash/Verify mot de passe: " . ($testVerify ? 'OK' : 'ERREUR') . "\n";
    
    $testIP = Security::getClientIP();
    echo "✅ Détection IP client: " . $testIP . "\n\n";
    
    // Test de validation des flags
    echo "🚩 Test de validation des flags...\n";
    $flag1Test = Security::validateCTFFlag('Zane Cipher', 1);
    echo "✅ Défi 1 - 'Zane Cipher': " . ($flag1Test ? 'VALIDE' : 'INVALIDE') . "\n";
    
    $flag2Test = Security::validateCTFFlag('<img src=x onerror=alert(1)>', 2);
    echo "✅ Défi 2 - XSS payload: " . ($flag2Test ? 'VALIDE' : 'INVALIDE') . "\n";
    
    $flag3Test = Security::validateCTFFlag('PHENIX{SSH_PORT:2222_IP:192.168.42.1_PASS:Zane<3Phenix_2024!}', 3);
    echo "✅ Défi 3 - JWT flag: " . ($flag3Test ? 'VALIDE' : 'INVALIDE') . "\n";
    
    $flag4Test = Security::validateCTFFlag('ELLE-EST-PARTIE-A-JAMAIS-PAR-LEUR-FAUTE', 4);
    echo "✅ Défi 4 - SSH fragments: " . ($flag4Test ? 'VALIDE' : 'INVALIDE') . "\n";
    
    $flag5Test = Security::validateCTFFlag('PHENIX{ULTIMATE_VICTORY_BL4CKH4T_NEUTRALIZED}', 5);
    echo "✅ Défi 5 - Final challenge: " . ($flag5Test ? 'VALIDE' : 'INVALIDE') . "\n\n";
    
    // Statistiques base de données
    echo "📈 Statistiques de la base de données:\n";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "   - Utilisateurs: $userCount\n";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM challenges");
    $challengeCount = $stmt->fetch()['count'];
    echo "   - Défis: $challengeCount\n";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM submissions");
    $submissionCount = $stmt->fetch()['count'];
    echo "   - Soumissions: $submissionCount\n";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM sessions");
    $sessionCount = $stmt->fetch()['count'];
    echo "   - Sessions actives: $sessionCount\n\n";
    
    // URLs d'accès
    echo "🌐 URLs d'accès:\n";
    echo "   - Page d'accueil: /index.html\n";
    echo "   - Connexion: /backend/pages/login.php\n";
    echo "   - Dashboard admin: /backend/pages/dashboard-admin.php\n";
    echo "   - Dashboard user: /backend/pages/dashboard-user.php\n";
    echo "   - API auth: /backend/api/auth.php\n\n";
    
    // Nettoyage des sessions expirées
    echo "🧹 Nettoyage des sessions expirées...\n";
    $cleanedSessions = $user->cleanExpiredSessions();
    echo "✅ $cleanedSessions sessions expirées supprimées\n\n";
    
    echo "🎉 INITIALISATION TERMINÉE AVEC SUCCÈS!\n";
    echo "=====================================\n";
    echo "La plateforme CTF Opération PHÉNIX est prête !\n\n";
    
    // Informations importantes
    echo "ℹ️  INFORMATIONS IMPORTANTES:\n";
    echo "- Assurez-vous que le serveur web a les droits d'écriture sur le dossier /backend/database/\n";
    echo "- Le fichier de base de données est créé automatiquement\n";
    echo "- Les logs sont stockés dans /backend/logs/\n";
    echo "- Mode maintenance: fichier .maintenance dans /backend/\n\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR LORS DE L'INITIALISATION:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

// Test de performance simple
echo "⚡ Test de performance simple...\n";
$startTime = microtime(true);

for ($i = 0; $i < 100; $i++) {
    Security::generateCSRFToken();
}

$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000;
echo "✅ 100 générations de tokens CSRF: " . round($duration, 2) . "ms\n\n";

echo "🚀 Système prêt pour l'Opération PHÉNIX !\n";
?>