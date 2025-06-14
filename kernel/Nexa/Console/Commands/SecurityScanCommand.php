<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;

class SecurityScanCommand extends Command
{
    protected function configure()
    {
        $this->setName('security:scan')
             ->setDescription('Effectue un audit de sécurité complet de l\'application')
             ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Affichage détaillé des vulnérabilités')
             ->addOption('fix', 'f', InputOption::VALUE_NONE, 'Tenter de corriger automatiquement les problèmes')
             ->addOption('export', 'e', InputOption::VALUE_OPTIONAL, 'Exporter le rapport vers un fichier');
    }

    protected function handle()
    {
        $this->info('🔒 Audit de Sécurité Nexa Framework');
        $this->line('');
        
        $detailed = $this->input->getOption('detailed');
        $autoFix = $this->input->getOption('fix');
        $exportFile = $this->input->getOption('export');
        
        // Barre de progression pour l'audit
        $progressBar = new ProgressBar($this->output, 6);
        $progressBar->setFormat('verbose');
        $progressBar->start();
        
        $this->line('Vérification des permissions de fichiers...');
        $filePermissions = $this->checkFilePermissions();
        $progressBar->advance();
        
        $this->line('Analyse des dépendances...');
        $dependencies = $this->checkDependencies();
        $progressBar->advance();
        
        $this->line('Vérification des configurations...');
        $configurations = $this->checkConfigurations();
        $progressBar->advance();
        
        $this->line('Scan des vulnérabilités communes...');
        $vulnerabilities = $this->scanVulnerabilities();
        $progressBar->advance();
        
        $this->line('Vérification des headers de sécurité...');
        $headers = $this->checkSecurityHeaders();
        $progressBar->advance();
        
        $this->line('Analyse des logs de sécurité...');
        $logs = $this->analyzeLogs();
        $progressBar->advance();
        
        $progressBar->finish();
        $this->line('');
        $this->line('');
        
        // Compilation des résultats
        $results = [
            'file_permissions' => $filePermissions,
            'dependencies' => $dependencies,
            'configurations' => $configurations,
            'vulnerabilities' => $vulnerabilities,
            'headers' => $headers,
            'logs' => $logs
        ];
        
        $this->displaySecuritySummary($results);
        
        if ($detailed) {
            $this->displayDetailedFindings($results);
        }
        
        if ($autoFix) {
            $this->applySecurityFixes($results);
        }
        
        $this->displaySecurityRecommendations($results);
        
        if ($exportFile) {
            $this->exportSecurityReport($results, $exportFile);
        }
    }
    
