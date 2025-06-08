<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Nexa\Database\MigrationManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use PDO;
use Exception;

class MigrateCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Run database migrations')
             ->addArgument('step', InputArgument::OPTIONAL, 'Number of migrations to run')
             ->addOption('rollback', null, InputOption::VALUE_NONE, 'Rollback migrations')
             ->addOption('reset', null, InputOption::VALUE_NONE, 'Reset all migrations')
             ->addOption('refresh', null, InputOption::VALUE_NONE, 'Reset and re-run all migrations')
             ->addOption('status', null, InputOption::VALUE_NONE, 'Show migration status')
             ->addOption('force', null, InputOption::VALUE_NONE, 'Force run in production');
    }

    protected function handle()
    {
        try {
            $connection = $this->getConnection();
            $migrationsPath = dirname(__DIR__, 4) . '/database/migrations';
            $manager = new MigrationManager($connection, $migrationsPath);

            // Show status
            if ($this->input->getOption('status')) {
                $this->showStatus($manager);
                return;
            }

            // Reset migrations
            if ($this->input->getOption('reset')) {
                $this->resetMigrations($manager);
                return;
            }

            // Refresh migrations
            if ($this->input->getOption('refresh')) {
                $this->refreshMigrations($manager);
                return;
            }

            // Rollback migrations
            if ($this->input->getOption('rollback')) {
                $steps = $this->input->getArgument('step') ? (int)$this->input->getArgument('step') : 1;
                $this->rollbackMigrations($manager, $steps);
                return;
            }

            // Run migrations
            $steps = isset($args['step']) ? (int)$args['step'] : null;
            $this->runMigrations($manager, $steps);

        } catch (Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
        }
    }

    private function runMigrations(MigrationManager $manager, $steps = null)
    {
        $this->info('Running migrations...');
        
        $executed = $manager->migrate($steps);
        
        if (empty($executed)) {
            $this->info('No pending migrations.');
        } else {
            foreach ($executed as $migration) {
                $this->success('Migrated: ' . $migration);
            }
            $this->success('Migration completed!');
        }
    }

    private function rollbackMigrations(MigrationManager $manager, $steps)
    {
        $this->info("Rolling back {$steps} migration(s)...");
        
        $rolledBack = $manager->rollback($steps);
        
        if (empty($rolledBack)) {
            $this->info('No migrations to rollback.');
        } else {
            foreach ($rolledBack as $migration) {
                $this->success('Rolled back: ' . $migration);
            }
            $this->success('Rollback completed!');
        }
    }

    private function resetMigrations(MigrationManager $manager)
    {
        $this->info('Resetting all migrations...');
        
        $reset = $manager->reset();
        
        if (empty($reset)) {
            $this->info('No migrations to reset.');
        } else {
            foreach ($reset as $migration) {
                $this->success('Reset: ' . $migration);
            }
            $this->success('Reset completed!');
        }
    }

    private function refreshMigrations(MigrationManager $manager)
    {
        $this->info('Refreshing migrations...');
        
        $result = $manager->refresh();
        
        if (!empty($result['reset'])) {
            foreach ($result['reset'] as $migration) {
                $this->info('Reset: ' . $migration);
            }
        }
        
        if (!empty($result['migrated'])) {
            foreach ($result['migrated'] as $migration) {
                $this->success('Migrated: ' . $migration);
            }
        }
        
        $this->success('Refresh completed!');
    }

    private function showStatus(MigrationManager $manager)
    {
        $this->info('Migration Status:');
        $this->info(str_repeat('-', 50));
        
        $status = $manager->status();
        
        if (empty($status)) {
            $this->info('No migrations found.');
            return;
        }
        
        foreach ($status as $migration) {
            $statusText = $migration['status'] === 'Ran' ? 
                "\033[32m" . $migration['status'] . "\033[0m" : 
                "\033[33m" . $migration['status'] . "\033[0m";
            
            $batch = $migration['batch'] ? " (Batch {$migration['batch']})" : '';
            $this->info($migration['migration'] . ' - ' . $statusText . $batch);
        }
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
}