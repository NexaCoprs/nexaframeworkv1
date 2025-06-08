import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class QueryTester {
    private panel: vscode.WebviewPanel | undefined;
    private context: vscode.ExtensionContext;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
    }

    async openQueryTester() {
        if (this.panel) {
            this.panel.reveal();
            return;
        }

        this.panel = vscode.window.createWebviewPanel(
            'nexaGraphQLTester',
            'Nexa GraphQL Query Tester',
            vscode.ViewColumn.Two,
            {
                enableScripts: true,
                retainContextWhenHidden: true,
                localResourceRoots: [this.context.extensionUri]
            }
        );

        this.panel.webview.html = await this.getWebviewContent();

        this.panel.webview.onDidReceiveMessage(
            async (message) => {
                switch (message.command) {
                    case 'executeQuery':
                        const result = await this.executeQuery(message.query, message.variables, message.endpoint);
                        this.panel?.webview.postMessage({
                            command: 'queryResult',
                            result: result
                        });
                        break;
                    case 'saveQuery':
                        await this.saveQuery(message.name, message.query, message.variables);
                        break;
                    case 'loadQuery':
                        const queryData = await this.loadQuery(message.name);
                        this.panel?.webview.postMessage({
                            command: 'queryLoaded',
                            data: queryData
                        });
                        break;
                    case 'getSavedQueries':
                        const queries = await this.getSavedQueries();
                        this.panel?.webview.postMessage({
                            command: 'savedQueries',
                            queries: queries
                        });
                        break;
                    case 'introspectEndpoint':
                        const schema = await this.introspectEndpoint(message.endpoint);
                        this.panel?.webview.postMessage({
                            command: 'introspectionResult',
                            schema: schema
                        });
                        break;
                }
            },
            undefined,
            this.context.subscriptions
        );

        this.panel.onDidDispose(() => {
            this.panel = undefined;
        });

        // Charger les requêtes sauvegardées au démarrage
        const savedQueries = await this.getSavedQueries();
        this.panel.webview.postMessage({
            command: 'savedQueries',
            queries: savedQueries
        });
    }

    private async getWebviewContent(): Promise<string> {
        const config = vscode.workspace.getConfiguration('nexa.graphql');
        const defaultEndpoint = config.get<string>('endpoint', 'http://localhost:8000/graphql');
        
        return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexa GraphQL Query Tester</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--vscode-editor-background);
            color: var(--vscode-editor-foreground);
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--vscode-panel-border);
        }
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
        }
        .left-panel {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .panel {
            border: 1px solid var(--vscode-panel-border);
            border-radius: 4px;
            overflow: hidden;
        }
        .panel-header {
            background: var(--vscode-panel-background);
            padding: 10px;
            border-bottom: 1px solid var(--vscode-panel-border);
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .panel-content {
            padding: 0;
            height: 100%;
        }
        .editor {
            width: 100%;
            height: 100%;
            border: none;
            background: var(--vscode-editor-background);
            color: var(--vscode-editor-foreground);
            font-family: 'Courier New', monospace;
            padding: 10px;
            resize: none;
            font-size: 14px;
        }
        .btn {
            padding: 8px 16px;
            background: var(--vscode-button-background);
            color: var(--vscode-button-foreground);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: var(--vscode-button-hoverBackground);
        }
        .btn-secondary {
            background: var(--vscode-button-secondaryBackground);
            color: var(--vscode-button-secondaryForeground);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .input {
            padding: 6px 12px;
            background: var(--vscode-input-background);
            color: var(--vscode-input-foreground);
            border: 1px solid var(--vscode-input-border);
            border-radius: 4px;
            font-size: 14px;
        }
        .select {
            padding: 6px 12px;
            background: var(--vscode-input-background);
            color: var(--vscode-input-foreground);
            border: 1px solid var(--vscode-input-border);
            border-radius: 4px;
            font-size: 14px;
        }
        .result-panel {
            flex: 1;
            min-height: 300px;
        }
        .variables-panel {
            height: 150px;
        }
        .query-panel {
            flex: 1;
            min-height: 300px;
        }
        .saved-queries {
            height: 200px;
            overflow-y: auto;
        }
        .query-item {
            padding: 8px;
            border-bottom: 1px solid var(--vscode-panel-border);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .query-item:hover {
            background: var(--vscode-list-hoverBackground);
        }
        .query-item:last-child {
            border-bottom: none;
        }
        .status-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 10px;
            background: var(--vscode-statusBar-background);
            color: var(--vscode-statusBar-foreground);
            font-size: 12px;
            border-top: 1px solid var(--vscode-panel-border);
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .error {
            color: var(--vscode-errorForeground);
        }
        .success {
            color: var(--vscode-testing-iconPassed);
        }
        .tabs {
            display: flex;
            background: var(--vscode-tab-inactiveBackground);
        }
        .tab {
            padding: 8px 16px;
            background: var(--vscode-tab-inactiveBackground);
            color: var(--vscode-tab-inactiveForeground);
            border: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab.active {
            background: var(--vscode-tab-activeBackground);
            color: var(--vscode-tab-activeForeground);
            border-bottom-color: var(--vscode-tab-activeBorder);
        }
        .tab-content {
            display: none;
            height: calc(100% - 40px);
        }
        .tab-content.active {
            display: block;
        }
        .headers-panel {
            height: 120px;
        }
        .endpoint-config {
            display: flex;
            gap: 10px;
            align-items: center;
            flex: 1;
        }
        .response-time {
            font-size: 12px;
            color: var(--vscode-descriptionForeground);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Nexa GraphQL Query Tester</h1>
            <div class="endpoint-config">
                <label>Endpoint:</label>
                <input type="text" class="input" id="endpoint" value="${defaultEndpoint}" style="flex: 1;">
                <button class="btn btn-secondary" onclick="introspectEndpoint()">🔍 Introspecter</button>
            </div>
        </div>

        <div class="toolbar">
            <button class="btn btn-success" onclick="executeQuery()" id="executeBtn">▶️ Exécuter</button>
            <button class="btn btn-secondary" onclick="formatQuery()">🎨 Formater</button>
            <button class="btn btn-secondary" onclick="saveQuery()">💾 Sauvegarder</button>
            <input type="text" class="input" id="queryName" placeholder="Nom de la requête" style="width: 200px;">
            <select class="select" id="savedQueries" onchange="loadSelectedQuery()">
                <option value="">Requêtes sauvegardées</option>
            </select>
            <span class="response-time" id="responseTime"></span>
        </div>

        <div class="main-content">
            <div class="left-panel">
                <div class="panel query-panel">
                    <div class="panel-header">
                        <span>Requête GraphQL</span>
                        <div>
                            <button class="btn btn-secondary" onclick="insertExample('query')">Query</button>
                            <button class="btn btn-secondary" onclick="insertExample('mutation')">Mutation</button>
                            <button class="btn btn-secondary" onclick="insertExample('subscription')">Subscription</button>
                        </div>
                    </div>
                    <div class="panel-content">
                        <textarea class="editor" id="queryEditor" placeholder="# Entrez votre requête GraphQL ici\nquery {\n  users {\n    id\n    name\n    email\n  }\n}">query GetUsers {\n  users {\n    id\n    name\n    email\n    createdAt\n  }\n}</textarea>
                    </div>
                </div>

                <div class="panel variables-panel">
                    <div class="tabs">
                        <button class="tab active" onclick="showTab('variables')">Variables</button>
                        <button class="tab" onclick="showTab('headers')">Headers</button>
                    </div>
                    <div id="variables" class="tab-content active">
                        <textarea class="editor" id="variablesEditor" placeholder="{\n  \"id\": 1,\n  \"name\": \"John Doe\"\n}"></textarea>
                    </div>
                    <div id="headers" class="tab-content headers-panel">
                        <textarea class="editor" id="headersEditor" placeholder="{\n  \"Authorization\": \"Bearer your-token\",\n  \"Content-Type\": \"application/json\"\n}"></textarea>
                    </div>
                </div>
            </div>

            <div class="right-panel">
                <div class="panel result-panel">
                    <div class="panel-header">
                        <span>Résultat</span>
                        <div>
                            <button class="btn btn-secondary" onclick="copyResult()">📋 Copier</button>
                            <button class="btn btn-secondary" onclick="exportResult()">📤 Exporter</button>
                        </div>
                    </div>
                    <div class="panel-content">
                        <textarea class="editor" id="resultEditor" readonly placeholder="Le résultat de votre requête apparaîtra ici..."></textarea>
                    </div>
                    <div class="status-bar">
                        <span id="statusText">Prêt</span>
                        <span id="resultStats"></span>
                    </div>
                </div>

                <div class="panel saved-queries">
                    <div class="panel-header">
                        <span>Requêtes sauvegardées</span>
                        <button class="btn btn-secondary" onclick="refreshSavedQueries()">🔄</button>
                    </div>
                    <div class="panel-content">
                        <div id="savedQueriesList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const vscode = acquireVsCodeApi();
        let isExecuting = false;
        let savedQueriesData = [];

        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }

        function executeQuery() {
            if (isExecuting) return;
            
            const query = document.getElementById('queryEditor').value;
            const variables = document.getElementById('variablesEditor').value;
            const endpoint = document.getElementById('endpoint').value;
            
            if (!query.trim()) {
                alert('Veuillez entrer une requête GraphQL');
                return;
            }
            
            if (!endpoint.trim()) {
                alert('Veuillez spécifier un endpoint');
                return;
            }
            
            isExecuting = true;
            document.getElementById('executeBtn').textContent = '⏳ Exécution...';
            document.getElementById('statusText').textContent = 'Exécution en cours...';
            document.getElementById('responseTime').textContent = '';
            
            const startTime = Date.now();
            
            vscode.postMessage({
                command: 'executeQuery',
                query: query,
                variables: variables,
                endpoint: endpoint
            });
        }

        function formatQuery() {
            const editor = document.getElementById('queryEditor');
            const query = editor.value;
            
            // Formatage simple de la requête GraphQL
            const formatted = query
                .replace(/\{/g, ' {\n  ')
                .replace(/\}/g, '\n}')
                .replace(/,/g, ',\n  ')
                .replace(/\n\s*\n/g, '\n')
                .replace(/^\s+/gm, (match) => '  '.repeat(match.length / 2));
            
            editor.value = formatted;
        }

        function saveQuery() {
            const name = document.getElementById('queryName').value;
            const query = document.getElementById('queryEditor').value;
            const variables = document.getElementById('variablesEditor').value;
            
            if (!name.trim()) {
                alert('Veuillez entrer un nom pour la requête');
                return;
            }
            
            if (!query.trim()) {
                alert('Veuillez entrer une requête GraphQL');
                return;
            }
            
            vscode.postMessage({
                command: 'saveQuery',
                name: name,
                query: query,
                variables: variables
            });
        }

        function loadSelectedQuery() {
            const select = document.getElementById('savedQueries');
            const queryName = select.value;
            
            if (queryName) {
                vscode.postMessage({
                    command: 'loadQuery',
                    name: queryName
                });
            }
        }

        function refreshSavedQueries() {
            vscode.postMessage({
                command: 'getSavedQueries'
            });
        }

        function introspectEndpoint() {
            const endpoint = document.getElementById('endpoint').value;
            
            if (!endpoint.trim()) {
                alert('Veuillez spécifier un endpoint');
                return;
            }
            
            vscode.postMessage({
                command: 'introspectEndpoint',
                endpoint: endpoint
            });
        }

        function insertExample(type) {
            const editor = document.getElementById('queryEditor');
            let example = '';
            
            switch (type) {
                case 'query':
                    example = 'query GetUsers {\n  users {\n    id\n    name\n    email\n  }\n}';
                    break;
                case 'mutation':
                    example = 'mutation CreateUser($input: UserInput!) {\n  createUser(input: $input) {\n    id\n    name\n    email\n  }\n}';
                    break;
                case 'subscription':
                    example = 'subscription UserUpdates {\n  userUpdated {\n    id\n    name\n    email\n  }\n}';
                    break;
            }
            
            editor.value = example;
        }

        function copyResult() {
            const result = document.getElementById('resultEditor').value;
            navigator.clipboard.writeText(result).then(() => {
                document.getElementById('statusText').textContent = 'Résultat copié!';
                setTimeout(() => {
                    document.getElementById('statusText').textContent = 'Prêt';
                }, 2000);
            });
        }

        function exportResult() {
            const result = document.getElementById('resultEditor').value;
            if (result) {
                const blob = new Blob([result], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'graphql-result.json';
                a.click();
            }
        }

        function loadQueryFromList(queryName) {
            vscode.postMessage({
                command: 'loadQuery',
                name: queryName
            });
        }

        function deleteQuery(queryName) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette requête ?')) {
                // Implémenter la suppression
                refreshSavedQueries();
            }
        }

        function updateSavedQueriesList(queries) {
            const list = document.getElementById('savedQueriesList');
            const select = document.getElementById('savedQueries');
            
            // Mettre à jour la liste
            list.innerHTML = '';
            select.innerHTML = '<option value="">Requêtes sauvegardées</option>';
            
            queries.forEach(query => {
                // Ajouter à la liste
                const item = document.createElement('div');
                item.className = 'query-item';
                item.innerHTML = \`
                    <span onclick="loadQueryFromList('\${query.name}')" style="flex: 1; cursor: pointer;">\${query.name}</span>
                    <button class="btn btn-danger" onclick="deleteQuery('\${query.name}')" style="padding: 2px 6px; font-size: 12px;">🗑️</button>
                \`;
                list.appendChild(item);
                
                // Ajouter au select
                const option = document.createElement('option');
                option.value = query.name;
                option.textContent = query.name;
                select.appendChild(option);
            });
            
            savedQueriesData = queries;
        }

        // Écouter les messages de l'extension
        window.addEventListener('message', event => {
            const message = event.data;
            
            switch (message.command) {
                case 'queryResult':
                    isExecuting = false;
                    document.getElementById('executeBtn').textContent = '▶️ Exécuter';
                    
                    const result = message.result;
                    const resultEditor = document.getElementById('resultEditor');
                    
                    if (result.success) {
                        resultEditor.value = JSON.stringify(result.data, null, 2);
                        document.getElementById('statusText').textContent = 'Succès';
                        document.getElementById('statusText').className = 'success';
                        
                        // Afficher les statistiques
                        const stats = \`Temps: \${result.responseTime}ms | Taille: \${JSON.stringify(result.data).length} caractères\`;
                        document.getElementById('resultStats').textContent = stats;
                        document.getElementById('responseTime').textContent = \`\${result.responseTime}ms\`;
                    } else {
                        resultEditor.value = JSON.stringify(result.error, null, 2);
                        document.getElementById('statusText').textContent = 'Erreur';
                        document.getElementById('statusText').className = 'error';
                        document.getElementById('resultStats').textContent = '';
                    }
                    break;
                    
                case 'queryLoaded':
                    const data = message.data;
                    document.getElementById('queryEditor').value = data.query;
                    document.getElementById('variablesEditor').value = data.variables || '';
                    document.getElementById('queryName').value = data.name;
                    document.getElementById('statusText').textContent = \`Requête '\${data.name}' chargée\`;
                    break;
                    
                case 'savedQueries':
                    updateSavedQueriesList(message.queries);
                    break;
                    
                case 'introspectionResult':
                    const schema = message.schema;
                    if (schema) {
                        document.getElementById('statusText').textContent = 'Introspection réussie';
                        // Vous pouvez afficher le schéma dans un modal ou un panneau séparé
                    } else {
                        document.getElementById('statusText').textContent = 'Échec de l\'introspection';
                        document.getElementById('statusText').className = 'error';
                    }
                    break;
            }
        });

        // Raccourcis clavier
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                executeQuery();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveQuery();
            }
        });

        // Charger les requêtes sauvegardées au démarrage
        refreshSavedQueries();
    </script>
</body>
</html>`;
    }

    private async executeQuery(query: string, variables: string, endpoint: string): Promise<any> {
        const startTime = Date.now();
        
        try {
            // Valider les variables JSON
            let parsedVariables = {};
            if (variables.trim()) {
                try {
                    parsedVariables = JSON.parse(variables);
                } catch (error) {
                    return {
                        success: false,
                        error: { message: 'Variables JSON invalides', details: error },
                        responseTime: Date.now() - startTime
                    };
                }
            }

            // Préparer la requête
            const requestBody = {
                query: query,
                variables: parsedVariables
            };

            // Configuration des headers
            const config = vscode.workspace.getConfiguration('nexa.graphql');
            const headers: any = {
                'Content-Type': 'application/json'
            };

            // Ajouter des headers personnalisés si configurés
            const customHeaders = config.get<any>('headers', {});
            Object.assign(headers, customHeaders);

            // Simuler une requête HTTP (dans un vrai environnement, vous utiliseriez fetch ou axios)
            // Pour cette démo, nous simulons une réponse
            const mockResponse = await this.simulateGraphQLRequest(requestBody, endpoint);
            
            const responseTime = Date.now() - startTime;
            
            return {
                success: true,
                data: mockResponse,
                responseTime: responseTime
            };
            
        } catch (error) {
            return {
                success: false,
                error: { message: 'Erreur de requête', details: error },
                responseTime: Date.now() - startTime
            };
        }
    }

    private async simulateGraphQLRequest(requestBody: any, endpoint: string): Promise<any> {
        // Simulation d'une réponse GraphQL
        // Dans un vrai environnement, vous feriez un appel HTTP réel
        
        await new Promise(resolve => setTimeout(resolve, 100 + Math.random() * 500)); // Simuler la latence
        
        if (requestBody.query.includes('users')) {
            return {
                data: {
                    users: [
                        { id: 1, name: 'John Doe', email: 'john@example.com', createdAt: '2023-01-01T00:00:00Z' },
                        { id: 2, name: 'Jane Smith', email: 'jane@example.com', createdAt: '2023-01-02T00:00:00Z' },
                        { id: 3, name: 'Bob Johnson', email: 'bob@example.com', createdAt: '2023-01-03T00:00:00Z' }
                    ]
                }
            };
        } else if (requestBody.query.includes('createUser')) {
            return {
                data: {
                    createUser: {
                        id: 4,
                        name: requestBody.variables.input?.name || 'Nouvel utilisateur',
                        email: requestBody.variables.input?.email || 'nouveau@example.com'
                    }
                }
            };
        } else {
            return {
                data: {
                    message: 'Requête exécutée avec succès (simulation)'
                }
            };
        }
    }

    private async saveQuery(name: string, query: string, variables: string) {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const queriesDir = path.join(workspaceFolder.uri.fsPath, '.vscode', 'graphql-queries');
        await fs.promises.mkdir(queriesDir, { recursive: true });
        
        const queryData = {
            name: name,
            query: query,
            variables: variables,
            createdAt: new Date().toISOString()
        };
        
        const fileName = `${name.replace(/[^a-zA-Z0-9]/g, '_')}.json`;
        const filePath = path.join(queriesDir, fileName);
        
        await fs.promises.writeFile(filePath, JSON.stringify(queryData, null, 2), 'utf8');
        
        vscode.window.showInformationMessage(`Requête '${name}' sauvegardée`);
    }

    private async loadQuery(name: string): Promise<any> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return null;

        const queriesDir = path.join(workspaceFolder.uri.fsPath, '.vscode', 'graphql-queries');
        const fileName = `${name.replace(/[^a-zA-Z0-9]/g, '_')}.json`;
        const filePath = path.join(queriesDir, fileName);
        
        try {
            const content = await fs.promises.readFile(filePath, 'utf8');
            return JSON.parse(content);
        } catch {
            vscode.window.showErrorMessage(`Impossible de charger la requête: ${name}`);
            return null;
        }
    }

    private async getSavedQueries(): Promise<any[]> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return [];

        const queriesDir = path.join(workspaceFolder.uri.fsPath, '.vscode', 'graphql-queries');
        
        try {
            const files = await fs.promises.readdir(queriesDir);
            const queries = [];
            
            for (const file of files) {
                if (file.endsWith('.json')) {
                    try {
                        const content = await fs.promises.readFile(path.join(queriesDir, file), 'utf8');
                        const queryData = JSON.parse(content);
                        queries.push(queryData);
                    } catch {
                        // Ignorer les fichiers corrompus
                    }
                }
            }
            
            return queries.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
        } catch {
            return [];
        }
    }

    private async introspectEndpoint(endpoint: string): Promise<any> {
        try {
            // Requête d'introspection GraphQL standard
            const introspectionQuery = `
                query IntrospectionQuery {
                    __schema {
                        queryType { name }
                        mutationType { name }
                        subscriptionType { name }
                        types {
                            ...FullType
                        }
                        directives {
                            name
                            description
                            locations
                            args {
                                ...InputValue
                            }
                        }
                    }
                }
                
                fragment FullType on __Type {
                    kind
                    name
                    description
                    fields(includeDeprecated: true) {
                        name
                        description
                        args {
                            ...InputValue
                        }
                        type {
                            ...TypeRef
                        }
                        isDeprecated
                        deprecationReason
                    }
                    inputFields {
                        ...InputValue
                    }
                    interfaces {
                        ...TypeRef
                    }
                    enumValues(includeDeprecated: true) {
                        name
                        description
                        isDeprecated
                        deprecationReason
                    }
                    possibleTypes {
                        ...TypeRef
                    }
                }
                
                fragment InputValue on __InputValue {
                    name
                    description
                    type { ...TypeRef }
                    defaultValue
                }
                
                fragment TypeRef on __Type {
                    kind
                    name
                    ofType {
                        kind
                        name
                        ofType {
                            kind
                            name
                            ofType {
                                kind
                                name
                                ofType {
                                    kind
                                    name
                                    ofType {
                                        kind
                                        name
                                        ofType {
                                            kind
                                            name
                                            ofType {
                                                kind
                                                name
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            `;
            
            // Simuler l'introspection (dans un vrai environnement, faire un appel HTTP)
            const mockSchema = {
                data: {
                    __schema: {
                        queryType: { name: 'Query' },
                        mutationType: { name: 'Mutation' },
                        subscriptionType: null,
                        types: [
                            {
                                kind: 'OBJECT',
                                name: 'User',
                                description: 'Utilisateur du système',
                                fields: [
                                    { name: 'id', type: { name: 'ID' } },
                                    { name: 'name', type: { name: 'String' } },
                                    { name: 'email', type: { name: 'String' } }
                                ]
                            }
                        ]
                    }
                }
            };
            
            return mockSchema;
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur d'introspection: ${error}`);
            return null;
        }
    }
}