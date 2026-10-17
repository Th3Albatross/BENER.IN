<?php

namespace App\Services;

use Exception;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
class ScramblrService {
    private int $_expiration = 30 * 60;
    private string $_key;

    public function __construct() {
        $this->_key = base64_decode(config('services.scramblr.key'));
        $this->_key = !$this->_key ? random_bytes(32) : $this->_key;
    }

    public function Scramble(string $text, string $key = null): string {
        $nonce = random_bytes(16);

        return sodium_bin2base64(
            $nonce .
            openssl_encrypt(
                $text,
                'aes-128-ctr',
                $key ?? base64_decode($this->_key),
                OPENSSL_RAW_DATA,
                $nonce,
            ),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );
    }

    public function Descramble(string $ciphertext, string $key = null): string {
        $bin = sodium_base642bin($ciphertext, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);

        $nonce = substr($bin, 0, 16);
        return openssl_decrypt(
            substr($bin, 16),
            'aes-128-ctr',
            $key ?? base64_decode($this->_key),
            OPENSSL_RAW_DATA,
            $nonce,
        );
    }

    public function ScrambleV2(string $text, ?string $key = null): string {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES);

        return sodium_bin2base64(
            $nonce .
            sodium_crypto_aead_chacha20poly1305_encrypt(
                pack('J', time() + $this->_expiration) . $text,
                '',
                $nonce,
                hash('sha256', $key ?? $this->_key, true)
            ),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );
    }

    public function DescrambleV2(string $ciphertext, ?string $key = null): array {
        $bin = sodium_base642bin($ciphertext, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        $nonce = substr($bin, 0, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES);

        $decrypted = sodium_crypto_aead_chacha20poly1305_decrypt(
            substr($bin, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES),
            '',
            $nonce,
            hash('sha256', $key ?? $this->_key, true)
        );

        $decrypted == null ? throw new Exception('Decryption failed!') : 0;

        return [
            unpack('J', substr($decrypted, 0, 8))[1] > time(),
            substr($decrypted, 8)
        ];
    }
}
