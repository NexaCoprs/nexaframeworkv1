import { RequestManager, ApiResponse } from './requestManager';

export interface TestResult {
    name: string;
    passed: boolean;
    error?: string;
    duration?: number;
    expected?: any;
    actual?: any;
}

export interface TestSuite {
    name: string;
    tests: TestResult[];
    passed: number;
    failed: number;
    total: number;
    duration: number;
}

export interface TestConfig {
    timeout?: number;
    retries?: number;
    validateSchema?: boolean;
    customTests?: CustomTest[];
}

export interface CustomTest {
    name: string;
    validator: (response: ApiResponse) => boolean | string;
}

export class TestRunner {
    private requestManager: RequestManager;
    private defaultTimeout = 5000;

    constructor(requestManager?: RequestManager) {
        this.requestManager = requestManager || new RequestManager();
    }

    async runTests(endpoint: string, config: TestConfig = {}): Promise<TestSuite> {
        const startTime = Date.now();
        const tests: TestResult[] = [];
        
        try {
            // Envoyer une requête GET de base
            const response = await this.requestManager.sendHttpRequest({
                method: 'GET',
                url: endpoint,
                headers: {
                    'User-Agent': 'Nexa-API-Tester/1.0',
                    'Accept': 'application/json'
                }
            });

            // Tests de base
            tests.push(...await this.runBasicTests(response));
            
            // Tests de performance
            tests.push(...await this.runPerformanceTests(response));
            
            // Tests de sécurité
            tests.push(...await this.runSecurityTests(endpoint));
            
            // Tests personnalisés
            if (config.customTests) {
                tests.push(...await this.runCustomTests(response, config.customTests));
            }

        } catch (error) {
            tests.push({
                name: 'Connectivité de base',
                passed: false,
                error: `Impossible de se connecter à l'endpoint: ${error}`
            });
        }

        const duration = Date.now() - startTime;
        const passed = tests.filter(t => t.passed).length;
        const failed = tests.filter(t => !t.passed).length;

        return {
            name: `Tests pour ${endpoint}`,
            tests,
            passed,
            failed,
            total: tests.length,
            duration
        };
    }

    private async runBasicTests(response: ApiResponse): Promise<TestResult[]> {
        const tests: TestResult[] = [];

        // Test du code de statut
        tests.push({
            name: 'Code de statut valide',
            passed: response.status >= 200 && response.status < 300,
            expected: '2xx',
            actual: response.status
        });

        // Test du Content-Type
        const contentType = response.headers['content-type'] || response.headers['Content-Type'];
        tests.push({
            name: 'Content-Type présent',
            passed: !!contentType,
            expected: 'En-tête Content-Type',
            actual: contentType || 'Absent'
        });

        // Test JSON valide
        if (contentType && contentType.includes('application/json')) {
            tests.push({
                name: 'JSON valide',
                passed: typeof response.body === 'object',
                expected: 'Objet JSON',
                actual: typeof response.body
            });
        }

        // Test de la présence du corps de réponse
        tests.push({
            name: 'Corps de réponse présent',
            passed: response.body !== null && response.body !== undefined,
            expected: 'Corps non vide',
            actual: response.body ? 'Présent' : 'Absent'
        });

        return tests;
    }

    private async runPerformanceTests(response: ApiResponse): Promise<TestResult[]> {
        const tests: TestResult[] = [];

        // Test du temps de réponse
        tests.push({
            name: 'Temps de réponse < 1s',
            passed: response.responseTime < 1000,
            expected: '< 1000ms',
            actual: `${response.responseTime}ms`,
            duration: response.responseTime
        });

        tests.push({
            name: 'Temps de réponse < 5s',
            passed: response.responseTime < 5000,
            expected: '< 5000ms',
            actual: `${response.responseTime}ms`,
            duration: response.responseTime
        });

        return tests;
    }

