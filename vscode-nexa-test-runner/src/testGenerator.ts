import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class TestGenerator {
    private outputChannel: vscode.OutputChannel;

    constructor() {
        this.outputChannel = vscode.window.createOutputChannel('Nexa Test Generator');
    }

    async generateTestForCurrentFile(): Promise<void> {
        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showWarningMessage('Aucun fichier ouvert');
            return;
        }

        const document = editor.document;
        const filePath = document.fileName;
        
        if (this.isTestFile(filePath)) {
            vscode.window.showWarningMessage('Ce fichier est déjà un fichier de test');
            return;
        }

        const testContent = await this.generateTestContent(document);
        const testFilePath = this.getTestFilePath(filePath);
        
        await this.createTestFile(testFilePath, testContent);
        
        // Ouvrir le fichier de test créé
        const testDocument = await vscode.workspace.openTextDocument(testFilePath);
        await vscode.window.showTextDocument(testDocument);
        
        vscode.window.showInformationMessage(`Test généré: ${path.basename(testFilePath)}`);
    }

    async generateMocks(): Promise<void> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const mockTypes = [
            'Database Mock',
            'HTTP Client Mock',
            'Service Mock',
            'Repository Mock',
            'Event Mock'
        ];

        const selectedType = await vscode.window.showQuickPick(mockTypes, {
            placeHolder: 'Quel type de mock voulez-vous générer ?'
        });

        if (!selectedType) {
            return;
        }

        const mockName = await vscode.window.showInputBox({
            prompt: 'Nom du mock',
            placeHolder: 'Ex: UserServiceMock'
        });

        if (!mockName) {
            return;
        }

        const mockContent = this.generateMockContent(selectedType, mockName);
        const mockFilePath = path.join(
            workspaceFolder.uri.fsPath,
            'tests',
            'Mocks',
            `${mockName}.php`
        );

        await this.createTestFile(mockFilePath, mockContent);
        
        const mockDocument = await vscode.workspace.openTextDocument(mockFilePath);
        await vscode.window.showTextDocument(mockDocument);
        
        vscode.window.showInformationMessage(`Mock généré: ${mockName}`);
    }

    async generateFixtures(): Promise<void> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const fixtureName = await vscode.window.showInputBox({
            prompt: 'Nom de la fixture',
            placeHolder: 'Ex: UserFixture'
        });

        if (!fixtureName) {
            return;
        }

        const fixtureContent = this.generateFixtureContent(fixtureName);
        const fixturePath = path.join(
            workspaceFolder.uri.fsPath,
            'tests',
            'Fixtures',
            `${fixtureName}.php`
        );

        await this.createTestFile(fixturePath, fixtureContent);
        
        const fixtureDocument = await vscode.workspace.openTextDocument(fixturePath);
        await vscode.window.showTextDocument(fixtureDocument);
        
        vscode.window.showInformationMessage(`Fixture générée: ${fixtureName}`);
    }

    private async generateTestContent(document: vscode.TextDocument): Promise<string> {
        const fileName = path.basename(document.fileName, '.php');
        const testClassName = `${fileName}Test`;
        const sourceContent = document.getText();
        
        // Analyser le contenu pour extraire les méthodes
        const methods = this.extractMethods(sourceContent);
        const className = this.extractClassName(sourceContent);
        
        let testMethods = '';
        methods.forEach(method => {
            testMethods += this.generateTestMethod(method);
        });

        return `<?php

namespace Tests\\Unit;

use PHPUnit\\Framework\\TestCase;
use App\\${className};

class ${testClassName} extends TestCase
{
    private $${className.toLowerCase()};

    protected function setUp(): void
    {
        parent::setUp();
        $this->${className.toLowerCase()} = new ${className}();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->${className.toLowerCase()});
    }
${testMethods}
}`;
    }

    private extractMethods(content: string): string[] {
        const methodRegex = /public\s+function\s+(\w+)\s*\(/g;
        const methods: string[] = [];
        let match;
        
        while ((match = methodRegex.exec(content)) !== null) {
            if (!match[1].startsWith('__')) { // Ignorer les méthodes magiques
                methods.push(match[1]);
            }
        }
        
        return methods;
    }

    private extractClassName(content: string): string {
        const classMatch = content.match(/class\s+(\w+)/);
        return classMatch ? classMatch[1] : 'UnknownClass';
    }

    private generateTestMethod(methodName: string): string {
        return `
    /**
     * Test ${methodName} method.
     */
    public function test${this.capitalize(methodName)}(): void
    {
        // Arrange
        $expected = null; // TODO: Define expected result
        
        // Act
        $result = $this->${this.extractClassName('')?.toLowerCase()}->${methodName}();
        
        // Assert
        $this->assertEquals($expected, $result);
        // TODO: Add more specific assertions
    }
`;
    }

    private generateMockContent(type: string, name: string): string {
        switch (type) {
            case 'Database Mock':
                return this.generateDatabaseMock(name);
            case 'HTTP Client Mock':
                return this.generateHttpClientMock(name);
            case 'Service Mock':
                return this.generateServiceMock(name);
            case 'Repository Mock':
                return this.generateRepositoryMock(name);
            case 'Event Mock':
                return this.generateEventMock(name);
            default:
                return this.generateGenericMock(name);
        }
    }

    private generateDatabaseMock(name: string): string {
        return `<?php

namespace Tests\\Mocks;

use Mockery;
use Mockery\\MockInterface;

class ${name}
{
    public static function create(): MockInterface
    {
        $mock = Mockery::mock('Database');
        
        $mock->shouldReceive('query')
            ->andReturn(collect([]));
            
        $mock->shouldReceive('insert')
            ->andReturn(true);
            
        $mock->shouldReceive('update')
            ->andReturn(1);
            
        $mock->shouldReceive('delete')
            ->andReturn(1);
        
        return $mock;
    }

    public static function withData(array $data): MockInterface
    {
        $mock = self::create();
        
        $mock->shouldReceive('query')
            ->andReturn(collect($data));
        
        return $mock;
    }
}`;
    }

    private generateHttpClientMock(name: string): string {
        return `<?php

namespace Tests\\Mocks;

use Mockery;
use Mockery\\MockInterface;

class ${name}
{
    public static function create(): MockInterface
    {
        $mock = Mockery::mock('HttpClient');
        
        $mock->shouldReceive('get')
            ->andReturn(['status' => 200, 'data' => []]);
            
        $mock->shouldReceive('post')
            ->andReturn(['status' => 201, 'data' => []]);
            
        $mock->shouldReceive('put')
            ->andReturn(['status' => 200, 'data' => []]);
            
        $mock->shouldReceive('delete')
            ->andReturn(['status' => 204]);
        
        return $mock;
    }

    public static function withResponse(array $response): MockInterface
    {
        $mock = Mockery::mock('HttpClient');
        
        $mock->shouldReceive('get', 'post', 'put', 'delete')
            ->andReturn($response);
        
        return $mock;
    }
}`;
    }

    private generateServiceMock(name: string): string {
        return `<?php

namespace Tests\\Mocks;

use Mockery;
use Mockery\\MockInterface;

class ${name}
{
    public static function create(): MockInterface
    {
        $mock = Mockery::mock('Service');
        
        $mock->shouldReceive('process')
            ->andReturn(true);
            
        $mock->shouldReceive('validate')
            ->andReturn(true);
            
        $mock->shouldReceive('execute')
            ->andReturn(['success' => true]);
        
        return $mock;
    }

    public static function withBehavior(callable $behavior): MockInterface
    {
        $mock = self::create();
        
        $behavior($mock);
        
        return $mock;
    }
}`;
    }

    private generateRepositoryMock(name: string): string {
        return `<?php

namespace Tests\\Mocks;

use Mockery;
use Mockery\\MockInterface;

class ${name}
{
    public static function create(): MockInterface
    {
        $mock = Mockery::mock('Repository');
        
        $mock->shouldReceive('find')
            ->andReturn(null);
            
        $mock->shouldReceive('findAll')
            ->andReturn(collect([]));
            
        $mock->shouldReceive('save')
            ->andReturn(true);
            
        $mock->shouldReceive('delete')
            ->andReturn(true);
        
        return $mock;
    }

    public static function withEntities(array $entities): MockInterface
    {
        $mock = self::create();
        
        $mock->shouldReceive('findAll')
            ->andReturn(collect($entities));
        
        return $mock;
    }
}`;
    }

    private generateEventMock(name: string): string {
        return `<?php

namespace Tests\\Mocks;

use Mockery;
use Mockery\\MockInterface;

class ${name}
{
    public static function create(): MockInterface
    {
        $mock = Mockery::mock('EventDispatcher');
        
        $mock->shouldReceive('dispatch')
            ->andReturn(true);
            
        $mock->shouldReceive('listen')
            ->andReturn(true);
            
        $mock->shouldReceive('forget')
            ->andReturn(true);
        
        return $mock;
    }

    public static function withListeners(array $listeners): MockInterface
    {
        $mock = self::create();
        
        foreach ($listeners as $event => $listener) {
            $mock->shouldReceive('listen')
                ->with($event, $listener)
                ->andReturn(true);
        }
        
        return $mock;
    }
}`;
    }

    private generateGenericMock(name: string): string {
        return `<?php

namespace Tests\\Mocks;

use Mockery;
use Mockery\\MockInterface;

class ${name}
{
    public static function create(): MockInterface
    {
        $mock = Mockery::mock('${name.replace('Mock', '')}');
        
        // TODO: Add mock expectations here
        
        return $mock;
    }
}`;
    }

    private generateFixtureContent(name: string): string {
        return `<?php

namespace Tests\\Fixtures;

class ${name}
{
    public static function create(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $overrides);
    }

    public static function createMultiple(int $count, array $overrides = []): array
    {
        $fixtures = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $fixtures[] = self::create(array_merge([
                'id' => $i,
                'name' => "Test Name {$i}",
                'email' => "test{$i}@example.com",
            ], $overrides));
        }
        
        return $fixtures;
    }

    public static function valid(): array
    {
        return self::create();
    }

    public static function invalid(): array
    {
        return self::create([
            'email' => 'invalid-email',
            'name' => '',
        ]);
    }
}`;
    }

    private getTestFilePath(sourceFilePath: string): string {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            throw new Error('Aucun workspace ouvert');
        }

        const relativePath = vscode.workspace.asRelativePath(sourceFilePath);
        const fileName = path.basename(sourceFilePath, '.php');
        const testFileName = `${fileName}Test.php`;
        
        // Déterminer le type de test basé sur le chemin du fichier source
        let testDir = 'Unit';
        if (relativePath.includes('Http') || relativePath.includes('Controller')) {
            testDir = 'Feature';
        }
        
        return path.join(
            workspaceFolder.uri.fsPath,
            'tests',
            testDir,
            testFileName
        );
    }

    private async createTestFile(filePath: string, content: string): Promise<void> {
        const dir = path.dirname(filePath);
        
        // Créer le répertoire s'il n'existe pas
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }
        
        // Écrire le fichier
        fs.writeFileSync(filePath, content);
    }

    private isTestFile(filePath: string): boolean {
        const fileName = path.basename(filePath);
        return fileName.includes('Test.php') || 
               fileName.includes('test.php') || 
               filePath.includes('/tests/') ||
               filePath.includes('\\tests\\');
    }

    private capitalize(str: string): string {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}