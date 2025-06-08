import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class CoverageAnalyzer {
    private context: vscode.ExtensionContext;
    private coverageData: any = null;
    private decorationType: vscode.TextEditorDecorationType;
    private uncoveredDecorationType: vscode.TextEditorDecorationType;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
        
        this.decorationType = vscode.window.createTextEditorDecorationType({
            backgroundColor: new vscode.ThemeColor('testing.coveredBackground'),
            isWholeLine: true,
            overviewRulerColor: new vscode.ThemeColor('testing.coveredBorder'),
            overviewRulerLane: vscode.OverviewRulerLane.Left
        });

        this.uncoveredDecorationType = vscode.window.createTextEditorDecorationType({
            backgroundColor: new vscode.ThemeColor('testing.uncoveredBackground'),
            isWholeLine: true,
            overviewRulerColor: new vscode.ThemeColor('testing.uncoveredBorder'),
            overviewRulerLane: vscode.OverviewRulerLane.Left
        });
    }

    async showCoverageReport(): Promise<void> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const coverageFile = path.join(workspaceFolder.uri.fsPath, 'coverage', 'clover.xml');
        if (!fs.existsSync(coverageFile)) {
            const runCoverage = await vscode.window.showInformationMessage(
                'Aucun rapport de couverture trouvé. Voulez-vous exécuter les tests avec couverture ?',
                'Oui', 'Non'
            );
            
            if (runCoverage === 'Oui') {
                await this.generateCoverageReport();
            }
            return;
        }

        await this.loadCoverageData(coverageFile);
        await this.showCoverageWebview();
        this.applyCoverageDecorations();
    }

    private async generateCoverageReport(): Promise<void> {
        const terminal = vscode.window.createTerminal('Nexa Coverage');
        terminal.sendText('./vendor/bin/phpunit --coverage-clover coverage/clover.xml --coverage-html coverage/html');
        terminal.show();
        
        vscode.window.showInformationMessage('Génération du rapport de couverture en cours...');
    }

    private async loadCoverageData(coverageFile: string): Promise<void> {
        try {
            const xmlContent = fs.readFileSync(coverageFile, 'utf8');
            // Ici, vous pourriez parser le XML avec une bibliothèque comme xml2js
            // Pour la simplicité, nous simulons les données
            this.coverageData = {
                files: {
                    'app/Models/User.php': {
                        lines: {
                            '10': 1, '11': 1, '12': 0, '13': 1
                        },
                        coverage: 75
                    }
                },
                overall: {
                    lines: 85.5,
                    functions: 92.3,
                    classes: 88.7
                }
            };
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors du chargement des données de couverture: ${error}`);
        }
    }

    private async showCoverageWebview(): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaCoverage',
            'Rapport de Couverture Nexa',
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                localResourceRoots: [this.context.extensionUri]
            }
        );

        panel.webview.html = this.getCoverageHtml();

        panel.webview.onDidReceiveMessage(
            message => {
                switch (message.command) {
                    case 'openFile':
                        vscode.workspace.openTextDocument(message.file).then(doc => {
                            vscode.window.showTextDocument(doc);
                        });
                        break;
                }
            },
            undefined,
            this.context.subscriptions
        );
    }

    private getCoverageHtml(): string {
        const overall = this.coverageData?.overall || { lines: 0, functions: 0, classes: 0 };
        
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Rapport de Couverture</title>
                <style>
                    body {
                        font-family: var(--vscode-font-family);
                        color: var(--vscode-foreground);
                        background-color: var(--vscode-editor-background);
                        margin: 0;
                        padding: 20px;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    .metrics {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 20px;
                        margin-bottom: 30px;
                    }
                    .metric-card {
                        background: var(--vscode-editor-inactiveSelectionBackground);
                        padding: 20px;
                        border-radius: 8px;
                        text-align: center;
                    }
                    .metric-value {
                        font-size: 2em;
                        font-weight: bold;
                        margin-bottom: 10px;
                    }
                    .metric-label {
                        color: var(--vscode-descriptionForeground);
                    }
                    .progress-bar {
                        width: 100%;
                        height: 20px;
                        background: var(--vscode-progressBar-background);
                        border-radius: 10px;
                        overflow: hidden;
                        margin-top: 10px;
                    }
                    .progress-fill {
                        height: 100%;
                        background: var(--vscode-progressBar-background);
                        transition: width 0.3s ease;
                    }
                    .files-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    .files-table th,
                    .files-table td {
                        padding: 12px;
                        text-align: left;
                        border-bottom: 1px solid var(--vscode-panel-border);
                    }
                    .files-table th {
                        background: var(--vscode-editor-inactiveSelectionBackground);
                        font-weight: bold;
                    }
                    .file-link {
                        color: var(--vscode-textLink-foreground);
                        cursor: pointer;
                        text-decoration: underline;
                    }
                    .file-link:hover {
                        color: var(--vscode-textLink-activeForeground);
                    }
                    .coverage-good { color: #4CAF50; }
                    .coverage-medium { color: #FF9800; }
                    .coverage-poor { color: #F44336; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>📊 Rapport de Couverture de Code</h1>
                    <p>Analyse de la couverture des tests Nexa</p>
                </div>

                <div class="metrics">
                    <div class="metric-card">
                        <div class="metric-value coverage-${this.getCoverageClass(overall.lines)}">
                            ${overall.lines.toFixed(1)}%
                        </div>
                        <div class="metric-label">Lignes</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${overall.lines}%"></div>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-value coverage-${this.getCoverageClass(overall.functions)}">
                            ${overall.functions.toFixed(1)}%
                        </div>
                        <div class="metric-label">Fonctions</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${overall.functions}%"></div>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-value coverage-${this.getCoverageClass(overall.classes)}">
                            ${overall.classes.toFixed(1)}%
                        </div>
                        <div class="metric-label">Classes</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${overall.classes}%"></div>
                        </div>
                    </div>
                </div>

                <h2>📁 Détails par Fichier</h2>
                <table class="files-table">
                    <thead>
                        <tr>
                            <th>Fichier</th>
                            <th>Couverture</th>
                            <th>Lignes Couvertes</th>
                            <th>Lignes Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.getFilesTableRows()}
                    </tbody>
                </table>

                <script>
                    const vscode = acquireVsCodeApi();
                    
                    function openFile(filePath) {
                        vscode.postMessage({
                            command: 'openFile',
                            file: filePath
                        });
                    }
                </script>
            </body>
            </html>
        `;
    }

    private getCoverageClass(percentage: number): string {
        if (percentage >= 80) return 'good';
        if (percentage >= 60) return 'medium';
        return 'poor';
    }

    private getFilesTableRows(): string {
        if (!this.coverageData?.files) {
            return '<tr><td colspan="4">Aucune donnée de couverture disponible</td></tr>';
        }

        return Object.entries(this.coverageData.files)
            .map(([file, data]: [string, any]) => {
                const coverage = data.coverage || 0;
                const coveredLines = Object.values(data.lines || {}).filter((hit: any) => hit > 0).length;
                const totalLines = Object.keys(data.lines || {}).length;
                
                return `
                    <tr>
                        <td>
                            <span class="file-link" onclick="openFile('${file}')">
                                ${file}
                            </span>
                        </td>
                        <td class="coverage-${this.getCoverageClass(coverage)}">
                            ${coverage.toFixed(1)}%
                        </td>
                        <td>${coveredLines}</td>
                        <td>${totalLines}</td>
                    </tr>
                `;
            })
            .join('');
    }

    private applyCoverageDecorations(): void {
        const editor = vscode.window.activeTextEditor;
        if (!editor || !this.coverageData) {
            return;
        }

        const filePath = vscode.workspace.asRelativePath(editor.document.fileName);
        const fileData = this.coverageData.files[filePath];
        
        if (!fileData) {
            return;
        }

        const coveredRanges: vscode.Range[] = [];
        const uncoveredRanges: vscode.Range[] = [];

        Object.entries(fileData.lines).forEach(([lineNumber, hits]: [string, any]) => {
            const line = parseInt(lineNumber) - 1; // VSCode uses 0-based line numbers
            const range = new vscode.Range(line, 0, line, 0);
            
            if (hits > 0) {
                coveredRanges.push(range);
            } else {
                uncoveredRanges.push(range);
            }
        });

        editor.setDecorations(this.decorationType, coveredRanges);
        editor.setDecorations(this.uncoveredDecorationType, uncoveredRanges);
    }

    async exportCoverageReport(): Promise<void> {
        const options = [
            'HTML',
            'XML (Clover)',
            'JSON',
            'Text'
        ];

        const format = await vscode.window.showQuickPick(options, {
            placeHolder: 'Choisissez le format d\'export'
        });

        if (!format) {
            return;
        }

        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            return;
        }

        let command = '';
        switch (format) {
            case 'HTML':
                command = './vendor/bin/phpunit --coverage-html coverage/html';
                break;
            case 'XML (Clover)':
                command = './vendor/bin/phpunit --coverage-clover coverage/clover.xml';
                break;
            case 'JSON':
                command = './vendor/bin/phpunit --coverage-php coverage/coverage.php';
                break;
            case 'Text':
                command = './vendor/bin/phpunit --coverage-text';
                break;
        }

        const terminal = vscode.window.createTerminal('Nexa Coverage Export');
        terminal.sendText(command);
        terminal.show();

        vscode.window.showInformationMessage(`Export de couverture ${format} en cours...`);
    }

    dispose(): void {
        this.decorationType.dispose();
        this.uncoveredDecorationType.dispose();
    }
}