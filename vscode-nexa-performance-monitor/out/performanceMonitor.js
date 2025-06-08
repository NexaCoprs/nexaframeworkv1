"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.PerformanceMonitor = void 0;
const vscode = require("vscode");
const fs = require("fs");
const path = require("path");
const chokidar = require('chokidar');
class PerformanceMonitor {
    constructor(context) {
        this.context = context;
        this.isMonitoring = false;
        this.monitoringInterval = null;
        this.metrics = [];
        this.maxMetricsHistory = 1000;
        this.watcher = null;
        this.updateConfiguration();
    }
    updateConfiguration() {
        const config = vscode.workspace.getConfiguration('nexa.performance');
        this.samplingInterval = config.get('samplingInterval', 1000);
        this.memoryThreshold = config.get('memoryThreshold', 128);
        this.executionTimeThreshold = config.get('executionTimeThreshold', 1000);
    }
    async startMonitoring() {
        if (this.isMonitoring) {
            return;
        }
        this.isMonitoring = true;
        this.metrics = [];
        this.monitoringInterval = setInterval(() => {
            this.collectMetrics();
        }, this.samplingInterval);
        // Start file system watcher for performance-critical files
        this.startFileWatcher();
    }
    async stopMonitoring() {
        if (!this.isMonitoring) {
            return;
        }
        this.isMonitoring = false;
        if (this.monitoringInterval) {
            clearInterval(this.monitoringInterval);
            this.monitoringInterval = null;
        }
        // Save metrics to storage
        await this.saveMetrics();
    }
    collectMetrics() {
        const now = Date.now();
        const memUsage = process.memoryUsage();
        const metrics = {
            timestamp: now,
            cpuUsage: this.getCpuUsage(),
            memoryUsage: Math.round(memUsage.heapUsed / 1024 / 1024),
            responseTime: this.getAverageResponseTime(),
            activeConnections: this.getActiveConnections(),
            errorRate: this.getErrorRate()
        };
        this.metrics.push(metrics);
        // Keep only recent metrics
        if (this.metrics.length > this.maxMetricsHistory) {
            this.metrics = this.metrics.slice(-this.maxMetricsHistory);
        }
        // Check thresholds and alert if necessary
        this.checkThresholds(metrics);
    }
    getCpuUsage() {
        // Simplified CPU usage calculation
        // In a real implementation, you would use more sophisticated methods
        const usage = process.cpuUsage();
        return Math.round((usage.user + usage.system) / 1000000); // Convert to percentage
    }
    getAverageResponseTime() {
        // Simulate response time calculation
        // In a real implementation, this would track actual HTTP response times
        return Math.random() * 100 + 50; // 50-150ms
    }
    getActiveConnections() {
        // Simulate active connections count
        return Math.floor(Math.random() * 50) + 10;
    }
    getErrorRate() {
        // Simulate error rate calculation
        return Math.random() * 5; // 0-5% error rate
    }
    checkThresholds(metrics) {
        if (metrics.memoryUsage > this.memoryThreshold) {
            vscode.window.showWarningMessage(`⚠️ Utilisation mémoire élevée: ${metrics.memoryUsage}MB (seuil: ${this.memoryThreshold}MB)`);
        }
        if (metrics.responseTime > this.executionTimeThreshold) {
            vscode.window.showWarningMessage(`⚠️ Temps de réponse élevé: ${metrics.responseTime}ms (seuil: ${this.executionTimeThreshold}ms)`);
        }
        if (metrics.errorRate > 10) {
            vscode.window.showErrorMessage(`🚨 Taux d'erreur élevé: ${metrics.errorRate.toFixed(1)}%`);
        }
    }
    startFileWatcher() {
        if (!vscode.workspace.workspaceFolders) {
            return;
        }
        const watcher = vscode.workspace.createFileSystemWatcher('**/*.{php,nx}');
        watcher.onDidChange(uri => {
            this.analyzeFilePerformance(uri.fsPath);
        });
        this.context.subscriptions.push(watcher);
    }
    async analyzeFilePerformance(filePath) {
        try {
            const content = fs.readFileSync(filePath, 'utf8');
            const analysis = this.quickPerformanceCheck(content);
            if (analysis.issues.length > 0) {
                const message = `Performance: ${analysis.issues.length} problème(s) détecté(s) dans ${path.basename(filePath)}`;
                vscode.window.showInformationMessage(message);
            }
        }
        catch (error) {
            console.error('Error analyzing file performance:', error);
        }
    }
    quickPerformanceCheck(content) {
        const issues = [];
        // Check for common performance issues
        if (content.includes('SELECT *')) {
            issues.push('SELECT * détecté - utilisez des colonnes spécifiques');
        }
        if (content.match(/for\s*\([^)]*\)\s*{[^}]*for\s*\(/)) {
            issues.push('Boucles imbriquées détectées - optimisation possible');
        }
        if (content.includes('file_get_contents(') && content.includes('http')) {
            issues.push('Appel HTTP synchrone détecté - utilisez des appels asynchrones');
        }
        const functionCount = (content.match(/function\s+\w+/g) || []).length;
        if (functionCount > 50) {
            issues.push('Fichier avec beaucoup de fonctions - considérez la refactorisation');
        }
        return { issues };
    }
    async getMemoryUsage() {
        const memUsage = process.memoryUsage();
        const totalMB = Math.round(memUsage.heapTotal / 1024 / 1024);
        const usedMB = Math.round(memUsage.heapUsed / 1024 / 1024);
        return {
            used: usedMB,
            total: totalMB,
            percentage: Math.round((usedMB / totalMB) * 100),
            details: {
                heap: Math.round(memUsage.heapUsed / 1024 / 1024),
                external: Math.round(memUsage.external / 1024 / 1024),
                buffers: Math.round(memUsage.arrayBuffers / 1024 / 1024)
            }
        };
    }
    getMetrics() {
        return [...this.metrics];
    }
    getLatestMetrics() {
        return this.metrics.length > 0 ? this.metrics[this.metrics.length - 1] : null;
    }
    isCurrentlyMonitoring() {
        return this.isMonitoring;
    }
    async saveMetrics() {
        try {
            const metricsData = {
                timestamp: Date.now(),
                metrics: this.metrics,
                summary: this.generateSummary()
            };
            const storageUri = vscode.Uri.joinPath(this.context.globalStorageUri, 'performance-metrics.json');
            await vscode.workspace.fs.writeFile(storageUri, Buffer.from(JSON.stringify(metricsData, null, 2)));
        }
        catch (error) {
            console.error('Error saving metrics:', error);
        }
    }
    generateSummary() {
        if (this.metrics.length === 0) {
            return null;
        }
        const avgMemory = this.metrics.reduce((sum, m) => sum + m.memoryUsage, 0) / this.metrics.length;
        const avgResponseTime = this.metrics.reduce((sum, m) => sum + m.responseTime, 0) / this.metrics.length;
        const maxMemory = Math.max(...this.metrics.map(m => m.memoryUsage));
        const maxResponseTime = Math.max(...this.metrics.map(m => m.responseTime));
        return {
            duration: this.metrics.length * this.samplingInterval,
            averageMemory: Math.round(avgMemory),
            averageResponseTime: Math.round(avgResponseTime),
            peakMemory: maxMemory,
            peakResponseTime: Math.round(maxResponseTime),
            totalSamples: this.metrics.length
        };
    }
}
exports.PerformanceMonitor = PerformanceMonitor;
//# sourceMappingURL=performanceMonitor.js.map