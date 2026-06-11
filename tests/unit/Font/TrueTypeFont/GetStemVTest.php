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
#[CoversMethod(TrueTypeFont::class, 'getStemV')]
final class GetStemVTest extends TestCase
{
    #[Test]
    public function getStemVReturnsStoredValue(): void
    {
        // Arrange
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $prop = $rc->getProperty('stemV');
        $prop->setValue($font, (int) 120);

        // Act / Assert
        self::assertSame(120, $font->getStemV());
    }
}
