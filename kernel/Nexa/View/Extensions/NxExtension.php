<?php

namespace Nexa\View\Extensions;

use Nexa\Security\Encryption;

abstract class NxExtension
{
    /**
     * Nom de l'extension
     */
    abstract public function getName(): string;
    
    /**
     * Version de l'extension
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    /**
     * Description de l'extension
     */
    public function getDescription(): string
    {
        return '';
    }
    
    /**
     * Directives fournies par l'extension
     */
    public function getDirectives(): array
    {
        return [];
    }
    
    /**
     * Filtres fournis par l'extension
     */
    public function getFilters(): array
    {
        return [];
    }
    
    /**
     * Composants fournis par l'extension
     */
    public function getComponents(): array
    {
        return [];
    }
    
    /**
     * Fonctions globales fournies par l'extension
     */
    public function getFunctions(): array
    {
        return [];
    }
    
    /**
     * Configuration par défaut de l'extension
     */
    public function getDefaultConfig(): array
    {
        return [];
    }
    
    /**
     * Initialisation de l'extension
     */
    public function boot(array $config = []): void
    {
        // Override dans les extensions spécifiques
    }
    
    /**
     * Vérification des dépendances
     */
    public function checkDependencies(): array
    {
        return [];
    }
    
    /**
     * Assets CSS/JS requis par l'extension
     */
    public function getAssets(): array
    {
        return [
            'css' => [],
            'js' => []
        ];
    }
}



/**
 * Extension pour les graphiques et visualisations
 */
class ChartsExtension extends NxExtension
{
    public function getName(): string
    {
        return 'charts';
    }
    
    public function getDescription(): string
    {
        return 'Extension pour les graphiques et visualisations de données';
    }
    
    public function getComponents(): array
    {
        return [
            'line-chart' => function($attributes, $content) {
                $data = $attributes['data'] ?? '{}';
                $options = $attributes['options'] ?? '{}';
                $height = $attributes['height'] ?? '400px';
                $id = 'chart_' . uniqid();
                
                return "
                    <div class='chart-container' style='height: {$height};'>
                        <canvas id='{$id}'></canvas>
                    </div>
                    <script>
                        new Chart(document.getElementById('{$id}'), {
                            type: 'line',
                            data: {$data},
                            options: Object.assign({
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'top' }
                                },
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }, {$options})
                        });
                    </script>
                ";
            },
            
            'bar-chart' => function($attributes, $content) {
                $data = $attributes['data'] ?? '{}';
                $options = $attributes['options'] ?? '{}';
                $height = $attributes['height'] ?? '400px';
                $horizontal = isset($attributes['horizontal']) ? 'true' : 'false';
                $id = 'chart_' . uniqid();
                
                $chartType = "'bar'";
                $indexAxis = $horizontal === 'true' ? "'y'" : "'x'";
                
                return "
                    <div class='chart-container' style='height: {$height};'>
                        <canvas id='{$id}'></canvas>
                    </div>
                    <script>
                        new Chart(document.getElementById('{$id}'), {
                            type: {$chartType},
                            data: {$data},
                            options: Object.assign({
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: {$indexAxis},
                                plugins: {
                                    legend: { position: 'top' }
                                }
                            }, {$options})
                        });
                    </script>
                ";
            },
            
            'pie-chart' => function($attributes, $content) {
                $data = $attributes['data'] ?? '{}';
                $options = $attributes['options'] ?? '{}';
                $height = isset($attributes['height']) ? $attributes['height'] : '400px';
                $doughnut = isset($attributes['doughnut']) ? 'true' : 'false';
                $id = 'chart_' . uniqid();
                
                $pieChartType = $doughnut === 'true' ? "'doughnut'" : "'pie'";
                
                return "
                    <div class='chart-container' style='height: {$height};'>
                        <canvas id='{$id}'></canvas>
                    </div>
                    <script>
                        new Chart(document.getElementById('{$id}'), {
                            type: {$pieChartType},
                            data: {$data},
                            options: Object.assign({
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { position: 'bottom' }
                                }
                            }, {$options})
                        });
                    </script>
                ";
            },
            
            'real-time-chart' => function($attributes, $content) {
                $endpoint = $attributes['endpoint'] ?? '/api/chart-data';
                $interval = $attributes['interval'] ?? '5000';
                $maxPoints = $attributes['max-points'] ?? '50';
                $height = $attributes['height'] ?? '400px';
                $id = 'chart_' . uniqid();
                
                return "
                    <div class='chart-container real-time-chart' style='height: {$height};'>
                        <canvas id='{$id}'></canvas>
                        <div class='chart-controls'>
                            <button class='btn btn-sm pause-chart' data-chart='{$id}'>Pause</button>
                            <button class='btn btn-sm reset-chart' data-chart='{$id}'>Reset</button>
                        </div>
                    </div>
                    <script>
                        new RealTimeChart('{$id}', {
                            endpoint: '{$endpoint}',
                            interval: {$interval},
                            maxPoints: {$maxPoints}
                        });
                    </script>
                ";
            }
        ];
    }
    
    public function getAssets(): array
    {
        return [
            'css' => [
                '/assets/css/charts.css'
            ],
            'js' => [
                'https://cdn.jsdelivr.net/npm/chart.js',
                '/assets/js/real-time-chart.js'
            ]
        ];
    }
}

