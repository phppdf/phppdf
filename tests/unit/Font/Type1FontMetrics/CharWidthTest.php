<?php

declare(strict_types=1);

namespace Type1FontMetrics;

use PhpPdf\Font\Type1FontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type1FontMetrics::class)]
#[CoversMethod(Type1FontMetrics::class, 'charWidth')]
final class CharWidthTest extends TestCase
{
    #[Test]
    public function charWidthReturnsMappedWidth(): void
    {
        // Arrange — 'A' = code 65, Helvetica width = 667
        $metrics = Type1FontMetrics::helvetica();

        // Act
        $width = $metrics->charWidth(65);

        // Assert
        self::assertSame(667.0, $width);
    }

    #[Test]
    public function charWidthReturnsDefaultForUnmappedCode(): void
    {
        // Arrange — code 0x80 is not in the Helvetica table; default = 556
        $metrics = Type1FontMetrics::helvetica();

        // Act
        $width = $metrics->charWidth(0x80);

        // Assert — fallback to defaultWidth
        self::assertSame(556.0, $width);
    }

    #[Test]
    public function charWidthCourierAlwaysReturnsDefault(): void
    {
        // Arrange — Courier table is empty; all chars use default 600
        $metrics = Type1FontMetrics::courier();

        // Act / Assert
        self::assertSame(600.0, $metrics->charWidth(65));
        self::assertSame(600.0, $metrics->charWidth(32));
    }
}
