<?php

namespace Nexa\Database;

use PDO;
use Nexa\Database\Relations\HasOneRelation;
use Nexa\Database\Relations\HasManyRelation;
use Nexa\Database\Relations\BelongsToRelation;
use Nexa\Database\Relations\BelongsToManyRelation;

abstract class Model
{
    protected static $connection;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $attributes = [];
    protected $relations = [];
    protected $hidden = [];
    protected $casts = [];

    public static function setConnection(PDO $connection)
    {
        static::$connection = $connection;
    }
    
    public function getConnection()
    {
        return static::$connection;
    }

    public function __construct(array $attributes = [])
    {
        if (!$this->table) {
            $this->table = $this->getTableName();
        }
        $this->fill($attributes);
    }
    
    public function getTableName()
    {
        $className = $this->getClassBasename(static::class);
        return strtolower($className) . 's';
    }
    
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the primary key name
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }


    
    protected function getClassBasename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function save()
    {
        $this->updateTimestamps();
        
        if (isset($this->attributes[$this->primaryKey]) && !empty($this->attributes[$this->primaryKey])) {
            return $this->performUpdate();
        }
        return $this->performInsert();
    }

    protected function insert()
    {
        $columns = implode(', ', array_keys($this->attributes));
        $placeholders = implode(', ', array_fill(0, count($this->attributes), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = static::$connection->prepare($sql);
        $stmt->execute(array_values($this->attributes));

        $this->attributes[$this->primaryKey] = static::$connection->lastInsertId();
        return true;
    }



    public static function find($id)
    {
        $model = new static;
        $sql = "SELECT * FROM {$model->table} WHERE {$model->primaryKey} = ? LIMIT 1";
        $stmt = static::$connection->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = new static;
            $instance->fill($row);
            // Manually set the primary key since it's guarded
            $instance->setAttribute($instance->primaryKey, $row[$instance->primaryKey]);
            return $instance;
        }
        return null;
    }

    public static function all()
    {
        $model = new static;
        $sql = "SELECT * FROM {$model->table}";
        $stmt = static::$connection->prepare($sql);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = new static;
            $instance->fill($row);
            // Manually set the primary key since it's guarded
            $instance->setAttribute($instance->primaryKey, $row[$instance->primaryKey]);
            $results[] = $instance;
        }
        return $results;
    }



    // Relations
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return new HasOneRelation($this, new $related, $foreignKey, $localKey);
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return new HasManyRelation($this, new $related, $foreignKey, $localKey);
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getRelatedForeignKey($related);
        $ownerKey = $ownerKey ?: (new $related)->primaryKey;
        
        return new BelongsToRelation($this, new $related, $foreignKey, $ownerKey);
    }

    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null)
    {
        $table = $table ?: $this->getPivotTableName($related);
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?: (new $related)->getForeignKey();
        
        return new BelongsToManyRelation($this, new $related, $table, $foreignPivotKey, $relatedPivotKey);
    }

    protected function getForeignKey()
    {
        return strtolower($this->getClassBasename(static::class)) . '_id';
    }

    protected function getRelatedForeignKey($related)
    {
        return strtolower($this->getClassBasename($related)) . '_id';
    }

    protected function getPivotTableName($related)
    {
        $models = [
            strtolower($this->getClassBasename(static::class)),
            strtolower($this->getClassBasename($related))
        ];
        sort($models);
        return implode('_', $models);
    }

    // Query Builder amélioré
    public static function where($column, $operator = null, $value = null)
    {
        $queryBuilder = new QueryBuilder(new static);
        
        // Only pass the arguments that were actually provided
        if (func_num_args() === 2) {
            return $queryBuilder->where($column, $operator);
        } else {
            return $queryBuilder->where($column, $operator, $value);
        }
    }

    public static function whereIn($column, array $values)
    {
        return (new QueryBuilder(new static))->whereIn($column, $values);
    }

    public static function orderBy($column, $direction = 'asc')
    {
        return (new QueryBuilder(new static))->orderBy($column, $direction);
    }

    public static function limit($limit)
    {
        return (new QueryBuilder(new static))->limit($limit);
    }

    public static function offset($offset)
    {
        return (new QueryBuilder(new static))->offset($offset);
    }

    // Add missing static methods that delegate to QueryBuilder
    public static function max($column)
    {
        return (new QueryBuilder(new static))->max($column);
    }

    public static function whereDate($column, $operator, $value = null)
    {
        return (new QueryBuilder(new static))->whereDate($column, $operator, $value);
    }

    public static function whereLike($column, $value)
    {
        return (new QueryBuilder(new static))->whereLike($column, $value);
    }

    public static function whereBetween($column, array $values)
    {
        return (new QueryBuilder(new static))->whereBetween($column, $values);
    }

    public static function insertData(array $data)
    {
        $model = new static;
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$model->table} ($columns) VALUES ($placeholders)";
        $stmt = static::$connection->prepare($sql);
        return $stmt->execute(array_values($data));
    }

    public static function setDefaultConnection(PDO $connection)
    {
        static::$connection = $connection;
    }

    // Add missing update method for mass updates
    public static function updateWhere(array $data)
    {
        return (new QueryBuilder(new static))->update($data);
    }

    public function toArray()
    {
        $array = $this->attributes;
        
        // Ajouter les relations chargées
        foreach ($this->relations as $key => $relation) {
            if (is_array($relation)) {
                $array[$key] = array_map(function($model) {
                    return $model->toArray();
                }, $relation);
            } else {
                $array[$key] = $relation ? $relation->toArray() : null;
            }
        }
        
        // Supprimer les champs cachés
        foreach ($this->hidden as $hidden) {
            unset($array[$hidden]);
        }
        
        return $array;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }
        
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        
        return null;
    }

    protected function castAttribute($key, $value)
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }
        
        switch ($this->casts[$key]) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
            case 'json':
                return json_decode($value, true);
            case 'date':
                return new \DateTime($value);
            default:
                return $value;
        }
    }

    /**
     * Set an attribute value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    // Advanced ORM Features

    /**
     * Create a new model instance
     */
    public static function create(array $attributes)
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Update or create a model
     */
    public static function updateOrCreate(array $attributes, array $values = [])
    {
        $model = static::where($attributes)->first();
        
        if ($model) {
            $model->fill($values);
            $model->save();
        } else {
            $model = static::create(array_merge($attributes, $values));
        }
        
        return $model;
    }

    /**
     * Find or create a model
     */
    public static function firstOrCreate(array $attributes, array $values = [])
    {
        $model = static::where($attributes)->first();
        
        if (!$model) {
            $model = static::create(array_merge($attributes, $values));
        }
        
        return $model;
    }

    /**
     * Find or fail
     */
    public static function findOrFail($id)
    {
        $model = static::find($id);
        
        if (!$model) {
            throw new \Exception("Model not found with ID: {$id}");
        }
        
        return $model;
    }

    /**
     * Get the first model or fail
     */
    public static function firstOrFail()
    {
        $model = static::first();
        
        if (!$model) {
            throw new \Exception("No model found");
        }
        
        return $model;
    }

    /**
     * Get the first model
     */
    public static function first()
    {
        return static::limit(1)->get()[0] ?? null;
    }

    /**
     * Get models with query builder
     */
    public static function get()
    {
        return (new QueryBuilder(new static))->get();
    }

    /**
     * Count models
     */
    public static function count()
    {
        return (new QueryBuilder(new static))->count();
    }

    /**
     * Update the model with given data
     */
    public function update(array $data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
        return $this->save();
    }

    /**
     * Delete the model
     */
    public function delete()
    {
        return $this->performDelete();
    }

    /**
     * Destroy models by IDs
     */
    public static function destroy($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $model = new static;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$model->table} WHERE {$model->primaryKey} IN ({$placeholders})";
        $stmt = static::$connection->prepare($sql);
        return $stmt->execute($ids);
    }

    /**
     * Soft deletes support
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $softDelete = false;

    public function softDelete()
    {
        if (!$this->softDelete) {
            return $this->delete();
        }
        
        $this->setAttribute('deleted_at', date('Y-m-d H:i:s'));
        return $this->save();
    }

    public function restore()
    {
        if (!$this->softDelete) {
            return false;
        }
        
        $this->setAttribute('deleted_at', null);
        return $this->save();
    }

    public static function withTrashed()
    {
        return (new QueryBuilder(new static))->withTrashed();
    }

    public static function onlyTrashed()
    {
        return (new QueryBuilder(new static))->onlyTrashed();
    }

    /**
     * Timestamps support
     */
    protected $timestamps = true;

    protected function updateTimestamps()
    {
        if (!$this->timestamps) {
            return;
        }
        
        $now = date('Y-m-d H:i:s');
        
        if (!isset($this->attributes[$this->primaryKey]) || empty($this->attributes[$this->primaryKey])) {
            // Creating
            $this->setAttribute('created_at', $now);
        }
        
        $this->setAttribute('updated_at', $now);
    }



    /**
     * Scopes support
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Accessors and Mutators
     */
    public function __get($name)
    {
        // Check for accessor
        $accessor = 'get' . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor();
        }
        
        return $this->getAttribute($name);
    }

    public function __set($name, $value)
    {
        // Check for mutator
        $mutator = 'set' . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->$mutator($value);
            return;
        }
        
        $this->setAttribute($name, $value);
    }

    /**
     * Mass assignment protection
     */
    protected $guarded = ['id'];

    public function isFillable($key)
    {
        if (in_array($key, $this->fillable)) {
            return true;
        }
        
        if (in_array($key, $this->guarded)) {
            return false;
        }
        
        return empty($this->fillable);
    }



    /**
     * Validation support
     */
    protected $rules = [];
    protected $messages = [];

    public function validate(array $data = null)
    {
        $data = $data ?: $this->attributes;
        $errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $data[$field] ?? null;
            $fieldRules = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($fieldRules as $rule) {
                if (!$this->validateRule($field, $value, $rule)) {
                    $errors[$field][] = $this->getErrorMessage($field, $rule);
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }

    protected function validateRule($field, $value, $rule)
    {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'numeric':
                return is_numeric($value);
            default:
                if (strpos($rule, 'min:') === 0) {
                    $min = (int) substr($rule, 4);
                    return strlen($value) >= $min;
                }
                if (strpos($rule, 'max:') === 0) {
                    $max = (int) substr($rule, 4);
                    return strlen($value) <= $max;
                }
                return true;
        }
    }

    protected function getErrorMessage($field, $rule)
    {
        $key = "{$field}.{$rule}";
        return $this->messages[$key] ?? "The {$field} field is invalid for rule {$rule}.";
    }

    /**
     * Events support
     */
    protected static $events = [];

    public static function creating($callback)
    {
        static::$events['creating'][] = $callback;
    }

    public static function created($callback)
    {
        static::$events['created'][] = $callback;
    }

    public static function updating($callback)
    {
        static::$events['updating'][] = $callback;
    }

    public static function updated($callback)
    {
        static::$events['updated'][] = $callback;
    }

    public static function deleting($callback)
    {
        static::$events['deleting'][] = $callback;
    }

    public static function deleted($callback)
    {
        static::$events['deleted'][] = $callback;
    }

    protected function fireEvent($event)
    {
        if (isset(static::$events[$event])) {
            foreach (static::$events[$event] as $callback) {
                call_user_func($callback, $this);
            }
        }
    }

    /**
     * Enhanced insert with events
     */
    protected function performInsert()
    {
        $this->fireEvent('creating');
        
        // Exclude primary key if it's null or empty (for auto-increment)
        $insertAttributes = $this->attributes;
        if (empty($insertAttributes[$this->primaryKey])) {
            unset($insertAttributes[$this->primaryKey]);
        }
        
        $columns = implode(', ', array_keys($insertAttributes));
        $placeholders = implode(', ', array_fill(0, count($insertAttributes), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        
        $stmt = static::$connection->prepare($sql);
        $result = $stmt->execute(array_values($insertAttributes));
        
        if ($result) {
            $lastId = static::$connection->lastInsertId();
            $this->attributes[$this->primaryKey] = $lastId;
            $this->fireEvent('created');
        }
        
        return $result;
    }

    /**
     * Enhanced update with events
     */
    protected function performUpdate()
    {
        $this->fireEvent('updating');
        
        $set = [];
        foreach ($this->attributes as $key => $value) {
            if ($key !== $this->primaryKey) {
                $set[] = "$key = ?";
            }
        }
        $set = implode(', ', $set);

        $sql = "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = ?";
        $values = array_values(array_diff_key($this->attributes, [$this->primaryKey => null]));
        $values[] = $this->attributes[$this->primaryKey];

        $stmt = static::$connection->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            $this->fireEvent('updated');
        }
        
        return $result;
    }

    /**
     * Enhanced delete with events
     */
    public function performDelete()
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }
        
        $this->fireEvent('deleting');
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = static::$connection->prepare($sql);
        $result = $stmt->execute([$this->attributes[$this->primaryKey]]);
        
        if ($result) {
            $this->fireEvent('deleted');
        }
        
        return $result;
    }

    /**
     * Handle static method calls for scopes and query builder methods
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;
        
        // Check if it's a scope method
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($instance, $scopeMethod)) {
            $query = new QueryBuilder($instance);
            return $instance->$scopeMethod($query, ...$parameters);
        }
        
        // Otherwise, delegate to QueryBuilder
        return (new QueryBuilder($instance))->$method(...$parameters);
    }

    /**
     * Handle instance method calls for scopes and query builder methods
     */
    public function __call($method, $parameters)
    {
        // Check if it's a scope method
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($this, $scopeMethod)) {
            $query = new QueryBuilder($this);
            return $this->$scopeMethod($query, ...$parameters);
        }
        
        // Otherwise, delegate to QueryBuilder
        return (new QueryBuilder($this))->$method(...$parameters);
    }
}