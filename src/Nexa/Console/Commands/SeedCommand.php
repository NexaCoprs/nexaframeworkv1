<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use PDO;
use Exception;

class SeedCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Run database seeders')
             ->addArgument('class', InputArgument::OPTIONAL, 'Specific seeder class to run')
             ->addOption('force', null, InputOption::VALUE_NONE, 'Force run in production');
    }

    protected function handle()
    {
        try {
            $connection = $this->getConnection();
            $seedersPath = dirname(__DIR__, 4) . '/database/seeders';
            
            if (!is_dir($seedersPath)) {
                $this->error('Seeders directory not found!');
                return;
            }

            $specificClass = $this->input->getArgument('class');

            if ($specificClass) {
                $this->runSpecificSeeder($seedersPath, $specificClass, $connection);
            } else {
                $this->runAllSeeders($seedersPath, $connection);
            }

        } catch (Exception $e) {
            $this->error('Seeding failed: ' . $e->getMessage());
        }
    }

    private function runSpecificSeeder($seedersPath, $className, $connection)
    {
        $file = $seedersPath . '/' . $className . '.php';
        
        if (!file_exists($file)) {
            $this->error("Seeder {$className} not found!");
            return;
        }

        $this->info("Running seeder: {$className}");
        
        require_once $file;
        
        if (class_exists($className)) {
            $seeder = new $className($connection);
            $seeder->run();
            $this->success("Seeded: {$className}");
        } else {
            $this->error("Seeder class {$className} not found in file!");
        }
    }

    private function runAllSeeders($seedersPath, $connection)
    {
        $this->info('Running all seeders...');
        
        // Look for DatabaseSeeder first
        $databaseSeederFile = $seedersPath . '/DatabaseSeeder.php';
        
        if (file_exists($databaseSeederFile)) {
            $this->runSpecificSeeder($seedersPath, 'DatabaseSeeder', $connection);
            return;
        }
        
        // If no DatabaseSeeder, run all individual seeders
        $seederFiles = glob($seedersPath . '/*Seeder.php');
        
        if (empty($seederFiles)) {
            $this->info('No seeders found.');
            return;
        }
        
        foreach ($seederFiles as $file) {
            $className = basename($file, '.php');
            $this->runSpecificSeeder($seedersPath, $className, $connection);
        }
        
        $this->success('All seeders completed!');
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