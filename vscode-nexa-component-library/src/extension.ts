import * as vscode from 'vscode';
import { ComponentLibraryProvider } from './componentLibraryProvider';
import { ComponentPreviewPanel } from './componentPreviewPanel';
import { ComponentManager } from './componentManager';

let componentLibraryProvider: ComponentLibraryProvider;
let componentManager: ComponentManager;

export function activate(context: vscode.ExtensionContext) {
    console.log('Nexa Component Library extension is now active!');

    // Initialize component manager
    componentManager = new ComponentManager(context);
    
    // Initialize component library provider
    componentLibraryProvider = new ComponentLibraryProvider(context, componentManager);
    
    // Register tree data provider
    vscode.window.registerTreeDataProvider('nexaComponentLibrary', componentLibraryProvider);

    // Register commands
    const commands = [
        vscode.commands.registerCommand('nexa.componentLibrary.open', () => {
            ComponentPreviewPanel.createOrShow(context.extensionUri, componentManager);
        }),
        
        vscode.commands.registerCommand('nexa.componentLibrary.insertComponent', (component) => {
            insertComponentAtCursor(component);
        }),
        
        vscode.commands.registerCommand('nexa.componentLibrary.previewComponent', (component) => {
            ComponentPreviewPanel.createOrShow(context.extensionUri, componentManager, component);
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

async function insertComponentAtCursor(component: any) {
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

export function deactivate() {
    console.log('Nexa Component Library extension is now deactivated');
}