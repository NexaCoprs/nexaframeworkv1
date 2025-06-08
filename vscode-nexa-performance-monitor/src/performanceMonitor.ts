import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import { performance } from 'perf_hooks';
const chokidar = require('chokidar');

export interface PerformanceMetrics {
    timestamp: number;
    cpuUsage: number;
    memoryUsage: number;
    responseTime: number;
    activeConnections: number;
    errorRate: number;
}

export interface MemoryReport {
    used: number;
    total: number;
    percentage: number;
    details: {
        heap: number;
        external: number;
        buffers: number;
    };
}

export class PerformanceMonitor {
    private isMonitoring: boolean = false;
    private monitoringInterval: NodeJS.Timeout | null = null;
    private metrics: PerformanceMetrics[] = [];
    private maxMetricsHistory: number = 1000;
    private samplingInterval: number;
    private memoryThreshold: number;
    private executionTimeThreshold: number;

    constructor(private context: vscode.ExtensionContext) {
        this.updateConfiguration();
    }

    updateConfiguration(): void {
        const config = vscode.workspace.getConfiguration('nexa.performance');
        this.samplingInterval = config.get<number>('samplingInterval', 1000);
        this.memoryThreshold = config.get<number>('memoryThreshold', 128);
        this.executionTimeThreshold = config.get<number>('executionTimeThreshold', 1000);
    }

    async startMonitoring(): Promise<void> {
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

    async stopMonitoring(): Promise<void> {
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

    private collectMetrics(): void {
        const now = Date.now();
        const memUsage = process.memoryUsage();
        
        const metrics: PerformanceMetrics = {
            timestamp: now,
            cpuUsage: this.getCpuUsage(),
            memoryUsage: Math.round(memUsage.heapUsed / 1024 / 1024), // MB
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

    private getCpuUsage(): number {
        // Simplified CPU usage calculation
        // In a real implementation, you would use more sophisticated methods
        const usage = process.cpuUsage();
        return Math.round((usage.user + usage.system) / 1000000); // Convert to percentage
    }

    private getAverageResponseTime(): number {
        // Simulate response time calculation
        // In a real implementation, this would track actual HTTP response times
        return Math.random() * 100 + 50; // 50-150ms
    }

    private getActiveConnections(): number {
        // Simulate active connections count
        return Math.floor(Math.random() * 50) + 10;
    }

    private getErrorRate(): number {
        // Simulate error rate calculation
        return Math.random() * 5; // 0-5% error rate
    }

    private checkThresholds(metrics: PerformanceMetrics): void {
        if (metrics.memoryUsage > this.memoryThreshold) {
            vscode.window.showWarningMessage(
                `⚠️ Utilisation mémoire élevée: ${metrics.memoryUsage}MB (seuil: ${this.memoryThreshold}MB)`
            );
        }

        if (metrics.responseTime > this.executionTimeThreshold) {
            vscode.window.showWarningMessage(
                `⚠️ Temps de réponse élevé: ${metrics.responseTime}ms (seuil: ${this.executionTimeThreshold}ms)`
            );
        }

        if (metrics.errorRate > 10) {
            vscode.window.showErrorMessage(
                `🚨 Taux d'erreur élevé: ${metrics.errorRate.toFixed(1)}%`
            );
        }
    }

    private watcher: any = null;

    private startFileWatcher(): void {
        if (!vscode.workspace.workspaceFolders) {
            return;
        }

        const watcher = vscode.workspace.createFileSystemWatcher('**/*.{php,nx}');
        
        watcher.onDidChange(uri => {
            this.analyzeFilePerformance(uri.fsPath);
        });

        this.context.subscriptions.push(watcher);
    }

    private async analyzeFilePerformance(filePath: string): Promise<void> {
        try {
            const content = fs.readFileSync(filePath, 'utf8');
            const analysis = this.quickPerformanceCheck(content);
            
            if (analysis.issues.length > 0) {
                const message = `Performance: ${analysis.issues.length} problème(s) détecté(s) dans ${path.basename(filePath)}`;
                vscode.window.showInformationMessage(message);
            }
        } catch (error) {
            console.error('Error analyzing file performance:', error);
        }
    }

    private quickPerformanceCheck(content: string): { issues: string[] } {
        const issues: string[] = [];
        
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

    async getMemoryUsage(): Promise<MemoryReport> {
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

    getMetrics(): PerformanceMetrics[] {
        return [...this.metrics];
    }

    getLatestMetrics(): PerformanceMetrics | null {
        return this.metrics.length > 0 ? this.metrics[this.metrics.length - 1] : null;
    }

    isCurrentlyMonitoring(): boolean {
        return this.isMonitoring;
    }

    private async saveMetrics(): Promise<void> {
        try {
            const metricsData = {
                timestamp: Date.now(),
                metrics: this.metrics,
                summary: this.generateSummary()
            };
            
            const storageUri = vscode.Uri.joinPath(this.context.globalStorageUri, 'performance-metrics.json');
            await vscode.workspace.fs.writeFile(storageUri, Buffer.from(JSON.stringify(metricsData, null, 2)));
        } catch (error) {
            console.error('Error saving metrics:', error);
        }
    }

    private generateSummary(): any {
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