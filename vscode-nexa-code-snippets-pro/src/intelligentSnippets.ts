import * as vscode from 'vscode';

export class IntelligentSnippets implements vscode.CompletionItemProvider {
    provideCompletionItems(
        document: vscode.TextDocument,
        position: vscode.Position,
        token: vscode.CancellationToken,
        context: vscode.CompletionContext
    ): vscode.ProviderResult<vscode.CompletionItem[] | vscode.CompletionList> {
        const linePrefix = document.lineAt(position).text.substr(0, position.character);
        
        // Détection du contexte
        if (linePrefix.endsWith('nexa:')) {
            return this.getNexaCompletions();
        }
        
        if (linePrefix.includes('class ') && linePrefix.includes('Handler')) {
            return this.getHandlerCompletions();
        }
        
        if (linePrefix.includes('class ') && linePrefix.includes('Entity')) {
            return this.getEntityCompletions();
        }
        
        if (linePrefix.includes('class ') && linePrefix.includes('Middleware')) {
            return this.getMiddlewareCompletions();
        }
        
        if (linePrefix.includes('WebSocket')) {
            return this.getWebSocketCompletions();
        }
        
        if (linePrefix.includes('GraphQL')) {
            return this.getGraphQLCompletions();
        }
        
        if (linePrefix.includes('test') || linePrefix.includes('Test')) {
            return this.getTestCompletions();
        }
        
        return this.getGenericCompletions();
    }

    private getNexaCompletions(): vscode.CompletionItem[] {
        const completions: vscode.CompletionItem[] = [];

        // Handler completion
        const handlerCompletion = new vscode.CompletionItem('handler', vscode.CompletionItemKind.Snippet);
        handlerCompletion.insertText = new vscode.SnippetString(
            'handler ${1:HandlerName} extends Handler\n{\n\tpublic function handle(Request $request): Response\n\t{\n\t\t${2:// TODO: Implémenter la logique}\n\t\treturn $this->json([\n\t\t\t\'success\' => true,\n\t\t\t\'data\' => ${3:[]}\n\t\t]);\n\t}\n}'
        );
        handlerCompletion.documentation = new vscode.MarkdownString('Crée un nouveau Handler Nexa');
        completions.push(handlerCompletion);

        // Entity completion
        const entityCompletion = new vscode.CompletionItem('entity', vscode.CompletionItemKind.Snippet);
        entityCompletion.insertText = new vscode.SnippetString(
            'entity ${1:EntityName} extends Entity\n{\n\t#[Column(type: \'int\', primary: true, autoIncrement: true)]\n\tpublic int $id;\n\n\t#[Column(type: \'string\', length: 255)]\n\tpublic string $${2:name};\n\n\t${3:// TODO: Ajouter plus de propriétés}\n}'
        );
        entityCompletion.documentation = new vscode.MarkdownString('Crée une nouvelle Entité Nexa');
        completions.push(entityCompletion);

        // Middleware completion
        const middlewareCompletion = new vscode.CompletionItem('middleware', vscode.CompletionItemKind.Snippet);
        middlewareCompletion.insertText = new vscode.SnippetString(
            'middleware ${1:MiddlewareName} extends Middleware\n{\n\tpublic function handle(Request $request, Closure $next): Response\n\t{\n\t\t${2:// Logique avant}\n\t\t\n\t\t$response = $next($request);\n\t\t\n\t\t${3:// Logique après}\n\t\t\n\t\treturn $response;\n\t}\n}'
        );
        middlewareCompletion.documentation = new vscode.MarkdownString('Crée un nouveau Middleware Nexa');
        completions.push(middlewareCompletion);

        return completions;
    }

    private getHandlerCompletions(): vscode.CompletionItem[] {
        const completions: vscode.CompletionItem[] = [];

        // Handle method
        const handleMethod = new vscode.CompletionItem('handle', vscode.CompletionItemKind.Method);
        handleMethod.insertText = new vscode.SnippetString(
            'public function handle(Request $request): Response\n{\n\t${1:// TODO: Implémenter la logique}\n\treturn $this->json([\n\t\t\'success\' => true,\n\t\t\'data\' => ${2:[]}\n\t]);\n}'
        );
        handleMethod.documentation = new vscode.MarkdownString('Méthode principale du Handler');
        completions.push(handleMethod);

        // Validation method
        const validateMethod = new vscode.CompletionItem('validate', vscode.CompletionItemKind.Method);
        validateMethod.insertText = new vscode.SnippetString(
            'protected function rules(): array\n{\n\treturn [\n\t\t${1:// TODO: Règles de validation}\n\t];\n}'
        );
        validateMethod.documentation = new vscode.MarkdownString('Méthode de validation du Handler');
        completions.push(validateMethod);

        // Response helpers
        const jsonResponse = new vscode.CompletionItem('json', vscode.CompletionItemKind.Method);
        jsonResponse.insertText = new vscode.SnippetString(
            'return $this->json([\n\t\'${1:key}\' => \'${2:value}\'\n]);'
        );
        jsonResponse.documentation = new vscode.MarkdownString('Retourne une réponse JSON');
        completions.push(jsonResponse);

        return completions;
    }

