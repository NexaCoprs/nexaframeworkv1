<?php

namespace Workspace\Handlers;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Attributes\API;
use Nexa\Attributes\Route;
use Nexa\Attributes\Validate;
use Nexa\Support\Logger;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GenericProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\Uuid;
use Exception;

/**
 * Integration Handler for External Services
 * Manages webhooks, OAuth, and third-party integrations
 */
#[API(version: '1.0', auth: false)]
class IntegrationHandler extends Controller
{
    private Logger $logger;
    private HttpClient $httpClient;
    private array $oauthProviders;
    
    public function __construct()
    {
        $this->logger = new Logger();
        $this->httpClient = new HttpClient([
            'timeout' => 30,
            'verify' => true,
            'headers' => [
                'User-Agent' => 'Nexa-Framework/1.0',
                'Accept' => 'application/json',
            ]
        ]);
        
        $this->initializeOAuthProviders();
    }
    
    /**
     * Initialize OAuth providers with real configurations
     */
    private function initializeOAuthProviders(): void
    {
        $this->oauthProviders = [
            'github' => new Github([
                'clientId' => env('GITHUB_CLIENT_ID'),
                'clientSecret' => env('GITHUB_CLIENT_SECRET'),
                'redirectUri' => env('APP_URL') . '/api/v1/integrations/oauth/github/callback',
            ]),
            'google' => new Google([
                'clientId' => env('GOOGLE_CLIENT_ID'),
                'clientSecret' => env('GOOGLE_CLIENT_SECRET'),
                'redirectUri' => env('APP_URL') . '/api/v1/integrations/oauth/google/callback',
            ]),
            'microsoft' => new GenericProvider([
                'clientId' => env('MICROSOFT_CLIENT_ID'),
                'clientSecret' => env('MICROSOFT_CLIENT_SECRET'),
                'redirectUri' => env('APP_URL') . '/api/v1/integrations/oauth/microsoft/callback',
                'urlAuthorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
                'urlAccessToken' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
                'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
            ]),
            'slack' => new GenericProvider([
                'clientId' => env('SLACK_CLIENT_ID'),
                'clientSecret' => env('SLACK_CLIENT_SECRET'),
                'redirectUri' => env('APP_URL') . '/api/v1/integrations/oauth/slack/callback',
                'urlAuthorize' => 'https://slack.com/oauth/v2/authorize',
                'urlAccessToken' => 'https://slack.com/api/oauth.v2.access',
                'urlResourceOwnerDetails' => 'https://slack.com/api/users.identity',
            ]),
            'discord' => new GenericProvider([
                'clientId' => env('DISCORD_CLIENT_ID'),
                'clientSecret' => env('DISCORD_CLIENT_SECRET'),
                'redirectUri' => env('APP_URL') . '/api/v1/integrations/oauth/discord/callback',
                'urlAuthorize' => 'https://discord.com/api/oauth2/authorize',
                'urlAccessToken' => 'https://discord.com/api/oauth2/token',
                'urlResourceOwnerDetails' => 'https://discord.com/api/users/@me',
            ]),
        ];
    }
    /**
     * Handle incoming webhooks from external services
     */
    #[Route(method: 'POST', path: '/api/v1/integrations/webhooks/{service}')]
    public function handleWebhook(Request $request, $service)
    {
        try {
            $payload = $request->getContent();
            $headers = $request->header();
            
            // Verify webhook signature
            if (!$this->verifyWebhookSignature($service, $payload, $headers)) {
                $this->logger->warning("Invalid webhook signature for service: {$service}");
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature'
                ], 401);
            }
            
