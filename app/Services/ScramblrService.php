<?php

namespace App\Services;

use Exception;
use RuntimeException;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
class ScramblrService {
    public int $expiration = 30 * 60;
    private string $_key;

    public function __construct() {
        $configured = (string) config('services.scramblr.key', '');
        $decoded = base64_decode($configured, true);
        $this->_key = $decoded === false ? $configured : $decoded;

        if ($this->_key === '') {
            /*
             * A random key per process silently breaks every image: the page
             * renders /blob/{token} scrambled with key A, then a different
             * PHP-FPM worker fails to decrypt it with key B and answers 401.
             * That turned a missing SCRAMBLR_KEY into "images never load",
             * so refuse to start a production worker without a real key.
             */
            if (app()->environment('production')) {
                throw new RuntimeException(
                    'SCRAMBLR_KEY is not set. Image tokens cannot be verified across requests; '
                    .'set it with: fly secrets set SCRAMBLR_KEY=<base64 key>'
                );
            }

            $this->_key = random_bytes(32);
        }
    }

    public function Scramble(string $text, ?string $key = null): string {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES);

        return sodium_bin2base64(
            $nonce .
            sodium_crypto_aead_chacha20poly1305_encrypt(
                pack('J', time() + $this->expiration) . $text,
                '',
                $nonce,
                hash('sha256', $key ?? $this->_key, true)
            ),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );
    }

    public function Descramble(string $ciphertext, ?string $key = null): array {
        $bin = sodium_base642bin($ciphertext, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        $nonce = substr($bin, 0, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES);

        $decrypted = sodium_crypto_aead_chacha20poly1305_decrypt(
            substr($bin, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_NPUBBYTES),
            '',
            $nonce,
            hash('sha256', $key ?? $this->_key, true)
        );

        $decrypted == false ? throw new Exception('Decryption failed!') : 0;

        return [
            unpack('J', substr($decrypted, 0, 8))[1] > time(),
            substr($decrypted, 8)
        ];
    }
}
