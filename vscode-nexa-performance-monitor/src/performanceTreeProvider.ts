import * as vscode from 'vscode';
import { PerformanceMonitor, PerformanceMetrics } from './performanceMonitor';

export class PerformanceTreeProvider implements vscode.TreeDataProvider<PerformanceItem> {
    private _onDidChangeTreeData: vscode.EventEmitter<PerformanceItem | undefined | null | void> = new vscode.EventEmitter<PerformanceItem | undefined | null | void>();
    readonly onDidChangeTreeData: vscode.Event<PerformanceItem | undefined | null | void> = this._onDidChangeTreeData.event;

    constructor(
        private context: vscode.ExtensionContext,
        private performanceMonitor: PerformanceMonitor
    ) {}

    refresh(): void {
        this._onDidChangeTreeData.fire();
    }

    getTreeItem(element: PerformanceItem): vscode.TreeItem {
        return element;
    }

    async getChildren(element?: PerformanceItem): Promise<PerformanceItem[]> {
        if (!element) {
            return this.getRootItems();
        }

        switch (element.contextValue) {
            case 'monitoring':
                return this.getMonitoringItems();
            case 'metrics':
                return this.getMetricsItems();
            case 'alerts':
                return this.getAlertsItems();
            case 'reports':
                return this.getReportsItems();
            default:
                return [];
        }
    }

    private getRootItems(): PerformanceItem[] {
        const isMonitoring = this.performanceMonitor.isCurrentlyMonitoring();
        const monitoringIcon = isMonitoring ? 'pulse' : 'debug-stop';
        const monitoringLabel = isMonitoring ? 'Monitoring actif' : 'Monitoring arrêté';
        
        return [
            new PerformanceItem(
                monitoringLabel,
                vscode.TreeItemCollapsibleState.Expanded,
                'monitoring',
                new vscode.ThemeIcon(monitoringIcon),
                isMonitoring ? 'Monitoring en cours...' : 'Cliquez pour démarrer'
            ),
            new PerformanceItem(
                'Métriques',
                vscode.TreeItemCollapsibleState.Collapsed,
                'metrics',
                new vscode.ThemeIcon('graph'),
                'Métriques de performance'
            ),
            new PerformanceItem(
                'Alertes',
                vscode.TreeItemCollapsibleState.Collapsed,
                'alerts',
                new vscode.ThemeIcon('warning'),
                'Alertes de performance'
            ),
            new PerformanceItem(
                'Rapports',
                vscode.TreeItemCollapsibleState.Collapsed,
                'reports',
                new vscode.ThemeIcon('file-text'),
                'Rapports d\'analyse'
            )
        ];
    }

    private getMonitoringItems(): PerformanceItem[] {
        const isMonitoring = this.performanceMonitor.isCurrentlyMonitoring();
        const latestMetrics = this.performanceMonitor.getLatestMetrics();
        
        const items: PerformanceItem[] = [];
        
        if (isMonitoring) {
            items.push(
                new PerformanceItem(
                    'Arrêter le monitoring',
                    vscode.TreeItemCollapsibleState.None,
                    'stopMonitoring',
                    new vscode.ThemeIcon('debug-stop'),
                    'Arrêter la surveillance'
                )
            );
        } else {
            items.push(
                new PerformanceItem(
                    'Démarrer le monitoring',
                    vscode.TreeItemCollapsibleState.None,
                    'startMonitoring',
                    new vscode.ThemeIcon('play'),
                    'Démarrer la surveillance'
                )
            );
        }
        
        if (latestMetrics) {
            items.push(
                new PerformanceItem(
                    `CPU: ${latestMetrics.cpuUsage}%`,
                    vscode.TreeItemCollapsibleState.None,
                    'metric',
                    new vscode.ThemeIcon('cpu'),
                    `Utilisation CPU: ${latestMetrics.cpuUsage}%`
                ),
                new PerformanceItem(
                    `Mémoire: ${latestMetrics.memoryUsage}MB`,
                    vscode.TreeItemCollapsibleState.None,
                    'metric',
                    new vscode.ThemeIcon('database'),
                    `Utilisation mémoire: ${latestMetrics.memoryUsage}MB`
                ),
                new PerformanceItem(
                    `Temps de réponse: ${Math.round(latestMetrics.responseTime)}ms`,
                    vscode.TreeItemCollapsibleState.None,
                    'metric',
                    new vscode.ThemeIcon('clock'),
                    `Temps de réponse moyen: ${Math.round(latestMetrics.responseTime)}ms`
                )
            );
        }
        
        return items;
    }

