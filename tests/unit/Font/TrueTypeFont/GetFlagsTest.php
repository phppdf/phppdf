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
#[CoversMethod(TrueTypeFont::class, 'getFlags')]
final class GetFlagsTest extends TestCase
{
    #[Test]
    public function getFlagsReturnsStoredValue(): void
    {
        // Arrange
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $prop = $rc->getProperty('flags');
        $prop->setValue($font, (int) 96);

        // Act / Assert
        self::assertSame(96, $font->getFlags());
    }
}
