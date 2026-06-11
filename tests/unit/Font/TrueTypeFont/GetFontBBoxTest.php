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
#[CoversMethod(TrueTypeFont::class, 'getFontBBox')]
final class GetFontBBoxTest extends TestCase
{
    #[Test]
    public function getFontBBoxReturnsAllFourComponents(): void
    {
        // Arrange
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();

        foreach (['xMin' => -10, 'yMin' => -200, 'xMax' => 1000, 'yMax' => 800] as $p => $v) {
            $rc->getProperty($p)->setValue($font, $v);
        }

        // Act
        $bbox = $font->getFontBBox();

        // Assert
        self::assertSame([-10, -200, 1000, 800], $bbox);
    }
}
