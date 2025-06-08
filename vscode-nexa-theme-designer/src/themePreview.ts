import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';

export class ThemePreview {
    private context: vscode.ExtensionContext;
    private previewPanel: vscode.WebviewPanel | undefined;
    private currentTheme: any = null;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
    }

    async previewTheme(themePath?: string): Promise<void> {
        let themeData: any;

        if (themePath) {
            // Prévisualiser un thème spécifique
            try {
                const content = fs.readFileSync(themePath, 'utf8');
                themeData = JSON.parse(content);
            } catch (error) {
                vscode.window.showErrorMessage(`Erreur lors du chargement du thème: ${error}`);
                return;
            }
        } else {
            // Sélectionner un thème à prévisualiser
            const themes = await this.getAvailableThemes();
            
            if (themes.length === 0) {
                vscode.window.showWarningMessage('Aucun thème personnalisé trouvé');
                return;
            }

            const selectedTheme = await vscode.window.showQuickPick(themes, {
                placeHolder: 'Choisissez un thème à prévisualiser'
            });

            if (!selectedTheme) {
                return;
            }

            try {
                const content = fs.readFileSync(this.getThemePath(selectedTheme), 'utf8');
                themeData = JSON.parse(content);
            } catch (error) {
                vscode.window.showErrorMessage(`Erreur lors du chargement du thème: ${error}`);
                return;
            }
        }

        this.currentTheme = themeData;
        await this.showPreview(themeData);
    }

    async previewLive(): Promise<void> {
        const activeEditor = vscode.window.activeTextEditor;
        
        if (!activeEditor) {
            vscode.window.showWarningMessage('Aucun éditeur actif trouvé');
            return;
        }

        const document = activeEditor.document;
        
        if (!document.fileName.endsWith('.json')) {
            vscode.window.showWarningMessage('Veuillez ouvrir un fichier de thème JSON');
            return;
        }

        try {
            const content = document.getText();
            const themeData = JSON.parse(content);
            
            this.currentTheme = themeData;
            await this.showPreview(themeData, true);
            
            // Écouter les changements pour la prévisualisation en temps réel
            const changeListener = vscode.workspace.onDidChangeTextDocument(event => {
                if (event.document === document) {
                    this.updateLivePreview(event.document);
                }
            });

            // Nettoyer l'écouteur quand le panel est fermé
            if (this.previewPanel) {
                this.previewPanel.onDidDispose(() => {
                    changeListener.dispose();
                });
            }
            
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'analyse du thème: ${error}`);
        }
    }

    async compareThemes(): Promise<void> {
        const themes = await this.getAvailableThemes();
        
        if (themes.length < 2) {
            vscode.window.showWarningMessage('Au moins 2 thèmes sont nécessaires pour la comparaison');
            return;
        }

        const theme1 = await vscode.window.showQuickPick(themes, {
            placeHolder: 'Choisissez le premier thème'
        });

        if (!theme1) {
            return;
        }

        const remainingThemes = themes.filter(t => t !== theme1);
        const theme2 = await vscode.window.showQuickPick(remainingThemes, {
            placeHolder: 'Choisissez le second thème'
        });

        if (!theme2) {
            return;
        }

        try {
            const themeData1 = JSON.parse(fs.readFileSync(this.getThemePath(theme1), 'utf8'));
            const themeData2 = JSON.parse(fs.readFileSync(this.getThemePath(theme2), 'utf8'));
            
            await this.showComparison(themeData1, themeData2);
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la comparaison: ${error}`);
        }
    }

    async previewOnDifferentFiles(): Promise<void> {
        if (!this.currentTheme) {
            vscode.window.showWarningMessage('Aucun thème sélectionné pour la prévisualisation');
            return;
        }

        const fileTypes = [
            { label: 'PHP', extension: 'php', sample: this.getPHPSample() },
            { label: 'JavaScript', extension: 'js', sample: this.getJavaScriptSample() },
            { label: 'TypeScript', extension: 'ts', sample: this.getTypeScriptSample() },
            { label: 'CSS', extension: 'css', sample: this.getCSSSample() },
            { label: 'HTML', extension: 'html', sample: this.getHTMLSample() },
            { label: 'JSON', extension: 'json', sample: this.getJSONSample() },
            { label: 'Markdown', extension: 'md', sample: this.getMarkdownSample() }
        ];

        const selectedType = await vscode.window.showQuickPick(fileTypes, {
            placeHolder: 'Choisissez un type de fichier pour la prévisualisation'
        });

        if (!selectedType) {
            return;
        }

        await this.showFileTypePreview(selectedType, this.currentTheme);
    }

    async exportPreview(): Promise<void> {
        if (!this.currentTheme) {
            vscode.window.showWarningMessage('Aucun thème sélectionné pour l\'export');
            return;
        }

        const exportFormat = await vscode.window.showQuickPick([
            'HTML (Page complète)',
            'PNG (Capture d\'écran)',
            'PDF (Document)',
            'SVG (Vectoriel)'
        ], {
            placeHolder: 'Format d\'export'
        });

        if (!exportFormat) {
            return;
        }

        switch (exportFormat) {
            case 'HTML (Page complète)':
                await this.exportAsHTML();
                break;
            case 'PNG (Capture d\'écran)':
                vscode.window.showInformationMessage('Export PNG sera disponible dans une prochaine version');
                break;
            case 'PDF (Document)':
                vscode.window.showInformationMessage('Export PDF sera disponible dans une prochaine version');
                break;
            case 'SVG (Vectoriel)':
                vscode.window.showInformationMessage('Export SVG sera disponible dans une prochaine version');
                break;
        }
    }

    private async showPreview(themeData: any, isLive: boolean = false): Promise<void> {
        if (this.previewPanel) {
            this.previewPanel.dispose();
        }

        this.previewPanel = vscode.window.createWebviewPanel(
            'nexaThemePreview',
            `Aperçu - ${themeData.name || 'Thème'}${isLive ? ' (Live)' : ''}`,
            vscode.ViewColumn.Two,
            {
                enableScripts: true,
                localResourceRoots: [this.context.extensionUri],
                retainContextWhenHidden: true
            }
        );

        this.previewPanel.webview.html = this.getPreviewHtml(themeData);

        this.previewPanel.webview.onDidReceiveMessage(
            message => {
                switch (message.command) {
                    case 'applyTheme':
                        this.applyThemeToVSCode(themeData);
                        break;
                    case 'exportTheme':
                        this.exportPreview();
                        break;
                    case 'editTheme':
                        this.editTheme(themeData);
                        break;
                }
            },
            undefined,
            this.context.subscriptions
        );
    }

    private async showComparison(theme1: any, theme2: any): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaThemeComparison',
            `Comparaison: ${theme1.name} vs ${theme2.name}`,
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                localResourceRoots: [this.context.extensionUri]
            }
        );

        panel.webview.html = this.getComparisonHtml(theme1, theme2);
    }

    private async showFileTypePreview(fileType: any, themeData: any): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaFileTypePreview',
            `Aperçu ${fileType.label} - ${themeData.name}`,
            vscode.ViewColumn.Two,
            {
                enableScripts: true,
                localResourceRoots: [this.context.extensionUri]
            }
        );

        panel.webview.html = this.getFileTypePreviewHtml(fileType, themeData);
    }

    private async updateLivePreview(document: vscode.TextDocument): Promise<void> {
        if (!this.previewPanel) {
            return;
        }

        try {
            const content = document.getText();
            const themeData = JSON.parse(content);
            
            this.currentTheme = themeData;
            
            // Mettre à jour le contenu du webview
            this.previewPanel.webview.postMessage({
                command: 'updateTheme',
                theme: themeData
            });
        } catch (error) {
            // Ignorer les erreurs de parsing pendant la saisie
        }
    }

    private getPreviewHtml(themeData: any): string {
        const colors = themeData.colors || {};
        const tokenColors = themeData.tokenColors || [];
        
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        margin: 0;
                        padding: 20px;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        background: ${colors['editor.background'] || '#1e1e1e'};
                        color: ${colors['editor.foreground'] || '#d4d4d4'};
                    }
                    
                    .preview-container {
                        max-width: 1200px;
                        margin: 0 auto;
                    }
                    
                    .theme-header {
                        text-align: center;
                        margin-bottom: 30px;
                        padding: 20px;
                        background: ${colors['titleBar.activeBackground'] || '#3c3c3c'};
                        border-radius: 8px;
                    }
                    
                    .theme-title {
                        font-size: 24px;
                        font-weight: bold;
                        margin-bottom: 10px;
                        color: ${colors['titleBar.activeForeground'] || '#cccccc'};
                    }
                    
                    .theme-info {
                        font-size: 14px;
                        opacity: 0.8;
                    }
                    
                    .preview-sections {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 20px;
                        margin-bottom: 30px;
                    }
                    
                    .preview-section {
                        background: ${colors['panel.background'] || '#252526'};
                        border: 1px solid ${colors['panel.border'] || '#3e3e42'};
                        border-radius: 6px;
                        overflow: hidden;
                    }
                    
                    .section-header {
                        background: ${colors['panelTitle.activeBorder'] || '#007acc'};
                        color: white;
                        padding: 10px 15px;
                        font-weight: bold;
                        font-size: 14px;
                    }
                    
                    .section-content {
                        padding: 15px;
                    }
                    
                    .editor-preview {
                        background: ${colors['editor.background'] || '#1e1e1e'};
                        border: 1px solid ${colors['editorGroup.border'] || '#444444'};
                        border-radius: 4px;
                        padding: 15px;
                        font-family: 'Consolas', 'Courier New', monospace;
                        font-size: 14px;
                        line-height: 1.5;
                        overflow-x: auto;
                    }
                    
                    .sidebar-preview {
                        background: ${colors['sideBar.background'] || '#252526'};
                        border: 1px solid ${colors['sideBar.border'] || '#3e3e42'};
                        border-radius: 4px;
                        padding: 15px;
                    }
                    
                    .activity-bar {
                        background: ${colors['activityBar.background'] || '#2d2d30'};
                        width: 50px;
                        height: 200px;
                        border-radius: 4px;
                        margin-bottom: 15px;
                    }
                    
                    .status-bar {
                        background: ${colors['statusBar.background'] || '#007acc'};
                        color: ${colors['statusBar.foreground'] || '#ffffff'};
                        padding: 8px 15px;
                        border-radius: 4px;
                        font-size: 12px;
                        margin-top: 15px;
                    }
                    
                    .color-palette {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                        gap: 10px;
                        margin-top: 20px;
                    }
                    
                    .color-item {
                        text-align: center;
                    }
                    
                    .color-swatch {
                        width: 100%;
                        height: 60px;
                        border-radius: 4px;
                        margin-bottom: 5px;
                        border: 1px solid ${colors['panel.border'] || '#3e3e42'};
                    }
                    
                    .color-label {
                        font-size: 11px;
                        opacity: 0.8;
                        word-break: break-all;
                    }
                    
                    .actions {
                        text-align: center;
                        margin-top: 30px;
                    }
                    
                    .btn {
                        background: ${colors['button.background'] || '#0e639c'};
                        color: ${colors['button.foreground'] || '#ffffff'};
                        border: none;
                        padding: 10px 20px;
                        border-radius: 4px;
                        cursor: pointer;
                        margin: 0 10px;
                        font-size: 14px;
                    }
                    
                    .btn:hover {
                        background: ${colors['button.hoverBackground'] || '#1177bb'};
                    }
                    
                    /* Styles pour les tokens de code */
                    ${this.generateTokenStyles(tokenColors)}
                </style>
            </head>
            <body>
                <div class="preview-container">
                    <div class="theme-header">
                        <div class="theme-title">${themeData.name || 'Thème Sans Nom'}</div>
                        <div class="theme-info">
                            Type: ${themeData.type || 'Non spécifié'} | 
                            Version: ${themeData.version || '1.0.0'} | 
                            Auteur: ${themeData.author || 'Inconnu'}
                        </div>
                        ${themeData.description ? `<div style="margin-top: 10px; font-style: italic;">${themeData.description}</div>` : ''}
                    </div>
                    
                    <div class="preview-sections">
                        <div class="preview-section">
                            <div class="section-header">Éditeur de Code</div>
                            <div class="section-content">
                                <div class="editor-preview">
                                    ${this.getCodeSample()}
                                </div>
                            </div>
                        </div>
                        
                        <div class="preview-section">
                            <div class="section-header">Interface Utilisateur</div>
                            <div class="section-content">
                                <div class="activity-bar"></div>
                                <div class="sidebar-preview">
                                    <div style="margin-bottom: 10px; font-weight: bold; color: ${colors['sideBarTitle.foreground'] || '#cccccc'};">EXPLORATEUR</div>
                                    <div style="margin-left: 15px; font-size: 13px;">
                                        <div style="margin-bottom: 5px;">📁 src</div>
                                        <div style="margin-left: 15px; margin-bottom: 5px;">📄 index.php</div>
                                        <div style="margin-left: 15px; margin-bottom: 5px;">📄 config.php</div>
                                        <div style="margin-bottom: 5px;">📁 public</div>
                                        <div style="margin-bottom: 5px;">📄 composer.json</div>
                                    </div>
                                </div>
                                <div class="status-bar">
                                    ✓ Prêt | PHP 8.1 | UTF-8 | Ln 42, Col 15
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="preview-section">
                        <div class="section-header">Palette de Couleurs</div>
                        <div class="section-content">
                            <div class="color-palette">
                                ${this.generateColorPalette(colors)}
                            </div>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <button class="btn" onclick="applyTheme()">Appliquer ce Thème</button>
                        <button class="btn" onclick="editTheme()">Éditer</button>
                        <button class="btn" onclick="exportTheme()">Exporter</button>
                    </div>
                </div>
                
                <script>
                    const vscode = acquireVsCodeApi();
                    
                    function applyTheme() {
                        vscode.postMessage({ command: 'applyTheme' });
                    }
                    
                    function editTheme() {
                        vscode.postMessage({ command: 'editTheme' });
                    }
                    
                    function exportTheme() {
                        vscode.postMessage({ command: 'exportTheme' });
                    }
                    
                    // Écouter les mises à jour du thème (pour la prévisualisation live)
                    window.addEventListener('message', event => {
                        const message = event.data;
                        if (message.command === 'updateTheme') {
                            location.reload();
                        }
                    });
                </script>
            </body>
            </html>
        `;
    }

    private getComparisonHtml(theme1: any, theme2: any): string {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        margin: 0;
                        padding: 20px;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        background: var(--vscode-editor-background);
                        color: var(--vscode-editor-foreground);
                    }
                    
                    .comparison-container {
                        max-width: 1400px;
                        margin: 0 auto;
                    }
                    
                    .comparison-header {
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    
                    .themes-grid {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 20px;
                    }
                    
                    .theme-column {
                        border: 1px solid var(--vscode-panel-border);
                        border-radius: 8px;
                        overflow: hidden;
                    }
                    
                    .theme-header {
                        padding: 15px;
                        text-align: center;
                        font-weight: bold;
                        font-size: 18px;
                    }
                    
                    .theme-preview {
                        padding: 20px;
                        min-height: 400px;
                    }
                    
                    .editor-sample {
                        font-family: 'Consolas', 'Courier New', monospace;
                        font-size: 14px;
                        line-height: 1.5;
                        padding: 15px;
                        border-radius: 4px;
                        margin-bottom: 20px;
                    }
                    
                    .color-comparison {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 10px;
                        margin-top: 20px;
                    }
                    
                    .color-item {
                        display: flex;
                        align-items: center;
                        margin-bottom: 8px;
                    }
                    
                    .color-swatch {
                        width: 30px;
                        height: 30px;
                        border-radius: 4px;
                        margin-right: 10px;
                        border: 1px solid #ccc;
                    }
                    
                    .color-label {
                        font-size: 12px;
                        font-family: monospace;
                    }
                </style>
            </head>
            <body>
                <div class="comparison-container">
                    <div class="comparison-header">
                        <h1>Comparaison de Thèmes</h1>
                        <p>Comparez visuellement les différences entre les thèmes</p>
                    </div>
                    
                    <div class="themes-grid">
                        <div class="theme-column">
                            <div class="theme-header" style="background: ${theme1.colors?.['titleBar.activeBackground'] || '#3c3c3c'}; color: ${theme1.colors?.['titleBar.activeForeground'] || '#cccccc'};">
                                ${theme1.name || 'Thème 1'}
                            </div>
                            <div class="theme-preview" style="background: ${theme1.colors?.['editor.background'] || '#1e1e1e'}; color: ${theme1.colors?.['editor.foreground'] || '#d4d4d4'};">
                                <div class="editor-sample" style="background: ${theme1.colors?.['editor.background'] || '#1e1e1e'}; border: 1px solid ${theme1.colors?.['editorGroup.border'] || '#444444'};">
                                    ${this.getCodeSample()}
                                </div>
                                <div class="color-comparison">
                                    ${this.generateColorList(theme1.colors || {})}
                                </div>
                            </div>
                        </div>
                        
                        <div class="theme-column">
                            <div class="theme-header" style="background: ${theme2.colors?.['titleBar.activeBackground'] || '#3c3c3c'}; color: ${theme2.colors?.['titleBar.activeForeground'] || '#cccccc'};">
                                ${theme2.name || 'Thème 2'}
                            </div>
                            <div class="theme-preview" style="background: ${theme2.colors?.['editor.background'] || '#1e1e1e'}; color: ${theme2.colors?.['editor.foreground'] || '#d4d4d4'};">
                                <div class="editor-sample" style="background: ${theme2.colors?.['editor.background'] || '#1e1e1e'}; border: 1px solid ${theme2.colors?.['editorGroup.border'] || '#444444'};">
                                    ${this.getCodeSample()}
                                </div>
                                <div class="color-comparison">
                                    ${this.generateColorList(theme2.colors || {})}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        `;
    }

    private getFileTypePreviewHtml(fileType: any, themeData: any): string {
        const colors = themeData.colors || {};
        
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body {
                        margin: 0;
                        padding: 20px;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        background: ${colors['editor.background'] || '#1e1e1e'};
                        color: ${colors['editor.foreground'] || '#d4d4d4'};
                    }
                    
                    .preview-header {
                        text-align: center;
                        margin-bottom: 30px;
                        padding: 20px;
                        background: ${colors['panel.background'] || '#252526'};
                        border-radius: 8px;
                    }
                    
                    .file-preview {
                        background: ${colors['editor.background'] || '#1e1e1e'};
                        border: 1px solid ${colors['editorGroup.border'] || '#444444'};
                        border-radius: 6px;
                        overflow: hidden;
                    }
                    
                    .file-tab {
                        background: ${colors['tab.activeBackground'] || '#1e1e1e'};
                        color: ${colors['tab.activeForeground'] || '#ffffff'};
                        padding: 10px 20px;
                        border-bottom: 1px solid ${colors['tab.border'] || '#252526'};
                        font-size: 14px;
                    }
                    
                    .file-content {
                        padding: 20px;
                        font-family: 'Consolas', 'Courier New', monospace;
                        font-size: 14px;
                        line-height: 1.6;
                        overflow-x: auto;
                    }
                    
                    ${this.generateTokenStyles(themeData.tokenColors || [])}
                </style>
            </head>
            <body>
                <div class="preview-header">
                    <h1>Aperçu ${fileType.label}</h1>
                    <p>Thème: ${themeData.name || 'Sans nom'}</p>
                </div>
                
                <div class="file-preview">
                    <div class="file-tab">
                        📄 example.${fileType.extension}
                    </div>
                    <div class="file-content">
                        ${fileType.sample}
                    </div>
                </div>
            </body>
            </html>
        `;
    }

    private generateTokenStyles(tokenColors: any[]): string {
        let styles = '';
        
        tokenColors.forEach((token, index) => {
            const className = `token-${index}`;
            const settings = token.settings || {};
            
            styles += `
                .${className} {
                    ${settings.foreground ? `color: ${settings.foreground};` : ''}
                    ${settings.background ? `background-color: ${settings.background};` : ''}
                    ${settings.fontStyle ? `font-style: ${settings.fontStyle};` : ''}
                    ${settings.fontWeight ? `font-weight: ${settings.fontWeight};` : ''}
                }
            `;
        });
        
        return styles;
    }

    private generateColorPalette(colors: any): string {
        const importantColors = [
            'editor.background',
            'editor.foreground',
            'activityBar.background',
            'sideBar.background',
            'statusBar.background',
            'panel.background',
            'titleBar.activeBackground',
            'button.background'
        ];
        
        return importantColors
            .filter(key => colors[key])
            .map(key => `
                <div class="color-item">
                    <div class="color-swatch" style="background-color: ${colors[key]}"></div>
                    <div class="color-label">
                        <div style="font-weight: bold;">${key}</div>
                        <div>${colors[key]}</div>
                    </div>
                </div>
            `)
            .join('');
    }

    private generateColorList(colors: any): string {
        return Object.entries(colors)
            .slice(0, 8) // Limiter à 8 couleurs pour l'affichage
            .map(([key, value]) => `
                <div class="color-item">
                    <div class="color-swatch" style="background-color: ${value}"></div>
                    <div class="color-label">
                        <div>${key}</div>
                        <div>${value}</div>
                    </div>
                </div>
            `)
            .join('');
    }

    private getCodeSample(): string {
        return `
<span class="token-keyword">&lt;?php</span><br>
<br>
<span class="token-keyword">namespace</span> <span class="token-namespace">App\Controllers</span>;<br>
<br>
<span class="token-keyword">use</span> <span class="token-namespace">Nexa\Framework\Controller</span>;<br>
<span class="token-keyword">use</span> <span class="token-namespace">Nexa\Framework\Request</span>;<br>
<br>
<span class="token-comment">/**</span><br>
<span class="token-comment"> * Contrôleur principal de l'application</span><br>
<span class="token-comment"> */</span><br>
<span class="token-keyword">class</span> <span class="token-class">HomeController</span> <span class="token-keyword">extends</span> <span class="token-class">Controller</span><br>
{<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span class="token-comment">// Méthode d'accueil</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;<span class="token-keyword">public</span> <span class="token-keyword">function</span> <span class="token-function">index</span>(<span class="token-class">Request</span> <span class="token-variable">$request</span>)<br>
&nbsp;&nbsp;&nbsp;&nbsp;{<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="token-variable">$data</span> = [<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="token-string">'title'</span> => <span class="token-string">'Bienvenue sur Nexa'</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="token-string">'version'</span> => <span class="token-string">'1.0.0'</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;];<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="token-keyword">return</span> <span class="token-variable">$this</span>-><span class="token-function">view</span>(<span class="token-string">'home'</span>, <span class="token-variable">$data</span>);<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}
        `;
    }

    private getPHPSample(): string {
        return `
&lt;?php<br>
<br>
namespace App\Models;<br>
<br>
use Nexa\Framework\Model;<br>
use Nexa\Framework\Database\Eloquent;<br>
<br>
class User extends Model<br>
{<br>
&nbsp;&nbsp;&nbsp;&nbsp;protected $table = 'users';<br>
&nbsp;&nbsp;&nbsp;&nbsp;protected $fillable = ['name', 'email', 'password'];<br>
&nbsp;&nbsp;&nbsp;&nbsp;protected $hidden = ['password'];<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;public function posts()<br>
&nbsp;&nbsp;&nbsp;&nbsp;{<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return $this->hasMany(Post::class);<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}
        `;
    }

    private getJavaScriptSample(): string {
        return `
// Configuration Nexa Framework<br>
const nexaConfig = {<br>
&nbsp;&nbsp;&nbsp;&nbsp;apiUrl: 'https://api.example.com',<br>
&nbsp;&nbsp;&nbsp;&nbsp;timeout: 5000,<br>
&nbsp;&nbsp;&nbsp;&nbsp;retries: 3<br>
};<br>
<br>
// Classe utilitaire<br>
class ApiClient {<br>
&nbsp;&nbsp;&nbsp;&nbsp;constructor(config) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;this.config = config;<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;async get(endpoint) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;try {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;const response = await fetch(`${this.config.apiUrl}/${endpoint}`);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return await response.json();<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;} catch (error) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;console.error('API Error:', error);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}
        `;
    }

    private getTypeScriptSample(): string {
        return `
interface User {<br>
&nbsp;&nbsp;&nbsp;&nbsp;id: number;<br>
&nbsp;&nbsp;&nbsp;&nbsp;name: string;<br>
&nbsp;&nbsp;&nbsp;&nbsp;email: string;<br>
&nbsp;&nbsp;&nbsp;&nbsp;createdAt: Date;<br>
}<br>
<br>
class UserService {<br>
&nbsp;&nbsp;&nbsp;&nbsp;private apiClient: ApiClient;<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;constructor(apiClient: ApiClient) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;this.apiClient = apiClient;<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;async getUser(id: number): Promise&lt;User | null&gt; {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;try {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;const user = await this.apiClient.get(`users/${id}`);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return user as User;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;} catch (error) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return null;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}
        `;
    }

    private getCSSSample(): string {
        return `
/* Styles pour Nexa Framework */<br>
:root {<br>
&nbsp;&nbsp;&nbsp;&nbsp;--primary-color: #007acc;<br>
&nbsp;&nbsp;&nbsp;&nbsp;--secondary-color: #6c757d;<br>
&nbsp;&nbsp;&nbsp;&nbsp;--success-color: #28a745;<br>
&nbsp;&nbsp;&nbsp;&nbsp;--danger-color: #dc3545;<br>
}<br>
<br>
.nexa-container {<br>
&nbsp;&nbsp;&nbsp;&nbsp;max-width: 1200px;<br>
&nbsp;&nbsp;&nbsp;&nbsp;margin: 0 auto;<br>
&nbsp;&nbsp;&nbsp;&nbsp;padding: 20px;<br>
}<br>
<br>
.nexa-button {<br>
&nbsp;&nbsp;&nbsp;&nbsp;background: var(--primary-color);<br>
&nbsp;&nbsp;&nbsp;&nbsp;color: white;<br>
&nbsp;&nbsp;&nbsp;&nbsp;border: none;<br>
&nbsp;&nbsp;&nbsp;&nbsp;padding: 10px 20px;<br>
&nbsp;&nbsp;&nbsp;&nbsp;border-radius: 4px;<br>
&nbsp;&nbsp;&nbsp;&nbsp;cursor: pointer;<br>
&nbsp;&nbsp;&nbsp;&nbsp;transition: background 0.3s ease;<br>
}<br>
<br>
.nexa-button:hover {<br>
&nbsp;&nbsp;&nbsp;&nbsp;background: #005a9e;<br>
}
        `;
    }

    private getHTMLSample(): string {
        return `
&lt;!DOCTYPE html&gt;<br>
&lt;html lang="fr"&gt;<br>
&lt;head&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;meta charset="UTF-8"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;title&gt;Nexa Framework&lt;/title&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;link rel="stylesheet" href="styles.css"&gt;<br>
&lt;/head&gt;<br>
&lt;body&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;header class="nexa-header"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;h1&gt;Bienvenue sur Nexa&lt;/h1&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;nav&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;a href="#home"&gt;Accueil&lt;/a&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;a href="#docs"&gt;Documentation&lt;/a&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;/nav&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;/header&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;main class="nexa-container"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;p&gt;Framework PHP moderne et élégant&lt;/p&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;/main&gt;<br>
&lt;/body&gt;<br>
&lt;/html&gt;
        `;
    }

    private getJSONSample(): string {
        return `
{<br>
&nbsp;&nbsp;&nbsp;&nbsp;"name": "nexa-framework",<br>
&nbsp;&nbsp;&nbsp;&nbsp;"version": "1.0.0",<br>
&nbsp;&nbsp;&nbsp;&nbsp;"description": "Framework PHP moderne",<br>
&nbsp;&nbsp;&nbsp;&nbsp;"require": {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"php": "^8.1",<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"symfony/console": "^6.0",<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"doctrine/orm": "^2.12"<br>
&nbsp;&nbsp;&nbsp;&nbsp;},<br>
&nbsp;&nbsp;&nbsp;&nbsp;"autoload": {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"psr-4": {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"App\\": "src/",<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"Nexa\\": "framework/"<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}
        `;
    }

    private getMarkdownSample(): string {
        return `
# Nexa Framework<br>
<br>
## Installation<br>
<br>
```bash<br>
composer create-project nexa/framework mon-projet<br>
cd mon-projet<br>
php nexa serve<br>
```<br>
<br>
## Fonctionnalités<br>
<br>
- **Routage** : Système de routage flexible<br>
- **ORM** : Intégration Doctrine<br>
- **CLI** : Outils en ligne de commande<br>
- **Tests** : Support PHPUnit intégré<br>
<br>
### Exemple de contrôleur<br>
<br>
```php<br>
class HomeController extends Controller<br>
{<br>
&nbsp;&nbsp;&nbsp;&nbsp;public function index()<br>
&nbsp;&nbsp;&nbsp;&nbsp;{<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return $this->view('home');<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}<br>
```<br>
<br>
> **Note** : Consultez la documentation complète sur [nexa-framework.com](https://nexa-framework.com)
        `;
    }

    private async getAvailableThemes(): Promise<string[]> {
        const themesDir = path.join(this.context.globalStorageUri.fsPath, 'themes');
        
        if (!fs.existsSync(themesDir)) {
            return [];
        }

        const files = fs.readdirSync(themesDir);
        return files
            .filter(file => file.endsWith('.json'))
            .map(file => path.basename(file, '.json'));
    }

    private getThemePath(themeName: string): string {
        return path.join(this.context.globalStorageUri.fsPath, 'themes', `${themeName}.json`);
    }

    private async applyThemeToVSCode(themeData: any): Promise<void> {
        // Cette fonctionnalité nécessiterait l'installation du thème dans VSCode
        vscode.window.showInformationMessage(
            `Application du thème "${themeData.name}" sera disponible dans une prochaine version`
        );
    }

    private async editTheme(themeData: any): Promise<void> {
        // Ouvrir l'éditeur de thème
        vscode.commands.executeCommand('nexa-theme-designer.editExistingTheme');
    }

    private async exportAsHTML(): Promise<void> {
        if (!this.currentTheme) {
            return;
        }

        const saveUri = await vscode.window.showSaveDialog({
            defaultUri: vscode.Uri.file(`${this.currentTheme.name || 'theme'}-preview.html`),
            filters: {
                'HTML Files': ['html'],
                'All Files': ['*']
            }
        });

        if (saveUri) {
            const html = this.getPreviewHtml(this.currentTheme);
            fs.writeFileSync(saveUri.fsPath, html);
            vscode.window.showInformationMessage(`Aperçu exporté vers ${saveUri.fsPath}`);
        }
    }
}