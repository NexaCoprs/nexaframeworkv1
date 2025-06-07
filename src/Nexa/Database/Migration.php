<?php

namespace Nexa\Database;

use PDO;
use Exception;

abstract class Migration
{
    protected $connection;
    protected $schema;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->schema = new Schema($connection);
    }

    abstract public function up();
    abstract public function down();

    public function run()
    {
        $this->up();
    }

    public function rollback()
    {
        $this->down();
    }

    /**
     * Create a new table
     */
    protected function createTable($tableName, $callback)
    {
        $blueprint = new Blueprint($tableName);
        $callback($blueprint);
        $this->schema->create($blueprint);
    }

    /**
     * Modify an existing table
     */
    protected function table($tableName, $callback)
    {
        $blueprint = new Blueprint($tableName, 'alter');
        $callback($blueprint);
        $this->schema->alter($blueprint);
    }

    /**
     * Drop a table
     */
    protected function dropTable($tableName)
    {
        $this->schema->drop($tableName);
    }

    /**
     * Drop a table if it exists
     */
    protected function dropTableIfExists($tableName)
    {
        $this->schema->dropIfExists($tableName);
    }

    /**
     * Rename a table
     */
    protected function renameTable($from, $to)
    {
        $this->schema->rename($from, $to);
    }

    /**
     * Check if table exists
     */
    protected function hasTable($tableName)
    {
        return $this->schema->hasTable($tableName);
    }

    /**
     * Check if column exists
     */
    protected function hasColumn($tableName, $columnName)
    {
        return $this->schema->hasColumn($tableName, $columnName);
    }

    /**
     * Add a column (legacy method)
     */
    protected function addColumn($tableName, $columnName, $type, $options = [])
    {
        $this->schema->addColumn($tableName, $columnName, $type, $options);
    }

    /**
     * Drop a column (legacy method)
     */
    protected function dropColumn($tableName, $columnName)
    {
        $this->schema->dropColumn($tableName, $columnName);
    }

    /**
     * Execute raw SQL
     */
    protected function statement($sql)
    {
        return $this->connection->exec($sql);
    }

    /**
     * Execute raw SQL with bindings
     */
    protected function query($sql, $bindings = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /**
     * Create an index
     */
    protected function createIndex($tableName, $columns, $indexName = null, $type = 'index')
    {
        $this->schema->createIndex($tableName, $columns, $indexName, $type);
    }

    /**
     * Drop an index
     */
    protected function dropIndex($tableName, $indexName)
    {
        $this->schema->dropIndex($tableName, $indexName);
    }

    /**
     * Create a foreign key
     */
    protected function foreign($tableName, $column, $referencedTable, $referencedColumn = 'id', $onDelete = 'CASCADE', $onUpdate = 'CASCADE')
    {
        $this->schema->addForeignKey($tableName, $column, $referencedTable, $referencedColumn, $onDelete, $onUpdate);
    }

    /**
     * Drop a foreign key
     */
    protected function dropForeign($tableName, $constraintName)
    {
        $this->schema->dropForeignKey($tableName, $constraintName);
    }

    /**
     * Seed data into table
     */
    protected function seed($tableName, $data)
    {
        if (empty($data)) {
            return;
        }

        $columns = array_keys($data[0]);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        $sql = "INSERT INTO {$tableName} (" . implode(',', $columns) . ") VALUES ({$placeholders})";
        
        $stmt = $this->connection->prepare($sql);
        
        foreach ($data as $row) {
            $stmt->execute(array_values($row));
        }
    }

    /**
     * Truncate table
     */
    protected function truncate($tableName)
    {
        $this->connection->exec("TRUNCATE TABLE {$tableName}");
    }

    /**
     * Disable foreign key checks
     */
    protected function disableForeignKeyChecks()
    {
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 0');
    }

    /**
     * Enable foreign key checks
     */
    protected function enableForeignKeyChecks()
    {
        $this->connection->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}