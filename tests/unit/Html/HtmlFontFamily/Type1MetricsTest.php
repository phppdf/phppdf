<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlFontFamily;

use PhpPdf\Font\FontMetrics;
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
final class Type1MetricsTest extends TestCase
{
    #[Test]
    public function timesRomanFamilyReturnsCorrectMetrics(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Times-Roman', 'Times-Bold', 'Times-Italic', 'Times-BoldItalic');

        // Act / Assert
        self::assertInstanceOf(FontMetrics::class, $family->getMetrics(false, false));
        self::assertInstanceOf(FontMetrics::class, $family->getMetrics(true, false));
        self::assertInstanceOf(FontMetrics::class, $family->getMetrics(false, true));
        self::assertInstanceOf(FontMetrics::class, $family->getMetrics(true, true));
    }

    #[Test]
    public function courierFamilyReturnsCorrectMetrics(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Courier', 'Courier-Bold', 'Courier-Oblique', 'Courier-BoldOblique');

        // Act / Assert
        self::assertInstanceOf(FontMetrics::class, $family->getMetrics(false, false));
        self::assertInstanceOf(FontMetrics::class, $family->getMetrics(true, false));
        self::assertInstanceOf(FontMetrics::class, $family->getMetrics(false, true));
        self::assertInstanceOf(FontMetrics::class, $family->getMetrics(true, true));
    }

    #[Test]
    public function unknownFontNameFallsBackToHelveticaMetrics(): void
    {
        // Arrange
        $familyWithUnknown = HtmlFontFamily::type1('Symbol');
        $familyHelvetica = HtmlFontFamily::type1('Helvetica');

        // Act
        $unknownMetrics = $familyWithUnknown->getMetrics(false, false);
        $helveticaMetrics = $familyHelvetica->getMetrics(false, false);

        // Assert — both should return equivalent Helvetica metrics
        self::assertEquals($unknownMetrics, $helveticaMetrics);
    }
}
