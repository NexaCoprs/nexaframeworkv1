<?php

namespace Nexa\Database;

class Column
{
    protected $name;
    protected $type;
    protected $options = [];

    public function __construct($name, $type, $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    public function nullable()
    {
        return $this->setOption('nullable', true);
    }

    public function unique()
    {
        return $this->setOption('unique', true);
    }

    public function isPrimary()
    {
        return isset($this->options['primary']) && $this->options['primary'] === true;
    }

    public function isNullable()
    {
        return isset($this->options['nullable']) && $this->options['nullable'] === true;
    }

    public function isUnique()
    {
        return isset($this->options['unique']) && $this->options['unique'] === true;
    }

    public function default($value)
    {
        return $this->setOption('default', $value);
    }

    public function unsigned()
    {
        return $this->setOption('unsigned', true);
    }

    public function autoIncrement()
    {
        return $this->setOption('auto_increment', true);
    }

    public function constrained($table = null, $column = 'id')
    {
        // Extract table name from column name if not provided
        if ($table === null) {
            $columnName = $this->name;
            if (str_ends_with($columnName, '_id')) {
                $table = substr($columnName, 0, -3) . 's'; // user_id -> users
            }
        }
        
        return $this->setOption('foreign_key', [
            'references' => $column,
            'on' => $table
        ]);
    }

    public function primary()
    {
        return $this->setOption('primary', true);
    }

    public function index()
    {
        return $this->setOption('index', true);
    }

    public function comment($comment)
    {
        return $this->setOption('comment', $comment);
    }

    public function after($column)
    {
        return $this->setOption('after', $column);
    }

    public function first()
    {
        return $this->setOption('first', true);
    }

    public function toSql()
    {
        $typeDefinition = $this->getTypeDefinition();
        $sql = "{$this->name} {$typeDefinition}";

        // If this is already INTEGER PRIMARY KEY AUTOINCREMENT, don't add more modifiers
        if (strpos($typeDefinition, 'INTEGER PRIMARY KEY AUTOINCREMENT') !== false) {
            return $sql;
        }

        // Add unsigned modifier
        if (isset($this->options['unsigned']) && $this->options['unsigned'] === true) {
            $sql .= " UNSIGNED";
        }

        // Handle nullability and defaults
        $sql .= $this->getNullabilityClause();
        $sql .= $this->getDefaultClause();

        // Add unique constraint
        if ($this->isUnique()) {
            $sql .= " UNIQUE";
        }

        // Add comment
        if (isset($this->options['comment'])) {
            $sql .= " COMMENT '{$this->options['comment']}'";
        }
        
        return $sql;
    }

    protected function getTypeDefinition()
    {
        $type = strtoupper($this->type);
        
        // Handle auto increment for SQLite - must be INTEGER PRIMARY KEY AUTOINCREMENT
        if (isset($this->options['auto_increment']) && $this->options['auto_increment'] === true) {
            if (isset($this->options['primary']) && $this->options['primary'] === true) {
                return "INTEGER PRIMARY KEY AUTOINCREMENT";
            }
        }
        
        // Handle types with length/precision
        if (isset($this->options['length'])) {
            return "{$type}({$this->options['length']})";
        }
        
        if (isset($this->options['precision']) && isset($this->options['scale'])) {
            return "{$type}({$this->options['precision']},{$this->options['scale']})";
        }
        
        if (isset($this->options['precision'])) {
            return "{$type}({$this->options['precision']})";
        }
        
        // Handle ENUM type
        if (strtolower($this->type) === 'enum' && isset($this->options['values'])) {
            $values = array_map(function($value) {
                return "'" . addslashes($value) . "'";
            }, $this->options['values']);
            return "ENUM(" . implode(',', $values) . ")";
        }
        
        return $type;
    }

    protected function getNullabilityClause()
    {
        // Auto-increment columns should be nullable to allow database to generate values
        if (isset($this->options['auto_increment']) && $this->options['auto_increment'] === true) {
            return "";
        }
        
        if ($this->isNullable()) {
            return " NULL";
        }
        
        return " NOT NULL";
    }

    protected function getDefaultClause()
    {
        // Handle timestamp columns specially
        if (strtolower($this->type) === 'timestamp') {
            if ($this->isNullable() && !isset($this->options['default'])) {
                return " DEFAULT NULL";
            } elseif (!isset($this->options['default'])) {
                $default = " DEFAULT CURRENT_TIMESTAMP";
                if ($this->name === 'updated_at') {
                    $default .= " ON UPDATE CURRENT_TIMESTAMP";
                }
                return $default;
            }
        }
        
        if (isset($this->options['default'])) {
            $default = $this->options['default'];
            
            // Handle special default values
            if (in_array(strtoupper($default), ['CURRENT_TIMESTAMP', 'NOW()', 'NULL'])) {
                return " DEFAULT {$default}";
            }
            
            // Handle boolean defaults
            if (is_bool($default)) {
                return " DEFAULT " . ($default ? '1' : '0');
            }
            
            // Handle numeric defaults
            if (is_numeric($default)) {
                return " DEFAULT {$default}";
            }
            
            // Handle string defaults
            return " DEFAULT '" . addslashes($default) . "'";
        }
        
        return "";
    }
}