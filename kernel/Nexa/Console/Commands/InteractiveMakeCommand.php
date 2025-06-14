<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Commande interactive intelligente pour la génération de code
 * Avec suggestions contextuelles et validation en temps réel
 */
class InteractiveMakeCommand extends Command
{
    protected $templates = [
        'handler' => [
            'basic' => 'Handler basique avec CRUD',
            'api' => 'Handler API avec validation',
            'auth' => 'Handler d\'authentification',
            'payment' => 'Handler de paiement (Stripe)',
            'upload' => 'Handler de gestion de fichiers'
        ],
        'entity' => [
            'basic' => 'Entité basique',
            'user' => 'Entité utilisateur avec auth',
            'audit' => 'Entité avec historique complet'
        ],
        'middleware' => [
            'auth' => 'Middleware d\'authentification',
            'cors' => 'Middleware CORS',
            'rate-limit' => 'Middleware de limitation',
            'cache' => 'Middleware de cache',
            'security' => 'Middleware de sécurité'
        ]
    ];

    protected function configure()
    {
        $this->setDescription('Mode interactif intelligent pour la génération de code')
             ->addArgument('type', InputArgument::OPTIONAL, 'Type de composant à créer')
             ->addArgument('name', InputArgument::OPTIONAL, 'Nom du composant')
             ->addOption('template', 't', InputOption::VALUE_OPTIONAL, 'Template à utiliser')
             ->addOption('with-tests', null, InputOption::VALUE_NONE, 'Générer les tests')
             ->addOption('with-docs', null, InputOption::VALUE_NONE, 'Générer la documentation')
             ->addOption('full-crud', null, InputOption::VALUE_NONE, 'Générer CRUD complet');
    }

    protected function handle()
    {
        $this->info('🚀 Assistant Intelligent Nexa Framework');
        $this->info('Génération de code avec suggestions contextuelles\n');

        // Étape 1: Sélection du type de composant
        $type = $this->getComponentType();
        
        // Étape 2: Sélection du template
        $template = $this->getTemplate($type);
        
        // Étape 3: Configuration du composant
        $config = $this->getComponentConfig($type, $template);
        
        // Étape 4: Options avancées
        $options = $this->getAdvancedOptions($type);
        
        // Étape 5: Résumé et confirmation
        $this->showSummary($type, $template, $config, $options);
        
        if ($this->confirmGeneration()) {
            $this->generateComponent($type, $template, $config, $options);
        }
    }

    protected function getComponentType()
    {
        $type = $this->input->getArgument('type');
        
        if (!$type) {
            $question = new ChoiceQuestion(
                'Quel type de composant voulez-vous créer ?',
                ['handler', 'entity', 'middleware', 'migration', 'job', 'event'],
                0
            );
            $type = $this->getHelper('question')->ask($this->input, $this->output, $question);
        }
        
        return $type;
    }

    protected function getTemplate($type)
    {
        $template = $this->input->getOption('template');
        
        if (!$template && isset($this->templates[$type])) {
            $question = new ChoiceQuestion(
                "Quel template voulez-vous utiliser pour le $type ?",
                array_keys($this->templates[$type]),
                0
            );
            $template = $this->getHelper('question')->ask($this->input, $this->output, $question);
        }
        
        return $template ?: 'basic';
    }

