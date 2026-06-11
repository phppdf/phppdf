<?php

declare(strict_types=1);

namespace PhpPdf\Encryption;

use RuntimeException;

/**
 * Encrypts individual PDF strings and streams using AES-128-CBC (V=4, R=4).
 *
 * Each value is encrypted with a per-object key derived from the document
 * encryption key combined with the indirect object number and generation
 * number. A random 16-byte IV is prepended to every ciphertext so the
 * serializer can write it directly as the encrypted value.
 *
 * The encryption dictionary's own indirect object is exempt from encryption;
 * call setEncryptDictObjectNumber() immediately after the dict is registered
 * so its O/U hex strings are written in plaintext.
 */
final class PdfEncryptionContext
{
    // PDF spec Table 20: append "sAlT" bytes to per-object key input for AES.
    private const AES_SALT = "\x73\x41\x6C\x54";

    private int $encryptDictObjectNumber = 0;

    public function __construct(
        private readonly string $encryptionKey, // 16 bytes
    ) {
    }

    public function setEncryptDictObjectNumber(int $objectNumber): void
    {
        $this->encryptDictObjectNumber = $objectNumber;
    }

    /**
     * Returns false for object number 0 (outside any indirect object) and
     * for the encryption dictionary itself.
     */
    public function shouldEncryptObject(int $objectNumber): bool
    {
        return $objectNumber > 0 && $objectNumber !== $this->encryptDictObjectNumber;
    }

    /**
     * Encrypts a string or stream with AES-128-CBC and returns IV + ciphertext.
     */
    public function encrypt(string $plaintext, int $objectNumber, int $generationNumber): string
    {
        $perObjectKey = $this->deriveObjectKey($objectNumber, $generationNumber);
        $iv = random_bytes(16);

        $ciphertext = openssl_encrypt(
            $plaintext,
            'AES-128-CBC',
            $perObjectKey,
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($ciphertext === false) {
            throw new RuntimeException('AES encryption failed: ' . openssl_error_string());
        }

        return $iv . $ciphertext;
    }

    /**
     * Decrypts AES-128-CBC ciphertext. The first 16 bytes of $ciphertext are
     * the IV prepended by encrypt(); the remainder is the actual ciphertext.
     * Returns an empty string when the input is too short to be valid.
     */
    public function decrypt(string $ciphertext, int $objectNumber, int $generationNumber): string
    {
        if (strlen($ciphertext) < 17) {
            return '';
        }
        $perObjectKey = $this->deriveObjectKey($objectNumber, $generationNumber);
        $iv = substr($ciphertext, 0, 16);
        $data = substr($ciphertext, 16);
        $plaintext = openssl_decrypt($data, 'AES-128-CBC', $perObjectKey, OPENSSL_RAW_DATA, $iv);
        return $plaintext !== false ? $plaintext : '';
    }

    private function deriveObjectKey(int $objectNumber, int $generationNumber): string
    {
        // ISO 32000-1 §7.6.3.3 Algorithm 1 (extended for AES).
        $keyInput = $this->encryptionKey
            . substr(pack('V', $objectNumber), 0, 3)   // obj num, 3 bytes LE
            . substr(pack('V', $generationNumber), 0, 2) // gen num, 2 bytes LE
            . self::AES_SALT;

        return substr(md5($keyInput, true), 0, 16);
    }
}
