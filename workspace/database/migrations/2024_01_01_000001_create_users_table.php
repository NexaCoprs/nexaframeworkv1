<?php

use Nexa\Database\Migration;
use Nexa\Database\Blueprint;
use Nexa\Database\Schema;
use Nexa\Database\QueryBuilder;
// use Nexa\Attributes\AutoDiscover; // Removed auto-discovery
use Nexa\Attributes\Quantum;
use Nexa\Attributes\Secure;
use DateTime;
use PDO;

/**
 * Migration pour la table users
 * Avec sécurité avancée et optimisations
 */
#[Secure]
class CreateUsersTable extends Migration
{
    /**
     * Exécuter la migration
     */
    public function up()
    {
        $this->schema->create('users', function (Blueprint $table) {
            // Clé primaire
            $table->id();
            
            // Champs utilisateur avec validation intégrée
            $table->string('name')->index();
            $table->string('email')->unique()->encrypted(); // Chiffrement sécurisé
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->encrypted(); // Hash sécurisé
            $table->string('avatar')->nullable();
            
            // Préférences utilisateur avec compression intelligente
            $table->json('preferences')->nullable()->compressed();
            
            // Tokens et sécurité
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable()->index();
            
            // Timestamps avec optimisation
            $table->timestamps();
            
            // Index composites pour optimiser les performances
            $table->index(['email', 'email_verified_at']);
            $table->index(['created_at', 'last_login_at']);
            
            // Contraintes de sécurité
            $table->check('length(name) >= 2', 'name_min_length');
            $table->check('email LIKE "%@%.%"', 'email_format');
        });
        
        // Commentaire de table pour documentation
        // Add table comment using raw SQL since schema doesn't have comment method
        $this->statement("ALTER TABLE users COMMENT = 'Table des utilisateurs avec architecture moderne'");
    }
    
    /**
     * Annuler la migration
     */
    public function down()
    {
        $this->schema->dropIfExists('users');
    }
    
    /**
     * Données de test
     */
    public function seedData()
    {
        $now = date('Y-m-d H:i:s');
        
        // Utilisateur administrateur par défaut
        $this->seed('users', [
            [
                'name' => 'Administrateur Nexa',
                'email' => 'admin@nexa.dev',
                'email_verified_at' => $now,
                'password' => $this->hashPassword('SecureNexa2024!'),
                'preferences' => json_encode([
                    'theme' => 'dark',
                    'language' => 'fr',
                    'notifications' => true,
                    'advanced_mode' => true
                ]),
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Utilisateur Demo',
                'email' => 'demo@nexa.dev',
                'email_verified_at' => $now,
                'password' => $this->hashPassword('DemoNexa2024!'),
                'preferences' => json_encode([
                    'theme' => 'light',
                    'language' => 'fr',
                    'notifications' => false,
                    'advanced_mode' => false
                ]),
                'created_at' => $now,
                'updated_at' => $now
            ]
        ]);
    }
    
    /**
     * Hash password securely
     */
    private function hashPassword($password)
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Optimisations post-migration
     */
    public function optimize()
    {
        // Analyse des performances
        $this->statement('ANALYZE TABLE users');
        
        // Optimisation des index
        $this->statement('OPTIMIZE TABLE users');
        
        // Cache warming pour les requêtes fréquentes
        $this->warmCache();
    }
    
    /**
     * Préchauffage du cache intelligent
     */
    private function warmCache()
    {
        $thirtyDaysAgo = (new DateTime())->modify('-30 days')->format('Y-m-d H:i:s');
        $sevenDaysAgo = (new DateTime())->modify('-7 days')->format('Y-m-d H:i:s');
        
        // Précharger les statistiques utilisateurs
        $connection = $this->connection;
        cache()->remember('users_stats', 3600, function() use ($thirtyDaysAgo, $connection) {
            // Use raw SQL queries with the connection
            $totalStmt = $connection->query('SELECT COUNT(*) as count FROM users');
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $verifiedStmt = $connection->query('SELECT COUNT(*) as count FROM users WHERE email_verified_at IS NOT NULL');
            $verified = $verifiedStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $activeStmt = $connection->prepare('SELECT COUNT(*) as count FROM users WHERE last_login_at >= ?');
            $activeStmt->execute([$thirtyDaysAgo]);
            $active = $activeStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return [
                'total' => $total,
                'verified' => $verified,
                'active' => $active
            ];
        });
        
        // Précharger les utilisateurs actifs
        cache()->remember('active_users', 1800, function() use ($sevenDaysAgo, $connection) {
            $stmt = $connection->prepare('
                SELECT id, name, email, last_login_at 
                FROM users 
                WHERE email_verified_at IS NOT NULL 
                AND last_login_at >= ? 
                ORDER BY last_login_at DESC 
                LIMIT 100
            ');
            $stmt->execute([$sevenDaysAgo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
}