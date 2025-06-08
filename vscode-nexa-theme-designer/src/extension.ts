import * as vscode from 'vscode';
import { ThemeDesigner } from './themeDesigner';
import { ColorPalette } from './colorPalette';
import { ThemePreview } from './themePreview';
import { ThemeExporter } from './themeExporter';

export function activate(context: vscode.ExtensionContext) {
    console.log('Extension Nexa Theme Designer activée');

    const themeDesigner = new ThemeDesigner(context);
    const colorPalette = new ColorPalette(context);
    const themePreview = new ThemePreview(context);
    const themeExporter = new ThemeExporter(context);

    // Commandes principales
    const createTheme = vscode.commands.registerCommand('nexa.theme.create', async () => {
        await themeDesigner.createNewTheme();
    });

    const editTheme = vscode.commands.registerCommand('nexa.theme.edit', async () => {
        await themeDesigner.editExistingTheme();
    });

    const previewTheme = vscode.commands.registerCommand('nexa.theme.preview', async () => {
        await themePreview.showPreview();
    });

    const exportTheme = vscode.commands.registerCommand('nexa.theme.export', async () => {
        await themeExporter.exportTheme();
    });

    const importTheme = vscode.commands.registerCommand('nexa.theme.import', async () => {
        await themeDesigner.importTheme();
    });

    const duplicateTheme = vscode.commands.registerCommand('nexa.theme.duplicate', async () => {
        await themeDesigner.duplicateTheme();
    });

    const deleteTheme = vscode.commands.registerCommand('nexa.theme.delete', async () => {
        await themeDesigner.deleteTheme();
    });

    const openColorPicker = vscode.commands.registerCommand('nexa.theme.colorPicker', async () => {
        await colorPalette.openColorPicker();
    });

    const generatePalette = vscode.commands.registerCommand('nexa.theme.generatePalette', async () => {
        await colorPalette.generateColorPalette();
    });

    const applyTemplate = vscode.commands.registerCommand('nexa.theme.applyTemplate', async () => {
        await themeDesigner.applyTemplate();
    });

    const customizeColors = vscode.commands.registerCommand('nexa.theme.customizeColors', async () => {
        await themeDesigner.customizeColors();
    });

    const customizeTokens = vscode.commands.registerCommand('nexa.theme.customizeTokens', async () => {
        await themeDesigner.customizeTokens();
    });

    const customizeUI = vscode.commands.registerCommand('nexa.theme.customizeUI', async () => {
        await themeDesigner.customizeUI();
    });

    const validateTheme = vscode.commands.registerCommand('nexa.theme.validate', async () => {
        await themeDesigner.validateTheme();
    });

    const shareTheme = vscode.commands.registerCommand('nexa.theme.share', async () => {
        await themeExporter.shareTheme();
    });

    const publishTheme = vscode.commands.registerCommand('nexa.theme.publish', async () => {
        await themeExporter.publishTheme();
    });

    // Enregistrement des commandes
    context.subscriptions.push(
        createTheme,
        editTheme,
        previewTheme,
        exportTheme,
        importTheme,
        duplicateTheme,
        deleteTheme,
        openColorPicker,
        generatePalette,
        applyTemplate,
        customizeColors,
        customizeTokens,
        customizeUI,
        validateTheme,
        shareTheme,
        publishTheme
    );

    // Theme Explorer
    const themeExplorer = new ThemeExplorerProvider(context);
    vscode.window.createTreeView('nexaThemeExplorer', {
        treeDataProvider: themeExplorer,
        showCollapseAll: true
    });

    // Color Palette Panel
    const colorPaletteProvider = new ColorPaletteProvider(context);
    vscode.window.registerWebviewViewProvider('nexaColorPalette', colorPaletteProvider);

    // Theme Preview Panel
    const themePreviewProvider = new ThemePreviewProvider(context);
    vscode.window.registerWebviewViewProvider('nexaThemePreview', themePreviewProvider);

    // Status bar pour le thème actuel
    const themeStatusBar = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Right, 100);
    themeStatusBar.command = 'nexa.theme.edit';
    themeStatusBar.text = '$(paintcan) Thème';
    themeStatusBar.tooltip = 'Cliquer pour éditer le thème';
    themeStatusBar.show();
    context.subscriptions.push(themeStatusBar);

    // Listener pour les changements de configuration
    const configChangeListener = vscode.workspace.onDidChangeConfiguration((event) => {
        if (event.affectsConfiguration('workbench.colorTheme')) {
            themeStatusBar.text = `$(paintcan) ${vscode.workspace.getConfiguration().get('workbench.colorTheme')}`;
        }
    });

    context.subscriptions.push(configChangeListener);

    // Hover provider pour les couleurs
    const colorHoverProvider = vscode.languages.registerHoverProvider(
        ['json', 'jsonc'],
        new ColorHoverProvider()
    );

    context.subscriptions.push(colorHoverProvider);

    vscode.window.showInformationMessage('Nexa Theme Designer est prêt!');
}

export function deactivate() {
    console.log('Extension Nexa Theme Designer désactivée');
}

