"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.RequestManager = void 0;
const https = require("https");
const http = require("http");
const url_1 = require("url");
class RequestManager {
    constructor(context) {
        this.collections = [];
        this.context = context;
        this.loadCollections();
    }
    async sendRequest(requestText) {
        try {
            // Parser le texte de requête (format simple)
            const lines = requestText.trim().split('\n');
            const firstLine = lines[0].split(' ');
            const method = firstLine[0];
            const url = firstLine[1];
            // Extraire les en-têtes
            const headers = {};
            let bodyStart = -1;
            for (let i = 1; i < lines.length; i++) {
                const line = lines[i].trim();
                if (line === '') {
                    bodyStart = i + 1;
                    break;
                }
                const [key, ...valueParts] = line.split(':');
                if (key && valueParts.length > 0) {
                    headers[key.trim()] = valueParts.join(':').trim();
                }
            }
            // Extraire le corps
            let body;
            if (bodyStart > 0 && bodyStart < lines.length) {
                const bodyText = lines.slice(bodyStart).join('\n').trim();
                if (bodyText) {
                    try {
                        body = JSON.parse(bodyText);
                    }
                    catch {
                        body = bodyText;
                    }
                }
            }
            return await this.sendHttpRequest({ method, url, headers, body });
        }
        catch (error) {
            throw new Error(`Erreur lors du parsing de la requête: ${error}`);
        }
    }
    async sendHttpRequest(request) {
        return new Promise((resolve, reject) => {
            const startTime = Date.now();
            const url = new url_1.URL(request.url);
            const isHttps = url.protocol === 'https:';
            const client = isHttps ? https : http;
            const options = {
                hostname: url.hostname,
                port: url.port || (isHttps ? 443 : 80),
                path: url.pathname + url.search,
                method: request.method,
                headers: request.headers
            };
            const req = client.request(options, (res) => {
                let data = '';
                res.on('data', (chunk) => {
                    data += chunk;
                });
                res.on('end', () => {
                    const responseTime = Date.now() - startTime;
                    let body;
                    try {
                        body = JSON.parse(data);
                    }
                    catch {
                        body = data;
                    }
                    const response = {
                        status: res.statusCode || 0,
                        statusText: res.statusMessage || '',
                        headers: res.headers,
                        body,
                        responseTime
                    };
                    resolve(response);
                });
            });
            req.on('error', (error) => {
                reject(new Error(`Erreur de requête: ${error.message}`));
            });
            // Envoyer le corps de la requête si présent
            if (request.body) {
                const bodyData = typeof request.body === 'string'
                    ? request.body
                    : JSON.stringify(request.body);
                req.write(bodyData);
            }
            req.end();
        });
    }
    saveRequest(requestData) {
        const request = {
            id: this.generateId(),
            name: `${requestData.method} ${requestData.url}`,
            method: requestData.method,
            url: requestData.url,
            headers: requestData.headers || {},
            body: requestData.body,
            timestamp: new Date().toISOString()
        };
        // Ajouter à la collection par défaut
        let defaultCollection = this.collections.find(c => c.name === 'Défaut');
        if (!defaultCollection) {
            defaultCollection = this.createCollection('Défaut');
        }
        defaultCollection.requests.push(request);
        this.saveCollections();
    }
    createCollection(name) {
        const collection = {
            id: this.generateId(),
            name,
            requests: [],
            createdAt: new Date().toISOString()
        };
        this.collections.push(collection);
        this.saveCollections();
        return collection;
    }
    getCollections() {
        return this.collections;
    }
    getCollection(id) {
        return this.collections.find(c => c.id === id);
    }
    deleteCollection(id) {
        const index = this.collections.findIndex(c => c.id === id);
        if (index > -1) {
            this.collections.splice(index, 1);
            this.saveCollections();
            return true;
        }
        return false;
    }
    addRequestToCollection(collectionId, request) {
        const collection = this.getCollection(collectionId);
        if (collection) {
            request.id = this.generateId();
            collection.requests.push(request);
            this.saveCollections();
            return true;
        }
        return false;
    }
    removeRequestFromCollection(collectionId, requestId) {
        const collection = this.getCollection(collectionId);
        if (collection) {
            const index = collection.requests.findIndex(r => r.id === requestId);
            if (index > -1) {
                collection.requests.splice(index, 1);
                this.saveCollections();
                return true;
            }
        }
        return false;
    }
    loadCollections() {
        if (!this.context) {
            return;
        }
        try {
            const saved = this.context.globalState.get('nexaApiCollections', []);
            this.collections = saved;
        }
        catch (error) {
            console.error('Erreur lors du chargement des collections:', error);
            this.collections = [];
        }
    }
    saveCollections() {
        if (!this.context) {
            return;
        }
        try {
            this.context.globalState.update('nexaApiCollections', this.collections);
        }
        catch (error) {
            console.error('Erreur lors de la sauvegarde des collections:', error);
        }
    }
    generateId() {
        return Math.random().toString(36).substr(2, 9);
    }
    // Méthodes pour l'import/export
    exportCollections() {
        return JSON.stringify(this.collections, null, 2);
    }
    importCollections(data) {
        try {
            const imported = JSON.parse(data);
            // Valider la structure
            if (!Array.isArray(imported)) {
                throw new Error('Format invalide');
            }
            // Fusionner avec les collections existantes
            for (const collection of imported) {
                if (!this.collections.find(c => c.id === collection.id)) {
                    this.collections.push(collection);
                }
            }
            this.saveCollections();
            return true;
        }
        catch (error) {
            console.error('Erreur lors de l\'import:', error);
            return false;
        }
    }
    // Recherche dans les collections
    searchRequests(query) {
        const results = [];
        const lowerQuery = query.toLowerCase();
        for (const collection of this.collections) {
            for (const request of collection.requests) {
                if (request.name.toLowerCase().includes(lowerQuery) ||
                    request.url.toLowerCase().includes(lowerQuery) ||
                    request.method.toLowerCase().includes(lowerQuery)) {
                    results.push(request);
                }
            }
        }
        return results;
    }
    // Statistiques
    getStats() {
        const methodStats = {};
        let totalRequests = 0;
        for (const collection of this.collections) {
            totalRequests += collection.requests.length;
            for (const request of collection.requests) {
                methodStats[request.method] = (methodStats[request.method] || 0) + 1;
            }
        }
        return {
            totalCollections: this.collections.length,
            totalRequests,
            methodStats
        };
    }
}
exports.RequestManager = RequestManager;
//# sourceMappingURL=requestManager.js.map