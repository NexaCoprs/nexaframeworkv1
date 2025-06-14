import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';

export class CICDManager {
    public async setupCICD(): Promise<void> {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const options = [
            'GitHub Actions',
            'GitLab CI/CD',
            'Jenkins Pipeline',
            'Azure DevOps',
            'Configuration complète (tous les providers)'
        ];

        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Choisissez le provider CI/CD'
        });

        if (!selected) return;

        const projectPath = workspaceFolder.uri.fsPath;

        switch (selected) {
            case options[0]:
                await this.setupGitHubActions(projectPath);
                break;
            case options[1]:
                await this.setupGitLabCI(projectPath);
                break;
            case options[2]:
                await this.setupJenkins(projectPath);
                break;
            case options[3]:
                await this.setupAzureDevOps(projectPath);
                break;
            case options[4]:
                await this.setupAllProviders(projectPath);
                break;
        }

        vscode.window.showInformationMessage('Configuration CI/CD créée avec succès!');
    }

    private async setupGitHubActions(projectPath: string): Promise<void> {
        const workflowsPath = path.join(projectPath, '.github', 'workflows');
        await this.ensureDirectoryExists(workflowsPath);

        // CI Workflow
        const ciWorkflow = this.getGitHubActionsCIContent();
        await fs.promises.writeFile(path.join(workflowsPath, 'ci.yml'), ciWorkflow);

        // CD Workflow
        const cdWorkflow = this.getGitHubActionsCDContent();
        await fs.promises.writeFile(path.join(workflowsPath, 'cd.yml'), cdWorkflow);

        // Security Workflow
        const securityWorkflow = this.getGitHubActionsSecurityContent();
        await fs.promises.writeFile(path.join(workflowsPath, 'security.yml'), securityWorkflow);

        // Dependabot
        const dependabot = this.getDependabotContent();
        await fs.promises.writeFile(path.join(projectPath, '.github', 'dependabot.yml'), dependabot);
    }

    private async setupGitLabCI(projectPath: string): Promise<void> {
        const gitlabCI = this.getGitLabCIContent();
        await fs.promises.writeFile(path.join(projectPath, '.gitlab-ci.yml'), gitlabCI);

        // Docker-in-Docker configuration
        const dockerConfig = this.getGitLabDockerConfig();
        await this.ensureDirectoryExists(path.join(projectPath, '.gitlab'));
        await fs.promises.writeFile(path.join(projectPath, '.gitlab', 'docker-config.yml'), dockerConfig);
    }

    private async setupJenkins(projectPath: string): Promise<void> {
        const jenkinsfile = this.getJenkinsfileContent();
        await fs.promises.writeFile(path.join(projectPath, 'Jenkinsfile'), jenkinsfile);

        // Jenkins configuration
        const jenkinsConfig = this.getJenkinsConfigContent();
        await this.ensureDirectoryExists(path.join(projectPath, 'jenkins'));
        await fs.promises.writeFile(path.join(projectPath, 'jenkins', 'config.yml'), jenkinsConfig);
    }

    private async setupAzureDevOps(projectPath: string): Promise<void> {
        const azurePipeline = this.getAzurePipelineContent();
        await fs.promises.writeFile(path.join(projectPath, 'azure-pipelines.yml'), azurePipeline);

        // Azure DevOps templates
        const templatesPath = path.join(projectPath, '.azure', 'templates');
        await this.ensureDirectoryExists(templatesPath);

        const buildTemplate = this.getAzureBuildTemplate();
        await fs.promises.writeFile(path.join(templatesPath, 'build.yml'), buildTemplate);

        const deployTemplate = this.getAzureDeployTemplate();
        await fs.promises.writeFile(path.join(templatesPath, 'deploy.yml'), deployTemplate);
    }

    private async setupAllProviders(projectPath: string): Promise<void> {
        await this.setupGitHubActions(projectPath);
        await this.setupGitLabCI(projectPath);
        await this.setupJenkins(projectPath);
        await this.setupAzureDevOps(projectPath);

        // Common configuration files
        await this.createCommonConfigFiles(projectPath);
    }

    private async createCommonConfigFiles(projectPath: string): Promise<void> {
        // PHPUnit configuration
        const phpunitConfig = this.getPhpUnitConfigContent();
        await fs.promises.writeFile(path.join(projectPath, 'phpunit.xml'), phpunitConfig);

        // PHP CS Fixer configuration
        const phpCSFixerConfig = this.getPhpCSFixerConfigContent();
        await fs.promises.writeFile(path.join(projectPath, '.php-cs-fixer.php'), phpCSFixerConfig);

        // PHPStan configuration
        const phpstanConfig = this.getPhpStanConfigContent();
        await fs.promises.writeFile(path.join(projectPath, 'phpstan.neon'), phpstanConfig);

        // Makefile for common tasks
        const makefile = this.getMakefileContent();
        await fs.promises.writeFile(path.join(projectPath, 'Makefile'), makefile);
    }

    private async ensureDirectoryExists(dirPath: string): Promise<void> {
        try {
            await fs.promises.access(dirPath);
        } catch {
            await fs.promises.mkdir(dirPath, { recursive: true });
        }
    }

    private getGitHubActionsCIContent(): string {
        return `name: CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: [8.1, 8.2, 8.3]
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: nexa_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      
      redis:
        image: redis:7
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: \${{ matrix.php-version }}
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, redis
        coverage: xdebug
    
    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: \${{ runner.os }}-php-\${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          \${{ runner.os }}-php-
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Copy environment file
      run: cp .env.example .env
    
    - name: Run tests
      run: vendor/bin/phpunit --coverage-clover=coverage.xml
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
        flags: unittests
        name: codecov-umbrella
    
  code-quality:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.2
        extensions: mbstring, xml, ctype, iconv, intl
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run PHP CS Fixer
      run: vendor/bin/php-cs-fixer fix --dry-run --diff
    
    - name: Run PHPStan
      run: vendor/bin/phpstan analyse
    
    - name: Run Psalm
      run: vendor/bin/psalm --output-format=github
`;
    }

    private getGitHubActionsCDContent(): string {
        return `name: CD

on:
  push:
    branches: [ main ]
    tags: [ 'v*' ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main' || startsWith(github.ref, 'refs/tags/v')
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.2
        extensions: mbstring, xml, ctype, iconv, intl
    
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
    
    - name: Build Docker image
      run: |
        docker build -t nexa-app:\${{ github.sha }} .
        docker tag nexa-app:\${{ github.sha }} nexa-app:latest
    
    - name: Login to Docker Hub
      uses: docker/login-action@v2
      with:
        username: \${{ secrets.DOCKER_USERNAME }}
        password: \${{ secrets.DOCKER_PASSWORD }}
    
    - name: Push Docker image
      run: |
        docker push nexa-app:\${{ github.sha }}
        docker push nexa-app:latest
    
    - name: Deploy to staging
      if: github.ref == 'refs/heads/main'
      run: |
        echo "Deploying to staging environment"
        # Add your staging deployment commands here
    
    - name: Deploy to production
      if: startsWith(github.ref, 'refs/tags/v')
      run: |
        echo "Deploying to production environment"
        # Add your production deployment commands here
    
    - name: Notify Slack
      uses: 8398a7/action-slack@v3
      with:
        status: \${{ job.status }}
        channel: '#deployments'
        webhook_url: \${{ secrets.SLACK_WEBHOOK }}
      if: always()
`;
    }

    private getGitHubActionsSecurityContent(): string {
        return `name: Security

on:
  schedule:
    - cron: '0 2 * * 1'  # Run every Monday at 2 AM
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  security-audit:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.2
        extensions: mbstring, xml, ctype, iconv, intl
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Run security audit
      run: composer audit
    
    - name: Run Psalm security analysis
      run: vendor/bin/psalm --taint-analysis
    
    - name: Run PHPStan security rules
      run: vendor/bin/phpstan analyse --level=max
  
  dependency-check:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Run Trivy vulnerability scanner
      uses: aquasecurity/trivy-action@master
      with:
        scan-type: 'fs'
        scan-ref: '.'
        format: 'sarif'
        output: 'trivy-results.sarif'
    
    - name: Upload Trivy scan results to GitHub Security tab
      uses: github/codeql-action/upload-sarif@v2
      with:
        sarif_file: 'trivy-results.sarif'
`;
    }

    private getDependabotContent(): string {
        return `version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
      time: "09:00"
    open-pull-requests-limit: 10
    reviewers:
      - "your-username"
    assignees:
      - "your-username"
    commit-message:
      prefix: "composer"
      include: "scope"
  
  - package-ecosystem: "docker"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
      time: "09:00"
    open-pull-requests-limit: 5
`;
    }

    private getGitLabCIContent(): string {
        return `stages:
  - build
  - test
  - security
  - deploy

variables:
  MYSQL_ROOT_PASSWORD: root
  MYSQL_DATABASE: nexa_test
  MYSQL_USER: nexa
  MYSQL_PASSWORD: nexa

services:
  - mysql:8.0
  - redis:7

before_script:
  - apt-get update -qq && apt-get install -y -qq git curl libmcrypt-dev libjpeg-dev libpng-dev libfreetype6-dev libbz2-dev libzip-dev
  - docker-php-ext-install zip pdo_mysql
  - curl -sS https://getcomposer.org/installer | php
  - php composer.phar install --no-dev --no-scripts

build:
  stage: build
  image: php:8.2-fpm
  script:
    - composer install --prefer-dist --no-progress --no-interaction
  artifacts:
    paths:
      - vendor/
    expire_in: 1 hour
  cache:
    paths:
      - vendor/

test:php8.1:
  stage: test
  image: php:8.1-fpm
  script:
    - cp .env.example .env
    - vendor/bin/phpunit --coverage-text --colors=never
  coverage: '/^\\s*Lines:\\s*\\d+.\\d+\\%/'

test:php8.2:
  stage: test
  image: php:8.2-fpm
  script:
    - cp .env.example .env
    - vendor/bin/phpunit --coverage-text --colors=never
  coverage: '/^\\s*Lines:\\s*\\d+.\\d+\\%/'

code_quality:
  stage: test
  image: php:8.2-fpm
  script:
    - vendor/bin/php-cs-fixer fix --dry-run --diff
    - vendor/bin/phpstan analyse
  allow_failure: true

security_audit:
  stage: security
  image: php:8.2-fpm
  script:
    - composer audit
  allow_failure: true

deploy_staging:
  stage: deploy
  image: docker:latest
  services:
    - docker:dind
  script:
    - docker build -t nexa-app:staging .
    - docker tag nexa-app:staging $CI_REGISTRY_IMAGE:staging
    - docker push $CI_REGISTRY_IMAGE:staging
  only:
    - develop
  environment:
    name: staging
    url: https://staging.example.com

deploy_production:
  stage: deploy
  image: docker:latest
  services:
    - docker:dind
  script:
    - docker build -t nexa-app:production .
    - docker tag nexa-app:production $CI_REGISTRY_IMAGE:latest
    - docker push $CI_REGISTRY_IMAGE:latest
  only:
    - main
  environment:
    name: production
    url: https://example.com
  when: manual
`;
    }

    private getGitLabDockerConfig(): string {
        return `image: docker:latest

services:
  - docker:dind

variables:
  DOCKER_DRIVER: overlay2
  DOCKER_TLS_CERTDIR: "/certs"

before_script:
  - docker login -u $CI_REGISTRY_USER -p $CI_REGISTRY_PASSWORD $CI_REGISTRY
`;
    }

    private getJenkinsfileContent(): string {
        return `pipeline {
    agent any
    
    environment {
        COMPOSER_CACHE_DIR = '/tmp/composer-cache'
        PHP_VERSION = '8.2'
    }
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Build') {
            steps {
                sh 'composer install --prefer-dist --no-progress --no-interaction'
            }
        }
        
        stage('Test') {
            parallel {
                stage('Unit Tests') {
                    steps {
                        sh 'cp .env.example .env'
                        sh 'vendor/bin/phpunit --log-junit tests/results/junit.xml'
                    }
                    post {
                        always {
                            junit 'tests/results/junit.xml'
                        }
                    }
                }
                
                stage('Code Quality') {
                    steps {
                        sh 'vendor/bin/php-cs-fixer fix --dry-run --diff'
                        sh 'vendor/bin/phpstan analyse --error-format=checkstyle > phpstan-report.xml || true'
                    }
                    post {
                        always {
                            recordIssues enabledForFailure: true, tools: [checkStyle(pattern: 'phpstan-report.xml')]
                        }
                    }
                }
            }
        }
        
        stage('Security Audit') {
            steps {
                sh 'composer audit || true'
            }
        }
        
        stage('Build Docker Image') {
            when {
                anyOf {
                    branch 'main'
                    branch 'develop'
                }
            }
            steps {
                script {
                    def image = docker.build("nexa-app:\${env.BUILD_ID}")
                    docker.withRegistry('https://registry.hub.docker.com', 'docker-hub-credentials') {
                        image.push()
                        image.push('latest')
                    }
                }
            }
        }
        
        stage('Deploy') {
            when {
                branch 'main'
            }
            steps {
                script {
                    // Add deployment logic here
                    echo 'Deploying to production...'
                }
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        success {
            slackSend channel: '#ci-cd', 
                     color: 'good', 
                     message: "✅ Build succeeded: \${env.JOB_NAME} - \${env.BUILD_NUMBER}"
        }
        failure {
            slackSend channel: '#ci-cd', 
                     color: 'danger', 
                     message: "❌ Build failed: \${env.JOB_NAME} - \${env.BUILD_NUMBER}"
        }
    }
}
`;
    }

    private getJenkinsConfigContent(): string {
        return `# Jenkins Configuration for Nexa Framework

## Required Plugins
- Docker Pipeline
- PHP Plugin
- Slack Notification
- JUnit Plugin
- Checkstyle Plugin

## Environment Variables
PHP_VERSION=8.2
COMPOSER_CACHE_DIR=/tmp/composer-cache
DOCKER_REGISTRY=registry.hub.docker.com

## Credentials
- docker-hub-credentials: Docker Hub login
- slack-token: Slack integration token
`;
    }

    private getAzurePipelineContent(): string {
        return `trigger:
  branches:
    include:
      - main
      - develop
  tags:
    include:
      - v*

pool:
  vmImage: 'ubuntu-latest'

variables:
  phpVersion: '8.2'
  composerCacheFolder: $(Pipeline.Workspace)/.composer

stages:
- stage: Build
  displayName: 'Build and Test'
  jobs:
  - job: Test
    displayName: 'Run Tests'
    strategy:
      matrix:
        PHP81:
          phpVersion: '8.1'
        PHP82:
          phpVersion: '8.2'
        PHP83:
          phpVersion: '8.3'
    
    services:
      mysql: mysql:8.0
      redis: redis:7
    
    steps:
    - task: UsePhpVersion@0
      inputs:
        versionSpec: $(phpVersion)
      displayName: 'Use PHP $(phpVersion)'
    
    - task: Cache@2
      inputs:
        key: 'composer | "$(Agent.OS)" | composer.lock'
        restoreKeys: |
          composer | "$(Agent.OS)"
        path: $(composerCacheFolder)
      displayName: 'Cache Composer packages'
    
    - script: |
        composer install --prefer-dist --no-progress --no-interaction
      displayName: 'Install dependencies'
    
    - script: |
        cp .env.example .env
        vendor/bin/phpunit --log-junit $(Agent.TempDirectory)/junit.xml --coverage-cobertura $(Agent.TempDirectory)/coverage.xml
      displayName: 'Run tests'
    
    - task: PublishTestResults@2
      inputs:
        testResultsFormat: 'JUnit'
        testResultsFiles: '$(Agent.TempDirectory)/junit.xml'
      displayName: 'Publish test results'
    
    - task: PublishCodeCoverageResults@1
      inputs:
        codeCoverageTool: 'Cobertura'
        summaryFileLocation: '$(Agent.TempDirectory)/coverage.xml'
      displayName: 'Publish coverage results'

- stage: CodeQuality
  displayName: 'Code Quality'
  dependsOn: Build
  jobs:
  - job: Quality
    displayName: 'Code Quality Checks'
    steps:
    - task: UsePhpVersion@0
      inputs:
        versionSpec: '8.2'
    
    - script: composer install --prefer-dist --no-progress --no-interaction
      displayName: 'Install dependencies'
    
    - script: vendor/bin/php-cs-fixer fix --dry-run --diff
      displayName: 'PHP CS Fixer'
    
    - script: vendor/bin/phpstan analyse
      displayName: 'PHPStan Analysis'

- stage: Deploy
  displayName: 'Deploy'
  dependsOn: 
    - Build
    - CodeQuality
  condition: and(succeeded(), eq(variables['Build.SourceBranch'], 'refs/heads/main'))
  jobs:
  - deployment: DeployToProduction
    displayName: 'Deploy to Production'
    environment: 'production'
    strategy:
      runOnce:
        deploy:
          steps:
          - task: Docker@2
            displayName: 'Build and push Docker image'
            inputs:
              command: 'buildAndPush'
              repository: 'nexa-app'
              dockerfile: 'Dockerfile'
              tags: |
                $(Build.BuildId)
                latest
`;
    }

    private getAzureBuildTemplate(): string {
        return `parameters:
  - name: phpVersion
    type: string
    default: '8.2'
  - name: runTests
    type: boolean
    default: true

steps:
- task: UsePhpVersion@0
  inputs:
    versionSpec: \${{ parameters.phpVersion }}
      displayName: 'Use PHP \${{ parameters.phpVersion }}'

- task: Cache@2
  inputs:
    key: 'composer | "$(Agent.OS)" | composer.lock'
    restoreKeys: |
      composer | "$(Agent.OS)"
    path: $(Pipeline.Workspace)/.composer
  displayName: 'Cache Composer packages'

- script: |
    composer install --prefer-dist --no-progress --no-interaction
  displayName: 'Install dependencies'

- \${{ if eq(parameters.runTests, true) }}:
  - script: |
      cp .env.example .env
      vendor/bin/phpunit
    displayName: 'Run tests'
`;
    }

    private getAzureDeployTemplate(): string {
        return `parameters:
  - name: environment
    type: string
  - name: dockerRegistry
    type: string
    default: 'dockerhub'

steps:
- task: Docker@2
  displayName: 'Build Docker image'
  inputs:
    command: 'build'
    repository: 'nexa-app'
    dockerfile: 'Dockerfile'
    tags: |
      $(Build.BuildId)
      \${{ parameters.environment }}

- task: Docker@2
  displayName: 'Push Docker image'
  inputs:
    command: 'push'
    repository: 'nexa-app'
    containerRegistry: \${{ parameters.dockerRegistry }}
    tags: |
      $(Build.BuildId)
      \${{ parameters.environment }}

- script: |
    echo "Deploying to \${{ parameters.environment }}"
    # Add deployment commands here
  displayName: 'Deploy to \${{ parameters.environment }}'
`;
    }

    private getPhpUnitConfigContent(): string {
        return `<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.0/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         executionOrder="depends,defects"
         failOnRisky="true"
         failOnWarning="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">./kernel</directory>
            <directory suffix=".php">./workspace</directory>
        </include>
        <exclude>
            <directory>./vendor</directory>
            <directory>./tests</directory>
        </exclude>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
`;
    }

    private getPhpCSFixerConfigContent(): string {
        return `<?php

$finder = PhpCsFixer\\Finder::create()
    ->in([
        __DIR__ . '/kernel',
        __DIR__ . '/workspace',
        __DIR__ . '/tests',
    ])
    ->exclude([
        'vendor',
        'storage',
        'bootstrap/cache',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\\Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
    ])
    ->setFinder($finder);
`;
    }

    private getPhpStanConfigContent(): string {
        return `parameters:
    level: 8
    paths:
        - kernel
        - workspace
        - tests
    excludePaths:
        - vendor
        - storage
        - bootstrap/cache
    ignoreErrors:
        - '#Call to an undefined method#'
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
    reportUnmatchedIgnoredErrors: false
`;
    }

    private getMakefileContent(): string {
        return `.PHONY: help install test lint fix security build deploy clean

# Default target
help:
	@echo "Available commands:"
	@echo "  install    Install dependencies"
	@echo "  test       Run tests"
	@echo "  lint       Run code quality checks"
	@echo "  fix        Fix code style issues"
	@echo "  security   Run security audit"
	@echo "  build      Build Docker image"
	@echo "  deploy     Deploy application"
	@echo "  clean      Clean cache and temporary files"

# Install dependencies
install:
	composer install

# Run tests
test:
	vendor/bin/phpunit

# Run code quality checks
lint:
	vendor/bin/php-cs-fixer fix --dry-run --diff
	vendor/bin/phpstan analyse

# Fix code style issues
fix:
	vendor/bin/php-cs-fixer fix

# Run security audit
security:
	composer audit

# Build Docker image
build:
	docker build -t nexa-app .

# Deploy application
deploy:
	@echo "Deploying application..."
	# Add deployment commands here

# Clean cache and temporary files
clean:
	rm -rf storage/cache/*
	rm -rf storage/logs/*
	rm -rf vendor
	composer install
`;
    }
}