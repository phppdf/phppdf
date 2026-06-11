<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'allowAnnotations')]
final class AllowAnnotationsTest extends TestCase
{
    #[Test]
    public function allowAnnotationsReturnsNewInstance(): void
    {
        // Arrange
        $original = PdfPermissions::none();

        // Act
        $modified = $original->allowAnnotations();

        // Assert
        self::assertNotSame($original, $modified);
    }

    #[Test]
    public function allowAnnotationsDoesNotMutateOriginal(): void
    {
        // Arrange
        $original = PdfPermissions::none();
        $before = $original->toInt();

        // Act
        $original->allowAnnotations();

        // Assert
        self::assertSame($before, $original->toInt());
    }

    #[Test]
    public function allowAnnotationsSetsAnnotateBit(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::none()->allowAnnotations();

        // Assert — 0xFFFFF0C0 | BIT_ANNOTATE(0x20) = signed -3872
        self::assertSame(-3872, $permissions->toInt());
    }
}
