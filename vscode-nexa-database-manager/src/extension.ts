import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';
import { DatabaseExplorer } from './databaseExplorer';
import { EntityVisualizer } from './entityVisualizer';
import { MigrationEditor } from './migrationEditor';
import { SchemaPreview } from './schemaPreview';

export function activate(context: vscode.ExtensionContext) {
    console.log('Extension Nexa Database Manager activée');

    // Vérifier si c'est un projet Nexa
    const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
    if (workspaceFolder) {
        const nexaPath = path.join(workspaceFolder.uri.fsPath, 'nexa');
        vscode.commands.executeCommand('setContext', 'workspaceHasNexaProject', true);
    }

    // Initialiser les providers
    const databaseExplorer = new DatabaseExplorer(context);
    const entityVisualizer = new EntityVisualizer(context);
    const migrationEditor = new MigrationEditor(context);
    const schemaPreview = new SchemaPreview(context);

    // Enregistrer le tree data provider
    vscode.window.registerTreeDataProvider('nexaDbExplorer', databaseExplorer);

    // Commande pour visualiser les entités
    const visualizeEntities = vscode.commands.registerCommand('nexa.db.visualizeEntities', async () => {
        await entityVisualizer.showEntityDiagram();
    });

    // Commande pour créer une migration
    const createMigration = vscode.commands.registerCommand('nexa.db.createMigration', async () => {
        const migrationName = await vscode.window.showInputBox({
            prompt: 'Nom de la migration',
            placeHolder: 'create_users_table'
        });

        if (migrationName) {
            await migrationEditor.createMigration(migrationName);
        }
    });

    // Commande pour aperçu du schéma
    const previewSchema = vscode.commands.registerCommand('nexa.db.previewSchema', async () => {
        await schemaPreview.showSchemaPreview();
    });

    // Commande pour générer un modèle
    const generateModel = vscode.commands.registerCommand('nexa.db.generateModel', async (uri: vscode.Uri) => {
        const entityPath = uri?.fsPath;
        if (entityPath) {
            await generateModelFromEntity(entityPath);
        } else {
            const entities = await getAvailableEntities();
            const selected = await vscode.window.showQuickPick(entities, {
                placeHolder: 'Sélectionnez une entité'
            });
            
            if (selected) {
                await generateModelFromEntity(selected);
            }
        }
    });

    // Commande pour ouvrir le dashboard
    const openDashboard = vscode.commands.registerCommand('nexa.db.openDashboard', async () => {
        const panel = vscode.window.createWebviewPanel(
            'nexaDbDashboard',
            'Nexa Database Dashboard',
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                retainContextWhenHidden: true
            }
        );

        panel.webview.html = getDashboardHtml();

        // Gérer les messages du webview
        panel.webview.onDidReceiveMessage(
            async message => {
                switch (message.command) {
                    case 'refreshEntities':
                        databaseExplorer.refresh();
                        break;
                    case 'createEntity':
                        await createEntityFromDashboard(message.data);
                        break;
                }
            },
            undefined,
            context.subscriptions
        );
    });

    // Commande pour actualiser l'explorateur
    const refreshExplorer = vscode.commands.registerCommand('nexa.db.refresh', () => {
        databaseExplorer.refresh();
    });

    context.subscriptions.push(
        visualizeEntities,
        createMigration,
        previewSchema,
        generateModel,
        openDashboard,
        refreshExplorer
    );

    // Watcher pour les changements de fichiers d'entités
    const entityWatcher = vscode.workspace.createFileSystemWatcher('**/entities/**/*.php');
    entityWatcher.onDidChange(() => databaseExplorer.refresh());
    entityWatcher.onDidCreate(() => databaseExplorer.refresh());
    entityWatcher.onDidDelete(() => databaseExplorer.refresh());
    
    context.subscriptions.push(entityWatcher);
}

async function getAvailableEntities(): Promise<string[]> {
    const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
    if (!workspaceFolder) return [];

    const entitiesPath = path.join(workspaceFolder.uri.fsPath, 'workspace', 'database', 'entities');
    
    try {
        const files = await fs.promises.readdir(entitiesPath);
        return files
            .filter(file => file.endsWith('.php'))
            .map(file => path.join(entitiesPath, file));
    } catch (error) {
        return [];
    }
}

