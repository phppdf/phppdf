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
#[CoversMethod(TrueTypeFont::class, 'getAscent')]
final class GetAscentTest extends TestCase
{
    #[Test]
    public function getAscentReturnsStoredValue(): void
    {
        // Arrange
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $prop = $rc->getProperty('ascent');
        $prop->setValue($font, (int) 1900);

        // Act / Assert
        self::assertSame(1900, $font->getAscent());
    }
}
