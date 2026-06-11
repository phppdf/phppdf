<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfEncryptionContext;

use PhpPdf\Encryption\PdfEncryptionContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfEncryptionContext::class)]
#[CoversMethod(PdfEncryptionContext::class, 'decrypt')]
final class DecryptTest extends TestCase
{
    #[Test]
    public function decryptRevertsEncrypt(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\xAB", 16));
        $plaintext = 'Hello, PDF!';

        // Act
        $ciphertext = $context->encrypt($plaintext, 1, 0);
        $recovered = $context->decrypt($ciphertext, 1, 0);

        // Assert
        self::assertSame($plaintext, $recovered);
    }

    #[Test]
    public function decryptReturnsEmptyStringForInputShorterThan17Bytes(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\xAB", 16));

        // Act — 16 bytes is one byte too short (needs at least IV + 1 byte)
        $result = $context->decrypt(str_repeat("\x00", 16), 1, 0);

        // Assert
        self::assertSame('', $result);
    }

    #[Test]
    public function decryptWithDifferentObjectNumberProducesWrongResult(): void
    {
        // Arrange — per-object key depends on object number
        $context = new PdfEncryptionContext(str_repeat("\xAB", 16));
        $plaintext = 'secret';

        // Act
        $ciphertext = $context->encrypt($plaintext, 1, 0);
        $recovered = $context->decrypt($ciphertext, 2, 0); // wrong object number

        // Assert
        self::assertNotSame($plaintext, $recovered);
    }

    #[Test]
    public function decryptEmptyStringRoundtrip(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\xCD", 16));

        // Act
        $ciphertext = $context->encrypt('', 3, 0);
        $recovered = $context->decrypt($ciphertext, 3, 0);

        // Assert
        self::assertSame('', $recovered);
    }
}
