"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.PostmanExporter = void 0;
const vscode = require("vscode");
class PostmanExporter {
    exportCollection(collection) {
        const postmanCollection = {
            info: {
                name: collection.name,
                description: `Exported from Nexa API Tester on ${new Date().toISOString()}`,
                schema: "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
            },
            item: collection.requests.map(request => this.convertRequest(request))
        };
        return JSON.stringify(postmanCollection, null, 2);
    }
    convertRequest(request) {
        return {
            name: request.name,
            request: {
                method: request.method,
                header: Object.entries(request.headers).map(([key, value]) => ({
                    key,
                    value,
                    type: "text"
                })),
                url: {
                    raw: request.url,
                    protocol: new URL(request.url).protocol.replace(':', ''),
                    host: new URL(request.url).hostname.split('.'),
                    port: new URL(request.url).port || (new URL(request.url).protocol === 'https:' ? '443' : '80'),
                    path: new URL(request.url).pathname.split('/').filter(p => p)
                },
                body: request.body ? {
                    mode: "raw",
                    raw: typeof request.body === 'string' ? request.body : JSON.stringify(request.body),
                    options: {
                        raw: {
                            language: "json"
                        }
                    }
                } : undefined
            }
        };
    }
    async exportToFile(collection) {
        const content = this.exportCollection(collection);
        const fileName = `${collection.name.replace(/[^a-zA-Z0-9]/g, '_')}.postman_collection.json`;
        const uri = await vscode.window.showSaveDialog({
            defaultUri: vscode.Uri.file(fileName),
            filters: {
                'Postman Collections': ['json']
            }
        });
        if (uri) {
            await vscode.workspace.fs.writeFile(uri, Buffer.from(content, 'utf8'));
            vscode.window.showInformationMessage(`Collection exportée vers ${uri.fsPath}`);
        }
    }
}
exports.PostmanExporter = PostmanExporter;
//# sourceMappingURL=postmanExporter.js.map