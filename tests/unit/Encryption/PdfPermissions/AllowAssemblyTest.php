<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'allowAssembly')]
final class AllowAssemblyTest extends TestCase
{
    #[Test]
    public function allowAssemblyReturnsNewInstance(): void
    {
        // Arrange
        $original = PdfPermissions::none();

        // Act
        $modified = $original->allowAssembly();

        // Assert
        self::assertNotSame($original, $modified);
    }

    #[Test]
    public function allowAssemblyDoesNotMutateOriginal(): void
    {
        // Arrange
        $original = PdfPermissions::none();
        $before = $original->toInt();

        // Act
        $original->allowAssembly();

        // Assert
        self::assertSame($before, $original->toInt());
    }

    #[Test]
    public function allowAssemblySetsAssemblyBit(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::none()->allowAssembly();

        // Assert — 0xFFFFF0C0 | BIT_ASSEMBLE(0x400) = signed -2880
        self::assertSame(-2880, $permissions->toInt());
    }
}