    private getMetricsItems(): PerformanceItem[] {
        const metrics = this.performanceMonitor.getMetrics();
        
        if (metrics.length === 0) {
            return [
                new PerformanceItem(
                    'Aucune métrique disponible',
                    vscode.TreeItemCollapsibleState.None,
                    'noData',
                    new vscode.ThemeIcon('info'),
                    'Démarrez le monitoring pour collecter des métriques'
                )
            ];
        }
        
        const latest = metrics[metrics.length - 1];
        const items: PerformanceItem[] = [];
        
        // Summary metrics
        const avgMemory = metrics.reduce((sum, m) => sum + m.memoryUsage, 0) / metrics.length;
        const avgResponseTime = metrics.reduce((sum, m) => sum + m.responseTime, 0) / metrics.length;
        const maxMemory = Math.max(...metrics.map(m => m.memoryUsage));
        const maxResponseTime = Math.max(...metrics.map(m => m.responseTime));
        
        items.push(
            new PerformanceItem(
                `Échantillons: ${metrics.length}`,
                vscode.TreeItemCollapsibleState.None,
                'summary',
                new vscode.ThemeIcon('list-ordered'),
                `${metrics.length} échantillons collectés`
            ),
            new PerformanceItem(
                `Mémoire moyenne: ${Math.round(avgMemory)}MB`,
                vscode.TreeItemCollapsibleState.None,
                'summary',
                new vscode.ThemeIcon('graph'),
                `Utilisation mémoire moyenne: ${Math.round(avgMemory)}MB`
            ),
            new PerformanceItem(
                `Mémoire pic: ${maxMemory}MB`,
                vscode.TreeItemCollapsibleState.None,
                'summary',
                new vscode.ThemeIcon('arrow-up'),
                `Pic d'utilisation mémoire: ${maxMemory}MB`
            ),
            new PerformanceItem(
                `Temps réponse moyen: ${Math.round(avgResponseTime)}ms`,
                vscode.TreeItemCollapsibleState.None,
                'summary',
                new vscode.ThemeIcon('clock'),
                `Temps de réponse moyen: ${Math.round(avgResponseTime)}ms`
            ),
            new PerformanceItem(
                `Temps réponse max: ${Math.round(maxResponseTime)}ms`,
                vscode.TreeItemCollapsibleState.None,
                'summary',
                new vscode.ThemeIcon('stopwatch'),
                `Temps de réponse maximum: ${Math.round(maxResponseTime)}ms`
            )
        );
        
        return items;
    }

