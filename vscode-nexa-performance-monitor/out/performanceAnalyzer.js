"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.PerformanceAnalyzer = void 0;
const fs = require("fs");
const path = require("path");
class PerformanceAnalyzer {
    constructor(context) {
        this.context = context;
        this.analysisCache = new Map();
        this.projectAnalysis = null;
    }
    async analyzeFile(filePath) {
        try {
            const content = fs.readFileSync(filePath, 'utf8');
            const analysis = this.performFileAnalysis(filePath, content);
            this.analysisCache.set(filePath, analysis);
            return analysis;
        }
        catch (error) {
            console.error(`Error analyzing file ${filePath}:`, error);
            return null;
        }
    }
    async analyzeProject(projectPath, progress, token) {
        try {
            const files = await this.findAnalyzableFiles(projectPath);
            const fileAnalyses = [];
            let processedFiles = 0;
            const totalFiles = files.length;
            for (const file of files) {
                if (token.isCancellationRequested) {
                    return null;
                }
                progress.report({
                    message: `Analyse de ${path.basename(file)}...`,
                    increment: (1 / totalFiles) * 100
                });
                const analysis = await this.analyzeFile(file);
                if (analysis) {
                    fileAnalyses.push(analysis);
                }
                processedFiles++;
            }
            this.projectAnalysis = this.generateProjectSummary(fileAnalyses);
            return this.projectAnalysis;
        }
        catch (error) {
            console.error('Error analyzing project:', error);
            return null;
        }
    }
    async findAnalyzableFiles(projectPath) {
        const files = [];
        const scanDirectory = async (dirPath) => {
            try {
                const items = fs.readdirSync(dirPath);
                for (const item of items) {
                    const itemPath = path.join(dirPath, item);
                    const stat = fs.statSync(itemPath);
                    if (stat.isDirectory()) {
                        // Skip common directories that don't need analysis
                        if (!['node_modules', '.git', 'vendor', 'out', 'dist'].includes(item)) {
                            await scanDirectory(itemPath);
                        }
                    }
                    else if (this.isAnalyzableFile(itemPath)) {
                        files.push(itemPath);
                    }
                }
            }
            catch (error) {
                console.error(`Error scanning directory ${dirPath}:`, error);
            }
        };
        await scanDirectory(projectPath);
        return files;
    }
    isAnalyzableFile(filePath) {
        const ext = path.extname(filePath).toLowerCase();
        return ['.php', '.nx', '.js', '.ts'].includes(ext);
    }
    performFileAnalysis(filePath, content) {
        const issues = [];
        const lines = content.split('\n');
        // Analyze each line for performance issues
        lines.forEach((line, index) => {
            const lineIssues = this.analyzeLine(line, index + 1);
            issues.push(...lineIssues);
        });
        // Calculate complexity metrics
        const complexity = this.calculateComplexity(content);
        const linesOfCode = lines.filter(line => line.trim() && !line.trim().startsWith('//')).length;
        const functions = this.countFunctions(content);
        const estimatedExecutionTime = this.estimateExecutionTime(content, complexity);
        return {
            filePath,
            issues,
            complexity,
            linesOfCode,
            functions,
            estimatedExecutionTime
        };
    }
    analyzeLine(line, lineNumber) {
        const issues = [];
        const trimmedLine = line.trim();
        // Database performance issues
        if (trimmedLine.includes('SELECT *')) {
            issues.push({
                type: 'warning',
                message: 'SELECT * peut impacter les performances',
                line: lineNumber,
                column: line.indexOf('SELECT *'),
                severity: 2,
                suggestion: 'Spécifiez les colonnes nécessaires au lieu d\'utiliser *',
                category: 'database'
            });
        }
        if (trimmedLine.match(/SELECT.+FROM.+WHERE.+LIKE\s*['"]%/)) {
            issues.push({
                type: 'warning',
                message: 'LIKE avec % au début empêche l\'utilisation d\'index',
                line: lineNumber,
                column: line.indexOf('LIKE'),
                severity: 2,
                suggestion: 'Évitez les wildcards au début des patterns LIKE',
                category: 'database'
            });
        }
        // Loop performance issues
        if (trimmedLine.match(/for\s*\(/)) {
            const nextLines = line.substring(line.indexOf('for'));
            if (nextLines.includes('for') && nextLines.lastIndexOf('for') !== nextLines.indexOf('for')) {
                issues.push({
                    type: 'info',
                    message: 'Boucles imbriquées détectées',
                    line: lineNumber,
                    column: line.indexOf('for'),
                    severity: 1,
                    suggestion: 'Considérez l\'optimisation des boucles imbriquées',
                    category: 'algorithm'
                });
            }
        }
        // Memory issues
        if (trimmedLine.includes('file_get_contents(')) {
            issues.push({
                type: 'warning',
                message: 'file_get_contents() charge tout le fichier en mémoire',
                line: lineNumber,
                column: line.indexOf('file_get_contents'),
                severity: 2,
                suggestion: 'Utilisez fopen() et fread() pour les gros fichiers',
                category: 'memory'
            });
        }
        // Network issues
        if (trimmedLine.includes('file_get_contents(') && trimmedLine.includes('http')) {
            issues.push({
                type: 'error',
                message: 'Appel HTTP synchrone bloquant',
                line: lineNumber,
                column: line.indexOf('file_get_contents'),
                severity: 3,
                suggestion: 'Utilisez cURL avec des options de timeout ou des appels asynchrones',
                category: 'network'
            });
        }
        // CPU intensive operations
        if (trimmedLine.includes('array_merge') && trimmedLine.includes('foreach')) {
            issues.push({
                type: 'warning',
                message: 'array_merge dans une boucle est inefficace',
                line: lineNumber,
                column: line.indexOf('array_merge'),
                severity: 2,
                suggestion: 'Utilisez array_push() ou l\'opérateur [] pour ajouter des éléments',
                category: 'cpu'
            });
        }
        return issues;
    }
    calculateComplexity(content) {
        let complexity = 1; // Base complexity
        // Count decision points
        const patterns = [
            /if\s*\(/g,
            /else\s*if\s*\(/g,
            /while\s*\(/g,
            /for\s*\(/g,
            /foreach\s*\(/g,
            /switch\s*\(/g,
            /case\s+/g,
            /catch\s*\(/g,
            /\?\s*:/g // Ternary operator
        ];
        patterns.forEach(pattern => {
            const matches = content.match(pattern);
            if (matches) {
                complexity += matches.length;
            }
        });
        return complexity;
    }
    countFunctions(content) {
        const functionPattern = /function\s+\w+/g;
        const matches = content.match(functionPattern);
        return matches ? matches.length : 0;
    }
    estimateExecutionTime(content, complexity) {
        // Simple heuristic for execution time estimation
        const linesOfCode = content.split('\n').length;
        const baseTime = linesOfCode * 0.1; // 0.1ms per line
        const complexityMultiplier = Math.log(complexity + 1);
        return Math.round(baseTime * complexityMultiplier);
    }
    generateProjectSummary(fileAnalyses) {
        const totalIssues = fileAnalyses.reduce((sum, analysis) => sum + analysis.issues.length, 0);
        const criticalIssues = fileAnalyses.reduce((sum, analysis) => sum + analysis.issues.filter(issue => issue.type === 'error').length, 0);
        const warningIssues = fileAnalyses.reduce((sum, analysis) => sum + analysis.issues.filter(issue => issue.type === 'warning').length, 0);
        const infoIssues = fileAnalyses.reduce((sum, analysis) => sum + analysis.issues.filter(issue => issue.type === 'info').length, 0);
        const averageComplexity = fileAnalyses.reduce((sum, analysis) => sum + analysis.complexity, 0) / fileAnalyses.length;
        return {
            totalFiles: fileAnalyses.length,
            totalIssues,
            fileAnalyses,
            summary: {
                criticalIssues,
                warningIssues,
                infoIssues,
                averageComplexity: Math.round(averageComplexity * 100) / 100
            }
        };
    }
    async getOptimizationSuggestions() {
        const suggestions = [
            {
                title: 'Optimisation des requêtes SQL',
                description: 'Améliorer les performances des requêtes de base de données',
                impact: 'Élevé',
                details: 'Utilisez des index appropriés, évitez SELECT *, optimisez les JOINs',
                category: 'Database'
            },
            {
                title: 'Mise en cache',
                description: 'Implémenter une stratégie de cache efficace',
                impact: 'Élevé',
                details: 'Utilisez Redis ou Memcached pour les données fréquemment accédées',
                category: 'Performance'
            },
            {
                title: 'Optimisation des boucles',
                description: 'Réduire la complexité des algorithmes',
                impact: 'Moyen',
                details: 'Évitez les boucles imbriquées, utilisez des structures de données appropriées',
                category: 'Algorithm'
            },
            {
                title: 'Gestion mémoire',
                description: 'Optimiser l\'utilisation de la mémoire',
                impact: 'Moyen',
                details: 'Libérez les ressources non utilisées, utilisez des générateurs pour les gros datasets',
                category: 'Memory'
            },
            {
                title: 'Appels asynchrones',
                description: 'Implémenter des appels non-bloquants',
                impact: 'Élevé',
                details: 'Utilisez des promesses et async/await pour les opérations I/O',
                category: 'Network'
            }
        ];
        return suggestions;
    }
    async profileFunction(functionCode, filePath) {
        try {
            // Extract function name
            const functionNameMatch = functionCode.match(/function\s+(\w+)/);
            const functionName = functionNameMatch ? functionNameMatch[1] : 'anonymous';
            // Simulate profiling (in a real implementation, this would use actual profiling tools)
            const complexity = this.calculateComplexity(functionCode);
            const linesOfCode = functionCode.split('\n').length;
            const executionTime = Math.round(complexity * linesOfCode * 0.5 + Math.random() * 10);
            const memoryUsage = Math.round(linesOfCode * 0.1 + Math.random() * 5);
            const callCount = Math.floor(Math.random() * 100) + 1;
            const bottlenecks = [];
            if (functionCode.includes('SELECT')) {
                bottlenecks.push('Requête SQL détectée');
            }
            if (functionCode.includes('file_get_contents')) {
                bottlenecks.push('Opération I/O bloquante');
            }
            if (complexity > 10) {
                bottlenecks.push('Complexité algorithmique élevée');
            }
            return {
                functionName,
                executionTime,
                memoryUsage,
                callCount,
                bottlenecks
            };
        }
        catch (error) {
            console.error('Error profiling function:', error);
            return null;
        }
    }
    getFileAnalysis(filePath) {
        return this.analysisCache.get(filePath) || null;
    }
    getProjectAnalysis() {
        return this.projectAnalysis;
    }
    clearCache() {
        this.analysisCache.clear();
        this.projectAnalysis = null;
    }
}
exports.PerformanceAnalyzer = PerformanceAnalyzer;
//# sourceMappingURL=performanceAnalyzer.js.map