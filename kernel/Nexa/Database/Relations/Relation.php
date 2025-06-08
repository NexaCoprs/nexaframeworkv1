<?php

namespace Nexa\Database\Relations;

use Nexa\Database\Model;

abstract class Relation
{
    protected $parent;
    protected $related;
    protected $foreignKey;
    protected $localKey;
    protected $query;
    protected static $connection;
    protected static $noConstraints = false;

    public function __construct(Model $parent, Model $related, $foreignKey, $localKey)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->query = new \Nexa\Database\QueryBuilder($this->related);
    }

    /**
     * Obtient les résultats de la relation
     *
     * @return mixed
     */
    abstract public function getResults();

    /**
     * Ajoute les contraintes de la relation à la requête
     *
     * @return void
     */
    abstract public function addConstraints();

    /**
     * Obtient la valeur de la clé locale
     *
     * @return mixed
     */
    protected function getParentKey()
    {
        return $this->parent->getAttribute($this->localKey);
    }

    /**
     * Obtient la clé étrangère
     *
     * @return string
     */
    public function getForeignKeyName()
    {
        return $this->foreignKey;
    }

    /**
     * Obtient la clé locale
     *
     * @return string
     */
    public function getLocalKeyName()
    {
        return $this->localKey;
    }

    /**
     * Handle dynamic method calls to the query builder
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $result = $this->query->$method(...$parameters);
        
        // If the result is a QueryBuilder, return this relation for chaining
        if ($result instanceof \Nexa\Database\QueryBuilder) {
            return $this;
        }
        
        return $result;
    }
}