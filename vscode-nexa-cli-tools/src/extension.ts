import * as vscode from 'vscode';
import * as path from 'path';
import { exec } from 'child_process';
import { promisify } from 'util';
import { CommandManager } from './commandManager';
import { TerminalManager } from './terminalManager';
import { ProjectScaffolder } from './projectScaffolder';

const execAsync = promisify(exec);

export function activate(context: vscode.ExtensionContext) {
    console.log('Extension Nexa CLI Tools activée');

    // Initialiser les managers
    const commandManager = new CommandManager();
    const terminalManager = new TerminalManager();
    const projectScaffolder = new ProjectScaffolder();

    // Vérifier si c'est un projet Nexa
    const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
    if (workspaceFolder) {
        const nexaPath = path.join(workspaceFolder.uri.fsPath, 'nexa');
        vscode.commands.executeCommand('setContext', 'workspaceHasNexaProject', true);
    }

    // Commandes principales
    const showCommandPalette = vscode.commands.registerCommand('nexa.showCommandPalette', async () => {
        await commandManager.showCommandPalette();
    });

    const createProject = vscode.commands.registerCommand('nexa.createProject', async () => {
        await projectScaffolder.createProject();
    });

    // Commandes de génération
    const generateHandler = vscode.commands.registerCommand('nexa.generateHandler', async () => {
        const name = await vscode.window.showInputBox({
            prompt: 'Nom du Handler (ex: UserHandler)',
            placeHolder: 'UserHandler'
        });

        if (name) {
            const result = await commandManager.executeCommand('generate:handler', [name]);
            if (result.success) {
                vscode.window.showInformationMessage(`Handler ${name} créé avec succès`);
            } else {
                vscode.window.showErrorMessage(`Erreur: ${result.error}`);
            }
        }
    });

    const generateEntity = vscode.commands.registerCommand('nexa.generateEntity', async () => {
        const name = await vscode.window.showInputBox({
            prompt: 'Nom de l\'Entité (ex: User)',
            placeHolder: 'User'
        });

        if (name) {
            const result = await commandManager.executeCommand('generate:entity', [name]);
            if (result.success) {
                vscode.window.showInformationMessage(`Entité ${name} créée avec succès`);
            } else {
                vscode.window.showErrorMessage(`Erreur: ${result.error}`);
            }
        }
    });

    const generateMiddleware = vscode.commands.registerCommand('nexa.generateMiddleware', async () => {
        const name = await vscode.window.showInputBox({
            prompt: 'Nom du Middleware (ex: AuthMiddleware)',
            placeHolder: 'AuthMiddleware'
        });

        if (name) {
            const result = await commandManager.executeCommand('generate:middleware', [name]);
            if (result.success) {
                vscode.window.showInformationMessage(`Middleware ${name} créé avec succès`);
            } else {
                vscode.window.showErrorMessage(`Erreur: ${result.error}`);
            }
        }
    });

    // Commandes de base de données
    const runMigration = vscode.commands.registerCommand('nexa.runMigration', async () => {
        const options = [
            { label: 'Exécuter les migrations', value: 'migrate' },
            { label: 'Annuler la dernière migration', value: 'migrate:rollback' },
            { label: 'Remettre à zéro les migrations', value: 'migrate:reset' },
            { label: 'Actualiser les migrations', value: 'migrate:refresh' }
        ];
        
        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Sélectionnez une action de migration'
        });

        if (selected) {
            const result = await commandManager.executeCommand(selected.value);
            if (result.success) {
                vscode.window.showInformationMessage('Migration exécutée avec succès');
            } else {
                vscode.window.showErrorMessage(`Erreur: ${result.error}`);
            }
        }
    });

    const runSeed = vscode.commands.registerCommand('nexa.runSeed', async () => {
        const options = [
            { label: 'Exécuter les seeders', value: 'seed' },
            { label: 'Actualiser les seeders', value: 'seed:refresh' }
        ];
        
        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Sélectionnez une action de seed'
        });

        if (selected) {
            const result = await commandManager.executeCommand(selected.value);
            if (result.success) {
                vscode.window.showInformationMessage('Seeders exécutés avec succès');
            } else {
                vscode.window.showErrorMessage(`Erreur: ${result.error}`);
            }
        }
    });

    // Commandes de terminal
    const openTerminal = vscode.commands.registerCommand('nexa.openTerminal', () => {
        terminalManager.createNexaTerminal('nexa');
    });

    const openTerminalWithCommand = vscode.commands.registerCommand('nexa.openTerminalWithCommand', async () => {
        await terminalManager.openTerminalWithCommand();
    });

    const startServer = vscode.commands.registerCommand('nexa.startServer', async () => {
        const port = await vscode.window.showInputBox({
            prompt: 'Port du serveur (optionnel)',
            placeHolder: '8000'
        });
        
        terminalManager.startServer(port ? parseInt(port) : undefined);
    });

    const stopServer = vscode.commands.registerCommand('nexa.stopServer', () => {
        terminalManager.stopServer();
        vscode.window.showInformationMessage('Serveur arrêté');
    });

    const runTests = vscode.commands.registerCommand('nexa.runTests', async () => {
        const testTypes = [
            { label: 'Tous les tests', value: 'all' as const },
            { label: 'Tests unitaires', value: 'unit' as const },
            { label: 'Tests de fonctionnalités', value: 'feature' as const }
        ];
        
        const selected = await vscode.window.showQuickPick(testTypes, {
            placeHolder: 'Type de tests à exécuter'
        });
        
        if (selected) {
            terminalManager.runTests(selected.value);
        }
    });

    const sendCustomCommand = vscode.commands.registerCommand('nexa.sendCustomCommand', async () => {
        await terminalManager.sendCustomCommand();
    });

    const showTerminals = vscode.commands.registerCommand('nexa.showTerminals', () => {
        terminalManager.showTerminalSelector();
    });

    // Enregistrement des commandes
    context.subscriptions.push(
        showCommandPalette,
        createProject,
        generateHandler,
        generateEntity,
        generateMiddleware,
        runMigration,
        runSeed,
        openTerminal,
        openTerminalWithCommand,
        startServer,
        stopServer,
        runTests,
        sendCustomCommand,
        showTerminals
    );

    // Enregistrement des managers pour nettoyage
    context.subscriptions.push(
        { dispose: () => commandManager.dispose() },
        { dispose: () => terminalManager.dispose() },
        { dispose: () => projectScaffolder.dispose() }
    );

    // Autocomplétion pour les commandes Nexa
    const completionProvider = vscode.languages.registerCompletionItemProvider(
        { scheme: 'file', language: 'shellscript' },
        {
            provideCompletionItems(document: vscode.TextDocument, position: vscode.Position) {
                const linePrefix = document.lineAt(position).text.substr(0, position.character);
                
                if (!linePrefix.includes('./nexa')) {
                    return undefined;
                }

                const completions = [
                    new vscode.CompletionItem('generate:handler', vscode.CompletionItemKind.Method),
                    new vscode.CompletionItem('generate:entity', vscode.CompletionItemKind.Method),
                    new vscode.CompletionItem('generate:middleware', vscode.CompletionItemKind.Method),
                    new vscode.CompletionItem('migrate', vscode.CompletionItemKind.Method),
                    new vscode.CompletionItem('migrate:rollback', vscode.CompletionItemKind.Method),
                    new vscode.CompletionItem('migrate:reset', vscode.CompletionItemKind.Method),
                    new vscode.CompletionItem('seed', vscode.CompletionItemKind.Method),
                    new vscode.CompletionItem('serve', vscode.CompletionItemKind.Method),
                    new vscode.CompletionItem('test', vscode.CompletionItemKind.Method)
                ];

                return completions;
            }
        },
        ' '
    );

    context.subscriptions.push(completionProvider);
}

async function executeNexaCommand(command: string) {
    const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
    if (!workspaceFolder) {
        vscode.window.showErrorMessage('Aucun workspace ouvert');
        return;
    }

    const config = vscode.workspace.getConfiguration('nexa');
    const cliPath = config.get<string>('cliPath', './nexa');
    const fullCommand = `${cliPath} ${command}`;

    try {
        vscode.window.showInformationMessage(`Exécution: ${fullCommand}`);
        
        const { stdout, stderr } = await execAsync(fullCommand, {
            cwd: workspaceFolder.uri.fsPath
        });

        if (stdout) {
            vscode.window.showInformationMessage(`Succès: ${stdout}`);
        }
        
        if (stderr) {
            vscode.window.showWarningMessage(`Avertissement: ${stderr}`);
        }
    } catch (error) {
        vscode.window.showErrorMessage(`Erreur: ${error}`);
    }
}

export function deactivate() {
    console.log('Extension Nexa CLI Tools désactivée');
}