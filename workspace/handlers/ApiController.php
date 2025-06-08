<?php

namespace App\Http\Controllers\Api;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Auth\JWTManager;
use Nexa\Core\Logger;
use App\Models\User;

class ApiController extends Controller
{
    protected $jwtManager;

    public function __construct()
    {
        $this->jwtManager = new JWTManager();
    }

    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        try {
            $email = $request->input('email');
            $password = $request->input('password');

            if (!$email || !$password) {
                return $this->json([
                    'error' => 'Email et mot de passe requis'
                ], 400);
            }

            // Ici vous devriez vérifier les credentials en base
            // Pour l'exemple, on simule une authentification
            if ($email === 'admin@example.com' && $password === 'password') {
                $token = $this->jwtManager->generateToken(1, $email);

                return $this->json([
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => 1,
                        'email' => $email
                    ]
                ]);
            }

            return $this->json([
                'error' => 'Identifiants invalides'
            ], 401);

        } catch (\Exception $e) {
            Logger::error('Erreur lors de la connexion: ' . $e->getMessage());
            return $this->json([
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Inscription utilisateur
     */
    public function register(Request $request)
    {
        try {
            $email = $request->input('email');
            $password = $request->input('password');
            $name = $request->input('name');

            if (!$email || !$password || !$name) {
                return $this->json([
                    'error' => 'Nom, email et mot de passe requis'
                ], 400);
            }

            // Ici vous devriez créer l'utilisateur en base
            // Pour l'exemple, on simule la création
            $userId = rand(1000, 9999);

            $token = $this->jwtManager->generateToken($userId, $email);

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email
                ]
            ], 201);

        } catch (\Exception $e) {
            Logger::error('Erreur lors de l\'inscription: ' . $e->getMessage());
            return $this->json([
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Déconnexion utilisateur
     */
    public function logout(Request $request)
    {
        return $this->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    public function user(Request $request)
    {
        // Ici vous devriez récupérer l'utilisateur depuis le token JWT
        return $this->json([
            'user' => [
                'id' => 1,
                'name' => 'Utilisateur Test',
                'email' => 'admin@example.com'
            ]
        ]);
    }

    /**
     * Liste des utilisateurs
     */
    public function users(Request $request)
    {
        return $this->json([
            'users' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
                ['id' => 3, 'name' => 'Alice Johnson', 'email' => 'alice@example.com']
            ],
            'total' => 3,
            'page' => 1
        ]);
    }
    
    /**
     * Obtenir un utilisateur spécifique
     */
    public function getUser(Request $request, $id)
    {
        // Simulation de récupération d'un utilisateur
        $users = [
            1 => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => '2023-01-01'],
            2 => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'created_at' => '2023-01-02'],
            3 => ['id' => 3, 'name' => 'Alice Johnson', 'email' => 'alice@example.com', 'created_at' => '2023-01-03']
        ];
        
        if (!isset($users[$id])) {
            return $this->json([
                'error' => 'Utilisateur non trouvé'
            ], 404);
        }
        
        return $this->json([
            'user' => $users[$id]
        ]);
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');

        if (!$name || !$email) {
            return $this->json([
                'error' => 'Nom et email requis'
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Utilisateur créé',
            'user' => [
                'id' => rand(1000, 9999),
                'name' => $name,
                'email' => $email
            ]
        ], 201);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(Request $request, $id)
    {
        return $this->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour',
            'user' => [
                'id' => $id,
                'name' => $request->input('name', 'Nom mis à jour'),
                'email' => $request->input('email', 'email@example.com')
            ]
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(Request $request, $id)
    {
        return $this->json([
            'success' => true,
            'message' => 'Utilisateur supprimé'
        ]);
    }

    /**
     * Status de l'API
     */
    public function status(Request $request)
    {
        return $this->json([
            'status' => 'OK',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Health check
     */
    public function health(Request $request)
    {
        return $this->json([
            'health' => 'OK',
            'services' => [
                'database' => 'OK',
                'cache' => 'OK',
                'storage' => 'OK'
            ]
        ]);
    }

    /**
     * Middleware d'authentification JWT (exemple)
     */
    protected function requireAuth(Request $request)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return $this->json(['error' => 'Token manquant'], 401);
        }
        
        // Vérifier le token JWT ici
        // ...
        
        return null; // Pas d'erreur
    }
}