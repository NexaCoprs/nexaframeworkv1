import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';

export class SnippetGenerator {
    private workspaceRoot: string;

    constructor() {
        this.workspaceRoot = vscode.workspace.workspaceFolders?.[0]?.uri.fsPath || '';
    }

    async generateIntelligentSnippet(): Promise<void> {
        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showErrorMessage('Aucun éditeur actif trouvé');
            return;
        }

        const document = editor.document;
        const selection = editor.selection;
        const selectedText = document.getText(selection);

        // Analyser le contexte du projet
        const projectContext = await this.analyzeProjectContext();
        
        // Générer le snippet basé sur le contexte
        const snippetType = await this.detectSnippetType(document, selection);
        const generatedSnippet = await this.generateContextualSnippet(snippetType, projectContext, selectedText);

        if (generatedSnippet) {
            await editor.edit((editBuilder: vscode.TextEditorEdit) => {
                if (selection.isEmpty) {
                    editBuilder.insert(selection.start, generatedSnippet);
                } else {
                    editBuilder.replace(selection, generatedSnippet);
                }
            });

            vscode.window.showInformationMessage(`Snippet ${snippetType} généré avec succès!`);
        }
    }

    private async analyzeProjectContext(): Promise<{hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}> {
        const context = {
            hasHandlers: false,
            hasEntities: false,
            hasMiddleware: false,
            hasWebSockets: false,
            hasGraphQL: false,
            hasMicroservices: false,
            framework: 'nexa'
        };

        try {
            // Vérifier la structure du projet
            const handlersPath = path.join(this.workspaceRoot, 'workspace', 'handlers');
            const entitiesPath = path.join(this.workspaceRoot, 'workspace', 'entities');
            const middlewarePath = path.join(this.workspaceRoot, 'workspace', 'middleware');

            context.hasHandlers = fs.existsSync(handlersPath);
            context.hasEntities = fs.existsSync(entitiesPath);
            context.hasMiddleware = fs.existsSync(middlewarePath);

            // Vérifier les modules spécialisés
            const kernelPath = path.join(this.workspaceRoot, 'kernel');
            if (fs.existsSync(kernelPath)) {
                context.hasWebSockets = fs.existsSync(path.join(kernelPath, 'WebSockets'));
                context.hasGraphQL = fs.existsSync(path.join(kernelPath, 'GraphQL'));
                context.hasMicroservices = fs.existsSync(path.join(kernelPath, 'Microservices'));
            }
        } catch (error) {
            console.error('Erreur lors de l\'analyse du contexte:', error);
        }

        return context;
    }

    private async detectSnippetType(document: vscode.TextDocument, selection: vscode.Selection): Promise<string> {
        const line = document.lineAt(selection.start.line).text;
        const fileName = path.basename(document.fileName);

        // Détection basée sur le nom du fichier
        if (fileName.includes('Handler')) return 'handler';
        if (fileName.includes('Entity')) return 'entity';
        if (fileName.includes('Middleware')) return 'middleware';
        if (fileName.includes('WebSocket')) return 'websocket';
        if (fileName.includes('GraphQL')) return 'graphql';
        if (fileName.includes('Test')) return 'test';

        // Détection basée sur le contenu
        if (line.includes('class') && line.includes('Handler')) return 'handler';
        if (line.includes('class') && line.includes('Entity')) return 'entity';
        if (line.includes('function') && line.includes('handle')) return 'middleware';
        if (line.includes('WebSocket')) return 'websocket';
        if (line.includes('GraphQL') || line.includes('Query') || line.includes('Mutation')) return 'graphql';
        if (line.includes('test') || line.includes('Test')) return 'test';

        // Par défaut
        return 'generic';
    }

    private async generateContextualSnippet(type: string, context: {hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}, selectedText: string): Promise<string> {
        const snippets: { [key: string]: string } = {
            handler: this.generateHandlerSnippet(context, selectedText),
            entity: this.generateEntitySnippet(context, selectedText),
            middleware: this.generateMiddlewareSnippet(context, selectedText),
            websocket: this.generateWebSocketSnippet(context, selectedText),
            graphql: this.generateGraphQLSnippet(context, selectedText),
            test: this.generateTestSnippet(context, selectedText),
            generic: this.generateGenericSnippet(context, selectedText)
        };

        return snippets[type] || snippets.generic;
    }

    private generateHandlerSnippet(context: {hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}, selectedText: string): string {
        const className = selectedText || 'NewHandler';
        return `<?php

namespace App\\Handlers;

use Nexa\\Core\\Handler;
use Nexa\\Http\\Request;
use Nexa\\Http\\Response;

class ${className} extends Handler
{
    public function handle(Request $request): Response
    {
        // TODO: Implémenter la logique du handler
        return $this->json([
            'message' => 'Handler ${className} exécuté avec succès',
            'data' => []
        ]);
    }

    public function validate(Request $request): array
    {
        return [
            // TODO: Définir les règles de validation
        ];
    }
}`;
    }

    private generateEntitySnippet(context: {hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}, selectedText: string): string {
        const className = selectedText || 'NewEntity';
        return `<?php

namespace App\\Entities;

use Nexa\\Core\\Entity;
use Nexa\\Database\\Attributes\\Table;
use Nexa\\Database\\Attributes\\Column;

#[Table('${className.toLowerCase()}s')]
class ${className} extends Entity
{
    #[Column(type: 'int', primary: true, autoIncrement: true)]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'datetime')]
    public \\DateTime $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \\DateTime();
    }

    // TODO: Ajouter les méthodes métier
}`;
    }

    private generateMiddlewareSnippet(context: {hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}, selectedText: string): string {
        const className = selectedText || 'NewMiddleware';
        return `<?php

namespace App\\Middleware;

use Nexa\\Core\\Middleware;
use Nexa\\Http\\Request;
use Nexa\\Http\\Response;
use Closure;

class ${className} extends Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // TODO: Logique avant traitement
        
        $response = $next($request);
        
        // TODO: Logique après traitement
        
        return $response;
    }
}`;
    }

    private generateWebSocketSnippet(context: {hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}, selectedText: string): string {
        const className = selectedText || 'NewWebSocketHandler';
        return `<?php

namespace App\\WebSockets;

use Nexa\\WebSockets\\WebSocketHandler;
use Nexa\\WebSockets\\Connection;
use Nexa\\WebSockets\\Message;

class ${className} extends WebSocketHandler
{
    public function onConnect(Connection $connection): void
    {
        // TODO: Logique de connexion
        $this->broadcast('user_connected', [
            'userId' => $connection->getUserId(),
            'timestamp' => time()
        ]);
    }

    public function onMessage(Connection $connection, Message $message): void
    {
        // TODO: Traitement des messages
        $data = $message->getData();
        
        $this->sendTo($connection, 'message_received', [
            'echo' => $data,
            'timestamp' => time()
        ]);
    }

    public function onDisconnect(Connection $connection): void
    {
        // TODO: Logique de déconnexion
        $this->broadcast('user_disconnected', [
            'userId' => $connection->getUserId(),
            'timestamp' => time()
        ]);
    }
}`;
    }

    private generateGraphQLSnippet(context: {hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}, selectedText: string): string {
        const className = selectedText || 'NewResolver';
        return `<?php

namespace App\\GraphQL\\Resolvers;

use Nexa\\GraphQL\\Resolver;
use Nexa\\GraphQL\\Context;
use Nexa\\GraphQL\\ResolveInfo;

class ${className} extends Resolver
{
    public function resolve($root, array $args, Context $context, ResolveInfo $info)
    {
        // TODO: Implémenter la résolution
        return [
            'message' => 'Resolver ${className} exécuté',
            'args' => $args,
            'timestamp' => time()
        ];
    }

    public function getType(): string
    {
        return '${className}Type';
    }

    public function getSchema(): array
    {
        return [
            // TODO: Définir le schéma GraphQL
        ];
    }
}`;
    }

    private generateTestSnippet(context: {hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}, selectedText: string): string {
        const className = selectedText || 'NewTest';
        return `<?php

namespace Tests;

use PHPUnit\\Framework\\TestCase;
use Nexa\\Testing\\NexaTestCase;

class ${className} extends NexaTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // TODO: Configuration du test
    }

    public function testExample(): void
    {
        // TODO: Implémenter le test
        $this->assertTrue(true);
    }

    public function tearDown(): void
    {
        // TODO: Nettoyage après test
        parent::tearDown();
    }
}`;
    }

    private generateGenericSnippet(context: {hasHandlers: boolean, hasEntities: boolean, hasMiddleware: boolean, hasWebSockets: boolean, hasGraphQL: boolean, hasMicroservices: boolean, framework: string}, selectedText: string): string {
        return `<?php

// TODO: Snippet générique généré automatiquement
// Contexte détecté: ${JSON.stringify(context, null, 2)}
// Texte sélectionné: ${selectedText}

// Ajoutez votre code ici`;
    }
}