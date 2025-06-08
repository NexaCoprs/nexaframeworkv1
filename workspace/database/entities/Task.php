<?php

namespace Workspace\Database\Entities;

use Nexa\Database\Model;
use Nexa\Attributes\Cache;
use Nexa\Attributes\Validate;
use Nexa\Attributes\Secure;
use Nexa\Attributes\Route;
use Nexa\Attributes\API;
use Nexa\Attributes\Relation;

/**
 * Task Entity
 */
#[Cache('Task', 3600), Validate, Secure]
#[Route(prefix: '/tasks'), API(version: 'v1')]
class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'user_id',
        'project_id',
        'due_date',
        'completed_at'
    ];
    
    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Task belongs to user
     */
    #[Relation(
        type: 'belongsTo',
        related: User::class,
        foreignKey: 'user_id',
        cache: true
    )]
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Task belongs to project
     */
    #[Relation(
        type: 'belongsTo',
        related: Project::class,
        foreignKey: 'project_id',
        cache: true
    )]
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    /**
     * Scope for pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    /**
     * Scope for completed tasks
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}