<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'all')]
final class AllTest extends TestCase
{
    #[Test]
    public function allReturnsPdfPermissionsInstance(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::all();

        // Assert
        self::assertInstanceOf(PdfPermissions::class, $permissions);
    }

    #[Test]
    public function allProducesExpectedSignedInt(): void
    {
        // Arrange / Act
        $permissions = PdfPermissions::all();

        // Assert — 0xFFFFFFFC converted to signed 32-bit = -4
        self::assertSame(-4, $permissions->toInt());
    }

    #[Test]
    public function allHasMorePermissionsThanNone(): void
    {
        // Arrange / Act
        $all = PdfPermissions::all()->toInt();
        $none = PdfPermissions::none()->toInt();

        // Assert — all() sets more bits, making its value less negative
        self::assertGreaterThan($none, $all);
    }
}
