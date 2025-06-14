"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.DockerManager = void 0;
const vscode = require("vscode");
const fs = require("fs");
const path = require("path");
class DockerManager {
    async setupDocker() {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }
        const options = [
            'Configuration complète (PHP + MySQL + Redis)',
            'Configuration simple (PHP seulement)',
            'Configuration microservices',
            'Configuration avec PostgreSQL'
        ];
        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Choisissez le type de configuration Docker'
        });
        if (!selected)
            return;
        const projectPath = workspaceFolder.uri.fsPath;
        switch (selected) {
            case options[0]:
                await this.createCompleteDockerSetup(projectPath);
                break;
            case options[1]:
                await this.createSimpleDockerSetup(projectPath);
                break;
            case options[2]:
                await this.createMicroservicesDockerSetup(projectPath);
                break;
            case options[3]:
                await this.createPostgreSQLDockerSetup(projectPath);
                break;
        }
        vscode.window.showInformationMessage('Configuration Docker créée avec succès!');
    }
    async createCompleteDockerSetup(projectPath) {
        // Dockerfile
        const dockerfile = this.getDockerfileContent();
        await fs.promises.writeFile(path.join(projectPath, 'Dockerfile'), dockerfile);
        // docker-compose.yml
        const dockerCompose = this.getCompleteDockerComposeContent();
        await fs.promises.writeFile(path.join(projectPath, 'docker-compose.yml'), dockerCompose);
        // .dockerignore
        const dockerignore = this.getDockerignoreContent();
        await fs.promises.writeFile(path.join(projectPath, '.dockerignore'), dockerignore);
        // nginx.conf
        const nginxConf = this.getNginxConfigContent();
        await this.ensureDirectoryExists(path.join(projectPath, 'docker', 'nginx'));
        await fs.promises.writeFile(path.join(projectPath, 'docker', 'nginx', 'nginx.conf'), nginxConf);
        // php.ini
        const phpIni = this.getPhpIniContent();
        await this.ensureDirectoryExists(path.join(projectPath, 'docker', 'php'));
        await fs.promises.writeFile(path.join(projectPath, 'docker', 'php', 'php.ini'), phpIni);
    }
    async createSimpleDockerSetup(projectPath) {
        const dockerfile = this.getSimpleDockerfileContent();
        await fs.promises.writeFile(path.join(projectPath, 'Dockerfile'), dockerfile);
        const dockerCompose = this.getSimpleDockerComposeContent();
        await fs.promises.writeFile(path.join(projectPath, 'docker-compose.yml'), dockerCompose);
        const dockerignore = this.getDockerignoreContent();
        await fs.promises.writeFile(path.join(projectPath, '.dockerignore'), dockerignore);
    }
    async createMicroservicesDockerSetup(projectPath) {
        const dockerfile = this.getMicroserviceDockerfileContent();
        await fs.promises.writeFile(path.join(projectPath, 'Dockerfile'), dockerfile);
        const dockerCompose = this.getMicroservicesDockerComposeContent();
        await fs.promises.writeFile(path.join(projectPath, 'docker-compose.yml'), dockerCompose);
        const dockerignore = this.getDockerignoreContent();
        await fs.promises.writeFile(path.join(projectPath, '.dockerignore'), dockerignore);
        // Kubernetes manifests
        await this.createKubernetesManifests(projectPath);
    }
    async createPostgreSQLDockerSetup(projectPath) {
        const dockerfile = this.getDockerfileContent();
        await fs.promises.writeFile(path.join(projectPath, 'Dockerfile'), dockerfile);
        const dockerCompose = this.getPostgreSQLDockerComposeContent();
        await fs.promises.writeFile(path.join(projectPath, 'docker-compose.yml'), dockerCompose);
        const dockerignore = this.getDockerignoreContent();
        await fs.promises.writeFile(path.join(projectPath, '.dockerignore'), dockerignore);
    }
    async createKubernetesManifests(projectPath) {
        const k8sPath = path.join(projectPath, 'k8s');
        await this.ensureDirectoryExists(k8sPath);
        // Deployment
        const deployment = this.getKubernetesDeploymentContent();
        await fs.promises.writeFile(path.join(k8sPath, 'deployment.yaml'), deployment);
        // Service
        const service = this.getKubernetesServiceContent();
        await fs.promises.writeFile(path.join(k8sPath, 'service.yaml'), service);
        // ConfigMap
        const configMap = this.getKubernetesConfigMapContent();
        await fs.promises.writeFile(path.join(k8sPath, 'configmap.yaml'), configMap);
    }
    async ensureDirectoryExists(dirPath) {
        try {
            await fs.promises.access(dirPath);
        }
        catch {
            await fs.promises.mkdir(dirPath, { recursive: true });
        }
    }
    getDockerfileContent() {
        return `FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \\
    git \\
    curl \\
    libpng-dev \\
    libonig-dev \\
    libxml2-dev \\
    zip \\
    unzip \\
    libzip-dev \\
    libpq-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Change current user to www
USER www-data

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
`;
    }
    getSimpleDockerfileContent() {
        return `FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \\
    git \\
    curl \\
    libpng-dev \\
    libonig-dev \\
    libxml2-dev \\
    zip \\
    unzip \\
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port 8000
EXPOSE 8000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
`;
    }
    getMicroserviceDockerfileContent() {
        return `FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \\
    git \\
    curl \\
    libpng-dev \\
    oniguruma-dev \\
    libxml2-dev \\
    zip \\
    unzip \\
    libzip-dev \\
    postgresql-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN apk add --no-cache redis && \\
    pecl install redis && \\
    docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy application code
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

# Create non-root user
RUN addgroup -g 1001 -S nexa && \\
    adduser -S nexa -u 1001

# Change ownership
RUN chown -R nexa:nexa /app

# Switch to non-root user
USER nexa

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \\
    CMD curl -f http://localhost:8080/health || exit 1

# Expose port
EXPOSE 8080

# Start application
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
`;
    }
    getCompleteDockerComposeContent() {
        return `version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: nexa-app
    container_name: nexa-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - nexa-network
    depends_on:
      - db
      - redis

  webserver:
    image: nginx:alpine
    container_name: nexa-webserver
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - nexa-network
    depends_on:
      - app

  db:
    image: mysql:8.0
    container_name: nexa-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: nexa
      MYSQL_ROOT_PASSWORD: root
      MYSQL_PASSWORD: nexa
      MYSQL_USER: nexa
    volumes:
      - dbdata:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - nexa-network

  redis:
    image: redis:7-alpine
    container_name: nexa-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - nexa-network

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: nexa-phpmyadmin
    restart: unless-stopped
    environment:
      PMA_HOST: db
      PMA_PORT: 3306
      PMA_USER: root
      PMA_PASSWORD: root
    ports:
      - "8080:80"
    networks:
      - nexa-network
    depends_on:
      - db

volumes:
  dbdata:
    driver: local

networks:
  nexa-network:
    driver: bridge
`;
    }
    getSimpleDockerComposeContent() {
        return `version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: nexa-app
    container_name: nexa-app
    restart: unless-stopped
    ports:
      - "8000:8000"
    volumes:
      - ./:/var/www
    working_dir: /var/www
`;
    }
    getMicroservicesDockerComposeContent() {
        return `version: '3.8'

services:
  nexa-service:
    build:
      context: .
      dockerfile: Dockerfile
    image: nexa-microservice
    container_name: nexa-microservice
    restart: unless-stopped
    ports:
      - "8080:8080"
    environment:
      - SERVICE_NAME=nexa-microservice
      - SERVICE_PORT=8080
      - REGISTRY_URL=http://consul:8500
    networks:
      - microservices
    depends_on:
      - consul
      - redis

  consul:
    image: consul:latest
    container_name: consul
    restart: unless-stopped
    ports:
      - "8500:8500"
    command: agent -server -ui -node=server-1 -bootstrap-expect=1 -client=0.0.0.0
    networks:
      - microservices

  redis:
    image: redis:7-alpine
    container_name: redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - microservices

  prometheus:
    image: prom/prometheus
    container_name: prometheus
    restart: unless-stopped
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
    networks:
      - microservices

  grafana:
    image: grafana/grafana
    container_name: grafana
    restart: unless-stopped
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
    networks:
      - microservices

networks:
  microservices:
    driver: bridge
`;
    }
    getPostgreSQLDockerComposeContent() {
        return `version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: nexa-app
    container_name: nexa-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - nexa-network
    depends_on:
      - db
      - redis

  webserver:
    image: nginx:alpine
    container_name: nexa-webserver
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - nexa-network
    depends_on:
      - app

  db:
    image: postgres:15
    container_name: nexa-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: nexa
      POSTGRES_USER: nexa
      POSTGRES_PASSWORD: nexa
    volumes:
      - dbdata:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    networks:
      - nexa-network

  redis:
    image: redis:7-alpine
    container_name: nexa-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - nexa-network

  pgadmin:
    image: dpage/pgadmin4
    container_name: nexa-pgadmin
    restart: unless-stopped
    environment:
      PGADMIN_DEFAULT_EMAIL: admin@nexa.com
      PGADMIN_DEFAULT_PASSWORD: admin
    ports:
      - "8080:80"
    networks:
      - nexa-network
    depends_on:
      - db

volumes:
  dbdata:
    driver: local

networks:
  nexa-network:
    driver: bridge
`;
    }
    getDockerignoreContent() {
        return `node_modules
npm-debug.log
vendor
composer.lock
.git
.gitignore
README.md
.env
.env.example
storage/logs/*
storage/cache/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
tests
.phpunit.result.cache
Dockerfile
docker-compose.yml
.dockerignore
`;
    }
    getNginxConfigContent() {
        return `server {
    listen 80;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/public;
    location ~ \\.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }
}
`;
    }
    getPhpIniContent() {
        return `upload_max_filesize=40M
post_max_size=40M
memory_limit=256M
max_execution_time=300
max_input_vars=3000
date.timezone=UTC
`;
    }
    getKubernetesDeploymentContent() {
        return `apiVersion: apps/v1
kind: Deployment
metadata:
  name: nexa-microservice
  labels:
    app: nexa-microservice
spec:
  replicas: 3
  selector:
    matchLabels:
      app: nexa-microservice
  template:
    metadata:
      labels:
        app: nexa-microservice
    spec:
      containers:
      - name: nexa-microservice
        image: nexa-microservice:latest
        ports:
        - containerPort: 8080
        env:
        - name: SERVICE_NAME
          value: "nexa-microservice"
        - name: SERVICE_PORT
          value: "8080"
        resources:
          requests:
            memory: "64Mi"
            cpu: "250m"
          limits:
            memory: "128Mi"
            cpu: "500m"
        livenessProbe:
          httpGet:
            path: /health
            port: 8080
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /health
            port: 8080
          initialDelaySeconds: 5
          periodSeconds: 5
`;
    }
    getKubernetesServiceContent() {
        return `apiVersion: v1
kind: Service
metadata:
  name: nexa-microservice-service
spec:
  selector:
    app: nexa-microservice
  ports:
    - protocol: TCP
      port: 80
      targetPort: 8080
  type: LoadBalancer
`;
    }
    getKubernetesConfigMapContent() {
        return `apiVersion: v1
kind: ConfigMap
metadata:
  name: nexa-microservice-config
data:
  SERVICE_NAME: "nexa-microservice"
  SERVICE_PORT: "8080"
  LOG_LEVEL: "info"
`;
    }
}
exports.DockerManager = DockerManager;
//# sourceMappingURL=dockerManager.js.map