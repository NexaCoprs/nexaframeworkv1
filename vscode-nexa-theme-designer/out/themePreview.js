"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ThemePreview = void 0;
const vscode = require("vscode");
const path = require("path");
const fs_1 = require("fs");
class ThemePreview {
    constructor(context) {
        this.currentTheme = null;
        this.context = context;
    }
    async showPreview(themeData) {
        try {
            this.currentTheme = themeData;
            const html = this.getPreviewHtml(themeData);
            if (this.panel) {
                this.panel.webview.html = html;
                this.panel.reveal();
            }
            else {
                this.createPreviewPanel(html, themeData);
            }
        }
        catch (error) {
            vscode.window.showErrorMessage(`Erreur lors du chargement du thème: ${error}`);
        }
    }
    async showLivePreview(themeData) {
        try {
            this.currentTheme = themeData;
            const html = this.getPreviewHtml(themeData, true);
            if (this.panel) {
                this.panel.webview.html = html;
            }
            else {
                this.createPreviewPanel(html, themeData, true);
            }
        }
        catch (error) {
            vscode.window.showErrorMessage(`Erreur lors du chargement du thème: ${error}`);
        }
    }
    createPreviewPanel(html, themeData, isLive = false) {
        this.panel = vscode.window.createWebviewPanel('nexaThemePreview', `Aperçu - ${themeData.name || 'Thème'}${isLive ? ' (Live)' : ''}`, vscode.ViewColumn.Two, {
            enableScripts: true,
            localResourceRoots: [this.context.extensionUri]
        });
        this.panel.webview.html = html;
        this.panel.onDidDispose(() => {
            this.panel = undefined;
        });
    }
    getPreviewHtml(themeData, isLive = false) {
        const colors = themeData.colors || {};
        const tokenColors = themeData.tokenColors || [];
        return `<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: ${colors['editor.background'] || '#1e1e1e'};
            color: ${colors['editor.foreground'] || '#d4d4d4'};
        }
        .preview-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .theme-info {
            background: ${colors['editor.background'] || '#252526'};
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid ${colors['panel.border'] || '#3c3c3c'};
        }
        .code-sample {
            background: ${colors['editor.background'] || '#1e1e1e'};
            border: 1px solid ${colors['panel.border'] || '#3c3c3c'};
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            overflow-x: auto;
        }
        .language-tab {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            background: ${colors['button.background'] || '#0e639c'};
            color: ${colors['button.foreground'] || '#ffffff'};
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .language-tab:hover {
            background: ${colors['button.hoverBackground'] || '#1177bb'};
        }
        .language-tab.active {
            background: ${colors['button.hoverBackground'] || '#1177bb'};
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="theme-info">
            <h1>${themeData.name || 'Thème sans nom'}</h1>
            <p><strong>Type:</strong> ${themeData.type || 'dark'}</p>
            ${themeData.description ? `<div style="margin-top: 10px; font-style: italic;">${themeData.description}</div>` : ''}
        </div>
        
        <div>
            <button class="language-tab active" onclick="showSample('php')">PHP</button>
            <button class="language-tab" onclick="showSample('javascript')">JavaScript</button>
            <button class="language-tab" onclick="showSample('typescript')">TypeScript</button>
            <button class="language-tab" onclick="showSample('css')">CSS</button>
            <button class="language-tab" onclick="showSample('html')">HTML</button>
            <button class="language-tab" onclick="showSample('markdown')">Markdown</button>
        </div>
        
        <div id="php-sample" class="code-sample">
            ${this.getPHPSample()}
        </div>
        
        <div id="javascript-sample" class="code-sample" style="display: none;">
            ${this.getJavaScriptSample()}
        </div>
        
        <div id="typescript-sample" class="code-sample" style="display: none;">
            ${this.getTypeScriptSample()}
        </div>
        
        <div id="css-sample" class="code-sample" style="display: none;">
            ${this.getCSSSample()}
        </div>
        
        <div id="html-sample" class="code-sample" style="display: none;">
            ${this.getHTMLSample()}
        </div>
        
        <div id="markdown-sample" class="code-sample" style="display: none;">
            ${this.getMarkdownSample()}
        </div>
    </div>
    
    <script>
        function showSample(language) {
            // Masquer tous les échantillons
            const samples = document.querySelectorAll('.code-sample');
            samples.forEach(sample => sample.style.display = 'none');
            
            // Afficher l'échantillon sélectionné
            document.getElementById(language + '-sample').style.display = 'block';
            
            // Mettre à jour les onglets
            const tabs = document.querySelectorAll('.language-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
        }
    </script>
</body>
</html>`;
    }
    getPHPSample() {
        return `&lt;?php<br>
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
}`;
    }
    getJavaScriptSample() {
        return `// Configuration Nexa Framework<br>
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
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;const response = await fetch(\`\${this.config.apiUrl}/\${endpoint}\`);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return await response.json();<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;} catch (error) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;console.error('API Error:', error);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}`;
    }
    getTypeScriptSample() {
        return `interface User {<br>
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
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;const user = await this.apiClient.get(\`users/\${id}\`);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return user as User;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;} catch (error) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return null;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}`;
    }
    getCSSSample() {
        return `/* Styles pour Nexa Framework */<br>
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
&nbsp;&nbsp;&nbsp;&nbsp;transition: background 0.3s;<br>
}<br>
<br>
.nexa-button:hover {<br>
&nbsp;&nbsp;&nbsp;&nbsp;background: #005a9e;<br>
}`;
    }
    getHTMLSample() {
        return `&lt;!DOCTYPE html&gt;<br>
&lt;html lang="fr"&gt;<br>
&lt;head&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;meta charset="UTF-8"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;meta name="viewport" content="width=device-width, initial-scale=1.0"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;title&gt;Nexa Framework&lt;/title&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;link rel="stylesheet" href="/css/nexa.css"&gt;<br>
&lt;/head&gt;<br>
&lt;body&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;div class="nexa-container"&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;header&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;h1&gt;Bienvenue sur Nexa&lt;/h1&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;/header&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;main&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;p&gt;Framework PHP moderne et élégant&lt;/p&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;button class="nexa-button"&gt;Commencer&lt;/button&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;/main&gt;<br>
&nbsp;&nbsp;&nbsp;&nbsp;&lt;/div&gt;<br>
&lt;/body&gt;<br>
&lt;/html&gt;`;
    }
    getMarkdownSample() {
        return `# Nexa Framework<br>
<br>
## Installation<br>
<br>
\`\`\`bash<br>
composer create-project nexa/framework mon-projet<br>
cd mon-projet<br>
php nexa serve<br>
\`\`\`<br>
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
\`\`\`php<br>
class HomeController extends Controller<br>
{<br>
&nbsp;&nbsp;&nbsp;&nbsp;public function index()<br>
&nbsp;&nbsp;&nbsp;&nbsp;{<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return $this->view('home');<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}<br>
\`\`\`<br>
<br>
> **Note** : Consultez la documentation complète sur [nexa-framework.com](https://nexa-framework.com)`;
    }
    async getAvailableThemes() {
        const themesDir = path.join(this.context.globalStorageUri.fsPath, 'themes');
        if (!(0, fs_1.existsSync)(themesDir)) {
            return [];
        }
        const files = (0, fs_1.readdirSync)(themesDir);
        return files
            .filter((file) => file.endsWith('.json'))
            .map((file) => path.basename(file, '.json'));
    }
    getThemePath(themeName) {
        return path.join(this.context.globalStorageUri.fsPath, 'themes', `${themeName}.json`);
    }
    async applyThemeToVSCode(themeData) {
        vscode.window.showInformationMessage(`Application du thème "${themeData.name}" sera disponible dans une prochaine version`);
    }
    async editTheme(themeData) {
        vscode.commands.executeCommand('nexa-theme-designer.editExistingTheme');
    }
    async exportAsHTML() {
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
            (0, fs_1.writeFileSync)(saveUri.fsPath, html);
            vscode.window.showInformationMessage(`Aperçu exporté vers ${saveUri.fsPath}`);
        }
    }
}
exports.ThemePreview = ThemePreview;
//# sourceMappingURL=themePreview.js.map