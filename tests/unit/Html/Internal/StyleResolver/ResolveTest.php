<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\StyleResolver;

use DOMDocument;
use DOMElement;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Html\HtmlFontFamily;
use PhpPdf\Html\Internal\ComputedStyle;
use PhpPdf\Html\Internal\CssParser;
use PhpPdf\Html\Internal\StyleResolver;
use PhpPdf\Text\TextAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use const LIBXML_NOERROR;

#[CoversClass(StyleResolver::class)]
#[CoversMethod(StyleResolver::class, 'resolve')]
#[UsesClass(HtmlConverterConfig::class)]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(ComputedStyle::class)]
#[UsesClass(CssParser::class)]
#[UsesClass(Type1FontMetrics::class)]
final class ResolveTest extends TestCase
{
    private HtmlConverterConfig $config;
    private ComputedStyle $parentStyle;

    #[Test]
    public function returnsParentStyleForNonElementNode(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $doc = new DOMDocument();
        $textNode = $doc->createTextNode('hello');

        // Act
        $result = $resolver->resolve($textNode, $this->parentStyle);

        // Assert
        self::assertSame($this->parentStyle->getFontFamily(), $result->getFontFamily());
        self::assertSame($this->parentStyle->getFontSize(), $result->getFontSize());
    }

    #[Test]
    public function resetsBoxPropertiesForNonElementNode(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $this->parentStyle->setMarginTop(20.0);
        $this->parentStyle->setMarginBottom(10.0);
        $doc = new DOMDocument();
        $textNode = $doc->createTextNode('hello');

        // Act
        $result = $resolver->resolve($textNode, $this->parentStyle);

        // Assert
        self::assertSame(0.0, $result->getMarginTop());
        self::assertSame(0.0, $result->getMarginBottom());
    }

    #[Test]
    public function appliesBrowserDefaultsForH1(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<h1>Title</h1>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
        self::assertGreaterThan($this->parentStyle->getFontSize(), $result->getFontSize());
        self::assertGreaterThan(0.0, $result->getMarginTop());
        self::assertGreaterThan(0.0, $result->getMarginBottom());
    }

    #[Test]
    public function appliesBrowserDefaultsForParagraph(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p>Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertGreaterThan(0.0, $result->getMarginBottom());
    }

    #[Test]
    public function appliesBrowserDefaultsForStrong(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<strong>Text</strong>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
    }

