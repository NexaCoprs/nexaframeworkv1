"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.DependencyScanner = void 0;
const vscode = require("vscode");
const fs = require("fs");
const path = require("path");
class DependencyScanner {
    constructor() {
        this.vulnerabilityDatabase = new Map();
        this.outputChannel = vscode.window.createOutputChannel('Nexa Dependency Scanner');
        this.initializeVulnerabilityDatabase();
    }
    async scanDependencies(workspacePath) {
        const startTime = Date.now();
        const dependencies = [];
        // Scan different dependency files
        const composerDeps = await this.scanComposerDependencies(workspacePath);
        const packageDeps = await this.scanPackageJsonDependencies(workspacePath);
        const requirementsDeps = await this.scanRequirementsTxtDependencies(workspacePath);
        dependencies.push(...composerDeps, ...packageDeps, ...requirementsDeps);
        // Check for vulnerabilities
        for (const dep of dependencies) {
            dep.vulnerabilities = this.checkVulnerabilities(dep.name, dep.version);
        }
        const scanTime = Date.now() - startTime;
        const result = this.calculateScanResult(dependencies, scanTime);
        return result;
    }
    async scanComposerDependencies(workspacePath) {
        const dependencies = [];
        const composerPath = path.join(workspacePath, 'composer.json');
        const composerLockPath = path.join(workspacePath, 'composer.lock');
        try {
            // Read composer.json for direct dependencies
            if (fs.existsSync(composerPath)) {
                const composerContent = await fs.promises.readFile(composerPath, 'utf8');
                const composer = JSON.parse(composerContent);
                const directDeps = this.parseComposerDependencies(composer.require || {}, composerPath, 'direct');
                const devDeps = this.parseComposerDependencies(composer['require-dev'] || {}, composerPath, 'direct');
                dependencies.push(...directDeps, ...devDeps);
            }
            // Read composer.lock for all dependencies including transitive
            if (fs.existsSync(composerLockPath)) {
                const lockContent = await fs.promises.readFile(composerLockPath, 'utf8');
                const lock = JSON.parse(lockContent);
                const lockDeps = this.parseComposerLockDependencies(lock.packages || [], composerLockPath);
                // Merge with existing dependencies or add new ones
                for (const lockDep of lockDeps) {
                    const existing = dependencies.find(d => d.name === lockDep.name);
                    if (existing) {
                        existing.version = lockDep.version;
                        existing.license = lockDep.license;
                    }
                    else {
                        dependencies.push({ ...lockDep, type: 'transitive' });
                    }
                }
            }
        }
        catch (error) {
            this.outputChannel.appendLine(`Erreur lors du scan des dépendances Composer: ${error}`);
        }
        return dependencies;
    }
    async scanPackageJsonDependencies(workspacePath) {
        const dependencies = [];
        const packagePath = path.join(workspacePath, 'package.json');
        const packageLockPath = path.join(workspacePath, 'package-lock.json');
        try {
            if (fs.existsSync(packagePath)) {
                const packageContent = await fs.promises.readFile(packagePath, 'utf8');
                const packageJson = JSON.parse(packageContent);
                const prodDeps = this.parsePackageJsonDependencies(packageJson.dependencies || {}, packagePath, 'direct');
                const devDeps = this.parsePackageJsonDependencies(packageJson.devDependencies || {}, packagePath, 'direct');
                dependencies.push(...prodDeps, ...devDeps);
            }
            // TODO: Parse package-lock.json for transitive dependencies
        }
        catch (error) {
            this.outputChannel.appendLine(`Erreur lors du scan des dépendances npm: ${error}`);
        }
        return dependencies;
    }
    async scanRequirementsTxtDependencies(workspacePath) {
        const dependencies = [];
        const requirementsPath = path.join(workspacePath, 'requirements.txt');
        try {
            if (fs.existsSync(requirementsPath)) {
                const content = await fs.promises.readFile(requirementsPath, 'utf8');
                const lines = content.split('\n');
                for (let i = 0; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (line && !line.startsWith('#')) {
                        const dep = this.parseRequirementLine(line, requirementsPath, i + 1);
                        if (dep) {
                            dependencies.push(dep);
                        }
                    }
                }
            }
        }
        catch (error) {
            this.outputChannel.appendLine(`Erreur lors du scan des dépendances Python: ${error}`);
        }
        return dependencies;
    }
    async checkOutdatedDependencies(dependencies) {
        // This would typically call package registries to check for latest versions
        // For now, we'll simulate this with some known outdated packages
        const knownOutdated = new Map([
            ['symfony/symfony', '6.4.0'],
            ['laravel/framework', '10.0.0'],
            ['doctrine/orm', '3.0.0'],
            ['monolog/monolog', '3.5.0'],
            ['phpunit/phpunit', '10.5.0']
        ]);
        for (const dep of dependencies) {
            const latestVersion = knownOutdated.get(dep.name);
            if (latestVersion && this.isVersionOutdated(dep.version, latestVersion)) {
                dep.outdated = true;
                dep.latestVersion = latestVersion;
            }
        }
    }
    generateSecurityIssues(scanResult) {
        const issues = [];
        for (const dep of scanResult.dependencies) {
            for (const vuln of dep.vulnerabilities) {
                issues.push({
                    id: `dep_${vuln.id}_${Date.now()}`,
                    type: 'vulnerability',
                    severity: vuln.severity,
                    title: `Vulnérabilité dans ${dep.name}`,
                    description: `${vuln.title}: ${vuln.description}`,
                    file: dep.file,
                    line: dep.line,
                    rule: 'dependency_vulnerability',
                    fix: vuln.fixedVersion ? `Mettre à jour vers la version ${vuln.fixedVersion}` : 'Mettre à jour vers une version corrigée',
                    references: vuln.references
                });
            }
            // Add issues for outdated dependencies
            if (dep.outdated && dep.latestVersion) {
                issues.push({
                    id: `outdated_${dep.name}_${Date.now()}`,
                    type: 'security_smell',
                    severity: 'medium',
                    title: `Dépendance obsolète: ${dep.name}`,
                    description: `La version ${dep.version} est obsolète. Version actuelle: ${dep.latestVersion}`,
                    file: dep.file,
                    line: dep.line,
                    rule: 'outdated_dependency',
                    fix: `Mettre à jour vers la version ${dep.latestVersion}`,
                    references: []
                });
            }
        }
        return issues;
    }
    parseComposerDependencies(deps, file, type) {
        const dependencies = [];
        for (const [name, version] of Object.entries(deps)) {
            if (name !== 'php') { // Skip PHP version constraint
                dependencies.push({
                    name,
                    version: this.normalizeVersion(version),
                    type,
                    file,
                    vulnerabilities: [],
                    outdated: false
                });
            }
        }
        return dependencies;
    }
    parseComposerLockDependencies(packages, file) {
        const dependencies = [];
        for (const pkg of packages) {
            dependencies.push({
                name: pkg.name,
                version: pkg.version,
                type: 'transitive',
                file,
                vulnerabilities: [],
                outdated: false,
                license: Array.isArray(pkg.license) ? pkg.license.join(', ') : pkg.license
            });
        }
        return dependencies;
    }
    parsePackageJsonDependencies(deps, file, type) {
        const dependencies = [];
        for (const [name, version] of Object.entries(deps)) {
            dependencies.push({
                name,
                version: this.normalizeVersion(version),
                type,
                file,
                vulnerabilities: [],
                outdated: false
            });
        }
        return dependencies;
    }
    parseRequirementLine(line, file, lineNumber) {
        // Parse Python requirements format: package==version or package>=version
        const match = line.match(/^([a-zA-Z0-9_-]+)([><=!]+)([0-9.]+.*)$/);
        if (match) {
            const [, name, operator, version] = match;
            return {
                name,
                version: this.normalizeVersion(version),
                type: 'direct',
                file,
                line: lineNumber,
                vulnerabilities: [],
                outdated: false
            };
        }
        return null;
    }
    normalizeVersion(version) {
        // Remove version operators and normalize
        return version.replace(/^[^0-9]*/, '').split(' ')[0];
    }
    checkVulnerabilities(packageName, version) {
        const vulnerabilities = this.vulnerabilityDatabase.get(packageName) || [];
        return vulnerabilities.filter(vuln => {
            // Check if the current version is affected
            return this.isVersionAffected(version, vuln.version);
        });
    }
    isVersionAffected(currentVersion, affectedVersion) {
        // Simplified version comparison - in reality, this would be more complex
        // and handle version ranges properly
        try {
            const current = this.parseVersion(currentVersion);
            const affected = this.parseVersion(affectedVersion);
            return current.major === affected.major &&
                current.minor === affected.minor &&
                current.patch <= affected.patch;
        }
        catch {
            return false;
        }
    }
    isVersionOutdated(currentVersion, latestVersion) {
        try {
            const current = this.parseVersion(currentVersion);
            const latest = this.parseVersion(latestVersion);
            if (current.major < latest.major)
                return true;
            if (current.major > latest.major)
                return false;
            if (current.minor < latest.minor)
                return true;
            if (current.minor > latest.minor)
                return false;
            return current.patch < latest.patch;
        }
        catch {
            return false;
        }
    }
    parseVersion(version) {
        const parts = version.split('.').map(p => parseInt(p.replace(/[^0-9]/g, ''), 10));
        return {
            major: parts[0] || 0,
            minor: parts[1] || 0,
            patch: parts[2] || 0
        };
    }
    calculateScanResult(dependencies, scanTime) {
        let vulnerableDependencies = 0;
        let totalVulnerabilities = 0;
        let criticalVulnerabilities = 0;
        let highVulnerabilities = 0;
        let mediumVulnerabilities = 0;
        let lowVulnerabilities = 0;
        for (const dep of dependencies) {
            if (dep.vulnerabilities.length > 0) {
                vulnerableDependencies++;
                totalVulnerabilities += dep.vulnerabilities.length;
                for (const vuln of dep.vulnerabilities) {
                    switch (vuln.severity) {
                        case 'critical':
                            criticalVulnerabilities++;
                            break;
                        case 'high':
                            highVulnerabilities++;
                            break;
                        case 'medium':
                            mediumVulnerabilities++;
                            break;
                        case 'low':
                            lowVulnerabilities++;
                            break;
                    }
                }
            }
        }
        return {
            totalDependencies: dependencies.length,
            vulnerableDependencies,
            totalVulnerabilities,
            criticalVulnerabilities,
            highVulnerabilities,
            mediumVulnerabilities,
            lowVulnerabilities,
            dependencies,
            scanTime,
            timestamp: new Date()
        };
    }
    initializeVulnerabilityDatabase() {
        // Initialize with some known vulnerabilities
        // In a real implementation, this would be loaded from a vulnerability database
        this.vulnerabilityDatabase.set('symfony/symfony', [
            {
                id: 'CVE-2023-1234',
                cve: 'CVE-2023-1234',
                title: 'Symfony Security Vulnerability',
                description: 'Remote code execution vulnerability in Symfony framework',
                severity: 'critical',
                package: 'symfony/symfony',
                version: '5.4.0',
                fixedVersion: '5.4.21',
                references: [
                    'https://symfony.com/security',
                    'https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-1234'
                ],
                publishedDate: new Date('2023-01-15'),
                lastModified: new Date('2023-01-20')
            }
        ]);
        this.vulnerabilityDatabase.set('laravel/framework', [
            {
                id: 'CVE-2023-5678',
                cve: 'CVE-2023-5678',
                title: 'Laravel SQL Injection',
                description: 'SQL injection vulnerability in Laravel ORM',
                severity: 'high',
                package: 'laravel/framework',
                version: '9.0.0',
                fixedVersion: '9.52.0',
                references: [
                    'https://laravel.com/security',
                    'https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-5678'
                ],
                publishedDate: new Date('2023-02-10'),
                lastModified: new Date('2023-02-15')
            }
        ]);
        this.vulnerabilityDatabase.set('monolog/monolog', [
            {
                id: 'CVE-2023-9999',
                cve: 'CVE-2023-9999',
                title: 'Monolog Information Disclosure',
                description: 'Information disclosure vulnerability in Monolog logging',
                severity: 'medium',
                package: 'monolog/monolog',
                version: '2.8.0',
                fixedVersion: '2.9.1',
                references: [
                    'https://github.com/Seldaek/monolog/security',
                    'https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-9999'
                ],
                publishedDate: new Date('2023-03-05'),
                lastModified: new Date('2023-03-10')
            }
        ]);
        this.vulnerabilityDatabase.set('doctrine/orm', [
            {
                id: 'CVE-2023-1111',
                cve: 'CVE-2023-1111',
                title: 'Doctrine ORM SQL Injection',
                description: 'SQL injection in Doctrine ORM query builder',
                severity: 'high',
                package: 'doctrine/orm',
                version: '2.14.0',
                fixedVersion: '2.14.3',
                references: [
                    'https://www.doctrine-project.org/security',
                    'https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-1111'
                ],
                publishedDate: new Date('2023-04-01'),
                lastModified: new Date('2023-04-05')
            }
        ]);
        // Add some npm package vulnerabilities
        this.vulnerabilityDatabase.set('lodash', [
            {
                id: 'CVE-2023-2222',
                cve: 'CVE-2023-2222',
                title: 'Lodash Prototype Pollution',
                description: 'Prototype pollution vulnerability in lodash',
                severity: 'high',
                package: 'lodash',
                version: '4.17.20',
                fixedVersion: '4.17.21',
                references: [
                    'https://github.com/lodash/lodash/security',
                    'https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-2222'
                ],
                publishedDate: new Date('2023-05-01'),
                lastModified: new Date('2023-05-05')
            }
        ]);
        this.vulnerabilityDatabase.set('express', [
            {
                id: 'CVE-2023-3333',
                cve: 'CVE-2023-3333',
                title: 'Express.js Path Traversal',
                description: 'Path traversal vulnerability in Express.js',
                severity: 'medium',
                package: 'express',
                version: '4.18.0',
                fixedVersion: '4.18.2',
                references: [
                    'https://expressjs.com/security',
                    'https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-3333'
                ],
                publishedDate: new Date('2023-06-01'),
                lastModified: new Date('2023-06-05')
            }
        ]);
        // Add some Python package vulnerabilities
        this.vulnerabilityDatabase.set('django', [
            {
                id: 'CVE-2023-4444',
                cve: 'CVE-2023-4444',
                title: 'Django SQL Injection',
                description: 'SQL injection vulnerability in Django ORM',
                severity: 'critical',
                package: 'django',
                version: '4.1.0',
                fixedVersion: '4.1.7',
                references: [
                    'https://www.djangoproject.com/security/',
                    'https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-4444'
                ],
                publishedDate: new Date('2023-07-01'),
                lastModified: new Date('2023-07-05')
            }
        ]);
        this.vulnerabilityDatabase.set('requests', [
            {
                id: 'CVE-2023-5555',
                cve: 'CVE-2023-5555',
                title: 'Requests Certificate Validation',
                description: 'Certificate validation bypass in requests library',
                severity: 'medium',
                package: 'requests',
                version: '2.28.0',
                fixedVersion: '2.28.2',
                references: [
                    'https://github.com/psf/requests/security',
                    'https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2023-5555'
                ],
                publishedDate: new Date('2023-08-01'),
                lastModified: new Date('2023-08-05')
            }
        ]);
    }
    addVulnerability(packageName, vulnerability) {
        const existing = this.vulnerabilityDatabase.get(packageName) || [];
        existing.push(vulnerability);
        this.vulnerabilityDatabase.set(packageName, existing);
    }
    updateVulnerabilityDatabase(vulnerabilities) {
        this.vulnerabilityDatabase = vulnerabilities;
    }
    getVulnerabilityDatabase() {
        return new Map(this.vulnerabilityDatabase);
    }
}
exports.DependencyScanner = DependencyScanner;
//# sourceMappingURL=dependencyScanner.js.map