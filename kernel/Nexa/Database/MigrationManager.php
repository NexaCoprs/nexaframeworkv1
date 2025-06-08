<?php

namespace Nexa\Database;

use PDO;
use Exception;
use Nexa\Core\Logger;

class MigrationManager
{
    protected $connection;
    protected $logger;
    protected $migrationsTable = 'migrations';
    protected $migrationsPath;
    
    public function __construct(PDO $connection, $migrationsPath = null)
    {
        $this->connection = $connection;
        $this->logger = new Logger();
        $this->migrationsPath = $migrationsPath ?: dirname(__DIR__, 3) . '/database/migrations';
        $this->ensureMigrationsTable();
    }
    
    /**
     * Create migrations table if it doesn't exist
     */
    protected function ensureMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->connection->exec($sql);
    }
    
    /**
     * Run pending migrations
     */
    public function migrate($steps = null)
    {
        $pendingMigrations = $this->getPendingMigrations();
        
        if (empty($pendingMigrations)) {
            $this->logger->info('No pending migrations.');
            return [];
        }
        
        if ($steps !== null) {
            $pendingMigrations = array_slice($pendingMigrations, 0, $steps);
        }
        
        $batch = $this->getNextBatchNumber();
        $executed = [];
        
        $this->connection->beginTransaction();
        
        try {
            foreach ($pendingMigrations as $migration) {
                $this->runMigration($migration, 'up');
                $this->recordMigration($migration, $batch);
                $executed[] = $migration;
                $this->logger->info("Migrated: {$migration}");
            }
            
            $this->connection->commit();
            return $executed;
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->logger->error("Migration failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Rollback migrations
     */
    public function rollback($steps = 1)
    {
        $migrations = $this->getExecutedMigrations($steps);
        
        if (empty($migrations)) {
            $this->logger->info('No migrations to rollback.');
            return [];
        }
        
        $rolledBack = [];
        
        $this->connection->beginTransaction();
        
        try {
            foreach (array_reverse($migrations) as $migration) {
                $this->runMigration($migration['migration'], 'down');
                $this->removeMigrationRecord($migration['migration']);
                $rolledBack[] = $migration['migration'];
                $this->logger->info("Rolled back: {$migration['migration']}");
            }
            
            $this->connection->commit();
            return $rolledBack;
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->logger->error("Rollback failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Reset all migrations
     */
    public function reset()
    {
        $migrations = $this->getAllExecutedMigrations();
        
        if (empty($migrations)) {
            $this->logger->info('No migrations to reset.');
            return [];
        }
        
        $reset = [];
        
        $this->connection->beginTransaction();
        
        try {
            foreach (array_reverse($migrations) as $migration) {
                $this->runMigration($migration['migration'], 'down');
                $reset[] = $migration['migration'];
                $this->logger->info("Reset: {$migration['migration']}");
            }
            
            // Clear migrations table
            $this->connection->exec("DELETE FROM {$this->migrationsTable}");
            
            $this->connection->commit();
            return $reset;
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->logger->error("Reset failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Refresh migrations (reset + migrate)
     */
    public function refresh()
    {
        $reset = $this->reset();
        $migrated = $this->migrate();
        
        return [
            'reset' => $reset,
            'migrated' => $migrated
        ];
    }
    
    /**
     * Get migration status
     */
    public function status()
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getAllExecutedMigrations();
        $executedNames = array_column($executedMigrations, 'migration');
        
        $status = [];
        
        foreach ($allMigrations as $migration) {
            $status[] = [
                'migration' => $migration,
                'status' => in_array($migration, $executedNames) ? 'Ran' : 'Pending',
                'batch' => $this->getMigrationBatch($migration)
            ];
        }
        
        return $status;
    }
    
    /**
     * Create a new migration file
     */
    public function createMigration($name, $table = null, $create = false)
    {
        $className = $this->getClassName($name);
        $fileName = $this->getFileName($className);
        $path = $this->migrationsPath . '/' . $fileName;
        
        if (file_exists($path)) {
            throw new Exception("Migration {$className} already exists!");
        }
        
        $this->ensureDirectoryExists(dirname($path));
        
        $stub = $this->getMigrationStub($className, $table, $create);
        file_put_contents($path, $stub);
        
        return $path;
    }
    
    /**
     * Run a specific migration
     */
    protected function runMigration($migrationName, $direction = 'up')
    {
        $path = $this->migrationsPath . '/' . $migrationName . '.php';
        
        if (!file_exists($path)) {
            throw new Exception("Migration file not found: {$path}");
        }
        
        require_once $path;
        
        $className = $this->getClassNameFromFile($migrationName);
        
        if (!class_exists($className)) {
            throw new Exception("Migration class not found: {$className}");
        }
        
        $migration = new $className($this->connection);
        
        if ($direction === 'up') {
            $migration->up();
        } else {
            $migration->down();
        }
    }
    
    /**
     * Get pending migrations
     */
    protected function getPendingMigrations()
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getAllExecutedMigrations();
        $executedNames = array_column($executedMigrations, 'migration');
        
        return array_diff($allMigrations, $executedNames);
    }
    
    /**
     * Get executed migrations
     */
    protected function getExecutedMigrations($steps = null)
    {
        $sql = "SELECT * FROM {$this->migrationsTable} ORDER BY batch DESC, id DESC";
        
        if ($steps !== null) {
            $sql .= " LIMIT {$steps}";
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all executed migrations
     */
    protected function getAllExecutedMigrations()
    {
        $sql = "SELECT * FROM {$this->migrationsTable} ORDER BY batch ASC, id ASC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all migration files
     */
    protected function getAllMigrationFiles()
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        
        $files = glob($this->migrationsPath . '/*.php');
        $migrations = [];
        
        foreach ($files as $file) {
            $migrations[] = basename($file, '.php');
        }
        
        sort($migrations);
        return $migrations;
    }
    
    /**
     * Record migration execution
     */
    protected function recordMigration($migration, $batch)
    {
        $sql = "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$migration, $batch]);
    }
    
    /**
     * Remove migration record
     */
    protected function removeMigrationRecord($migration)
    {
        $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$migration]);
    }
    
    /**
     * Get next batch number
     */
    protected function getNextBatchNumber()
    {
        $sql = "SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['max_batch'] ?? 0) + 1;
    }
    
    /**
     * Get migration batch number
     */
    protected function getMigrationBatch($migration)
    {
        $sql = "SELECT batch FROM {$this->migrationsTable} WHERE migration = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$migration]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['batch'] : null;
    }
    
    /**
     * Get class name from migration name
     */
    protected function getClassName($name)
    {
        return 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name))) . 'Table';
    }
    
    /**
     * Get file name for migration
     */
    protected function getFileName($className)
    {
        $timestamp = date('Y_m_d_His');
        return $timestamp . '_' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . '.php';
    }
    
    /**
     * Get class name from file name
     */
    protected function getClassNameFromFile($fileName)
    {
        // Remove timestamp prefix
        $className = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $fileName);
        // Convert snake_case to PascalCase
        return str_replace('_', '', ucwords($className, '_'));
    }
    
    /**
     * Ensure directory exists
     */
    protected function ensureDirectoryExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    
    /**
     * Get migration stub
     */
    protected function getMigrationStub($className, $table = null, $create = false)
    {
        $upContent = '// Add migration logic here';
        $downContent = '// Add rollback logic here';
        
        if ($create && $table) {
            $upContent = "\$this->createTable('{$table}', function(\$table) {\n            \$table->increments('id');\n            \$table->timestamps();\n        });";
            $downContent = "\$this->dropTable('{$table}');";
        } elseif ($table) {
            $upContent = "\$this->table('{$table}', function(\$table) {\n            // Add columns or modify table\n        });";
            $downContent = "\$this->table('{$table}', function(\$table) {\n            // Reverse the changes\n        });";
        }
        
        return <<<EOT
<?php

use Nexa\Database\Migration;

class {$className} extends Migration
{
    public function up()
    {
        {$upContent}
    }

    public function down()
    {
        {$downContent}
    }
}
EOT;
    }
}