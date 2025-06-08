import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class GraphQLStudio {
    private panel: vscode.WebviewPanel | undefined;
    private context: vscode.ExtensionContext;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
    }

    async openStudio() {
        if (this.panel) {
            this.panel.reveal();
            return;
        }

        this.panel = vscode.window.createWebviewPanel(
            'nexaGraphQLStudio',
            'Nexa GraphQL Studio',
            vscode.ViewColumn.One,
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
                    case 'saveSchema':
                        await this.saveSchema(message.content, message.filename);
                        break;
                    case 'loadSchema':
                        await this.loadSchema(message.filename);
                        break;
                    case 'validateSchema':
                        await this.validateSchema(message.content);
                        break;
                    case 'generateResolver':
                        await this.generateResolverFromStudio(message.typeName, message.fields);
                        break;
                    case 'introspectSchema':
                        const introspection = await this.introspectSchema();
                        this.panel?.webview.postMessage({
                            command: 'introspectionResult',
                            result: introspection
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
    }

    private async getWebviewContent(): Promise<string> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        const schemas = workspaceFolder ? await this.getSchemaFiles() : [];
        
        return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexa GraphQL Studio</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--vscode-editor-background);
            color: var(--vscode-editor-foreground);
        }
        .container {
            max-width: 1200px;
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
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--vscode-panel-border);
        }
        .tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: var(--vscode-foreground);
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab.active {
            border-bottom-color: var(--vscode-button-background);
            color: var(--vscode-button-background);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            background: var(--vscode-button-background);
            color: var(--vscode-button-foreground);
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background: var(--vscode-button-hoverBackground);
        }
        .btn-secondary {
            background: var(--vscode-button-secondaryBackground);
            color: var(--vscode-button-secondaryForeground);
        }
        .input, .select {
            padding: 8px;
            background: var(--vscode-input-background);
            color: var(--vscode-input-foreground);
            border: 1px solid var(--vscode-input-border);
            border-radius: 4px;
        }
        .editor-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            height: 500px;
        }
        .editor-panel {
            border: 1px solid var(--vscode-panel-border);
            border-radius: 4px;
            overflow: hidden;
        }
        .editor-header {
            background: var(--vscode-panel-background);
            padding: 10px;
            border-bottom: 1px solid var(--vscode-panel-border);
            font-weight: bold;
        }
        .editor {
            width: 100%;
            height: calc(100% - 40px);
            background: var(--vscode-editor-background);
            color: var(--vscode-editor-foreground);
            border: none;
            padding: 10px;
            font-family: 'Courier New', monospace;
            resize: none;
        }
        .validation-result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            background: var(--vscode-panel-background);
            border: 1px solid var(--vscode-panel-border);
        }
        .schema-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .schema-item {
            padding: 10px;
            border: 1px solid var(--vscode-panel-border);
            margin-bottom: 5px;
            cursor: pointer;
            border-radius: 4px;
        }
        .schema-item:hover {
            background: var(--vscode-list-hoverBackground);
        }
        .resolver-generator {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid var(--vscode-panel-border);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Nexa GraphQL Studio</h1>
            <div>
                <select class="select" id="schemaSelect">
                    <option value="">Nouveau schéma</option>
                    ${schemas.map((schema: string) => `<option value="${schema}">${schema}</option>`).join('')}
                </select>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('editor')">Éditeur</button>
            <button class="tab" onclick="showTab('introspection')">Introspection</button>
            <button class="tab" onclick="showTab('resolver')">Générateur de Resolver</button>
        </div>

        <div id="editor" class="tab-content active">
            <div class="toolbar">
                <input type="text" class="input" id="filename" placeholder="schema.graphql" value="schema.graphql">
                <button class="btn" onclick="saveSchema()">💾 Sauvegarder</button>
                <button class="btn btn-secondary" onclick="loadSchema()">📂 Charger</button>
                <button class="btn btn-secondary" onclick="validateSchema()">✅ Valider</button>
                <button class="btn btn-secondary" onclick="formatSchema()">🎨 Formater</button>
            </div>

            <div class="editor-container">
                <div class="editor-panel">
                    <div class="editor-header">Schéma GraphQL</div>
                    <textarea class="editor" id="schemaEditor" placeholder="# Définissez votre schéma GraphQL ici\ntype User {\n  id: ID!\n  name: String!\n  email: String!\n}\n\ntype Query {\n  users: [User!]!\n  user(id: ID!): User\n}">type User {\n  id: ID!\n  name: String!\n  email: String!\n  createdAt: String!\n}\n\ntype Query {\n  users: [User!]!\n  user(id: ID!): User\n}\n\ntype Mutation {\n  createUser(name: String!, email: String!): User!\n  updateUser(id: ID!, name: String, email: String): User\n  deleteUser(id: ID!): Boolean!\n}</textarea>
                </div>
                <div class="editor-panel">
                    <div class="editor-header">Aperçu / Documentation</div>
                    <div class="editor" id="preview" style="overflow-y: auto; white-space: pre-wrap;">Votre documentation apparaîtra ici après validation du schéma.</div>
                </div>
            </div>

            <div id="validationResult" class="validation-result" style="display: none;"></div>
        </div>

        <div id="introspection" class="tab-content">
            <div class="toolbar">
                <button class="btn" onclick="introspectSchema()">🔍 Introspecter le schéma</button>
                <button class="btn btn-secondary" onclick="exportIntrospection()">📤 Exporter</button>
            </div>
            <div class="editor-panel">
                <div class="editor-header">Résultat de l'introspection</div>
                <textarea class="editor" id="introspectionResult" readonly style="height: 400px;"></textarea>
            </div>
        </div>

        <div id="resolver" class="tab-content">
            <div class="resolver-generator">
                <h3>Générateur de Resolver</h3>
                <div class="toolbar">
                    <input type="text" class="input" id="typeName" placeholder="Nom du type (ex: User)">
                    <button class="btn" onclick="generateResolver()">🔧 Générer Resolver</button>
                </div>
                <div class="editor-panel" style="margin-top: 20px;">
                    <div class="editor-header">Champs détectés</div>
                    <div id="fieldsPreview" style="padding: 20px;">Sélectionnez un type dans le schéma pour voir ses champs.</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const vscode = acquireVsCodeApi();

        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
            
            event.target.classList.add('active');
            document.getElementById(tabName).style.display = 'block';
        }

        function saveSchema() {
            const content = document.getElementById('schemaEditor').value;
            const filename = document.getElementById('filename').value || 'schema.graphql';
            
            vscode.postMessage({
                command: 'saveSchema',
                content: content,
                filename: filename
            });
        }

        function loadSchema() {
            const filename = document.getElementById('schemaSelect').value;
            if (filename) {
                vscode.postMessage({
                    command: 'loadSchema',
                    filename: filename
                });
            }
        }

        function validateSchema() {
            const content = document.getElementById('schemaEditor').value;
            
            vscode.postMessage({
                command: 'validateSchema',
                content: content
            });
        }

        function formatSchema() {
            const editor = document.getElementById('schemaEditor');
            const content = editor.value;
            
            const formatted = content
                .replace(/\\{/g, ' {\\n  ')
                .replace(/\\}/g, '\\n}\\n')
                .replace(/,/g, ',\\n  ')
                .replace(/\\n\\s*\\n/g, '\\n');
            
            editor.value = formatted;
        }

        function introspectSchema() {
            vscode.postMessage({
                command: 'introspectSchema'
            });
        }

        function exportIntrospection() {
            const content = document.getElementById('introspectionResult').value;
            if (content) {
                const blob = new Blob([content], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'schema-introspection.json';
                a.click();
            }
        }

        function generateResolver() {
            const typeName = document.getElementById('typeName').value;
            if (!typeName) {
                alert('Veuillez entrer un nom de type');
                return;
            }
            
            const schema = document.getElementById('schemaEditor').value;
            const fields = extractFieldsFromType(schema, typeName);
            
            vscode.postMessage({
                command: 'generateResolver',
                typeName: typeName,
                fields: fields
            });
        }

        function extractFieldsFromType(schema, typeName) {
            const typeRegex = new RegExp('type\\\\s+' + typeName + '\\\\s*\\\\{([^}]+)\\\\}', 'i');
            const match = schema.match(typeRegex);
            
            if (!match) return [];
            
            const fieldsText = match[1];
            const fieldLines = fieldsText.split('\\n').filter(line => line.trim());
            
            return fieldLines.map(line => {
                const fieldMatch = line.trim().match(/^(\\w+)\\s*:\\s*(.+)$/);
                if (fieldMatch) {
                    return {
                        name: fieldMatch[1],
                        type: fieldMatch[2].replace(/[!\\[\\]]/g, '').trim()
                    };
                }
                return null;
            }).filter(field => field !== null);
        }

        window.addEventListener('message', event => {
            const message = event.data;
            
            switch (message.command) {
                case 'schemaLoaded':
                    document.getElementById('schemaEditor').value = message.content;
                    break;
                case 'validationResult':
                    const resultDiv = document.getElementById('validationResult');
                    resultDiv.style.display = 'block';
                    resultDiv.innerHTML = '<h3>' + (message.isValid ? '✅ Schéma valide' : '❌ Erreurs détectées') + '</h3><pre>' + (message.errors || 'Aucune erreur détectée.') + '</pre>';
                    
                    if (message.isValid && message.documentation) {
                        document.getElementById('preview').textContent = message.documentation;
                    }
                    break;
                case 'introspectionResult':
                    document.getElementById('introspectionResult').value = JSON.stringify(message.result, null, 2);
                    break;
                case 'resolverGenerated':
                    const fieldsPreview = document.getElementById('fieldsPreview');
                    fieldsPreview.innerHTML = '<h4>Resolver généré pour ' + message.typeName + '</h4><pre>' + message.resolver + '</pre><h4>Champs détectés:</h4><ul>' + message.fields.map(field => '<li>' + field.name + ': ' + field.type + '</li>').join('') + '</ul>';
                    break;
            }
        });

        document.getElementById('schemaSelect').addEventListener('change', function(e) {
            if (e.target.value) {
                loadSchema();
            }
        });
    </script>
</body>
</html>`;
    }

    private async getSchemaFiles(): Promise<string[]> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return [];

        const schemaDir = path.join(workspaceFolder.uri.fsPath, 'schemas');
        
        try {
            if (!fs.existsSync(schemaDir)) {
                fs.mkdirSync(schemaDir, { recursive: true });
                return [];
            }

            const files = fs.readdirSync(schemaDir);
            return files.filter(file => file.endsWith('.graphql'));
        } catch (error) {
            return [];
        }
    }

    private async saveSchema(content: string, filename: string) {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const schemaDir = path.join(workspaceFolder.uri.fsPath, 'schemas');
        if (!fs.existsSync(schemaDir)) {
            fs.mkdirSync(schemaDir, { recursive: true });
        }

        const filePath = path.join(schemaDir, filename);
        
        try {
            fs.writeFileSync(filePath, content);
            vscode.window.showInformationMessage(`Schéma sauvegardé: ${filename}`);
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la sauvegarde: ${error}`);
        }
    }

    private async loadSchema(filename: string) {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return;

        const filePath = path.join(workspaceFolder.uri.fsPath, 'schemas', filename);
        
        try {
            const content = fs.readFileSync(filePath, 'utf8');
            this.panel?.webview.postMessage({
                command: 'schemaLoaded',
                content: content
            });
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors du chargement: ${error}`);
        }
    }

    private async validateSchema(content: string) {
        try {
            const errors: string[] = [];
            const documentation: string[] = [];
            
            if (!content.trim()) {
                errors.push('Le schéma est vide');
            }
            
            const typeMatches = content.match(/type\s+(\w+)\s*\{[^}]+\}/g);
            if (typeMatches) {
                typeMatches.forEach(typeMatch => {
                    const nameMatch = typeMatch.match(/type\s+(\w+)/);
                    if (nameMatch) {
                        documentation.push(`Type: ${nameMatch[1]}`);
                    }
                });
            }
            
            const isValid = errors.length === 0;
            
            this.panel?.webview.postMessage({
                command: 'validationResult',
                isValid: isValid,
                errors: errors.join('\n'),
                documentation: documentation.join('\n')
            });
        } catch (error) {
            this.panel?.webview.postMessage({
                command: 'validationResult',
                isValid: false,
                errors: `Erreur de validation: ${error}`
            });
        }
    }

    private async generateResolverFromStudio(typeName: string, fields: any[]) {
        const resolver = this.generateResolver(typeName, fields);
        
        this.panel?.webview.postMessage({
            command: 'resolverGenerated',
            typeName: typeName,
            fields: fields,
            resolver: resolver
        });
    }

    private generateResolver(typeName: string, fields: any[]): string {
        const resolverName = typeName.toLowerCase();
        
        return `const ${resolverName}Resolver = {
    Query: {
        ${resolverName}: async (parent: any, args: any, context: any) => {
            // Implémentation pour récupérer un ${typeName}
            return {
${fields.map(field => `                ${field.name}: null, // TODO: Implémenter`).join('\n')}
            };
        },
        ${resolverName}s: async (parent: any, args: any, context: any) => {
            // Implémentation pour récupérer tous les ${typeName}s
            return [];
        }
    },
    Mutation: {
        create${typeName}: async (parent: any, args: any, context: any) => {
            // Implémentation pour créer un ${typeName}
            return {
${fields.map(field => `                ${field.name}: args.${field.name},`).join('\n')}
            };
        }
    }
};`;
    }

    private async introspectSchema(): Promise<any> {
        return {
            data: {
                __schema: {
                    types: [],
                    queryType: { name: 'Query' },
                    mutationType: { name: 'Mutation' },
                    subscriptionType: null
                }
            }
        };
    }

    private extractTypesFromSchemas(schemas: any[]): any[] {
        const types: any[] = [];
        
        schemas.forEach((schema: any) => {
            const content = schema.content;
            const typeMatches = content.match(/type\s+(\w+)\s*\{([^}]+)\}/g) || [];
            
            typeMatches.forEach((typeMatch: any) => {
                const nameMatch = typeMatch.match(/type\s+(\w+)/);
                const fieldsMatch = typeMatch.match(/\{([^}]+)\}/);
                
                if (nameMatch && fieldsMatch) {
                    const typeName = nameMatch[1];
                    const fieldsText = fieldsMatch[1];
                    const fields = fieldsText.split('\n')
                        .map((line: string) => line.trim())
                        .filter((line: string) => line.length > 0)
                        .map((line: string) => {
                            const fieldMatch = line.match(/^(\w+)\s*:\s*(.+)$/);
                            if (fieldMatch) {
                                return {
                                    name: fieldMatch[1],
                                    type: fieldMatch[2].replace(/[!\[\]]/g, '').trim()
                                };
                            }
                            return null;
                        })
                        .filter((field: any) => field !== null);
                    
                    types.push({
                        name: typeName,
                        fields: fields
                    });
                }
            });
        });
        
        return types;
    }
}