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
 * Project Entity
 */
#[Cache('Project', 3600), Validate, Secure]
#[Route(prefix: '/projects'), API(version: 'v1')]
class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'user_id',
        'started_at',
        'completed_at'
    ];
    
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Project belongs to user
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
     * Project has many tasks
     */
    #[Relation(
        type: 'hasMany',
        related: Task::class,
        foreignKey: 'project_id',
        cache: true
    )]
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}