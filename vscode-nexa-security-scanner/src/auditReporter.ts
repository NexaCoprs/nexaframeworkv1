import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import { SecurityIssue } from './securityScanner';
import { ComplianceReport } from './complianceChecker';
import { DependencyScanResult } from './dependencyScanner';

export interface AuditSection {
    title: string;
    description: string;
    status: 'pass' | 'fail' | 'warning' | 'info';
    issues: SecurityIssue[];
    recommendations: string[];
    score: number;
    maxScore: number;
}

export interface AuditReport {
    id: string;
    title: string;
    description: string;
    timestamp: Date;
    projectPath: string;
    projectName: string;
    version: string;
    
    // Overall metrics
    overallScore: number;
    maxScore: number;
    riskLevel: 'low' | 'medium' | 'high' | 'critical';
    
    // Sections
    sections: AuditSection[];
    
    // Summary
    summary: {
        totalIssues: number;
        criticalIssues: number;
        highIssues: number;
        mediumIssues: number;
        lowIssues: number;
        fixedIssues: number;
        newIssues: number;
    };
    
    // Compliance
    compliance: ComplianceReport[];
    
    // Dependencies
    dependencies?: DependencyScanResult;
    
    // Metadata
    scanDuration: number;
    filesScanned: number;
    linesScanned: number;
    
    // Previous report comparison
    previousReportId?: string;
    improvements: string[];
    regressions: string[];
}

export interface AuditConfiguration {
    includeCompliance: boolean;
    includeDependencies: boolean;
    includeCodeAnalysis: boolean;
    includeFilePermissions: boolean;
    complianceStandards: string[];
    outputFormats: ('html' | 'json' | 'pdf' | 'csv')[];
    customSections: AuditSection[];
    excludePatterns: string[];
    severity: ('low' | 'medium' | 'high' | 'critical')[];
}

export class AuditReporter {
    private outputChannel: vscode.OutputChannel;
    private reportHistory: Map<string, AuditReport> = new Map();
    private configuration: AuditConfiguration;

    constructor() {
        this.outputChannel = vscode.window.createOutputChannel('Nexa Audit Reporter');
        this.configuration = this.getDefaultConfiguration();
        this.loadReportHistory();
    }

    public async generateAuditReport(
        projectPath: string,
        securityIssues: SecurityIssue[],
        complianceReports: ComplianceReport[] = [],
        dependencyResults?: DependencyScanResult,
        config?: Partial<AuditConfiguration>
    ): Promise<AuditReport> {
        const startTime = Date.now();
        
        if (config) {
            this.configuration = { ...this.configuration, ...config };
        }

        const projectName = path.basename(projectPath);
        const reportId = this.generateReportId();
        
        // Get project metadata
        const projectMetadata = await this.getProjectMetadata(projectPath);
        
        // Create audit sections
        const sections = await this.createAuditSections(
            securityIssues,
            complianceReports,
            dependencyResults
        );
        
        // Calculate overall score
        const { overallScore, maxScore, riskLevel } = this.calculateOverallScore(sections);
        
        // Create summary
        const summary = this.createSummary(securityIssues);
        
        // Compare with previous report
        const previousReport = this.getPreviousReport(projectPath);
        const { improvements, regressions } = this.compareWithPreviousReport(
            securityIssues,
            previousReport
        );
        
        const scanDuration = Date.now() - startTime;
        
        const report: AuditReport = {
            id: reportId,
            title: `Rapport d'audit de sécurité - ${projectName}`,
            description: `Audit de sécurité complet du projet ${projectName}`,
            timestamp: new Date(),
            projectPath,
            projectName,
            version: projectMetadata.version || '1.0.0',
            overallScore,
            maxScore,
            riskLevel,
            sections,
            summary,
            compliance: complianceReports,
            dependencies: dependencyResults,
            scanDuration,
            filesScanned: projectMetadata.filesScanned,
            linesScanned: projectMetadata.linesScanned,
            previousReportId: previousReport?.id,
            improvements,
            regressions
        };
        
        // Save report
        this.saveReport(report);
        
        return report;
    }