/**
 * Extension pour les fonctionnalités de sécurité
 */
class SecurityExtension extends NxExtension
{
    public function getName(): string
    {
        return 'security';
    }
    
    public function getDescription(): string
    {
        return 'Extension pour les fonctionnalités de sécurité avancées';
    }
    
    public function getDirectives(): array
    {
        return [
            'secure' => function($level, $content) {
                return "<?php if(auth()->user() && auth()->user()->hasSecurityLevel({$level})): ?>" . $content . "<?php endif; ?>";
            },
            'role' => function($roles, $content) {
                return "<?php if(auth()->user() && auth()->user()->hasAnyRole({$roles})): ?>" . $content . "<?php endif; ?>";
            },
            'permission' => function($permission, $content) {
                return "<?php if(auth()->user() && auth()->user()->can({$permission})): ?>" . $content . "<?php endif; ?>";
            },
            'csrf_meta' => function($params, $content) {
                return '<meta name="csrf-token" content="<?php echo csrf_token(); ?>">';
            },
            'honeypot' => function($params, $content) {
                return '<input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">';
            }
        ];
    }
    
    public function getFilters(): array
    {
        return [
            'sanitize' => function($value) {
                return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            },
            'encrypt' => function($value) {
                $encryption = new Encryption();
                return $encryption->encrypt($value);
            },
            'hash' => function($value, $algorithm = 'sha256') {
                return hash($algorithm, $value);
            },
            'mask_email' => function($email) {
                $parts = explode('@', $email);
                if (count($parts) !== 2) return $email;
                $username = $parts[0];
                $domain = $parts[1];
                $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
                return $maskedUsername . '@' . $domain;
            },
            'mask_phone' => function($phone) {
                return preg_replace('/(?<=\d{2})\d(?=\d{2})/', '*', $phone);
            }
        ];
    }
    
    public function getComponents(): array
    {
        return [
            'captcha' => function($attributes, $content) {
                $siteKey = config('services.recaptcha.site_key');
                $theme = $attributes['theme'] ?? 'light';
                $size = $attributes['size'] ?? 'normal';
                
                return "
                    <div class='captcha-container'>
                        <div class='g-recaptcha' 
                             data-sitekey='{$siteKey}'
                             data-theme='{$theme}'
                             data-size='{$size}'>
                        </div>
                    </div>
                    <script src='https://www.google.com/recaptcha/api.js' async defer></script>
                ";
            },
            
            'two-factor-input' => function($attributes, $content) {
                $length = $attributes['length'] ?? '6';
                $name = $attributes['name'] ?? 'two_factor_code';
                
                return "
                    <div class='two-factor-input'>
                        <div class='code-inputs'>
                            " . str_repeat("
                                <input type='text' 
                                       class='code-digit' 
                                       maxlength='1' 
                                       pattern='[0-9]'
                                       inputmode='numeric'
                                       autocomplete='one-time-code'>
                            ", (int)$length) . "
                        </div>
                        <input type='hidden' name='{$name}' id='{$name}'>
                    </div>
                    
                    <script>
                        new TwoFactorInput('.two-factor-input', '{$name}');
                    </script>
                ";
            },
            
            'password-strength' => function($attributes, $content) {
                $target = $attributes['target'] ?? 'password';
                $showRequirements = isset($attributes['show-requirements']) ? 'true' : 'false';
                
                $requirementsHtml = '';
                if ($showRequirements === 'true') {
                    $requirementsHtml = "
                        <div class='password-requirements'>
                            <ul class='requirements-list'>
                                <li class='requirement' data-requirement='length'>Au moins 8 caractères</li>
                                <li class='requirement' data-requirement='uppercase'>Une majuscule</li>
                                <li class='requirement' data-requirement='lowercase'>Une minuscule</li>
                                <li class='requirement' data-requirement='number'>Un chiffre</li>
                                <li class='requirement' data-requirement='special'>Un caractère spécial</li>
                            </ul>
                        </div>
                    ";
                }
                
                return "
                    <div class='password-strength-meter'>
                        <div class='strength-bar'>
                            <div class='strength-fill' id='strength-fill'></div>
                        </div>
                        <div class='strength-text' id='strength-text'>Faible</div>
                        
                        {$requirementsHtml}
                    </div>
                    
                    <script>
                        new PasswordStrengthMeter('#{$target}', {
                            showRequirements: {$showRequirements}
                        });
                    </script>
                ";
            }
        ];
    }
    
    public function getAssets(): array
    {
        return [
            'css' => [
                '/assets/css/security.css'
            ],
            'js' => [
                '/assets/js/two-factor.js',
                '/assets/js/password-strength.js'
            ]
        ];
    }
}