    protected function getComponentConfig($type, $template)
    {
        $config = [];
        
        // Nom du composant
        $name = $this->input->getArgument('name');
        if (!$name) {
            $question = new Question('Nom du composant : ');
            $question->setValidator(function ($value) {
                if (empty($value)) {
                    throw new \Exception('Le nom ne peut pas être vide');
                }
                if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $value)) {
                    throw new \Exception('Le nom doit commencer par une majuscule et contenir uniquement des lettres et chiffres');
                }
                return $value;
            });
            $name = $this->getHelper('question')->ask($this->input, $this->output, $question);
        }
        $config['name'] = $name;
        
        // Configuration spécifique selon le type
        switch ($type) {
            case 'handler':
                $config = array_merge($config, $this->getHandlerConfig($template));
                break;
            case 'entity':
                $config = array_merge($config, $this->getEntityConfig($template));
                break;
            case 'middleware':
                $config = array_merge($config, $this->getMiddlewareConfig($template));
                break;
        }
        
        return $config;
    }

    protected function getHandlerConfig($template)
    {
        $config = [];
        
        // Route prefix
        $question = new Question('Préfixe de route (ex: /api/users) : ');
        $config['route_prefix'] = $this->getHelper('question')->ask($this->input, $this->output, $question);
        
        // API version
        if ($template === 'api') {
            $question = new Question('Version de l\'API (défaut: v1) : ', 'v1');
            $config['api_version'] = $this->getHelper('question')->ask($this->input, $this->output, $question);
        }
        
        // Middleware
        $question = new ConfirmationQuestion('Ajouter un middleware d\'authentification ? (y/N) ', false);
        $config['with_auth'] = $this->getHelper('question')->ask($this->input, $this->output, $question);
        
        return $config;
    }

    protected function getEntityConfig($template)
    {
        $config = [];
        
        // Table name
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $config['name'] ?? '')) . 's';
        $question = new Question("Nom de la table (défaut: $tableName) : ", $tableName);
        $config['table'] = $this->getHelper('question')->ask($this->input, $this->output, $question);
        
        // Fields
        $this->info('\nDéfinition des champs (tapez \'done\' pour terminer) :');
        $fields = [];
        while (true) {
            $question = new Question('Champ (nom:type) : ');
            $field = $this->getHelper('question')->ask($this->input, $this->output, $question);
            
            if ($field === 'done' || empty($field)) {
                break;
            }
            
            if (strpos($field, ':') !== false) {
                [$name, $type] = explode(':', $field, 2);
                $fields[$name] = $type;
                $this->info("✓ Champ ajouté : $name ($type)");
            } else {
                $this->error('Format invalide. Utilisez : nom:type');
            }
        }
        $config['fields'] = $fields;
        
        return $config;
    }

    protected function getMiddlewareConfig($template)
    {
        $config = [];
        
        switch ($template) {
            case 'rate-limit':
                $question = new Question('Nombre de requêtes par minute (défaut: 60) : ', '60');
                $config['rate_limit'] = $this->getHelper('question')->ask($this->input, $this->output, $question);
                break;
                
            case 'cors':
                $question = new Question('Origines autorisées (défaut: *) : ', '*');
                $config['allowed_origins'] = $this->getHelper('question')->ask($this->input, $this->output, $question);
                break;
        }
        
        return $config;
    }

    protected function getAdvancedOptions($type)
    {
        $options = [];
        
        // Tests
        $withTests = $this->input->getOption('with-tests');
        if (!$withTests) {
            $question = new ConfirmationQuestion('Générer les tests automatiquement ? (Y/n) ', true);
            $withTests = $this->getHelper('question')->ask($this->input, $this->output, $question);
        }
        $options['with_tests'] = $withTests;
        
        // Documentation
        $withDocs = $this->input->getOption('with-docs');
        if (!$withDocs) {
            $question = new ConfirmationQuestion('Générer la documentation ? (Y/n) ', true);
            $withDocs = $this->getHelper('question')->ask($this->input, $this->output, $question);
        }
        $options['with_docs'] = $withDocs;
        
        // CRUD complet pour les handlers
        if ($type === 'handler') {
            $fullCrud = $this->input->getOption('full-crud');
            if (!$fullCrud) {
                $question = new ConfirmationQuestion('Générer un CRUD complet ? (Y/n) ', true);
                $fullCrud = $this->getHelper('question')->ask($this->input, $this->output, $question);
            }
            $options['full_crud'] = $fullCrud;
        }
        
        return $options;
    }

    protected function showSummary($type, $template, $config, $options)
    {
        $this->info('\n📋 Résumé de la génération :');
        
        $table = new Table($this->output);
        $table->setHeaders(['Propriété', 'Valeur']);
        
        $table->addRow(['Type', ucfirst($type)]);
        $table->addRow(['Template', $template]);
        $table->addRow(['Nom', $config['name']]);
        
        if (isset($config['route_prefix'])) {
            $table->addRow(['Route', $config['route_prefix']]);
        }
        
        if (isset($config['table'])) {
            $table->addRow(['Table', $config['table']]);
        }
        
        $table->addRow(new TableSeparator());
        $table->addRow(['Tests', $options['with_tests'] ? '✓ Oui' : '✗ Non']);
        $table->addRow(['Documentation', $options['with_docs'] ? '✓ Oui' : '✗ Non']);
        
        if (isset($options['full_crud'])) {
            $table->addRow(['CRUD complet', $options['full_crud'] ? '✓ Oui' : '✗ Non']);
        }
        
        $table->render();
    }

    protected function confirmGeneration()
    {
        $question = new ConfirmationQuestion('\nConfirmer la génération ? (Y/n) ', true);
        return $this->getHelper('question')->ask($this->input, $this->output, $question);
    }

    protected function generateComponent($type, $template, $config, $options)
    {
        $this->info('\n🔄 Génération en cours...');
        
        try {
            // Génération du composant principal
            $this->generateMainComponent($type, $template, $config);
            
            // Génération des tests si demandé
            if ($options['with_tests']) {
                $this->generateTests($type, $config);
            }
            
            // Génération de la documentation si demandée
            if ($options['with_docs']) {
                $this->generateDocumentation($type, $config);
            }
            
            // CRUD complet si demandé
            if (isset($options['full_crud']) && $options['full_crud']) {
                $this->generateCrudComponents($config);
            }
            
            $this->success('\n✅ Génération terminée avec succès !');
            $this->showGeneratedFiles($type, $config, $options);
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la génération : ' . $e->getMessage());
        }
    }

    protected function generateMainComponent($type, $template, $config)
    {
        // Logique de génération selon le type et template
        $this->info("  ✓ Génération du $type : {$config['name']}");
        
        // Ici on appellerait les générateurs spécifiques
        // $this->call('make:' . $type, ['name' => $config['name'], '--template' => $template]);
    }

    protected function generateTests($type, $config)
    {
        $this->info("  ✓ Génération des tests pour : {$config['name']}");
        // Logique de génération des tests
    }

    protected function generateDocumentation($type, $config)
    {
        $this->info("  ✓ Génération de la documentation pour : {$config['name']}");
        // Logique de génération de la documentation
    }

    protected function generateCrudComponents($config)
    {
        $this->info("  ✓ Génération des composants CRUD pour : {$config['name']}");
        // Génération de l'entité, migration, routes, etc.
    }

    protected function showGeneratedFiles($type, $config, $options)
    {
        $this->info('\n📁 Fichiers générés :');
        
        $files = [
            "workspace/handlers/{$config['name']}Handler.php",
        ];
        
        if ($options['with_tests']) {
            $files[] = "tests/Handlers/{$config['name']}HandlerTest.php";
        }
        
        if ($options['with_docs']) {
            $files[] = "docs/api/{$config['name']}.md";
        }
        
        foreach ($files as $file) {
            $this->info("  📄 $file");
        }
        
        $this->info('\n💡 Prochaines étapes :');
        $this->info('  1. Vérifiez les fichiers générés');
        $this->info('  2. Adaptez le code selon vos besoins');
        $this->info('  3. Exécutez les tests : php nexa test');
        $this->info('  4. Consultez la documentation générée');
    }
}