    private getAlertsItems(): PerformanceItem[] {
        const latestMetrics = this.performanceMonitor.getLatestMetrics();
        const config = vscode.workspace.getConfiguration('nexa.performance');
        const memoryThreshold = config.get<number>('memoryThreshold', 128);
        const timeThreshold = config.get<number>('executionTimeThreshold', 1000);
        
        const alerts: PerformanceItem[] = [];
        
        if (!latestMetrics) {
            return [
                new PerformanceItem(
                    'Aucune donnée disponible',
                    vscode.TreeItemCollapsibleState.None,
                    'noData',
                    new vscode.ThemeIcon('info'),
                    'Démarrez le monitoring pour voir les alertes'
                )
            ];
        }
        
        // Memory alerts
        if (latestMetrics.memoryUsage > memoryThreshold) {
            alerts.push(
                new PerformanceItem(
                    `⚠️ Mémoire élevée: ${latestMetrics.memoryUsage}MB`,
                    vscode.TreeItemCollapsibleState.None,
                    'alert',
                    new vscode.ThemeIcon('warning'),
                    `Utilisation mémoire au-dessus du seuil (${memoryThreshold}MB)`
                )
            );
        }
        
        // Response time alerts
        if (latestMetrics.responseTime > timeThreshold) {
            alerts.push(
                new PerformanceItem(
                    `⚠️ Temps de réponse élevé: ${Math.round(latestMetrics.responseTime)}ms`,
                    vscode.TreeItemCollapsibleState.None,
                    'alert',
                    new vscode.ThemeIcon('clock'),
                    `Temps de réponse au-dessus du seuil (${timeThreshold}ms)`
                )
            );
        }
        
        // Error rate alerts
        if (latestMetrics.errorRate > 5) {
            alerts.push(
                new PerformanceItem(
                    `🚨 Taux d'erreur élevé: ${latestMetrics.errorRate.toFixed(1)}%`,
                    vscode.TreeItemCollapsibleState.None,
                    'alert',
                    new vscode.ThemeIcon('error'),
                    `Taux d'erreur critique: ${latestMetrics.errorRate.toFixed(1)}%`
                )
            );
        }
        
        if (alerts.length === 0) {
            alerts.push(
                new PerformanceItem(
                    '✅ Aucune alerte',
                    vscode.TreeItemCollapsibleState.None,
                    'noAlert',
                    new vscode.ThemeIcon('check'),
                    'Toutes les métriques sont dans les seuils normaux'
                )
            );
        }
        
        return alerts;
    }

    private getReportsItems(): PerformanceItem[] {
        return [
            new PerformanceItem(
                'Rapport détaillé',
                vscode.TreeItemCollapsibleState.None,
                'detailedReport',
                new vscode.ThemeIcon('file-text'),
                'Générer un rapport détaillé'
            ),
            new PerformanceItem(
                'Suggestions d\'optimisation',
                vscode.TreeItemCollapsibleState.None,
                'optimizationSuggestions',
                new vscode.ThemeIcon('lightbulb'),
                'Voir les suggestions d\'amélioration'
            ),
            new PerformanceItem(
                'Analyser le fichier actuel',
                vscode.TreeItemCollapsibleState.None,
                'analyzeCurrentFile',
                new vscode.ThemeIcon('search'),
                'Analyser le fichier ouvert'
            ),
            new PerformanceItem(
                'Analyser le projet',
                vscode.TreeItemCollapsibleState.None,
                'analyzeProject',
                new vscode.ThemeIcon('folder'),
                'Analyser tout le projet'
            )
        ];
    }
}

export class PerformanceItem extends vscode.TreeItem {
    constructor(
        public readonly label: string,
        public readonly collapsibleState: vscode.TreeItemCollapsibleState,
        public readonly contextValue: string,
        public readonly iconPath?: vscode.ThemeIcon,
        public readonly tooltip?: string
    ) {
        super(label, collapsibleState);
        this.contextValue = contextValue;
        this.iconPath = iconPath;
        this.tooltip = tooltip;
        
        // Add commands for interactive items
        switch (contextValue) {
            case 'startMonitoring':
                this.command = {
                    command: 'nexa.performance.startMonitoring',
                    title: 'Démarrer le monitoring'
                };
                break;
            case 'stopMonitoring':
                this.command = {
                    command: 'nexa.performance.stopMonitoring',
                    title: 'Arrêter le monitoring'
                };
                break;
            case 'detailedReport':
                this.command = {
                    command: 'nexa.performance.showReport',
                    title: 'Afficher le rapport'
                };
                break;
            case 'optimizationSuggestions':
                this.command = {
                    command: 'nexa.performance.optimizationSuggestions',
                    title: 'Suggestions d\'optimisation'
                };
                break;
            case 'analyzeCurrentFile':
                this.command = {
                    command: 'nexa.performance.analyzeFile',
                    title: 'Analyser le fichier'
                };
                break;
            case 'analyzeProject':
                this.command = {
                    command: 'nexa.performance.analyzeProject',
                    title: 'Analyser le projet'
                };
                break;
        }
    }
}