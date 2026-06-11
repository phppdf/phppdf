<?php

declare(strict_types=1);

namespace PhpPdf\Font\TrueTypeFont;

use PhpPdf\Font\MinimalFontBuilder;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeSubsetter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrueTypeFont::class)]
#[CoversMethod(TrueTypeFont::class, 'subset')]
#[UsesClass(TrueTypeSubsetter::class)]
final class SubsetTest extends TestCase
{
    #[Test]
    public function subsetDelegatestoSubsetter(): void
    {
        // Arrange
        $data = MinimalFontBuilder::build(['numGlyphs' => 3]);
        $font = TrueTypeFont::fromData($data);

        // Act — ask for GID 1 (used glyph)
        $result = $font->subset([1 => 0x41]);

        // Assert — result is a non-empty binary string
        self::assertNotEmpty($result);
    }

    #[Test]
    public function subsetReturnsCffDataUnchangedForCffFont(): void
    {
        // Arrange — CFF font; subsetter returns full data unchanged
        $data = MinimalFontBuilder::buildCff();
        $font = TrueTypeFont::fromData($data);

        // Act
        $result = $font->subset([1 => 0x41]);

        // Assert — CFF subsetting is not implemented → full font data returned
        self::assertSame($data, $result);
    }
}