    public async exportReport(
        report: AuditReport,
        format: 'html' | 'json' | 'pdf' | 'csv',
        outputPath?: string
    ): Promise<string> {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const defaultFileName = `audit-report-${report.projectName}-${timestamp}`;
        
        if (!outputPath) {
            outputPath = path.join(report.projectPath, 'security-reports', `${defaultFileName}.${format}`);
        }
        
        // Ensure output directory exists
        const outputDir = path.dirname(outputPath);
        if (!fs.existsSync(outputDir)) {
            await fs.promises.mkdir(outputDir, { recursive: true });
        }
        
        switch (format) {
            case 'html':
                await this.exportToHtml(report, outputPath);
                break;
            case 'json':
                await this.exportToJson(report, outputPath);
                break;
            case 'csv':
                await this.exportToCsv(report, outputPath);
                break;
            case 'pdf':
                await this.exportToPdf(report, outputPath);
                break;
            default:
                throw new Error(`Format d'export non supporté: ${format}`);
        }
        
        this.outputChannel.appendLine(`Rapport exporté vers: ${outputPath}`);
        return outputPath;
    }

    public async showReport(report: AuditReport): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaAuditReport',
            `Rapport d'audit - ${report.projectName}`,
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                retainContextWhenHidden: true
            }
        );
        
        panel.webview.html = await this.generateHtmlContent(report);
        
        // Handle messages from webview
        panel.webview.onDidReceiveMessage(
            async (message) => {
                switch (message.command) {
                    case 'exportReport':
                        await this.exportReport(report, message.format);
                        break;
                    case 'fixIssue':
                        await this.fixIssue(message.issueId);
                        break;
                    case 'showIssueDetails':
                        await this.showIssueDetails(message.issueId);
                        break;
                }
            },
            undefined
        );
    }

    private async createAuditSections(
        securityIssues: SecurityIssue[],
        complianceReports: ComplianceReport[],
        dependencyResults?: DependencyScanResult
    ): Promise<AuditSection[]> {
        const sections: AuditSection[] = [];
        
        // Security Issues Section
        if (this.configuration.includeCodeAnalysis) {
            sections.push(await this.createSecurityIssuesSection(securityIssues));
        }
        
        // Compliance Section
        if (this.configuration.includeCompliance && complianceReports.length > 0) {
            sections.push(await this.createComplianceSection(complianceReports));
        }
        
        // Dependencies Section
        if (this.configuration.includeDependencies && dependencyResults) {
            sections.push(await this.createDependenciesSection(dependencyResults));
        }
        
        // File Permissions Section
        if (this.configuration.includeFilePermissions) {
            sections.push(await this.createFilePermissionsSection(securityIssues));
        }
        
        // Custom sections
        sections.push(...this.configuration.customSections);
        
        return sections;
    }

    private async createSecurityIssuesSection(issues: SecurityIssue[]): Promise<AuditSection> {
        const criticalIssues = issues.filter(i => i.severity === 'critical');
        const highIssues = issues.filter(i => i.severity === 'high');
        const mediumIssues = issues.filter(i => i.severity === 'medium');
        const lowIssues = issues.filter(i => i.severity === 'low');
        
        const score = this.calculateSecurityScore(issues);
        const maxScore = 100;
        
        let status: 'pass' | 'fail' | 'warning' | 'info' = 'pass';
        if (criticalIssues.length > 0) {
            status = 'fail';
        } else if (highIssues.length > 0) {
            status = 'warning';
        } else if (mediumIssues.length > 0) {
            status = 'warning';
        }
        
        const recommendations = this.generateSecurityRecommendations(issues);
        
        return {
            title: 'Analyse de sécurité du code',
            description: 'Analyse des vulnérabilités et problèmes de sécurité dans le code source',
            status,
            issues,
            recommendations,
            score,
            maxScore
        };
    }

    private async createComplianceSection(reports: ComplianceReport[]): Promise<AuditSection> {
        const allIssues: SecurityIssue[] = [];
        let totalScore = 0;
        let maxScore = 0;
        
        for (const report of reports) {
            totalScore += report.score;
            maxScore += 100; // Assuming max score is 100 for each compliance report
            
            // Convert compliance issues to security issues
            for (const issue of report.issues) {
                allIssues.push({
                    id: `compliance_${issue.id}`,
                    type: 'compliance',
                    severity: issue.severity,
                    title: issue.title,
                    description: issue.description,
                    file: issue.file || '',
                    line: issue.line,
                    rule: issue.rule,
                    fix: issue.fix,
                    references: []
                });
            }
        }
        
        const averageScore = maxScore > 0 ? (totalScore / maxScore) * 100 : 100;
        let status: 'pass' | 'fail' | 'warning' | 'info' = 'pass';
        
        if (averageScore < 60) {
            status = 'fail';
        } else if (averageScore < 80) {
            status = 'warning';
        }
        
        const recommendations = this.generateComplianceRecommendations(reports);
        
        return {
            title: 'Conformité aux standards',
            description: 'Vérification de la conformité aux standards de sécurité',
            status,
            issues: allIssues,
            recommendations,
            score: averageScore,
            maxScore: 100
        };
    }

    private async createDependenciesSection(results: DependencyScanResult): Promise<AuditSection> {
        const issues: SecurityIssue[] = [];
        
        // Convert dependency vulnerabilities to security issues
        for (const dep of results.dependencies) {
            for (const vuln of dep.vulnerabilities) {
                issues.push({
                    id: `dep_${vuln.id}`,
                    type: 'vulnerability',
                    severity: vuln.severity,
                    title: `Vulnérabilité dans ${dep.name}`,
                    description: vuln.description,
                    file: dep.file,
                    line: dep.line,
                    rule: 'dependency_vulnerability',
                    fix: vuln.fixedVersion ? `Mettre à jour vers ${vuln.fixedVersion}` : 'Mettre à jour',
                    references: vuln.references
                });
            }
        }
        
        const score = this.calculateDependencyScore(results);
        let status: 'pass' | 'fail' | 'warning' | 'info' = 'pass';
        
        if (results.criticalVulnerabilities > 0) {
            status = 'fail';
        } else if (results.highVulnerabilities > 0) {
            status = 'warning';
        } else if (results.mediumVulnerabilities > 0) {
            status = 'warning';
        }
        
        const recommendations = this.generateDependencyRecommendations(results);
        
        return {
            title: 'Sécurité des dépendances',
            description: 'Analyse des vulnérabilités dans les dépendances du projet',
            status,
            issues,
            recommendations,
            score,
            maxScore: 100
        };
    }

    private async createFilePermissionsSection(issues: SecurityIssue[]): Promise<AuditSection> {
        const permissionIssues = issues.filter(i => i.rule === 'file_permissions');
        
        const score = permissionIssues.length === 0 ? 100 : Math.max(0, 100 - (permissionIssues.length * 10));
        const status = permissionIssues.length === 0 ? 'pass' : 'warning';
        
        const recommendations = [
            'Vérifier les permissions des fichiers sensibles',
            'Restreindre l\'accès aux fichiers de configuration',
            'Utiliser des permissions appropriées pour les fichiers exécutables'
        ];
        
        return {
            title: 'Permissions des fichiers',
            description: 'Vérification des permissions et de la sécurité des fichiers',
            status,
            issues: permissionIssues,
            recommendations,
            score,
            maxScore: 100
        };
    }

    private calculateOverallScore(sections: AuditSection[]): {
        overallScore: number;
        maxScore: number;
        riskLevel: 'low' | 'medium' | 'high' | 'critical';
    } {
        let totalScore = 0;
        let totalMaxScore = 0;
        
        for (const section of sections) {
            totalScore += section.score;
            totalMaxScore += section.maxScore;
        }
        
        const overallScore = totalMaxScore > 0 ? (totalScore / totalMaxScore) * 100 : 100;
        
        let riskLevel: 'low' | 'medium' | 'high' | 'critical';
        if (overallScore >= 90) {
            riskLevel = 'low';
        } else if (overallScore >= 70) {
            riskLevel = 'medium';
        } else if (overallScore >= 50) {
            riskLevel = 'high';
        } else {
            riskLevel = 'critical';
        }
        
        return {
            overallScore,
            maxScore: 100,
            riskLevel
        };
    }

    private calculateSecurityScore(issues: SecurityIssue[]): number {
        let score = 100;
        
        for (const issue of issues) {
            switch (issue.severity) {
                case 'critical':
                    score -= 20;
                    break;
                case 'high':
                    score -= 10;
                    break;
                case 'medium':
                    score -= 5;
                    break;
                case 'low':
                    score -= 2;
                    break;
            }
        }
        
        return Math.max(0, score);
    }

    private calculateDependencyScore(results: DependencyScanResult): number {
        let score = 100;
        
        score -= results.criticalVulnerabilities * 20;
        score -= results.highVulnerabilities * 10;
        score -= results.mediumVulnerabilities * 5;
        score -= results.lowVulnerabilities * 2;
        
        return Math.max(0, score);
    }

    private createSummary(issues: SecurityIssue[]): AuditReport['summary'] {
        return {
            totalIssues: issues.length,
            criticalIssues: issues.filter(i => i.severity === 'critical').length,
            highIssues: issues.filter(i => i.severity === 'high').length,
            mediumIssues: issues.filter(i => i.severity === 'medium').length,
            lowIssues: issues.filter(i => i.severity === 'low').length,
            fixedIssues: 0, // Would be calculated from previous reports
            newIssues: issues.length // Would be calculated from previous reports
        };
    }

    private generateSecurityRecommendations(issues: SecurityIssue[]): string[] {
        const recommendations: string[] = [];
        
        const criticalIssues = issues.filter(i => i.severity === 'critical');
        const highIssues = issues.filter(i => i.severity === 'high');
        
        if (criticalIssues.length > 0) {
            recommendations.push('Corriger immédiatement toutes les vulnérabilités critiques');
        }
        
        if (highIssues.length > 0) {
            recommendations.push('Planifier la correction des vulnérabilités de haute sévérité');
        }
        
        const sqlInjectionIssues = issues.filter(i => i.rule.includes('sql_injection'));
        if (sqlInjectionIssues.length > 0) {
            recommendations.push('Utiliser des requêtes préparées pour éviter les injections SQL');
        }
        
        const xssIssues = issues.filter(i => i.rule.includes('xss'));
        if (xssIssues.length > 0) {
            recommendations.push('Échapper toutes les sorties utilisateur pour prévenir les attaques XSS');
        }
        
        const authIssues = issues.filter(i => i.rule.includes('auth'));
        if (authIssues.length > 0) {
            recommendations.push('Renforcer les mécanismes d\'authentification et d\'autorisation');
        }
        
        return recommendations;
    }

    private generateComplianceRecommendations(reports: ComplianceReport[]): string[] {
        const recommendations: string[] = [];
        
        for (const report of reports) {
            if (report.score < 100 * 0.8) {
                recommendations.push(`Améliorer la conformité ${report.standard}`);
            }
        }
        
        return recommendations;
    }

    private generateDependencyRecommendations(results: DependencyScanResult): string[] {
        const recommendations: string[] = [];
        
        if (results.criticalVulnerabilities > 0) {
            recommendations.push('Mettre à jour immédiatement les dépendances avec des vulnérabilités critiques');
        }
        
        if (results.vulnerableDependencies > 0) {
            recommendations.push('Planifier la mise à jour des dépendances vulnérables');
        }
        
        const outdatedDeps = results.dependencies.filter(d => d.outdated);
        if (outdatedDeps.length > 0) {
            recommendations.push('Mettre à jour les dépendances obsolètes');
        }
        
        recommendations.push('Mettre en place un processus de surveillance des vulnérabilités');
        
        return recommendations;
    }

    private async getProjectMetadata(projectPath: string): Promise<{
        version: string;
        filesScanned: number;
        linesScanned: number;
    }> {
        let version = '1.0.0';
        let filesScanned = 0;
        let linesScanned = 0;
        
        try {
            // Try to get version from package.json or composer.json
            const packageJsonPath = path.join(projectPath, 'package.json');
            const composerJsonPath = path.join(projectPath, 'composer.json');
            
            if (fs.existsSync(packageJsonPath)) {
                const packageJson = JSON.parse(await fs.promises.readFile(packageJsonPath, 'utf8'));
                version = packageJson.version || version;
            } else if (fs.existsSync(composerJsonPath)) {
                const composerJson = JSON.parse(await fs.promises.readFile(composerJsonPath, 'utf8'));
                version = composerJson.version || version;
            }
            
            // Count files and lines (simplified)
            const files = await this.getProjectFiles(projectPath);
            filesScanned = files.length;
            
            for (const file of files) {
                try {
                    const content = await fs.promises.readFile(file, 'utf8');
                    linesScanned += content.split('\n').length;
                } catch {
                    // Ignore files that can't be read
                }
            }
        } catch (error) {
            this.outputChannel.appendLine(`Erreur lors de la récupération des métadonnées: ${error}`);
        }
        
        return { version, filesScanned, linesScanned };
    }

    private async getProjectFiles(projectPath: string): Promise<string[]> {
        const files: string[] = [];
        const extensions = ['.php', '.js', '.ts', '.py', '.java', '.cs', '.rb', '.go'];
        
        const scanDirectory = async (dir: string) => {
            try {
                const entries = await fs.promises.readdir(dir, { withFileTypes: true });
                
                for (const entry of entries) {
                    const fullPath = path.join(dir, entry.name);
                    
                    if (entry.isDirectory() && !entry.name.startsWith('.') && entry.name !== 'node_modules') {
                        await scanDirectory(fullPath);
                    } else if (entry.isFile() && extensions.some(ext => entry.name.endsWith(ext))) {
                        files.push(fullPath);
                    }
                }
            } catch {
                // Ignore directories that can't be read
            }
        };
        
        await scanDirectory(projectPath);
        return files;
    }

    private compareWithPreviousReport(
        currentIssues: SecurityIssue[],
        previousReport?: AuditReport
    ): { improvements: string[]; regressions: string[] } {
        const improvements: string[] = [];
        const regressions: string[] = [];
        
        if (!previousReport) {
            return { improvements, regressions };
        }
        
        const previousIssues = previousReport.sections.flatMap(s => s.issues);
        const currentIssueIds = new Set(currentIssues.map(i => `${i.file}:${i.line}:${i.rule}`));
        const previousIssueIds = new Set(previousIssues.map(i => `${i.file}:${i.line}:${i.rule}`));
        
        // Find fixed issues (in previous but not in current)
        for (const prevIssue of previousIssues) {
            const issueId = `${prevIssue.file}:${prevIssue.line}:${prevIssue.rule}`;
            if (!currentIssueIds.has(issueId)) {
                improvements.push(`Corrigé: ${prevIssue.title}`);
            }
        }
        
        // Find new issues (in current but not in previous)
        for (const currentIssue of currentIssues) {
            const issueId = `${currentIssue.file}:${currentIssue.line}:${currentIssue.rule}`;
            if (!previousIssueIds.has(issueId)) {
                regressions.push(`Nouveau: ${currentIssue.title}`);
            }
        }
        
        return { improvements, regressions };
    }

    private getPreviousReport(projectPath: string): AuditReport | undefined {
        // Find the most recent report for this project
        let latestReport: AuditReport | undefined;
        let latestTimestamp = 0;
        
        for (const report of this.reportHistory.values()) {
            if (report.projectPath === projectPath && report.timestamp.getTime() > latestTimestamp) {
                latestReport = report;
                latestTimestamp = report.timestamp.getTime();
            }
        }
        
        return latestReport;
    }

    private generateReportId(): string {
        return `audit_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    private saveReport(report: AuditReport): void {
        this.reportHistory.set(report.id, report);
        
        // Keep only the last 10 reports per project
        const projectReports = Array.from(this.reportHistory.values())
            .filter(r => r.projectPath === report.projectPath)
            .sort((a, b) => b.timestamp.getTime() - a.timestamp.getTime());
        
        if (projectReports.length > 10) {
            for (let i = 10; i < projectReports.length; i++) {
                this.reportHistory.delete(projectReports[i].id);
            }
        }
        
        this.saveReportHistory();
    }

    private loadReportHistory(): void {
        // In a real implementation, this would load from a persistent storage
        // For now, we'll keep it in memory
    }

    private saveReportHistory(): void {
        // In a real implementation, this would save to a persistent storage
        // For now, we'll keep it in memory
    }

    private getDefaultConfiguration(): AuditConfiguration {
        return {
            includeCompliance: true,
            includeDependencies: true,
            includeCodeAnalysis: true,
            includeFilePermissions: true,
            complianceStandards: ['OWASP', 'PCI-DSS'],
            outputFormats: ['html', 'json'],
            customSections: [],
            excludePatterns: ['node_modules/**', 'vendor/**', '.git/**'],
            severity: ['critical', 'high', 'medium', 'low']
        };
    }

    private async exportToHtml(report: AuditReport, outputPath: string): Promise<void> {
        const html = await this.generateHtmlContent(report);
        await fs.promises.writeFile(outputPath, html, 'utf8');
    }

    private async exportToJson(report: AuditReport, outputPath: string): Promise<void> {
        const json = JSON.stringify(report, null, 2);
        await fs.promises.writeFile(outputPath, json, 'utf8');
    }

    private async exportToCsv(report: AuditReport, outputPath: string): Promise<void> {
        const issues = report.sections.flatMap(s => s.issues);
        const headers = ['ID', 'Type', 'Sévérité', 'Titre', 'Description', 'Fichier', 'Ligne', 'Règle', 'Correction'];
        
        const rows = issues.map(issue => [
            issue.id,
            issue.type,
            issue.severity,
            issue.title,
            issue.description,
            issue.file,
            issue.line?.toString() || '',
            issue.rule,
            issue.fix || ''
        ]);
        
        const csv = [headers, ...rows]
            .map(row => row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(','))
            .join('\n');
        
        await fs.promises.writeFile(outputPath, csv, 'utf8');
    }

    private async exportToPdf(report: AuditReport, outputPath: string): Promise<void> {
        // PDF export would require a library like puppeteer or similar
        // For now, we'll create an HTML file and suggest manual conversion
        const htmlPath = outputPath.replace('.pdf', '.html');
        await this.exportToHtml(report, htmlPath);
        
        vscode.window.showInformationMessage(
            `Export PDF non implémenté. Fichier HTML créé: ${htmlPath}. Utilisez un outil externe pour convertir en PDF.`
        );
    }

    private async generateHtmlContent(report: AuditReport): Promise<string> {
        return `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${report.title}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-card .value {
            font-size: 2em;
            font-weight: bold;
            margin: 0;
        }
        .risk-low { color: #28a745; }
        .risk-medium { color: #ffc107; }
        .risk-high { color: #fd7e14; }
        .risk-critical { color: #dc3545; }
        .section {
            background: white;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .section-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-title {
            font-size: 1.3em;
            font-weight: bold;
            margin: 0;
        }
        .section-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pass { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-fail { background: #f8d7da; color: #721c24; }
        .status-info { background: #d1ecf1; color: #0c5460; }
        .section-content {
            padding: 20px;
        }
        .issue {
            border-left: 4px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 0 5px 5px 0;
        }
        .issue.critical { border-left-color: #dc3545; }
        .issue.high { border-left-color: #fd7e14; }
        .issue.medium { border-left-color: #ffc107; }
        .issue.low { border-left-color: #28a745; }
        .issue-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .issue-meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        .issue-description {
            margin-bottom: 10px;
        }
        .issue-fix {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            font-style: italic;
        }
        .recommendations {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .recommendations h4 {
            margin-top: 0;
            color: #495057;
        }
        .recommendations ul {
            margin: 0;
            padding-left: 20px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9em;
        }
        .score-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .score-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        .export-buttons {
            margin: 20px 0;
            text-align: center;
        }
        .export-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .export-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>${report.title}</h1>
        <p>${report.description}</p>
        <p>Généré le ${report.timestamp.toLocaleString('fr-FR')}</p>
    </div>

    <div class="export-buttons">
        <button class="export-button" onclick="exportReport('json')">Exporter JSON</button>
        <button class="export-button" onclick="exportReport('csv')">Exporter CSV</button>
        <button class="export-button" onclick="window.print()">Imprimer</button>
    </div>

    <div class="summary">
        <div class="summary-card">
            <h3>Score Global</h3>
            <p class="value risk-${report.riskLevel}">${Math.round(report.overallScore)}/100</p>
            <div class="score-bar">
                <div class="score-fill risk-${report.riskLevel}" style="width: ${report.overallScore}%; background-color: var(--risk-color);"></div>
            </div>
        </div>
        <div class="summary-card">
            <h3>Niveau de Risque</h3>
            <p class="value risk-${report.riskLevel}">${report.riskLevel.toUpperCase()}</p>
        </div>
        <div class="summary-card">
            <h3>Total des Problèmes</h3>
            <p class="value">${report.summary.totalIssues}</p>
        </div>
        <div class="summary-card">
            <h3>Critiques</h3>
            <p class="value risk-critical">${report.summary.criticalIssues}</p>
        </div>
        <div class="summary-card">
            <h3>Élevés</h3>
            <p class="value risk-high">${report.summary.highIssues}</p>
        </div>
        <div class="summary-card">
            <h3>Moyens</h3>
            <p class="value risk-medium">${report.summary.mediumIssues}</p>
        </div>
    </div>

    ${report.sections.map(section => `
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">${section.title}</h2>
            <span class="section-status status-${section.status}">${section.status}</span>
        </div>
        <div class="section-content">
            <p>${section.description}</p>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0;">
                <span>Score: ${Math.round(section.score)}/${section.maxScore}</span>
                <div class="score-bar" style="width: 200px;">
                    <div class="score-fill" style="width: ${(section.score/section.maxScore)*100}%; background-color: ${section.score > 80 ? '#28a745' : section.score > 60 ? '#ffc107' : '#dc3545'};"></div>
                </div>
            </div>

            ${section.issues.length > 0 ? `
            <h4>Problèmes détectés (${section.issues.length})</h4>
            ${section.issues.map(issue => `
            <div class="issue ${issue.severity}">
                <div class="issue-title">${issue.title}</div>
                <div class="issue-meta">
                    <strong>Sévérité:</strong> ${issue.severity} | 
                    <strong>Fichier:</strong> ${issue.file}${issue.line ? `:${issue.line}` : ''} | 
                    <strong>Règle:</strong> ${issue.rule}
                </div>
                <div class="issue-description">${issue.description}</div>
                ${issue.fix ? `<div class="issue-fix"><strong>Correction suggérée:</strong> ${issue.fix}</div>` : ''}
            </div>
            `).join('')}
            ` : '<p>Aucun problème détecté dans cette section.</p>'}

            ${section.recommendations.length > 0 ? `
            <div class="recommendations">
                <h4>Recommandations</h4>
                <ul>
                    ${section.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                </ul>
            </div>
            ` : ''}
        </div>
    </div>
    `).join('')}

    <div class="footer">
        <p>Rapport généré par Nexa Security Scanner</p>
        <p>Durée du scan: ${report.scanDuration}ms | Fichiers analysés: ${report.filesScanned} | Lignes analysées: ${report.linesScanned}</p>
    </div>

    <script>
        function exportReport(format) {
            if (typeof acquireVsCodeApi !== 'undefined') {
                const vscode = acquireVsCodeApi();
                vscode.postMessage({
                    command: 'exportReport',
                    format: format
                });
            } else {
                alert('Export disponible uniquement dans VS Code');
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

        function showIssueDetails(issueId) {
            if (typeof acquireVsCodeApi !== 'undefined') {
                const vscode = acquireVsCodeApi();
                vscode.postMessage({
                    command: 'showIssueDetails',
                    issueId: issueId
                });
            }
        }
    </script>
</body>
</html>
        `;
    }

    private async fixIssue(issueId: string): Promise<void> {
        // Implementation would depend on the specific issue type
        vscode.window.showInformationMessage(`Correction automatique non implémentée pour l'issue ${issueId}`);
    }

    private async showIssueDetails(issueId: string): Promise<void> {
        // Implementation would show detailed information about the issue
        vscode.window.showInformationMessage(`Détails de l'issue ${issueId}`);
    }

    public getReportHistory(): AuditReport[] {
        return Array.from(this.reportHistory.values())
            .sort((a, b) => b.timestamp.getTime() - a.timestamp.getTime());
    }

    public getReportById(id: string): AuditReport | undefined {
        return this.reportHistory.get(id);
    }

    public deleteReport(id: string): boolean {
        return this.reportHistory.delete(id);
    }

    public updateConfiguration(config: Partial<AuditConfiguration>): void {
        this.configuration = { ...this.configuration, ...config };
    }

    public getConfiguration(): AuditConfiguration {
        return { ...this.configuration };
    }
}