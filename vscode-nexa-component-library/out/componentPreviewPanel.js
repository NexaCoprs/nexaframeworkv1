"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ComponentPreviewPanel = void 0;
const vscode = require("vscode");
class ComponentPreviewPanel {
    static createOrShow(extensionUri, componentManager, component) {
        const column = vscode.window.activeTextEditor
            ? vscode.window.activeTextEditor.viewColumn
            : undefined;
        if (ComponentPreviewPanel.currentPanel) {
            ComponentPreviewPanel.currentPanel._panel.reveal(column);
            if (component) {
                ComponentPreviewPanel.currentPanel._updatePreview(component);
            }
            return;
        }
        const panel = vscode.window.createWebviewPanel(ComponentPreviewPanel.viewType, 'Prévisualisation Composant', column || vscode.ViewColumn.One, {
            enableScripts: true,
            localResourceRoots: [extensionUri]
        });
        ComponentPreviewPanel.currentPanel = new ComponentPreviewPanel(panel, extensionUri, componentManager, component);
    }
    constructor(panel, extensionUri, componentManager, currentComponent) {
        this.componentManager = componentManager;
        this.currentComponent = currentComponent;
        this._disposables = [];
        this._panel = panel;
        this._extensionUri = extensionUri;
        this._update();
        this._panel.onDidDispose(() => this.dispose(), null, this._disposables);
        this._panel.webview.onDidReceiveMessage(message => {
            switch (message.command) {
                case 'insertComponent':
                    this._insertComponent();
                    return;
                case 'refreshPreview':
                    this._update();
                    return;
            }
        }, null, this._disposables);
    }
    _insertComponent() {
        if (!this.currentComponent) {
            vscode.window.showErrorMessage('Aucun composant sélectionné');
            return;
        }
        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showErrorMessage('Aucun éditeur actif trouvé');
            return;
        }
        const position = editor.selection.active;
        editor.edit(editBuilder => {
            editBuilder.insert(position, this.currentComponent.content);
        });
        vscode.window.showInformationMessage(`Composant ${this.currentComponent.name} inséré!`);
    }
    _updatePreview(component) {
        this.currentComponent = component;
        this._update();
    }
    dispose() {
        ComponentPreviewPanel.currentPanel = undefined;
        this._panel.dispose();
        while (this._disposables.length) {
            const x = this._disposables.pop();
            if (x) {
                x.dispose();
            }
        }
    }
    async _update() {
        const webview = this._panel.webview;
        this._panel.title = this.currentComponent
            ? `Prévisualisation - ${this.currentComponent.name}`
            : 'Bibliothèque de Composants';
        this._panel.webview.html = this._getHtmlForWebview(webview);
    }
    _getHtmlForWebview(webview) {
        const nonce = getNonce();
        if (this.currentComponent) {
            return this._getComponentPreviewHtml(webview, nonce);
        }
        else {
            return this._getLibraryOverviewHtml(webview, nonce);
        }
    }
    _getComponentPreviewHtml(webview, nonce) {
        const component = this.currentComponent;
        // Extract styles from component content
        const styleMatch = component.content.match(/<style>([\s\S]*?)<\/style>/i);
        const styles = styleMatch ? styleMatch[1] : '';
        // Extract HTML content (remove style tags)
        const htmlContent = component.content.replace(/<style>[\s\S]*?<\/style>/gi, '').trim();
        return `<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src ${webview.cspSource} 'unsafe-inline'; script-src 'nonce-${nonce}';">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Prévisualisation - ${component.name}</title>
            <style>
                body {
                    font-family: var(--vscode-font-family);
                    color: var(--vscode-foreground);
                    background-color: var(--vscode-editor-background);
                    padding: 20px;
                    margin: 0;
                }
                .preview-header {
                    border-bottom: 1px solid var(--vscode-panel-border);
                    padding-bottom: 15px;
                    margin-bottom: 20px;
                }
                .preview-title {
                    font-size: 18px;
                    font-weight: bold;
                    margin: 0 0 5px 0;
                }
                .preview-description {
                    color: var(--vscode-descriptionForeground);
                    margin: 0;
                }
                .preview-content {
                    border: 1px solid var(--vscode-panel-border);
                    border-radius: 4px;
                    padding: 20px;
                    margin: 20px 0;
                    background-color: var(--vscode-editor-background);
                }
                .preview-actions {
                    margin-top: 20px;
                }
                .btn {
                    background-color: var(--vscode-button-background);
                    color: var(--vscode-button-foreground);
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin-right: 10px;
                }
                .btn:hover {
                    background-color: var(--vscode-button-hoverBackground);
                }
                .code-block {
                    background-color: var(--vscode-textBlockQuote-background);
                    border: 1px solid var(--vscode-panel-border);
                    border-radius: 4px;
                    padding: 15px;
                    margin: 15px 0;
                    font-family: var(--vscode-editor-font-family);
                    font-size: var(--vscode-editor-font-size);
                    overflow-x: auto;
                }
                ${styles}
            </style>
        </head>
        <body>
            <div class="preview-header">
                <h1 class="preview-title">${component.name}</h1>
                <p class="preview-description">${component.description}</p>
            </div>
            
            <h3>Prévisualisation</h3>
            <div class="preview-content">
                ${htmlContent}
            </div>
            
            <div class="preview-actions">
                <button class="btn" onclick="insertComponent()">Insérer dans l'éditeur</button>
                <button class="btn" onclick="refreshPreview()">Actualiser</button>
            </div>
            
            <h3>Code source</h3>
            <div class="code-block">
                <pre><code>${escapeHtml(component.content)}</code></pre>
            </div>
            
            <script nonce="${nonce}">
                const vscode = acquireVsCodeApi();
                
                function insertComponent() {
                    vscode.postMessage({
                        command: 'insertComponent'
                    });
                }
                
                function refreshPreview() {
                    vscode.postMessage({
                        command: 'refreshPreview'
                    });
                }
            </script>
        </body>
        </html>`;
    }
    _getLibraryOverviewHtml(webview, nonce) {
        return `<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src ${webview.cspSource} 'unsafe-inline'; script-src 'nonce-${nonce}';">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bibliothèque de Composants Nexa</title>
            <style>
                body {
                    font-family: var(--vscode-font-family);
                    color: var(--vscode-foreground);
                    background-color: var(--vscode-editor-background);
                    padding: 20px;
                    margin: 0;
                }
                .welcome-header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .welcome-title {
                    font-size: 24px;
                    font-weight: bold;
                    margin: 0 0 10px 0;
                }
                .welcome-subtitle {
                    color: var(--vscode-descriptionForeground);
                    margin: 0;
                }
                .features {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin: 30px 0;
                }
                .feature-card {
                    border: 1px solid var(--vscode-panel-border);
                    border-radius: 8px;
                    padding: 20px;
                    background-color: var(--vscode-editor-background);
                }
                .feature-title {
                    font-weight: bold;
                    margin: 0 0 10px 0;
                }
                .feature-description {
                    color: var(--vscode-descriptionForeground);
                    margin: 0;
                }
                .getting-started {
                    margin-top: 30px;
                    padding: 20px;
                    border: 1px solid var(--vscode-panel-border);
                    border-radius: 8px;
                    background-color: var(--vscode-textBlockQuote-background);
                }
            </style>
        </head>
        <body>
            <div class="welcome-header">
                <h1 class="welcome-title">Bibliothèque de Composants Nexa</h1>
                <p class="welcome-subtitle">Galerie de composants .nx prêts à l'emploi avec prévisualisation en temps réel</p>
            </div>
            
            <div class="features">
                <div class="feature-card">
                    <h3 class="feature-title">🎨 Composants Prêts</h3>
                    <p class="feature-description">Une collection de composants UI pré-construits pour accélérer votre développement.</p>
                </div>
                <div class="feature-card">
                    <h3 class="feature-title">👁️ Prévisualisation</h3>
                    <p class="feature-description">Visualisez vos composants en temps réel avant de les intégrer dans votre projet.</p>
                </div>
                <div class="feature-card">
                    <h3 class="feature-title">🔧 Personnalisable</h3>
                    <p class="feature-description">Créez et modifiez facilement vos propres composants personnalisés.</p>
                </div>
                <div class="feature-card">
                    <h3 class="feature-title">📁 Organisé</h3>
                    <p class="feature-description">Composants organisés par catégories pour une navigation facile.</p>
                </div>
            </div>
            
            <div class="getting-started">
                <h3>🚀 Pour commencer</h3>
                <ol>
                    <li>Explorez les composants dans la vue "Composants Nexa" de l'explorateur</li>
                    <li>Cliquez sur un composant pour le prévisualiser</li>
                    <li>Utilisez "Insérer dans l'éditeur" pour ajouter le composant à votre fichier .nx</li>
                    <li>Créez vos propres composants avec la commande "Créer un nouveau composant"</li>
                </ol>
            </div>
        </body>
        </html>`;
    }
}
exports.ComponentPreviewPanel = ComponentPreviewPanel;
ComponentPreviewPanel.viewType = 'nexaComponentPreview';
function getNonce() {
    let text = '';
    const possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    for (let i = 0; i < 32; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
}
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
//# sourceMappingURL=componentPreviewPanel.js.map