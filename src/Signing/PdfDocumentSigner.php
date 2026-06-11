<?php

declare(strict_types=1);

namespace PhpPdf\Signing;

use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use RuntimeException;

use const OPENSSL_CMS_BINARY;
use const OPENSSL_CMS_DETACHED;
use const OPENSSL_ENCODING_DER;

/**
 * Signs a PDF that was prepared with PdfDocumentBuilder::prepareForSigning().
 *
 * PDF signing is a two-pass process:
 *   1. The builder writes fixed-size ByteRange and Contents placeholders.
 *   2. This class locates those placeholders, computes the real byte offsets,
 *      signs the covered bytes with PKCS#7 (CMS detached), and patches both
 *      placeholders in place.
 *
 * Because the ByteRange placeholder uses zero-padded 10-digit numbers and the
 * Contents placeholder is a fixed-size hex string, the replacement never
 * changes the file length, so byte offsets remain stable throughout.
 *
 * Example:
 *
 *   $signer = new PdfDocumentSigner(
 *       openssl_x509_read($certPem),
 *       openssl_pkey_get_private($keyPem),
 *   );
 *   $signedBytes = $signer->sign($output->getContent());
 */
final class PdfDocumentSigner
{
    /**
     * Number of bytes reserved for the PKCS#7 signature in the Contents field.
     * Must match PdfSignatureConfig::RESERVED_BYTES.
     */
    public const SIGNATURE_RESERVED_BYTES = PdfSignatureConfig::RESERVED_BYTES;

    private const string BYTERANGE_PLACEHOLDER = '[0 0000000000 0000000000 0000000000]';
    private const string CONTENTS_MARKER = '/Contents <';

    public function __construct(
        private readonly OpenSSLCertificate $cert,
        private readonly OpenSSLAsymmetricKey $privateKey,
    ) {
    }

    /**
     * Locates the signature placeholders, computes PKCS#7, and returns the
     * signed PDF as a byte string.
     *
     * @throws \RuntimeException if the PDF has no signature placeholder, the
     *                          signature is too large for the reserved space,
     *                          or the OpenSSL signing call fails.
     */
    public function sign(string $pdfBytes): string
    {
        $markerLen = strlen(self::CONTENTS_MARKER);
        $markerPos = strpos($pdfBytes, self::CONTENTS_MARKER);

        if ($markerPos === false) {
            throw new RuntimeException(
                'No signature placeholder found. Call PdfDocumentBuilder::prepareForSigning() before serializing.',
            );
        }

        // Byte offset of the '<' that opens the Contents hex string.
        $angleOpenPos = $markerPos + $markerLen - 1;
        // Byte offset of the '>' that closes it.
        $hexLength = self::SIGNATURE_RESERVED_BYTES * 2;
        $angleClosePos = $angleOpenPos + 1 + $hexLength;
        $afterClosePos = $angleClosePos + 1;
        $totalLength = strlen($pdfBytes);

        // ByteRange covers everything except the <hex> value itself.
        $byteRange = sprintf('[0 %010d %010d %010d]', $angleOpenPos, $afterClosePos, $totalLength - $afterClosePos);

        // Replace the placeholder with real values. Length is identical
        // (10-digit zero-padded integers), so no byte offsets shift.
        $pdfBytes = str_replace(self::BYTERANGE_PLACEHOLDER, $byteRange, $pdfBytes);

        // Sign the two byte ranges defined above (using the updated file).
        $dataToSign = substr($pdfBytes, 0, $angleOpenPos)
            . substr($pdfBytes, $afterClosePos);

        $signature = $this->computePkcs7Signature($dataToSign);

        $sigLen = strlen($signature);

        // @codeCoverageIgnoreStart
        // A real PKCS#7 signature for a typical certificate chain is 1–3 KB,
        // well under the 8192-byte reserve. Triggering this in a unit test
        // would require generating an unusually large certificate chain.
        if ($sigLen > self::SIGNATURE_RESERVED_BYTES) {
            throw new RuntimeException(sprintf(
                'Signature (%d bytes) exceeds the %d bytes reserved in the Contents field.',
                $sigLen,
                self::SIGNATURE_RESERVED_BYTES,
            ));
        }

        // @codeCoverageIgnoreEnd

        // Hex-encode and zero-pad to exactly fill the reserved space.
        $sigHex = strtoupper(bin2hex($signature))
            . str_repeat('0', (self::SIGNATURE_RESERVED_BYTES - $sigLen) * 2);

        // Patch Contents: replace the hex placeholder (starts one byte after '<').
        return substr_replace($pdfBytes, $sigHex, $angleOpenPos + 1, $hexLength);
    }

    private function computePkcs7Signature(string $data): string
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'phppdf_in_');
        $outputFile = tempnam(sys_get_temp_dir(), 'phppdf_out_');

        try {
            file_put_contents($inputFile, $data);

            $ok = @openssl_cms_sign(
                $inputFile,
                $outputFile,
                $this->cert,
                $this->privateKey,
                [],
                OPENSSL_CMS_DETACHED | OPENSSL_CMS_BINARY,
                OPENSSL_ENCODING_DER,
            );

            if (!$ok) {
                throw new RuntimeException('openssl_cms_sign failed: ' . openssl_error_string());
            }

            $signature = file_get_contents($outputFile);

            if ($signature === false) {
                throw new RuntimeException('Failed to read PKCS#7 signature output.');
            }

            return $signature;
        } finally {
            @unlink($inputFile);
            @unlink($outputFile);
        }
    }
}
