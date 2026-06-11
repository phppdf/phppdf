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
#[CoversMethod(TrueTypeFont::class, 'getGlyphId')]
final class GetGlyphIdTest extends TestCase
{
    #[Test]
    public function getGlyphIdReturnsMappedGlyph(): void
    {
        // Arrange
        $font = self::makeFont([0x41 => 5]);

        // Act / Assert
        self::assertSame(5, $font->getGlyphId(0x41));
    }

    #[Test]
    public function getGlyphIdReturnsZeroWhenNotFound(): void
    {
        // Arrange
        $font = self::makeFont([]);

        // Act / Assert
        self::assertSame(0, $font->getGlyphId(0x41));
    }

    /** @param array<int,int> $cmap */
    private static function makeFont(array $cmap): TrueTypeFont
    {
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $rc->getProperty('cmap')->setValue($font, $cmap);

        return $font;
    }
}
