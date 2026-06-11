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
#[CoversMethod(TrueTypeFont::class, 'toPdfUnits')]
final class ToPdfUnitsTest extends TestCase
{
    #[Test]
    public function toPdfUnitsConvertsUsingFormula(): void
    {
        // Arrange — unitsPerEm=1000, so 750 font-units → 750 PDF units
        $font = self::makeFont(1000);

        // Act / Assert
        self::assertSame(750, $font->toPdfUnits(750));
    }

    #[Test]
    public function toPdfUnitsRoundsResult(): void
    {
        // Arrange — unitsPerEm=2048; 1000 × 1000 / 2048 ≈ 488.28 → rounds to 488
        $font = self::makeFont(2048);

        // Act / Assert
        self::assertSame(488, $font->toPdfUnits(1000));
    }

    private static function makeFont(int $unitsPerEm): TrueTypeFont
    {
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $rc->getProperty('unitsPerEm')->setValue($font, $unitsPerEm);

        return $font;
    }
}
