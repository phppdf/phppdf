<?php

declare(strict_types=1);

namespace PhpPdf\Encryption;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfString;

/**
 * Authenticates a password against a Standard Security Handler (V=4, R=4)
 * encryption dictionary and derives the AES-128 decryption key.
 *
 * Tries the supplied string first as the user password (Algorithm 6), then
 * as the owner password (Algorithm 7). Returns a ready-to-use
 * PdfEncryptionContext on success or null when authentication fails.
 *
 * Algorithm references: ISO 32000-1, §7.6.3.
 */
final class PdfDecryptionHandler
{
    // ISO 32000-1 §7.6.3.3: standard 32-byte padding string.
    private const string PASSWORD_PADDING =
        "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08"
        . "\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";

    private const int KEY_LENGTH = 16;

    /** @param string $fileId First element of the /ID array, raw binary (16 bytes). */
    public static function authenticate(
        PdfDictionary $encryptDict,
        string $fileId,
        string $password,
    ): ?PdfEncryptionContext {
        $O = self::binaryEntry($encryptDict, 'O');
        $U = self::binaryEntry($encryptDict, 'U');
        $P = self::intEntry($encryptDict, 'P');

        if ($O === null || $U === null || $P === null) {
            return null;
        }

        // Algorithm 6: try as user password.
        $key = self::computeEncryptionKey($password, $O, $P, $fileId);

        if (self::verifyUserPassword($key, $U, $fileId)) {
            return new PdfEncryptionContext($key);
        }

        // Algorithm 7: try as owner password — decrypt /O to recover user password.
        $recoveredUser = self::recoverUserPassword($password, $O);
        $key = self::computeEncryptionKey($recoveredUser, $O, $P, $fileId);

        if (self::verifyUserPassword($key, $U, $fileId)) {
            return new PdfEncryptionContext($key);
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // ISO 32000-1 §7.6.3.3 — key derivation algorithms
    // -------------------------------------------------------------------------

    /**
     * Algorithm 3: compute 128-bit encryption key.
     * Identical to PdfStandardSecurityHandler::computeEncryptionKey() on the write side.
     */
    private static function computeEncryptionKey(string $password, string $O, int $P, string $fileId): string
    {
        $data = self::padPassword($password)
            . $O
            . pack('V', $P & 0xFFFFFFFF)
            . $fileId;

        $digest = md5($data, true);

        for ($i = 0; $i < 50; $i++) {
            $digest = md5(substr($digest, 0, self::KEY_LENGTH), true);
        }

        return substr($digest, 0, self::KEY_LENGTH);
    }

    /**
     * Algorithm 6: verify that $key produces a U entry matching the stored one.
     * For R=3/4 only the first 16 bytes of U are significant.
     */
    private static function verifyUserPassword(string $key, string $U, string $fileId): bool
    {
        $hash = md5(self::PASSWORD_PADDING . $fileId, true);
        $value = self::rc4($key, $hash);

        for ($i = 1; $i <= 19; $i++) {
            $value = self::rc4(self::xorKey($key, $i), $value);
        }

        return substr($value, 0, 16) === substr($U, 0, 16);
    }

    /**
     * Algorithm 7 step b: derive owner key from owner password, then reverse-RC4
     * the /O entry 20 times to recover the padded user password.
     */
    private static function recoverUserPassword(string $ownerPassword, string $O): string
    {
        $digest = md5(self::padPassword($ownerPassword), true);

        for ($i = 0; $i < 50; $i++) {
            $digest = md5(substr($digest, 0, self::KEY_LENGTH), true);
        }

        $ownerKey = substr($digest, 0, self::KEY_LENGTH);

        $value = $O;

        for ($i = 19; $i >= 0; $i--) {
            $value = self::rc4(self::xorKey($ownerKey, $i), $value);
        }

        return $value; // 32-byte padded user password
    }

    // -------------------------------------------------------------------------
    // Helpers shared with PdfStandardSecurityHandler
    // -------------------------------------------------------------------------

    private static function padPassword(string $password): string
    {
        return substr($password . self::PASSWORD_PADDING, 0, 32);
    }

    private static function xorKey(string $key, int $byte): string
    {
        $result = '';

        for ($i = 0, $len = strlen($key); $i < $len; $i++) {
            $result .= chr((ord($key[$i]) ^ $byte) & 0xFF);
        }

        return $result;
    }

    private static function rc4(string $key, string $data): string
    {
        $keyLen = strlen($key);
        $S = range(0, 255);
        $j = 0;

        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $S[$i] + ord($key[$i % $keyLen])) & 0xFF;
            [$S[$i], $S[$j]] = [$S[$j], $S[$i]];
        }

        $out = '';
        $i = $j = 0;

        for ($k = 0, $len = strlen($data); $k < $len; $k++) {
            $i = $i + 1 & 0xFF;
            $j = $j + $S[$i] & 0xFF;
            [$S[$i], $S[$j]] = [$S[$j], $S[$i]];
            $out .= chr((ord($data[$k]) ^ $S[$S[$i] + $S[$j] & 0xFF]) & 0xFF);
        }

        return $out;
    }

    private static function binaryEntry(PdfDictionary $dict, string $key): ?string
    {
        $val = $dict->get($key);

        return $val instanceof PdfString
            ? $val->getValue()
            : null;
    }

    private static function intEntry(PdfDictionary $dict, string $key): ?int
    {
        $val = $dict->get($key);

        return $val instanceof PdfInteger
            ? $val->getValue()
            : null;
    }
}
