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
 * Test simple des relations et du Query Builder
 */

class SimpleRelationTest
{
    private $pdo;
    
    public function __construct()
    {
        $this->setupDatabase();
    }
    
    private function setupDatabase()
    {
        try {
            // Connexion SQLite en mémoire
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Définir la connexion pour les modèles
            Model::setConnection($this->pdo);
            
            // Créer les tables
            $this->createTables();
            $this->insertTestData();
            
            echo "✅ Base de données configurée\n\n";
        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "\n";
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
                password VARCHAR(255) NOT NULL
            )
        ");
        
        // Table posts
        $this->pdo->exec("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL
            )
        ");
    }
    
    private function insertTestData()
    {
        // Insérer des utilisateurs
        $this->pdo->exec("
            INSERT INTO users (name, email, password) VALUES 
            ('Jean Dupont', 'jean@example.com', 'password123'),
            ('Marie Martin', 'marie@example.com', 'password456')
        ");
        
        // Insérer des posts
        $this->pdo->exec("
            INSERT INTO posts (user_id, title, content) VALUES 
            (1, 'Premier post de Jean', 'Contenu du premier post'),
            (1, 'Deuxième post de Jean', 'Contenu du deuxième post'),
            (2, 'Post de Marie', 'Contenu du post de Marie')
        ");
    }
    
    public function testBasicQueries()
    {
        echo "=== Test des requêtes de base ===\n";
        
        try {
            // Test 1: Récupérer tous les utilisateurs
            echo "1. Tous les utilisateurs:\n";
            $users = User::all();
            foreach ($users as $user) {
                echo "   - ID: {$user->id}, Nom: {$user->name}, Email: {$user->email}\n";
            }
            echo "\n";
            
            // Test 2: Trouver un utilisateur par ID
            echo "2. Utilisateur avec ID 1:\n";
            $user = User::find(1);
            if ($user) {
                echo "   - Trouvé: {$user->name} ({$user->email})\n";
            } else {
                echo "   - Aucun utilisateur trouvé\n";
            }
            echo "\n";
            
            // Test 3: Récupérer tous les posts
            echo "3. Tous les posts:\n";
            $posts = Post::all();
            foreach ($posts as $post) {
                echo "   - ID: {$post->id}, Titre: {$post->title}, User ID: {$post->user_id}\n";
            }
            echo "\n";
            
            // Test 4: Query Builder avec WHERE
            echo "4. Posts de l'utilisateur 1 (avec WHERE):\n";
            $userPosts = Post::where('user_id', '=', 1)->get();
            foreach ($userPosts as $post) {
                echo "   - {$post->title}\n";
            }
            echo "\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur dans les requêtes de base: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }
    
    public function testRelations()
    {
        echo "=== Test des relations ===\n";
        
        try {
            // Test 1: Relation HasMany (User -> Posts)
            echo "1. Posts d'un utilisateur (HasMany):\n";
            $user = User::find(1);
            if ($user) {
                echo "   Utilisateur: {$user->name}\n";
                
                // Tester la relation
                $postsRelation = $user->posts();
                echo "   Type de relation: " . get_class($postsRelation) . "\n";
                
                $posts = $postsRelation->getResults();
                echo "   Type de résultat: " . gettype($posts) . "\n";
                
                if (is_array($posts) && !empty($posts)) {
                    echo "   Nombre de posts: " . count($posts) . "\n";
                    foreach ($posts as $post) {
                        echo "     - {$post->title}\n";
                    }
                } else {
                    echo "   Aucun post trouvé ou erreur\n";
                }
            }
            echo "\n";
            
            // Test 2: Relation BelongsTo (Post -> User)
            echo "2. Auteur d'un post (BelongsTo):\n";
            $post = Post::find(1);
            if ($post) {
                echo "   Post: {$post->title}\n";
                
                // Tester la relation
                $userRelation = $post->user();
                echo "   Type de relation: " . get_class($userRelation) . "\n";
                
                $author = $userRelation->getResults();
                echo "   Type de résultat: " . gettype($author) . "\n";
                
                if ($author) {
                    echo "   Auteur: {$author->name}\n";
                } else {
                    echo "   Aucun auteur trouvé\n";
                }
            }
            echo "\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur dans les relations: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }
    
    public function debugRelations()
    {
        echo "=== Debug des relations ===\n";
        
        try {
            // Test direct de la requête SQL
            echo "Test direct de la requête SQL:\n";
            $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE user_id = ?");
            $stmt->execute([1]);
            $directResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Résultats directs: " . count($directResults) . " posts\n";
            foreach ($directResults as $row) {
                echo "  - {$row['title']}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Erreur dans le debug: " . $e->getMessage() . "\n";
        }
    }
}

// Exécuter les tests
echo "=== Test Simple des Relations - Nexa Framework ===\n\n";

try {
    $tester = new SimpleRelationTest();
    
    $tester->testBasicQueries();
    $tester->testRelations();
    $tester->debugRelations();
    
    echo "\n✅ Tests terminés\n";
    
} catch (Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}