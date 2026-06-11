<?php

declare(strict_types=1);

namespace TrueTypeFontMetrics;

use PhpPdf\Font\MinimalFontBuilder;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeFontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrueTypeFontMetrics::class)]
#[CoversMethod(TrueTypeFontMetrics::class, 'stringWidth')]
#[UsesClass(TrueTypeFont::class)]
final class StringWidthTest extends TestCase
{
    #[Test]
    public function stringWidthOfEmptyStringIsZero(): void
    {
        // Arrange
        $font = TrueTypeFont::fromData(MinimalFontBuilder::build());
        $metrics = TrueTypeFontMetrics::fromFont($font);

        // Act / Assert
        self::assertSame(0.0, $metrics->stringWidth(''));
    }

    #[Test]
    public function stringWidthSumsCharWidths(): void
    {
        // Arrange
        $font = TrueTypeFont::fromData(MinimalFontBuilder::build());
        $metrics = TrueTypeFontMetrics::fromFont($font);

        // Act
        $single = $metrics->charWidth(65); // 'A'
        $double = $metrics->stringWidth('AA');

        // Assert
        self::assertEqualsWithDelta($single * 2, $double, 0.001);
    }
}
