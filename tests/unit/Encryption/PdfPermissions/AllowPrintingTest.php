<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'allowPrinting')]
final class AllowPrintingTest extends TestCase
{
    #[Test]
    public function allowPrintingReturnsNewInstance(): void
    {
        // Arrange
        $original = PdfPermissions::none();

        // Act
        $modified = $original->allowPrinting();

        // Assert
        self::assertNotSame($original, $modified);
    }

    #[Test]
    public function allowPrintingDoesNotMutateOriginal(): void
    {
        // Arrange
        $original = PdfPermissions::none();
        $before = $original->toInt();

        // Act
        $original->allowPrinting();

        // Assert
        self::assertSame($before, $original->toInt());
    }

    #[Test]
    public function allowPrintingHighQualitySetsHighQualityBit(): void
    {
        // Arrange / Act — high-quality is the default
        $permissions = PdfPermissions::none()->allowPrinting();

        // Assert — 0xFFFFF0C0 | BIT_PRINT(0x04) | BIT_PRINT_HQ(0x800) = signed -1852
        self::assertSame(-1852, $permissions->toInt());
    }

    #[Test]
    public function allowPrintingLowQualityOmitsHighQualityBit(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::none()->allowPrinting(false);

        // Assert — 0xFFFFF0C0 | BIT_PRINT(0x04) = signed -3900
        self::assertSame(-3900, $permissions->toInt());
    }
}
