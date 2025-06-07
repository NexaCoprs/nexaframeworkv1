<?php

namespace Nexa\Database;

use PDO;
use Exception;

class Schema
{
    protected $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create a new table with callback
     */
    public function create($tableName, $callback = null)
    {
        if ($tableName instanceof Blueprint) {
            // Direct blueprint usage
            $blueprint = $tableName;
        } else {
            // Table name with callback
            $blueprint = new Blueprint($tableName);
            if ($callback) {
                $callback($blueprint);
            }
        }
        
        $sql = $blueprint->toSql();
        $this->connection->exec($sql);
        
        // Create indexes
        $this->createIndexes($blueprint);
        
        // Create foreign keys
        $this->createForeignKeys($blueprint);
    }

    /**
     * Alter an existing table
     */
    public function alter(Blueprint $blueprint)
    {
        $sql = $blueprint->toAlterSql();
        if (!empty($sql)) {
            $this->connection->exec($sql);
        }
        
        // Create new indexes
        $this->createIndexes($blueprint);
        
        // Create new foreign keys
        $this->createForeignKeys($blueprint);
    }

    /**
     * Drop a table
     */
    public function drop($table)
    {
        $this->connection->exec("DROP TABLE {$table}");
    }

    /**
     * Drop a table if it exists
     */
    public function dropIfExists($table)
    {
        $this->connection->exec("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Rename a table
     */
    public function rename($from, $to)
    {
        $this->connection->exec("RENAME TABLE {$from} TO {$to}");
    }

    /**
     * Truncate a table
     */
    public function truncate($table)
    {
        $this->connection->exec("TRUNCATE TABLE {$table}");
    }

    /**
     * Modify an existing table
     */
    public function table($table, \Closure $callback)
    {
        $blueprint = new Blueprint($table, 'alter');
        $callback($blueprint);
        $this->alter($blueprint);
    }

    /**
     * Check if table exists
     */
    public function hasTable($table)
    {
        $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if column exists
     */
    public function hasColumn($table, $column)
    {
        $stmt = $this->connection->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if index exists
     */
    public function hasIndex($table, $index)
    {
        $stmt = $this->connection->prepare("SHOW INDEX FROM {$table} WHERE Key_name = ?");
        $stmt->execute([$index]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get table columns
     */
    public function getColumns($table)
    {
        $stmt = $this->connection->query("SHOW COLUMNS FROM {$table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get table indexes
     */
    public function getIndexes($table)
    {
        $stmt = $this->connection->query("SHOW INDEX FROM {$table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all tables
     */
    public function getTables()
    {
        $stmt = $this->connection->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Add a column to existing table
     */
    public function addColumn($table, $column, $type, $options = [])
    {
        $columnObj = new Column($column, $type, $options);
        $sql = "ALTER TABLE {$table} ADD COLUMN " . $columnObj->toSql();
        
        if (isset($options['after'])) {
            $sql .= " AFTER {$options['after']}";
        } elseif (isset($options['first']) && $options['first']) {
            $sql .= " FIRST";
        }
        
        $this->connection->exec($sql);
    }

    /**
     * Drop a column from table
     */
    public function dropColumn($table, $column)
    {
        $this->connection->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
    }

    /**
     * Modify a column
     */
    public function modifyColumn($table, $column, $type, $options = [])
    {
        $columnObj = new Column($column, $type, $options);
        $sql = "ALTER TABLE {$table} MODIFY COLUMN " . $columnObj->toSql();
        $this->connection->exec($sql);
    }

    /**
     * Rename a column
     */
    public function renameColumn($table, $from, $to, $type = null, $options = [])
    {
        if ($type === null) {
            // Get current column definition
            $columns = $this->getColumns($table);
            foreach ($columns as $col) {
                if ($col['Field'] === $from) {
                    $type = $col['Type'];
                    break;
                }
            }
        }
        
        $columnObj = new Column($to, $type, $options);
        $sql = "ALTER TABLE {$table} CHANGE {$from} " . $columnObj->toSql();
        $this->connection->exec($sql);
    }

    /**
     * Create an index
     */
    public function createIndex($table, $columns, $name = null, $type = 'index')
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $name = $name ?: $table . '_' . implode('_', $columns) . '_' . $type;
        
        $columnList = implode(', ', $columns);
        
        switch (strtolower($type)) {
            case 'unique':
                $sql = "CREATE UNIQUE INDEX {$name} ON {$table} ({$columnList})";
                break;
            case 'fulltext':
                $sql = "CREATE FULLTEXT INDEX {$name} ON {$table} ({$columnList})";
                break;
            default:
                $sql = "CREATE INDEX {$name} ON {$table} ({$columnList})";
        }
        
        $this->connection->exec($sql);
    }

    /**
     * Drop an index
     */
    public function dropIndex($table, $name)
    {
        $this->connection->exec("DROP INDEX {$name} ON {$table}");
    }

    /**
     * Add a foreign key constraint
     */
    public function addForeignKey($table, $column, $referencedTable, $referencedColumn = 'id', $onDelete = 'CASCADE', $onUpdate = 'CASCADE', $name = null)
    {
        $name = $name ?: "fk_{$table}_{$column}_{$referencedTable}_{$referencedColumn}";
        
        $sql = "ALTER TABLE {$table} ADD CONSTRAINT {$name} FOREIGN KEY ({$column}) REFERENCES {$referencedTable}({$referencedColumn})";
        
        if ($onDelete) {
            $sql .= " ON DELETE {$onDelete}";
        }
        
        if ($onUpdate) {
            $sql .= " ON UPDATE {$onUpdate}";
        }
        
        $this->connection->exec($sql);
    }

    /**
     * Drop a foreign key constraint
     */
    public function dropForeignKey($table, $name)
    {
        $this->connection->exec("ALTER TABLE {$table} DROP FOREIGN KEY {$name}");
    }

    /**
     * Create indexes from blueprint
     */
    protected function createIndexes(Blueprint $blueprint)
    {
        $indexes = $blueprint->getIndexes();
        
        foreach ($indexes as $index) {
            $this->createIndex(
                $blueprint->getTable(),
                $index['columns'],
                $index['name'] ?? null,
                $index['type']
            );
        }
    }

    /**
     * Create foreign keys from blueprint
     */
    protected function createForeignKeys(Blueprint $blueprint)
    {
        $foreignKeys = $blueprint->getForeignKeys();
        
        foreach ($foreignKeys as $fk) {
            if ($fk['references'] && $fk['on']) {
                $this->addForeignKey(
                    $blueprint->getTable(),
                    implode(',', $fk['columns']),
                    $fk['on'],
                    implode(',', $fk['references']),
                    $fk['onDelete'],
                    $fk['onUpdate'],
                    $fk['name']
                );
            }
        }
    }

    /**
     * Execute raw SQL
     */
    public function statement($sql)
    {
        return $this->connection->exec($sql);
    }

    /**
     * Get database name
     */
    public function getDatabaseName()
    {
        $stmt = $this->connection->query('SELECT DATABASE()');
        return $stmt->fetchColumn();
    }

    /**
     * Get database size
     */
    public function getDatabaseSize()
    {
        $dbName = $this->getDatabaseName();
        $stmt = $this->connection->prepare(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' 
             FROM information_schema.tables 
             WHERE table_schema = ?"
        );
        $stmt->execute([$dbName]);
        return $stmt->fetchColumn();
    }

    /**
     * Get table size
     */
    public function getTableSize($table)
    {
        $dbName = $this->getDatabaseName();
        $stmt = $this->connection->prepare(
            "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB'
             FROM information_schema.TABLES 
             WHERE table_schema = ? AND table_name = ?"
        );
        $stmt->execute([$dbName, $table]);
        return $stmt->fetchColumn();
    }
}