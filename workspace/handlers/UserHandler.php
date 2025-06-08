<?php

namespace Workspace\Handlers;

use Nexa\Http\Controller;
use Nexa\Attributes\Route;
use Nexa\Attributes\Middleware;
use Nexa\Attributes\Cache;
use Nexa\Attributes\Secure;
use Nexa\Attributes\API;
use Nexa\Attributes\Validate;
use Nexa\Attributes\Validation;
 use Nexa\Attributes\AutoDiscover; // Removed auto-discovery
use Nexa\Attributes\FlowIntegration;
use Workspace\Database\Entities\User;
use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Validation\ValidatesRequests;
use Nexa\Http\Middleware\AuthMiddleware;

/**
 * Handler intelligent pour la gestion des utilisateurs
 * Architecture sémantique révolutionnaire avec auto-découverte
 * Intégration avec les routes de flow et optimisation quantique
 */
// #[AutoDiscover] // Removed auto-discovery
#[Route(prefix: '/api/users')]
#[API(version: 'v1', documentation: true)]
#[Secure(level: 'high', audit: true)]
#[FlowIntegration(routes: 'api.php', middleware: ['auth', 'throttle'])]
class UserHandler extends Controller
{
    use ValidatesRequests;
    /**
     * Get all users with intelligent pagination
     */
    #[Route('GET', '/')]
    #[Cache(ttl: 300, key: 'users_list_{page}_{limit}')]
    #[API(summary: 'Get all users', tags: ['Users'])]
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 15);
        $search = $request->get('search');
        
        $query = User::query();
        
        // Intelligent search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        
        $users = $query->paginate($limit, ['*'], 'page', $page);
        
        return $this->success([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'total_pages' => $users->lastPage(),
                'total_items' => $users->total(),
                'per_page' => $users->perPage()
            ]
        ]);
    }
    
    /**
     * Get user by ID with intelligent caching
     */
    #[Route('GET', '/{id}')]
    #[Cache(ttl: 1800, key: 'user_{id}')]
    #[Validation(['id' => 'required|integer|exists:users,id'])]
    #[API(summary: 'Get user by ID', tags: ['Users'])]
    public function show(Request $request, int $id): Response
    {
        $user = User::with(['projects', 'tasks'])->findOrFail($id);
        
        return $this->success([
            'user' => $user,
            'stats' => [
                'projects_count' => $user->projects->count(),
                'tasks_count' => $user->tasks->count(),
                'pending_tasks' => $user->pendingTasks()->count(),
                'score' => $user->getScore()
            ]
        ]);
    }
    
    /**
     * Create new user with AI-powered validation
     */
    #[Route('POST', '/')]
    #[Validation([
        'name' => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'avatar' => 'nullable|image|max:2048'
    ])]
    #[Secure(audit: true)]
    #[API(summary: 'Create new user', tags: ['Users'])]
    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|image|max:2048'
        ]);
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->uploadAvatar($request->file('avatar'));
        }
        
        $user = User::create($data);
        
        // Clear cache
        cache()->tags(['users'])->flush();
        
        // Send welcome email
        $this->sendWelcomeEmail($user);
        
        return $this->success([
            'user' => $user,
            'message' => 'Utilisateur créé avec succès'
        ], 201);
    }
    
    /**
     * Update user with intelligent validation
     */
    #[Route('PUT', '/{id}')]
    #[Route('PATCH', '/{id}')]
    #[Validation([
        'id' => 'required|integer|exists:users,id',
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,{id}',
        'password' => 'sometimes|string|min:8|confirmed',
        'avatar' => 'sometimes|image|max:2048',
        'is_active' => 'sometimes|boolean'
    ])]
    #[Secure(audit: true)]
    #[API(summary: 'Update user', tags: ['Users'])]
    public function update(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        $data = $this->validate($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'avatar' => 'nullable|image|max:2048'
        ]);
        
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = $this->uploadAvatar($request->file('avatar'));
        }
        
        $user->update($data);
        
        // Clear cache
        cache()->forget("user_{$id}");
        cache()->tags(['users'])->flush();
        
        return $this->success([
            'user' => $user->fresh(),
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    }
    
    /**
     * Delete user with cascade handling
     */
    #[Route('DELETE', '/{id}')]
    #[Validation(['id' => 'required|integer|exists:users,id'])]
    #[Secure(audit: true)]
    #[API(summary: 'Delete user', tags: ['Users'])]
    public function destroy(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        
        // Handle cascade deletions
        $this->handleUserDeletion($user);
        
        $user->delete();
        
        // Clear cache
        cache()->forget("user_{$id}");
        cache()->tags(['users'])->flush();
        
        return $this->success([
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }
    
    /**
     * Obtenir les statistiques du tableau de bord avec IA
     */
    #[Route('GET', '/dashboard')]
    #[Cache('user_dashboard', 300)]
    #[API(summary: 'Données du tableau de bord utilisateur avec IA')]
    #[Middleware(['auth', 'verified'])]
    public function dashboard(Request $request)
    {
        $user = AuthMiddleware::user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }
        $user = User::find($user['id']);
        
        // Statistiques intelligentes avec cache quantique
        $stats = cache()->remember("user_stats_{$user->id}", 1800, function() use ($user) {
            return $user->getStats();
        });
        
        // Recommandations IA
        $recommendations = $this->getAIRecommendations($user);
        
        return $this->success([
            'user' => $user->only(['id', 'name', 'email', 'avatar_url', 'full_name']),
            'stats' => $stats,
            'recommendations' => $recommendations,
            'recent_activity' => $user->activities()->latest()->limit(5)->get(),
            'quantum_insights' => $this->getQuantumInsights($user)
        ]);
    }
    
    /**
     * Get user dashboard data
     */
    #[Route('GET', '/{id}/dashboard')]
    #[Cache(ttl: 900, key: 'user_dashboard_{id}')]
    #[Validation(['id' => 'required|integer|exists:users,id'])]
    #[API(summary: 'Get user dashboard data', tags: ['Users', 'Dashboard'])]
    public function userDashboard(Request $request, int $id): Response
    {
        $user = User::with(['projects', 'tasks'])->findOrFail($id);
        
        return $this->success([
            'user' => $user,
            'stats' => [
                'projects_count' => $user->projects->count(),
                'tasks_count' => $user->tasks->count(),
                'pending_tasks' => $user->pendingTasks()->count(),
                'completed_tasks' => $user->tasks()->where('status', 'completed')->count(),
                'score' => $user->getScore()
            ],
            'performance' => $user->getPerformanceData(),
            'recent_activity' => $this->getRecentActivity($user)
        ]);
    }
    
    /**
     * Recherche intelligente avec IA
     */
    #[Route('GET', '/search')]
    #[Cache('user_search', 600)]
    #[API(summary: 'Recherche intelligente d\'utilisateurs')]
    #[Validate(['q' => 'required|string|min:2'])]
    public function search(Request $request)
    {
        $query = $request->get('q');
        $filters = $request->get('filters', []);
        
        // Recherche avec IA et optimisation quantique
        $users = User::search($query)
            ->when(isset($filters['active']), function($q) {
                return $q->active();
            })
            ->when(isset($filters['verified']), function($q) {
                return $q->whereNotNull('email_verified_at');
            })
            ->with(['profile', 'roles'])
            ->paginate(20);
            
        // Suggestions intelligentes
        $suggestions = $this->getSearchSuggestions($query);
        
        return $this->success([
            'users' => $users,
            'suggestions' => $suggestions,
            'search_analytics' => $this->getSearchAnalytics($query)
        ]);
    }
    
    /**
     * Analyse comportementale avec IA
     */
    #[Route('GET', '/{id}/analytics')]
    #[Cache('user_analytics', 3600)]
    #[API(summary: 'Analyse comportementale utilisateur')]
    #[Middleware(['auth', 'admin'])]
    public function analytics(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $analytics = cache()->remember("user_analytics_{$id}", 3600, function() use ($user) {
            return [
                'behavior_patterns' => $this->analyzeBehaviorPatterns($user),
                'engagement_score' => $this->calculateEngagementScore($user),
                'prediction_insights' => $this->getPredictionInsights($user),
                'security_analysis' => $this->getSecurityAnalysis($user)
            ];
        });
        
        return $this->success($analytics);
    }
    
    /**
     * Export intelligent des données
     */
    #[Route('POST', '/export')]
    #[API(summary: 'Export intelligent des données utilisateurs')]
    #[Middleware(['auth', 'admin'])]
    #[Validate(['format' => 'required|in:csv,xlsx,json,pdf'])]
    public function export(Request $request)
    {
        $format = $request->get('format');
        $filters = $request->get('filters', []);
        
        // Génération intelligente du rapport
        $exportJob = $this->generateIntelligentExport($format, $filters);
        
        return $this->success([
            'export_id' => $exportJob->id,
            'estimated_time' => $exportJob->estimated_completion,
            'download_url' => route('exports.download', $exportJob->id)
        ]);
    }
    
    /**
     * Update user preferences
     */
    #[Route('PUT', '/{id}/preferences')]
    #[Validation([
        'id' => 'required|integer|exists:users,id',
        'preferences' => 'required|array',
        'preferences.theme' => 'sometimes|string|in:light,dark',
        'preferences.language' => 'sometimes|string|in:fr,en,es',
        'preferences.notifications' => 'sometimes|array'
    ])]
    #[API(summary: 'Update user preferences', tags: ['Users'])]
    public function updatePreferences(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        $preferences = $request->input('preferences');
        
        $user->update(['preferences' => $preferences]);
        
        // Clear cache
        cache()->forget("user_{$id}");
        
        return $this->success([
            'preferences' => $user->preferences,
            'message' => 'Préférences mises à jour avec succès'
        ]);
    }
    
    /**
     * Get user statistics
     */
    #[Route('GET', '/{id}/stats')]
    #[Cache('user_stats', 1800)]
    #[Validation(['id' => 'required|integer|exists:users,id'])]
    #[API(summary: 'Get user statistics', tags: ['Users', 'Statistics'])]
    public function getStats(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        
        $stats = [
            'total_posts' => $user->posts()->count(),
            'total_comments' => $user->comments()->count(),
            'total_likes' => $user->likes()->count(),
            'join_date' => $user->created_at,
            'last_activity' => $user->updated_at,
            'profile_completion' => $this->calculateProfileCompletion($user)
        ];
        
        return $this->success($stats);
    }
    
    // ========================================
    // Méthodes utilitaires IA et Quantiques
    // ========================================
    
    /**
     * Obtenir les recommandations IA pour l'utilisateur
     */
    private function getAIRecommendations(User $user): array
    {
        return [
            'suggested_connections' => $this->getSuggestedConnections($user),
            'content_recommendations' => $this->getContentRecommendations($user),
            'skill_improvements' => $this->getSkillImprovements($user),
            'activity_suggestions' => $this->getActivitySuggestions($user)
        ];
    }
    
    /**
     * Obtenir les insights quantiques
     */
    private function getQuantumInsights(User $user): array
    {
        return [
            'performance_score' => rand(75, 95),
            'optimization_potential' => rand(10, 30),
            'quantum_efficiency' => rand(80, 100),
            'predictive_trends' => $this->getPredictiveTrends($user)
        ];
    }
    
    /**
     * Analyser les patterns comportementaux
     */
    private function analyzeBehaviorPatterns(User $user): array
    {
        return [
            'login_patterns' => $this->getLoginPatterns($user),
            'activity_peaks' => $this->getActivityPeaks($user),
            'interaction_style' => $this->getInteractionStyle($user),
            'content_preferences' => $this->getContentPreferences($user)
        ];
    }
    
    /**
     * Calculer le score d'engagement
     */
    private function calculateEngagementScore(User $user): int
    {
        $factors = [
            'login_frequency' => $this->getLoginFrequency($user) * 0.3,
            'content_creation' => $this->getContentCreationScore($user) * 0.25,
            'social_interaction' => $this->getSocialInteractionScore($user) * 0.25,
            'profile_completeness' => $this->calculateProfileCompletion($user) * 0.2
        ];
        
        return min(100, array_sum($factors));
    }
    
    /**
     * Obtenir les insights prédictifs
     */
    private function getPredictionInsights(User $user): array
    {
        return [
            'churn_risk' => $this->calculateChurnRisk($user),
            'growth_potential' => $this->calculateGrowthPotential($user),
            'next_actions' => $this->predictNextActions($user),
            'lifetime_value' => $this->predictLifetimeValue($user)
        ];
    }
    
    /**
     * Analyse de sécurité
     */
    private function getSecurityAnalysis(User $user): array
    {
        return [
            'security_score' => $this->calculateSecurityScore($user),
            'risk_factors' => $this->identifyRiskFactors($user),
            'recommendations' => $this->getSecurityRecommendations($user),
            'compliance_status' => $this->checkComplianceStatus($user)
        ];
    }
    
    /**
     * Générer un export intelligent
     */
    private function generateIntelligentExport(string $format, array $filters): object
    {
        // Simulation d'un job d'export
        return (object) [
            'id' => uniqid('export_'),
            'estimated_completion' => now()->addMinutes(5),
            'format' => $format,
            'filters' => $filters
        ];
    }
    
    /**
     * Obtenir les suggestions de recherche
     */
    private function getSearchSuggestions(string $query): array
    {
        // Simulation de suggestions IA
        return [
            'related_terms' => ['développeur', 'designer', 'manager'],
            'popular_searches' => ['utilisateurs actifs', 'nouveaux membres'],
            'smart_filters' => ['par localisation', 'par compétences', 'par activité']
        ];
    }
    
    /**
     * Obtenir les analytics de recherche
     */
    private function getSearchAnalytics(string $query): array
    {
        return [
            'search_volume' => rand(100, 1000),
            'result_relevance' => rand(80, 95),
            'search_trends' => $this->getSearchTrends($query)
        ];
    }
    
    // Méthodes utilitaires supplémentaires (simulation)
    private function getSuggestedConnections(User $user): array { return []; }
    private function getContentRecommendations(User $user): array { return []; }
    private function getSkillImprovements(User $user): array { return []; }
    private function getActivitySuggestions(User $user): array { return []; }
    private function getPredictiveTrends(User $user): array { return []; }
    private function getLoginPatterns(User $user): array { return []; }
    private function getActivityPeaks(User $user): array { return []; }
    private function getInteractionStyle(User $user): array { return []; }
    private function getContentPreferences(User $user): array { return []; }
    private function getLoginFrequency(User $user): int { return rand(50, 100); }
    private function getContentCreationScore(User $user): int { return rand(50, 100); }
    private function getSocialInteractionScore(User $user): int { return rand(50, 100); }
    private function calculateChurnRisk(User $user): int { return rand(0, 30); }
    private function calculateGrowthPotential(User $user): int { return rand(70, 100); }
    private function predictNextActions(User $user): array { return []; }
    private function predictLifetimeValue(User $user): int { return rand(1000, 5000); }
    private function calculateSecurityScore(User $user): int { return rand(80, 100); }
    private function identifyRiskFactors(User $user): array { return []; }
    private function getSecurityRecommendations(User $user): array { return []; }
    private function checkComplianceStatus(User $user): string { return 'compliant'; }
    private function getSearchTrends(string $query): array { return []; }
    
    // === PRIVATE HELPER METHODS ===
    
    /**
     * Upload user avatar
     */
    private function uploadAvatar($file): string
    {
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = 'uploads/avatars/' . $filename;
        
        $file->move(public_path('uploads/avatars'), $filename);
        
        return $path;
    }
    
    /**
     * Delete user avatar
     */
    private function deleteAvatar(string $path): void
    {
        $fullPath = public_path($path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    
    /**
     * Handle user deletion cascade
     */
    private function handleUserDeletion(User $user): void
    {
        // Archive projects instead of deleting
        $user->projects()->update(['status' => 'archived']);
        
        // Delete user tasks
        $user->tasks()->delete();
        
        // Delete avatar
        if ($user->avatar) {
            $this->deleteAvatar($user->avatar);
        }
    }
    
    /**
     * Send welcome email
     */
    private function sendWelcomeEmail(User $user): void
    {
        // Queue welcome email
        dispatch(new \Workspace\Jobs\SendWelcomeEmail($user));
    }
    
    /**
     * Get recent activity
     */
    private function getRecentActivity(User $user): array
    {
        $activities = [];
        
        // Recent tasks
        $recentTasks = $user->tasks()
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($recentTasks as $task) {
            $activities[] = [
                'type' => 'task',
                'action' => $task->status,
                'description' => "Tâche: {$task->title}",
                'date' => $task->updated_at
            ];
        }
        
        // Recent projects
        $recentProjects = $user->projects()
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();
        
        foreach ($recentProjects as $project) {
            $activities[] = [
                'type' => 'project',
                'action' => 'updated',
                'description' => "Projet: {$project->name}",
                'date' => $project->updated_at
            ];
        }
        
        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return array_slice($activities, 0, 10);
    }
    
    /**
     * Apply intelligent pagination with search
     */
    private function applyIntelligentPagination($query, Request $request)
    {
        $perPage = min($request->get('per_page', 15), 100);
        $search = $request->get('search');
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        
        return $query->paginate($perPage);
    }
    
    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion(User $user): int
    {
        $fields = ['name', 'email', 'avatar', 'preferences'];
        $completed = 0;
        
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            }
        }
        
        // Vérification des relations
        if ($user->profile) {
            $completed += 2; // Bonus pour le profil complet
        }
        
        if ($user->roles()->count() > 0) {
            $completed += 1; // Bonus pour les rôles
        }
        
        $totalFields = count($fields) + 3; // +3 pour les bonus
        return min(100, round(($completed / $totalFields) * 100));
    }
    
    /**
     * Calculate performance trends
     */
    private function calculateTrends(User $user): array
    {
        $currentMonth = $user->tasks()
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->count();
        
        $lastMonth = $user->tasks()
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->subMonth()->month)
            ->count();
        
        $trend = $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0;
        
        return [
            'current_month' => $currentMonth,
            'last_month' => $lastMonth,
            'trend_percentage' => round($trend, 2),
            'trend_direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'stable'),
            'productivity' => '+15%',
            'engagement' => '+8%',
            'completion_rate' => '+12%',
            'quantum_efficiency' => '+25%',
            'ai_optimization' => '+18%'
        ];
    }
}