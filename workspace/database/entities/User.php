<?php

namespace Workspace\Database\Entities;

use Nexa\Database\Model;
// use Nexa\Attributes\AutoDiscover; // Removed auto-discovery
use Nexa\Attributes\Cache;
use Nexa\Attributes\Validate;
use Nexa\Attributes\Secure;
use Nexa\Attributes\Route;
use Nexa\Attributes\API;
use Nexa\Attributes\Relation;

/**
 * Entité User avec architecture moderne
 * Auto-découverte et optimisations avancées
 */
#[Cache('User', 3600), Validate, Secure]
#[Route(prefix: '/users'), API(version: 'v1')]
class User extends Model
{
    /**
     * Champs remplissables avec validation automatique
     */
    protected $fillable = [
        'name',
        'email', 
        'password',
        'avatar',
        'preferences',
        'last_login_at',
        'email_verified_at'
    ];
    
    /**
     * Casting automatique avec optimisations
     */
    protected $casts = [
        'preferences' => 'json',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Champs cachés pour la sécurité
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];
    
    /**
     * Règles de validation auto-découvertes
     */
    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed'
    ];
    
    // === AUTO-DISCOVERED RELATIONS ===
    
    /**
     * User has many projects
     * Auto-discovered through Project entity
     */
    #[Relation(
        type: 'hasMany',
        related: Project::class,
        foreignKey: 'user_id',
        cache: true,
        eager: false
    )]
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
    
    /**
     * User has many tasks
     * Auto-discovered through Task entity
     */
    #[Relation(
        type: 'hasMany',
        related: Task::class,
        foreignKey: 'user_id',
        cache: true
    )]
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    
    /**
     * Get pending tasks with intelligent caching
     */
    #[Cache(ttl: 300, key: 'user_pending_tasks_{id}')]
    public function pendingTasks()
    {
        return $this->tasks()->where('status', 'pending');
    }
    
    /**
     * Get user score with quantum calculation
     */
    #[Cache(ttl: 1800, key: 'user_score_{id}')]
    public function getScore(): int
    {
        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        $projectsCount = $this->projects()->count();
        
        // Quantum algorithm for score calculation
        return ($completedTasks * 10) + ($projectsCount * 50);
    }
    
    /**
     * Get performance data for analytics
     */
    #[Cache(ttl: 3600, key: 'user_performance_{id}')]
    public function getPerformanceData(): array
    {
        $months = [];
        $now = new \DateTime();
        
        for ($i = 11; $i >= 0; $i--) {
            $month = clone $now;
            $month->modify("-$i months");
            
            $monthStart = $month->format('Y-m-01');
            $monthEnd = $month->format('Y-m-t');
            
            $tasksCompleted = $this->tasks()
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$monthStart, $monthEnd])
                ->count();
            
            $months[] = [
                'month' => $month->format('M Y'),
                'tasks' => $tasksCompleted,
                'score' => $tasksCompleted * 10
            ];
        }
        
        return $months;
    }
    
    /**
     * Custom validation rules
     */
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255|regex:/^[a-zA-ZÀ-ÿ\s]+$/',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
            'avatar' => 'nullable|image|max:2048'
        ];
    }
    
    /**
     * Auto-sanitization
     */
    public function sanitize(): void
    {
        $this->name = trim(strip_tags($this->name));
        $this->email = strtolower(trim($this->email));
    }
    
    // === EVENTS ===
    
    protected static function boot()
    {
        parent::boot();
        
        // Auto-sanitize before saving
        static::saving(function ($user) {
            $user->sanitize();
        });
        
        // Clear cache after saving
        static::saved(function ($user) {
            cache()->tags(['users'])->flush();
        });
    }
}