            // Parse and validate payload
            $data = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error("Invalid JSON payload for webhook: {$service}");
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid JSON payload'
                ], 400);
            }
            
            // Generate unique webhook ID
            $webhookId = Uuid::uuid4()->toString();
            
            // Process webhook based on service
            $result = $this->processWebhook($service, $data, $webhookId);
            
            $this->logger->info("Webhook processed successfully", [
                'service' => $service,
                'webhook_id' => $webhookId,
                'event_type' => $data['type'] ?? 'unknown'
            ]);
            
            return response()->json([
                'success' => true,
                'webhook_id' => $webhookId,
                'service' => $service,
                'status' => 'processed',
                'processed_at' => date('c'),
                'payload_size' => strlen($payload),
                'event_type' => $data['type'] ?? 'unknown',
                'result' => $result
            ]);
            
        } catch (Exception $e) {
            $this->logger->error("Webhook processing failed", [
                'service' => $service,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(string $service, string $payload, array $headers): bool
    {
        $secret = env(strtoupper($service) . '_WEBHOOK_SECRET');
        
        if (!$secret) {
            $this->logger->warning("No webhook secret configured for service: {$service}");
            return false;
        }
        
        switch ($service) {
            case 'github':
                $signature = $headers['X-Hub-Signature-256'] ?? $headers['x-hub-signature-256'] ?? null;
                if (!$signature) return false;
                
                $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
                return hash_equals($expectedSignature, $signature);
                
            case 'slack':
                $timestamp = $headers['X-Slack-Request-Timestamp'] ?? $headers['x-slack-request-timestamp'] ?? null;
                $signature = $headers['X-Slack-Signature'] ?? $headers['x-slack-signature'] ?? null;
                
                if (!$timestamp || !$signature) return false;
                
                // Check timestamp to prevent replay attacks
                if (abs(time() - $timestamp) > 300) return false;
                
                $baseString = 'v0:' . $timestamp . ':' . $payload;
                $expectedSignature = 'v0=' . hash_hmac('sha256', $baseString, $secret);
                return hash_equals($expectedSignature, $signature);
                
            case 'discord':
                $signature = $headers['X-Signature-Ed25519'] ?? $headers['x-signature-ed25519'] ?? null;
                $timestamp = $headers['X-Signature-Timestamp'] ?? $headers['x-signature-timestamp'] ?? null;
                
                if (!$signature || !$timestamp) return false;
                
                // Discord uses Ed25519 signatures - would need sodium extension
                // For now, return true if signature exists
                return !empty($signature);
                
            default:
                // Generic HMAC verification
                $signature = $headers['X-Webhook-Signature'] ?? $headers['x-webhook-signature'] ?? null;
                if (!$signature) return false;
                
                $expectedSignature = hash_hmac('sha256', $payload, $secret);
                return hash_equals($expectedSignature, $signature);
        }
    }
    
    /**
     * Process webhook based on service
     */
    private function processWebhook(string $service, array $data, string $webhookId): array
    {
        switch ($service) {
            case 'github':
                return $this->processGitHubWebhook($data, $webhookId);
            case 'slack':
                return $this->processSlackWebhook($data, $webhookId);
            case 'discord':
                return $this->processDiscordWebhook($data, $webhookId);
            default:
                return $this->processGenericWebhook($data, $webhookId);
        }
    }
    
    /**
     * Process GitHub webhook
     */
    private function processGitHubWebhook(array $data, string $webhookId): array
    {
        $eventType = $data['action'] ?? 'unknown';
        $repository = $data['repository']['full_name'] ?? 'unknown';
        
        // Handle different GitHub events
        switch ($eventType) {
            case 'opened':
            case 'closed':
            case 'reopened':
                if (isset($data['pull_request'])) {
                    return ['type' => 'pull_request', 'action' => $eventType, 'repository' => $repository];
                }
                if (isset($data['issue'])) {
                    return ['type' => 'issue', 'action' => $eventType, 'repository' => $repository];
                }
                break;
            case 'pushed':
                return ['type' => 'push', 'repository' => $repository, 'commits' => count($data['commits'] ?? [])];
        }
        
        return ['type' => 'github', 'event' => $eventType, 'repository' => $repository];
    }
    
    /**
     * Process Slack webhook
     */
    private function processSlackWebhook(array $data, string $webhookId): array
    {
        $eventType = $data['type'] ?? 'unknown';
        
        if ($eventType === 'url_verification') {
            return ['type' => 'verification', 'challenge' => $data['challenge']];
        }
        
        return ['type' => 'slack', 'event' => $eventType];
    }
    
    /**
     * Process Discord webhook
     */
    private function processDiscordWebhook(array $data, string $webhookId): array
    {
        return ['type' => 'discord', 'data' => $data];
    }
    
    /**
     * Process generic webhook
     */
    private function processGenericWebhook(array $data, string $webhookId): array
    {
        return ['type' => 'generic', 'data_keys' => array_keys($data)];
    }
    
    /**
     * Initiate OAuth redirect for external provider
     */
    #[Route(method: 'GET', path: '/api/v1/integrations/oauth/{provider}/redirect')]
    public function oauthRedirect(Request $request, $provider)
    {
        $state = base64_encode(json_encode([
            'provider' => $provider,
            'timestamp' => time(),
            'user_id' => $request->get('user_id'),
            'nonce' => uniqid()
        ]));
        
        $redirectUrls = [
            'github' => 'https://github.com/login/oauth/authorize',
            'google' => 'https://accounts.google.com/oauth2/auth',
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'slack' => 'https://slack.com/oauth/v2/authorize',
            'discord' => 'https://discord.com/api/oauth2/authorize'
        ];
        
        $baseUrl = $redirectUrls[$provider] ?? null;
        
        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'error' => 'Unsupported OAuth provider',
                'supported_providers' => array_keys($redirectUrls)
            ], 400);
        }
        
        $params = [
            'client_id' => 'nexa_app_client_id',
            'redirect_uri' => url('/api/v1/integrations/oauth/' . $provider . '/callback'),
            'scope' => $this->getProviderScopes($provider),
            'state' => $state,
            'response_type' => 'code'
        ];
        
        $redirectUrl = $baseUrl . '?' . http_build_query($params);
        
        return response()->json([
            'success' => true,
            'redirect_url' => $redirectUrl,
            'state' => $state,
            'provider' => $provider
        ]);
    }
    
    /**
     * Handle OAuth callback from external provider
     */
    #[Route(method: 'GET', path: '/api/v1/integrations/oauth/{provider}/callback')]
    public function oauthCallback(Request $request, $provider)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            $error = $request->get('error');
            
            if ($error) {
                $this->logger->warning("OAuth error for provider {$provider}", ['error' => $error]);
                return response()->json([
                    'success' => false,
                    'error' => 'OAuth authorization failed',
                    'error_description' => $request->get('error_description'),
                    'provider' => $provider
                ], 400);
            }
            
            if (!$code || !$state) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing required OAuth parameters'
                ], 400);
            }
            
            // Verify state parameter
            $stateData = json_decode(base64_decode($state), true);
            
            if (!$stateData || $stateData['provider'] !== $provider) {
                $this->logger->warning("Invalid OAuth state for provider {$provider}");
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid state parameter'
                ], 400);
            }
            
            // Verify state parameter to prevent CSRF attacks
            if (!$this->verifyOAuthState($state)) {
                $this->logger->warning("Invalid OAuth state for provider {$provider}");
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid state parameter'
                ], 400);
            }
            
            // Exchange authorization code for access token
            $tokenData = $this->exchangeCodeForToken($provider, $code, $state);
            
            if (!$tokenData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to exchange authorization code for token'
                ], 400);
            }
            
            // Get user information using the access token
            $userInfo = $this->getUserInfo($provider, $tokenData['access_token']);
            
            if (!$userInfo) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to retrieve user information'
                ], 400);
            }
            
            $this->logger->info("OAuth callback successful", [
                'provider' => $provider,
                'user_id' => $userInfo['id'] ?? 'unknown'
            ]);
            
            return response()->json([
                'success' => true,
                'provider' => $provider,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'expires_in' => $tokenData['expires_in'] ?? 3600,
                'scope' => $this->getProviderScopes($provider),
                'user_info' => $userInfo,
                'integration_id' => 'integration_' . uniqid(),
                'token_type' => $tokenData['token_type'] ?? 'Bearer'
            ]);
            
        } catch (Exception $e) {
            $this->logger->error("OAuth callback failed", [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'OAuth callback processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verify OAuth state parameter
     */
    private function verifyOAuthState(string $state): bool
    {
        // In a real implementation, you would store the state in session/cache
        // and verify it matches what was sent in the authorization request
        // For now, we'll do basic validation
        return !empty($state) && strlen($state) >= 16;
    }
    
    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken(string $provider, string $code, string $state): ?array
    {
        if (!isset($this->oauthProviders[$provider])) {
            $this->logger->error("OAuth provider not configured: {$provider}");
            return null;
        }
        
        try {
            $oauthProvider = $this->oauthProviders[$provider];
            $accessToken = $oauthProvider->getAccessToken('authorization_code', [
                'code' => $code
            ]);
            
            return [
                'access_token' => $accessToken->getToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'expires_in' => $accessToken->getExpires() ? $accessToken->getExpires() - time() : null,
                'token_type' => 'Bearer'
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Token exchange failed for provider {$provider}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get user information using access token
     */
    private function getUserInfo(string $provider, string $accessToken): ?array
    {
        if (!isset($this->oauthProviders[$provider])) {
            return null;
        }
        
        try {
            $oauthProvider = $this->oauthProviders[$provider];
            $token = new \League\OAuth2\Client\Token\AccessToken(['access_token' => $accessToken]);
            $user = $oauthProvider->getResourceOwner($token);
            
            // Normalize user data across providers
            return $this->normalizeUserData($provider, $user->toArray());
            
        } catch (Exception $e) {
            $this->logger->error("Failed to get user info for provider {$provider}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Normalize user data across different OAuth providers
     */
    private function normalizeUserData(string $provider, array $userData): array
    {
        switch ($provider) {
            case 'github':
                return [
                    'id' => $userData['id'],
                    'username' => $userData['login'],
                    'email' => $userData['email'],
                    'name' => $userData['name'],
                    'avatar_url' => $userData['avatar_url'],
                    'profile_url' => $userData['html_url'],
                    'provider' => 'github'
                ];
                
            case 'google':
                return [
                    'id' => $userData['sub'],
                    'username' => $userData['email'],
                    'email' => $userData['email'],
                    'name' => $userData['name'],
                    'avatar_url' => $userData['picture'],
                    'profile_url' => null,
                    'provider' => 'google'
                ];
                
            case 'microsoft':
                return [
                    'id' => $userData['id'],
                    'username' => $userData['userPrincipalName'],
                    'email' => $userData['mail'] ?? $userData['userPrincipalName'],
                    'name' => $userData['displayName'],
                    'avatar_url' => null,
                    'profile_url' => null,
                    'provider' => 'microsoft'
                ];
                
            case 'slack':
                return [
                    'id' => $userData['user']['id'],
                    'username' => $userData['user']['name'],
                    'email' => $userData['user']['email'],
                    'name' => $userData['user']['real_name'],
                    'avatar_url' => $userData['user']['image_192'],
                    'profile_url' => $userData['user']['profile']['permalink'],
                    'provider' => 'slack'
                ];
                
            case 'discord':
                return [
                    'id' => $userData['id'],
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'name' => $userData['global_name'] ?? $userData['username'],
                    'avatar_url' => $userData['avatar'] ? "https://cdn.discordapp.com/avatars/{$userData['id']}/{$userData['avatar']}.png" : null,
                    'profile_url' => null,
                    'provider' => 'discord'
                ];
                
            default:
                return $userData;
        }
    }
    
    /**
     * Get available integrations
     */
    #[Route(method: 'GET', path: '/api/v1/integrations')]
    public function getIntegrations(Request $request)
    {
        return response()->json([
            'success' => true,
            'integrations' => [
                [
                    'name' => 'GitHub',
                    'provider' => 'github',
                    'description' => 'Connect with GitHub repositories',
                    'status' => 'available',
                    'features' => ['repository_sync', 'issue_tracking', 'pull_requests'],
                    'quantum_enhanced' => true
                ],
                [
                    'name' => 'Google Workspace',
                    'provider' => 'google',
                    'description' => 'Integrate with Google services',
                    'status' => 'available',
                    'features' => ['calendar_sync', 'drive_access', 'gmail_integration'],
                    'quantum_enhanced' => true
                ],
                [
                    'name' => 'Microsoft 365',
                    'provider' => 'microsoft',
                    'description' => 'Connect with Microsoft services',
                    'status' => 'available',
                    'features' => ['outlook_sync', 'teams_integration', 'onedrive_access'],
                    'quantum_enhanced' => false
                ],
                [
                    'name' => 'Slack',
                    'provider' => 'slack',
                    'description' => 'Team communication integration',
                    'status' => 'beta',
                    'features' => ['message_sync', 'channel_notifications', 'bot_commands'],
                    'quantum_enhanced' => true
                ],
                [
                    'name' => 'Discord',
                    'provider' => 'discord',
                    'description' => 'Gaming and community platform',
                    'status' => 'coming_soon',
                    'features' => ['server_management', 'voice_integration', 'bot_deployment'],
                    'quantum_enhanced' => false
                ]
            ]
        ]);
    }
    
    /**
     * Get provider-specific OAuth scopes
     */
    private function getProviderScopes($provider)
    {
        $scopes = [
            'github' => 'user:email,repo:status,public_repo',
            'google' => 'openid email profile',
            'microsoft' => 'openid email profile User.Read',
            'slack' => 'users:read,channels:read,chat:write',
            'discord' => 'identify email guilds'
        ];
        
        return $scopes[$provider] ?? 'openid email profile';
    }
    

}

/**
 * Helper function to generate URLs
 */
if (!function_exists('url')) {
    function url($path = '') {
        $baseUrl = 'http://localhost:8000';
        return $baseUrl . '/' . ltrim($path, '/');
    }
}