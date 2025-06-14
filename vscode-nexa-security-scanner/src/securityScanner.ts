import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import { VulnerabilityDetector } from './vulnerabilityDetector';
import { ComplianceChecker } from './complianceChecker';

export interface SecurityIssue {
    id: string;
    type: 'vulnerability' | 'security_smell' | 'compliance' | 'permission';
    severity: 'critical' | 'high' | 'medium' | 'low' | 'info';
    title: string;
    description: string;
    file: string;
    line?: number;
    column?: number;
    rule: string;
    fix?: string;
    references?: string[];
}

export interface ScanResult {
    issues: SecurityIssue[];
    summary: {
        total: number;
        critical: number;
        high: number;
        medium: number;
        low: number;
        info: number;
    };
    scanTime: number;
    timestamp: Date;
}

export class SecurityScanner {
    private vulnerabilityDetector: VulnerabilityDetector;
    private complianceChecker: ComplianceChecker;
    private diagnosticCollection: vscode.DiagnosticCollection;
    private securityRules: Map<string, SecurityRule> = new Map();

    constructor() {
        this.vulnerabilityDetector = new VulnerabilityDetector();
        this.complianceChecker = new ComplianceChecker();
        this.diagnosticCollection = vscode.languages.createDiagnosticCollection('nexa-security');
        this.initializeSecurityRules();
    }

    public async scanCurrentFile(): Promise<void> {
        const activeEditor = vscode.window.activeTextEditor;
        if (!activeEditor) {
            vscode.window.showWarningMessage('Aucun fichier ouvert pour le scan');
            return;
        }

        const document = activeEditor.document;
        if (document.languageId !== 'php') {
            vscode.window.showWarningMessage('Le scan de sécurité est disponible uniquement pour les fichiers PHP');
            return;
        }

        vscode.window.withProgress({
            location: vscode.ProgressLocation.Notification,
            title: 'Scan de sécurité en cours...',
            cancellable: false
        }, async (progress) => {
            progress.report({ increment: 0, message: 'Analyse du fichier...' });
            
            const result = await this.scanFile(document.uri.fsPath);
            
            progress.report({ increment: 50, message: 'Génération du rapport...' });
            
            this.updateDiagnostics(document.uri, result.issues);
            this.showScanResults(result, document.fileName);
            
            progress.report({ increment: 100, message: 'Scan terminé' });
        });
    }

