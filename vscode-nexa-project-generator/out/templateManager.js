"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.TemplateManager = void 0;
const vscode = require("vscode");
const fs = require("fs");
const path = require("path");
class TemplateManager {
    constructor() {
        this.templates = new Map();
        this.initializeTemplates();
    }
    initializeTemplates() {
        // Template API REST
        this.templates.set('api-rest', {
            name: 'API REST',
            description: 'Template pour une API REST complète avec Nexa',
            type: 'api',
            files: [
                {
                    path: 'workspace/handlers',
                    content: '',
                    isDirectory: true
                },
                {
                    path: 'workspace/handlers/UserHandler.php',
                    content: this.getUserHandlerTemplate()
                },
                {
                    path: 'workspace/interface/User.php',
                    content: this.getUserEntityTemplate()
                },
                {
                    path: 'workspace/config/routes.php',
                    content: this.getRoutesTemplate()
                }
            ],
            dependencies: [],
            devDependencies: []
        });
        // Template Microservice
        this.templates.set('microservice', {
            name: 'Microservice',
            description: 'Template pour un microservice Nexa',
            type: 'microservice',
            files: [
                {
                    path: 'workspace/handlers',
                    content: '',
                    isDirectory: true
                },
                {
                    path: 'workspace/handlers/ServiceHandler.php',
                    content: this.getMicroserviceHandlerTemplate()
                },
                {
                    path: 'workspace/config/microservice.php',
                    content: this.getMicroserviceConfigTemplate()
                }
            ],
            dependencies: [],
            devDependencies: []
        });
        // Template WebSocket
        this.templates.set('websocket', {
            name: 'WebSocket Server',
            description: 'Template pour un serveur WebSocket avec Nexa',
            type: 'websocket',
            files: [
                {
                    path: 'workspace/handlers',
                    content: '',
                    isDirectory: true
                },
                {
                    path: 'workspace/handlers/WebSocketHandler.php',
                    content: this.getWebSocketHandlerTemplate()
                },
                {
                    path: 'workspace/config/websocket.php',
                    content: this.getWebSocketConfigTemplate()
                }
            ],
            dependencies: [],
            devDependencies: []
        });
        // Template GraphQL
        this.templates.set('graphql', {
            name: 'GraphQL API',
            description: 'Template pour une API GraphQL avec Nexa',
            type: 'graphql',
            files: [
                {
                    path: 'workspace/handlers',
                    content: '',
                    isDirectory: true
                },
                {
                    path: 'workspace/handlers/GraphQLHandler.php',
                    content: this.getGraphQLHandlerTemplate()
                },
                {
                    path: 'workspace/config/graphql.php',
                    content: this.getGraphQLConfigTemplate()
                },
                {
                    path: 'workspace/schema/schema.graphql',
                    content: this.getGraphQLSchemaTemplate()
                }
            ],
            dependencies: [],
            devDependencies: []
        });
    }
    getAvailableTemplates() {
        return Array.from(this.templates.values());
    }
    getTemplate(name) {
        return this.templates.get(name);
    }
    async applyTemplate(templateName, targetPath) {
        const template = this.getTemplate(templateName);
        if (!template) {
            throw new Error(`Template '${templateName}' non trouvé`);
        }
        for (const file of template.files) {
            const fullPath = path.join(targetPath, file.path);
            if (file.isDirectory) {
                await this.ensureDirectoryExists(fullPath);
            }
            else {
                await this.ensureDirectoryExists(path.dirname(fullPath));
                await fs.promises.writeFile(fullPath, file.content, 'utf8');
            }
        }
        vscode.window.showInformationMessage(`Template '${template.name}' appliqué avec succès`);
    }
    async ensureDirectoryExists(dirPath) {
        try {
            await fs.promises.access(dirPath);
        }
        catch {
            await fs.promises.mkdir(dirPath, { recursive: true });
        }
    }
    getUserHandlerTemplate() {
        return `<?php

namespace App\\Handlers;

use Nexa\\Http\\Request;
use Nexa\\Http\\Response;
use Nexa\\Attributes\\Route;
use Nexa\\Attributes\\Middleware;

class UserHandler
{
    #[Route('GET', '/api/users')]
    #[Middleware('auth')]
    public function index(Request $request): Response
    {
        // Logique pour récupérer tous les utilisateurs
        $users = [];
        
        return Response::json($users);
    }

    #[Route('GET', '/api/users/{id}')]
    #[Middleware('auth')]
    public function show(Request $request, int $id): Response
    {
        // Logique pour récupérer un utilisateur spécifique
        $user = null;
        
        if (!$user) {
            return Response::json(['error' => 'Utilisateur non trouvé'], 404);
        }
        
        return Response::json($user);
    }

    #[Route('POST', '/api/users')]
    #[Middleware('auth')]
    public function store(Request $request): Response
    {
        // Logique pour créer un nouvel utilisateur
        $data = $request->json();
        
        // Validation et création
        
        return Response::json(['message' => 'Utilisateur créé'], 201);
    }

    #[Route('PUT', '/api/users/{id}')]
    #[Middleware('auth')]
    public function update(Request $request, int $id): Response
    {
        // Logique pour mettre à jour un utilisateur
        $data = $request->json();
        
        return Response::json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('DELETE', '/api/users/{id}')]
    #[Middleware('auth')]
    public function destroy(Request $request, int $id): Response
    {
        // Logique pour supprimer un utilisateur
        
        return Response::json(['message' => 'Utilisateur supprimé']);
    }
}
`;
    }
    getUserEntityTemplate() {
        return `<?php

namespace App\\Entities;

use Nexa\\Database\\Entity;
use Nexa\\Attributes\\Table;
use Nexa\\Attributes\\Column;
use Nexa\\Attributes\\PrimaryKey;

#[Table('users')]
class User extends Entity
{
    #[PrimaryKey]
    #[Column('id', 'integer', autoIncrement: true)]
    public int $id;

    #[Column('name', 'string', length: 255)]
    public string $name;

    #[Column('email', 'string', length: 255, unique: true)]
    public string $email;

    #[Column('password', 'string', length: 255)]
    public string $password;

    #[Column('created_at', 'datetime')]
    public \\DateTime $createdAt;

    #[Column('updated_at', 'datetime')]
    public \\DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \\DateTime();
        $this->updatedAt = new \\DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}
`;
    }
    getRoutesTemplate() {
        return `<?php

use Nexa\\Routing\\Router;
use App\\Handlers\\UserHandler;

// Routes API
Router::group('/api', function() {
    // Routes utilisateurs
    Router::get('/users', [UserHandler::class, 'index']);
    Router::get('/users/{id}', [UserHandler::class, 'show']);
    Router::post('/users', [UserHandler::class, 'store']);
    Router::put('/users/{id}', [UserHandler::class, 'update']);
    Router::delete('/users/{id}', [UserHandler::class, 'destroy']);
});

// Route de base
Router::get('/', function() {
    return 'Bienvenue sur votre API Nexa!';
});
`;
    }
    getMicroserviceHandlerTemplate() {
        return `<?php

namespace App\\Handlers;

use Nexa\\Http\\Request;
use Nexa\\Http\\Response;
use Nexa\\Attributes\\Route;
use Nexa\\Microservices\\ServiceClient;

class ServiceHandler
{
    private ServiceClient $serviceClient;

    public function __construct(ServiceClient $serviceClient)
    {
        $this->serviceClient = $serviceClient;
    }

    #[Route('GET', '/api/service/health')]
    public function health(Request $request): Response
    {
        return Response::json([
            'status' => 'healthy',
            'service' => 'nexa-microservice',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('POST', '/api/service/process')]
    public function process(Request $request): Response
    {
        $data = $request->json();
        
        // Logique de traitement du microservice
        $result = $this->processData($data);
        
        return Response::json($result);
    }

    private function processData(array $data): array
    {
        // Implémentez votre logique métier ici
        return [
            'processed' => true,
            'data' => $data,
            'timestamp' => time()
        ];
    }
}
`;
    }
    getMicroserviceConfigTemplate() {
        return `<?php

return [
    'service_name' => 'nexa-microservice',
    'service_port' => 8080,
    'registry' => [
        'enabled' => true,
        'url' => 'http://localhost:8500',
        'health_check_interval' => 30
    ],
    'communication' => [
        'protocol' => 'http',
        'timeout' => 30,
        'retry_attempts' => 3
    ]
];
`;
    }
    getWebSocketHandlerTemplate() {
        return `<?php

namespace App\\Handlers;

use Nexa\\WebSockets\\WebSocketHandler as BaseHandler;
use Nexa\\WebSockets\\Connection;
use Nexa\\WebSockets\\Message;

class WebSocketHandler extends BaseHandler
{
    public function onOpen(Connection $connection): void
    {
        echo "Nouvelle connexion: {$connection->getId()}\\n";
        
        // Envoyer un message de bienvenue
        $connection->send(json_encode([
            'type' => 'welcome',
            'message' => 'Connexion établie avec succès'
        ]));
    }

    public function onMessage(Connection $connection, Message $message): void
    {
        $data = json_decode($message->getPayload(), true);
        
        switch ($data['type'] ?? '') {
            case 'ping':
                $connection->send(json_encode(['type' => 'pong']));
                break;
                
            case 'broadcast':
                $this->broadcast($data['message'] ?? '');
                break;
                
            default:
                $connection->send(json_encode([
                    'type' => 'error',
                    'message' => 'Type de message non reconnu'
                ]));
        }
    }

    public function onClose(Connection $connection): void
    {
        echo "Connexion fermée: {$connection->getId()}\\n";
    }

    public function onError(Connection $connection, \\Exception $exception): void
    {
        echo "Erreur sur connexion {$connection->getId()}: {$exception->getMessage()}\\n";
    }

    private function broadcast(string $message): void
    {
        $this->getServer()->broadcast(json_encode([
            'type' => 'broadcast',
            'message' => $message,
            'timestamp' => time()
        ]));
    }
}
`;
    }
    getWebSocketConfigTemplate() {
        return `<?php

return [
    'host' => '0.0.0.0',
    'port' => 8080,
    'max_connections' => 1000,
    'heartbeat_interval' => 30,
    'ssl' => [
        'enabled' => false,
        'cert_file' => '',
        'key_file' => ''
    ],
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_headers' => ['*']
    ]
];
`;
    }
    getGraphQLHandlerTemplate() {
        return `<?php

namespace App\\Handlers;

use Nexa\\Http\\Request;
use Nexa\\Http\\Response;
use Nexa\\Attributes\\Route;
use Nexa\\GraphQL\\GraphQLManager;

class GraphQLHandler
{
    private GraphQLManager $graphql;

    public function __construct(GraphQLManager $graphql)
    {
        $this->graphql = $graphql;
    }

    #[Route('POST', '/graphql')]
    public function handle(Request $request): Response
    {
        $input = $request->json();
        
        $query = $input['query'] ?? '';
        $variables = $input['variables'] ?? [];
        $operationName = $input['operationName'] ?? null;
        
        try {
            $result = $this->graphql->executeQuery($query, $variables, $operationName);
            return Response::json($result);
        } catch (\\Exception $e) {
            return Response::json([
                'errors' => [[
                    'message' => $e->getMessage()
                ]]
            ], 400);
        }
    }

    #[Route('GET', '/graphql')]
    public function playground(Request $request): Response
    {
        // Interface GraphQL Playground
        $html = $this->getPlaygroundHTML();
        return Response::html($html);
    }

    private function getPlaygroundHTML(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>GraphQL Playground</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/graphql-playground-react/build/static/css/index.css" />
</head>
<body>
    <div id="root"></div>
    <script src="https://cdn.jsdelivr.net/npm/graphql-playground-react/build/static/js/middleware.js"></script>
</body>
</html>';
    }
}
`;
    }
    getGraphQLConfigTemplate() {
        return `<?php

return [
    'schema_path' => 'workspace/schema/schema.graphql',
    'resolvers_path' => 'workspace/resolvers',
    'playground' => [
        'enabled' => true,
        'endpoint' => '/graphql'
    ],
    'introspection' => true,
    'debug' => true
];
`;
    }
    getGraphQLSchemaTemplate() {
        return `type Query {
    users: [User!]!
    user(id: ID!): User
}

type Mutation {
    createUser(input: CreateUserInput!): User!
    updateUser(id: ID!, input: UpdateUserInput!): User!
    deleteUser(id: ID!): Boolean!
}

type User {
    id: ID!
    name: String!
    email: String!
    createdAt: String!
    updatedAt: String!
}

input CreateUserInput {
    name: String!
    email: String!
    password: String!
}

input UpdateUserInput {
    name: String
    email: String
    password: String
}
`;
    }
}
exports.TemplateManager = TemplateManager;
//# sourceMappingURL=templateManager.js.map