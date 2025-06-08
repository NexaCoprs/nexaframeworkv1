import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class MigrationEditor {
    constructor(private context: vscode.ExtensionContext) {}

    async createMigration(migrationName: string): Promise<void> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const timestamp = new Date().toISOString().replace(/[-:T]/g, '').split('.')[0];
        const fileName = `${timestamp}_${migrationName}.php`;
        const migrationsPath = path.join(
            workspaceFolder.uri.fsPath,
            'workspace',
            'database',
            'migrations'
        );

        // Créer le dossier migrations s'il n'existe pas
        await fs.promises.mkdir(migrationsPath, { recursive: true });

        const migrationContent = this.generateMigrationContent(migrationName, timestamp);
        const filePath = path.join(migrationsPath, fileName);

        try {
            await fs.promises.writeFile(filePath, migrationContent);
            
            const doc = await vscode.workspace.openTextDocument(filePath);
            await vscode.window.showTextDocument(doc);
            
            vscode.window.showInformationMessage(`Migration ${fileName} créée avec succès`);
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la création de la migration: ${error}`);
        }
    }

    private generateMigrationContent(migrationName: string, timestamp: string): string {
        const className = this.toPascalCase(migrationName);
        
        return `<?php

namespace Workspace\Database\Migrations;

use Kernel\Nexa\Database\Migration;
use Kernel\Nexa\Database\Schema\Blueprint;
use Kernel\Nexa\Database\Schema\Schema;

class ${className} extends Migration
{
    /**
     * Nom de la migration
     */
    public string $name = '${migrationName}';
    
    /**
     * Version de la migration
     */
    public string $version = '${timestamp}';

    /**
     * Exécuter la migration
     */
    public function up(): void
    {
        Schema::create('${this.getTableName(migrationName)}', function (Blueprint $table) {
            $table->id();
            // Ajoutez vos colonnes ici
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::dropIfExists('${this.getTableName(migrationName)}');
    }

    /**
     * Données de test (optionnel)
     */
    public function seed(): void
    {
        // Ajoutez vos données de test ici
    }
}`;
    }

    private toPascalCase(str: string): string {
        return str
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join('');
    }

    private getTableName(migrationName: string): string {
        // Extraire le nom de table du nom de migration
        const match = migrationName.match(/create_(.+)_table/);
        if (match) {
            return match[1];
        }
        
        // Si pas de pattern reconnu, utiliser le nom tel quel
        return migrationName.toLowerCase().replace(/_/g, '_');
    }

    async showMigrationEditor(): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaMigrationEditor',
            'Éditeur de Migration Nexa',
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                retainContextWhenHidden: true
            }
        );

        panel.webview.html = this.getMigrationEditorHtml();

        panel.webview.onDidReceiveMessage(
            async message => {
                switch (message.command) {
                    case 'generateMigration':
                        await this.generateMigrationFromEditor(message.data);
                        break;
                    case 'previewMigration':
                        this.previewMigration(message.data);
                        break;
                }
            },
            undefined,
            this.context.subscriptions
        );
    }

    private async generateMigrationFromEditor(data: any): Promise<void> {
        const { name, tables } = data;
        
        for (const table of tables) {
            const migrationName = `create_${table.name}_table`;
            await this.createMigration(migrationName);
        }
    }

    private previewMigration(data: any): void {
        vscode.window.showInformationMessage('Aperçu de la migration généré');
    }

    private getMigrationEditorHtml(): string {
        return `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Éditeur de Migration</title>
            <style>
                body {
                    font-family: var(--vscode-font-family);
                    color: var(--vscode-foreground);
                    background-color: var(--vscode-editor-background);
                    margin: 0;
                    padding: 20px;
                }
                .editor-container {
                    max-width: 1200px;
                    margin: 0 auto;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                .form-group input, .form-group select {
                    width: 100%;
                    padding: 8px;
                    background: var(--vscode-input-background);
                    color: var(--vscode-input-foreground);
                    border: 1px solid var(--vscode-input-border);
                    border-radius: 4px;
                }
                .table-designer {
                    border: 1px solid var(--vscode-panel-border);
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .column-row {
                    display: grid;
                    grid-template-columns: 2fr 1fr 1fr 100px 50px;
                    gap: 10px;
                    margin-bottom: 10px;
                    align-items: center;
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
                .btn-secondary {
                    background: var(--vscode-button-secondaryBackground);
                    color: var(--vscode-button-secondaryForeground);
                }
                .btn-danger {
                    background: #d73a49;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="editor-container">
                <h1>🔄 Éditeur de Migration</h1>
                
                <div class="form-group">
                    <label for="migrationName">Nom de la migration:</label>
                    <input type="text" id="migrationName" placeholder="create_users_table">
                </div>
                
                <div class="table-designer">
                    <h3>Designer de Table</h3>
                    
                    <div class="form-group">
                        <label for="tableName">Nom de la table:</label>
                        <input type="text" id="tableName" placeholder="users">
                    </div>
                    
                    <h4>Colonnes</h4>
                    <div class="column-row">
                        <strong>Nom</strong>
                        <strong>Type</strong>
                        <strong>Contraintes</strong>
                        <strong>Nullable</strong>
                        <strong>Actions</strong>
                    </div>
                    
                    <div id="columns">
                        <div class="column-row">
                            <input type="text" placeholder="id" value="id">
                            <select>
                                <option value="id">ID (Auto)</option>
                                <option value="string">String</option>
                                <option value="integer">Integer</option>
                                <option value="text">Text</option>
                                <option value="boolean">Boolean</option>
                                <option value="datetime">DateTime</option>
                                <option value="decimal">Decimal</option>
                            </select>
                            <input type="text" placeholder="unique, index">
                            <input type="checkbox">
                            <button class="btn btn-danger" onclick="removeColumn(this)">×</button>
                        </div>
                    </div>
                    
                    <button class="btn btn-secondary" onclick="addColumn()">+ Ajouter Colonne</button>
                </div>
                
                <div class="form-group">
                    <button class="btn" onclick="generateMigration()">Générer Migration</button>
                    <button class="btn btn-secondary" onclick="previewMigration()">Aperçu</button>
                </div>
            </div>
            
            <script>
                const vscode = acquireVsCodeApi();
                
                function addColumn() {
                    const columnsDiv = document.getElementById('columns');
                    const newColumn = document.createElement('div');
                    newColumn.className = 'column-row';
                    newColumn.innerHTML = \`
                        <input type="text" placeholder="nom_colonne">
                        <select>
                            <option value="string">String</option>
                            <option value="integer">Integer</option>
                            <option value="text">Text</option>
                            <option value="boolean">Boolean</option>
                            <option value="datetime">DateTime</option>
                            <option value="decimal">Decimal</option>
                        </select>
                        <input type="text" placeholder="unique, index">
                        <input type="checkbox">
                        <button class="btn btn-danger" onclick="removeColumn(this)">×</button>
                    \`;
                    columnsDiv.appendChild(newColumn);
                }
                
                function removeColumn(button) {
                    button.parentElement.remove();
                }
                
                function generateMigration() {
                    const migrationName = document.getElementById('migrationName').value;
                    const tableName = document.getElementById('tableName').value;
                    
                    if (!migrationName || !tableName) {
                        alert('Veuillez remplir le nom de la migration et de la table');
                        return;
                    }
                    
                    const columns = [];
                    const columnRows = document.querySelectorAll('#columns .column-row');
                    
                    columnRows.forEach(row => {
                        const inputs = row.querySelectorAll('input, select');
                        columns.push({
                            name: inputs[0].value,
                            type: inputs[1].value,
                            constraints: inputs[2].value,
                            nullable: inputs[3].checked
                        });
                    });
                    
                    vscode.postMessage({
                        command: 'generateMigration',
                        data: {
                            name: migrationName,
                            tables: [{
                                name: tableName,
                                columns: columns
                            }]
                        }
                    });
                }
                
                function previewMigration() {
                    const migrationName = document.getElementById('migrationName').value;
                    const tableName = document.getElementById('tableName').value;
                    
                    vscode.postMessage({
                        command: 'previewMigration',
                        data: {
                            name: migrationName,
                            table: tableName
                        }
                    });
                }
            </script>
        </body>
        </html>
        `;
    }
}