    private function checkFilePermissions(): array
    {
        $issues = [];
        
        // Vérification des permissions critiques
        $criticalPaths = [
            'storage/' => '755',
            'storage/logs/' => '755',
            'storage/cache/' => '755',
            'storage/framework/' => '755',
            'storage/framework/cache/' => '755',
            'storage/framework/sessions/' => '755',
            'storage/framework/views/' => '755',
            '.env' => '600',
            'composer.json' => '644',
            'composer.lock' => '644'
        ];
        
        foreach ($criticalPaths as $path => $expectedPerm) {
            $fullPath = base_path($path);
            
            if (file_exists($fullPath)) {
                $currentPerm = substr(sprintf('%o', fileperms($fullPath)), -3);
                
                if ($currentPerm !== $expectedPerm) {
                    $issues[] = [
                        'type' => 'permission',
                        'file' => $path,
                        'current' => $currentPerm,
                        'expected' => $expectedPerm,
                        'severity' => $path === '.env' ? 'critical' : 'medium',
                        'description' => "Permissions incorrectes pour {$path}: {$currentPerm} au lieu de {$expectedPerm}"
                    ];
                }
            } else {
                $issues[] = [
                    'type' => 'missing_file',
                    'file' => $path,
                    'severity' => 'high',
                    'description' => "Fichier ou dossier manquant: {$path}"
                ];
            }
        }
        
        // Vérification des fichiers sensibles
        $sensitiveFiles = ['.env', 'composer.json', 'composer.lock'];
        foreach ($sensitiveFiles as $file) {
            $fullPath = base_path($file);
            if (file_exists($fullPath) && is_readable($fullPath)) {
                $perms = fileperms($fullPath);
                if ($perms & 0x0004) { // World readable
                    $issues[] = [
                        'type' => 'world_readable',
                        'file' => $file,
                        'severity' => 'critical',
                        'description' => "Fichier sensible lisible par tous: {$file}"
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    private function checkDependencies(): array
    {
        $issues = [];
        
        // Vérification des vulnérabilités avec composer audit
        $composerLockPath = base_path('composer.lock');
        if (!file_exists($composerLockPath)) {
            $issues[] = [
                'package' => 'composer.lock',
                'version' => 'N/A',
                'vulnerability' => 'MISSING_LOCK_FILE',
                'severity' => 'high',
                'description' => 'Fichier composer.lock manquant - impossible de vérifier les vulnérabilités'
            ];
            return $issues;
        }
        
        // Exécuter composer audit
        $output = [];
        $returnCode = 0;
        exec('composer audit --format=json 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            $auditResult = implode('\n', $output);
            $auditData = json_decode($auditResult, true);
            
            if ($auditData && isset($auditData['advisories'])) {
                foreach ($auditData['advisories'] as $advisory) {
                    $issues[] = [
                        'package' => $advisory['packageName'] ?? 'Unknown',
                        'version' => $advisory['affectedVersions'] ?? 'Unknown',
                        'vulnerability' => $advisory['cve'] ?? $advisory['title'] ?? 'Unknown CVE',
                        'severity' => $this->mapSeverity($advisory['severity'] ?? 'medium'),
                        'description' => $advisory['title'] ?? 'Vulnérabilité détectée'
                    ];
                }
            }
        } else {
            // Fallback: vérification manuelle des packages connus vulnérables
            $knownVulnerablePackages = [
                'symfony/http-kernel' => ['< 4.4.50', '< 5.4.20', '< 6.0.20'],
                'laravel/framework' => ['< 8.83.27', '< 9.52.7'],
                'monolog/monolog' => ['< 2.8.0'],
                'guzzlehttp/guzzle' => ['< 7.4.5']
            ];
            
            $composerData = json_decode(file_get_contents($composerLockPath), true);
            if ($composerData && isset($composerData['packages'])) {
                foreach ($composerData['packages'] as $package) {
                    $packageName = $package['name'];
                    $packageVersion = $package['version'];
                    
                    if (isset($knownVulnerablePackages[$packageName])) {
                        foreach ($knownVulnerablePackages[$packageName] as $vulnerableVersion) {
                            if (version_compare($packageVersion, str_replace(['<', ' '], '', $vulnerableVersion), '<')) {
                                $issues[] = [
                                    'package' => $packageName,
                                    'version' => $packageVersion,
                                    'vulnerability' => 'KNOWN_VULNERABILITY',
                                    'severity' => 'high',
                                    'description' => "Version vulnérable détectée: {$packageName} {$packageVersion}"
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        // Vérification des packages obsolètes
        exec('composer outdated --format=json 2>&1', $outdatedOutput, $outdatedReturnCode);
        if ($outdatedReturnCode === 0) {
            $outdatedResult = implode('\n', $outdatedOutput);
            $outdatedData = json_decode($outdatedResult, true);
            
            if ($outdatedData && isset($outdatedData['installed'])) {
                foreach ($outdatedData['installed'] as $package) {
                    if (isset($package['latest-status']) && $package['latest-status'] === 'semver-safe-update') {
                        $issues[] = [
                            'package' => $package['name'],
                            'version' => $package['version'],
                            'vulnerability' => 'OUTDATED_PACKAGE',
                            'severity' => 'medium',
                            'description' => "Package obsolète: {$package['name']} {$package['version']} -> {$package['latest']}"
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    private function mapSeverity(string $severity): string
    {
        return match(strtolower($severity)) {
            'critical' => 'critical',
            'high' => 'high',
            'medium', 'moderate' => 'medium',
            'low' => 'low',
            default => 'medium'
        };
    }
    
    private function checkConfigurations(): array
    {
        $issues = [];
        
        // Charger les variables d'environnement depuis .env
        $envPath = base_path('.env');
        $envVars = [];
        
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $lines = explode("\n", $envContent);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && !str_starts_with($line, '#') && str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $envVars[trim($key)] = trim($value, '"\' ');
                }
            }
        }
        
        // Configurations de sécurité critiques à vérifier
        $securityChecks = [
            'APP_DEBUG' => [
                'recommended' => 'false',
                'severity' => 'critical',
                'description' => 'Le mode debug doit être désactivé en production'
            ],
            'APP_ENV' => [
                'recommended' => 'production',
                'severity' => 'high',
                'description' => 'L\'environnement doit être configuré en production'
            ],
            'APP_KEY' => [
                'required' => true,
                'min_length' => 32,
                'severity' => 'critical',
                'description' => 'Clé d\'application manquante ou trop courte'
            ],
            'DB_PASSWORD' => [
                'required' => true,
                'min_length' => 8,
                'severity' => 'high',
                'description' => 'Mot de passe de base de données faible ou manquant'
            ],
            'SESSION_DRIVER' => [
                'recommended' => ['redis', 'database', 'memcached'],
                'severity' => 'medium',
                'description' => 'Driver de session non sécurisé (éviter file)'
            ],
            'CACHE_DRIVER' => [
                'recommended' => ['redis', 'memcached'],
                'severity' => 'low',
                'description' => 'Driver de cache non optimisé'
            ]
        ];
        
        foreach ($securityChecks as $configKey => $check) {
            $currentValue = $envVars[$configKey] ?? null;
            
            // Vérifier si la variable est requise
            if (isset($check['required']) && $check['required'] && empty($currentValue)) {
                $issues[] = [
                    'type' => 'missing_config',
                    'config' => $configKey,
                    'current' => 'null',
                    'recommended' => 'required',
                    'severity' => $check['severity'],
                    'description' => $check['description']
                ];
                continue;
            }
            
            // Vérifier la longueur minimale
            if (isset($check['min_length']) && !empty($currentValue) && strlen($currentValue) < $check['min_length']) {
                $issues[] = [
                    'type' => 'weak_config',
                    'config' => $configKey,
                    'current' => 'too_short',
                    'recommended' => "min {$check['min_length']} characters",
                    'severity' => $check['severity'],
                    'description' => $check['description']
                ];
            }
            
            // Vérifier les valeurs recommandées
            if (isset($check['recommended'])) {
                $recommended = is_array($check['recommended']) ? $check['recommended'] : [$check['recommended']];
                
                if (!empty($currentValue) && !in_array($currentValue, $recommended)) {
                    $issues[] = [
                        'type' => 'insecure_config',
                        'config' => $configKey,
                        'current' => $currentValue,
                        'recommended' => implode(' or ', $recommended),
                        'severity' => $check['severity'],
                        'description' => $check['description']
                    ];
                }
            }
        }
        
        // Vérifier les configurations PHP dangereuses
        $dangerousPhpSettings = [
            'display_errors' => 'Off',
            'expose_php' => 'Off',
            'allow_url_fopen' => 'Off',
            'allow_url_include' => 'Off'
        ];
        
        foreach ($dangerousPhpSettings as $setting => $recommendedValue) {
            $currentValue = ini_get($setting);
            if ($currentValue !== $recommendedValue) {
                $issues[] = [
                    'type' => 'php_config',
                    'config' => $setting,
                    'current' => $currentValue ?: 'not_set',
                    'recommended' => $recommendedValue,
                    'severity' => 'medium',
                    'description' => "Configuration PHP dangereuse: {$setting}"
                ];
            }
        }
        
        return $issues;
    }
    
    private function scanVulnerabilities(): array
    {
        $vulnerabilities = [];
        
        // Patterns de vulnérabilités à détecter
        $vulnerabilityPatterns = [
            'sql_injection' => [
                'patterns' => [
                    '/\$[a-zA-Z_][a-zA-Z0-9_]*\s*\.\s*["\']\s*SELECT\s+.*\s*\$[a-zA-Z_][a-zA-Z0-9_]*/i',
                    '/query\s*\(\s*["\'].*\$[a-zA-Z_][a-zA-Z0-9_]*.*["\']\s*\)/i',
                    '/mysql_query\s*\(\s*["\'].*\$[a-zA-Z_][a-zA-Z0-9_]*.*["\']\s*\)/i',
                    '/\$_(?:GET|POST|REQUEST)\[[^\]]+\].*(?:SELECT|INSERT|UPDATE|DELETE)/i'
                ],
                'severity' => 'critical',
                'description' => 'Possible injection SQL détectée'
            ],
            'xss' => [
                'patterns' => [
                    '/echo\s+\$_(?:GET|POST|REQUEST)\[[^\]]+\]/i',
                    '/print\s+\$_(?:GET|POST|REQUEST)\[[^\]]+\]/i',
                    '/<\?=\s*\$_(?:GET|POST|REQUEST)\[[^\]]+\]/i',
                    '/innerHTML\s*=\s*["\'].*\$[a-zA-Z_][a-zA-Z0-9_]*.*["\']/',
                    '/document\.write\s*\(.*\$[a-zA-Z_][a-zA-Z0-9_]*.*/'
                ],
                'severity' => 'high',
                'description' => 'Possible vulnérabilité XSS détectée'
            ],
            'file_inclusion' => [
                'patterns' => [
                    '/(?:include|require)(?:_once)?\s*\(\s*\$_(?:GET|POST|REQUEST)\[[^\]]+\]/i',
                    '/file_get_contents\s*\(\s*\$_(?:GET|POST|REQUEST)\[[^\]]+\]/i',
                    '/fopen\s*\(\s*\$_(?:GET|POST|REQUEST)\[[^\]]+\]/i'
                ],
                'severity' => 'critical',
                'description' => 'Possible inclusion de fichier non sécurisée'
            ],
            'command_injection' => [
                'patterns' => [
                    '/(?:exec|system|shell_exec|passthru)\s*\(.*\$_(?:GET|POST|REQUEST)\[[^\]]+\]/i',
                    '/`.*\$_(?:GET|POST|REQUEST)\[[^\]]+\].*`/i',
                    '/proc_open\s*\(.*\$_(?:GET|POST|REQUEST)\[[^\]]+\]/i'
                ],
                'severity' => 'critical',
                'description' => 'Possible injection de commande détectée'
            ],
            'weak_crypto' => [
                'patterns' => [
                    '/md5\s*\(/i',
                    '/sha1\s*\(/i',
                    '/base64_encode\s*\(.*password/i',
                    '/crypt\s*\(/i'
                ],
                'severity' => 'medium',
                'description' => 'Utilisation d\'algorithme de hachage faible'
            ],
            'hardcoded_secrets' => [
                'patterns' => [
                    '/password\s*=\s*["\'][^"\'
]{8,}["\']/',
                    '/api[_-]?key\s*=\s*["\'][^"\'
]{16,}["\']/',
                    '/secret\s*=\s*["\'][^"\'
]{16,}["\']/',
                    '/token\s*=\s*["\'][^"\'
]{20,}["\']/',
                ],
                'severity' => 'high',
                'description' => 'Secrets ou mots de passe en dur détectés'
            ]
        ];
        
        // Scanner les fichiers PHP
        $phpFiles = $this->findPhpFiles(base_path());
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            foreach ($vulnerabilityPatterns as $vulnType => $config) {
                foreach ($config['patterns'] as $pattern) {
                    foreach ($lines as $lineNumber => $line) {
                        if (preg_match($pattern, $line)) {
                            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
                            $vulnerabilities[] = [
                                'type' => ucfirst(str_replace('_', ' ', $vulnType)),
                                'location' => $relativePath . ':' . ($lineNumber + 1),
                                'severity' => $config['severity'],
                                'description' => $config['description'],
                                'code_snippet' => trim($line)
                            ];
                        }
                    }
                }
            }
        }
        
        // Vérifier les configurations de sécurité dans les fichiers
        $this->checkSecurityConfigurations($vulnerabilities);
        
        return $vulnerabilities;
    }
    
    private function findPhpFiles(string $directory): array
    {
        $phpFiles = [];
        $excludeDirs = ['vendor', 'node_modules', 'storage', '.git'];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getPathname();
                $skip = false;
                
                foreach ($excludeDirs as $excludeDir) {
                    if (str_contains($path, DIRECTORY_SEPARATOR . $excludeDir . DIRECTORY_SEPARATOR)) {
                        $skip = true;
                        break;
                    }
                }
                
                if (!$skip) {
                    $phpFiles[] = $path;
                }
            }
        }
        
        return $phpFiles;
    }
    
    private function checkSecurityConfigurations(array &$vulnerabilities): void
    {
        // Vérifier la présence de CSRF protection
        $webPhpPath = base_path('routes/web.php');
        if (file_exists($webPhpPath)) {
            $content = file_get_contents($webPhpPath);
            if (!str_contains($content, 'csrf') && !str_contains($content, 'VerifyCsrfToken')) {
                $vulnerabilities[] = [
                    'type' => 'CSRF Protection',
                    'location' => 'routes/web.php',
                    'severity' => 'high',
                    'description' => 'Protection CSRF non configurée dans les routes web'
                ];
            }
        }
        
        // Vérifier la configuration HTTPS
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            if (!str_contains($envContent, 'FORCE_HTTPS=true') && !str_contains($envContent, 'APP_URL=https://')) {
                $vulnerabilities[] = [
                    'type' => 'HTTPS Configuration',
                    'location' => '.env',
                    'severity' => 'medium',
                    'description' => 'HTTPS non forcé dans la configuration'
                ];
            }
        }
    }
    
    private function checkSecurityHeaders(): array
    {
        $issues = [];
        
        // Headers de sécurité recommandés
        $requiredHeaders = [
            'X-Content-Type-Options' => [
                'recommended' => 'nosniff',
                'severity' => 'medium',
                'description' => 'Prévient le MIME type sniffing'
            ],
            'X-Frame-Options' => [
                'recommended' => 'DENY',
                'severity' => 'high',
                'description' => 'Prévient les attaques clickjacking'
            ],
            'X-XSS-Protection' => [
                'recommended' => '1; mode=block',
                'severity' => 'medium',
                'description' => 'Active la protection XSS du navigateur'
            ],
            'Strict-Transport-Security' => [
                'recommended' => 'max-age=31536000; includeSubDomains',
                'severity' => 'high',
                'description' => 'Force l\'utilisation de HTTPS'
            ],
            'Content-Security-Policy' => [
                'recommended' => "default-src 'self'",
                'severity' => 'high',
                'description' => 'Contrôle les ressources que le navigateur peut charger'
            ],
            'Referrer-Policy' => [
                'recommended' => 'strict-origin-when-cross-origin',
                'severity' => 'low',
                'description' => 'Contrôle les informations de référent envoyées'
            ],
            'Permissions-Policy' => [
                'recommended' => 'geolocation=(), microphone=(), camera=()',
                'severity' => 'low',
                'description' => 'Contrôle l\'accès aux APIs du navigateur'
            ]
        ];
        
        // Tester les headers via une requête HTTP locale
        $testUrl = $this->getTestUrl();
        if ($testUrl) {
            $headers = $this->getHttpHeaders($testUrl);
            
            foreach ($requiredHeaders as $headerName => $config) {
                $headerKey = strtolower($headerName);
                $found = false;
                $currentValue = null;
                
                foreach ($headers as $header => $value) {
                    if (strtolower($header) === $headerKey) {
                        $found = true;
                        $currentValue = $value;
                        break;
                    }
                }
                
                if (!$found) {
                    $issues[] = [
                        'header' => $headerName,
                        'current_value' => 'missing',
                        'recommended_value' => $config['recommended'],
                        'status' => 'missing',
                        'severity' => $config['severity'],
                        'description' => $config['description']
                    ];
                } else {
                    // Vérifier si la valeur est appropriée
                    if ($this->isHeaderValueWeak($headerName, $currentValue)) {
                        $issues[] = [
                            'header' => $headerName,
                            'current_value' => $currentValue,
                            'recommended_value' => $config['recommended'],
                            'status' => 'weak',
                            'severity' => 'medium',
                            'description' => $config['description'] . ' (valeur faible)'
                        ];
                    }
                }
            }
        } else {
            // Fallback: vérifier les fichiers de configuration
            $this->checkHeadersInConfigFiles($issues, $requiredHeaders);
        }
        
        return $issues;
    }
    
    private function getTestUrl(): ?string
    {
        // Essayer de déterminer l'URL de test
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            if (preg_match('/APP_URL=(.+)/', $envContent, $matches)) {
                return trim($matches[1], '"\' ');
            }
        }
        
        // URLs de test par défaut
        $testUrls = [
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost'
        ];
        
        foreach ($testUrls as $url) {
            if ($this->isUrlAccessible($url)) {
                return $url;
            }
        }
        
        return null;
    }
    
    private function isUrlAccessible(string $url): bool
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'ignore_errors' => true
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        return $result !== false;
    }
    
    private function getHttpHeaders(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        $headers = [];
        
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (str_contains($header, ':')) {
                    [$name, $value] = explode(':', $header, 2);
                    $headers[trim($name)] = trim($value);
                }
            }
        }
        
        return $headers;
    }
    
    private function isHeaderValueWeak(string $headerName, string $value): bool
    {
        $weakPatterns = [
            'X-Frame-Options' => ['SAMEORIGIN'],
            'Content-Security-Policy' => ['unsafe-inline', 'unsafe-eval', '*'],
            'Strict-Transport-Security' => ['max-age=0', 'max-age=31536000']
        ];
        
        if (isset($weakPatterns[$headerName])) {
            foreach ($weakPatterns[$headerName] as $weakPattern) {
                if (str_contains(strtolower($value), strtolower($weakPattern))) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function checkHeadersInConfigFiles(array &$issues, array $requiredHeaders): void
    {
        // Vérifier .htaccess
        $htaccessPath = public_path('.htaccess');
        if (file_exists($htaccessPath)) {
            $htaccessContent = file_get_contents($htaccessPath);
            
            foreach ($requiredHeaders as $headerName => $config) {
                if (!str_contains($htaccessContent, $headerName)) {
                    $issues[] = [
                        'header' => $headerName,
                        'current_value' => 'not_configured',
                        'recommended_value' => $config['recommended'],
                        'status' => 'missing',
                        'severity' => $config['severity'],
                        'description' => $config['description'] . ' (non configuré dans .htaccess)'
                    ];
                }
            }
        } else {
            foreach ($requiredHeaders as $headerName => $config) {
                $issues[] = [
                    'header' => $headerName,
                    'current_value' => 'no_htaccess',
                    'recommended_value' => $config['recommended'],
                    'status' => 'missing',
                    'severity' => $config['severity'],
                    'description' => $config['description'] . ' (fichier .htaccess manquant)'
                ];
            }
        }
    }
    
    private function analyzeLogs(): array
    {
        $suspiciousActivities = [];
        
        // Analyser les logs d'application
        $logPaths = [
            storage_path('logs/laravel.log'),
            storage_path('logs/security.log'),
            storage_path('logs/access.log'),
            storage_path('logs/error.log')
        ];
        
        foreach ($logPaths as $logPath) {
            if (file_exists($logPath)) {
                $activities = $this->analyzeLogFile($logPath);
                $suspiciousActivities = array_merge($suspiciousActivities, $activities);
            }
        }
        
        // Analyser les logs du serveur web si disponibles
        $webServerLogs = [
            '/var/log/apache2/access.log',
            '/var/log/apache2/error.log',
            '/var/log/nginx/access.log',
            '/var/log/nginx/error.log',
            'C:\\xampp\\apache\\logs\\access.log',
            'C:\\xampp\\apache\\logs\\error.log'
        ];
        
        foreach ($webServerLogs as $logPath) {
            if (file_exists($logPath) && is_readable($logPath)) {
                $activities = $this->analyzeWebServerLog($logPath);
                $suspiciousActivities = array_merge($suspiciousActivities, $activities);
            }
        }
        
        // Grouper et trier les activités suspectes
        $suspiciousActivities = $this->groupSuspiciousActivities($suspiciousActivities);
        
        return $suspiciousActivities;
    }
    
    private function analyzeLogFile(string $logPath): array
    {
        $activities = [];
        $suspiciousPatterns = [
            'sql_injection' => [
                'patterns' => ['/SQL.*injection/i', '/UNION.*SELECT/i', '/DROP.*TABLE/i'],
                'severity' => 'critical'
            ],
            'xss_attempt' => [
                'patterns' => ['/<script/i', '/javascript:/i', '/onerror=/i'],
                'severity' => 'high'
            ],
            'file_inclusion' => [
                'patterns' => ['/\.\.\/\.\.\//', '/etc\/passwd/', '/proc\/self\/environ/'],
                'severity' => 'critical'
            ],
            'brute_force' => [
                'patterns' => ['/failed.*login/i', '/authentication.*failed/i', '/invalid.*credentials/i'],
                'severity' => 'high'
            ],
            'suspicious_user_agent' => [
                'patterns' => ['/sqlmap/i', '/nikto/i', '/nmap/i', '/burp/i'],
                'severity' => 'medium'
            ],
            'error_500' => [
                'patterns' => ['/HTTP\/1\.[01]" 5\d\d/', '/Internal Server Error/'],
                'severity' => 'medium'
            ]
        ];
        
        $handle = fopen($logPath, 'r');
        if ($handle) {
            $lineCount = 0;
            while (($line = fgets($handle)) !== false && $lineCount < 10000) { // Limiter à 10k lignes
                $lineCount++;
                
                foreach ($suspiciousPatterns as $type => $config) {
                    foreach ($config['patterns'] as $pattern) {
                        if (preg_match($pattern, $line)) {
                            $timestamp = $this->extractTimestamp($line);
                            $ip = $this->extractIpAddress($line);
                            
                            $activities[] = [
                                'timestamp' => $timestamp ?: date('Y-m-d H:i:s'),
                                'type' => ucfirst(str_replace('_', ' ', $type)),
                                'ip' => $ip ?: 'unknown',
                                'severity' => $config['severity'],
                                'log_file' => basename($logPath),
                                'raw_line' => trim($line)
                            ];
                            break 2; // Sortir des deux boucles
                        }
                    }
                }
            }
            fclose($handle);
        }
        
        return $activities;
    }
    
    private function analyzeWebServerLog(string $logPath): array
    {
        $activities = [];
        $suspiciousPatterns = [
            '/\s(4\d\d|5\d\d)\s/', // Codes d'erreur HTTP
            '/\s"[^"]*\.\.\//i', // Tentatives de directory traversal
            '/\s"[^"]*<script/i', // Tentatives XSS
            '/\s"[^"]*UNION.*SELECT/i', // Tentatives SQL injection
            '/\s"[^"]*\/admin/i', // Tentatives d'accès admin
            '/\s"[^"]*\/wp-admin/i', // Tentatives WordPress
            '/\s"[^"]*\/phpmyadmin/i' // Tentatives phpMyAdmin
        ];
        
        $handle = fopen($logPath, 'r');
        if ($handle) {
            $lineCount = 0;
            while (($line = fgets($handle)) !== false && $lineCount < 5000) { // Limiter à 5k lignes
                $lineCount++;
                
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        $timestamp = $this->extractTimestamp($line);
                        $ip = $this->extractIpAddress($line);
                        $statusCode = $this->extractStatusCode($line);
                        
                        $activities[] = [
                            'timestamp' => $timestamp ?: date('Y-m-d H:i:s'),
                            'type' => $this->categorizeWebActivity($line, $statusCode),
                            'ip' => $ip ?: 'unknown',
                            'severity' => $this->getSeverityFromStatusCode($statusCode),
                            'log_file' => basename($logPath),
                            'status_code' => $statusCode,
                            'raw_line' => trim($line)
                        ];
                        break;
                    }
                }
            }
            fclose($handle);
        }
        
        return $activities;
    }
    
    private function extractTimestamp(string $line): ?string
    {
        // Patterns de timestamp courants
        $patterns = [
            '/\[(\d{2}\/\w{3}\/\d{4}:\d{2}:\d{2}:\d{2})/', // Apache format
            '/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', // Laravel format
            '/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/' // ISO format
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    private function extractIpAddress(string $line): ?string
    {
        if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    private function extractStatusCode(string $line): ?string
    {
        if (preg_match('/\s(\d{3})\s/', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    private function categorizeWebActivity(string $line, ?string $statusCode): string
    {
        if (str_contains($line, 'admin')) return 'Admin access attempt';
        if (str_contains($line, 'UNION') || str_contains($line, 'SELECT')) return 'SQL injection attempt';
        if (str_contains($line, '<script')) return 'XSS attempt';
        if (str_contains($line, '../')) return 'Directory traversal attempt';
        if ($statusCode && in_array($statusCode, ['404', '403'])) return 'Unauthorized access attempt';
        if ($statusCode && str_starts_with($statusCode, '5')) return 'Server error';
        
        return 'Suspicious activity';
    }
    
    private function getSeverityFromStatusCode(?string $statusCode): string
    {
        if (!$statusCode) return 'medium';
        
        return match(true) {
            str_starts_with($statusCode, '5') => 'high',
            in_array($statusCode, ['403', '401']) => 'medium',
            $statusCode === '404' => 'low',
            default => 'medium'
        };
    }
    
    private function groupSuspiciousActivities(array $activities): array
    {
        $grouped = [];
        $ipCounts = [];
        
        // Compter les activités par IP
        foreach ($activities as $activity) {
            $ip = $activity['ip'];
            $type = $activity['type'];
            $key = $ip . '|' . $type;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = $activity;
                $grouped[$key]['count'] = 1;
                $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
            } else {
                $grouped[$key]['count']++;
                $ipCounts[$ip]++;
            }
        }
        
        // Ajuster la sévérité basée sur la fréquence
        foreach ($grouped as &$activity) {
            $ip = $activity['ip'];
            if ($ipCounts[$ip] > 10) {
                $activity['severity'] = 'critical';
            } elseif ($ipCounts[$ip] > 5) {
                $activity['severity'] = 'high';
            }
        }
        
        // Trier par sévérité et timestamp
        usort($grouped, function($a, $b) {
            $severityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            $aSeverity = $severityOrder[$a['severity']] ?? 0;
            $bSeverity = $severityOrder[$b['severity']] ?? 0;
            
            if ($aSeverity === $bSeverity) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            }
            
            return $bSeverity - $aSeverity;
        });
        
        return array_values($grouped);
    }
    
    private function displaySecuritySummary(array $results): void
    {
        $this->info('🛡️ Résumé de Sécurité');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $totalIssues = 0;
        $criticalIssues = 0;
        $highIssues = 0;
        $mediumIssues = 0;
        
        foreach ($results as $category => $issues) {
            foreach ($issues as $issue) {
                $totalIssues++;
                $severity = $issue['severity'] ?? 'medium';
                switch ($severity) {
                    case 'critical':
                        $criticalIssues++;
                        break;
                    case 'high':
                        $highIssues++;
                        break;
                    case 'medium':
                        $mediumIssues++;
                        break;
                }
            }
        }
        
        $table = new Table($this->output);
        $table->setHeaders(['Catégorie', 'Problèmes', 'Statut']);
        $table->addRows([
            ['Permissions fichiers', count($results['file_permissions']), count($results['file_permissions']) > 0 ? '⚠️ Problèmes' : '✅ OK'],
            ['Dépendances', count($results['dependencies']), count($results['dependencies']) > 0 ? '❌ Vulnérables' : '✅ Sécurisées'],
            ['Configurations', count($results['configurations']), count($results['configurations']) > 0 ? '⚠️ À corriger' : '✅ Sécurisées'],
            ['Vulnérabilités', count($results['vulnerabilities']), count($results['vulnerabilities']) > 0 ? '❌ Détectées' : '✅ Aucune'],
            ['Headers sécurité', count($results['headers']), count($results['headers']) > 0 ? '⚠️ Manquants' : '✅ Configurés'],
            ['Activités suspectes', count($results['logs']), count($results['logs']) > 0 ? '🚨 Détectées' : '✅ Aucune']
        ]);
        $table->render();
        
        $this->line('');
        $this->line("<comment>📊 Résumé des problèmes:</comment>");
        $this->line("   🔴 Critiques: {$criticalIssues}");
        $this->line("   🟠 Élevés: {$highIssues}");
        $this->line("   🟡 Moyens: {$mediumIssues}");
        $this->line("   📈 Total: {$totalIssues}");
        $this->line('');
    }
    
    private function displayDetailedFindings(array $results): void
    {
        $this->info('🔍 Détails des Vulnérabilités');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if (!empty($results['vulnerabilities'])) {
            $this->line('<comment>🚨 Vulnérabilités critiques détectées:</comment>');
            $table = new Table($this->output);
            $table->setHeaders(['Type', 'Localisation', 'Sévérité', 'Description']);
            
            foreach ($results['vulnerabilities'] as $vuln) {
                $severityIcon = match($vuln['severity']) {
                    'critical' => '🔴',
                    'high' => '🟠',
                    'medium' => '🟡',
                    default => '🟢'
                };
                
                $table->addRow([
                    $vuln['type'],
                    $vuln['location'],
                    $severityIcon . ' ' . ucfirst($vuln['severity']),
                    $vuln['description']
                ]);
            }
            $table->render();
            $this->line('');
        }
        
        if (!empty($results['dependencies'])) {
            $this->line('<comment>📦 Dépendances vulnérables:</comment>');
            $table = new Table($this->output);
            $table->setHeaders(['Package', 'Version', 'CVE', 'Sévérité', 'Description']);
            
            foreach ($results['dependencies'] as $dep) {
                $table->addRow([
                    $dep['package'],
                    $dep['version'],
                    $dep['vulnerability'],
                    ucfirst($dep['severity']),
                    $dep['description']
                ]);
            }
            $table->render();
            $this->line('');
        }
    }
    
    private function applySecurityFixes(array $results): void
    {
        $this->info('🔧 Application des Corrections Automatiques');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $fixesApplied = [];
        $errors = [];
        
        // 1. Correction des permissions de fichiers critiques
        $this->fixFilePermissions($results['file_permissions'], $fixesApplied, $errors);
        
        // 2. Mise à jour du fichier .htaccess avec les en-têtes de sécurité
        $this->addSecurityHeaders($results['headers'], $fixesApplied, $errors);
        
        // 3. Mise à jour des configurations PHP
        $this->fixPhpConfigurations($results['configurations'], $fixesApplied, $errors);
        
        // 4. Création/mise à jour du fichier robots.txt
        $this->updateRobotsTxt($fixesApplied, $errors);
        
        // 5. Mise à jour des configurations Laravel
        $this->fixLaravelConfigurations($results['configurations'], $fixesApplied, $errors);
        
        // Afficher les résultats
        if (!empty($fixesApplied)) {
            $this->info('\n✅ Corrections appliquées:');
            foreach ($fixesApplied as $fix) {
                $this->line("  • $fix");
            }
        }
        
        if (!empty($errors)) {
            $this->error('\n❌ Erreurs rencontrées:');
            foreach ($errors as $error) {
                $this->line("  • $error");
            }
        }
        
        if (empty($fixesApplied) && empty($errors)) {
            $this->info('✅ Aucune correction nécessaire');
        }
        
        $this->line('');
    }
    
    private function fixFilePermissions(array $issues, array &$fixesApplied, array &$errors): void
    {
        foreach ($issues as $issue) {
            if ($issue['type'] === 'permission') {
                $this->line("Correction des permissions pour {$issue['file']}...");
                $fullPath = base_path($issue['file']);
                if (file_exists($fullPath)) {
                    if (chmod($fullPath, octdec($issue['expected']))) {
                        $fixesApplied[] = "Permissions corrigées pour {$issue['file']} ({$issue['expected']})";
                    } else {
                        $errors[] = "Impossible de corriger les permissions pour {$issue['file']}";
                    }
                }
            }
        }
    }
    
    private function addSecurityHeaders(array $headerIssues, array &$fixesApplied, array &$errors): void
    {
        $htaccessPath = public_path('.htaccess');
        
        if (!file_exists($htaccessPath)) {
            // Créer un fichier .htaccess basique
            $basicHtaccess = "<IfModule mod_rewrite.c>\n";
            $basicHtaccess .= "    <IfModule mod_negotiation.c>\n";
            $basicHtaccess .= "        Options -MultiViews -Indexes\n";
            $basicHtaccess .= "    </IfModule>\n\n";
            $basicHtaccess .= "    RewriteEngine On\n\n";
            $basicHtaccess .= "    # Handle Authorization Header\n";
            $basicHtaccess .= "    RewriteCond %{HTTP:Authorization} .\n";
            $basicHtaccess .= "    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n\n";
            $basicHtaccess .= "    # Redirect Trailing Slashes If Not A Folder...\n";
            $basicHtaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
            $basicHtaccess .= "    RewriteCond %{REQUEST_URI} (.+)/$\n";
            $basicHtaccess .= "    RewriteRule ^ %1 [L,R=301]\n\n";
            $basicHtaccess .= "    # Send Requests To Front Controller...\n";
            $basicHtaccess .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
            $basicHtaccess .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
            $basicHtaccess .= "    RewriteRule ^ index.php [L]\n";
            $basicHtaccess .= "</IfModule>\n\n";
            
            file_put_contents($htaccessPath, $basicHtaccess);
        }
        
        $currentContent = file_get_contents($htaccessPath);
        
        // Vérifier si les en-têtes de sécurité existent déjà
        if (!str_contains($currentContent, '# Security Headers')) {
            $securityHeaders = "\n# Security Headers\n";
            $securityHeaders .= "<IfModule mod_headers.c>\n";
            $securityHeaders .= "    Header always set X-Content-Type-Options nosniff\n";
            $securityHeaders .= "    Header always set X-Frame-Options DENY\n";
            $securityHeaders .= "    Header always set X-XSS-Protection \"1; mode=block\"\n";
            $securityHeaders .= "    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"\n";
            $securityHeaders .= "    Header always set Permissions-Policy \"geolocation=(), microphone=(), camera=()\"\n";
            $securityHeaders .= "    # Remove server signature\n";
            $securityHeaders .= "    Header unset Server\n";
            $securityHeaders .= "    Header unset X-Powered-By\n";
            $securityHeaders .= "</IfModule>\n\n";
            
            if (file_put_contents($htaccessPath, $securityHeaders, FILE_APPEND)) {
                $fixesApplied[] = "En-têtes de sécurité ajoutés au fichier .htaccess";
            } else {
                $errors[] = "Impossible d'écrire dans le fichier .htaccess";
            }
        }
    }
    
    private function fixPhpConfigurations(array $configIssues, array &$fixesApplied, array &$errors): void
    {
        // Créer un fichier .user.ini pour les configurations PHP locales
        $userIniPath = public_path('.user.ini');
        $phpConfigs = [];
        
        foreach ($configIssues as $issue) {
            if ($issue['type'] === 'php_config') {
                $phpConfigs[] = "{$issue['config']} = {$issue['recommended']}";
            }
        }
        
        if (!empty($phpConfigs)) {
            $content = "; Security configurations\n" . implode("\n", $phpConfigs) . "\n";
            
            if (file_put_contents($userIniPath, $content)) {
                $fixesApplied[] = "Configurations PHP sécurisées dans .user.ini";
            } else {
                $errors[] = "Impossible de créer le fichier .user.ini";
            }
        }
    }
    
    private function updateRobotsTxt(array &$fixesApplied, array &$errors): void
    {
        $robotsPath = public_path('robots.txt');
        $robotsContent = "User-agent: *\n";
        $robotsContent .= "Disallow: /admin/\n";
        $robotsContent .= "Disallow: /config/\n";
        $robotsContent .= "Disallow: /storage/\n";
        $robotsContent .= "Disallow: /vendor/\n";
        $robotsContent .= "Disallow: /.env\n";
        $robotsContent .= "Disallow: /composer.json\n";
        $robotsContent .= "Disallow: /composer.lock\n";
        
        if (!file_exists($robotsPath) || file_get_contents($robotsPath) !== $robotsContent) {
            if (file_put_contents($robotsPath, $robotsContent)) {
                $fixesApplied[] = "Fichier robots.txt mis à jour";
            } else {
                $errors[] = "Impossible de créer/mettre à jour robots.txt";
            }
        }
    }
    
    private function fixLaravelConfigurations(array $configIssues, array &$fixesApplied, array &$errors): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            $errors[] = "Fichier .env introuvable";
            return;
        }
        
        $envContent = file_get_contents($envPath);
        $modified = false;
        
        foreach ($configIssues as $issue) {
            if ($issue['type'] === 'insecure_config') {
                $pattern = "/^{$issue['config']}=.*$/m";
                $replacement = "{$issue['config']}={$issue['recommended']}";
                
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, $replacement, $envContent);
                    $modified = true;
                }
            }
        }
        
        // Ajouter des configurations de sécurité manquantes
        $securityConfigs = [
            'SESSION_SECURE_COOKIE' => 'true',
            'SESSION_HTTP_ONLY' => 'true',
            'SESSION_SAME_SITE' => 'strict',
            'SANCTUM_STATEFUL_DOMAINS' => 'localhost,127.0.0.1'
        ];
        
        foreach ($securityConfigs as $key => $value) {
            if (!str_contains($envContent, $key)) {
                $envContent .= "\n$key=$value";
                $modified = true;
            }
        }
        
        if ($modified) {
            if (file_put_contents($envPath, $envContent)) {
                $fixesApplied[] = "Configurations Laravel sécurisées dans .env";
            } else {
                $errors[] = "Impossible de modifier le fichier .env";
            }
        }
    }
    
    private function displaySecurityRecommendations(array $results): void
    {
        $this->info('💡 Recommandations de Sécurité');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $recommendations = [
            '🔐 Activer l\'authentification à deux facteurs pour les comptes admin',
            '🛡️ Implémenter la validation CSRF sur tous les formulaires',
            '🔒 Utiliser HTTPS en production avec des certificats valides',
            '📝 Configurer la rotation automatique des logs de sécurité',
            '🚫 Désactiver les fonctions PHP dangereuses (eval, exec, etc.)',
            '🔍 Mettre en place un monitoring des tentatives d\'intrusion',
            '📊 Effectuer des audits de sécurité réguliers',
            '🔄 Maintenir les dépendances à jour avec des patches de sécurité'
        ];
        
        foreach ($recommendations as $recommendation) {
            $this->line($recommendation);
        }
        
        $this->line('');
        $this->line('<comment>🔧 Actions rapides:</comment>');
        $this->line('  • <info>php nexa security:scan --fix</info> - Corriger automatiquement les problèmes');
        $this->line('  • <info>composer audit</info> - Vérifier les vulnérabilités des dépendances');
        $this->line('  • <info>php nexa optimize:security</info> - Optimiser la configuration de sécurité');
        $this->line('');
    }
    
    private function exportSecurityReport(array $results, string $filename): void
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'scan_results' => $results,
            'summary' => [
                'total_issues' => array_sum(array_map('count', $results)),
                'critical_issues' => $this->countBySeverity($results, 'critical'),
                'high_issues' => $this->countBySeverity($results, 'high'),
                'medium_issues' => $this->countBySeverity($results, 'medium')
            ],
            'recommendations' => [
                'Enable 2FA for admin accounts',
                'Implement CSRF validation',
                'Use HTTPS in production',
                'Configure log rotation',
                'Disable dangerous PHP functions'
            ]
        ];
        
        $exportPath = storage_path('security/' . $filename);
        
        // Créer le dossier s'il n'existe pas
        $dir = dirname($exportPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($exportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->success("🔒 Rapport de sécurité exporté vers: {$exportPath}");
    }
    
    private function countBySeverity(array $results, string $severity): int
    {
        $count = 0;
        foreach ($results as $category => $issues) {
            foreach ($issues as $issue) {
                if (($issue['severity'] ?? 'medium') === $severity) {
                    $count++;
                }
            }
        }
        return $count;
    }
}