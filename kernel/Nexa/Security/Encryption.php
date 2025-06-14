<?php

namespace Nexa\Security;

/**
 * Encryption class for Nexa Framework
 * Provides secure encryption and decryption functionality
 */
class Encryption
{
    private string $key;
    private string $cipher = 'AES-256-CBC';

    public function __construct(string $key = null)
    {
        $this->key = $key ?? $this->generateKey();
    }

    /**
     * Encrypt data
     */
    public function encrypt(string $data): string
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     */
    public function decrypt(string $encryptedData): string
    {
        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, $this->cipher, $this->key, 0, $iv);
    }

    /**
     * Generate encryption key
     */
    public function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Hash data
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Verify hash
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return hash_equals($hash, $this->hash($data));
    }
}