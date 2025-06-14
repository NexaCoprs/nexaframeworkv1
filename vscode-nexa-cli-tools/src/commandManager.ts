import * as vscode from 'vscode';
import * as path from 'path';
import { exec } from 'child_process';
import { promisify } from 'util';
import * as fs from 'fs';

const execAsync = promisify(exec);

export interface NexaCommand {
    name: string;
    description: string;
    category: string;
    args?: string[];
    options?: { [key: string]: any };
}

export interface CommandResult {
    success: boolean;
    output: string;
    error?: string;
}

export class CommandManager {
    private workspaceFolder: vscode.WorkspaceFolder | undefined;
    private outputChannel: vscode.OutputChannel;
    private commands: NexaCommand[] = [];

    constructor() {
        this.workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        this.outputChannel = vscode.window.createOutputChannel('Nexa CLI');
        this.initializeCommands();
    }

    private initializeCommands(): void {
        this.commands = [
            {
                name: 'generate:handler',
                description: 'Génère un nouveau Handler',
                category: 'Génération',
                args: ['name']
            },
            {
                name: 'generate:entity',
                description: 'Génère une nouvelle Entité',
                category: 'Génération',
                args: ['name']
            },
            {
                name: 'generate:middleware',
                description: 'Génère un nouveau Middleware',
                category: 'Génération',
                args: ['name']
            },
            {
                name: 'generate:model',
                description: 'Génère un nouveau Modèle',
                category: 'Génération',
                args: ['name']
            },
            {
                name: 'generate:migration',
                description: 'Génère une nouvelle Migration',
                category: 'Base de données',
                args: ['name']
            },
            {
                name: 'generate:seeder',
                description: 'Génère un nouveau Seeder',
                category: 'Base de données',
                args: ['name']
            },
            {
                name: 'migrate',
                description: 'Exécute les migrations',
                category: 'Base de données'
            },
            {
                name: 'migrate:rollback',
                description: 'Annule la dernière migration',
                category: 'Base de données'
            },
            {
                name: 'migrate:reset',
                description: 'Remet à zéro toutes les migrations',
                category: 'Base de données'
            },
            {
                name: 'migrate:refresh',
                description: 'Remet à zéro et re-exécute les migrations',
                category: 'Base de données'
            },
            {
                name: 'seed',
                description: 'Exécute les seeders',
                category: 'Base de données'
            },
            {
                name: 'seed:refresh',
                description: 'Remet à zéro et re-exécute les seeders',
                category: 'Base de données'
            },
            {
                name: 'serve',
                description: 'Démarre le serveur de développement',
                category: 'Serveur',
                options: { port: 8000, host: 'localhost' }
            },
            {
                name: 'test',
                description: 'Exécute les tests',
                category: 'Tests'
            },
            {
                name: 'test:unit',
                description: 'Exécute les tests unitaires',
                category: 'Tests'
            },
            {
                name: 'test:feature',
                description: 'Exécute les tests de fonctionnalités',
                category: 'Tests'
            },
            {
                name: 'cache:clear',
                description: 'Vide le cache',
                category: 'Cache'
            },
            {
                name: 'config:cache',
                description: 'Met en cache la configuration',
                category: 'Cache'
            },
            {
                name: 'route:list',
                description: 'Liste toutes les routes',
                category: 'Routes'
            },
            {
                name: 'make:command',
                description: 'Crée une nouvelle commande console',
                category: 'Console',
                args: ['name']
            }
        ];
    }

    public getCommands(): NexaCommand[] {
        return this.commands;
    }

    public getCommandsByCategory(): { [category: string]: NexaCommand[] } {
        const categorized: { [category: string]: NexaCommand[] } = {};
        
        this.commands.forEach(command => {
            if (!categorized[command.category]) {
                categorized[command.category] = [];
            }
            categorized[command.category].push(command);
        });
        
        return categorized;
    }

