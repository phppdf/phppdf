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
#[CoversMethod(TrueTypeFont::class, 'getUnitsPerEm')]
final class GetUnitsPerEmTest extends TestCase
{
    #[Test]
    public function getUnitsPerEmReturnsStoredValue(): void
    {
        // Arrange
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $prop = $rc->getProperty('unitsPerEm');
        $prop->setValue($font, (int) 2048);

        // Act / Assert
        self::assertSame(2048, $font->getUnitsPerEm());
    }
}
