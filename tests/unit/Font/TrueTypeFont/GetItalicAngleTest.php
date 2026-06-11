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
#[CoversMethod(TrueTypeFont::class, 'getItalicAngle')]
final class GetItalicAngleTest extends TestCase
{
    #[Test]
    public function getItalicAngleReturnsStoredValue(): void
    {
        // Arrange
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $rc->getProperty('italicAngle')->setValue($font, -12.5);

        // Act / Assert
        self::assertSame(-12.5, $font->getItalicAngle());
    }
}
