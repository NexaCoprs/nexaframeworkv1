<?php

namespace Nexa\Database;

class Blueprint
{
    protected $table;
    protected $columns = [];
    protected $commands = [];
    protected $indexes = [];
    protected $foreignKeys = [];
    protected $action = 'create'; // 'create' or 'alter'

    public function __construct($table, $action = 'create')
    {
        $this->table = $table;
        $this->action = $action;
    }

    // Primary key methods
    public function id($column = 'id')
    {
        return $this->bigIncrements($column);
    }

    public function increments($column)
    {
        return $this->integer($column, ['unsigned' => true, 'auto_increment' => true, 'primary' => true]);
    }

    public function bigIncrements($column)
    {
        return $this->bigInteger($column, ['unsigned' => true, 'auto_increment' => true, 'primary' => true]);
    }

    // String types
    public function string($column, $length = 255)
    {
        return $this->addColumn($column, 'varchar', ['length' => $length]);
    }

    public function char($column, $length = 255)
    {
        return $this->addColumn($column, 'char', ['length' => $length]);
    }

    public function text($column)
    {
        return $this->addColumn($column, 'text');
    }

    public function mediumText($column)
    {
        return $this->addColumn($column, 'mediumtext');
    }

    public function longText($column)
    {
        return $this->addColumn($column, 'longtext');
    }

    public function json($column)
    {
        return $this->addColumn($column, 'json');
    }

    public function uuid($column)
    {
        return $this->char($column, 36);
    }

    // Integer types
    public function integer($column, $options = [])
    {
        return $this->addColumn($column, 'int', $options);
    }

    public function bigInteger($column, $options = [])
    {
        return $this->addColumn($column, 'bigint', $options);
    }

    public function smallInteger($column, $options = [])
    {
        return $this->addColumn($column, 'smallint', $options);
    }

    public function tinyInteger($column, $options = [])
    {
        return $this->addColumn($column, 'tinyint', $options);
    }

    public function unsignedInteger($column)
    {
        return $this->integer($column, ['unsigned' => true]);
    }

    public function unsignedBigInteger($column)
    {
        return $this->bigInteger($column, ['unsigned' => true]);
    }

    // Decimal types
    public function decimal($column, $precision = 8, $scale = 2)
    {
        return $this->addColumn($column, 'decimal', ['precision' => $precision, 'scale' => $scale]);
    }

    public function float($column, $precision = 8, $scale = 2)
    {
        return $this->addColumn($column, 'float', ['precision' => $precision, 'scale' => $scale]);
    }

    public function double($column, $precision = 8, $scale = 2)
    {
        return $this->addColumn($column, 'double', ['precision' => $precision, 'scale' => $scale]);
    }

    // Date and time types
    public function date($column)
    {
        return $this->addColumn($column, 'date');
    }

    public function dateTime($column)
    {
        return $this->addColumn($column, 'datetime');
    }

    public function time($column)
    {
        return $this->addColumn($column, 'time');
    }

    public function timestamp($column)
    {
        return $this->addColumn($column, 'timestamp');
    }

    public function timestamps()
    {
        $this->timestamp('created_at')->nullable()->default(null);
        $this->timestamp('updated_at')->nullable()->default(null);
        return $this;
    }

    public function softDeletes($column = 'deleted_at')
    {
        $this->timestamp($column)->nullable();
        return $this;
    }

    // Boolean type
    public function boolean($column)
    {
        return $this->addColumn($column, 'boolean');
    }

    // Binary types
    public function binary($column)
    {
        return $this->addColumn($column, 'blob');
    }

    // Enum type
    public function enum($column, array $values)
    {
        return $this->addColumn($column, 'enum', ['values' => $values]);
    }

    // Foreign key helpers
    public function foreignId($column)
    {
        return $this->unsignedBigInteger($column);
    }

    public function foreignIdFor($model, $column = null)
    {
        $column = $column ?: strtolower($this->getClassBasename($model)) . '_id';
        return $this->foreignId($column);
    }

    /**
     * Get the class basename
     */
    private function getClassBasename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    // Column modifiers
    public function nullable()
    {
        return $this->addOption('nullable', true);
    }

    public function default($value)
    {
        return $this->addOption('default', $value);
    }

    public function unique($columns = null)
    {
        if ($columns) {
            $columns = is_array($columns) ? $columns : [$columns];
            $this->indexes[] = ['type' => 'unique', 'columns' => $columns];
        } else {
            return $this->addOption('unique', true);
        }
        return $this;
    }

    public function index($columns = null, $name = null)
    {
        if ($columns) {
            $columns = is_array($columns) ? $columns : [$columns];
            $this->indexes[] = ['type' => 'index', 'columns' => $columns, 'name' => $name];
        } else {
            return $this->addOption('index', true);
        }
        return $this;
    }

