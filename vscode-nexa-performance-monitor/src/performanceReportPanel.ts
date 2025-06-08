import * as vscode from 'vscode';
import { PerformanceMonitor, PerformanceMetrics } from './performanceMonitor';
import { PerformanceAnalyzer, ProjectAnalysis, OptimizationSuggestion } from './performanceAnalyzer';

export class PerformanceReportPanel {
    public static currentPanel: PerformanceReportPanel | undefined;
    private readonly _panel: vscode.WebviewPanel;
    private _disposables: vscode.Disposable[] = [];

    public static createOrShow(
        extensionUri: vscode.Uri,
        performanceMonitor: PerformanceMonitor,
        performanceAnalyzer: PerformanceAnalyzer
    ) {
        const column = vscode.window.activeTextEditor
            ? vscode.window.activeTextEditor.viewColumn
            : undefined;

        if (PerformanceReportPanel.currentPanel) {
            PerformanceReportPanel.currentPanel._panel.reveal(column);
            PerformanceReportPanel.currentPanel._update(performanceMonitor, performanceAnalyzer);
            return;
        }

        const panel = vscode.window.createWebviewPanel(
            'nexaPerformanceReport',
            'Rapport de Performance Nexa',
            column || vscode.ViewColumn.One,
            {
                enableScripts: true,
                localResourceRoots: [vscode.Uri.joinPath(extensionUri, 'media')]
            }
        );

        PerformanceReportPanel.currentPanel = new PerformanceReportPanel(
            panel,
            extensionUri,
            performanceMonitor,
            performanceAnalyzer
        );
    }

    private constructor(
        panel: vscode.WebviewPanel,
        extensionUri: vscode.Uri,
        private performanceMonitor: PerformanceMonitor,
        private performanceAnalyzer: PerformanceAnalyzer
    ) {
        this._panel = panel;
        this._update(performanceMonitor, performanceAnalyzer);
        this._panel.onDidDispose(() => this.dispose(), null, this._disposables);

        this._panel.webview.onDidReceiveMessage(
            message => {
                switch (message.command) {
                    case 'refresh':
                        this._update(this.performanceMonitor, this.performanceAnalyzer);
                        return;
                    case 'exportReport':
                        this._exportReport();
                        return;
                    case 'analyzeFile':
                        if (message.filePath) {
                            this._analyzeSpecificFile(message.filePath);
                        }
                        return;
                }
            },
            null,
            this._disposables
        );
    }

    public dispose() {
        PerformanceReportPanel.currentPanel = undefined;
        this._panel.dispose();
        while (this._disposables.length) {
            const x = this._disposables.pop();
            if (x) {
                x.dispose();
            }
        }
    }

    private async _update(
        performanceMonitor: PerformanceMonitor,
        performanceAnalyzer: PerformanceAnalyzer
    ) {
        const webview = this._panel.webview;
        this._panel.title = 'Rapport de Performance Nexa';
        this._panel.webview.html = await this._getHtmlForWebview(
            webview,
            performanceMonitor,
            performanceAnalyzer
        );
    }

