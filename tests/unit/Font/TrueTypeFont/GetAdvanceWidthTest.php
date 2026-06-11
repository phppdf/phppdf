<?php

declare(strict_types=1);

namespace PhpPdf\Font\TrueTypeFont;

use PhpPdf\Font\TrueTypeFont;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(TrueTypeFont::class)]
#[CoversMethod(TrueTypeFont::class, 'getAdvanceWidth')]
final class GetAdvanceWidthTest extends TestCase
{
    #[Test]
    public function getAdvanceWidthReturnsMappedWidth(): void
    {
        // Arrange
        $font = self::makeFont([0 => 500, 3 => 750]);

        // Act / Assert
        self::assertSame(750, $font->getAdvanceWidth(3));
    }

    #[Test]
    public function getAdvanceWidthFallsBackToGlyphZero(): void
    {
        // Arrange — no entry for glyph 5, but glyph 0 exists
        $font = self::makeFont([0 => 600]);

        // Act / Assert
        self::assertSame(600, $font->getAdvanceWidth(5));
    }

    #[Test]
    public function getAdvanceWidthReturnsZeroWhenBothMissing(): void
    {
        // Arrange — completely empty map
        $font = self::makeFont([]);

        // Act / Assert
        self::assertSame(0, $font->getAdvanceWidth(5));
    }

    /** @param array<int,int> $widths */
    private static function makeFont(array $widths): TrueTypeFont
    {
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $rc->getProperty('advanceWidths')->setValue($font, $widths);

        return $font;
    }
}