async function generateModelFromEntity(entityPath: string): Promise<void> {
    try {
        const entityContent = await fs.promises.readFile(entityPath, 'utf8');
        const entityName = path.basename(entityPath, '.php');
        
        // Analyser l'entité et générer le modèle
        const modelContent = generateModelContent(entityName, entityContent);
        
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (workspaceFolder) {
            const modelPath = path.join(
                workspaceFolder.uri.fsPath,
                'workspace',
                'handlers',
                `${entityName}Model.php`
            );
            
            await fs.promises.writeFile(modelPath, modelContent);
            
            const doc = await vscode.workspace.openTextDocument(modelPath);
            await vscode.window.showTextDocument(doc);
            
            vscode.window.showInformationMessage(`Modèle ${entityName}Model généré avec succès`);
        }
    } catch (error) {
        vscode.window.showErrorMessage(`Erreur lors de la génération du modèle: ${error}`);
    }
}

function generateModelContent(entityName: string, entityContent: string): string {
    return `<?php

namespace Workspace\Handlers;

use Kernel\Nexa\Database\Model;
use Workspace\Database\Entities\${entityName};

class ${entityName}Model extends Model
{
    protected string $entity = ${entityName}::class;
    
    /**
     * Récupérer tous les ${entityName.toLowerCase()}s
     */
    public function getAll(): array
    {
        return $this->findAll();
    }
    
    /**
     * Récupérer un ${entityName.toLowerCase()} par ID
     */
    public function getById(int $id): ?${entityName}
    {
        return $this->find($id);
    }
    
    /**
     * Créer un nouveau ${entityName.toLowerCase()}
     */
    public function create(array $data): ${entityName}
    {
        return $this->save($data);
    }
    
    /**
     * Mettre à jour un ${entityName.toLowerCase()}
     */
    public function update(int $id, array $data): ?${entityName}
    {
        $entity = $this->find($id);
        if ($entity) {
            return $this->save($data, $id);
        }
        return null;
    }
    
    /**
     * Supprimer un ${entityName.toLowerCase()}
     */
    public function delete(int $id): bool
    {
        return $this->remove($id);
    }
}`;
}

async function createEntityFromDashboard(data: any): Promise<void> {
    // Logique pour créer une entité depuis le dashboard
    vscode.window.showInformationMessage('Création d\'entité depuis le dashboard');
}

function getDashboardHtml(): string {
    return `
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nexa Database Dashboard</title>
        <style>
            body {
                font-family: var(--vscode-font-family);
                color: var(--vscode-foreground);
                background-color: var(--vscode-editor-background);
                margin: 0;
                padding: 20px;
            }
            .dashboard-header {
                border-bottom: 1px solid var(--vscode-panel-border);
                padding-bottom: 20px;
                margin-bottom: 20px;
            }
            .dashboard-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }
            .dashboard-card {
                background: var(--vscode-editor-background);
                border: 1px solid var(--vscode-panel-border);
                border-radius: 8px;
                padding: 20px;
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
        </style>
    </head>
    <body>
        <div class="dashboard-header">
            <h1>🗄️ Nexa Database Dashboard</h1>
            <p>Gérez vos entités, migrations et schémas de base de données</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>📊 Entités</h3>
                <p>Visualisez et gérez vos entités de base de données</p>
                <button class="btn" onclick="refreshEntities()">Actualiser</button>
                <button class="btn" onclick="createEntity()">Nouvelle Entité</button>
            </div>
            
            <div class="dashboard-card">
                <h3>🔄 Migrations</h3>
                <p>Gérez vos migrations de base de données</p>
                <button class="btn" onclick="createMigration()">Nouvelle Migration</button>
                <button class="btn" onclick="runMigrations()">Exécuter</button>
            </div>
            
            <div class="dashboard-card">
                <h3>🏗️ Schémas</h3>
                <p>Visualisez la structure de votre base de données</p>
                <button class="btn" onclick="previewSchema()">Aperçu</button>
                <button class="btn" onclick="exportSchema()">Exporter</button>
            </div>
        </div>
        
        <script>
            const vscode = acquireVsCodeApi();
            
            function refreshEntities() {
                vscode.postMessage({
                    command: 'refreshEntities'
                });
            }
            
            function createEntity() {
                vscode.postMessage({
                    command: 'createEntity',
                    data: {}
                });
            }
            
            function createMigration() {
                vscode.postMessage({
                    command: 'createMigration'
                });
            }
            
            function runMigrations() {
                vscode.postMessage({
                    command: 'runMigrations'
                });
            }
            
            function previewSchema() {
                vscode.postMessage({
                    command: 'previewSchema'
                });
            }
            
            function exportSchema() {
                vscode.postMessage({
                    command: 'exportSchema'
                });
            }
        </script>
    </body>
    </html>
    `;
}

export function deactivate() {}