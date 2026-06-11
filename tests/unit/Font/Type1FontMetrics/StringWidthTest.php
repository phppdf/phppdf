<?php

declare(strict_types=1);

namespace Type1FontMetrics;

use PhpPdf\Font\Type1FontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type1FontMetrics::class)]
#[CoversMethod(Type1FontMetrics::class, 'stringWidth')]
final class StringWidthTest extends TestCase
{
    #[Test]
    public function stringWidthSumsCharacterWidths(): void
    {
        // Arrange — "Hi" in Helvetica: H=722, i=222 → total 944
        $metrics = Type1FontMetrics::helvetica();

        // Act
        $width = $metrics->stringWidth('Hi');

        // Assert
        self::assertSame(944.0, $width);
    }

    #[Test]
    public function stringWidthEmptyStringReturnsZero(): void
    {
        $metrics = Type1FontMetrics::helvetica();

        self::assertSame(0.0, $metrics->stringWidth(''));
    }

    #[Test]
    public function stringWidthConvertsUtf8ToWindows1252(): void
    {
        // Arrange — em dash (U+2014) maps to Windows-1252 byte 0x97 (em dash),
        // which has width 1000 in the Helvetica table
        $metrics = Type1FontMetrics::helvetica();

        // Act
        $width = $metrics->stringWidth("\u{2014}");

        // Assert
        self::assertSame(1000.0, $width);
    }
}
