<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlFontFamily;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Font\MinimalFontBuilder;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeFontMetrics;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlFontFamily;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlFontFamily::class)]
#[CoversMethod(HtmlFontFamily::class, 'registerVariantOnPage')]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(TrueTypeFont::class)]
#[UsesClass(TrueTypeFontMetrics::class)]
#[UsesClass(Type1FontMetrics::class)]
final class RegisterVariantOnPageTest extends TestCase
{
    #[Test]
    public function registersType1FontOnPage(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');
        $page = new PdfPageBuilder();

        // Act
        $family->registerVariantOnPage($page, 'F0N', false, false);

        // Assert — no exception means registration succeeded
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function registersType1BoldVariantOnPage(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');
        $page = new PdfPageBuilder();

        // Act
        $family->registerVariantOnPage($page, 'F0B', true, false);

        // Assert
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function registersType1ItalicVariantOnPage(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');
        $page = new PdfPageBuilder();

        // Act
        $family->registerVariantOnPage($page, 'F0I', false, true);

        // Assert
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function registersType1BoldItalicVariantOnPage(): void
    {
        // Arrange
        $family = HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique');
        $page = new PdfPageBuilder();

        // Act
        $family->registerVariantOnPage($page, 'F0X', true, true);

        // Assert
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function registersEmbeddedTrueTypeFontOnPage(): void
    {
        // Arrange
        $font = TrueTypeFont::fromData(MinimalFontBuilder::build());
        $family = HtmlFontFamily::trueType($font);
        $page = new PdfPageBuilder();

        // Act
        $family->registerVariantOnPage($page, 'F0N', false, false);

        // Assert
        $this->expectNotToPerformAssertions();
    }
}
