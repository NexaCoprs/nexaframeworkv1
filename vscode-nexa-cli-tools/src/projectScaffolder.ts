import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';
import { promisify } from 'util';
import { exec } from 'child_process';

const execAsync = promisify(exec);
const writeFileAsync = promisify(fs.writeFile);
const mkdirAsync = promisify(fs.mkdir);
const existsAsync = promisify(fs.exists);

export interface ProjectTemplate {
    name: string;
    description: string;
    type: 'api' | 'web' | 'microservice' | 'full';
    features: string[];
    dependencies: string[];
    structure: ProjectStructure;
}

export interface ProjectStructure {
    directories: string[];
    files: { [path: string]: string };
}

export interface ScaffoldOptions {
    projectName: string;
    template: ProjectTemplate;
    targetDirectory: string;
    features: string[];
    database?: 'mysql' | 'postgresql' | 'sqlite';
    authentication?: boolean;
    api?: boolean;
    frontend?: 'none' | 'vue' | 'react' | 'vanilla';
}

export class ProjectScaffolder {
    private templates: ProjectTemplate[] = [];
    private outputChannel: vscode.OutputChannel;

    constructor() {
        this.outputChannel = vscode.window.createOutputChannel('Nexa Scaffolder');
        this.initializeTemplates();
    }

    private initializeTemplates(): void {
        this.templates = [
            {
                name: 'API REST',
                description: 'Projet API REST avec authentification',
                type: 'api',
                features: ['routing', 'middleware', 'authentication', 'database', 'validation'],
                dependencies: ['nexa/auth', 'nexa/database', 'nexa/validation'],
                structure: {
                    directories: [
                        'app/Handlers',
                        'app/Middleware',
                        'app/Models',
                        'app/Services',
                        'config',
                        'database/migrations',
                        'database/seeders',
                        'routes',
                        'tests/Unit',
                        'tests/Feature'
                    ],
                    files: {
                        'routes/api.php': this.getApiRoutesTemplate(),
                        'app/Handlers/AuthHandler.php': this.getAuthHandlerTemplate(),
                        'app/Middleware/AuthMiddleware.php': this.getAuthMiddlewareTemplate(),
                        'config/database.php': this.getDatabaseConfigTemplate()
                    }
                }
            },
            {
                name: 'Application Web',
                description: 'Application web complète avec vues',
                type: 'web',
                features: ['routing', 'views', 'assets', 'authentication', 'database'],
                dependencies: ['nexa/view', 'nexa/auth', 'nexa/database'],
                structure: {
                    directories: [
                        'app/Handlers',
                        'app/Models',
                        'resources/views',
                        'resources/assets/css',
                        'resources/assets/js',
                        'public/css',
                        'public/js',
                        'config',
                        'database/migrations',
                        'routes'
                    ],
                    files: {
                        'routes/web.php': this.getWebRoutesTemplate(),
                        'resources/views/layout.php': this.getLayoutTemplate(),
                        'resources/views/home.php': this.getHomeTemplate()
                    }
                }
            },
            {
                name: 'Microservice',
                description: 'Microservice léger avec API',
                type: 'microservice',
                features: ['routing', 'middleware', 'validation', 'logging'],
                dependencies: ['nexa/microservice', 'nexa/validation'],
                structure: {
                    directories: [
                        'app/Handlers',
                        'app/Services',
                        'config',
                        'routes',
                        'tests'
                    ],
                    files: {
                        'routes/api.php': this.getMicroserviceRoutesTemplate(),
                        'config/microservice.php': this.getMicroserviceConfigTemplate()
                    }
                }
            },
            {
                name: 'Projet Complet',
                description: 'Projet full-stack avec toutes les fonctionnalités',
                type: 'full',
                features: ['routing', 'views', 'api', 'authentication', 'database', 'websockets', 'queue'],
                dependencies: ['nexa/full'],
                structure: {
                    directories: [
                        'app/Handlers',
                        'app/Middleware',
                        'app/Models',
                        'app/Services',
                        'app/Jobs',
                        'app/WebSockets',
                        'resources/views',
                        'resources/assets',
                        'public',
                        'config',
                        'database/migrations',
                        'database/seeders',
                        'routes',
                        'tests/Unit',
                        'tests/Feature',
                        'tests/Integration'
                    ],
                    files: {
                        'routes/web.php': this.getWebRoutesTemplate(),
                        'routes/api.php': this.getApiRoutesTemplate(),
                        'config/app.php': this.getAppConfigTemplate()
                    }
                }
            }
        ];
    }