    private async runSecurityTests(endpoint: string): Promise<TestResult[]> {
        const tests: TestResult[] = [];

        try {
            // Test HTTPS
            const isHttps = endpoint.startsWith('https://');
            tests.push({
                name: 'Utilise HTTPS',
                passed: isHttps,
                expected: 'https://',
                actual: isHttps ? 'https://' : 'http://'
            });

            // Test des en-têtes de sécurité
            const response = await this.requestManager.sendHttpRequest({
                method: 'HEAD',
                url: endpoint,
                headers: {}
            });

            const securityHeaders = [
                'x-frame-options',
                'x-content-type-options',
                'x-xss-protection',
                'strict-transport-security'
            ];

            for (const header of securityHeaders) {
                const present = !!(response.headers[header] || response.headers[header.toUpperCase()]);
                tests.push({
                    name: `En-tête de sécurité: ${header}`,
                    passed: present,
                    expected: 'Présent',
                    actual: present ? 'Présent' : 'Absent'
                });
            }

        } catch (error) {
            tests.push({
                name: 'Tests de sécurité',
                passed: false,
                error: `Erreur lors des tests de sécurité: ${error}`
            });
        }

        return tests;
    }

    private async runCustomTests(response: ApiResponse, customTests: CustomTest[]): Promise<TestResult[]> {
        const tests: TestResult[] = [];

        for (const customTest of customTests) {
            try {
                const result = customTest.validator(response);
                
                if (typeof result === 'boolean') {
                    tests.push({
                        name: customTest.name,
                        passed: result
                    });
                } else {
                    tests.push({
                        name: customTest.name,
                        passed: false,
                        error: result
                    });
                }
            } catch (error) {
                tests.push({
                    name: customTest.name,
                    passed: false,
                    error: `Erreur dans le test personnalisé: ${error}`
                });
            }
        }

        return tests;
    }

    async generateTests(endpoint: string): Promise<string> {
        try {
            // Analyser l'endpoint pour générer des tests
            const response = await this.requestManager.sendHttpRequest({
                method: 'OPTIONS',
                url: endpoint,
                headers: {}
            });

            const allowedMethods = response.headers['allow'] || 'GET';
            const methods = allowedMethods.split(',').map(m => m.trim());

            let testCode = `// Tests générés automatiquement pour ${endpoint}\n`;
            testCode += `// Généré le ${new Date().toISOString()}\n\n`;
            
            testCode += `const { TestRunner } = require('./testRunner');\n`;
            testCode += `const testRunner = new TestRunner();\n\n`;

            for (const method of methods) {
                testCode += this.generateMethodTest(endpoint, method);
            }

            testCode += `\n// Exécuter tous les tests\n`;
            testCode += `async function runAllTests() {\n`;
            testCode += `    console.log('Démarrage des tests pour ${endpoint}');\n`;
            
            for (const method of methods) {
                testCode += `    await test${method}();\n`;
            }
            
            testCode += `    console.log('Tests terminés');\n`;
            testCode += `}\n\n`;
            testCode += `runAllTests().catch(console.error);\n`;

            return testCode;
        } catch (error) {
            throw new Error(`Erreur lors de la génération des tests: ${error}`);
        }
    }