    public async executeCommand(commandName: string, args: string[] = [], options: any = {}): Promise<CommandResult> {
        if (!this.workspaceFolder) {
            throw new Error('Aucun workspace ouvert');
        }

        const config = vscode.workspace.getConfiguration('nexa');
        const cliPath = config.get<string>('cliPath', './nexa');
        
        let fullCommand = `${cliPath} ${commandName}`;
        
        if (args.length > 0) {
            fullCommand += ` ${args.join(' ')}`;
        }

        // Ajouter les options
        Object.keys(options).forEach(key => {
            if (options[key] !== undefined) {
                fullCommand += ` --${key}=${options[key]}`;
            }
        });

        this.outputChannel.appendLine(`Exécution: ${fullCommand}`);
        this.outputChannel.show();

        try {
            const { stdout, stderr } = await execAsync(fullCommand, {
                cwd: this.workspaceFolder.uri.fsPath,
                timeout: 30000 // 30 secondes timeout
            });

            const output = stdout || stderr;
            this.outputChannel.appendLine(output);

            return {
                success: true,
                output: output
            };
        } catch (error: any) {
            const errorMessage = error.message || error.toString();
            this.outputChannel.appendLine(`Erreur: ${errorMessage}`);
            
            return {
                success: false,
                output: '',
                error: errorMessage
            };
        }
    }

    public async showCommandPalette(): Promise<void> {
        const categorized = this.getCommandsByCategory();
        const items: vscode.QuickPickItem[] = [];

        Object.keys(categorized).forEach(category => {
            items.push({
                label: `$(folder) ${category}`,
                kind: vscode.QuickPickItemKind.Separator
            });

            categorized[category].forEach(command => {
                items.push({
                    label: `$(terminal) ${command.name}`,
                    description: command.description,
                    detail: command.args ? `Arguments: ${command.args.join(', ')}` : undefined
                });
            });
        });

        const selected = await vscode.window.showQuickPick(items, {
            placeHolder: 'Sélectionnez une commande Nexa',
            matchOnDescription: true,
            matchOnDetail: true
        });

        if (selected && selected.label.startsWith('$(terminal)')) {
            const commandName = selected.label.replace('$(terminal) ', '');
            await this.executeCommandWithPrompts(commandName);
        }
    }

    private async executeCommandWithPrompts(commandName: string): Promise<void> {
        const command = this.commands.find(cmd => cmd.name === commandName);
        if (!command) {
            vscode.window.showErrorMessage(`Commande inconnue: ${commandName}`);
            return;
        }

        const args: string[] = [];
        
        // Demander les arguments requis
        if (command.args) {
            for (const arg of command.args) {
                const value = await vscode.window.showInputBox({
                    prompt: `Entrez la valeur pour ${arg}`,
                    placeHolder: arg
                });
                
                if (!value) {
                    return; // Annulé par l'utilisateur
                }
                
                args.push(value);
            }
        }

        // Exécuter la commande
        const result = await this.executeCommand(commandName, args);
        
        if (result.success) {
            vscode.window.showInformationMessage(`Commande exécutée avec succès: ${commandName}`);
        } else {
            vscode.window.showErrorMessage(`Erreur lors de l'exécution: ${result.error}`);
        }
    }

    public async checkNexaInstallation(): Promise<boolean> {
        if (!this.workspaceFolder) {
            return false;
        }

        const nexaPath = path.join(this.workspaceFolder.uri.fsPath, 'nexa');
        const nexaBatPath = path.join(this.workspaceFolder.uri.fsPath, 'nexa.bat');
        
        try {
            return fs.existsSync(nexaPath) || fs.existsSync(nexaBatPath);
        } catch {
            return false;
        }
    }

    public async getProjectInfo(): Promise<any> {
        if (!this.workspaceFolder) {
            return null;
        }

        try {
            const result = await this.executeCommand('--version');
            return {
                hasNexa: result.success,
                version: result.output
            };
        } catch {
            return {
                hasNexa: false,
                version: null
            };
        }
    }

    public dispose(): void {
        this.outputChannel.dispose();
    }
}