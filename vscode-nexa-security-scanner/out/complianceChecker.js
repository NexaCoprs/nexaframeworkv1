"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ComplianceChecker = void 0;
const fs = require("fs");
const path = require("path");
class ComplianceChecker {
    constructor() {
        this.complianceRules = new Map();
        this.enabledStandards = new Set(['OWASP', 'PCI-DSS']);
        this.initializeComplianceRules();
    }
    async checkCompliance(content, filePath) {
        const issues = [];
        for (const standard of this.enabledStandards) {
            const rules = this.complianceRules.get(standard) || [];
            for (const rule of rules) {
                const complianceIssues = rule.check(content, filePath);
                for (const issue of complianceIssues) {
                    issues.push({
                        id: `${rule.id}_${issue.line || 0}_${Date.now()}`,
                        type: 'compliance',
                        severity: rule.severity,
                        title: `${standard}: ${rule.name}`,
                        description: `${rule.description} - ${issue.message}`,
                        file: filePath,
                        line: issue.line,
                        column: issue.column,
                        rule: rule.id,
                        fix: rule.remediation,
                        references: rule.references
                    });
                }
            }
        }
        return issues;
    }
    async generateComplianceReport(workspacePath, standard) {
        const rules = this.complianceRules.get(standard) || [];
        const allIssues = [];
        const phpFiles = await this.findPhpFiles(workspacePath);
        let passedRules = 0;
        let failedRules = 0;
        for (const file of phpFiles) {
            const content = await fs.promises.readFile(file, 'utf8');
            const fileIssues = await this.checkCompliance(content, file);
            allIssues.push(...fileIssues.filter(issue => issue.title.startsWith(standard)));
        }
        // Calculate compliance score
        const ruleResults = new Map();
        for (const rule of rules) {
            const hasIssues = allIssues.some(issue => issue.rule === rule.id);
            ruleResults.set(rule.id, !hasIssues);
            if (hasIssues) {
                failedRules++;
            }
            else {
                passedRules++;
            }
        }
        const score = rules.length > 0 ? Math.round((passedRules / rules.length) * 100) : 100;
        const recommendations = this.generateRecommendations(standard, allIssues);
        return {
            standard,
            totalRules: rules.length,
            passedRules,
            failedRules,
            score,
            issues: allIssues,
            recommendations
        };
    }
    getAvailableStandards() {
        return Array.from(this.complianceRules.keys());
    }
    enableStandard(standard) {
        if (this.complianceRules.has(standard)) {
            this.enabledStandards.add(standard);
        }
    }
    disableStandard(standard) {
        this.enabledStandards.delete(standard);
    }
    getEnabledStandards() {
        return Array.from(this.enabledStandards);
    }
    initializeComplianceRules() {
        this.initializeOWASPRules();
        this.initializePCIDSSRules();
        this.initializeGDPRRules();
        this.initializeHIPAARules();
        this.initializeSOXRules();
        this.initializeISO27001Rules();
    }
    initializeOWASPRules() {
        const owaspRules = [
            {
                id: 'owasp_a01_access_control',
                name: 'A01:2021 – Contrôle d\'accès défaillant',
                description: 'Vérification des contrôles d\'accès appropriés',
                standard: 'OWASP',
                severity: 'high',
                category: 'access_control',
                check: (content, filePath) => {
                    const issues = [];
                    const lines = content.split('\n');
                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i];
                        const lineNumber = i + 1;
                        // Check for direct object access without authorization
                        if (line.includes('$_GET[') && line.includes('id') &&
                            !this.hasAuthorizationInContext(content, i)) {
                            issues.push({
                                ruleId: 'owasp_a01_access_control',
                                line: lineNumber,
                                message: 'Accès direct à un objet sans vérification d\'autorisation',
                                evidence: line.trim()
                            });
                        }
                    }
                    return issues;
                },
                remediation: 'Implémentez des vérifications d\'autorisation appropriées avant d\'accéder aux ressources',
                references: ['https://owasp.org/Top10/A01_2021-Broken_Access_Control/']
            },
            {
                id: 'owasp_a02_crypto_failures',
                name: 'A02:2021 – Défaillances cryptographiques',
                description: 'Vérification de l\'utilisation appropriée de la cryptographie',
                standard: 'OWASP',
                severity: 'high',
                category: 'cryptography',
                check: (content, filePath) => {
                    const issues = [];
                    const lines = content.split('\n');
                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i];
                        const lineNumber = i + 1;
                        // Check for weak encryption
                        if (line.match(/(md5|sha1|des|rc4)\s*\(/i)) {
                            issues.push({
                                ruleId: 'owasp_a02_crypto_failures',
                                line: lineNumber,
                                message: 'Utilisation d\'algorithmes cryptographiques faibles',
                                evidence: line.trim()
                            });
                        }
                        // Check for hardcoded secrets
                        if (line.match(/(password|secret|key)\s*=\s*["'][^"']{8,}["']/i)) {
                            issues.push({
                                ruleId: 'owasp_a02_crypto_failures',
                                line: lineNumber,
                                message: 'Secret codé en dur détecté',
                                evidence: line.trim()
                            });
                        }
                    }
                    return issues;
                },
                remediation: 'Utilisez des algorithmes cryptographiques forts et stockez les secrets de manière sécurisée',
                references: ['https://owasp.org/Top10/A02_2021-Cryptographic_Failures/']
            },
            {
                id: 'owasp_a03_injection',
                name: 'A03:2021 – Injection',
                description: 'Vérification des vulnérabilités d\'injection',
                standard: 'OWASP',
                severity: 'critical',
                category: 'injection',
                check: (content, filePath) => {
                    const issues = [];
                    const lines = content.split('\n');
                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i];
                        const lineNumber = i + 1;
                        // SQL Injection
                        if (line.match(/\$.*\s*\.\s*["'].*(?:SELECT|INSERT|UPDATE|DELETE).*["']\s*\.\s*\$/i)) {
                            issues.push({
                                ruleId: 'owasp_a03_injection',
                                line: lineNumber,
                                message: 'Vulnérabilité d\'injection SQL potentielle',
                                evidence: line.trim()
                            });
                        }
                        // Command Injection
                        if (line.match(/(exec|system|shell_exec)\s*\(.*\$_(GET|POST)/)) {
                            issues.push({
                                ruleId: 'owasp_a03_injection',
                                line: lineNumber,
                                message: 'Vulnérabilité d\'injection de commande potentielle',
                                evidence: line.trim()
                            });
                        }
                    }
                    return issues;
                },
                remediation: 'Utilisez des requêtes préparées et validez toutes les entrées utilisateur',
                references: ['https://owasp.org/Top10/A03_2021-Injection/']
            },
            {
                id: 'owasp_a04_insecure_design',
                name: 'A04:2021 – Conception non sécurisée',
                description: 'Vérification des problèmes de conception sécurisée',
                standard: 'OWASP',
                severity: 'medium',
                category: 'design',
                check: (content, filePath) => {
                    const issues = [];
                    // Check for missing rate limiting
                    if (content.includes('login') && !content.includes('rate_limit') && !content.includes('throttle')) {
                        issues.push({
                            ruleId: 'owasp_a04_insecure_design',
                            message: 'Absence de limitation de taux sur la fonctionnalité de connexion',
                            evidence: 'Fonction de connexion sans protection contre les attaques par force brute'
                        });
                    }
                    return issues;
                },
                remediation: 'Implémentez des contrôles de sécurité appropriés dès la conception',
                references: ['https://owasp.org/Top10/A04_2021-Insecure_Design/']
            },
            {
                id: 'owasp_a05_security_misconfiguration',
                name: 'A05:2021 – Mauvaise configuration de sécurité',
                description: 'Vérification des configurations de sécurité',
                standard: 'OWASP',
                severity: 'medium',
                category: 'configuration',
                check: (content, filePath) => {
                    const issues = [];
                    const lines = content.split('\n');
                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i];
                        const lineNumber = i + 1;
                        // Check for debug mode in production
                        if (line.includes('error_reporting(E_ALL)') || line.includes('display_errors') && line.includes('1')) {
                            issues.push({
                                ruleId: 'owasp_a05_security_misconfiguration',
                                line: lineNumber,
                                message: 'Mode de débogage activé en production',
                                evidence: line.trim()
                            });
                        }
                    }
                    return issues;
                },
                remediation: 'Configurez correctement les paramètres de sécurité pour l\'environnement de production',
                references: ['https://owasp.org/Top10/A05_2021-Security_Misconfiguration/']
            }
        ];
        this.complianceRules.set('OWASP', owaspRules);
    }
    initializePCIDSSRules() {
        const pciRules = [
            {
                id: 'pci_req_3_encryption',
                name: 'Exigence 3 - Chiffrement des données de cartes',
                description: 'Vérification du chiffrement des données sensibles de cartes de paiement',
                standard: 'PCI-DSS',
                severity: 'critical',
                category: 'encryption',
                check: (content, filePath) => {
                    const issues = [];
                    const lines = content.split('\n');
                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i];
                        const lineNumber = i + 1;
                        // Check for credit card patterns without encryption
                        if (line.match(/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/) &&
                            !content.includes('encrypt') && !content.includes('hash')) {
                            issues.push({
                                ruleId: 'pci_req_3_encryption',
                                line: lineNumber,
                                message: 'Données de carte de crédit potentiellement non chiffrées',
                                evidence: 'Numéro de carte détecté sans chiffrement apparent'
                            });
                        }
                    }
                    return issues;
                },
                remediation: 'Chiffrez toutes les données sensibles de cartes de paiement',
                references: ['https://www.pcisecuritystandards.org/document_library']
            },
            {
                id: 'pci_req_6_secure_development',
                name: 'Exigence 6 - Développement sécurisé',
                description: 'Vérification des pratiques de développement sécurisé',
                standard: 'PCI-DSS',
                severity: 'high',
                category: 'development',
                check: (content, filePath) => {
                    const issues = [];
                    // Check for common vulnerabilities
                    if (content.includes('$_GET') && !content.includes('filter_') && !content.includes('validate')) {
                        issues.push({
                            ruleId: 'pci_req_6_secure_development',
                            message: 'Validation d\'entrée insuffisante pour les données GET',
                            evidence: 'Utilisation de $_GET sans validation appropriée'
                        });
                    }
                    return issues;
                },
                remediation: 'Implémentez des pratiques de développement sécurisé et validez toutes les entrées',
                references: ['https://www.pcisecuritystandards.org/document_library']
            }
        ];
        this.complianceRules.set('PCI-DSS', pciRules);
    }
    initializeGDPRRules() {
        const gdprRules = [
            {
                id: 'gdpr_data_protection',
                name: 'Protection des données personnelles',
                description: 'Vérification de la protection des données personnelles selon le RGPD',
                standard: 'GDPR',
                severity: 'high',
                category: 'privacy',
                check: (content, filePath) => {
                    const issues = [];
                    const lines = content.split('\n');
                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i];
                        const lineNumber = i + 1;
                        // Check for personal data without encryption
                        if (line.match(/(email|phone|address|ssn|passport).*=.*\$_(GET|POST)/) &&
                            !content.includes('encrypt') && !content.includes('hash')) {
                            issues.push({
                                ruleId: 'gdpr_data_protection',
                                line: lineNumber,
                                message: 'Données personnelles potentiellement non protégées',
                                evidence: line.trim()
                            });
                        }
                    }
                    return issues;
                },
                remediation: 'Chiffrez et protégez toutes les données personnelles conformément au RGPD',
                references: ['https://gdpr.eu/']
            }
        ];
        this.complianceRules.set('GDPR', gdprRules);
    }
    initializeHIPAARules() {
        const hipaaRules = [
            {
                id: 'hipaa_phi_protection',
                name: 'Protection des informations de santé protégées (PHI)',
                description: 'Vérification de la protection des PHI selon HIPAA',
                standard: 'HIPAA',
                severity: 'critical',
                category: 'healthcare',
                check: (content, filePath) => {
                    const issues = [];
                    // Check for health-related data without proper protection
                    if (content.match(/(medical|health|patient|diagnosis|treatment)/i) &&
                        !content.includes('encrypt') && !content.includes('secure')) {
                        issues.push({
                            ruleId: 'hipaa_phi_protection',
                            message: 'Informations de santé potentiellement non protégées',
                            evidence: 'Données de santé détectées sans protection appropriée'
                        });
                    }
                    return issues;
                },
                remediation: 'Implémentez des mesures de protection appropriées pour les PHI',
                references: ['https://www.hhs.gov/hipaa/']
            }
        ];
        this.complianceRules.set('HIPAA', hipaaRules);
    }
    initializeSOXRules() {
        const soxRules = [
            {
                id: 'sox_audit_trail',
                name: 'Piste d\'audit SOX',
                description: 'Vérification de la traçabilité des opérations financières',
                standard: 'SOX',
                severity: 'high',
                category: 'audit',
                check: (content, filePath) => {
                    const issues = [];
                    // Check for financial operations without logging
                    if (content.match(/(transaction|payment|financial|accounting)/i) &&
                        !content.includes('log') && !content.includes('audit')) {
                        issues.push({
                            ruleId: 'sox_audit_trail',
                            message: 'Opération financière sans piste d\'audit',
                            evidence: 'Transaction financière détectée sans journalisation'
                        });
                    }
                    return issues;
                },
                remediation: 'Implémentez une journalisation complète pour toutes les opérations financières',
                references: ['https://www.sox-online.com/']
            }
        ];
        this.complianceRules.set('SOX', soxRules);
    }
    initializeISO27001Rules() {
        const isoRules = [
            {
                id: 'iso27001_access_control',
                name: 'Contrôle d\'accès ISO 27001',
                description: 'Vérification des contrôles d\'accès selon ISO 27001',
                standard: 'ISO27001',
                severity: 'medium',
                category: 'access_management',
                check: (content, filePath) => {
                    const issues = [];
                    // Check for proper access controls
                    if (content.includes('admin') && !content.includes('auth') && !content.includes('permission')) {
                        issues.push({
                            ruleId: 'iso27001_access_control',
                            message: 'Fonctionnalité administrative sans contrôle d\'accès approprié',
                            evidence: 'Code administratif détecté sans vérification d\'autorisation'
                        });
                    }
                    return issues;
                },
                remediation: 'Implémentez des contrôles d\'accès appropriés selon ISO 27001',
                references: ['https://www.iso.org/isoiec-27001-information-security.html']
            }
        ];
        this.complianceRules.set('ISO27001', isoRules);
    }
    hasAuthorizationInContext(content, currentLine) {
        const lines = content.split('\n');
        const checkRange = 5;
        const startLine = Math.max(0, currentLine - checkRange);
        const endLine = Math.min(lines.length - 1, currentLine + checkRange);
        for (let i = startLine; i <= endLine; i++) {
            const line = lines[i];
            if (line.includes('Auth::') || line.includes('authorize') || line.includes('can(')) {
                return true;
            }
        }
        return false;
    }
    async findPhpFiles(directory) {
        const phpFiles = [];
        const scanDirectory = async (dir) => {
            try {
                const entries = await fs.promises.readdir(dir, { withFileTypes: true });
                for (const entry of entries) {
                    const fullPath = path.join(dir, entry.name);
                    if (entry.isDirectory() && !this.shouldSkipDirectory(entry.name)) {
                        await scanDirectory(fullPath);
                    }
                    else if (entry.isFile() && entry.name.endsWith('.php')) {
                        phpFiles.push(fullPath);
                    }
                }
            }
            catch (error) {
                // Skip directories that can't be read
            }
        };
        await scanDirectory(directory);
        return phpFiles;
    }
    shouldSkipDirectory(dirName) {
        const skipDirs = ['vendor', 'node_modules', '.git', 'storage/cache', 'storage/logs'];
        return skipDirs.includes(dirName);
    }
    generateRecommendations(standard, issues) {
        const recommendations = [];
        const issuesByCategory = new Map();
        // Count issues by category
        for (const issue of issues) {
            const category = issue.rule.split('_')[2] || 'general';
            issuesByCategory.set(category, (issuesByCategory.get(category) || 0) + 1);
        }
        // Generate recommendations based on most common issues
        const sortedCategories = Array.from(issuesByCategory.entries())
            .sort((a, b) => b[1] - a[1]);
        for (const [category, count] of sortedCategories.slice(0, 5)) {
            switch (category) {
                case 'injection':
                    recommendations.push('Implémentez une validation stricte des entrées et utilisez des requêtes préparées');
                    break;
                case 'cryptography':
                case 'encryption':
                    recommendations.push('Mettez à jour vers des algorithmes cryptographiques modernes et sécurisés');
                    break;
                case 'access':
                case 'control':
                    recommendations.push('Renforcez les contrôles d\'accès et implémentez le principe du moindre privilège');
                    break;
                case 'configuration':
                    recommendations.push('Révisez et sécurisez les configurations de production');
                    break;
                case 'privacy':
                    recommendations.push('Implémentez des mesures de protection des données personnelles');
                    break;
                default:
                    recommendations.push(`Adressez les problèmes de ${category} (${count} problème(s) détecté(s))`);
            }
        }
        if (recommendations.length === 0) {
            recommendations.push('Continuez à maintenir de bonnes pratiques de sécurité');
        }
        return recommendations;
    }
}
exports.ComplianceChecker = ComplianceChecker;
//# sourceMappingURL=complianceChecker.js.map