    #[Test]
    public function appliesBrowserDefaultsForEm(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<em>Text</em>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isItalic());
    }

    #[Test]
    public function appliesBrowserDefaultsForBlockquote(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<blockquote>Text</blockquote>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isItalic());
        self::assertGreaterThan(0.0, $result->getMarginLeft());
    }

    #[Test]
    public function appliesElementSelectorFromStylesheet(): void
    {
        // Arrange
        $rules = ['p' => ['color' => 'red']];
        $resolver = new StyleResolver($rules, $this->config);
        $element = $this->makeElement('<p>Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertEqualsWithDelta([1.0, 0.0, 0.0], $result->getColor(), 0.001);
    }

    #[Test]
    public function appliesClassSelectorFromStylesheet(): void
    {
        // Arrange
        $rules = ['.highlight' => ['background-color' => 'yellow']];
        $resolver = new StyleResolver($rules, $this->config);
        $element = $this->makeElement('<p class="highlight">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertNotNull($result->getBackgroundColor());
    }

    #[Test]
    public function appliesUniversalSelectorFromStylesheet(): void
    {
        // Arrange
        $rules = ['*' => ['font-size' => '20pt']];
        $resolver = new StyleResolver($rules, $this->config);
        $element = $this->makeElement('<p>Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(20.0, $result->getFontSize());
    }

    #[Test]
    public function appliesInlineStyleAttribute(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="color: blue; font-size: 16pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertEqualsWithDelta([0.0, 0.0, 1.0], $result->getColor(), 0.001);
        self::assertSame(16.0, $result->getFontSize());
    }

    #[Test]
    public function inlineStyleOverridesStylesheetRule(): void
    {
        // Arrange
        $rules = ['p' => ['color' => 'red']];
        $resolver = new StyleResolver($rules, $this->config);
        $element = $this->makeElement('<p style="color: blue;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert — inline blue wins over stylesheet red
        self::assertEqualsWithDelta([0.0, 0.0, 1.0], $result->getColor(), 0.001);
    }

    #[Test]
    public function inheritsFontFamilyFromParent(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $this->parentStyle->setFontFamily('courier');
        $element = $this->makeElement('<span>Text</span>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame('courier', $result->getFontFamily());
    }

    #[Test]
    public function appliesTextAlignFromStylesheet(): void
    {
        // Arrange
        $rules = ['p' => ['text-align' => 'center']];
        $resolver = new StyleResolver($rules, $this->config);
        $element = $this->makeElement('<p>Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(TextAlign::Center, $result->getTextAlign());
    }

    #[Test]
    public function appliesMarginShorthandFromStylesheet(): void
    {
        // Arrange
        $rules = ['p' => ['margin' => '10pt']];
        $resolver = new StyleResolver($rules, $this->config);
        $element = $this->makeElement('<p>Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(10.0, $result->getMarginTop());
        self::assertSame(10.0, $result->getMarginBottom());
    }

    #[Test]
    public function appliesFontWeightBoldFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<span style="font-weight: bold;">Text</span>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
    }

    #[Test]
    public function appliesFontWeightByNumericValueFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<span style="font-weight: 700;">Text</span>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
    }

    #[Test]
    public function appliesFontStyleItalicFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<span style="font-style: italic;">Text</span>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isItalic());
    }

    #[Test]
    public function appliesLineHeightUnitlessMultiplierFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="font-size: 10pt; line-height: 2;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(20.0, $result->getLineHeight());
    }

    #[Test]
    public function appliesFontFamilyFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="font-family: courier;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame('courier', $result->getFontFamily());
    }

    // ── applyDefaults: h2–h6, b, i, ul, ol ──────────────────────────────────

    #[Test]
    public function appliesBrowserDefaultsForH2(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<h2>Title</h2>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
        self::assertGreaterThan($this->parentStyle->getFontSize(), $result->getFontSize());
    }

    #[Test]
    public function appliesBrowserDefaultsForH3(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<h3>Title</h3>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
        self::assertGreaterThan($this->parentStyle->getFontSize(), $result->getFontSize());
    }

    #[Test]
    public function appliesBrowserDefaultsForH4(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<h4>Title</h4>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
    }

    #[Test]
    public function appliesBrowserDefaultsForH5(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<h5>Title</h5>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
    }

    #[Test]
    public function appliesBrowserDefaultsForH6(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<h6>Title</h6>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
        self::assertLessThan($this->parentStyle->getFontSize(), $result->getFontSize());
    }

    #[Test]
    public function appliesBrowserDefaultsForBoldTag(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<b>Text</b>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isBold());
    }

    #[Test]
    public function appliesBrowserDefaultsForItalicTag(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<i>Text</i>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertTrue($result->isItalic());
    }

    #[Test]
    public function appliesBrowserDefaultsForUl(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<ul><li>Item</li></ul>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertGreaterThan(0.0, $result->getPaddingLeft());
    }

    #[Test]
    public function appliesBrowserDefaultsForOl(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<ol><li>Item</li></ol>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertGreaterThan(0.0, $result->getPaddingLeft());
    }

    #[Test]
    public function appliesMarginLeftFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="margin-left: 20pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(20.0, $result->getMarginLeft());
    }

    #[Test]
    public function appliesPaddingLeftFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="padding-left: 15pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(15.0, $result->getPaddingLeft());
    }

    #[Test]
    public function appliesMarginTopFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="margin-top: 8pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(8.0, $result->getMarginTop());
    }

    #[Test]
    public function appliesMarginBottomFromInlineStyle(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="margin-bottom: 6pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(6.0, $result->getMarginBottom());
    }

    #[Test]
    public function appliesFontWeightNormalResetsItalic(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $this->parentStyle->setBold(true);
        $element = $this->makeElement('<span style="font-weight: normal;">Text</span>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertFalse($result->isBold());
    }

    #[Test]
    public function appliesFontStyleNormalResetsItalic(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $this->parentStyle->setItalic(true);
        $element = $this->makeElement('<span style="font-style: normal;">Text</span>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertFalse($result->isItalic());
    }

    #[Test]
    public function appliesTextAlignRight(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="text-align: right;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(TextAlign::Right, $result->getTextAlign());
    }

    #[Test]
    public function appliesTextAlignJustify(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="text-align: justify;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(TextAlign::Justify, $result->getTextAlign());
    }

    #[Test]
    public function appliesTextAlignLeftAsDefault(): void
    {
        // Arrange — 'left' falls through to the default arm of the match
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="text-align: left;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(TextAlign::Left, $result->getTextAlign());
    }

    #[Test]
    public function appliesLineHeightWithPtUnit(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="line-height: 18pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(18.0, $result->getLineHeight());
    }

    #[Test]
    public function appliesMarginTwoValueShorthand(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="margin: 10pt 20pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert — first value is vertical (top+bottom), second is horizontal (left)
        self::assertSame(10.0, $result->getMarginTop());
        self::assertSame(10.0, $result->getMarginBottom());
        self::assertSame(20.0, $result->getMarginLeft());
    }

    #[Test]
    public function appliesMarginThreeValueShorthand(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="margin: 5pt 15pt 10pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert — top, horizontal, bottom
        self::assertSame(5.0, $result->getMarginTop());
        self::assertSame(15.0, $result->getMarginLeft());
        self::assertSame(10.0, $result->getMarginBottom());
    }

    #[Test]
    public function appliesMarginFourValueShorthand(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="margin: 5pt 10pt 15pt 20pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert — top, right, bottom, left
        self::assertSame(5.0, $result->getMarginTop());
        self::assertSame(15.0, $result->getMarginBottom());
        self::assertSame(20.0, $result->getMarginLeft());
    }

    #[Test]
    public function appliesPaddingOneValueShorthand(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="padding: 12pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert
        self::assertSame(12.0, $result->getPaddingLeft());
    }

    #[Test]
    public function appliesPaddingFourValueShorthand(): void
    {
        // Arrange
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="padding: 5pt 10pt 15pt 20pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert — fourth value is left padding
        self::assertSame(20.0, $result->getPaddingLeft());
    }

    #[Test]
    public function paddingTwoValueShorthandDoesNotSetPaddingLeft(): void
    {
        // Arrange — 2-value shorthand does not map to padding-left in this implementation
        $resolver = new StyleResolver([], $this->config);
        $element = $this->makeElement('<p style="padding: 5pt 10pt;">Text</p>');

        // Act
        $result = $resolver->resolve($element, $this->parentStyle);

        // Assert — 2-value shorthand not handled → paddingLeft stays 0
        self::assertSame(0.0, $result->getPaddingLeft());
    }

    protected function setUp(): void
    {
        $this->config = new HtmlConverterConfig();
        $this->parentStyle = new ComputedStyle('helvetica', 11.0);
    }

    private function makeElement(string $html): DOMElement
    {
        $doc = new DOMDocument();
        $doc->loadHTML('<html><body>' . $html . '</body></html>', LIBXML_NOERROR);

        $body = $doc->getElementsByTagName('body')->item(0);
        self::assertInstanceOf(DOMElement::class, $body);
        $element = $body->firstChild;
        self::assertInstanceOf(DOMElement::class, $element);

        return $element;
    }
}