    private generateMethodTest(endpoint: string, method: string): string {
        let testCode = `\nasync function test${method}() {\n`;
        testCode += `    console.log('Test ${method} ${endpoint}');\n`;
        testCode += `    \n`;
        testCode += `    const config = {\n`;
        testCode += `        timeout: 5000,\n`;
        testCode += `        customTests: [\n`;
        
        // Tests spécifiques selon la méthode
        switch (method.toUpperCase()) {
            case 'GET':
                testCode += `            {\n`;
                testCode += `                name: 'Données retournées',\n`;
                testCode += `                validator: (response) => {\n`;
                testCode += `                    return response.body && Object.keys(response.body).length > 0;\n`;
                testCode += `                }\n`;
                testCode += `            }\n`;
                break;
                
            case 'POST':
                testCode += `            {\n`;
                testCode += `                name: 'Création réussie',\n`;
                testCode += `                validator: (response) => {\n`;
                testCode += `                    return response.status === 201 || response.status === 200;\n`;
                testCode += `                }\n`;
                testCode += `            }\n`;
                break;
                
            case 'PUT':
            case 'PATCH':
                testCode += `            {\n`;
                testCode += `                name: 'Mise à jour réussie',\n`;
                testCode += `                validator: (response) => {\n`;
                testCode += `                    return response.status === 200 || response.status === 204;\n`;
                testCode += `                }\n`;
                testCode += `            }\n`;
                break;
                
            case 'DELETE':
                testCode += `            {\n`;
                testCode += `                name: 'Suppression réussie',\n`;
                testCode += `                validator: (response) => {\n`;
                testCode += `                    return response.status === 200 || response.status === 204 || response.status === 404;\n`;
                testCode += `                }\n`;
                testCode += `            }\n`;
                break;
        }
        
        testCode += `        ]\n`;
        testCode += `    };\n`;
        testCode += `    \n`;
        testCode += `    try {\n`;
        testCode += `        const results = await testRunner.runTests('${endpoint}', config);\n`;
        testCode += `        console.log(\`${method}: \${results.passed}/\${results.total} tests réussis\`);\n`;
        testCode += `        \n`;
        testCode += `        // Afficher les échecs\n`;
        testCode += `        const failures = results.tests.filter(t => !t.passed);\n`;
        testCode += `        if (failures.length > 0) {\n`;
        testCode += `            console.log('Échecs:');\n`;
        testCode += `            failures.forEach(f => console.log(\`  - \${f.name}: \${f.error || 'Échec'}\`));\n`;
        testCode += `        }\n`;
        testCode += `    } catch (error) {\n`;
        testCode += `        console.error(\`Erreur lors du test ${method}: \${error}\`);\n`;
        testCode += `    }\n`;
        testCode += `}\n`;
        
        return testCode;
    }

    // Méthodes utilitaires pour les tests
    static createCustomTest(name: string, validator: (response: ApiResponse) => boolean | string): CustomTest {
        return { name, validator };
    }

    static validateJsonSchema(schema: any): CustomTest {
        return {
            name: 'Validation du schéma JSON',
            validator: (response: ApiResponse) => {
                try {
                    // Ici, on pourrait utiliser une bibliothèque comme ajv pour la validation
                    // Pour l'instant, on fait une validation basique
                    if (typeof response.body !== 'object') {
                        return 'Le corps de réponse n\'est pas un objet JSON';
                    }
                    
                    // Vérifier les propriétés requises
                    if (schema.required) {
                        for (const prop of schema.required) {
                            if (!(prop in response.body)) {
                                return `Propriété requise manquante: ${prop}`;
                            }
                        }
                    }
                    
                    return true;
                } catch (error) {
                    return `Erreur de validation: ${error}`;
                }
            }
        };
    }

    static validateResponseTime(maxTime: number): CustomTest {
        return {
            name: `Temps de réponse < ${maxTime}ms`,
            validator: (response: ApiResponse) => {
                return response.responseTime < maxTime;
            }
        };
    }

    static validateStatusCode(expectedStatus: number): CustomTest {
        return {
            name: `Code de statut = ${expectedStatus}`,
            validator: (response: ApiResponse) => {
                return response.status === expectedStatus;
            }
        };
    }

    static validateHeader(headerName: string, expectedValue?: string): CustomTest {
        return {
            name: `En-tête ${headerName}${expectedValue ? ` = ${expectedValue}` : ' présent'}`,
            validator: (response: ApiResponse) => {
                const headerValue = response.headers[headerName] || response.headers[headerName.toLowerCase()];
                
                if (!headerValue) {
                    return `En-tête ${headerName} absent`;
                }
                
                if (expectedValue && headerValue !== expectedValue) {
                    return `Valeur attendue: ${expectedValue}, reçue: ${headerValue}`;
                }
                
                return true;
            }
        };
    }
}