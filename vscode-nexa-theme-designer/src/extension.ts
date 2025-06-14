import * as vscode from 'vscode';
import { ThemeDesigner } from './themeDesigner';
import { ColorPalette } from './colorPalette';
import { ThemePreview } from './themePreview';
import { ThemeGenerator } from './themeGenerator';
import { ThemeExporter } from './themeExporter';

let themeDesigner: ThemeDesigner;
let colorPalette: ColorPalette;
let themePreview: ThemePreview;
let themeGenerator: ThemeGenerator;
let themeExporter: ThemeExporter;

export function activate(context: vscode.ExtensionContext) {
    // Initialize components
    colorPalette = new ColorPalette(context);
    themeGenerator = new ThemeGenerator(colorPalette);
    themeExporter = new ThemeExporter();
    themeDesigner = new ThemeDesigner(context);
    themePreview = new ThemePreview(context);

    // Commandes principales
    const createTheme = vscode.commands.registerCommand('nexa.theme.create', async () => {
        await themeDesigner.createNewTheme();
    });

    const editTheme = vscode.commands.registerCommand('nexa.theme.edit', async () => {
        await themeDesigner.editExistingTheme();
    });

    const previewTheme = vscode.commands.registerCommand('nexa.theme.preview', async () => {
        if (themeDesigner.getCurrentTheme) {
                const currentTheme = await themeDesigner.getCurrentTheme();
                if (currentTheme) {
                    await themePreview.showPreview(currentTheme);
                } else {
                    vscode.window.showWarningMessage('Aucun thème actif à prévisualiser');
                }
            } else {
                vscode.window.showWarningMessage('Aucun thème actif à prévisualiser');
            }
    });

    const exportTheme = vscode.commands.registerCommand('nexa.theme.export', async () => {
        try {
            const currentTheme = await themeDesigner.getCurrentTheme();
            if (currentTheme) {
                const exportOptions = await themeExporter.showExportDialog(currentTheme);
                if (exportOptions) {
                    const outputPath = await themeExporter.exportTheme(currentTheme, exportOptions);
                    vscode.window.showInformationMessage(`Thème exporté avec succès: ${outputPath}`);
                }
            } else {
                vscode.window.showWarningMessage('Aucun thème à exporter. Créez d\'abord un thème.');
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'export: ${error}`);
        }
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

    const openColorPicker = vscode.commands.registerCommand('nexa.theme.openColorPicker', async () => {
        await colorPalette.createPalette();
    });

    const generatePalette = vscode.commands.registerCommand('nexa.theme.generatePalette', async () => {
        await colorPalette.createPalette();
    });

    const generateFromTemplate = vscode.commands.registerCommand('nexa.theme.generateFromTemplate', async () => {
        try {
            const templates = themeGenerator.getBuiltInTemplates();
            const selectedTemplate = await vscode.window.showQuickPick(
                templates.map(t => ({
                    label: t.name,
                    description: t.description,
                    detail: `Type: ${t.type}`,
                    template: t
                })),
                {
                    placeHolder: 'Sélectionnez un template de thème',
                    title: 'Générer un thème à partir d\'un template'
                }
            );
            
            if (selectedTemplate) {
                const theme = themeGenerator.generateTheme(selectedTemplate.template);
                await themeDesigner.setCurrentTheme(theme);
                vscode.window.showInformationMessage(`Thème "${theme.name}" généré avec succès!`);
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la génération: ${error}`);
        }
    });

    const generateFromPalette = vscode.commands.registerCommand('nexa.theme.generateFromPalette', async () => {
        try {
            const palettes = colorPalette.getPalettes();
            if (palettes.size === 0) {
                vscode.window.showWarningMessage('Aucune palette disponible. Créez d\'abord une palette.');
                return;
            }
            const paletteNames = Array.from(palettes.keys());
            const selectedPaletteName = await vscode.window.showQuickPick(paletteNames, {
                placeHolder: 'Choisissez une palette'
            });
            if (!selectedPaletteName) return;
            const palette = colorPalette.getPalette(selectedPaletteName);
            if (!palette) {
                vscode.window.showWarningMessage('Aucune palette disponible. Créez d\'abord une palette de couleurs.');
                return;
            }
            
            const themeName = await vscode.window.showInputBox({
                prompt: 'Nom du thème',
                placeHolder: 'Mon Thème Personnalisé'
            });
            
            if (!themeName) {
                return;
            }
            
            const themeType = await vscode.window.showQuickPick(
                [
                    { label: 'Sombre', value: 'dark' as const },
                    { label: 'Clair', value: 'light' as const }
                ],
                {
                    placeHolder: 'Type de thème'
                }
            );
            
            if (!themeType) {
                return;
            }
            
            const theme = themeGenerator.generateFromColorPalette(palette, themeName, themeType.value);
            await themeDesigner.setCurrentTheme(theme);
            vscode.window.showInformationMessage(`Thème "${theme.name}" généré à partir de la palette!`);
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la génération: ${error}`);
        }
    });

    const installTheme = vscode.commands.registerCommand('nexa.theme.install', async () => {
        try {
            const currentTheme = await themeDesigner.getCurrentTheme();
            if (currentTheme) {
                await themeGenerator.installTheme(currentTheme);
                vscode.window.showInformationMessage('Thème installé avec succès!');
            } else {
                vscode.window.showWarningMessage('Aucun thème à installer. Créez d\'abord un thème.');
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'installation: ${error}`);
        }
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
        try {
            const currentTheme = await themeDesigner.getCurrentTheme();
            if (currentTheme) {
                const exportOptions = {
                    format: 'json' as const,
                    includeMetadata: true,
                    minify: false
                };
                const outputPath = await themeExporter.exportTheme(currentTheme, exportOptions);
                vscode.window.showInformationMessage(`Thème partagé: ${outputPath}`);
            } else {
                vscode.window.showWarningMessage('Aucun thème à partager.');
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors du partage: ${error}`);
        }
    });

    const publishTheme = vscode.commands.registerCommand('nexa.theme.publish', async () => {
        try {
            const currentTheme = await themeDesigner.getCurrentTheme();
            if (currentTheme) {
                const exportOptions = {
                    format: 'vsix' as const,
                    includeMetadata: true,
                    minify: false
                };
                const outputPath = await themeExporter.exportTheme(currentTheme, exportOptions);
                vscode.window.showInformationMessage(`Package VSIX créé: ${outputPath}`);
            } else {
                vscode.window.showWarningMessage('Aucun thème à publier.');
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la publication: ${error}`);
        }
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
        generateFromTemplate,
        generateFromPalette,
        installTheme,
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
    if (themeExporter) {
        themeExporter.dispose();
    }
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