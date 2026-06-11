<?php

declare(strict_types=1);

namespace PhpPdf\Encryption;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;

/**
 * Implements the PDF Standard Security Handler, Revision 4 (V=4, R=4).
 *
 * Content is encrypted with AES-128-CBC. Key derivation and the O/U password
 * verification entries use RC4 as specified by ISO 32000-1 Algorithms 2–5.
 * RC4 is implemented in pure PHP so it is not subject to OpenSSL's legacy
 * cipher policy that disables RC4 in newer builds.
 *
 * Algorithm references are from ISO 32000-1, §7.6.3.
 */
final class PdfStandardSecurityHandler
{
    // ISO 32000-1 §7.6.3.3: standard 32-byte padding string.
    private const string PASSWORD_PADDING =
        "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08"
        . "\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";

    private const int KEY_LENGTH = 16; // 128-bit key

    private readonly string $ownerEntry;
    private readonly string $encryptionKey;
    private readonly string $userEntry;

    /** @param string $documentId 16 random bytes (first element of /ID array). */
    public function __construct(private readonly PdfEncryptionConfig $config, private readonly string $documentId)
    {
        $this->ownerEntry = $this->computeOwnerEntry();
        $this->encryptionKey = $this->computeEncryptionKey();
        $this->userEntry = $this->computeUserEntry();
    }

    /**
     * Builds the encryption dictionary that goes into the PDF as an indirect
     * object and is referenced from the trailer /Encrypt entry.
     */
    public function buildEncryptionDictionary(): PdfDictionary
    {
        $cfEntry = new PdfDictionary([
            'StdCF' => new PdfDictionary([
                'AuthEvent' => new PdfName('DocOpen'),
                'CFM' => new PdfName('AESV2'),
                'Length' => new PdfInteger(self::KEY_LENGTH),
            ]),
        ]);

        return new PdfDictionary([
            'CF' => $cfEntry,
            'Filter' => new PdfName('Standard'),
            'Length' => new PdfInteger(128), // key length in bits
            'O' => new PdfHexString($this->ownerEntry),
            'P' => new PdfInteger($this->config->getPermissions()->toInt()),
            'R' => new PdfInteger(4),
            'StmF' => new PdfName('StdCF'),
            'StrF' => new PdfName('StdCF'),
            'U' => new PdfHexString($this->userEntry),
            'V' => new PdfInteger(4),
        ]);
    }

    public function createEncryptionContext(): PdfEncryptionContext
    {
        return new PdfEncryptionContext($this->encryptionKey);
    }

    // -------------------------------------------------------------------------
    // Key derivation — ISO 32000-1 §7.6.3.3
    // -------------------------------------------------------------------------

    /**
     * Algorithm 2 (O entry): encrypts the padded user password with a key
     * derived from the owner password using 20 rounds of RC4.
     */
    private function computeOwnerEntry(): string
    {
        $ownerPad = $this->padPassword($this->config->getOwnerPassword());
        $digest = md5($ownerPad, true);

        for ($i = 0; $i < 50; $i++) {
            $digest = md5(substr($digest, 0, self::KEY_LENGTH), true);
        }

        $ownerKey = substr($digest, 0, self::KEY_LENGTH);
        $value = $this->padPassword($this->config->getUserPassword());

        for ($i = 0; $i <= 19; $i++) {
            $value = $this->rc4($this->xorKey($ownerKey, $i), $value);
        }

        return $value; // 32 bytes
    }

    /**
     * Algorithm 3 (encryption key): derived from user password, O entry,
     * permissions, and file ID.
     */
    private function computeEncryptionKey(): string
    {
        $userPad = $this->padPassword($this->config->getUserPassword());
        $P = $this->config->getPermissions()->toInt();

        $data = $userPad
            . $this->ownerEntry
            . pack('V', $P & 0xFFFFFFFF) // 4-byte little-endian unsigned
            . $this->documentId;

        $digest = md5($data, true);

        for ($i = 0; $i < 50; $i++) {
            $digest = md5(substr($digest, 0, self::KEY_LENGTH), true);
        }

        return substr($digest, 0, self::KEY_LENGTH);
    }

    /**
     * Algorithm 5 (U entry, R=3): encrypts the standard padding string
     * combined with the file ID using 20 rounds of RC4.
     */
    private function computeUserEntry(): string
    {
        $hash = md5(self::PASSWORD_PADDING . $this->documentId, true);
        $value = $this->rc4($this->encryptionKey, $hash);

        for ($i = 1; $i <= 19; $i++) {
            $value = $this->rc4($this->xorKey($this->encryptionKey, $i), $value);
        }

        return $value . str_repeat("\x00", 16); // pad to 32 bytes
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function padPassword(string $password): string
    {
        return substr($password . self::PASSWORD_PADDING, 0, 32);
    }

    /** XORs every byte of $key with $byte. */
    private function xorKey(string $key, int $byte): string
    {
        $result = '';

        for ($i = 0, $len = strlen($key); $i < $len; $i++) {
            $result .= chr((ord($key[$i]) ^ $byte) & 0xFF);
        }

        return $result;
    }

    /** Pure-PHP RC4 — avoids dependency on OpenSSL legacy cipher support. */
    private function rc4(string $key, string $data): string
    {
        $keyLen = strlen($key);
        $S = range(0, 255);
        $j = 0;

        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $S[$i] + ord($key[$i % $keyLen])) & 0xFF;
            [$S[$i], $S[$j]] = [$S[$j], $S[$i]];
        }

        $out = '';
        $i = 0;
        $j = 0;
        $len = strlen($data);

        for ($k = 0; $k < $len; $k++) {
            $i = $i + 1 & 0xFF;
            $j = $j + $S[$i] & 0xFF;
            [$S[$i], $S[$j]] = [$S[$j], $S[$i]];
            $out .= chr((ord($data[$k]) ^ $S[$S[$i] + $S[$j] & 0xFF]) & 0xFF);
        }

        return $out;
    }
}
