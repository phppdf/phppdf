<?php

declare(strict_types=1);

namespace PhpPdf\Font\TrueTypeFontMetrics;

use PhpPdf\Font\MinimalFontBuilder;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeFontMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrueTypeFontMetrics::class)]
#[CoversMethod(TrueTypeFontMetrics::class, 'fromFont')]
#[UsesClass(TrueTypeFont::class)]
final class FromFontTest extends TestCase
{
    #[Test]
    public function fromFontReturnsTrueTypeFontMetrics(): void
    {
        // Arrange
        $font = TrueTypeFont::fromData(MinimalFontBuilder::build());

        // Act
        $metrics = TrueTypeFontMetrics::fromFont($font);

        // Assert
        self::assertInstanceOf(TrueTypeFontMetrics::class, $metrics);
    }
}
