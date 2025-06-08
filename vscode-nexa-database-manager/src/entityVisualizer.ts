import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class EntityVisualizer {
    constructor(private context: vscode.ExtensionContext) {}

    async showEntityDiagram(): Promise<void> {
        const panel = vscode.window.createWebviewPanel(
            'nexaEntityDiagram',
            'Diagramme des Entités Nexa',
            vscode.ViewColumn.One,
            {
                enableScripts: true,
                retainContextWhenHidden: true
            }
        );

        const entities = await this.getEntitiesData();
        panel.webview.html = this.getEntityDiagramHtml(entities);

        panel.webview.onDidReceiveMessage(
            async message => {
                switch (message.command) {
                    case 'openEntity':
                        await this.openEntityFile(message.entityName);
                        break;
                    case 'createRelation':
                        await this.createRelation(message.from, message.to, message.type);
                        break;
                }
            },
            undefined,
            this.context.subscriptions
        );
    }

    private async getEntitiesData(): Promise<any[]> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return [];

        const entitiesPath = path.join(workspaceFolder.uri.fsPath, 'workspace', 'database', 'entities');
        
        try {
            const files = await fs.promises.readdir(entitiesPath);
            const entities = [];

            for (const file of files.filter(f => f.endsWith('.php'))) {
                const filePath = path.join(entitiesPath, file);
                const content = await fs.promises.readFile(filePath, 'utf8');
                const entityData = this.parseEntityFile(content, path.basename(file, '.php'));
                entities.push(entityData);
            }

            return entities;
        } catch (error) {
            return [];
        }
    }

    private parseEntityFile(content: string, entityName: string): any {
        const fields = [];
        const relations = [];

        // Parser basique pour extraire les propriétés
        const propertyRegex = /(?:public|private|protected)\s+(?:\w+\s+)?\$(\w+)(?:\s*=\s*[^;]+)?;/g;
        let match;
        
        while ((match = propertyRegex.exec(content)) !== null) {
            fields.push({
                name: match[1],
                type: 'string' // Type par défaut, pourrait être amélioré
            });
        }

        // Parser les relations (OneToMany, ManyToOne, etc.)
        const relationRegex = /#\[(?:OneToMany|ManyToOne|OneToOne|ManyToMany)\([^\]]+\)\]/g;
        while ((match = relationRegex.exec(content)) !== null) {
            relations.push({
                type: match[0],
                target: 'Unknown' // Pourrait être amélioré
            });
        }

        return {
            name: entityName,
            fields,
            relations,
            x: Math.random() * 800 + 100,
            y: Math.random() * 600 + 100
        };
    }

    private async openEntityFile(entityName: string): Promise<void> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) return;

        const entityPath = path.join(
            workspaceFolder.uri.fsPath,
            'workspace',
            'database',
            'entities',
            `${entityName}.php`
        );

        try {
            const doc = await vscode.workspace.openTextDocument(entityPath);
            await vscode.window.showTextDocument(doc);
        } catch (error) {
            vscode.window.showErrorMessage(`Impossible d'ouvrir l'entité ${entityName}`);
        }
    }

    private async createRelation(from: string, to: string, type: string): Promise<void> {
        vscode.window.showInformationMessage(`Création d'une relation ${type} de ${from} vers ${to}`);
        // Logique pour créer une relation
    }

    private getEntityDiagramHtml(entities: any[]): string {
        return `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Diagramme des Entités</title>
            <script src="https://d3js.org/d3.v7.min.js"></script>
            <style>
                body {
                    font-family: var(--vscode-font-family);
                    color: var(--vscode-foreground);
                    background-color: var(--vscode-editor-background);
                    margin: 0;
                    padding: 0;
                    overflow: hidden;
                }
                #diagram {
                    width: 100vw;
                    height: 100vh;
                }
                .entity {
                    fill: var(--vscode-editor-background);
                    stroke: var(--vscode-panel-border);
                    stroke-width: 2;
                    cursor: move;
                }
                .entity:hover {
                    stroke: var(--vscode-focusBorder);
                }
                .entity-title {
                    font-weight: bold;
                    font-size: 14px;
                    fill: var(--vscode-foreground);
                }
                .entity-field {
                    font-size: 12px;
                    fill: var(--vscode-descriptionForeground);
                }
                .relation {
                    stroke: var(--vscode-charts-blue);
                    stroke-width: 2;
                    marker-end: url(#arrowhead);
                }
            </style>
        </head>
        <body>
            <svg id="diagram"></svg>
            
            <script>
                const vscode = acquireVsCodeApi();
                const entities = ${JSON.stringify(entities)};
                
                const svg = d3.select('#diagram');
                const width = window.innerWidth;
                const height = window.innerHeight;
                
                svg.attr('width', width).attr('height', height);
                
                // Définir les marqueurs pour les flèches
                svg.append('defs')
                    .append('marker')
                    .attr('id', 'arrowhead')
                    .attr('viewBox', '0 -5 10 10')
                    .attr('refX', 8)
                    .attr('refY', 0)
                    .attr('markerWidth', 6)
                    .attr('markerHeight', 6)
                    .attr('orient', 'auto')
                    .append('path')
                    .attr('d', 'M0,-5L10,0L0,5')
                    .attr('fill', 'var(--vscode-charts-blue)');
                
                // Dessiner les entités
                const entityGroups = svg.selectAll('.entity-group')
                    .data(entities)
                    .enter()
                    .append('g')
                    .attr('class', 'entity-group')
                    .attr('transform', d => \`translate(\${d.x}, \${d.y})\`);
                
                // Rectangle de l'entité
                entityGroups.append('rect')
                    .attr('class', 'entity')
                    .attr('width', 200)
                    .attr('height', d => 40 + d.fields.length * 20)
                    .attr('rx', 5)
                    .on('click', function(event, d) {
                        vscode.postMessage({
                            command: 'openEntity',
                            entityName: d.name
                        });
                    });
                
                // Titre de l'entité
                entityGroups.append('text')
                    .attr('class', 'entity-title')
                    .attr('x', 10)
                    .attr('y', 20)
                    .text(d => d.name);
                
                // Ligne de séparation
                entityGroups.append('line')
                    .attr('x1', 0)
                    .attr('y1', 30)
                    .attr('x2', 200)
                    .attr('y2', 30)
                    .attr('stroke', 'var(--vscode-panel-border)');
                
                // Champs de l'entité
                entityGroups.selectAll('.field')
                    .data(d => d.fields)
                    .enter()
                    .append('text')
                    .attr('class', 'entity-field')
                    .attr('x', 10)
                    .attr('y', (d, i) => 50 + i * 20)
                    .text(d => \`\${d.name}: \${d.type}\`);
                
                // Drag behavior
                const drag = d3.drag()
                    .on('drag', function(event, d) {
                        d.x = event.x;
                        d.y = event.y;
                        d3.select(this).attr('transform', \`translate(\${d.x}, \${d.y})\`);
                    });
                
                entityGroups.call(drag);
                
                // Zoom behavior
                const zoom = d3.zoom()
                    .scaleExtent([0.1, 3])
                    .on('zoom', function(event) {
                        svg.selectAll('.entity-group')
                            .attr('transform', d => \`translate(\${event.transform.x + d.x * event.transform.k}, \${event.transform.y + d.y * event.transform.k}) scale(\${event.transform.k})\`);
                    });
                
                svg.call(zoom);
            </script>
        </body>
        </html>
        `;
    }
}