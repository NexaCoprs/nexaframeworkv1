<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\Profile;
use Nexa\Database\Model;
use PDO;

/**
 * Test des relations et du Query Builder
 * Ce fichier démontre l'utilisation des relations ORM dans Nexa Framework
 */

class RelationTestController
{
    private $pdo;
    
    public function __construct()
    {
        // Configuration de la base de données (simulée)
        $this->setupDatabase();
    }
    
    private function setupDatabase()
    {
        try {
            // Connexion SQLite en mémoire pour les tests
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Définir la connexion pour les modèles
            Model::setConnection($this->pdo);
            
            // Créer les tables de test
            $this->createTables();
            
            echo "✅ Base de données configurée avec succès\n\n";
        } catch (Exception $e) {
            echo "❌ Erreur de configuration de la base de données: " . $e->getMessage() . "\n";
        }
    }
    
    private function createTables()
    {
        // Table users
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Table profiles
        $this->pdo->exec("
            CREATE TABLE profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                bio TEXT,
                avatar VARCHAR(255),
                website VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        // Table posts
        $this->pdo->exec("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        // Table comments
        $this->pdo->exec("
            CREATE TABLE comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                post_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (post_id) REFERENCES posts(id)
            )
        ");
        
        // Table tags
        $this->pdo->exec("
            CREATE TABLE tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Table pivot post_tags
        $this->pdo->exec("
            CREATE TABLE post_tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id),
                FOREIGN KEY (tag_id) REFERENCES tags(id),
                UNIQUE(post_id, tag_id)
            )
        ");
    }
    
    public function seedData()
    {
        echo "=== Insertion des données de test ===\n";
        
        try {
            // Créer des utilisateurs
            $this->pdo->exec("
                INSERT INTO users (name, email, password) VALUES 
                ('Jean Dupont', 'jean@example.com', 'password123'),
                ('Marie Martin', 'marie@example.com', 'password456'),
                ('Pierre Durand', 'pierre@example.com', 'password789')
            ");
            
            // Créer des profils
            $this->pdo->exec("
                INSERT INTO profiles (user_id, bio, avatar, website) VALUES 
                (1, 'Développeur passionné', 'avatar1.jpg', 'https://jean.dev'),
                (2, 'Designer créative', 'avatar2.jpg', 'https://marie.design'),
                (3, 'Chef de projet', 'avatar3.jpg', 'https://pierre.pm')
            ");
            
            // Créer des posts
            $this->pdo->exec("
                INSERT INTO posts (user_id, title, content) VALUES 
                (1, 'Introduction à Nexa Framework', 'Nexa est un framework PHP moderne...'),
                (1, 'Les relations ORM', 'Les relations permettent de lier les modèles...'),
                (2, 'Design patterns en PHP', 'Les design patterns sont essentiels...'),
                (3, 'Gestion de projet agile', 'L\'agilité en développement...')
            ");
            
            // Créer des commentaires
            $this->pdo->exec("
                INSERT INTO comments (user_id, post_id, content) VALUES 
                (2, 1, 'Excellent article sur Nexa !'),
                (3, 1, 'Merci pour ce tutoriel'),
                (1, 3, 'Très intéressant, merci Marie'),
                (2, 4, 'Bonne approche Pierre')
            ");
            
            // Créer des tags
            $this->pdo->exec("
                INSERT INTO tags (name, slug) VALUES 
                ('PHP', 'php'),
                ('Framework', 'framework'),
                ('ORM', 'orm'),
                ('Design', 'design'),
                ('Agile', 'agile')
            ");
            
            // Associer des tags aux posts
            $this->pdo->exec("
                INSERT INTO post_tags (post_id, tag_id) VALUES 
                (1, 1), (1, 2),
                (2, 1), (2, 3),
                (3, 1), (3, 4),
                (4, 5)
            ");
            
            echo "✅ Données de test insérées avec succès\n\n";
        } catch (Exception $e) {
            echo "❌ Erreur lors de l'insertion des données: " . $e->getMessage() . "\n";
        }
    }
    
    public function testQueryBuilder()
    {
        echo "=== Test du Query Builder ===\n";
        
        try {
            // Test 1: Requête simple
            echo "1. Requête simple - Tous les utilisateurs:\n";
            $users = User::all();
            foreach ($users as $user) {
                echo "   - {$user->name} ({$user->email})\n";
            }
            echo "\n";
            
            // Test 2: Requête avec WHERE
            echo "2. Requête avec WHERE - Utilisateur par email:\n";
            $user = User::where('email', '=', 'jean@example.com')->first();
            if ($user) {
                echo "   - Trouvé: {$user->name}\n";
            }
            echo "\n";
            
            // Test 3: Requête avec LIMIT
            echo "3. Requête avec LIMIT - 2 premiers posts:\n";
            $posts = Post::limit(2)->get();
            foreach ($posts as $post) {
                echo "   - {$post->title}\n";
            }
            echo "\n";
            
            // Test 4: Requête avec ORDER BY
            echo "4. Requête avec ORDER BY - Posts par titre:\n";
            $posts = Post::orderBy('title', 'ASC')->get();
            foreach ($posts as $post) {
                echo "   - {$post->title}\n";
            }
            echo "\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur dans le test du Query Builder: " . $e->getMessage() . "\n";
        }
    }
    
    public function testRelations()
    {
        echo "=== Test des Relations ===\n";
        
        try {
            // Test 1: Relation HasMany (User -> Posts)
            echo "1. Relation HasMany - Posts d'un utilisateur:\n";
            $user = User::find(1);
            if ($user) {
                $posts = $user->posts()->getResults();
                echo "   Utilisateur: {$user->name}\n";
                echo "   Posts:\n";
                if (is_array($posts)) {
                    foreach ($posts as $post) {
                        echo "     - {$post->title}\n";
                    }
                } else {
                    echo "     Aucun post trouvé\n";
                }
            }
            echo "\n";
            
            // Test 2: Relation BelongsTo (Post -> User)
            echo "2. Relation BelongsTo - Auteur d'un post:\n";
            $post = Post::find(1);
            if ($post) {
                $author = $post->user()->getResults();
                echo "   Post: {$post->title}\n";
                if ($author) {
                    echo "   Auteur: {$author->name}\n";
                } else {
                    echo "   Aucun auteur trouvé\n";
                }
            }
            echo "\n";
            
            // Test 3: Relation HasOne (User -> Profile)
            echo "3. Relation HasOne - Profil d'un utilisateur:\n";
            $user = User::find(1);
            if ($user) {
                $profile = $user->profile()->getResults();
                echo "   Utilisateur: {$user->name}\n";
                if ($profile) {
                    echo "   Bio: {$profile->bio}\n";
                    echo "   Site web: {$profile->website}\n";
                } else {
                    echo "   Aucun profil trouvé\n";
                }
            }
            echo "\n";
            
            // Test 4: Relation BelongsToMany (Post -> Tags)
            echo "4. Relation BelongsToMany - Tags d'un post:\n";
            $post = Post::find(1);
            if ($post) {
                $tags = $post->tags()->getResults();
                echo "   Post: {$post->title}\n";
                echo "   Tags:\n";
                if (is_array($tags)) {
                    foreach ($tags as $tag) {
                        echo "     - {$tag->name}\n";
                    }
                } else {
                    echo "     Aucun tag trouvé\n";
                }
            }
            echo "\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur dans le test des relations: " . $e->getMessage() . "\n";
        }
    }
    
    public function testAdvancedQueries()
    {
        echo "=== Test des Requêtes Avancées ===\n";
        
        try {
            // Test 1: Requête avec JOIN
            echo "1. Requête avec JOIN - Posts avec auteurs:\n";
            $stmt = $this->pdo->query("
                SELECT p.title, u.name as author 
                FROM posts p 
                JOIN users u ON p.user_id = u.id
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "   - {$row['title']} par {$row['author']}\n";
            }
            echo "\n";
            
            // Test 2: Compter les relations
            echo "2. Comptage - Nombre de posts par utilisateur:\n";
            $stmt = $this->pdo->query("
                SELECT u.name, COUNT(p.id) as post_count 
                FROM users u 
                LEFT JOIN posts p ON u.id = p.user_id 
                GROUP BY u.id, u.name
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "   - {$row['name']}: {$row['post_count']} post(s)\n";
            }
            echo "\n";
            
            // Test 3: Requête complexe avec sous-requête
            echo "3. Requête complexe - Utilisateurs avec plus d'un post:\n";
            $stmt = $this->pdo->query("
                SELECT u.name 
                FROM users u 
                WHERE (SELECT COUNT(*) FROM posts p WHERE p.user_id = u.id) > 1
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "   - {$row['name']}\n";
            }
            echo "\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur dans les requêtes avancées: " . $e->getMessage() . "\n";
        }
    }
}

/**
 * Fonction principale pour exécuter tous les tests
 */
function runRelationTests()
{
    echo "=== Test des Relations et Query Builder - Nexa Framework ===\n\n";
    
    try {
        $tester = new RelationTestController();
        
        // Insérer les données de test
        $tester->seedData();
        
        // Tester le Query Builder
        $tester->testQueryBuilder();
        
        // Tester les relations
        $tester->testRelations();
        
        // Tester les requêtes avancées
        $tester->testAdvancedQueries();
        
        echo "✅ Tous les tests terminés avec succès !\n";
        
    } catch (Exception $e) {
        echo "❌ Erreur générale: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

// Exécuter les tests si le script est appelé directement
if (php_sapi_name() === 'cli') {
    runRelationTests();
} else {
    echo "<h1>Test des Relations et Query Builder</h1>";
    echo "<p>Exécutez ce script en ligne de commande pour voir les tests.</p>";
    echo "<pre>php test_relations.php</pre>";
}