    private async _getHtmlForWebview(
        webview: vscode.Webview,
        performanceMonitor: PerformanceMonitor,
        performanceAnalyzer: PerformanceAnalyzer
    ): Promise<string> {
        const nonce = this._getNonce();
        const metrics = performanceMonitor.getMetrics();
        const summary = performanceMonitor.getSummary();
        
        // Get project analysis
        let projectAnalysis: ProjectAnalysis | null = null;
        try {
            if (vscode.workspace.workspaceFolders && vscode.workspace.workspaceFolders.length > 0) {
                projectAnalysis = await performanceAnalyzer.analyzeProject(vscode.workspace.workspaceFolders[0].uri.fsPath);
            }
        } catch (error) {
            console.error('Error analyzing project:', error);
        }

        const optimizationSuggestions = await performanceAnalyzer.getOptimizationSuggestions();

        return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src ${webview.cspSource} 'unsafe-inline'; script-src 'nonce-${nonce}'; img-src ${webview.cspSource} https:;">
    <title>Rapport de Performance Nexa</title>
    <style>
        body {
            font-family: var(--vscode-font-family);
            font-size: var(--vscode-font-size);
            color: var(--vscode-foreground);
            background-color: var(--vscode-editor-background);
            padding: 20px;
            line-height: 1.6;
        }
        .header {
            border-bottom: 1px solid var(--vscode-panel-border);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: var(--vscode-textLink-foreground);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header .subtitle {
            color: var(--vscode-descriptionForeground);
            margin-top: 5px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--vscode-editor-inactiveSelectionBackground);
            border-radius: 8px;
            border: 1px solid var(--vscode-panel-border);
        }
        .section h2 {
            margin-top: 0;
            color: var(--vscode-textLink-foreground);
            border-bottom: 1px solid var(--vscode-panel-border);
            padding-bottom: 10px;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .metric-card {
            background-color: var(--vscode-input-background);
            padding: 15px;
            border-radius: 6px;
            border: 1px solid var(--vscode-input-border);
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--vscode-textLink-foreground);
        }
        .metric-label {
            color: var(--vscode-descriptionForeground);
            font-size: 12px;
            margin-top: 5px;
        }
        .chart-container {
            height: 300px;
            margin: 20px 0;
            background-color: var(--vscode-input-background);
            border-radius: 6px;
            padding: 15px;
            border: 1px solid var(--vscode-input-border);
        }
        .issues-list {
            list-style: none;
            padding: 0;
        }
        .issue-item {
            background-color: var(--vscode-input-background);
            margin: 10px 0;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid var(--vscode-notificationsWarningIcon-foreground);
        }
        .issue-item.high {
            border-left-color: var(--vscode-notificationsErrorIcon-foreground);
        }
        .issue-item.medium {
            border-left-color: var(--vscode-notificationsWarningIcon-foreground);
        }
        .issue-item.low {
            border-left-color: var(--vscode-notificationsInfoIcon-foreground);
        }
        .issue-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .issue-description {
            color: var(--vscode-descriptionForeground);
            font-size: 14px;
        }
        .issue-location {
            font-family: var(--vscode-editor-font-family);
            font-size: 12px;
            color: var(--vscode-textLink-foreground);
            margin-top: 5px;
            cursor: pointer;
        }
        .suggestions-list {
            list-style: none;
            padding: 0;
        }
        .suggestion-item {
            background-color: var(--vscode-input-background);
            margin: 10px 0;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid var(--vscode-notificationsInfoIcon-foreground);
        }
        .suggestion-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--vscode-textLink-foreground);
        }
        .suggestion-description {
            color: var(--vscode-descriptionForeground);
            margin-bottom: 10px;
        }
        .suggestion-impact {
            font-size: 12px;
            color: var(--vscode-descriptionForeground);
        }
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            background-color: var(--vscode-button-background);
            color: var(--vscode-button-foreground);
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: var(--vscode-button-hoverBackground);
        }
        .btn-secondary {
            background-color: var(--vscode-button-secondaryBackground);
            color: var(--vscode-button-secondaryForeground);
        }
        .btn-secondary:hover {
            background-color: var(--vscode-button-secondaryHoverBackground);
        }
        .no-data {
            text-align: center;
            color: var(--vscode-descriptionForeground);
            padding: 40px;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-good { background-color: var(--vscode-testing-iconPassed); }
        .status-warning { background-color: var(--vscode-notificationsWarningIcon-foreground); }
        .status-error { background-color: var(--vscode-notificationsErrorIcon-foreground); }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            📊 Rapport de Performance Nexa
            <span class="status-indicator ${this._getOverallStatus(metrics, projectAnalysis)}"></span>
        </h1>
        <div class="subtitle">
            Généré le ${new Date().toLocaleString('fr-FR')}
        </div>
    </div>

    ${this._generateMetricsSection(metrics, summary)}
    ${this._generateProjectAnalysisSection(projectAnalysis)}
    ${this._generateOptimizationSection(optimizationSuggestions)}
    ${this._generateChartsSection(metrics)}

    <div class="actions">
        <button class="btn" onclick="refreshReport()">🔄 Actualiser</button>
        <button class="btn btn-secondary" onclick="exportReport()">📄 Exporter</button>
    </div>

    <script nonce="${nonce}">
        const vscode = acquireVsCodeApi();
        
        function refreshReport() {
            vscode.postMessage({ command: 'refresh' });
        }
        
        function exportReport() {
            vscode.postMessage({ command: 'exportReport' });
        }
        
        function analyzeFile(filePath) {
            vscode.postMessage({ command: 'analyzeFile', filePath: filePath });
        }
        
        // Simple chart rendering
        function renderChart(canvasId, data, label) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !data || data.length === 0) return;
            
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);
            
            // Set styles
            ctx.strokeStyle = getComputedStyle(document.body).getPropertyValue('--vscode-textLink-foreground');
            ctx.fillStyle = getComputedStyle(document.body).getPropertyValue('--vscode-textLink-foreground') + '40';
            ctx.lineWidth = 2;
            
            // Calculate points
            const maxValue = Math.max(...data);
            const minValue = Math.min(...data);
            const range = maxValue - minValue || 1;
            
            const points = data.map((value, index) => ({
                x: (index / (data.length - 1)) * (width - 40) + 20,
                y: height - 40 - ((value - minValue) / range) * (height - 60)
            }));
            
            // Draw line
            ctx.beginPath();
            points.forEach((point, index) => {
                if (index === 0) {
                    ctx.moveTo(point.x, point.y);
                } else {
                    ctx.lineTo(point.x, point.y);
                }
            });
            ctx.stroke();
            
            // Fill area under curve
            ctx.lineTo(points[points.length - 1].x, height - 20);
            ctx.lineTo(points[0].x, height - 20);
            ctx.closePath();
            ctx.fill();
        }
        
        // Render charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const metricsData = ${JSON.stringify(metrics)};
            if (metricsData && metricsData.length > 0) {
                renderChart('memoryChart', metricsData.map(m => m.memoryUsage), 'Mémoire');
                renderChart('responseChart', metricsData.map(m => m.responseTime), 'Temps de réponse');
                renderChart('cpuChart', metricsData.map(m => m.cpuUsage), 'CPU');
            }
        });
    </script>
