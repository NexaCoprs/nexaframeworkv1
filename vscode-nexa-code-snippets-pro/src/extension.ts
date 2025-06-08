import * as vscode from 'vscode';
import { SnippetGenerator } from './snippetGenerator';
import { SnippetProvider } from './snippetProvider';
import { IntelligentSnippets } from './intelligentSnippets';

export function activate(context: vscode.ExtensionContext) {
    console.log('Extension Nexa Code Snippets Pro activée');

    const snippetGenerator = new SnippetGenerator();
    const snippetProvider = new SnippetProvider();
    const intelligentSnippets = new IntelligentSnippets();

    // Commandes principales
    const generateSnippet = vscode.commands.registerCommand('nexa.snippets.generate', async () => {
        await snippetGenerator.generateIntelligentSnippet();
    });

    const insertHandler = vscode.commands.registerCommand('nexa.snippets.insertHandler', async () => {
        await snippetProvider.insertHandlerSnippet();
    });

    const insertEntity = vscode.commands.registerCommand('nexa.snippets.insertEntity', async () => {
        await snippetProvider.insertEntitySnippet();
    });

    const insertMiddleware = vscode.commands.registerCommand('nexa.snippets.insertMiddleware', async () => {
        await snippetProvider.insertMiddlewareSnippet();
    });

    const insertWebSocket = vscode.commands.registerCommand('nexa.snippets.insertWebSocket', async () => {
        await snippetProvider.insertWebSocketSnippet();
    });

    const insertGraphQL = vscode.commands.registerCommand('nexa.snippets.insertGraphQL', async () => {
        await snippetProvider.insertGraphQLSnippet();
    });

    const insertMicroservice = vscode.commands.registerCommand('nexa.snippets.insertMicroservice', async () => {
        await snippetProvider.insertMicroserviceSnippet();
    });

    const insertTest = vscode.commands.registerCommand('nexa.snippets.insertTest', async () => {
        await snippetProvider.insertTestSnippet();
    });

    const insertValidation = vscode.commands.registerCommand('nexa.snippets.insertValidation', async () => {
        await snippetProvider.insertValidationSnippet();
    });

    const insertSecurity = vscode.commands.registerCommand('nexa.snippets.insertSecurity', async () => {
        await snippetProvider.insertSecuritySnippet();
    });

    const insertPerformance = vscode.commands.registerCommand('nexa.snippets.insertPerformance', async () => {
        await snippetProvider.insertPerformanceSnippet();
    });

    // Enregistrement des commandes
    context.subscriptions.push(
        generateSnippet,
        insertHandler,
        insertEntity,
        insertMiddleware,
        insertWebSocket,
        insertGraphQL,
        insertMicroservice,
        insertTest,
        insertValidation,
        insertSecurity,
        insertPerformance
    );

    // Enregistrement du provider de completion
    const completionProvider = vscode.languages.registerCompletionItemProvider(
        ['php', 'nx'],
        intelligentSnippets,
        '.', ':', '$'
    );

    context.subscriptions.push(completionProvider);

    vscode.window.showInformationMessage('Nexa Code Snippets Pro est prêt!');
}

export function deactivate() {
    console.log('Extension Nexa Code Snippets Pro désactivée');
}