<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'allowCopying')]
final class AllowCopyingTest extends TestCase
{
    #[Test]
    public function allowCopyingReturnsNewInstance(): void
    {
        // Arrange
        $original = PdfPermissions::none();

        // Act
        $modified = $original->allowCopying();

        // Assert
        self::assertNotSame($original, $modified);
    }

    #[Test]
    public function allowCopyingDoesNotMutateOriginal(): void
    {
        // Arrange
        $original = PdfPermissions::none();
        $before = $original->toInt();

        // Act
        $original->allowCopying();

        // Assert
        self::assertSame($before, $original->toInt());
    }

    #[Test]
    public function allowCopyingSetsCopyAndAccessibilityBits(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::none()->allowCopying();

        // Assert — 0xFFFFF0C0 | BIT_COPY(0x10) | BIT_ACCESSIBILITY(0x200) = signed -3376
        self::assertSame(-3376, $permissions->toInt());
    }
}
