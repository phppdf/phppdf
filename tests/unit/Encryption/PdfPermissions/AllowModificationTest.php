<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'allowModification')]
final class AllowModificationTest extends TestCase
{
    #[Test]
    public function allowModificationReturnsNewInstance(): void
    {
        // Arrange
        $original = PdfPermissions::none();

        // Act
        $modified = $original->allowModification();

        // Assert
        self::assertNotSame($original, $modified);
    }

    #[Test]
    public function allowModificationDoesNotMutateOriginal(): void
    {
        // Arrange
        $original = PdfPermissions::none();
        $before = $original->toInt();

        // Act
        $original->allowModification();

        // Assert
        self::assertSame($before, $original->toInt());
    }

    #[Test]
    public function allowModificationSetsModifyBit(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::none()->allowModification();

        // Assert — 0xFFFFF0C0 | BIT_MODIFY(0x08) = signed -3896
        self::assertSame(-3896, $permissions->toInt());
    }
}
