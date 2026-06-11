<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfEncryptionContext;

use PhpPdf\Encryption\PdfEncryptionContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfEncryptionContext::class)]
#[CoversMethod(PdfEncryptionContext::class, 'encrypt')]
final class EncryptTest extends TestCase
{
    #[Test]
    public function encryptReturnsBinaryStringWithIvPrefix(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\xAB", 16));

        // Act
        $result = $context->encrypt('hello', 1, 0);

        // Assert — at least 16 bytes IV + 16 bytes AES block (empty → one padded block)
        self::assertGreaterThanOrEqual(32, strlen($result));
    }

    #[Test]
    public function encryptProducesDifferentCiphertextEachCall(): void
    {
        // Arrange — same plaintext, same object coordinates
        $context = new PdfEncryptionContext(str_repeat("\xAB", 16));

        // Act
        $first = $context->encrypt('hello', 1, 0);
        $second = $context->encrypt('hello', 1, 0);

        // Assert — random IV makes each encryption unique
        self::assertNotSame($first, $second);
    }

    #[Test]
    public function encryptEmptyStringProducesPaddedBlock(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\xCD", 16));

        // Act — AES-CBC pads empty input to one 16-byte block, plus 16-byte IV
        $result = $context->encrypt('', 2, 0);

        // Assert
        self::assertSame(32, strlen($result));
    }
}