    public async createProject(): Promise<void> {
        try {
            // Sélection du template
            const template = await this.selectTemplate();
            if (!template) return;

            // Configuration du projet
            const options = await this.configureProject(template);
            if (!options) return;

            // Création du projet
            await this.scaffoldProject(options);

            // Ouverture du projet
            await this.openProject(options.targetDirectory);

        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la création du projet: ${error}`);
        }
    }

    private async selectTemplate(): Promise<ProjectTemplate | undefined> {
        const items = this.templates.map(template => ({
            label: `$(package) ${template.name}`,
            description: template.description,
            detail: `Type: ${template.type} | Fonctionnalités: ${template.features.join(', ')}`,
            template
        }));

        const selected = await vscode.window.showQuickPick(items, {
            placeHolder: 'Sélectionnez un template de projet',
            matchOnDescription: true,
            matchOnDetail: true
        });

        return selected?.template;
    }

    private async configureProject(template: ProjectTemplate): Promise<ScaffoldOptions | undefined> {
        // Nom du projet
        const projectName = await vscode.window.showInputBox({
            prompt: 'Nom du projet',
            placeHolder: 'mon-projet-nexa',
            validateInput: (value) => {
                if (!value || value.trim().length === 0) {
                    return 'Le nom du projet est requis';
                }
                if (!/^[a-zA-Z0-9-_]+$/.test(value)) {
                    return 'Le nom ne peut contenir que des lettres, chiffres, tirets et underscores';
                }
                return null;
            }
        });

        if (!projectName) return undefined;

        // Répertoire cible
        const targetUri = await vscode.window.showOpenDialog({
            canSelectFiles: false,
            canSelectFolders: true,
            canSelectMany: false,
            openLabel: 'Sélectionner le répertoire parent'
        });

        if (!targetUri || targetUri.length === 0) return undefined;

        const targetDirectory = path.join(targetUri[0].fsPath, projectName);

        // Configuration de la base de données
        let database: 'mysql' | 'postgresql' | 'sqlite' | undefined;
        if (template.features.includes('database')) {
            const dbChoice = await vscode.window.showQuickPick([
                { label: 'MySQL', value: 'mysql' as const },
                { label: 'PostgreSQL', value: 'postgresql' as const },
                { label: 'SQLite', value: 'sqlite' as const }
            ], {
                placeHolder: 'Sélectionnez une base de données'
            });
            database = dbChoice?.value;
        }

        // Authentification
        let authentication = false;
        if (template.features.includes('authentication')) {
            const authChoice = await vscode.window.showQuickPick([
                { label: 'Oui', value: true },
                { label: 'Non', value: false }
            ], {
                placeHolder: 'Inclure l\'authentification ?'
            });
            authentication = authChoice?.value ?? false;
        }

        return {
            projectName,
            template,
            targetDirectory,
            features: template.features,
            database,
            authentication
        };
    }

    private async scaffoldProject(options: ScaffoldOptions): Promise<void> {
        this.outputChannel.show();
        this.outputChannel.appendLine(`Création du projet ${options.projectName}...`);

        // Vérifier si le répertoire existe déjà
        if (fs.existsSync(options.targetDirectory)) {
            throw new Error(`Le répertoire ${options.targetDirectory} existe déjà`);
        }

        // Créer le répertoire principal
        await mkdirAsync(options.targetDirectory, { recursive: true });
        this.outputChannel.appendLine(`Répertoire créé: ${options.targetDirectory}`);

        // Créer la structure de répertoires
        await this.createDirectories(options);

        // Créer les fichiers
        await this.createFiles(options);

        // Créer le fichier composer.json
        await this.createComposerJson(options);

        // Créer le fichier .env
        await this.createEnvFile(options);

        // Créer le fichier README.md
        await this.createReadme(options);

        // Initialiser Git
        await this.initializeGit(options.targetDirectory);

        this.outputChannel.appendLine('Projet créé avec succès!');
    }

    private async createDirectories(options: ScaffoldOptions): Promise<void> {
        for (const dir of options.template.structure.directories) {
            const fullPath = path.join(options.targetDirectory, dir);
            await mkdirAsync(fullPath, { recursive: true });
            this.outputChannel.appendLine(`Répertoire créé: ${dir}`);
        }
    }

    private async createFiles(options: ScaffoldOptions): Promise<void> {
        for (const [filePath, content] of Object.entries(options.template.structure.files)) {
            const fullPath = path.join(options.targetDirectory, filePath);
            const processedContent = this.processTemplate(content, options);
            await writeFileAsync(fullPath, processedContent, 'utf8');
            this.outputChannel.appendLine(`Fichier créé: ${filePath}`);
        }
    }

    private processTemplate(content: string, options: ScaffoldOptions): string {
        return content
            .replace(/{{PROJECT_NAME}}/g, options.projectName)
            .replace(/{{PROJECT_NAME_PASCAL}}/g, this.toPascalCase(options.projectName))
            .replace(/{{DATABASE_TYPE}}/g, options.database || 'sqlite')
            .replace(/{{NAMESPACE}}/g, this.toPascalCase(options.projectName));
    }

    private toPascalCase(str: string): string {
        return str
            .split(/[-_]/) 
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join('');
    }

    private async createComposerJson(options: ScaffoldOptions): Promise<void> {
        const composerJson = {
            name: `nexa/${options.projectName}`,
            description: `Projet Nexa - ${options.projectName}`,
            type: 'project',
            require: {
                'php': '^8.0',
                'nexa/framework': '^1.0'
            },
            'require-dev': {
                'phpunit/phpunit': '^9.0'
            },
            autoload: {
                'psr-4': {
                    [`${this.toPascalCase(options.projectName)}\\`]: 'app/'
                }
            },
            scripts: {
                'post-create-project-cmd': [
                    'php nexa key:generate'
                ]
            }
        };

        const filePath = path.join(options.targetDirectory, 'composer.json');
        await writeFileAsync(filePath, JSON.stringify(composerJson, null, 2), 'utf8');
    }

    private async createEnvFile(options: ScaffoldOptions): Promise<void> {
        let envContent = `APP_NAME=${options.projectName}
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

`;

        if (options.database) {
            envContent += `DB_CONNECTION=${options.database}
`;
            switch (options.database) {
                case 'mysql':
                    envContent += `DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${options.projectName}
DB_USERNAME=root
DB_PASSWORD=
`;
                    break;
                case 'postgresql':
                    envContent += `DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=${options.projectName}
DB_USERNAME=postgres
DB_PASSWORD=
`;
                    break;
                case 'sqlite':
                    envContent += `DB_DATABASE=database/${options.projectName}.sqlite
`;
                    break;
            }
        }

        const filePath = path.join(options.targetDirectory, '.env');
        await writeFileAsync(filePath, envContent, 'utf8');
    }

    private async createReadme(options: ScaffoldOptions): Promise<void> {
        const readmeContent = `# ${options.projectName}

${options.template.description}

## Installation

1. Installer les dépendances:
\`\`\`bash
composer install
\`\`\`

2. Configurer l'environnement:
\`\`\`bash
cp .env.example .env
php nexa key:generate
\`\`\`

3. Configurer la base de données dans le fichier .env

4. Exécuter les migrations:
\`\`\`bash
php nexa migrate
\`\`\`

## Utilisation

Démarrer le serveur de développement:
\`\`\`bash
php nexa serve
\`\`\`

## Fonctionnalités

${options.features.map(feature => `- ${feature}`).join('\n')}

## Tests

\`\`\`bash
php nexa test
\`\`\`
`;

        const filePath = path.join(options.targetDirectory, 'README.md');
        await writeFileAsync(filePath, readmeContent, 'utf8');
    }

    private async initializeGit(projectPath: string): Promise<void> {
        try {
            await execAsync('git init', { cwd: projectPath });
            
            // Créer .gitignore
            const gitignoreContent = `/vendor/
/node_modules/
.env
/storage/logs/*
/storage/cache/*
!/storage/logs/.gitkeep
!/storage/cache/.gitkeep
`;
            
            const gitignorePath = path.join(projectPath, '.gitignore');
            await writeFileAsync(gitignorePath, gitignoreContent, 'utf8');
            
            this.outputChannel.appendLine('Git initialisé');
        } catch (error) {
            this.outputChannel.appendLine(`Erreur lors de l'initialisation Git: ${error}`);
        }
    }

    private async openProject(projectPath: string): Promise<void> {
        const openChoice = await vscode.window.showInformationMessage(
            'Projet créé avec succès! Voulez-vous l\'ouvrir ?',
            'Ouvrir',
            'Ouvrir dans une nouvelle fenêtre',
            'Plus tard'
        );

        if (openChoice === 'Ouvrir') {
            await vscode.commands.executeCommand('vscode.openFolder', vscode.Uri.file(projectPath));
        } else if (openChoice === 'Ouvrir dans une nouvelle fenêtre') {
            await vscode.commands.executeCommand('vscode.openFolder', vscode.Uri.file(projectPath), true);
        }
    }

    // Templates de fichiers
    private getApiRoutesTemplate(): string {
        return `<?php

use {{NAMESPACE}}\Handlers\AuthHandler;
use Nexa\Routing\Router;

$router = new Router();

// Routes d'authentification
$router->post('/auth/login', [AuthHandler::class, 'login']);
$router->post('/auth/register', [AuthHandler::class, 'register']);
$router->post('/auth/logout', [AuthHandler::class, 'logout'])->middleware('auth');

// Routes protégées
$router->group(['middleware' => 'auth'], function($router) {
    $router->get('/user', [AuthHandler::class, 'user']);
});

return $router;
`;
    }

    private getWebRoutesTemplate(): string {
        return `<?php

use {{NAMESPACE}}\Handlers\HomeHandler;
use Nexa\Routing\Router;

$router = new Router();

$router->get('/', [HomeHandler::class, 'index']);

return $router;
`;
    }

    private getMicroserviceRoutesTemplate(): string {
        return `<?php

use {{NAMESPACE}}\Handlers\ServiceHandler;
use Nexa\Routing\Router;

$router = new Router();

$router->get('/health', [ServiceHandler::class, 'health']);
$router->get('/status', [ServiceHandler::class, 'status']);

return $router;
`;
    }

    private getAuthHandlerTemplate(): string {
        return `<?php

namespace {{NAMESPACE}}\Handlers;

use Nexa\Http\Request;
use Nexa\Http\Response;

class AuthHandler
{
    public function login(Request $request): Response
    {
        // Logique d'authentification
        return Response::json(['message' => 'Login endpoint']);
    }

    public function register(Request $request): Response
    {
        // Logique d'inscription
        return Response::json(['message' => 'Register endpoint']);
    }

    public function logout(Request $request): Response
    {
        // Logique de déconnexion
        return Response::json(['message' => 'Logout successful']);
    }

    public function user(Request $request): Response
    {
        // Retourner l'utilisateur connecté
        return Response::json(['user' => $request->user()]);
    }
}
`;
    }

    private getAuthMiddlewareTemplate(): string {
        return `<?php

namespace {{NAMESPACE}}\Middleware;

use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Middleware\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        // Vérifier l'authentification
        if (!$request->user()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
`;
    }

    private getDatabaseConfigTemplate(): string {
        return `<?php

return [
    'default' => env('DB_CONNECTION', '{{DATABASE_TYPE}}'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', '{{PROJECT_NAME}}'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        
        'postgresql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', '{{PROJECT_NAME}}'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('{{PROJECT_NAME}}.sqlite')),
        ],
    ],
];
`;
    }

    private getMicroserviceConfigTemplate(): string {
        return `<?php

return [
    'name' => '{{PROJECT_NAME}}',
    'version' => '1.0.0',
    'port' => env('SERVICE_PORT', 8000),
    'registry' => [
        'enabled' => env('SERVICE_REGISTRY_ENABLED', false),
        'url' => env('SERVICE_REGISTRY_URL', 'http://localhost:8500'),
    ],
];
`;
    }

    private getAppConfigTemplate(): string {
        return `<?php

return [
    'name' => env('APP_NAME', '{{PROJECT_NAME}}'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'fr',
    'key' => env('APP_KEY'),
];
`;
    }

    private getLayoutTemplate(): string {
        return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? '{{PROJECT_NAME}}' ?></title>
    <link href="/css/app.css" rel="stylesheet">
</head>
<body>
    <header>
        <h1>{{PROJECT_NAME}}</h1>
    </header>
    
    <main>
        <?= $content ?>
    </main>
    
    <footer>
        <p>&copy; 2024 {{PROJECT_NAME}}</p>
    </footer>
    
    <script src="/js/app.js"></script>
</body>
</html>
`;
    }

    private getHomeTemplate(): string {
        return `<div class="container">
    <h2>Bienvenue sur {{PROJECT_NAME}}</h2>
    <p>Votre application Nexa est prête!</p>
    
    <div class="features">
        <h3>Fonctionnalités incluses:</h3>
        <ul>
            <li>Routing</li>
            <li>Middleware</li>
            <li>Base de données</li>
            <li>Authentification</li>
        </ul>
    </div>
</div>
`;
    }

    public dispose(): void {
        this.outputChannel.dispose();
    }
}