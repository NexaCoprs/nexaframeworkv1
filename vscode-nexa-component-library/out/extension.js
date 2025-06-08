"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.deactivate = exports.activate = void 0;
const vscode = require("vscode");
const componentLibraryProvider_1 = require("./componentLibraryProvider");
const componentPreviewPanel_1 = require("./componentPreviewPanel");
const componentManager_1 = require("./componentManager");
let componentLibraryProvider;
let componentManager;
function activate(context) {
    console.log('Nexa Component Library extension is now active!');
    // Initialize component manager
    componentManager = new componentManager_1.ComponentManager(context);
    // Initialize component library provider
    componentLibraryProvider = new componentLibraryProvider_1.ComponentLibraryProvider(context, componentManager);
    // Register tree data provider
    vscode.window.registerTreeDataProvider('nexaComponentLibrary', componentLibraryProvider);
    // Register commands
    const commands = [
        vscode.commands.registerCommand('nexa.componentLibrary.open', () => {
            componentPreviewPanel_1.ComponentPreviewPanel.createOrShow(context.extensionUri, componentManager);
        }),
        vscode.commands.registerCommand('nexa.componentLibrary.insertComponent', (component) => {
            insertComponentAtCursor(component);
        }),
        vscode.commands.registerCommand('nexa.componentLibrary.previewComponent', (component) => {
            componentPreviewPanel_1.ComponentPreviewPanel.createOrShow(context.extensionUri, componentManager, component);
        }),
        vscode.commands.registerCommand('nexa.componentLibrary.createComponent', async () => {
            await createNewComponent();
        }),
        vscode.commands.registerCommand('nexa.componentLibrary.refreshLibrary', () => {
            componentLibraryProvider.refresh();
        })
    ];
    commands.forEach(command => context.subscriptions.push(command));
    // Set context for when extension is active
    vscode.commands.executeCommand('setContext', 'nexaProject', true);
    // Watch for file changes in component library
    const watcher = vscode.workspace.createFileSystemWatcher('**/workspace/interface/components/**/*.nx');
    watcher.onDidChange(() => componentLibraryProvider.refresh());
    watcher.onDidCreate(() => componentLibraryProvider.refresh());
    watcher.onDidDelete(() => componentLibraryProvider.refresh());
    context.subscriptions.push(watcher);
}
exports.activate = activate;
async function insertComponentAtCursor(component) {
    const editor = vscode.window.activeTextEditor;
    if (!editor) {
        vscode.window.showErrorMessage('Aucun éditeur actif trouvé');
        return;
    }
    const componentCode = await componentManager.getComponentCode(component.path);
    if (componentCode) {
        const position = editor.selection.active;
        editor.edit(editBuilder => {
            editBuilder.insert(position, componentCode);
        });
    }
}
async function createNewComponent() {
    const componentName = await vscode.window.showInputBox({
        placeHolder: 'Nom du composant (ex: MyButton)',
        prompt: 'Entrez le nom du nouveau composant'
    });
    if (!componentName) {
        return;
    }
    const componentType = await vscode.window.showQuickPick([
        { label: 'Button', description: 'Composant bouton' },
        { label: 'Card', description: 'Composant carte' },
        { label: 'Form', description: 'Composant formulaire' },
        { label: 'Layout', description: 'Composant de mise en page' },
        { label: 'Custom', description: 'Composant personnalisé' }
    ], {
        placeHolder: 'Sélectionnez le type de composant'
    });
    if (!componentType) {
        return;
    }
    await componentManager.createComponent(componentName, componentType.label);
    componentLibraryProvider.refresh();
    vscode.window.showInformationMessage(`Composant ${componentName} créé avec succès!`);
}
function deactivate() {
    console.log('Nexa Component Library extension is now deactivated');
}
exports.deactivate = deactivate;
//# sourceMappingURL=extension.js.map