import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';
import { buildSchema, GraphQLSchema, GraphQLObjectType, GraphQLField, GraphQLType, isObjectType, isScalarType, isEnumType, isInterfaceType, isUnionType, isListType, isNonNullType } from 'graphql';

export class DocumentationGenerator {
    private context: vscode.ExtensionContext;
    private panel: vscode.WebviewPanel | undefined;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
    }

    async generateDocumentation() {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        try {
            // Rechercher les fichiers de schéma GraphQL
            const schemaFiles = await this.findSchemaFiles(workspaceFolder.uri.fsPath);
            
            if (schemaFiles.length === 0) {
                vscode.window.showWarningMessage('Aucun fichier de schéma GraphQL trouvé');
                return;
            }

            // Lire et combiner tous les schémas
            const schemaContent = await this.combineSchemas(schemaFiles);
            
            // Construire le schéma GraphQL
            const schema = buildSchema(schemaContent);
            
            // Générer la documentation
            const documentation = this.generateSchemaDocumentation(schema);
            
            // Afficher dans un webview
            this.showDocumentation(documentation);
            
            // Optionnel: sauvegarder en fichier
            await this.saveDocumentation(documentation, workspaceFolder.uri.fsPath);
            
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la génération de la documentation: ${error}`);
        }
    }

    private async findSchemaFiles(workspacePath: string): Promise<string[]> {
        const schemaFiles: string[] = [];
        
        const searchPatterns = [
            '**/*.graphql',
            '**/*.gql',
            '**/schema.gql',
            '**/schema.graphql',
            '**/types.graphql',
            'graphql/**/*.graphql'
        ];
        
        for (const pattern of searchPatterns) {
            const files = await vscode.workspace.findFiles(pattern, '**/node_modules/**');
            schemaFiles.push(...files.map(file => file.fsPath));
        }
        
        // Supprimer les doublons
        return [...new Set(schemaFiles)];
    }

    private async combineSchemas(schemaFiles: string[]): Promise<string> {
        let combinedSchema = '';
        
        for (const file of schemaFiles) {
            try {
                const content = await fs.promises.readFile(file, 'utf8');
                combinedSchema += `\n# From: ${path.basename(file)}\n${content}\n`;
            } catch (error) {
                console.warn(`Impossible de lire le fichier: ${file}`);
            }
        }
        
        return combinedSchema;
    }

    private generateSchemaDocumentation(schema: GraphQLSchema): any {
        const typeMap = schema.getTypeMap();
        const queryType = schema.getQueryType();
        const mutationType = schema.getMutationType();
        const subscriptionType = schema.getSubscriptionType();
        
        const documentation = {
            title: 'Documentation API GraphQL Nexa',
            description: 'Documentation générée automatiquement pour l\'API GraphQL',
            generatedAt: new Date().toISOString(),
            schema: {
                query: queryType ? this.generateTypeDocumentation(queryType) : null,
                mutation: mutationType ? this.generateTypeDocumentation(mutationType) : null,
                subscription: subscriptionType ? this.generateTypeDocumentation(subscriptionType) : null
            },
            types: {} as any,
            enums: {} as any,
            interfaces: {} as any,
            unions: {} as any,
            scalars: {} as any
        };
        
        // Documenter tous les types
        Object.keys(typeMap).forEach(typeName => {
            if (typeName.startsWith('__')) return; // Ignorer les types introspection
            
            const type = typeMap[typeName];
            
            if (isObjectType(type)) {
                documentation.types[typeName] = this.generateTypeDocumentation(type);
            } else if (isEnumType(type)) {
                documentation.enums[typeName] = this.generateEnumDocumentation(type);
            } else if (isInterfaceType(type)) {
                documentation.interfaces[typeName] = this.generateInterfaceDocumentation(type);
            } else if (isUnionType(type)) {
                documentation.unions[typeName] = this.generateUnionDocumentation(type);
            } else if (isScalarType(type)) {
                documentation.scalars[typeName] = this.generateScalarDocumentation(type);
            }
        });
        
        return documentation;
    }

    private generateTypeDocumentation(type: GraphQLObjectType): any {
        const fields = type.getFields();
        
        return {
            name: type.name,
            description: type.description || '',
            fields: Object.keys(fields).map(fieldName => {
                const field = fields[fieldName];
                return {
                    name: fieldName,
                    description: field.description || '',
                    type: this.getTypeString(field.type),
                    args: field.args.map(arg => ({
                        name: arg.name,
                        description: arg.description || '',
                        type: this.getTypeString(arg.type),
                        defaultValue: arg.defaultValue
                    })) as any[],
                    deprecated: field.deprecationReason ? true : false,
                    deprecationReason: field.deprecationReason || null
                };
            })
        };
    }

    private generateEnumDocumentation(type: any): any {
        return {
            name: type.name,
            description: type.description || '',
            values: type.getValues().map((value: any) => ({
                name: value.name,
                description: value.description || '',
                value: value.value,
                deprecated: value.isDeprecated,
                deprecationReason: value.deprecationReason
            }))
        };
    }

    private generateInterfaceDocumentation(type: any): any {
        const fields = type.getFields();
        
        return {
            name: type.name,
            description: type.description || '',
            fields: Object.keys(fields).map(fieldName => {
                const field = fields[fieldName];
                return {
                    name: fieldName,
                    description: field.description || '',
                    type: this.getTypeString(field.type),
                    args: field.args.map((arg: any) => ({
                        name: arg.name,
                        description: arg.description || '',
                        type: this.getTypeString(arg.type),
                        defaultValue: arg.defaultValue
                    }))
                };
            }),
            implementedBy: [] // TODO: Trouver les types qui implémentent cette interface
        };
    }

    private generateUnionDocumentation(type: any): any {
        return {
            name: type.name,
            description: type.description || '',
            possibleTypes: type.getTypes().map((t: any) => t.name)
        };
    }

    private generateScalarDocumentation(type: any): any {
        return {
            name: type.name,
            description: type.description || '',
            specifiedBy: type.specifiedByUrl || null
        };
    }

    private getTypeString(type: GraphQLType): string {
        if (isNonNullType(type)) {
            return `${this.getTypeString(type.ofType)}!`;
        }
        if (isListType(type)) {
            return `[${this.getTypeString(type.ofType)}]`;
        }
        return type.name;
    }

    private showDocumentation(documentation: any) {
        if (this.panel) {
            this.panel.reveal();
        } else {
            this.panel = vscode.window.createWebviewPanel(
                'nexaGraphQLDocs',
                'Documentation GraphQL Nexa',
                vscode.ViewColumn.Two,
                {
                    enableScripts: true,
                    retainContextWhenHidden: true
                }
            );

            this.panel.onDidDispose(() => {
                this.panel = undefined;
            });
        }

        this.panel.webview.html = this.generateDocumentationHTML(documentation);
    }

    private generateDocumentationHTML(documentation: any): string {
        return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${documentation.title}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--vscode-editor-background);
            color: var(--vscode-editor-foreground);
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--vscode-panel-border);
        }
        .header h1 {
            color: var(--vscode-textLink-foreground);
            margin-bottom: 10px;
        }
        .header .subtitle {
            color: var(--vscode-descriptionForeground);
            font-size: 16px;
        }
        .nav {
            position: sticky;
            top: 20px;
            background: var(--vscode-sideBar-background);
            border: 1px solid var(--vscode-panel-border);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .nav h3 {
            margin-top: 0;
            color: var(--vscode-textLink-foreground);
        }
        .nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .nav li {
            margin: 5px 0;
        }
        .nav a {
            color: var(--vscode-textLink-foreground);
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            display: block;
        }
        .nav a:hover {
            background: var(--vscode-list-hoverBackground);
        }
        .section {
            margin-bottom: 40px;
            background: var(--vscode-editor-background);
            border: 1px solid var(--vscode-panel-border);
            border-radius: 8px;
            padding: 20px;
        }
        .section h2 {
            color: var(--vscode-textLink-foreground);
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--vscode-panel-border);
        }
        .type-card {
            background: var(--vscode-sideBar-background);
            border: 1px solid var(--vscode-panel-border);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .type-name {
            font-size: 18px;
            font-weight: bold;
            color: var(--vscode-symbolIcon-classForeground);
            margin-bottom: 5px;
        }
        .type-description {
            color: var(--vscode-descriptionForeground);
            margin-bottom: 15px;
            font-style: italic;
        }
        .field {
            background: var(--vscode-editor-background);
            border-left: 3px solid var(--vscode-textLink-foreground);
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 0 4px 4px 0;
        }
        .field-name {
            font-weight: bold;
            color: var(--vscode-symbolIcon-fieldForeground);
        }
        .field-type {
            color: var(--vscode-symbolIcon-typeForeground);
            font-family: 'Courier New', monospace;
            background: var(--vscode-textCodeBlock-background);
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 10px;
        }
        .field-description {
            color: var(--vscode-descriptionForeground);
            margin-top: 5px;
            font-size: 14px;
        }
        .args {
            margin-top: 10px;
            padding-left: 20px;
        }
        .arg {
            background: var(--vscode-textCodeBlock-background);
            padding: 5px 10px;
            margin: 5px 0;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .deprecated {
            opacity: 0.6;
            text-decoration: line-through;
        }
        .deprecation-reason {
            color: var(--vscode-errorForeground);
            font-size: 12px;
            margin-top: 5px;
        }
        .enum-value {
            background: var(--vscode-textCodeBlock-background);
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 3px solid var(--vscode-symbolIcon-enumForeground);
        }
        .enum-value-name {
            font-weight: bold;
            color: var(--vscode-symbolIcon-enumForeground);
            font-family: 'Courier New', monospace;
        }
        .code {
            background: var(--vscode-textCodeBlock-background);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-query {
            background: #28a745;
            color: white;
        }
        .badge-mutation {
            background: #ffc107;
            color: black;
        }
        .badge-subscription {
            background: #17a2b8;
            color: white;
        }
        .badge-type {
            background: var(--vscode-symbolIcon-classForeground);
            color: white;
        }
        .badge-enum {
            background: var(--vscode-symbolIcon-enumForeground);
            color: white;
        }
        .badge-interface {
            background: var(--vscode-symbolIcon-interfaceForeground);
            color: white;
        }
        .badge-union {
            background: var(--vscode-symbolIcon-structForeground);
            color: white;
        }
        .badge-scalar {
            background: var(--vscode-symbolIcon-numberForeground);
            color: white;
        }
        .toc {
            columns: 2;
            column-gap: 30px;
        }
        .toc li {
            break-inside: avoid;
        }
        .search-box {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            background: var(--vscode-input-background);
            color: var(--vscode-input-foreground);
            border: 1px solid var(--vscode-input-border);
            border-radius: 4px;
            font-size: 14px;
        }
        .hidden {
            display: none;
        }
        .highlight {
            background: var(--vscode-editor-findMatchHighlightBackground);
            padding: 1px 3px;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 ${documentation.title}</h1>
            <div class="subtitle">${documentation.description}</div>
            <div class="subtitle">Généré le ${new Date(documentation.generatedAt).toLocaleString('fr-FR')}</div>
        </div>

        <input type="text" class="search-box" id="searchBox" placeholder="🔍 Rechercher dans la documentation...">

        <div class="nav">
            <h3>📋 Table des matières</h3>
            <div class="toc">
                <ul>
                    ${documentation.schema.query ? '<li><a href="#query"><span class="badge badge-query">Query</span> Requêtes</a></li>' : ''}
                    ${documentation.schema.mutation ? '<li><a href="#mutation"><span class="badge badge-mutation">Mutation</span> Mutations</a></li>' : ''}
                    ${documentation.schema.subscription ? '<li><a href="#subscription"><span class="badge badge-subscription">Subscription</span> Subscriptions</a></li>' : ''}
                    ${Object.keys(documentation.types).length > 0 ? '<li><a href="#types"><span class="badge badge-type">Types</span> Types d\'objets</a></li>' : ''}
                    ${Object.keys(documentation.enums).length > 0 ? '<li><a href="#enums"><span class="badge badge-enum">Enums</span> Énumérations</a></li>' : ''}
                    ${Object.keys(documentation.interfaces).length > 0 ? '<li><a href="#interfaces"><span class="badge badge-interface">Interfaces</span> Interfaces</a></li>' : ''}
                    ${Object.keys(documentation.unions).length > 0 ? '<li><a href="#unions"><span class="badge badge-union">Unions</span> Unions</a></li>' : ''}
                    ${Object.keys(documentation.scalars).length > 0 ? '<li><a href="#scalars"><span class="badge badge-scalar">Scalars</span> Types scalaires</a></li>' : ''}
                </ul>
            </div>
        </div>

        ${this.generateSchemaSection(documentation.schema)}
        ${this.generateTypesSection(documentation.types, 'Types d\'objets', 'types', 'type')}
        ${this.generateEnumsSection(documentation.enums)}
        ${this.generateInterfacesSection(documentation.interfaces)}
        ${this.generateUnionsSection(documentation.unions)}
        ${this.generateScalarsSection(documentation.scalars)}
    </div>

    <script>
        // Recherche en temps réel
        const searchBox = document.getElementById('searchBox');
        const allSections = document.querySelectorAll('.section, .type-card');
        
        searchBox.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            
            allSections.forEach(section => {
                const text = section.textContent.toLowerCase();
                if (query === '' || text.includes(query)) {
                    section.classList.remove('hidden');
                    // Surligner les termes trouvés
                    if (query !== '') {
                        highlightText(section, query);
                    } else {
                        removeHighlight(section);
                    }
                } else {
                    section.classList.add('hidden');
                }
            });
        });
        
        function highlightText(element, query) {
            // Implémentation simple du surlignage
            // Dans un vrai projet, vous utiliseriez une bibliothèque plus sophistiquée
        }
        
        function removeHighlight(element) {
            // Supprimer le surlignage
        }
        
        // Navigation fluide
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>`;
    }

    private generateSchemaSection(schema: any): string {
        let html = '';
        
        if (schema.query) {
            html += `
                <div class="section" id="query">
                    <h2><span class="badge badge-query">Query</span> Requêtes disponibles</h2>
                    ${this.generateTypeHTML(schema.query, 'query')}
                </div>
            `;
        }
        
        if (schema.mutation) {
            html += `
                <div class="section" id="mutation">
                    <h2><span class="badge badge-mutation">Mutation</span> Mutations disponibles</h2>
                    ${this.generateTypeHTML(schema.mutation, 'mutation')}
                </div>
            `;
        }
        
        if (schema.subscription) {
            html += `
                <div class="section" id="subscription">
                    <h2><span class="badge badge-subscription">Subscription</span> Subscriptions disponibles</h2>
                    ${this.generateTypeHTML(schema.subscription, 'subscription')}
                </div>
            `;
        }
        
        return html;
    }

    private generateTypesSection(types: any, title: string, id: string, badgeClass: string): string {
        if (Object.keys(types).length === 0) return '';
        
        let html = `
            <div class="section" id="${id}">
                <h2><span class="badge badge-${badgeClass}">${badgeClass.toUpperCase()}</span> ${title}</h2>
        `;
        
        Object.keys(types).forEach(typeName => {
            html += this.generateTypeHTML(types[typeName], badgeClass);
        });
        
        html += '</div>';
        return html;
    }

    private generateEnumsSection(enums: any): string {
        if (Object.keys(enums).length === 0) return '';
        
        let html = `
            <div class="section" id="enums">
                <h2><span class="badge badge-enum">ENUM</span> Énumérations</h2>
        `;
        
        Object.keys(enums).forEach(enumName => {
            const enumType = enums[enumName];
            html += `
                <div class="type-card">
                    <div class="type-name">${enumType.name}</div>
                    ${enumType.description ? `<div class="type-description">${enumType.description}</div>` : ''}
                    ${enumType.values.map((value: any) => `
                        <div class="enum-value ${value.deprecated ? 'deprecated' : ''}">
                            <div class="enum-value-name">${value.name}</div>
                            ${value.description ? `<div class="field-description">${value.description}</div>` : ''}
                            ${value.deprecated ? `<div class="deprecation-reason">Déprécié: ${value.deprecationReason || 'Raison non spécifiée'}</div>` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }

    private generateInterfacesSection(interfaces: any): string {
        if (Object.keys(interfaces).length === 0) return '';
        
        let html = `
            <div class="section" id="interfaces">
                <h2><span class="badge badge-interface">INTERFACE</span> Interfaces</h2>
        `;
        
        Object.keys(interfaces).forEach(interfaceName => {
            html += this.generateTypeHTML(interfaces[interfaceName], 'interface');
        });
        
        html += '</div>';
        return html;
    }

    private generateUnionsSection(unions: any): string {
        if (Object.keys(unions).length === 0) return '';
        
        let html = `
            <div class="section" id="unions">
                <h2><span class="badge badge-union">UNION</span> Types Union</h2>
        `;
        
        Object.keys(unions).forEach(unionName => {
            const unionType = unions[unionName];
            html += `
                <div class="type-card">
                    <div class="type-name">${unionType.name}</div>
                    ${unionType.description ? `<div class="type-description">${unionType.description}</div>` : ''}
                    <div class="field">
                        <div class="field-name">Types possibles:</div>
                        ${unionType.possibleTypes.map((type: string) => `<span class="code">${type}</span>`).join(' | ')}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }

    private generateScalarsSection(scalars: any): string {
        if (Object.keys(scalars).length === 0) return '';
        
        let html = `
            <div class="section" id="scalars">
                <h2><span class="badge badge-scalar">SCALAR</span> Types scalaires</h2>
        `;
        
        Object.keys(scalars).forEach(scalarName => {
            const scalarType = scalars[scalarName];
            html += `
                <div class="type-card">
                    <div class="type-name">${scalarType.name}</div>
                    ${scalarType.description ? `<div class="type-description">${scalarType.description}</div>` : ''}
                    ${scalarType.specifiedBy ? `<div class="field"><div class="field-name">Spécification:</div> <a href="${scalarType.specifiedBy}" target="_blank">${scalarType.specifiedBy}</a></div>` : ''}
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }

    private generateTypeHTML(type: any, badgeClass: string): string {
        return `
            <div class="type-card">
                <div class="type-name">${type.name}</div>
                ${type.description ? `<div class="type-description">${type.description}</div>` : ''}
                ${type.fields ? type.fields.map((field: any) => `
                    <div class="field ${field.deprecated ? 'deprecated' : ''}">
                        <span class="field-name">${field.name}</span>
                        <span class="field-type">${field.type}</span>
                        ${field.description ? `<div class="field-description">${field.description}</div>` : ''}
                        ${field.args && field.args.length > 0 ? `
                            <div class="args">
                                <strong>Arguments:</strong>
                                ${field.args.map((arg: any) => `
                                    <div class="arg">
                                        <strong>${arg.name}</strong>: <span class="field-type">${arg.type}</span>
                                        ${arg.description ? `<br><em>${arg.description}</em>` : ''}
                                        ${arg.defaultValue !== undefined ? `<br>Défaut: <code>${JSON.stringify(arg.defaultValue)}</code>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                        ${field.deprecated ? `<div class="deprecation-reason">Déprécié: ${field.deprecationReason || 'Raison non spécifiée'}</div>` : ''}
                    </div>
                `).join('') : ''}
            </div>
        `;
    }

    private async saveDocumentation(documentation: any, workspacePath: string) {
        const choice = await vscode.window.showInformationMessage(
            'Documentation générée avec succès!',
            'Sauvegarder en HTML',
            'Sauvegarder en Markdown',
            'Sauvegarder en JSON'
        );
        
        if (!choice) return;
        
        const docsDir = path.join(workspacePath, 'docs', 'graphql');
        await fs.promises.mkdir(docsDir, { recursive: true });
        
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        
        switch (choice) {
            case 'Sauvegarder en HTML':
                const htmlPath = path.join(docsDir, `api-documentation-${timestamp}.html`);
                await fs.promises.writeFile(htmlPath, this.generateDocumentationHTML(documentation), 'utf8');
                vscode.window.showInformationMessage(`Documentation HTML sauvegardée: ${htmlPath}`);
                break;
                
            case 'Sauvegarder en Markdown':
                const mdPath = path.join(docsDir, `api-documentation-${timestamp}.md`);
                await fs.promises.writeFile(mdPath, this.generateMarkdownDocumentation(documentation), 'utf8');
                vscode.window.showInformationMessage(`Documentation Markdown sauvegardée: ${mdPath}`);
                break;
                
            case 'Sauvegarder en JSON':
                const jsonPath = path.join(docsDir, `api-documentation-${timestamp}.json`);
                await fs.promises.writeFile(jsonPath, JSON.stringify(documentation, null, 2), 'utf8');
                vscode.window.showInformationMessage(`Documentation JSON sauvegardée: ${jsonPath}`);
                break;
        }
    }

    private generateMarkdownDocumentation(documentation: any): string {
        let md = `# ${documentation.title}\n\n`;
        md += `${documentation.description}\n\n`;
        md += `*Généré le ${new Date(documentation.generatedAt).toLocaleString('fr-FR')}*\n\n`;
        
        // Table des matières
        md += '## Table des matières\n\n';
        if (documentation.schema.query) md += '- [Requêtes](#requêtes)\n';
        if (documentation.schema.mutation) md += '- [Mutations](#mutations)\n';
        if (documentation.schema.subscription) md += '- [Subscriptions](#subscriptions)\n';
        if (Object.keys(documentation.types).length > 0) md += '- [Types d\'objets](#types-dobjets)\n';
        if (Object.keys(documentation.enums).length > 0) md += '- [Énumérations](#énumérations)\n';
        if (Object.keys(documentation.interfaces).length > 0) md += '- [Interfaces](#interfaces)\n';
        if (Object.keys(documentation.unions).length > 0) md += '- [Unions](#unions)\n';
        if (Object.keys(documentation.scalars).length > 0) md += '- [Types scalaires](#types-scalaires)\n';
        md += '\n';
        
        // Sections
        if (documentation.schema.query) {
            md += '## Requêtes\n\n';
            md += this.generateTypeMarkdown(documentation.schema.query);
        }
        
        if (documentation.schema.mutation) {
            md += '## Mutations\n\n';
            md += this.generateTypeMarkdown(documentation.schema.mutation);
        }
        
        if (documentation.schema.subscription) {
            md += '## Subscriptions\n\n';
            md += this.generateTypeMarkdown(documentation.schema.subscription);
        }
        
        if (Object.keys(documentation.types).length > 0) {
            md += '## Types d\'objets\n\n';
            Object.keys(documentation.types).forEach(typeName => {
                md += this.generateTypeMarkdown(documentation.types[typeName]);
            });
        }
        
        // Ajouter les autres sections...
        
        return md;
    }

    private generateTypeMarkdown(type: any): string {
        let md = `### ${type.name}\n\n`;
        if (type.description) {
            md += `${type.description}\n\n`;
        }
        
        if (type.fields && type.fields.length > 0) {
            md += '#### Champs\n\n';
            type.fields.forEach((field: any) => {
                md += `- **${field.name}**: \`${field.type}\``;
                if (field.description) {
                    md += ` - ${field.description}`;
                }
                md += '\n';
                
                if (field.args && field.args.length > 0) {
                    md += '  - Arguments:\n';
                    field.args.forEach((arg: any) => {
                        md += `    - **${arg.name}**: \`${arg.type}\``;
                        if (arg.description) md += ` - ${arg.description}`;
                        if (arg.defaultValue !== undefined) md += ` (défaut: \`${JSON.stringify(arg.defaultValue)}\`)`;
                        md += '\n';
                    });
                }
            });
            md += '\n';
        }
        
        return md;
    }
}