</body>
</html>`;
    }

    private _generateMetricsSection(metrics: PerformanceMetrics[], summary: any): string {
        if (!metrics || metrics.length === 0) {
            return `
            <div class="section">
                <h2>📈 Métriques de Performance</h2>
                <div class="no-data">
                    <p>Aucune métrique disponible</p>
                    <p>Démarrez le monitoring pour collecter des données</p>
                </div>
            </div>`;
        }

        const latest = metrics[metrics.length - 1];
        const avgMemory = metrics.reduce((sum, m) => sum + m.memoryUsage, 0) / metrics.length;
        const avgResponseTime = metrics.reduce((sum, m) => sum + m.responseTime, 0) / metrics.length;
        const maxMemory = Math.max(...metrics.map(m => m.memoryUsage));
        const maxResponseTime = Math.max(...metrics.map(m => m.responseTime));

        return `
        <div class="section">
            <h2>📈 Métriques de Performance</h2>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value">${latest.memoryUsage}MB</div>
                    <div class="metric-label">Mémoire actuelle</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${Math.round(avgMemory)}MB</div>
                    <div class="metric-label">Mémoire moyenne</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${maxMemory}MB</div>
                    <div class="metric-label">Pic mémoire</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${Math.round(latest.responseTime)}ms</div>
                    <div class="metric-label">Temps de réponse actuel</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${Math.round(avgResponseTime)}ms</div>
                    <div class="metric-label">Temps de réponse moyen</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${Math.round(maxResponseTime)}ms</div>
                    <div class="metric-label">Temps de réponse max</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${latest.cpuUsage}%</div>
                    <div class="metric-label">CPU actuel</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${latest.errorRate.toFixed(1)}%</div>
                    <div class="metric-label">Taux d'erreur</div>
                </div>
            </div>
        </div>`;
    }

    private _generateProjectAnalysisSection(projectAnalysis: ProjectAnalysis | null): string {
        if (!projectAnalysis) {
            return `
            <div class="section">
                <h2>🔍 Analyse du Projet</h2>
                <div class="no-data">
                    <p>Analyse du projet non disponible</p>
                    <p>Vérifiez que le workspace contient des fichiers analysables</p>
                </div>
            </div>`;
        }

        const issuesHtml = projectAnalysis.issues.length > 0 
            ? projectAnalysis.issues.map(issue => `
                <li class="issue-item ${issue.severity}">
                    <div class="issue-title">${this._escapeHtml(issue.message)}</div>
                    <div class="issue-description">${this._escapeHtml(issue.description)}</div>
                    <div class="issue-location" onclick="analyzeFile('${this._escapeHtml(issue.file)}')">
                        📁 ${this._escapeHtml(issue.file)}:${issue.line}
                    </div>
                </li>`).join('')
            : '<li class="issue-item low"><div class="issue-title">✅ Aucun problème détecté</div></li>';

        return `
        <div class="section">
            <h2>🔍 Analyse du Projet</h2>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value">${projectAnalysis.totalFiles}</div>
                    <div class="metric-label">Fichiers analysés</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${projectAnalysis.totalLines}</div>
                    <div class="metric-label">Lignes de code</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${projectAnalysis.issues.length}</div>
                    <div class="metric-label">Problèmes détectés</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">${Math.round(projectAnalysis.averageComplexity)}</div>
                    <div class="metric-label">Complexité moyenne</div>
                </div>
            </div>
            <h3>🚨 Problèmes Détectés</h3>
            <ul class="issues-list">
                ${issuesHtml}
            </ul>
        </div>`;
    }

    private _generateOptimizationSection(suggestions: OptimizationSuggestion[]): string {
        const suggestionsHtml = suggestions.length > 0
            ? suggestions.map(suggestion => `
                <li class="suggestion-item">
                    <div class="suggestion-title">${this._escapeHtml(suggestion.title)}</div>
                    <div class="suggestion-description">${this._escapeHtml(suggestion.description)}</div>
                    <div class="suggestion-impact">Impact estimé: ${this._escapeHtml(suggestion.impact)}</div>
                </li>`).join('')
            : '<li class="suggestion-item"><div class="suggestion-title">✅ Aucune suggestion disponible</div></li>';

        return `
        <div class="section">
            <h2>💡 Suggestions d'Optimisation</h2>
            <ul class="suggestions-list">
                ${suggestionsHtml}
            </ul>
        </div>`;
    }

    private _generateChartsSection(metrics: PerformanceMetrics[]): string {
        if (!metrics || metrics.length === 0) {
            return '';
        }

        return `
        <div class="section">
            <h2>📊 Graphiques de Performance</h2>
            <div class="chart-container">
                <h3>Utilisation Mémoire</h3>
                <canvas id="memoryChart" width="600" height="200"></canvas>
            </div>
            <div class="chart-container">
                <h3>Temps de Réponse</h3>
                <canvas id="responseChart" width="600" height="200"></canvas>
            </div>
            <div class="chart-container">
                <h3>Utilisation CPU</h3>
                <canvas id="cpuChart" width="600" height="200"></canvas>
            </div>
        </div>`;
    }

    private _getOverallStatus(metrics: PerformanceMetrics[], projectAnalysis: ProjectAnalysis | null): string {
        if (!metrics || metrics.length === 0) {
            return 'status-warning';
        }

        const latest = metrics[metrics.length - 1];
        const config = vscode.workspace.getConfiguration('nexa.performance');
        const memoryThreshold = config.get<number>('memoryThreshold', 128);
        const timeThreshold = config.get<number>('executionTimeThreshold', 1000);

        const hasHighMemory = latest.memoryUsage > memoryThreshold;
        const hasSlowResponse = latest.responseTime > timeThreshold;
        const hasHighErrorRate = latest.errorRate > 5;
        const hasCriticalIssues = projectAnalysis?.issues.some(issue => issue.severity === 'high') || false;

        if (hasHighErrorRate || hasCriticalIssues) {
            return 'status-error';
        }
        if (hasHighMemory || hasSlowResponse) {
            return 'status-warning';
        }
        return 'status-good';
    }

    private async _exportReport() {
        try {
            const metrics = this.performanceMonitor.getMetrics();
            const summary = this.performanceMonitor.getSummary();
            
            let projectAnalysis: ProjectAnalysis | null = null;
            if (vscode.workspace.workspaceFolders && vscode.workspace.workspaceFolders.length > 0) {
                projectAnalysis = await this.performanceAnalyzer.analyzeProject(vscode.workspace.workspaceFolders[0].uri.fsPath);
            }
            
            const optimizationSuggestions = await this.performanceAnalyzer.getOptimizationSuggestions();
            
            const report = {
                timestamp: new Date().toISOString(),
                metrics,
                summary,
                projectAnalysis,
                optimizationSuggestions
            };
            
            const reportJson = JSON.stringify(report, null, 2);
            
            const saveUri = await vscode.window.showSaveDialog({
                defaultUri: vscode.Uri.file(`performance-report-${new Date().toISOString().split('T')[0]}.json`),
                filters: {
                    'JSON': ['json'],
                    'All Files': ['*']
                }
            });
            
            if (saveUri) {
                await vscode.workspace.fs.writeFile(saveUri, Buffer.from(reportJson, 'utf8'));
                vscode.window.showInformationMessage(`Rapport exporté vers ${saveUri.fsPath}`);
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'export: ${error}`);
        }
    }

    private async _analyzeSpecificFile(filePath: string) {
        try {
            const analysis = await this.performanceAnalyzer.analyzeFile(filePath);
            if (analysis && analysis.issues.length > 0) {
                const message = `Fichier ${filePath}: ${analysis.issues.length} problème(s) détecté(s)`;
                vscode.window.showWarningMessage(message);
            } else {
                vscode.window.showInformationMessage(`Fichier ${filePath}: Aucun problème détecté`);
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de l'analyse: ${error}`);
        }
    }

    private _getNonce(): string {
        let text = '';
        const possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for (let i = 0; i < 32; i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        }
        return text;
    }

    private _escapeHtml(text: string): string {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}