    private getEntityCompletions(): vscode.CompletionItem[] {
        const completions: vscode.CompletionItem[] = [];

        // Column attribute
        const columnAttr = new vscode.CompletionItem('column', vscode.CompletionItemKind.Property);
        columnAttr.insertText = new vscode.SnippetString(
            '#[Column(type: \'${1|string,int,float,boolean,datetime,text|}\', ${2:length: ${3:255}, }${4:nullable: ${5|true,false|}, })]\npublic ${6:string} $${7:propertyName};'
        );
        columnAttr.documentation = new vscode.MarkdownString('Attribut de colonne pour une propriété d\'entité');
        completions.push(columnAttr);

        // Primary key
        const primaryKey = new vscode.CompletionItem('primary', vscode.CompletionItemKind.Property);
        primaryKey.insertText = new vscode.SnippetString(
            '#[Column(type: \'int\', primary: true, autoIncrement: true)]\npublic int $id;'
        );
        primaryKey.documentation = new vscode.MarkdownString('Clé primaire auto-incrémentée');
        completions.push(primaryKey);

        // Timestamps
        const timestamps = new vscode.CompletionItem('timestamps', vscode.CompletionItemKind.Property);
        timestamps.insertText = new vscode.SnippetString(
            '#[Column(type: \'datetime\')]\npublic \\DateTime $createdAt;\n\n#[Column(type: \'datetime\', nullable: true)]\npublic ?\\DateTime $updatedAt = null;'
        );
        timestamps.documentation = new vscode.MarkdownString('Timestamps created_at et updated_at');
        completions.push(timestamps);

        return completions;
    }

    private getMiddlewareCompletions(): vscode.CompletionItem[] {
        const completions: vscode.CompletionItem[] = [];

        // Handle method
        const handleMethod = new vscode.CompletionItem('handle', vscode.CompletionItemKind.Method);
        handleMethod.insertText = new vscode.SnippetString(
            'public function handle(Request $request, Closure $next): Response\n{\n\t${1:// Logique avant traitement}\n\t\n\t$response = $next($request);\n\t\n\t${2:// Logique après traitement}\n\t\n\treturn $response;\n}'
        );
        handleMethod.documentation = new vscode.MarkdownString('Méthode principale du Middleware');
        completions.push(handleMethod);

        // Auth check
        const authCheck = new vscode.CompletionItem('auth-check', vscode.CompletionItemKind.Snippet);
        authCheck.insertText = new vscode.SnippetString(
            'if (!$request->user()) {\n\treturn $this->unauthorized(\'Authentication required\');\n}'
        );
        authCheck.documentation = new vscode.MarkdownString('Vérification d\'authentification');
        completions.push(authCheck);

        // Rate limiting
        const rateLimit = new vscode.CompletionItem('rate-limit', vscode.CompletionItemKind.Snippet);
        rateLimit.insertText = new vscode.SnippetString(
            'if ($this->rateLimiter->tooManyAttempts($request->ip())) {\n\treturn $this->tooManyRequests();\n}'
        );
        rateLimit.documentation = new vscode.MarkdownString('Limitation de taux');
        completions.push(rateLimit);

        return completions;
    }

    private getWebSocketCompletions(): vscode.CompletionItem[] {
        const completions: vscode.CompletionItem[] = [];

        // WebSocket handler
        const wsHandler = new vscode.CompletionItem('ws-handler', vscode.CompletionItemKind.Class);
        wsHandler.insertText = new vscode.SnippetString(
            'class ${1:WebSocketHandler} extends WebSocketHandler\n{\n\tpublic function onConnect(Connection $connection): void\n\t{\n\t\t${2:// Logique de connexion}\n\t}\n\n\tpublic function onMessage(Connection $connection, Message $message): void\n\t{\n\t\t${3:// Traitement des messages}\n\t}\n\n\tpublic function onDisconnect(Connection $connection): void\n\t{\n\t\t${4:// Logique de déconnexion}\n\t}\n}'
        );
        wsHandler.documentation = new vscode.MarkdownString('Handler WebSocket complet');
        completions.push(wsHandler);

        // Broadcast
        const broadcast = new vscode.CompletionItem('broadcast', vscode.CompletionItemKind.Method);
        broadcast.insertText = new vscode.SnippetString(
            '$this->broadcast(\'${1:event}\', [\n\t\'${2:key}\' => \'${3:value}\'\n]);'
        );
        broadcast.documentation = new vscode.MarkdownString('Diffuser un message à tous les clients');
        completions.push(broadcast);

        return completions;
    }

