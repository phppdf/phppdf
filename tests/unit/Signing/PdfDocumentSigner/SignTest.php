<?php

declare(strict_types=1);

namespace PhpPdf\Signing\PdfDocumentSigner;

use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use OpenSSLCertificateSigningRequest;
use PhpPdf\Signing\PdfDocumentSigner;
use PhpPdf\Signing\PdfSignatureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use const OPENSSL_KEYTYPE_RSA;

#[CoversClass(PdfDocumentSigner::class)]
#[CoversMethod(PdfDocumentSigner::class, 'sign')]
#[UsesClass(PdfSignatureConfig::class)]
final class SignTest extends TestCase
{
    private OpenSSLCertificate $cert;
    private OpenSSLAsymmetricKey $privateKey;

    #[Test]
    public function signatureReservedBytesIsConsistentWithPdfSignatureConfig(): void
    {
        self::assertSame(PdfSignatureConfig::RESERVED_BYTES, PdfDocumentSigner::SIGNATURE_RESERVED_BYTES);
    }

    // =========================================================================
    // sign() — error paths
    // =========================================================================

    #[Test]
    public function signThrowsWhenNoPdfSignaturePlaceholderFound(): void
    {
        // Arrange
        $signer = new PdfDocumentSigner($this->cert, $this->privateKey);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No signature placeholder found');

        // Act — bytes contain no /Contents < marker
        $signer->sign('this is not a valid signed PDF');
    }

    #[Test]
    public function signThrowsWhenPkcs7SigningFails(): void
    {
        // Arrange — use a different private key than the one that signed the cert;
        // openssl_cms_sign will fail because the key does not match the certificate.
        $mismatchedKey = self::asAsymmetricKey(openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]));
        $signer = new PdfDocumentSigner($this->cert, $mismatchedKey);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('openssl_cms_sign failed');

        // Act
        $signer->sign(self::buildPlaceholderPdf());
    }

    // =========================================================================
    // sign() — happy path
    // =========================================================================

    #[Test]
    public function signReturnsOutputOfSameLengthAsInput(): void
    {
        // Arrange
        $signer = new PdfDocumentSigner($this->cert, $this->privateKey);
        $pdf = self::buildPlaceholderPdf();

        // Act
        $signed = $signer->sign($pdf);

        // Assert — patching is in-place; length must not change
        self::assertSame(strlen($pdf), strlen($signed));
    }

    #[Test]
    public function signReplacesZeroFilledContentsFieldWithRealSignature(): void
    {
        // Arrange
        $signer = new PdfDocumentSigner($this->cert, $this->privateKey);
        $pdf = self::buildPlaceholderPdf();

        // Act
        $signed = $signer->sign($pdf);

        // Assert — signed output differs from the placeholder input
        self::assertNotSame($pdf, $signed);
    }

    #[Test]
    public function signReplacesZeroFilledByteRangePlaceholderWithRealOffsets(): void
    {
        // Arrange
        $signer = new PdfDocumentSigner($this->cert, $this->privateKey);
        $pdf = self::buildPlaceholderPdf();

        // Act
        $signed = $signer->sign($pdf);

        // Assert — the zero-padded ByteRange placeholder is gone
        self::assertStringNotContainsString('[0 0000000000 0000000000 0000000000]', $signed);
    }

    #[Test]
    public function signedOutputContainsContentsMarker(): void
    {
        // Arrange
        $signer = new PdfDocumentSigner($this->cert, $this->privateKey);
        $pdf = self::buildPlaceholderPdf();

        // Act
        $signed = $signer->sign($pdf);

        // Assert — the /Contents < marker is preserved (only the hex payload changed)
        self::assertStringContainsString('/Contents <', $signed);
    }

    protected function setUp(): void
    {
        // 1024-bit key — small enough for a fast unit test.
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $this->privateKey = self::asAsymmetricKey(openssl_pkey_new($config));

        $privateKey = $this->privateKey;
        $csr = openssl_csr_new(['commonName' => 'PhpPdf Test Signer'], $privateKey);
        if (!$csr instanceof OpenSSLCertificateSigningRequest) {
            throw new RuntimeException('openssl_csr_new failed');
        }

        $cert = openssl_csr_sign($csr, null, $this->privateKey, 1, ['digest_alg' => 'sha256']);
        if (!$cert instanceof OpenSSLCertificate) {
            throw new RuntimeException('openssl_csr_sign failed');
        }
        $this->cert = $cert;
    }

    /**
     * Builds a minimal byte string that contains both placeholders expected
     * by PdfDocumentSigner::sign():
     *   • /Contents <{16384 hex zeros}>
     *   • [0 0000000000 0000000000 0000000000]
     */
    private static function buildPlaceholderPdf(): string
    {
        $hexPlaceholder = str_repeat('0', PdfDocumentSigner::SIGNATURE_RESERVED_BYTES * 2);

        return 'BEFORE /Contents <'
            . $hexPlaceholder
            . '> [0 0000000000 0000000000 0000000000] AFTER';
    }

    private static function asAsymmetricKey(mixed $value): OpenSSLAsymmetricKey
    {
        if (!$value instanceof OpenSSLAsymmetricKey) {
            throw new RuntimeException('openssl_pkey_new failed');
        }

        return $value;
    }
}
