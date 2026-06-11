<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfEncryptionContext;

use PhpPdf\Encryption\PdfEncryptionContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfEncryptionContext::class)]
#[CoversMethod(PdfEncryptionContext::class, 'setEncryptDictObjectNumber')]
final class SetEncryptDictObjectNumberTest extends TestCase
{
    #[Test]
    public function setEncryptDictObjectNumberExemptsObjectFromEncryption(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\x00", 16));

        // Act
        $context->setEncryptDictObjectNumber(3);

        // Assert
        self::assertFalse($context->shouldEncryptObject(3));
    }

    #[Test]
    public function setEncryptDictObjectNumberDoesNotExemptOtherObjects(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\x00", 16));

        // Act
        $context->setEncryptDictObjectNumber(3);

        // Assert
        self::assertTrue($context->shouldEncryptObject(4));
    }
}
