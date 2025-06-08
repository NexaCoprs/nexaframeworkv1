<?php

namespace Nexa\Database;

class ForeignKeyDefinition
{
    protected $foreignKey;
    protected $blueprint;
    
    public function __construct(&$foreignKey, Blueprint $blueprint)
    {
        $this->foreignKey = &$foreignKey;
        $this->blueprint = $blueprint;
    }
    
    /**
     * Set the referenced table and column(s)
     */
    public function references($columns)
    {
        $this->foreignKey['references'] = is_array($columns) ? $columns : [$columns];
        return $this;
    }
    
    /**
     * Set the referenced table
     */
    public function on($table)
    {
        $this->foreignKey['on'] = $table;
        return $this;
    }
    
    /**
     * Set the ON DELETE action
     */
    public function onDelete($action)
    {
        $this->foreignKey['onDelete'] = strtoupper($action);
        return $this;
    }
    
    /**
     * Set the ON UPDATE action
     */
    public function onUpdate($action)
    {
        $this->foreignKey['onUpdate'] = strtoupper($action);
        return $this;
    }
    
    /**
     * Set CASCADE on delete
     */
    public function cascadeOnDelete()
    {
        return $this->onDelete('CASCADE');
    }
    
    /**
     * Set CASCADE on update
     */
    public function cascadeOnUpdate()
    {
        return $this->onUpdate('CASCADE');
    }
    
    /**
     * Set RESTRICT on delete
     */
    public function restrictOnDelete()
    {
        return $this->onDelete('RESTRICT');
    }
    
    /**
     * Set RESTRICT on update
     */
    public function restrictOnUpdate()
    {
        return $this->onUpdate('RESTRICT');
    }
    
    /**
     * Set NULL on delete
     */
    public function nullOnDelete()
    {
        return $this->onDelete('SET NULL');
    }
    
    /**
     * Set NULL on update
     */
    public function nullOnUpdate()
    {
        return $this->onUpdate('SET NULL');
    }
    
    /**
     * Set NO ACTION on delete
     */
    public function noActionOnDelete()
    {
        return $this->onDelete('NO ACTION');
    }
    
    /**
     * Set NO ACTION on update
     */
    public function noActionOnUpdate()
    {
        return $this->onUpdate('NO ACTION');
    }
    
    /**
     * Set constraint name
     */
    public function name($name)
    {
        $this->foreignKey['name'] = $name;
        return $this;
    }
}