<?php

namespace App\Infrastructure\Pii;

use SodiumException;

final class ProtectorTelefono
{
    private const FORMATO = 1;

    public function canonico(string $telefono): string
    {
        $digitos = preg_replace('/\D/', '', trim($telefono));

        return is_string($digitos) ? $digitos : '';
    }

    /**
     * @return array{cifrado: string, hmac: string, ultimos4: string, version_clave: int}
     */
    public function protegerTelefono(string $valor, string $contexto): array
    {
        $canonico = $this->exigirCanonico($valor);
        $version = $this->versionActual();

        return [
            'cifrado' => $this->cifrar($canonico, $contexto, $version),
            'hmac' => $this->hmacDeCanonico($canonico, $version),
            'ultimos4' => substr($canonico, -4),
            'version_clave' => $version,
        ];
    }

    public function revelarTelefono(string $cifrado, int $version, string $contexto): string
    {
        $this->exigirSodium();

        if (strlen($cifrado) < 1 + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES + 16) {
            throw DatoPiiException::failClosed();
        }

        if (ord($cifrado[0]) !== self::FORMATO) {
            throw DatoPiiException::failClosed();
        }

        $nonce = substr($cifrado, 1, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = substr($cifrado, 1 + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $clave = $this->clave('encryption_keys', $version);

        try {
            try {
                $plano = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
                    $ciphertext,
                    $this->aad($contexto, $version),
                    $nonce,
                    $clave,
                );
            } catch (SodiumException) {
                $plano = false;
            }
        } finally {
            sodium_memzero($clave);
        }

        if (! is_string($plano)) {
            throw DatoPiiException::failClosed();
        }

        return $plano;
    }

    public function hmacTelefono(string $valor, int $version): string
    {
        return $this->hmacDeCanonico($this->exigirCanonico($valor), $version);
    }

    private function exigirCanonico(string $valor): string
    {
        $canonico = $this->canonico($valor);

        if (strlen($canonico) < 4) {
            throw new TelefonoInsuficienteException;
        }

        return $canonico;
    }

    private function cifrar(string $canonico, string $contexto, int $version): string
    {
        $this->exigirSodium();
        $clave = $this->clave('encryption_keys', $version);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        try {
            try {
                $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
                    $canonico,
                    $this->aad($contexto, $version),
                    $nonce,
                    $clave,
                );
            } catch (SodiumException) {
                throw DatoPiiException::failClosed();
            }
        } finally {
            sodium_memzero($clave);
        }

        return chr(self::FORMATO).$nonce.$ciphertext;
    }

    private function hmacDeCanonico(string $canonico, int $version): string
    {
        $clave = $this->clave('hmac_keys', $version);

        try {
            return hash_hmac('sha256', $canonico, $clave, true);
        } finally {
            sodium_memzero($clave);
        }
    }

    private function aad(string $contexto, int $version): string
    {
        return $contexto.':v'.$version;
    }

    private function versionActual(): int
    {
        $version = config('credimex.pii.key_version');

        if (is_string($version) && ctype_digit($version)) {
            $version = (int) $version;
        }

        if (! is_int($version) || $version < 1) {
            throw DatoPiiException::failClosed();
        }

        return $version;
    }

    private function clave(string $mapa, int $version): string
    {
        if ($version < 1) {
            throw DatoPiiException::failClosed();
        }

        $claves = config('credimex.pii.'.$mapa);
        $codificada = is_array($claves) ? ($claves[$version] ?? null) : null;

        if (! is_string($codificada) || $codificada === '') {
            throw DatoPiiException::failClosed();
        }

        $binaria = base64_decode($codificada, true);

        if (! is_string($binaria) || strlen($binaria) !== 32) {
            throw DatoPiiException::failClosed();
        }

        return $binaria;
    }

    private function exigirSodium(): void
    {
        if (! function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')
            || ! function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt')) {
            throw DatoPiiException::failClosed();
        }
    }
}
