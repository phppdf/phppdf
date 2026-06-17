<?php

declare(strict_types=1);

namespace PhpPdf\Font\TrueTypeFontMetrics;

use PhpPdf\Font\MinimalFontBuilder;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeFontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrueTypeFontMetrics::class)]
#[CoversMethod(TrueTypeFontMetrics::class, 'charWidth')]
#[UsesClass(TrueTypeFont::class)]
final class CharWidthTest extends TestCase
{
    #[Test]
    public function charWidthReturnsNumericValue(): void
    {
        // Arrange
        $font = TrueTypeFont::fromData(MinimalFontBuilder::build());
        $metrics = TrueTypeFontMetrics::fromFont($font);

        // Act
        $width = $metrics->charWidth(65); // 'A'

        // Assert
        self::assertGreaterThanOrEqual(0.0, $width);
    }
}