    public function primary($columns = null)
    {
        if ($columns) {
            $columns = is_array($columns) ? $columns : [$columns];
            $this->indexes[] = ['type' => 'primary', 'columns' => $columns];
        } else {
            return $this->addOption('primary', true);
        }
        return $this;
    }

    public function unsigned()
    {
        return $this->addOption('unsigned', true);
    }

    public function autoIncrement()
    {
        return $this->addOption('auto_increment', true);
    }

    public function comment($comment)
    {
        return $this->addOption('comment', $comment);
    }

    public function after($column)
    {
        return $this->addOption('after', $column);
    }

    public function first()
    {
        return $this->addOption('first', true);
    }

    public function rememberToken()
    {
        return $this->string('remember_token', 100)->nullable();
    }

    public function compressed()
    {
        return $this->addOption('compressed', true);
    }

    public function check($expression, $name = null)
    {
        $this->commands[] = [
            'type' => 'check',
            'expression' => $expression,
            'name' => $name
        ];
        return $this;
    }

    // Foreign key constraints
    public function foreign($columns, $name = null)
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $foreignKey = [
            'columns' => $columns,
            'name' => $name,
            'references' => null,
            'on' => null,
            'onDelete' => null,
            'onUpdate' => null
        ];
        $this->foreignKeys[] = $foreignKey;
        return new ForeignKeyDefinition($foreignKey, $this);
    }

    public function addColumn($name, $type, $options = [])
    {
        $column = new Column($name, $type, $options);
        $this->columns[] = $column;
        return $column;
    }

    protected function addOption($option, $value)
    {
        if (!empty($this->columns)) {
            $lastColumn = end($this->columns);
            $lastColumn->setOption($option, $value);
        }
        return $this;
    }

    /**
     * Drop columns from table
     */
    public function dropColumn($column)
    {
        $this->commands[] = [
            'type' => 'drop',
            'columns' => is_array($column) ? $column : [$column]
        ];
        return $this;
    }

    /**
     * Rename a column
     */
    public function renameColumn($from, $to)
    {
        $this->commands[] = [
            'type' => 'rename',
            'from' => $from,
            'to' => $to
        ];
        return $this;
    }

    /**
     * Modify a column
     */
    public function modifyColumn($column, $type, $options = [])
    {
        $this->commands[] = [
            'type' => 'modify',
            'column' => new Column($column, $type, $options)
        ];
        return $this;
    }

    /**
     * Get table name
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get indexes
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * Get foreign keys
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * Generate CREATE TABLE SQL
     */
    public function toSql()
    {
        $columns = [];
        $primaryKeys = [];

        foreach ($this->columns as $column) {
            $columnSql = $column->toSql();
            $columns[] = $columnSql;
            
            // Only add to primaryKeys if it's not already INTEGER PRIMARY KEY AUTOINCREMENT
            if ($column->isPrimary() && strpos($columnSql, 'INTEGER PRIMARY KEY AUTOINCREMENT') === false) {
                $primaryKeys[] = $column->getName();
            }
        }

        $sql = "CREATE TABLE {$this->table} (".implode(', ', $columns);

        // Add primary key from indexes if not already set
        foreach ($this->indexes as $index) {
            if ($index['type'] === 'primary') {
                $primaryKeys = array_merge($primaryKeys, $index['columns']);
            }
        }

        if (!empty($primaryKeys)) {
            $primaryKeys = array_unique($primaryKeys);
            $sql .= ", PRIMARY KEY (".implode(', ', $primaryKeys).")";
        }

        // Note: All indexes (including unique) will be created separately for SQLite compatibility

        $sql .= ")";

        return $sql;
    }

    /**
     * Generate ALTER TABLE SQL
     */
    public function toAlterSql()
    {
        $sql = [];

        foreach ($this->commands as $command) {
            switch ($command['type']) {
                case 'add':
                    foreach ($command['columns'] as $column) {
                        $sql[] = "ALTER TABLE {$this->table} ADD COLUMN ".$column->toSql();
                    }
                    break;
                case 'drop':
                    foreach ($command['columns'] as $column) {
                        $sql[] = "ALTER TABLE {$this->table} DROP COLUMN {$column}";
                    }
                    break;
                case 'modify':
                    $sql[] = "ALTER TABLE {$this->table} MODIFY COLUMN " . $command['column']->toSql();
                    break;
                case 'rename':
                    $sql[] = "ALTER TABLE {$this->table} CHANGE {$command['from']} {$command['to']}";
                    break;
            }
        }

        // Add new columns from this blueprint
        foreach ($this->columns as $column) {
            $sql[] = "ALTER TABLE {$this->table} ADD COLUMN " . $column->toSql();
        }

        return implode('; ', $sql);
    }
}