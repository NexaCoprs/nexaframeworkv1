"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.deactivate = exports.activate = void 0;
const vscode = require("vscode");
const apiTesterPanel_1 = require("./apiTesterPanel");
const requestManager_1 = require("./requestManager");
const themeManager_1 = require("./themeManager");
const testRunner_1 = require("./testRunner");
const postmanExporter_1 = require("./postmanExporter");
function activate(context) {
    console.log('Extension Nexa API Tester activée');
    const requestManager = new requestManager_1.RequestManager(context);
    const themeManager = new themeManager_1.ThemeManager(context);
    const testRunner = new testRunner_1.TestRunner(requestManager);
    const postmanExporter = new postmanExporter_1.PostmanExporter();
    // Charger les thèmes personnalisés
    themeManager.loadCustomThemes();
    // Commande pour ouvrir l'API Tester
    const openApiTesterCommand = vscode.commands.registerCommand('nexa-api-tester.open', () => {
        apiTesterPanel_1.ApiTesterPanel.createOrShow(context.extensionUri, requestManager, themeManager);
    });
    // Commande pour créer une nouvelle requête
    const newRequestCommand = vscode.commands.registerCommand('nexa-api-tester.newRequest', async () => {
        const method = await vscode.window.showQuickPick(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'], { placeHolder: 'Sélectionnez la méthode HTTP' });
        if (method) {
            const url = await vscode.window.showInputBox({
                prompt: 'Entrez l\'URL de la requête',
                placeHolder: 'https://api.example.com/endpoint'
            });
            if (url) {
                const request = {
                    id: requestManager.generateId(),
                    name: `${method} ${new URL(url).pathname}`,
                    method: method,
                    url,
                    headers: {},
                    body: method !== 'GET' && method !== 'HEAD' ? '{}' : undefined
                };
                await requestManager.saveRequest(request);
                apiTesterPanel_1.ApiTesterPanel.createOrShow(context.extensionUri, requestManager, themeManager);
                vscode.window.showInformationMessage(`Requête ${method} créée avec succès`);
            }
        }
    });
    // Commande pour envoyer une requête
    const sendRequestCommand = vscode.commands.registerCommand('nexa.api.sendRequest', async () => {
        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showErrorMessage('Aucun fichier ouvert');
            return;
        }
        const selection = editor.selection;
        const text = editor.document.getText(selection.isEmpty ? undefined : selection);
        try {
            const result = await requestManager.sendRequest(text);
            vscode.window.showInformationMessage(`Requête envoyée: ${result.status}`);
        }
        catch (error) {
            vscode.window.showErrorMessage(`Erreur: ${error}`);
        }
    });
    // Commande pour tester un endpoint
    const testEndpointCommand = vscode.commands.registerCommand('nexa.api.testEndpoint', async () => {
        const endpoint = await vscode.window.showInputBox({
            prompt: 'URL de l\'endpoint à tester',
            placeHolder: 'https://api.example.com/endpoint'
        });
        if (endpoint) {
            try {
                const results = await testRunner.runTests(endpoint);
                vscode.window.showInformationMessage(`Tests terminés: ${results.passed}/${results.total} réussis`);
            }
            catch (error) {
                vscode.window.showErrorMessage(`Erreur lors des tests: ${error}`);
            }
        }
    });
    // Commande pour créer une collection
    const createCollectionCommand = vscode.commands.registerCommand('nexa.api.createCollection', async () => {
        const name = await vscode.window.showInputBox({
            prompt: 'Nom de la collection',
            placeHolder: 'Ma Collection API'
        });
        if (name) {
            requestManager.createCollection(name);
            vscode.window.showInformationMessage(`Collection "${name}" créée`);
        }
    });
    // Commande pour exporter vers Postman
    const exportPostmanCommand = vscode.commands.registerCommand('nexa.api.exportPostman', async () => {
        try {
            const collections = requestManager.getCollections();
            if (collections.length === 0) {
                vscode.window.showWarningMessage('Aucune collection à exporter');
                return;
            }
            // For now, export the first collection or create a combined one
            const collection = collections[0] || { id: 'combined', name: 'Combined Collection', requests: [], createdAt: new Date().toISOString() };
            const exported = postmanExporter.exportCollection(collection);
            const uri = await vscode.window.showSaveDialog({
                defaultUri: vscode.Uri.file('nexa-api-collection.json'),
                filters: {
                    'JSON': ['json']
                }
            });
            if (uri) {
                await vscode.workspace.fs.writeFile(uri, Buffer.from(JSON.stringify(exported, null, 2)));
                vscode.window.showInformationMessage('Collection exportée vers Postman');
            }
        }
        catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'export: ${error}`);
        }
    });
    // Commande pour importer une collection Postman
    const importPostmanCommand = vscode.commands.registerCommand('nexa-api-tester.importPostman', async () => {
        const fileUri = await vscode.window.showOpenDialog({
            canSelectFiles: true,
            canSelectFolders: false,
            canSelectMany: false,
            filters: {
                'Collections Postman': ['json']
            }
        });
        if (fileUri && fileUri[0]) {
            try {
                const fileContent = await vscode.workspace.fs.readFile(fileUri[0]);
                const jsonContent = JSON.parse(fileContent.toString());
                await requestManager.importCollections(JSON.stringify(jsonContent));
                vscode.window.showInformationMessage('Collection Postman importée avec succès');
                apiTesterPanel_1.ApiTesterPanel.createOrShow(context.extensionUri, requestManager, themeManager);
            }
            catch (error) {
                vscode.window.showErrorMessage(`Erreur lors de l'importation: ${error}`);
            }
        }
    });
    // Commande pour changer de thème
    const changeThemeCommand = vscode.commands.registerCommand('nexa-api-tester.changeTheme', async () => {
        const themes = themeManager.getAvailableThemes();
        const themeItems = themes.map(theme => ({
            label: theme.displayName,
            description: theme.description,
            detail: theme.name
        }));
        const selected = await vscode.window.showQuickPick(themeItems, {
            placeHolder: 'Sélectionnez un thème pour l\'API Tester'
        });
        if (selected) {
            await themeManager.setTheme(selected.detail);
            vscode.window.showInformationMessage(`Thème '${selected.label}' appliqué`);
        }
    });
    // Commande pour exécuter des tests automatiques
    const runTestsCommand = vscode.commands.registerCommand('nexa-api-tester.runTests', async () => {
        const url = await vscode.window.showInputBox({
            prompt: 'Entrez l\'URL à tester',
            placeHolder: 'https://api.example.com/endpoint'
        });
        if (url) {
            try {
                vscode.window.showInformationMessage('Exécution des tests en cours...');
                const results = await testRunner.runTests(url);
                const message = `Tests terminés: ${results.passed}/${results.total} réussis en ${results.duration}ms`;
                if (results.failed > 0) {
                    const action = await vscode.window.showWarningMessage(message, 'Voir les détails');
                    if (action === 'Voir les détails') {
                        // Ouvrir l'API Tester avec les résultats
                        apiTesterPanel_1.ApiTesterPanel.createOrShow(context.extensionUri, requestManager, themeManager);
                    }
                }
                else {
                    vscode.window.showInformationMessage(message);
                }
            }
            catch (error) {
                vscode.window.showErrorMessage(`Erreur lors des tests: ${error}`);
            }
        }
    });
    // Commande pour exporter un thème
    const exportThemeCommand = vscode.commands.registerCommand('nexa-api-tester.exportTheme', async () => {
        const themes = themeManager.getAvailableThemes();
        const themeItems = themes.map(theme => ({
            label: theme.displayName,
            description: theme.description,
            detail: theme.name
        }));
        const selected = await vscode.window.showQuickPick(themeItems, {
            placeHolder: 'Sélectionnez un thème à exporter'
        });
        if (selected) {
            try {
                const themeJson = themeManager.exportTheme(selected.detail);
                const doc = await vscode.workspace.openTextDocument({
                    content: themeJson,
                    language: 'json'
                });
                await vscode.window.showTextDocument(doc);
                vscode.window.showInformationMessage('Thème exporté avec succès');
            }
            catch (error) {
                vscode.window.showErrorMessage(`Erreur lors de l'exportation: ${error}`);
            }
        }
    });
    // Commande pour importer un thème
    const importThemeCommand = vscode.commands.registerCommand('nexa-api-tester.importTheme', async () => {
        const fileUri = await vscode.window.showOpenDialog({
            canSelectFiles: true,
            canSelectFolders: false,
            canSelectMany: false,
            filters: {
                'Thèmes JSON': ['json']
            }
        });
        if (fileUri && fileUri[0]) {
            try {
                const fileContent = await vscode.workspace.fs.readFile(fileUri[0]);
                const themeJson = fileContent.toString();
                const themeName = await themeManager.importTheme(themeJson);
                vscode.window.showInformationMessage(`Thème '${themeName}' importé avec succès`);
            }
            catch (error) {
                vscode.window.showErrorMessage(`Erreur lors de l'importation: ${error}`);
            }
        }
    });
    // Commande pour générer des tests automatiques
    const generateTestsCommand = vscode.commands.registerCommand('nexa.api.generateTests', async () => {
        const endpoint = await vscode.window.showInputBox({
            prompt: 'URL de l\'endpoint pour générer les tests',
            placeHolder: 'https://api.example.com/endpoint'
        });
        if (endpoint) {
            try {
                const tests = await testRunner.generateTests(endpoint);
                const doc = await vscode.workspace.openTextDocument({
                    content: tests,
                    language: 'javascript'
                });
                await vscode.window.showTextDocument(doc);
            }
            catch (error) {
                vscode.window.showErrorMessage(`Erreur lors de la génération: ${error}`);
            }
        }
    });
    context.subscriptions.push(openApiTesterCommand, newRequestCommand, importPostmanCommand, changeThemeCommand, runTestsCommand, generateTestsCommand, exportThemeCommand, importThemeCommand, themeManager);
}
exports.activate = activate;
function deactivate() {
    console.log('Extension Nexa API Tester désactivée');
}
exports.deactivate = deactivate;
//# sourceMappingURL=extension.js.map