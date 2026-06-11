<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlFontFamily;

use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlFontFamily;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlFontFamily::class)]
#[CoversMethod(HtmlFontFamily::class, 'type1')]
#[UsesClass(Type1FontMetrics::class)]
final class Type1Test extends TestCase
{
    #[Test]
    public function createsNonEmbeddedFamily(): void
    {
        // Arrange / Act
        $family = HtmlFontFamily::type1('Helvetica');

        // Assert
        self::assertFalse($family->isEmbedded());
    }

    #[Test]
    public function fallsBackNullBoldToNormal(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica');

        // Act
        $normalMetrics = $family->getMetrics(false, false);
        $boldMetrics = $family->getMetrics(true, false);

        // Assert — both should use Helvetica metrics
        self::assertEquals($normalMetrics, $boldMetrics);
    }

    #[Test]
    public function fallsBackNullItalicToNormal(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica');

        // Act
        $normalMetrics = $family->getMetrics(false, false);
        $italicMetrics = $family->getMetrics(false, true);

        // Assert
        self::assertEquals($normalMetrics, $italicMetrics);
    }

    #[Test]
    public function fallsBackNullBoldItalicToBoldWhenBoldProvided(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold');

        // Act
        $boldMetrics = $family->getMetrics(true, false);
        $boldItalicMetrics = $family->getMetrics(true, true);

        // Assert — bold-italic should fall back to bold
        self::assertEquals($boldMetrics, $boldItalicMetrics);
    }

    #[Test]
    public function usesDifferentMetricsForEachVariantWhenProvided(): void
    {
        // Arrange / Act
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');

        // Assert — each variant should yield different metrics objects
        $normal = $family->getMetrics(false, false);
        $bold = $family->getMetrics(true, false);
        $italic = $family->getMetrics(false, true);
        $boldItalic = $family->getMetrics(true, true);

        self::assertNotSame($normal, $bold);
        self::assertNotSame($normal, $italic);
        self::assertNotSame($normal, $boldItalic);
    }
}
