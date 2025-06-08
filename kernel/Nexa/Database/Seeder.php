<?php

namespace Nexa\Database;

use PDO;

abstract class Seeder
{
    protected $connection;
    protected $schema;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->schema = new Schema($connection);
    }

    /**
     * Run the database seeder
     */
    abstract public function run();

    /**
     * Call another seeder
     */
    protected function call($seederClass)
    {
        if (class_exists($seederClass)) {
            $seeder = new $seederClass($this->connection);
            $seeder->run();
        }
    }

    /**
     * Insert data into a table
     */
    protected function insert($table, array $data)
    {
        if (empty($data)) {
            return;
        }

        // Handle single row or multiple rows
        $rows = isset($data[0]) && is_array($data[0]) ? $data : [$data];
        
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $placeholders = ':' . implode(', :', $columns);
            $columnsList = implode(', ', $columns);
            
            $sql = "INSERT INTO {$table} ({$columnsList}) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($sql);
            
            foreach ($row as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
        }
    }

    /**
     * Truncate a table
     */
    protected function truncate($table)
    {
        $this->connection->exec("TRUNCATE TABLE {$table}");
    }

    /**
     * Delete all records from a table
     */
    protected function delete($table)
    {
        $this->connection->exec("DELETE FROM {$table}");
    }

    /**
     * Execute raw SQL
     */
    protected function statement($sql, array $bindings = [])
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($bindings);
    }

    /**
     * Get a query builder for a table
     */
    protected function table($table)
    {
        return new QueryBuilder(null, $this->connection, $table);
    }

    /**
     * Disable foreign key checks
     */
    protected function disableForeignKeyChecks()
    {
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        switch ($driver) {
            case 'mysql':
                $this->connection->exec('SET FOREIGN_KEY_CHECKS=0');
                break;
            case 'sqlite':
                $this->connection->exec('PRAGMA foreign_keys=OFF');
                break;
            case 'pgsql':
                // PostgreSQL doesn't have a global way to disable FK checks
                break;
        }
    }

    /**
     * Enable foreign key checks
     */
    protected function enableForeignKeyChecks()
    {
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        switch ($driver) {
            case 'mysql':
                $this->connection->exec('SET FOREIGN_KEY_CHECKS=1');
                break;
            case 'sqlite':
                $this->connection->exec('PRAGMA foreign_keys=ON');
                break;
            case 'pgsql':
                // PostgreSQL doesn't have a global way to enable FK checks
                break;
        }
    }

    /**
     * Generate fake data using simple patterns
     */
    protected function fake()
    {
        return new class {
            public function name()
            {
                $names = ['John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Diana', 'Eve', 'Frank'];
                $surnames = ['Doe', 'Smith', 'Johnson', 'Brown', 'Davis', 'Miller', 'Wilson', 'Moore'];
                return $names[array_rand($names)] . ' ' . $surnames[array_rand($surnames)];
            }

            public function email()
            {
                $domains = ['example.com', 'test.com', 'demo.org', 'sample.net'];
                $username = strtolower(str_replace(' ', '.', $this->name()));
                return $username . '@' . $domains[array_rand($domains)];
            }

            public function text($length = 100)
            {
                $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit'];
                $text = '';
                while (strlen($text) < $length) {
                    $text .= $words[array_rand($words)] . ' ';
                }
                return trim(substr($text, 0, $length));
            }

            public function number($min = 1, $max = 100)
            {
                return rand($min, $max);
            }

            public function boolean()
            {
                return (bool) rand(0, 1);
            }

            public function date($format = 'Y-m-d')
            {
                $timestamp = rand(strtotime('-1 year'), time());
                return date($format, $timestamp);
            }

            public function dateTime($format = 'Y-m-d H:i:s')
            {
                $timestamp = rand(strtotime('-1 year'), time());
                return date($format, $timestamp);
            }
        };
    }
}