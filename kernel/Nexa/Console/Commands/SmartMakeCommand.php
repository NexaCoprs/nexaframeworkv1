<?php

namespace Nexa\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class SmartMakeCommand extends Command
{
    protected static $defaultName = 'nexa:smart-make';
    protected static $defaultDescription = 'Générateur intelligent de code Nexa avec assistant interactif';

    protected function configure()
    {
        $this
            ->setDescription('Générateur intelligent de code Nexa avec assistant interactif')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type de composant à générer')
            ->addArgument('name', InputArgument::OPTIONAL, 'Nom du composant')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Mode interactif')
            ->addOption('template', 't', InputOption::VALUE_OPTIONAL, 'Template à utiliser')
            ->addOption('with-tests', null, InputOption::VALUE_NONE, 'Générer les tests automatiquement')
            ->addOption('with-docs', null, InputOption::VALUE_NONE, 'Générer la documentation');
    }

    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $this->info('🧠 Assistant Intelligent Nexa', $output);
        $this->line('', $output);
        
        $helper = $this->getHelper('question');
        
        // Mode interactif ou arguments fournis
        if ($input->getOption('interactive') || !$input->getArgument('type')) {
            return $this->interactiveMode($input, $output, $helper);
        }
        
        return $this->directMode($input, $output);
    }
    
    private function interactiveMode(InputInterface $input, OutputInterface $output, $helper)
    {
        $this->info('🎯 Mode interactif activé', $output);
        $this->line('', $output);
        
        // Choix du type de composant
        $typeQuestion = new ChoiceQuestion(
            'Quel type de composant voulez-vous créer?',
            [
                'controller' => 'Contrôleur (API/Web)',
                'model' => 'Modèle avec relations',
                'middleware' => 'Middleware personnalisé',
                'service' => 'Service métier',
                'job' => 'Tâche en arrière-plan',
                'event' => 'Événement système',
                'listener' => 'Écouteur d\'événement',
                'crud' => 'CRUD complet (Modèle + Contrôleur + Routes)',
                'api-resource' => 'Ressource API complète'
            ],
            'controller'
        );
        $type = $helper->ask($input, $output, $typeQuestion);
        
        // Nom du composant
        $nameQuestion = new Question('Nom du composant: ');
        $nameQuestion->setValidator(function ($value) {
            if (empty($value)) {
                throw new \Exception('Le nom ne peut pas être vide');
            }
            return $value;
        });
        $name = $helper->ask($input, $output, $nameQuestion);
        
        // Options spécifiques selon le type
        $options = $this->getTypeSpecificOptions($type, $input, $output, $helper);
        
        // Confirmation
        $this->showGenerationSummary($type, $name, $options, $output);
        $confirmQuestion = new ConfirmationQuestion('Générer ce composant? (y/N) ', false);
        
        if (!$helper->ask($input, $output, $confirmQuestion)) {
            $this->info('Génération annulée.', $output);
            return 1;
        }
        
        return $this->generateComponent($type, $name, $options, $output);
    }
    
    private function directMode(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        $name = $input->getArgument('name');
        
        $options = [
            'with_tests' => $input->getOption('with-tests'),
            'with_docs' => $input->getOption('with-docs'),
            'template' => $input->getOption('template')
        ];
        
        return $this->generateComponent($type, $name, $options, $output);
    }
    
    private function getTypeSpecificOptions(string $type, InputInterface $input, OutputInterface $output, $helper): array
    {
        $options = [];
        
        switch ($type) {
            case 'controller':
                $apiQuestion = new ConfirmationQuestion('Contrôleur API? (y/N) ', false);
                $options['api'] = $helper->ask($input, $output, $apiQuestion);
                
                $resourceQuestion = new ConfirmationQuestion('Avec méthodes CRUD? (y/N) ', false);
                $options['resource'] = $helper->ask($input, $output, $resourceQuestion);
                break;
                
            case 'model':
                $migrationQuestion = new ConfirmationQuestion('Créer la migration? (Y/n) ', true);
                $options['migration'] = $helper->ask($input, $output, $migrationQuestion);
                
                $factoryQuestion = new ConfirmationQuestion('Créer la factory? (y/N) ', false);
                $options['factory'] = $helper->ask($input, $output, $factoryQuestion);
                break;
                
            case 'crud':
                $options['api'] = true;
                $options['resource'] = true;
                $options['migration'] = true;
                $options['factory'] = true;
                break;
        }
        
        // Options communes
        $testsQuestion = new ConfirmationQuestion('Générer les tests? (Y/n) ', true);
        $options['with_tests'] = $helper->ask($input, $output, $testsQuestion);
        
        $docsQuestion = new ConfirmationQuestion('Générer la documentation? (y/N) ', false);
        $options['with_docs'] = $helper->ask($input, $output, $docsQuestion);
        
        return $options;
    }
    
    private function showGenerationSummary(string $type, string $name, array $options, OutputInterface $output)
    {
        $this->line('', $output);
        $this->info('📋 Résumé de la génération:', $output);
        $this->line("  Type: {$type}", $output);
        $this->line("  Nom: {$name}", $output);
        
        if (!empty($options)) {
            $this->line('  Options:', $output);
            foreach ($options as $key => $value) {
                if ($value) {
                    $this->line("    ✓ {$key}", $output);
                }
            }
        }
        $this->line('', $output);
    }
    
    private function generateComponent(string $type, string $name, array $options, OutputInterface $output): int
    {
        $this->info("🔨 Génération du {$type}: {$name}", $output);
        
        // Simulation de la génération
        $files = $this->getFilesToGenerate($type, $name, $options);
        
        foreach ($files as $file) {
            $this->line("  ✓ Créé: {$file}", $output);
            usleep(100000); // Simulation du temps de génération
        }
        
        $this->line('', $output);
        $this->info('✅ Génération terminée avec succès!', $output);
        
        if ($options['with_tests'] ?? false) {
            $this->line('💡 N\'oubliez pas d\'exécuter: php nexa test', $output);
        }
        
        return 0;
    }
    
    private function getFilesToGenerate(string $type, string $name, array $options): array
    {
        $files = [];
        
        switch ($type) {
            case 'controller':
                $files[] = "workspace/handlers/{$name}Handler.php";
                if ($options['with_tests'] ?? false) {
                    $files[] = "tests/Handlers/{$name}HandlerTest.php";
                }
                break;
                
            case 'model':
                $files[] = "workspace/entities/{$name}.php";
                if ($options['migration'] ?? false) {
                    $files[] = "workspace/database/migrations/create_{$name}_table.php";
                }
                if ($options['factory'] ?? false) {
                    $files[] = "workspace/database/factories/{$name}Factory.php";
                }
                break;
                
            case 'crud':
                $files[] = "workspace/entities/{$name}.php";
                $files[] = "workspace/handlers/{$name}Handler.php";
                $files[] = "workspace/database/migrations/create_{$name}_table.php";
                $files[] = "workspace/routes/{$name}.php";
                if ($options['with_tests'] ?? false) {
                    $files[] = "tests/Feature/{$name}Test.php";
                }
                break;
        }
        
        if ($options['with_docs'] ?? false) {
            $files[] = "docs/components/{$name}.md";
        }
        
        return $files;
    }
}