class ThemeExplorerProvider implements vscode.TreeDataProvider<ThemeItem> {
    private _onDidChangeTreeData: vscode.EventEmitter<ThemeItem | undefined | null | void> = new vscode.EventEmitter<ThemeItem | undefined | null | void>();
    readonly onDidChangeTreeData: vscode.Event<ThemeItem | undefined | null | void> = this._onDidChangeTreeData.event;

    constructor(private context: vscode.ExtensionContext) {}

    refresh(): void {
        this._onDidChangeTreeData.fire();
    }

    getTreeItem(element: ThemeItem): vscode.TreeItem {
        return element;
    }

    getChildren(element?: ThemeItem): Thenable<ThemeItem[]> {
        if (!element) {
            return Promise.resolve([
                new ThemeItem('Thèmes Installés', vscode.TreeItemCollapsibleState.Expanded, 'installed'),
                new ThemeItem('Mes Thèmes', vscode.TreeItemCollapsibleState.Expanded, 'custom'),
                new ThemeItem('Modèles', vscode.TreeItemCollapsibleState.Collapsed, 'templates')
            ]);
        }
        return Promise.resolve([]);
    }
}

class ThemeItem extends vscode.TreeItem {
    constructor(
        public readonly label: string,
        public readonly collapsibleState: vscode.TreeItemCollapsibleState,
        public readonly type: string
    ) {
        super(label, collapsibleState);
        this.tooltip = `${this.label} - ${this.type}`;
        this.description = this.type;
        this.contextValue = 'themeItem';
    }

    iconPath = new vscode.ThemeIcon('paintcan');
}

class ColorPaletteProvider implements vscode.WebviewViewProvider {
    constructor(private context: vscode.ExtensionContext) {}

    resolveWebviewView(
        webviewView: vscode.WebviewView,
        context: vscode.WebviewViewResolveContext,
        token: vscode.CancellationToken
    ): void | Thenable<void> {
        webviewView.webview.options = {
            enableScripts: true,
            localResourceRoots: [this.context.extensionUri]
        };

        webviewView.webview.html = this.getColorPaletteHtml();

        webviewView.webview.onDidReceiveMessage((message) => {
            switch (message.command) {
                case 'colorSelected':
                    vscode.window.showInformationMessage(`Couleur sélectionnée: ${message.color}`);
                    break;
            }
        });
    }

    private getColorPaletteHtml(): string {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: var(--vscode-font-family); }
                    .color-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
                    .color-item { width: 40px; height: 40px; border-radius: 4px; cursor: pointer; }
                </style>
            </head>
            <body>
                <h3>Palette de Couleurs</h3>
                <div class="color-grid">
                    <div class="color-item" style="background: #ff0000" onclick="selectColor('#ff0000')"></div>
                    <div class="color-item" style="background: #00ff00" onclick="selectColor('#00ff00')"></div>
                    <div class="color-item" style="background: #0000ff" onclick="selectColor('#0000ff')"></div>
                    <div class="color-item" style="background: #ffff00" onclick="selectColor('#ffff00')"></div>
                </div>
                <script>
                    const vscode = acquireVsCodeApi();
                    function selectColor(color) {
                        vscode.postMessage({ command: 'colorSelected', color: color });
                    }
                </script>
            </body>
            </html>
        `;
    }
}

class ThemePreviewProvider implements vscode.WebviewViewProvider {
    constructor(private context: vscode.ExtensionContext) {}

    resolveWebviewView(
        webviewView: vscode.WebviewView,
        context: vscode.WebviewViewResolveContext,
        token: vscode.CancellationToken
    ): void | Thenable<void> {
        webviewView.webview.options = {
            enableScripts: true,
            localResourceRoots: [this.context.extensionUri]
        };

        webviewView.webview.html = this.getPreviewHtml();
    }

    private getPreviewHtml(): string {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: var(--vscode-font-family); }
                    .preview-container { padding: 16px; }
                    .code-sample { background: var(--vscode-editor-background); padding: 12px; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class="preview-container">
                    <h3>Aperçu du Thème</h3>
                    <div class="code-sample">
                        <span style="color: var(--vscode-editor-foreground)">function</span>
                        <span style="color: #569cd6"> hello</span>
                        <span style="color: var(--vscode-editor-foreground)">(</span>
                        <span style="color: #ce9178">"world"</span>
                        <span style="color: var(--vscode-editor-foreground)">)</span>
                    </div>
                </div>
            </body>
            </html>
        `;
    }
}

class ColorHoverProvider implements vscode.HoverProvider {
    provideHover(
        document: vscode.TextDocument,
        position: vscode.Position,
        token: vscode.CancellationToken
    ): vscode.ProviderResult<vscode.Hover> {
        const range = document.getWordRangeAtPosition(position, /#[0-9a-fA-F]{6}|#[0-9a-fA-F]{3}/);
        if (range) {
            const color = document.getText(range);
            const markdown = new vscode.MarkdownString(`**Couleur:** ${color}`);
            markdown.appendMarkdown(`\n\n<div style="background-color: ${color}; width: 50px; height: 20px; border: 1px solid #ccc;"></div>`);
            markdown.supportHtml = true;
            return new vscode.Hover(markdown, range);
        }
    }
}