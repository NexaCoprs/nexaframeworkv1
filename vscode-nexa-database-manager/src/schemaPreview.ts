import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class SchemaPreview {
    constructor(private context: vscode.ExtensionContext) {}

    async showSchemaPreview(): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaSchemaPreview',
            'Aperçu du Schéma Nexa',
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                retainContextWhenHidden: true
            }
        );

        const schemaData = await this.getSchemaData();
        panel.webview.html = this.getSchemaPreviewHtml(schemaData);

        panel.webview.onDidReceiveMessage(
            async message => {
                switch (message.command) {
                    case 'exportSchema':
                        await this.exportSchema(message.format);
                        break;
                    case 'refreshSchema':
                        const newSchemaData = await this.getSchemaData();
                        panel.webview.postMessage({
                            command: 'updateSchema',
                            data: newSchemaData
                        });
                        break;
                }
            },
            undefined,
            this.context.subscriptions
        );
    }

    private async getSchemaData(): Promise<any> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return { entities: [], migrations: [] };

        const entities = await this.getEntitiesSchema();
        const migrations = await this.getMigrationsSchema();

        return {
            entities,
            migrations,
            relationships: this.extractRelationships(entities)
        };
    }

    private async getEntitiesSchema(): Promise<any[]> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return [];

        const entitiesPath = path.join(workspaceFolder.uri.fsPath, 'workspace', 'database', 'entities');
        
        try {
            const files = await fs.promises.readdir(entitiesPath);
            const entities = [];

            for (const file of files.filter(f => f.endsWith('.php'))) {
                const filePath = path.join(entitiesPath, file);
                const content = await fs.promises.readFile(filePath, 'utf8');
                const entitySchema = this.parseEntitySchema(content, path.basename(file, '.php'));
                entities.push(entitySchema);
            }

            return entities;
        } catch (error) {
            return [];
        }
    }

    private async getMigrationsSchema(): Promise<any[]> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return [];

        const migrationsPath = path.join(workspaceFolder.uri.fsPath, 'workspace', 'database', 'migrations');
        
        try {
            const files = await fs.promises.readdir(migrationsPath);
            const migrations = [];

            for (const file of files.filter(f => f.endsWith('.php'))) {
                const filePath = path.join(migrationsPath, file);
                const content = await fs.promises.readFile(filePath, 'utf8');
                const migrationSchema = this.parseMigrationSchema(content, file);
                migrations.push(migrationSchema);
            }

            return migrations.sort((a, b) => a.timestamp.localeCompare(b.timestamp));
        } catch (error) {
            return [];
        }
    }

    private parseEntitySchema(content: string, entityName: string): any {
        const fields: any[] = [];
        const indexes: any[] = [];
        const constraints: any[] = [];

        // Parser les propriétés avec leurs types
        const propertyRegex = /#\[Column\([^\]]*\)\]\s*(?:public|private|protected)\s+(\w+)\s+\$(\w+)/g;
        let match: RegExpExecArray | null;
        
        while ((match = propertyRegex.exec(content)) !== null) {
            fields.push({
                name: match[2],
                type: match[1],
                nullable: content.includes(`${match[2]}?`),
                primary: content.includes('#[Id]') && content.indexOf('#[Id]') < match.index
            });
        }

        // Parser les index
        const indexRegex = /#\[Index\([^\]]*name:\s*["']([^"']+)["'][^\]]*\)\]/g;
        while ((match = indexRegex.exec(content)) !== null) {
            indexes.push({
                name: match[1],
                type: 'index'
            });
        }

        return {
            name: entityName,
            tableName: this.camelToSnake(entityName),
            fields,
            indexes,
            constraints
        };
    }

    private parseMigrationSchema(content: string, fileName: string): any {
        const timestamp = fileName.substring(0, 14);
        const name = fileName.replace(/^\d+_/, '').replace('.php', '');
        
        // Parser les opérations de migration
        const operations = [];
        
        // Rechercher les créations de tables
        const createTableRegex = /Schema::create\(["']([^"']+)["']/g;
        let match;
        
        while ((match = createTableRegex.exec(content)) !== null) {
            operations.push({
                type: 'create_table',
                table: match[1]
            });
        }

        // Rechercher les suppressions de tables
        const dropTableRegex = /Schema::dropIfExists\(["']([^"']+)["']/g;
        while ((match = dropTableRegex.exec(content)) !== null) {
            operations.push({
                type: 'drop_table',
                table: match[1]
            });
        }

        return {
            name,
            fileName,
            timestamp,
            operations
        };
    }

    private extractRelationships(entities: any[]): any[] {
        const relationships: any[] = [];
        
        // Logique pour extraire les relations entre entités
        // Basée sur les clés étrangères et les annotations
        
        return relationships;
    }

    private camelToSnake(str: string): string {
        return str.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`).substring(1);
    }

    private async exportSchema(format: string): Promise<void> {
        const schemaData = await this.getSchemaData();
        
        switch (format) {
            case 'sql':
                await this.exportToSQL(schemaData);
                break;
            case 'json':
                await this.exportToJSON(schemaData);
                break;
            case 'markdown':
                await this.exportToMarkdown(schemaData);
                break;
        }
    }

    private async exportToSQL(schemaData: any): Promise<void> {
        let sql = '-- Schéma de base de données Nexa\n\n';
        
        for (const entity of schemaData.entities) {
            sql += `CREATE TABLE ${entity.tableName} (\n`;
            
            for (const field of entity.fields) {
                sql += `  ${field.name} ${this.phpToSQLType(field.type)}`;
                if (field.primary) sql += ' PRIMARY KEY';
                if (!field.nullable) sql += ' NOT NULL';
                sql += ',\n';
            }
            
            sql = sql.slice(0, -2) + '\n';  // Enlever la dernière virgule
            sql += ');\n\n';
        }
        
        await this.saveExportedFile('schema.sql', sql);
    }

    private async exportToJSON(schemaData: any): Promise<void> {
        const json = JSON.stringify(schemaData, null, 2);
        await this.saveExportedFile('schema.json', json);
    }

    private async exportToMarkdown(schemaData: any): Promise<void> {
        let markdown = '# Schéma de Base de Données Nexa\n\n';
        
        markdown += '## Entités\n\n';
        
        for (const entity of schemaData.entities) {
            markdown += `### ${entity.name}\n\n`;
            markdown += `**Table:** \`${entity.tableName}\`\n\n`;
            markdown += '| Champ | Type | Nullable | Contraintes |\n';
            markdown += '|-------|------|----------|-------------|\n';
            
            for (const field of entity.fields) {
                markdown += `| ${field.name} | ${field.type} | ${field.nullable ? 'Oui' : 'Non'} | ${field.primary ? 'PRIMARY KEY' : ''} |\n`;
            }
            
            markdown += '\n';
        }
        
        await this.saveExportedFile('schema.md', markdown);
    }

    private phpToSQLType(phpType: string): string {
        const typeMap: { [key: string]: string } = {
            'string': 'VARCHAR(255)',
            'int': 'INTEGER',
            'integer': 'INTEGER',
            'float': 'FLOAT',
            'bool': 'BOOLEAN',
            'boolean': 'BOOLEAN',
            'DateTime': 'TIMESTAMP',
            'array': 'JSON'
        };
        
        return typeMap[phpType] || 'TEXT';
    }

    private async saveExportedFile(fileName: string, content: string): Promise<void> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return;

        const filePath = path.join(workspaceFolder.uri.fsPath, fileName);
        
        try {
            await fs.promises.writeFile(filePath, content);
            vscode.window.showInformationMessage(`Schéma exporté vers ${fileName}`);
            
            const doc = await vscode.workspace.openTextDocument(filePath);
            await vscode.window.showTextDocument(doc);
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'export: ${error}`);
        }
    }

    private getSchemaPreviewHtml(schemaData: any): string {
        return `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Aperçu du Schéma</title>
            <style>
                body {
                    font-family: var(--vscode-font-family);
                    color: var(--vscode-foreground);
                    background-color: var(--vscode-editor-background);
                    margin: 0;
                    padding: 20px;
                }
                .schema-container {
                    max-width: 1200px;
                    margin: 0 auto;
                }
                .entity-card {
                    background: var(--vscode-editor-background);
                    border: 1px solid var(--vscode-panel-border);
                    border-radius: 8px;
                    margin: 20px 0;
                    padding: 20px;
                }
                .entity-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 1px solid var(--vscode-panel-border);
                    padding-bottom: 10px;
                    margin-bottom: 15px;
                }
                .entity-name {
                    font-size: 18px;
                    font-weight: bold;
                }
                .table-name {
                    color: var(--vscode-descriptionForeground);
                    font-size: 14px;
                }
                .fields-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .fields-table th,
                .fields-table td {
                    text-align: left;
                    padding: 8px 12px;
                    border-bottom: 1px solid var(--vscode-panel-border);
                }
                .fields-table th {
                    background: var(--vscode-list-hoverBackground);
                    font-weight: bold;
                }
                .primary-key {
                    color: var(--vscode-charts-yellow);
                    font-weight: bold;
                }
                .nullable {
                    color: var(--vscode-charts-blue);
                }
                .btn {
                    background: var(--vscode-button-background);
                    color: var(--vscode-button-foreground);
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 5px;
                }
                .btn:hover {
                    background: var(--vscode-button-hoverBackground);
                }
                .export-section {
                    background: var(--vscode-list-hoverBackground);
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .migration-timeline {
                    margin: 20px 0;
                }
                .migration-item {
                    display: flex;
                    align-items: center;
                    padding: 10px;
                    border-left: 3px solid var(--vscode-charts-green);
                    margin: 10px 0;
                    background: var(--vscode-list-hoverBackground);
                }
                .migration-timestamp {
                    font-family: monospace;
                    color: var(--vscode-descriptionForeground);
                    margin-right: 15px;
                }
            </style>
        </head>
        <body>
            <div class="schema-container">
                <div class="schema-header">
                    <h1>🏗️ Aperçu du Schéma de Base de Données</h1>
                    <button class="btn" onclick="refreshSchema()">🔄 Actualiser</button>
                </div>
                
                <div class="export-section">
                    <h3>📤 Exporter le Schéma</h3>
                    <button class="btn" onclick="exportSchema('sql')">SQL</button>
                    <button class="btn" onclick="exportSchema('json')">JSON</button>
                    <button class="btn" onclick="exportSchema('markdown')">Markdown</button>
                </div>
                
                <div id="entities-section">
                    <h2>📊 Entités (${schemaData.entities.length})</h2>
                    ${schemaData.entities.map((entity: any) => `
                        <div class="entity-card">
                            <div class="entity-header">
                                <div>
                                    <div class="entity-name">${entity.name}</div>
                                    <div class="table-name">Table: ${entity.tableName}</div>
                                </div>
                            </div>
                            <table class="fields-table">
                                <thead>
                                    <tr>
                                        <th>Champ</th>
                                        <th>Type</th>
                                        <th>Nullable</th>
                                        <th>Contraintes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${entity.fields.map((field: any) => `
                                        <tr>
                                            <td class="${field.primary ? 'primary-key' : ''}">
                                                ${field.name}
                                                ${field.primary ? '🔑' : ''}
                                            </td>
                                            <td>${field.type}</td>
                                            <td class="${field.nullable ? 'nullable' : ''}">
                                                ${field.nullable ? 'Oui' : 'Non'}
                                            </td>
                                            <td>${field.primary ? 'PRIMARY KEY' : ''}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `).join('')}
                </div>
                
                <div class="migration-timeline">
                    <h2>🔄 Historique des Migrations (${schemaData.migrations.length})</h2>
                    ${schemaData.migrations.map((migration: any) => `
                        <div class="migration-item">
                            <div class="migration-timestamp">${migration.timestamp}</div>
                            <div>
                                <strong>${migration.name}</strong>
                                <div style="font-size: 12px; color: var(--vscode-descriptionForeground);">
                                    ${migration.operations.map((op: any) => `${op.type}: ${op.table}`).join(', ')}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <script>
                const vscode = acquireVsCodeApi();
                
                function refreshSchema() {
                    vscode.postMessage({
                        command: 'refreshSchema'
                    });
                }
                
                function exportSchema(format) {
                    vscode.postMessage({
                        command: 'exportSchema',
                        format: format
                    });
                }
                
                // Écouter les messages du webview
                window.addEventListener('message', event => {
                    const message = event.data;
                    
                    switch (message.command) {
                        case 'updateSchema':
                            location.reload();
                            break;
                    }
                });
            </script>
        </body>
        </html>
        `;
    }
}