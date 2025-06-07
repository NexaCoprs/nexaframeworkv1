<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Nexa\Console\Kernel;
use Nexa\Database\MigrationManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use PDO;
use Exception;

class MakeMigrationCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Create a new migration file')
             ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
             ->addOption('create', null, InputOption::VALUE_OPTIONAL, 'Create a new table')
             ->addOption('table', null, InputOption::VALUE_OPTIONAL, 'Modify an existing table');
    }

    protected function handle()
    {
        try {
            $name = $this->input->getArgument('name');
            
            if (!$name) {
                $this->error('Migration name is required.');
                return;
            }

            $connection = $this->getConnection();
            $migrationsPath = dirname(__DIR__, 4) . '/database/migrations';
            $manager = new MigrationManager($connection, $migrationsPath);

            $table = null;
            $create = false;

            if ($this->input->getOption('create')) {
                $createValue = $this->input->getOption('create');
                $table = is_string($createValue) ? $createValue : $this->extractTableName($name);
                $create = true;
            } elseif ($this->input->getOption('table')) {
                $tableValue = $this->input->getOption('table');
                $table = is_string($tableValue) ? $tableValue : $this->extractTableName($name);
            }

            $filename = $manager->createMigration($name, $table, $create);
            
            $this->success('Migration created: ' . basename($filename));
            $this->info('Location: ' . $filename);

        } catch (Exception $e) {
            $this->error('Failed to create migration: ' . $e->getMessage());
        }
    }

    private function extractTableName($migrationName)
    {
        // Extract table name from migration name patterns
        if (preg_match('/create_(.+)_table/', $migrationName, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/add_(.+)_to_(.+)/', $migrationName, $matches)) {
            return $matches[2];
        }
        
        if (preg_match('/drop_(.+)_from_(.+)/', $migrationName, $matches)) {
            return $matches[2];
        }
        
        if (preg_match('/modify_(.+)_in_(.+)/', $migrationName, $matches)) {
            return $matches[2];
        }
        
        // Default: use the migration name as table name
        return str_replace(['_table', '_migration'], '', $migrationName);
    }

    private function getConnection()
    {
        $host = $this->getEnv('DB_HOST', 'localhost');
        $dbname = $this->getEnv('DB_NAME', 'nexa');
        $username = $this->getEnv('DB_USER', 'root');
        $password = $this->getEnv('DB_PASS', '');
        $driver = $this->getEnv('DB_DRIVER', 'mysql');
        
        if ($driver === 'sqlite') {
            $dsn = "sqlite:" . $this->getDatabasePath();
            return new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
        
        $dsn = "{$driver}:host={$host};dbname={$dbname}";
        
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }

    private function getEnv($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }

    private function getDatabasePath()
    {
        return dirname(__DIR__, 4) . '/database/database.sqlite';
    }

    protected function call($command, array $arguments = [])
    {
        $kernel = new Kernel($this->getApplication());
        $input = new ArrayInput(array_merge(['command' => $command], $arguments));
        return $kernel->handle($input, $this->output);
    }
}