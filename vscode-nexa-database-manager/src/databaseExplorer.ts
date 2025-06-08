import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class DatabaseExplorer implements vscode.TreeDataProvider<DatabaseItem> {
    private _onDidChangeTreeData: vscode.EventEmitter<DatabaseItem | undefined | null | void> = new vscode.EventEmitter<DatabaseItem | undefined | null | void>();
    readonly onDidChangeTreeData: vscode.Event<DatabaseItem | undefined | null | void> = this._onDidChangeTreeData.event;

    constructor(private context: vscode.ExtensionContext) {}

    refresh(): void {
        this._onDidChangeTreeData.fire();
    }

    getTreeItem(element: DatabaseItem): vscode.TreeItem {
        return element;
    }

    async getChildren(element?: DatabaseItem): Promise<DatabaseItem[]> {
        if (!element) {
            // Racine - afficher les catégories principales
            return [
                new DatabaseItem('Entités', vscode.TreeItemCollapsibleState.Expanded, 'entities'),
                new DatabaseItem('Migrations', vscode.TreeItemCollapsibleState.Expanded, 'migrations'),
                new DatabaseItem('Seeds', vscode.TreeItemCollapsibleState.Expanded, 'seeds')
            ];
        }

        switch (element.contextValue) {
            case 'entities':
                return this.getEntities();
            case 'migrations':
                return this.getMigrations();
            case 'seeds':
                return this.getSeeds();
            default:
                return [];
        }
    }

    private async getEntities(): Promise<DatabaseItem[]> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return [];

        const entitiesPath = path.join(workspaceFolder.uri.fsPath, 'workspace', 'database', 'entities');
        
        try {
            const files = await fs.promises.readdir(entitiesPath);
            return files
                .filter(file => file.endsWith('.php'))
                .map(file => {
                    const entityName = path.basename(file, '.php');
                    const item = new DatabaseItem(
                        entityName,
                        vscode.TreeItemCollapsibleState.None,
                        'entity'
                    );
                    item.resourceUri = vscode.Uri.file(path.join(entitiesPath, file));
                    item.command = {
                        command: 'vscode.open',
                        title: 'Ouvrir',
                        arguments: [item.resourceUri]
                    };
                    item.iconPath = new vscode.ThemeIcon('symbol-class');
                    return item;
                });
        } catch (error) {
            return [];
        }
    }

    private async getMigrations(): Promise<DatabaseItem[]> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return [];

        const migrationsPath = path.join(workspaceFolder.uri.fsPath, 'workspace', 'database', 'migrations');
        
        try {
            const files = await fs.promises.readdir(migrationsPath);
            return files
                .filter(file => file.endsWith('.php'))
                .sort()
                .map(file => {
                    const migrationName = path.basename(file, '.php');
                    const item = new DatabaseItem(
                        migrationName,
                        vscode.TreeItemCollapsibleState.None,
                        'migration'
                    );
                    item.resourceUri = vscode.Uri.file(path.join(migrationsPath, file));
                    item.command = {
                        command: 'vscode.open',
                        title: 'Ouvrir',
                        arguments: [item.resourceUri]
                    };
                    item.iconPath = new vscode.ThemeIcon('database');
                    return item;
                });
        } catch (error) {
            return [];
        }
    }

    private async getSeeds(): Promise<DatabaseItem[]> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return [];

        const seedsPath = path.join(workspaceFolder.uri.fsPath, 'workspace', 'database', 'seeds');
        
        try {
            // Créer le dossier s'il n'existe pas
            await fs.promises.mkdir(seedsPath, { recursive: true });
            
            const files = await fs.promises.readdir(seedsPath);
            return files
                .filter(file => file.endsWith('.php'))
                .map(file => {
                    const seedName = path.basename(file, '.php');
                    const item = new DatabaseItem(
                        seedName,
                        vscode.TreeItemCollapsibleState.None,
                        'seed'
                    );
                    item.resourceUri = vscode.Uri.file(path.join(seedsPath, file));
                    item.command = {
                        command: 'vscode.open',
                        title: 'Ouvrir',
                        arguments: [item.resourceUri]
                    };
                    item.iconPath = new vscode.ThemeIcon('symbol-method');
                    return item;
                });
        } catch (error) {
            return [];
        }
    }
}

class DatabaseItem extends vscode.TreeItem {
    constructor(
        public readonly label: string,
        public readonly collapsibleState: vscode.TreeItemCollapsibleState,
        public readonly contextValue: string
    ) {
        super(label, collapsibleState);
        this.tooltip = `${this.label} - ${this.contextValue}`;
    }
}