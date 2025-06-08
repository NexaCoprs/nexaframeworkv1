import * as vscode from 'vscode';
import * as path from 'path';
import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);

export function activate(context: vscode.ExtensionContext) {
    console.log('Extension Nexa CLI Tools activée');

    // Vérifier si c'est un projet Nexa
    const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
    if (workspaceFolder) {
        const nexaPath = path.join(workspaceFolder.uri.fsPath, 'nexa');
        vscode.commands.executeCommand('setContext', 'workspaceHasNexaProject', true);
    }

    // Commande pour générer un Handler
    const generateHandler = vscode.commands.registerCommand('nexa.generateHandler', async () => {
        const name = await vscode.window.showInputBox({
            prompt: 'Nom du Handler (ex: UserHandler)',
            placeHolder: 'UserHandler'
        });

        if (name) {
            await executeNexaCommand(`generate:handler ${name}`);
        }
    });

    // Commande pour générer une Entité
    const generateEntity = vscode.commands.registerCommand('nexa.generateEntity', async () => {
        const name = await vscode.window.showInputBox({
            prompt: 'Nom de l\'Entité (ex: User)',
            placeHolder: 'User'
        });

        if (name) {
            await executeNexaCommand(`generate:entity ${name}`);
        }
    });

    // Commande pour générer un Middleware
    const generateMiddleware = vscode.commands.registerCommand('nexa.generateMiddleware', async () => {
        const name = await vscode.window.showInputBox({
            prompt: 'Nom du Middleware (ex: AuthMiddleware)',
            placeHolder: 'AuthMiddleware'
        });

        if (name) {
            await executeNexaCommand(`generate:middleware ${name}`);
        }
    });

    // Commande pour exécuter les migrations
    const runMigration = vscode.commands.registerCommand('nexa.runMigration', async () => {
        const options = ['migrate', 'migrate:rollback', 'migrate:reset', 'migrate:refresh'];
        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Sélectionnez une action de migration'
        });

        if (selected) {
            await executeNexaCommand(selected);
        }
    });

    // Commande pour exécuter les seeds
    const runSeed = vscode.commands.registerCommand('nexa.runSeed', async () => {
        const options = ['seed', 'seed:refresh'];
        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Sélectionnez une action de seed'
        });

        if (selected) {
            await executeNexaCommand(selected);
        }
    });

    // Commande pour ouvrir le terminal Nexa
    const openTerminal = vscode.commands.registerCommand('nexa.openTerminal', () => {
        const terminal = vscode.window.createTerminal({
            name: 'Nexa CLI',
            cwd: vscode.workspace.workspaceFolders?.[0].uri.fsPath
        });
        terminal.show();
        terminal.sendText('./nexa --help');
    });

    context.subscriptions.push(
        generateHandler,
        generateEntity,
        generateMiddleware,
        runMigration,
        runSeed,
        openTerminal
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

export function deactivate() {}