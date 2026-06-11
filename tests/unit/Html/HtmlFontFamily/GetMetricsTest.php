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
#[CoversMethod(HtmlFontFamily::class, 'getMetrics')]
#[UsesClass(Type1FontMetrics::class)]
final class GetMetricsTest extends TestCase
{
    #[Test]
    public function returnsMetricsInstanceForNormalVariant(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');

        // Act
        $metrics = $family->getMetrics(false, false);

        // Assert
        self::assertInstanceOf(FontMetrics::class, $metrics);
    }

    #[Test]
    public function returnsMetricsInstanceForBoldVariant(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');

        // Act
        $metrics = $family->getMetrics(true, false);

        // Assert
        self::assertInstanceOf(FontMetrics::class, $metrics);
    }

    #[Test]
    public function returnsMetricsInstanceForItalicVariant(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');

        // Act
        $metrics = $family->getMetrics(false, true);

        // Assert
        self::assertInstanceOf(FontMetrics::class, $metrics);
    }

    #[Test]
    public function returnsMetricsInstanceForBoldItalicVariant(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');

        // Act
        $metrics = $family->getMetrics(true, true);

        // Assert
        self::assertInstanceOf(FontMetrics::class, $metrics);
    }

    #[Test]
    public function normalAndBoldVariantsAreDistinct(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');

        // Act
        $normal = $family->getMetrics(false, false);
        $bold = $family->getMetrics(true, false);

        // Assert
        self::assertNotSame($normal, $bold);
    }
}