    public async scanEntireProject(): Promise<ScanResult | null> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return null;
        }

        return vscode.window.withProgress({
            location: vscode.ProgressLocation.Notification,
            title: 'Scan de sécurité du projet...',
            cancellable: true
        }, async (progress, token) => {
            const phpFiles = await this.findPhpFiles(workspaceFolder.uri.fsPath);
            const totalFiles = phpFiles.length;
            let processedFiles = 0;
            const allIssues: SecurityIssue[] = [];

            for (const file of phpFiles) {
                if (token.isCancellationRequested) {
                    break;
                }

                progress.report({
                    increment: (100 / totalFiles),
                    message: `Analyse de ${path.basename(file)} (${processedFiles + 1}/${totalFiles})`
                });

                const result = await this.scanFile(file);
                allIssues.push(...result.issues);
                
                // Update diagnostics for each file
                const uri = vscode.Uri.file(file);
                this.updateDiagnostics(uri, result.issues);
                
                processedFiles++;
            }

            const projectResult: ScanResult = {
                issues: allIssues,
                summary: this.calculateSummary(allIssues),
                scanTime: Date.now(),
                timestamp: new Date()
            };

            this.showProjectScanResults(projectResult);
            return projectResult;
        });
    }

    public async fixVulnerability(vulnerability: SecurityIssue): Promise<void> {
        if (!vulnerability.fix) {
            vscode.window.showWarningMessage('Aucune correction automatique disponible pour cette vulnérabilité');
            return;
        }

        const document = await vscode.workspace.openTextDocument(vulnerability.file);
        const editor = await vscode.window.showTextDocument(document);

        if (vulnerability.line !== undefined) {
            const line = document.lineAt(vulnerability.line - 1);
            const range = line.range;
            
            await editor.edit(editBuilder => {
                editBuilder.replace(range, vulnerability.fix!);
            });

            vscode.window.showInformationMessage(`Vulnérabilité corrigée: ${vulnerability.title}`);
        }
    }

    public async configureSecurityRules(): Promise<void> {
        const options = [
            'Activer/Désactiver des règles',
            'Configurer la sévérité',
            'Ajouter des exclusions',
            'Réinitialiser la configuration'
        ];

        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Choisissez une option de configuration'
        });

        switch (selected) {
            case options[0]:
                await this.toggleSecurityRules();
                break;
            case options[1]:
                await this.configureSeverity();
                break;
            case options[2]:
                await this.configureExclusions();
                break;
            case options[3]:
                await this.resetConfiguration();
                break;
        }
    }

    public async checkFilePermissions(): Promise<void> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const issues: SecurityIssue[] = [];
        const sensitiveFiles = [
            '.env',
            'config/database.php',
            'config/app.php',
            'storage/logs',
            'storage/cache'
        ];

        for (const file of sensitiveFiles) {
            const filePath = path.join(workspaceFolder.uri.fsPath, file);
            try {
                const stats = await fs.promises.stat(filePath);
                const permissions = (stats.mode & parseInt('777', 8)).toString(8);
                
                if (this.isInsecurePermission(file, permissions)) {
                    issues.push({
                        id: `perm_${Date.now()}_${Math.random()}`,
                        type: 'permission',
                        severity: 'high',
                        title: 'Permissions de fichier non sécurisées',
                        description: `Le fichier ${file} a des permissions trop permissives (${permissions})`,
                        file: filePath,
                        rule: 'file_permissions',
                        fix: `chmod 644 ${file}`,
                        references: ['https://owasp.org/www-project-top-ten/']
                    });
                }
            } catch (error) {
                // File doesn't exist, skip
            }
        }

        if (issues.length > 0) {
            const result: ScanResult = {
                issues,
                summary: this.calculateSummary(issues),
                scanTime: Date.now(),
                timestamp: new Date()
            };
            this.showScanResults(result, 'Vérification des permissions');
        } else {
            vscode.window.showInformationMessage('Aucun problème de permissions détecté');
        }
    }

    private async scanFile(filePath: string): Promise<ScanResult> {
        const startTime = Date.now();
        const content = await fs.promises.readFile(filePath, 'utf8');
        const issues: SecurityIssue[] = [];

        // Scan for vulnerabilities
        const vulnerabilities = await this.vulnerabilityDetector.detectVulnerabilities(content, filePath);
        issues.push(...vulnerabilities);

        // Apply security rules
        const ruleIssues = this.applySecurityRules(content, filePath);
        issues.push(...ruleIssues);

        // Check compliance
        const complianceIssues = await this.complianceChecker.checkCompliance(content, filePath);
        issues.push(...complianceIssues);

        const scanTime = Date.now() - startTime;

        return {
            issues,
            summary: this.calculateSummary(issues),
            scanTime,
            timestamp: new Date()
        };
    }

    private applySecurityRules(content: string, filePath: string): SecurityIssue[] {
        const issues: SecurityIssue[] = [];
        const lines = content.split('\n');

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            const lineNumber = i + 1;

            for (const [ruleId, rule] of this.securityRules) {
                if (rule.enabled && rule.pattern.test(line)) {
                    issues.push({
                        id: `${ruleId}_${lineNumber}_${Date.now()}`,
                        type: 'security_smell',
                        severity: rule.severity,
                        title: rule.title,
                        description: rule.description,
                        file: filePath,
                        line: lineNumber,
                        rule: ruleId,
                        fix: rule.fix,
                        references: rule.references
                    });
                }
            }
        }

        return issues;
    }

    private initializeSecurityRules(): void {
        this.securityRules.set('sql_injection', {
            id: 'sql_injection',
            title: 'Injection SQL potentielle',
            description: 'Utilisation de concaténation de chaînes dans une requête SQL',
            pattern: /\$.*\s*\.\s*["'].*SELECT|INSERT|UPDATE|DELETE.*["']\s*\.\s*\$/,
            severity: 'critical',
            enabled: true,
            fix: 'Utilisez des requêtes préparées avec des paramètres liés',
            references: ['https://owasp.org/www-community/attacks/SQL_Injection']
        });

        this.securityRules.set('xss_vulnerability', {
            id: 'xss_vulnerability',
            title: 'Vulnérabilité XSS potentielle',
            description: 'Sortie non échappée de données utilisateur',
            pattern: /echo\s+\$_(GET|POST|REQUEST|COOKIE)/,
            severity: 'high',
            enabled: true,
            fix: 'Échappez les données avec htmlspecialchars() ou utilisez un moteur de template',
            references: ['https://owasp.org/www-community/attacks/xss/']
        });

        this.securityRules.set('hardcoded_credentials', {
            id: 'hardcoded_credentials',
            title: 'Identifiants codés en dur',
            description: 'Mot de passe ou clé API codé en dur dans le code',
            pattern: /(password|pwd|secret|key|token)\s*=\s*["'][^"']{8,}["']/i,
            severity: 'critical',
            enabled: true,
            fix: 'Utilisez des variables d\'environnement ou un gestionnaire de secrets',
            references: ['https://owasp.org/www-project-top-ten/2017/A2_2017-Broken_Authentication']
        });

        this.securityRules.set('file_inclusion', {
            id: 'file_inclusion',
            title: 'Inclusion de fichier non sécurisée',
            description: 'Inclusion de fichier basée sur une entrée utilisateur',
            pattern: /(include|require)(_once)?\s*\(\s*\$_(GET|POST|REQUEST)/,
            severity: 'critical',
            enabled: true,
            fix: 'Validez et filtrez les chemins de fichiers, utilisez une liste blanche',
            references: ['https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/07-Input_Validation_Testing/11.1-Testing_for_Local_File_Inclusion']
        });

        this.securityRules.set('command_injection', {
            id: 'command_injection',
            title: 'Injection de commande potentielle',
            description: 'Exécution de commande système avec des données utilisateur',
            pattern: /(exec|system|shell_exec|passthru|popen)\s*\(.*\$_(GET|POST|REQUEST)/,
            severity: 'critical',
            enabled: true,
            fix: 'Évitez l\'exécution de commandes système ou utilisez escapeshellarg()',
            references: ['https://owasp.org/www-community/attacks/Command_Injection']
        });

        this.securityRules.set('weak_crypto', {
            id: 'weak_crypto',
            title: 'Cryptographie faible',
            description: 'Utilisation d\'algorithmes de cryptographie faibles',
            pattern: /(md5|sha1|des|rc4)\s*\(/i,
            severity: 'medium',
            enabled: true,
            fix: 'Utilisez des algorithmes cryptographiques forts comme SHA-256, bcrypt',
            references: ['https://owasp.org/www-project-top-ten/2017/A3_2017-Sensitive_Data_Exposure']
        });
    }

    private async findPhpFiles(directory: string): Promise<string[]> {
        const phpFiles: string[] = [];
        
        const scanDirectory = async (dir: string): Promise<void> => {
            const entries = await fs.promises.readdir(dir, { withFileTypes: true });
            
            for (const entry of entries) {
                const fullPath = path.join(dir, entry.name);
                
                if (entry.isDirectory() && !this.shouldSkipDirectory(entry.name)) {
                    await scanDirectory(fullPath);
                } else if (entry.isFile() && entry.name.endsWith('.php')) {
                    phpFiles.push(fullPath);
                }
            }
        };

        await scanDirectory(directory);
        return phpFiles;
    }

    private shouldSkipDirectory(dirName: string): boolean {
        const skipDirs = ['vendor', 'node_modules', '.git', 'storage/cache', 'storage/logs'];
        return skipDirs.includes(dirName);
    }

    private calculateSummary(issues: SecurityIssue[]): ScanResult['summary'] {
        const summary = {
            total: issues.length,
            critical: 0,
            high: 0,
            medium: 0,
            low: 0,
            info: 0
        };

        for (const issue of issues) {
            summary[issue.severity]++;
        }

        return summary;
    }

    private updateDiagnostics(uri: vscode.Uri, issues: SecurityIssue[]): void {
        const diagnostics: vscode.Diagnostic[] = issues.map(issue => {
            const line = (issue.line || 1) - 1;
            const range = new vscode.Range(line, 0, line, Number.MAX_VALUE);
            
            const diagnostic = new vscode.Diagnostic(
                range,
                `${issue.title}: ${issue.description}`,
                this.severityToDiagnosticSeverity(issue.severity)
            );
            
            diagnostic.source = 'Nexa Security';
            diagnostic.code = issue.rule;
            
            return diagnostic;
        });

        this.diagnosticCollection.set(uri, diagnostics);
    }

    private severityToDiagnosticSeverity(severity: SecurityIssue['severity']): vscode.DiagnosticSeverity {
        switch (severity) {
            case 'critical':
            case 'high':
                return vscode.DiagnosticSeverity.Error;
            case 'medium':
                return vscode.DiagnosticSeverity.Warning;
            case 'low':
                return vscode.DiagnosticSeverity.Information;
            case 'info':
                return vscode.DiagnosticSeverity.Hint;
            default:
                return vscode.DiagnosticSeverity.Warning;
        }
    }

    private showScanResults(result: ScanResult, fileName: string): void {
        const { summary } = result;
        const message = `Scan terminé pour ${fileName}: ${summary.total} problème(s) trouvé(s) (${summary.critical} critique(s), ${summary.high} élevé(s), ${summary.medium} moyen(s))`;
        
        if (summary.critical > 0 || summary.high > 0) {
            vscode.window.showErrorMessage(message);
        } else if (summary.medium > 0) {
            vscode.window.showWarningMessage(message);
        } else {
            vscode.window.showInformationMessage(message);
        }
    }

    private showProjectScanResults(result: ScanResult): void {
        const { summary } = result;
        const message = `Scan du projet terminé: ${summary.total} problème(s) de sécurité trouvé(s)`;
        
        vscode.window.showInformationMessage(message, 'Voir le rapport')
            .then(selection => {
                if (selection === 'Voir le rapport') {
                    // Open security report panel
                    vscode.commands.executeCommand('nexa.security.showReport');
                }
            });
    }

    private isInsecurePermission(file: string, permissions: string): boolean {
        const securePermissions: { [key: string]: string[] } = {
            '.env': ['600', '644'],
            'config/database.php': ['600', '644'],
            'config/app.php': ['644'],
            'storage/logs': ['755'],
            'storage/cache': ['755']
        };

        const allowedPerms = securePermissions[file];
        return allowedPerms ? !allowedPerms.includes(permissions) : false;
    }

    private async toggleSecurityRules(): Promise<void> {
        const ruleItems = Array.from(this.securityRules.values()).map(rule => ({
            label: rule.title,
            description: rule.enabled ? '✓ Activée' : '✗ Désactivée',
            rule: rule
        }));

        const selected = await vscode.window.showQuickPick(ruleItems, {
            placeHolder: 'Sélectionnez une règle à activer/désactiver'
        });

        if (selected) {
            selected.rule.enabled = !selected.rule.enabled;
            const status = selected.rule.enabled ? 'activée' : 'désactivée';
            vscode.window.showInformationMessage(`Règle "${selected.rule.title}" ${status}`);
        }
    }

    private async configureSeverity(): Promise<void> {
        // Implementation for severity configuration
        vscode.window.showInformationMessage('Configuration de la sévérité - À implémenter');
    }

    private async configureExclusions(): Promise<void> {
        // Implementation for exclusions configuration
        vscode.window.showInformationMessage('Configuration des exclusions - À implémenter');
    }

    private async resetConfiguration(): Promise<void> {
        const confirm = await vscode.window.showWarningMessage(
            'Êtes-vous sûr de vouloir réinitialiser la configuration de sécurité ?',
            'Oui', 'Non'
        );

        if (confirm === 'Oui') {
            this.initializeSecurityRules();
            vscode.window.showInformationMessage('Configuration de sécurité réinitialisée');
        }
    }
}

interface SecurityRule {
    id: string;
    title: string;
    description: string;
    pattern: RegExp;
    severity: SecurityIssue['severity'];
    enabled: boolean;
    fix?: string;
    references?: string[];
}