    private getGraphQLCompletions(): vscode.CompletionItem[] {
        const completions: vscode.CompletionItem[] = [];

        // Query resolver
        const queryResolver = new vscode.CompletionItem('query-resolver', vscode.CompletionItemKind.Class);
        queryResolver.insertText = new vscode.SnippetString(
            'class ${1:QueryName} extends Query\n{\n\tpublic function resolve($root, array $args, Context $context)\n\t{\n\t\t${2:// TODO: Implémenter la résolution}\n\t\treturn [];\n\t}\n\n\tpublic function type(): string\n\t{\n\t\treturn \'${3:ReturnType}\';\n\t}\n}'
        );
        queryResolver.documentation = new vscode.MarkdownString('Resolver GraphQL Query');
        completions.push(queryResolver);

        // Mutation resolver
        const mutationResolver = new vscode.CompletionItem('mutation-resolver', vscode.CompletionItemKind.Class);
        mutationResolver.insertText = new vscode.SnippetString(
            'class ${1:MutationName} extends Mutation\n{\n\tpublic function resolve($root, array $args, Context $context)\n\t{\n\t\t${2:// TODO: Implémenter la mutation}\n\t\treturn [];\n\t}\n\n\tpublic function type(): string\n\t{\n\t\treturn \'${3:ReturnType}\';\n\t}\n}'
        );
        mutationResolver.documentation = new vscode.MarkdownString('Resolver GraphQL Mutation');
        completions.push(mutationResolver);

        return completions;
    }

    private getTestCompletions(): vscode.CompletionItem[] {
        const completions: vscode.CompletionItem[] = [];

        // Test method
        const testMethod = new vscode.CompletionItem('test-method', vscode.CompletionItemKind.Method);
        testMethod.insertText = new vscode.SnippetString(
            'public function test${1:MethodName}(): void\n{\n\t${2:// Arrange}\n\t\n\t${3:// Act}\n\t\n\t${4:// Assert}\n\t$this->assertTrue(${5:true});\n}'
        );
        testMethod.documentation = new vscode.MarkdownString('Méthode de test avec structure AAA');
        completions.push(testMethod);

        // HTTP test
        const httpTest = new vscode.CompletionItem('http-test', vscode.CompletionItemKind.Snippet);
        httpTest.insertText = new vscode.SnippetString(
            '$response = $this->${1|get,post,put,delete|}(\'${2:/api/endpoint}\');\n\n$response->assertStatus(${3:200})\n\t->assertJson([\n\t\t\'${4:key}\' => \'${5:value}\'\n\t]);'
        );
        httpTest.documentation = new vscode.MarkdownString('Test de requête HTTP');
        completions.push(httpTest);

        return completions;
    }

    private getGenericCompletions(): vscode.CompletionItem[] {
        const completions: vscode.CompletionItem[] = [];

        // Nexa imports
        const nexaImports = new vscode.CompletionItem('nexa-imports', vscode.CompletionItemKind.Module);
        nexaImports.insertText = new vscode.SnippetString(
            'use Nexa\\Core\\${1|Handler,Entity,Middleware,Service|};\nuse Nexa\\Http\\{Request, Response};'
        );
        nexaImports.documentation = new vscode.MarkdownString('Imports Nexa courants');
        completions.push(nexaImports);

        // Logger
        const logger = new vscode.CompletionItem('log', vscode.CompletionItemKind.Function);
        logger.insertText = new vscode.SnippetString(
            'logger(\'${1:message}\', [\n\t\'${2:key}\' => \'${3:value}\'\n]);'
        );
        logger.documentation = new vscode.MarkdownString('Log avec contexte');
        completions.push(logger);

        // Try-catch
        const tryCatch = new vscode.CompletionItem('try-catch', vscode.CompletionItemKind.Snippet);
        tryCatch.insertText = new vscode.SnippetString(
            'try {\n\t${1:// Code à exécuter}\n} catch (\\Exception $e) {\n\tlogger(\'Error: \' . $e->getMessage());\n\t${2:// Gestion d\'erreur}\n}'
        );
        tryCatch.documentation = new vscode.MarkdownString('Bloc try-catch avec logging');
        completions.push(tryCatch);

        return completions;
    }
}