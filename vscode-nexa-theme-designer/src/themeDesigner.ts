import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class ThemeDesigner {
    private context: vscode.ExtensionContext;
    private currentTheme: any = null;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
    }

    async getCurrentTheme(): Promise<any> {
        return this.currentTheme;
    }

    async setCurrentTheme(theme: any): Promise<void> {
        this.currentTheme = theme;
    }





    async createNewTheme(): Promise<void> {
        const themeName = await vscode.window.showInputBox({
            prompt: 'Nom du nouveau thème',
            placeHolder: 'Ex: Mon Thème Nexa'
        });

        if (!themeName) {
            return;
        }

        const baseThemes = [
            'Dark (Visual Studio)',
            'Light (Visual Studio)',
            'Dark+ (default dark)',
            'Light+ (default light)',
            'Monokai',
            'Solarized Dark',
            'Solarized Light'
        ];

        const baseTheme = await vscode.window.showQuickPick(baseThemes, {
            placeHolder: 'Choisissez un thème de base'
        });

        if (!baseTheme) {
            return;
        }

        const themeData = this.generateThemeTemplate(themeName, baseTheme);
        await this.saveTheme(themeName, themeData);
        
        vscode.window.showInformationMessage(`Thème "${themeName}" créé avec succès!`);
        await this.openThemeEditor(themeName);
    }

    async editExistingTheme(): Promise<void> {
        const themes = await this.getAvailableThemes();
        
        if (themes.length === 0) {
            const create = await vscode.window.showInformationMessage(
                'Aucun thème personnalisé trouvé. Voulez-vous en créer un ?',
                'Créer', 'Annuler'
            );
            
            if (create === 'Créer') {
                await this.createNewTheme();
            }
            return;
        }

        const selectedTheme = await vscode.window.showQuickPick(themes, {
            placeHolder: 'Choisissez un thème à éditer'
        });

        if (selectedTheme) {
            await this.openThemeEditor(selectedTheme);
        }
    }

    async importTheme(): Promise<void> {
        const fileUri = await vscode.window.showOpenDialog({
            canSelectFiles: true,
            canSelectFolders: false,
            canSelectMany: false,
            filters: {
                'Theme Files': ['json'],
                'All Files': ['*']
            }
        });

        if (!fileUri || fileUri.length === 0) {
            return;
        }

        try {
            const content = fs.readFileSync(fileUri[0].fsPath, 'utf8');
            const themeData = JSON.parse(content);
            
            const themeName = await vscode.window.showInputBox({
                prompt: 'Nom pour le thème importé',
                value: themeData.name || 'Thème Importé'
            });

            if (themeName) {
                await this.saveTheme(themeName, themeData);
                vscode.window.showInformationMessage(`Thème "${themeName}" importé avec succès!`);
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'import: ${error}`);
        }
    }

    async duplicateTheme(): Promise<void> {
        const themes = await this.getAvailableThemes();
        
        if (themes.length === 0) {
            vscode.window.showWarningMessage('Aucun thème à dupliquer');
            return;
        }

        const sourceTheme = await vscode.window.showQuickPick(themes, {
            placeHolder: 'Choisissez le thème à dupliquer'
        });

        if (!sourceTheme) {
            return;
        }

        const newName = await vscode.window.showInputBox({
            prompt: 'Nom du nouveau thème',
            value: `${sourceTheme} - Copie`
        });

        if (!newName) {
            return;
        }

        try {
            const themeData = await this.loadTheme(sourceTheme);
            themeData.name = newName;
            themeData.displayName = newName;
            
            await this.saveTheme(newName, themeData);
            vscode.window.showInformationMessage(`Thème "${newName}" créé par duplication!`);
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la duplication: ${error}`);
        }
    }

    async deleteTheme(): Promise<void> {
        const themes = await this.getAvailableThemes();
        
        if (themes.length === 0) {
            vscode.window.showWarningMessage('Aucun thème à supprimer');
            return;
        }

        const themeToDelete = await vscode.window.showQuickPick(themes, {
            placeHolder: 'Choisissez le thème à supprimer'
        });

        if (!themeToDelete) {
            return;
        }

        const confirm = await vscode.window.showWarningMessage(
            `Êtes-vous sûr de vouloir supprimer le thème "${themeToDelete}" ?`,
            'Supprimer', 'Annuler'
        );

        if (confirm === 'Supprimer') {
            try {
                const themePath = this.getThemePath(themeToDelete);
                fs.unlinkSync(themePath);
                vscode.window.showInformationMessage(`Thème "${themeToDelete}" supprimé!`);
            } catch (error) {
                vscode.window.showErrorMessage(`Erreur lors de la suppression: ${error}`);
            }
        }
    }

    async applyTemplate(): Promise<void> {
        const templates = [
            'Nexa Dark Pro',
            'Nexa Light Clean',
            'Nexa Blue Ocean',
            'Nexa Green Forest',
            'Nexa Purple Night',
            'Nexa Orange Sunset',
            'Nexa Minimal',
            'Nexa High Contrast'
        ];

        const selectedTemplate = await vscode.window.showQuickPick(templates, {
            placeHolder: 'Choisissez un modèle de thème'
        });

        if (!selectedTemplate) {
            return;
        }

        const themeName = await vscode.window.showInputBox({
            prompt: 'Nom du thème basé sur ce modèle',
            value: selectedTemplate
        });

        if (!themeName) {
            return;
        }

        const themeData = this.generateTemplateTheme(selectedTemplate, themeName);
        await this.saveTheme(themeName, themeData);
        
        vscode.window.showInformationMessage(`Thème "${themeName}" créé à partir du modèle!`);
        await this.openThemeEditor(themeName);
    }

    async customizeColors(): Promise<void> {
        const currentTheme = vscode.workspace.getConfiguration().get('workbench.colorTheme');
        
        const panel = vscode.window.createWebviewPanel(
            'nexaColorCustomizer',
            'Personnaliser les Couleurs',
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                localResourceRoots: [this.context.extensionUri]
            }
        );

        panel.webview.html = this.getColorCustomizerHtml();

        panel.webview.onDidReceiveMessage(
            message => {
                switch (message.command) {
                    case 'updateColor':
                        this.updateThemeColor(message.property, message.color);
                        break;
                    case 'saveTheme':
                        this.saveCustomizedTheme(message.themeData);
                        break;
                }
            },
            undefined,
            this.context.subscriptions
        );
    }

    async customizeTokens(): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaTokenCustomizer',
            'Personnaliser les Tokens',
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                localResourceRoots: [this.context.extensionUri]
            }
        );

        panel.webview.html = this.getTokenCustomizerHtml();
    }

    async customizeUI(): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaUICustomizer',
            'Personnaliser l\'Interface',
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                localResourceRoots: [this.context.extensionUri]
            }
        );

        panel.webview.html = this.getUICustomizerHtml();
    }

    async validateTheme(): Promise<void> {
        const themes = await this.getAvailableThemes();
        
        if (themes.length === 0) {
            vscode.window.showWarningMessage('Aucun thème à valider');
            return;
        }

        const themeToValidate = await vscode.window.showQuickPick(themes, {
            placeHolder: 'Choisissez le thème à valider'
        });

        if (!themeToValidate) {
            return;
        }

        try {
            const themeData = await this.loadTheme(themeToValidate);
            const validation = this.validateThemeData(themeData);
            
            if (validation.isValid) {
                vscode.window.showInformationMessage(`✅ Thème "${themeToValidate}" est valide!`);
            } else {
                const errors = validation.errors.join('\n');
                vscode.window.showErrorMessage(`❌ Erreurs dans le thème:\n${errors}`);
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la validation: ${error}`);
        }
    }

    private generateThemeTemplate(name: string, baseTheme: string): any {
        const baseColors = this.getBaseThemeColors(baseTheme);
        
        return {
            name: name,
            displayName: name,
            description: `Thème personnalisé basé sur ${baseTheme}`,
            author: 'Nexa Theme Designer',
            version: '1.0.0',
            type: baseTheme.includes('Dark') ? 'dark' : 'light',
            colors: baseColors,
            tokenColors: this.getBaseTokenColors(baseTheme)
        };
    }

    private generateTemplateTheme(template: string, name: string): any {
        const templates = {
            'Nexa Dark Pro': {
                type: 'dark',
                colors: {
                    'editor.background': '#1e1e1e',
                    'editor.foreground': '#d4d4d4',
                    'activityBar.background': '#2d2d30',
                    'sideBar.background': '#252526',
                    'statusBar.background': '#007acc'
                }
            },
            'Nexa Light Clean': {
                type: 'light',
                colors: {
                    'editor.background': '#ffffff',
                    'editor.foreground': '#333333',
                    'activityBar.background': '#f3f3f3',
                    'sideBar.background': '#f8f8f8',
                    'statusBar.background': '#007acc'
                }
            },
            'Nexa Blue Ocean': {
                type: 'dark',
                colors: {
                    'editor.background': '#0f1419',
                    'editor.foreground': '#bfbdb6',
                    'activityBar.background': '#1a2332',
                    'sideBar.background': '#14191f',
                    'statusBar.background': '#2196f3'
                }
            }
        };

        const templateData = templates[template as keyof typeof templates] || templates['Nexa Dark Pro'];
        
        return {
            name: name,
            displayName: name,
            description: `Thème ${template} personnalisé`,
            author: 'Nexa Theme Designer',
            version: '1.0.0',
            type: templateData.type,
            colors: templateData.colors,
            tokenColors: this.getDefaultTokenColors()
        };
    }

    private getBaseThemeColors(baseTheme: string): any {
        // Couleurs de base selon le thème choisi
        const darkColors = {
            'editor.background': '#1e1e1e',
            'editor.foreground': '#d4d4d4',
            'activityBar.background': '#2d2d30',
            'sideBar.background': '#252526',
            'statusBar.background': '#007acc'
        };

        const lightColors = {
            'editor.background': '#ffffff',
            'editor.foreground': '#333333',
            'activityBar.background': '#f3f3f3',
            'sideBar.background': '#f8f8f8',
            'statusBar.background': '#007acc'
        };

        return baseTheme.includes('Dark') ? darkColors : lightColors;
    }

    private getBaseTokenColors(baseTheme: string): any[] {
        return this.getDefaultTokenColors();
    }

    private getDefaultTokenColors(): any[] {
        return [
            {
                name: 'Comment',
                scope: ['comment', 'punctuation.definition.comment'],
                settings: {
                    fontStyle: 'italic',
                    foreground: '#6A9955'
                }
            },
            {
                name: 'Variables',
                scope: ['variable', 'string constant.other.placeholder'],
                settings: {
                    foreground: '#9CDCFE'
                }
            },
            {
                name: 'Keywords',
                scope: ['keyword', 'storage.type', 'storage.modifier'],
                settings: {
                    foreground: '#569CD6'
                }
            },
            {
                name: 'Strings',
                scope: ['string'],
                settings: {
                    foreground: '#CE9178'
                }
            },
            {
                name: 'Functions',
                scope: ['entity.name.function', 'meta.function-call'],
                settings: {
                    foreground: '#DCDCAA'
                }
            }
        ];
    }

    private async getAvailableThemes(): Promise<string[]> {
        const themesDir = this.getThemesDirectory();
        
        if (!fs.existsSync(themesDir)) {
            return [];
        }

        const files = fs.readdirSync(themesDir);
        return files
            .filter(file => file.endsWith('.json'))
            .map(file => path.basename(file, '.json'));
    }

    private async loadTheme(themeName: string): Promise<any> {
        const themePath = this.getThemePath(themeName);
        const content = fs.readFileSync(themePath, 'utf8');
        return JSON.parse(content);
    }

    private async saveTheme(themeName: string, themeData: any): Promise<void> {
        const themesDir = this.getThemesDirectory();
        
        if (!fs.existsSync(themesDir)) {
            fs.mkdirSync(themesDir, { recursive: true });
        }

        const themePath = this.getThemePath(themeName);
        fs.writeFileSync(themePath, JSON.stringify(themeData, null, 2));
    }

    private getThemesDirectory(): string {
        return path.join(this.context.globalStorageUri.fsPath, 'themes');
    }

    private getThemePath(themeName: string): string {
        return path.join(this.getThemesDirectory(), `${themeName}.json`);
    }

    private async openThemeEditor(themeName: string): Promise<void> {
        const themePath = this.getThemePath(themeName);
        const document = await vscode.workspace.openTextDocument(themePath);
        await vscode.window.showTextDocument(document);
    }

    private validateThemeData(themeData: any): { isValid: boolean; errors: string[] } {
        const errors: string[] = [];
        
        if (!themeData.name) {
            errors.push('Le nom du thème est requis');
        }
        
        if (!themeData.type || !['dark', 'light'].includes(themeData.type)) {
            errors.push('Le type de thème doit être "dark" ou "light"');
        }
        
        if (!themeData.colors || typeof themeData.colors !== 'object') {
            errors.push('Les couleurs du thème sont requises');
        }
        
        if (!themeData.tokenColors || !Array.isArray(themeData.tokenColors)) {
            errors.push('Les couleurs de tokens sont requises');
        }
        
        return {
            isValid: errors.length === 0,
            errors
        };
    }

    private getColorCustomizerHtml(): string {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: var(--vscode-font-family); padding: 20px; }
                    .color-section { margin-bottom: 30px; }
                    .color-item { display: flex; align-items: center; margin-bottom: 10px; }
                    .color-label { width: 200px; }
                    .color-input { margin-left: 10px; }
                    .preview { width: 30px; height: 30px; border: 1px solid #ccc; margin-left: 10px; }
                </style>
            </head>
            <body>
                <h1>Personnaliser les Couleurs</h1>
                
                <div class="color-section">
                    <h3>Éditeur</h3>
                    <div class="color-item">
                        <label class="color-label">Arrière-plan de l'éditeur:</label>
                        <input type="color" class="color-input" value="#1e1e1e" onchange="updateColor('editor.background', this.value)">
                        <div class="preview" style="background: #1e1e1e"></div>
                    </div>
                    <div class="color-item">
                        <label class="color-label">Texte de l'éditeur:</label>
                        <input type="color" class="color-input" value="#d4d4d4" onchange="updateColor('editor.foreground', this.value)">
                        <div class="preview" style="background: #d4d4d4"></div>
                    </div>
                </div>
                
                <script>
                    const vscode = acquireVsCodeApi();
                    
                    function updateColor(property, color) {
                        vscode.postMessage({
                            command: 'updateColor',
                            property: property,
                            color: color
                        });
                    }
                </script>
            </body>
            </html>
        `;
    }

    private getTokenCustomizerHtml(): string {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: var(--vscode-font-family); padding: 20px; }
                    .token-section { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; }
                </style>
            </head>
            <body>
                <h1>Personnaliser les Tokens</h1>
                
                <div class="token-section">
                    <h3>Commentaires</h3>
                    <label>Couleur: <input type="color" value="#6A9955"></label>
                    <label>Style: 
                        <select>
                            <option value="normal">Normal</option>
                            <option value="italic" selected>Italique</option>
                            <option value="bold">Gras</option>
                        </select>
                    </label>
                </div>
                
                <div class="token-section">
                    <h3>Mots-clés</h3>
                    <label>Couleur: <input type="color" value="#569CD6"></label>
                    <label>Style: 
                        <select>
                            <option value="normal" selected>Normal</option>
                            <option value="italic">Italique</option>
                            <option value="bold">Gras</option>
                        </select>
                    </label>
                </div>
            </body>
            </html>
        `;
    }

    private getUICustomizerHtml(): string {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: var(--vscode-font-family); padding: 20px; }
                    .ui-section { margin-bottom: 30px; }
                </style>
            </head>
            <body>
                <h1>Personnaliser l'Interface</h1>
                
                <div class="ui-section">
                    <h3>Barre d'Activité</h3>
                    <label>Couleur de fond: <input type="color" value="#2d2d30"></label>
                </div>
                
                <div class="ui-section">
                    <h3>Barre Latérale</h3>
                    <label>Couleur de fond: <input type="color" value="#252526"></label>
                </div>
                
                <div class="ui-section">
                    <h3>Barre de Statut</h3>
                    <label>Couleur de fond: <input type="color" value="#007acc"></label>
                </div>
            </body>
            </html>
        `;
    }

    private updateThemeColor(property: string, color: string): void {
        // Logique pour mettre à jour la couleur du thème en temps réel
        console.log(`Updating ${property} to ${color}`);
    }

    private async saveCustomizedTheme(themeData: any): Promise<void> {
        const themeName = await vscode.window.showInputBox({
            prompt: 'Nom du thème personnalisé',
            value: 'Mon Thème Personnalisé'
        });

        if (themeName) {
            await this.saveTheme(themeName, themeData);
            vscode.window.showInformationMessage(`Thème "${themeName}" sauvegardé!`);
        }
    }
}