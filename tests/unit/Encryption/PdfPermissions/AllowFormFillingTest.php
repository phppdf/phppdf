<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'allowFormFilling')]
final class AllowFormFillingTest extends TestCase
{
    #[Test]
    public function allowFormFillingReturnsNewInstance(): void
    {
        // Arrange
        $original = PdfPermissions::none();

        // Act
        $modified = $original->allowFormFilling();

        // Assert
        self::assertNotSame($original, $modified);
    }

    #[Test]
    public function allowFormFillingDoesNotMutateOriginal(): void
    {
        // Arrange
        $original = PdfPermissions::none();
        $before = $original->toInt();

        // Act
        $original->allowFormFilling();

        // Assert
        self::assertSame($before, $original->toInt());
    }

    #[Test]
    public function allowFormFillingSetFormsBit(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::none()->allowFormFilling();

        // Assert — 0xFFFFF0C0 | BIT_FORMS(0x100) = signed -3648
        self::assertSame(-3648, $permissions->toInt());
    }
}
