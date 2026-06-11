<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfEncryptionContext;

use PhpPdf\Encryption\PdfEncryptionContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfEncryptionContext::class)]
#[CoversMethod(PdfEncryptionContext::class, 'shouldEncryptObject')]
final class ShouldEncryptObjectTest extends TestCase
{
    #[Test]
    public function shouldEncryptObjectReturnsFalseForObjectNumberZero(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\x00", 16));

        // Act
        $result = $context->shouldEncryptObject(0);

        // Assert
        self::assertFalse($result);
    }

    #[Test]
    public function shouldEncryptObjectReturnsTrueForNormalObjectNumber(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\x00", 16));

        // Act
        $result = $context->shouldEncryptObject(1);

        // Assert
        self::assertTrue($result);
    }

    #[Test]
    public function shouldEncryptObjectReturnsFalseForEncryptDictObjectNumber(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\x00", 16));
        $context->setEncryptDictObjectNumber(5);

        // Act
        $result = $context->shouldEncryptObject(5);

        // Assert
        self::assertFalse($result);
    }

    #[Test]
    public function shouldEncryptObjectReturnsTrueForObjectsOtherThanEncryptDict(): void
    {
        // Arrange
        $context = new PdfEncryptionContext(str_repeat("\x00", 16));
        $context->setEncryptDictObjectNumber(5);

        // Act
        $result = $context->shouldEncryptObject(6);

        // Assert
        self::assertTrue($result);
    }
}
