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
#[CoversMethod(TrueTypeFont::class, 'isCff')]
final class IsCffTest extends TestCase
{
    #[Test]
    public function isCffReturnsStoredValue(): void
    {
        // Arrange
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        $prop = $rc->getProperty('isCff');
        $prop->setValue($font, true);

        // Act / Assert
        self::assertTrue($font->isCff());
    }
}
