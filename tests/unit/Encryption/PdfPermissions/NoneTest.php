<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'none')]
final class NoneTest extends TestCase
{
    #[Test]
    public function noneReturnsPdfPermissionsInstance(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::none();

        // Assert
        self::assertInstanceOf(PdfPermissions::class, $permissions);
    }

    #[Test]
    public function noneProducesExpectedSignedInt(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::none();

        // Assert — 0xFFFFF0C0 converted to signed 32-bit = -3904
        self::assertSame(-3904, $permissions->toInt());
    }

    #[Test]
    public function noneReturnsDifferentInstancesOnEachCall(): void
    {
        // Arrange / Act
        $a = PdfPermissions::none();
        $b = PdfPermissions::none();

        // Assert
        self::assertNotSame($a, $b);
    }
}
