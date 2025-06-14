"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ReportGenerator = void 0;
const vscode = require("vscode");
const fs = require("fs");
const path = require("path");
class ReportGenerator {
    constructor() {
        this.reportsHistory = [];
        this.outputChannel = vscode.window.createOutputChannel('Nexa Security Reports');
        this.loadReportsHistory();
    }
    async generateSecurityReport(scanResults, complianceReports = [], projectName = 'Unknown Project') {
        const reportId = this.generateReportId();
        const summary = this.calculateReportSummary(scanResults, complianceReports);
        const recommendations = this.generateRecommendations(scanResults, complianceReports);
        const trends = this.calculateTrends();
        const report = {
            id: reportId,
            title: `Rapport de sécurité - ${projectName}`,
            timestamp: new Date(),
            projectName,
            summary,
            scanResults,
            complianceReports,
            recommendations,
            trends
        };
        this.reportsHistory.push(report);
        this.saveReportsHistory();
        return report;
    }
    async exportReport(report, options) {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            throw new Error('Aucun workspace ouvert');
        }
        const reportsDir = path.join(workspaceFolder.uri.fsPath, 'security-reports');
        await this.ensureDirectoryExists(reportsDir);
        const fileName = `security-report-${report.id}.${options.format}`;
        const filePath = path.join(reportsDir, fileName);
        let content;
        switch (options.format) {
            case 'html':
                content = this.generateHTMLReport(report, options);
                break;
            case 'json':
                content = this.generateJSONReport(report, options);
                break;
            case 'csv':
                content = this.generateCSVReport(report, options);
                break;
            case 'xml':
                content = this.generateXMLReport(report, options);
                break;
            case 'pdf':
                // For PDF, we'll generate HTML first then convert
                content = this.generateHTMLReport(report, options);
                // Note: PDF conversion would require additional libraries
                vscode.window.showWarningMessage('Export PDF non implémenté - HTML généré à la place');
                break;
            default:
                throw new Error(`Format d'export non supporté: ${options.format}`);
        }
        await fs.promises.writeFile(filePath, content, 'utf8');
        return filePath;
    }
    async showReport(report) {
        const panel = vscode.window.createWebviewPanel('nexaSecurityReport', `Rapport de sécurité - ${report.projectName}`, vscode.ViewColumn.One, {
            enableScripts: true,
            retainContextWhenHidden: true
        });
        panel.webview.html = this.generateHTMLReport(report, {
            format: 'html',
            includeDetails: true,
            includeCompliance: true,
            includeTrends: true,
            includeRecommendations: true
        });
        // Handle messages from webview
        panel.webview.onDidReceiveMessage(async (message) => {
            switch (message.command) {
                case 'exportReport':
                    try {
                        const filePath = await this.exportReport(report, message.options);
                        vscode.window.showInformationMessage(`Rapport exporté: ${filePath}`);
                    }
                    catch (error) {
                        vscode.window.showErrorMessage(`Erreur d'export: ${error}`);
                    }
                    break;
                case 'fixIssue':
                    await this.fixIssue(message.issueId);
                    break;
            }
        }, undefined);
    }
    getReportsHistory() {
        return this.reportsHistory.slice().reverse(); // Most recent first
    }
    async deleteReport(reportId) {
        const index = this.reportsHistory.findIndex(r => r.id === reportId);
        if (index !== -1) {
            this.reportsHistory.splice(index, 1);
            this.saveReportsHistory();
            return true;
        }
        return false;
    }
    async generateTrendReport(days = 30) {
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - days);
        const recentReports = this.reportsHistory.filter(report => report.timestamp >= cutoffDate);
        const trends = recentReports.map(report => ({
            date: report.timestamp,
            totalIssues: report.summary.totalIssues,
            criticalIssues: report.summary.criticalIssues,
            riskScore: report.summary.riskScore
        }));
        return trends.sort((a, b) => a.date.getTime() - b.date.getTime());
    }
    calculateReportSummary(scanResults, complianceReports) {
        let totalFiles = 0;
        let totalIssues = 0;
        let criticalIssues = 0;
        let highIssues = 0;
        let mediumIssues = 0;
        let lowIssues = 0;
        let infoIssues = 0;
        for (const result of scanResults) {
            totalFiles++;
            totalIssues += result.summary.total;
            criticalIssues += result.summary.critical;
            highIssues += result.summary.high;
            mediumIssues += result.summary.medium;
            lowIssues += result.summary.low;
            infoIssues += result.summary.info;
        }
        const riskScore = this.calculateRiskScore(criticalIssues, highIssues, mediumIssues, lowIssues);
        const complianceScore = this.calculateComplianceScore(complianceReports);
        return {
            totalFiles,
            totalIssues,
            criticalIssues,
            highIssues,
            mediumIssues,
            lowIssues,
            infoIssues,
            riskScore,
            complianceScore
        };
    }
    calculateRiskScore(critical, high, medium, low) {
        // Risk score calculation: weighted sum normalized to 0-100
        const weightedScore = (critical * 10) + (high * 5) + (medium * 2) + (low * 1);
        const maxPossibleScore = 100; // Arbitrary maximum for normalization
        return Math.min(100, Math.round((weightedScore / maxPossibleScore) * 100));
    }
    calculateComplianceScore(complianceReports) {
        if (complianceReports.length === 0) {
            return 100;
        }
        const totalScore = complianceReports.reduce((sum, report) => sum + report.score, 0);
        return Math.round(totalScore / complianceReports.length);
    }
    generateRecommendations(scanResults, complianceReports) {
        const recommendations = [];
        const issuesByType = new Map();
        const issuesBySeverity = new Map();
        // Analyze scan results
        for (const result of scanResults) {
            for (const issue of result.issues) {
                issuesByType.set(issue.type, (issuesByType.get(issue.type) || 0) + 1);
                issuesBySeverity.set(issue.severity, (issuesBySeverity.get(issue.severity) || 0) + 1);
            }
        }
        // Generate recommendations based on most common issues
        const sortedTypes = Array.from(issuesByType.entries())
            .sort((a, b) => b[1] - a[1]);
        for (const [type, count] of sortedTypes.slice(0, 3)) {
            switch (type) {
                case 'vulnerability':
                    recommendations.push(`Priorité: Corriger ${count} vulnérabilité(s) détectée(s)`);
                    break;
                case 'security_smell':
                    recommendations.push(`Améliorer la qualité du code sécurisé (${count} problème(s))`);
                    break;
                case 'compliance':
                    recommendations.push(`Adresser ${count} problème(s) de conformité`);
                    break;
                case 'permission':
                    recommendations.push(`Réviser les permissions de fichiers (${count} problème(s))`);
                    break;
            }
        }
        // Add compliance-specific recommendations
        for (const report of complianceReports) {
            if (report.score < 80) {
                recommendations.push(`Améliorer la conformité ${report.standard} (score: ${report.score}%)`);
            }
            recommendations.push(...report.recommendations.slice(0, 2));
        }
        // Add general recommendations based on severity
        const criticalCount = issuesBySeverity.get('critical') || 0;
        const highCount = issuesBySeverity.get('high') || 0;
        if (criticalCount > 0) {
            recommendations.unshift(`URGENT: Corriger immédiatement ${criticalCount} problème(s) critique(s)`);
        }
        if (highCount > 5) {
            recommendations.push('Planifier une révision de sécurité approfondie');
        }
        return recommendations.slice(0, 10); // Limit to top 10 recommendations
    }
    calculateTrends() {
        return this.reportsHistory.slice(-10).map(report => ({
            date: report.timestamp,
            totalIssues: report.summary.totalIssues,
            criticalIssues: report.summary.criticalIssues,
            riskScore: report.summary.riskScore
        }));
    }
    generateHTMLReport(report, options) {
        const html = `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${report.title}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
        }
        .header .meta {
            color: #7f8c8d;
            margin-top: 10px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-card.critical {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }
        .summary-card.high {
            background: linear-gradient(135deg, #ffa726 0%, #ff7043 100%);
        }
        .summary-card.medium {
            background: linear-gradient(135deg, #ffca28 0%, #ffa000 100%);
        }
        .summary-card.low {
            background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 2em;
        }
        .summary-card p {
            margin: 0;
            opacity: 0.9;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .issue {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }
        .issue.critical {
            border-left-color: #e74c3c;
            background: #fdf2f2;
        }
        .issue.high {
            border-left-color: #f39c12;
            background: #fef9e7;
        }
        .issue.medium {
            border-left-color: #f1c40f;
            background: #fffbf0;
        }
        .issue.low {
            border-left-color: #27ae60;
            background: #f0f9f0;
        }
        .issue-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .issue-description {
            color: #5a6c7d;
            margin-bottom: 10px;
        }
        .issue-meta {
            font-size: 0.9em;
            color: #7f8c8d;
        }
        .recommendations {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 4px;
            padding: 20px;
        }
        .recommendations ul {
            margin: 0;
            padding-left: 20px;
        }
        .recommendations li {
            margin-bottom: 8px;
        }
        .compliance-section {
            background: #f0f8ff;
            border: 1px solid #b3d9ff;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .compliance-score {
            font-size: 1.2em;
            font-weight: bold;
            color: #2c3e50;
        }
        .export-buttons {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2980b9;
        }
        .chart-container {
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>${report.title}</h1>
            <div class="meta">
                <p>Généré le ${report.timestamp.toLocaleDateString('fr-FR')} à ${report.timestamp.toLocaleTimeString('fr-FR')}</p>
                <p>Projet: ${report.projectName}</p>
            </div>
        </div>

        <div class="summary">
            <div class="summary-card">
                <h3>${report.summary.totalFiles}</h3>
                <p>Fichiers analysés</p>
            </div>
            <div class="summary-card critical">
                <h3>${report.summary.criticalIssues}</h3>
                <p>Problèmes critiques</p>
            </div>
            <div class="summary-card high">
                <h3>${report.summary.highIssues}</h3>
                <p>Problèmes élevés</p>
            </div>
            <div class="summary-card medium">
                <h3>${report.summary.mediumIssues}</h3>
                <p>Problèmes moyens</p>
            </div>
            <div class="summary-card low">
                <h3>${report.summary.lowIssues}</h3>
                <p>Problèmes faibles</p>
            </div>
            <div class="summary-card">
                <h3>${report.summary.riskScore}/100</h3>
                <p>Score de risque</p>
            </div>
        </div>

        ${options.includeRecommendations ? this.generateRecommendationsHTML(report.recommendations) : ''}
        
        ${options.includeCompliance ? this.generateComplianceHTML(report.complianceReports) : ''}
        
        ${options.includeDetails ? this.generateIssuesHTML(report.scanResults) : ''}
        
        ${options.includeTrends ? this.generateTrendsHTML(report.trends) : ''}

        <div class="export-buttons">
            <button class="btn" onclick="exportReport('json')">Exporter JSON</button>
            <button class="btn" onclick="exportReport('csv')">Exporter CSV</button>
            <button class="btn" onclick="exportReport('xml')">Exporter XML</button>
        </div>
    </div>

    <script>
        function exportReport(format) {
            if (typeof acquireVsCodeApi !== 'undefined') {
                const vscode = acquireVsCodeApi();
                vscode.postMessage({
                    command: 'exportReport',
                    options: {
                        format: format,
                        includeDetails: true,
                        includeCompliance: true,
                        includeTrends: true,
                        includeRecommendations: true
                    }
                });
            }
        }
        
        function fixIssue(issueId) {
            if (typeof acquireVsCodeApi !== 'undefined') {
                const vscode = acquireVsCodeApi();
                vscode.postMessage({
                    command: 'fixIssue',
                    issueId: issueId
                });
            }
        }
    </script>
</body>
</html>`;
        return html;
    }
    generateRecommendationsHTML(recommendations) {
        if (recommendations.length === 0) {
            return '';
        }
        return `
        <div class="section">
            <h2>🎯 Recommandations</h2>
            <div class="recommendations">
                <ul>
                    ${recommendations.map(rec => `<li>${rec}</li>`).join('')}
                </ul>
            </div>
        </div>`;
    }
    generateComplianceHTML(complianceReports) {
        if (complianceReports.length === 0) {
            return '';
        }
        return `
        <div class="section">
            <h2>📋 Conformité</h2>
            ${complianceReports.map(report => `
                <div class="compliance-section">
                    <h3>${report.standard}</h3>
                    <div class="compliance-score">Score: ${report.score}%</div>
                    <p>Règles respectées: ${report.passedRules}/${report.totalRules}</p>
                    <p>Problèmes détectés: ${report.failedRules}</p>
                </div>
            `).join('')}
        </div>`;
    }
    generateIssuesHTML(scanResults) {
        const allIssues = scanResults.flatMap(result => result.issues);
        if (allIssues.length === 0) {
            return '<div class="section"><h2>✅ Aucun problème détecté</h2></div>';
        }
        return `
        <div class="section">
            <h2>🔍 Problèmes détectés</h2>
            ${allIssues.map(issue => `
                <div class="issue ${issue.severity}">
                    <div class="issue-title">${issue.title}</div>
                    <div class="issue-description">${issue.description}</div>
                    <div class="issue-meta">
                        📁 ${path.basename(issue.file)} 
                        ${issue.line ? `📍 Ligne ${issue.line}` : ''}
                        🏷️ ${issue.severity.toUpperCase()}
                        ${issue.fix ? `<br>💡 ${issue.fix}` : ''}
                    </div>
                </div>
            `).join('')}
        </div>`;
    }
    generateTrendsHTML(trends) {
        if (trends.length === 0) {
            return '';
        }
        return `
        <div class="section">
            <h2>📈 Tendances</h2>
            <div class="chart-container">
                <p>Évolution des problèmes de sécurité sur les ${trends.length} derniers rapports</p>
                <!-- Chart implementation would go here -->
            </div>
        </div>`;
    }
    generateJSONReport(report, options) {
        const exportData = {
            ...report,
            exportOptions: options,
            exportedAt: new Date().toISOString()
        };
        return JSON.stringify(exportData, null, 2);
    }
    generateCSVReport(report, options) {
        const allIssues = report.scanResults.flatMap(result => result.issues);
        const headers = [
            'ID', 'Type', 'Sévérité', 'Titre', 'Description', 'Fichier', 'Ligne', 'Règle', 'Correction'
        ];
        const rows = allIssues.map(issue => [
            issue.id,
            issue.type,
            issue.severity,
            `"${issue.title.replace(/"/g, '""')}"`,
            `"${issue.description.replace(/"/g, '""')}"`,
            issue.file,
            issue.line || '',
            issue.rule,
            `"${(issue.fix || '').replace(/"/g, '""')}"`
        ]);
        return [headers.join(','), ...rows.map(row => row.join(','))].join('\n');
    }
    generateXMLReport(report, options) {
        const allIssues = report.scanResults.flatMap(result => result.issues);
        return `<?xml version="1.0" encoding="UTF-8"?>
<securityReport>
    <metadata>
        <id>${report.id}</id>
        <title>${this.escapeXml(report.title)}</title>
        <timestamp>${report.timestamp.toISOString()}</timestamp>
        <projectName>${this.escapeXml(report.projectName)}</projectName>
    </metadata>
    <summary>
        <totalFiles>${report.summary.totalFiles}</totalFiles>
        <totalIssues>${report.summary.totalIssues}</totalIssues>
        <criticalIssues>${report.summary.criticalIssues}</criticalIssues>
        <highIssues>${report.summary.highIssues}</highIssues>
        <mediumIssues>${report.summary.mediumIssues}</mediumIssues>
        <lowIssues>${report.summary.lowIssues}</lowIssues>
        <riskScore>${report.summary.riskScore}</riskScore>
    </summary>
    <issues>
        ${allIssues.map(issue => `
        <issue>
            <id>${issue.id}</id>
            <type>${issue.type}</type>
            <severity>${issue.severity}</severity>
            <title>${this.escapeXml(issue.title)}</title>
            <description>${this.escapeXml(issue.description)}</description>
            <file>${this.escapeXml(issue.file)}</file>
            <line>${issue.line || ''}</line>
            <rule>${issue.rule}</rule>
            <fix>${this.escapeXml(issue.fix || '')}</fix>
        </issue>`).join('')}
    </issues>
</securityReport>`;
    }
    escapeXml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    generateReportId() {
        return `report_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
    async ensureDirectoryExists(dirPath) {
        try {
            await fs.promises.access(dirPath);
        }
        catch {
            await fs.promises.mkdir(dirPath, { recursive: true });
        }
    }
    async fixIssue(issueId) {
        // Find the issue in recent reports
        for (const report of this.reportsHistory) {
            for (const scanResult of report.scanResults) {
                const issue = scanResult.issues.find(i => i.id === issueId);
                if (issue && issue.fix) {
                    // Implement auto-fix logic here
                    vscode.commands.executeCommand('nexa.security.fixVulnerability', issue);
                    return;
                }
            }
        }
        vscode.window.showWarningMessage('Problème non trouvé ou correction automatique non disponible');
    }
    loadReportsHistory() {
        try {
            const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
            if (workspaceFolder) {
                const historyPath = path.join(workspaceFolder.uri.fsPath, '.vscode', 'nexa-security-history.json');
                if (fs.existsSync(historyPath)) {
                    const data = fs.readFileSync(historyPath, 'utf8');
                    this.reportsHistory = JSON.parse(data).map((report) => ({
                        ...report,
                        timestamp: new Date(report.timestamp)
                    }));
                }
            }
        }
        catch (error) {
            // Ignore errors, start with empty history
        }
    }
    saveReportsHistory() {
        try {
            const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
            if (workspaceFolder) {
                const vscodeDir = path.join(workspaceFolder.uri.fsPath, '.vscode');
                if (!fs.existsSync(vscodeDir)) {
                    fs.mkdirSync(vscodeDir, { recursive: true });
                }
                const historyPath = path.join(vscodeDir, 'nexa-security-history.json');
                // Keep only last 50 reports
                const recentReports = this.reportsHistory.slice(-50);
                fs.writeFileSync(historyPath, JSON.stringify(recentReports, null, 2));
            }
        }
        catch (error) {
            // Ignore save errors
        }
    }
}
exports.ReportGenerator = ReportGenerator;
//# sourceMappingURL=reportGenerator.js.map