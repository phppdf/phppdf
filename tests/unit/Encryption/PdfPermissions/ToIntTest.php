<?php

declare(strict_types=1);

namespace PhpPdf\Encryption\PdfPermissions;

use PhpPdf\Encryption\PdfPermissions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPermissions::class)]
#[CoversMethod(PdfPermissions::class, 'toInt')]
final class ToIntTest extends TestCase
{
    #[Test]
    public function toIntReturnsSignedInteger(): void
    {
        // Arrange / Act
        $value = PdfPermissions::none()->toInt();

        // Assert — required bits (0xFFFFF0C0) produce a negative signed int
        self::assertLessThan(0, $value);
    }

    #[Test]
    public function toIntAllPermissionsIsNegativeFour(): void
    {
        // Arrange / Act — 0xFFFFFFFC signed = -4
        $value = PdfPermissions::all()->toInt();

        // Assert
        self::assertSame(-4, $value);
    }

    #[Test]
    public function toIntNonePermissionsIsNegative3904(): void
    {
        // Arrange / Act — 0xFFFFF0C0 signed = -3904
        $value = PdfPermissions::none()->toInt();

        // Assert
        self::assertSame(-3904, $value);
    }

    #[Test]
    public function toIntCombinedPermissionsReflectsAllSetBits(): void
    {
        // Arrange / Act — combine all individual allowX methods from none
        $value = PdfPermissions::none()
            ->allowPrinting()
            ->allowModification()
            ->allowCopying()
            ->allowAnnotations()
            ->allowFormFilling()
            ->allowAssembly()
            ->toInt();

        // Assert — equals all() value
        self::assertSame(PdfPermissions::all()->toInt(), $value);
    }
}
