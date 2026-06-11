<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlFontFamily;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Font\MinimalFontBuilder;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeFontMetrics;
use PhpPdf\Html\HtmlFontFamily;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlFontFamily::class)]
#[CoversMethod(HtmlFontFamily::class, 'trueType')]
#[UsesClass(TrueTypeFont::class)]
#[UsesClass(TrueTypeFontMetrics::class)]
final class TrueTypeTest extends TestCase
{
    #[Test]
    public function createsEmbeddedFamily(): void
    {
        // Arrange
        $font = TrueTypeFont::fromData(MinimalFontBuilder::build());

        // Act
        $family = HtmlFontFamily::trueType($font);

        // Assert
        self::assertTrue($family->isEmbedded());
    }

    #[Test]
    public function returnsMetricsForNormalVariant(): void
    {
        // Arrange
        $font = TrueTypeFont::fromData(MinimalFontBuilder::build());
        $family = HtmlFontFamily::trueType($font);

        // Act
        $metrics = $family->getMetrics(false, false);

        // Assert
        self::assertInstanceOf(FontMetrics::class, $metrics);
    }

    #[Test]
    public function fallsBackNullBoldToNormal(): void
    {
        // Arrange
        $font = TrueTypeFont::fromData(MinimalFontBuilder::build());
        $family = HtmlFontFamily::trueType($font);

        // Act
        $normal = $family->getMetrics(false, false);
        $bold = $family->getMetrics(true, false);

        // Assert — same font used for both variants → equal metrics
        self::assertEquals($normal, $bold);
    }

    #[Test]
    public function usesSeparateBoldFontWhenProvided(): void
    {
        // Arrange
        $normal = TrueTypeFont::fromData(MinimalFontBuilder::build());
        $bold = TrueTypeFont::fromData(MinimalFontBuilder::build(['weight' => 700]));
        $family = HtmlFontFamily::trueType($normal, $bold);

        // Act
        $normalMetrics = $family->getMetrics(false, false);
        $boldMetrics = $family->getMetrics(true, false);

        // Assert — different metric objects for different fonts
        self::assertNotSame($normalMetrics, $boldMetrics);
    }

    #[Test]
    public function fallsBackNullBoldItalicToBoldWhenBoldProvided(): void
    {
        // Arrange
        $normal = TrueTypeFont::fromData(MinimalFontBuilder::build());
        $bold = TrueTypeFont::fromData(MinimalFontBuilder::build(['weight' => 700]));
        $family = HtmlFontFamily::trueType($normal, $bold);

        // Act
        $boldMetrics = $family->getMetrics(true, false);
        $boldItalicMetrics = $family->getMetrics(true, true);

        // Assert — bold-italic falls back to bold → equal metrics
        self::assertEquals($boldMetrics, $boldItalicMetrics);
    }
}
