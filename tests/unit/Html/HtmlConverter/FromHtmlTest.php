<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlConverter;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlConverter;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Html\HtmlFontFamily;
use PhpPdf\Html\Internal\ComputedStyle;
use PhpPdf\Html\Internal\CssParser;
use PhpPdf\Html\Internal\HtmlLayoutEngine;
use PhpPdf\Html\Internal\HtmlTableCellData;
use PhpPdf\Html\Internal\HtmlTableData;
use PhpPdf\Html\Internal\HtmlTableRowData;
use PhpPdf\Html\Internal\LayoutBlock;
use PhpPdf\Html\Internal\MeasuredBlock;
use PhpPdf\Html\Internal\StyleResolver;
use PhpPdf\Table\TableBuilder;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlConverter::class)]
#[CoversMethod(HtmlConverter::class, 'fromHtml')]
#[UsesClass(HtmlConverterConfig::class)]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(HtmlLayoutEngine::class)]
#[UsesClass(StyleResolver::class)]
#[UsesClass(ComputedStyle::class)]
#[UsesClass(CssParser::class)]
#[UsesClass(LayoutBlock::class)]
#[UsesClass(MeasuredBlock::class)]
#[UsesClass(HtmlTableData::class)]
#[UsesClass(HtmlTableRowData::class)]
#[UsesClass(HtmlTableCellData::class)]
#[UsesClass(Type1FontMetrics::class)]
#[UsesClass(PdfDocumentBuilder::class)]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(TextBox::class)]
#[UsesClass(Color::class)]
#[UsesClass(TableBuilder::class)]
#[UsesClass(TableCell::class)]
#[UsesClass(TableRow::class)]
final class FromHtmlTest extends TestCase
{
    #[Test]
    public function returnsDocumentBuilderFromSimpleHtml(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<p>Hello World</p>');

        // Assert
        self::assertInstanceOf(PdfDocumentBuilder::class, $builder);
    }

    #[Test]
    public function createsAtLeastOnePageForContent(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<h1>Title</h1><p>Content</p>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function createsOneBlankPageForEmptyHtml(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('');

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function usesProvidedConfig(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $config->setPageWidth(400);

        // Act
        $builder = HtmlConverter::fromHtml('<p>Hello</p>', $config);

        // Assert
        self::assertInstanceOf(PdfDocumentBuilder::class, $builder);
    }

    #[Test]
    public function defaultsToA4ConfigWhenNullPassed(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<p>Hello</p>', null);

        // Assert
        self::assertInstanceOf(PdfDocumentBuilder::class, $builder);
    }

    #[Test]
    public function rendersHeadings(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<h1>H1</h1><h2>H2</h2><h3>H3</h3>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function rendersUnorderedList(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<ul><li>Item 1</li><li>Item 2</li></ul>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function rendersOrderedList(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<ol><li>Item 1</li><li>Item 2</li></ol>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function rendersHorizontalRule(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<p>Before</p><hr><p>After</p>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function rendersLineBreak(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<p>Line1<br>Line2</p>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function rendersTable(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml(
            '<table><thead><tr><th>Name</th><th>Age</th></tr></thead>'
            . '<tbody><tr><td>Alice</td><td>30</td></tr></tbody></table>',
        );

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function rendersTableWithBorders(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<table border="1"><tr><td>Cell</td></tr></table>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function extractsEmbeddedStyleRules(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<style>p { color: red; }</style><p>Styled text</p>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function rendersBlockquote(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<blockquote>A famous quote.</blockquote>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function rendersInlineBoldAndItalic(): void
    {
        // Arrange / Act
        $builder = HtmlConverter::fromHtml('<p>Normal <strong>bold</strong> and <em>italic</em></p>');

        // Assert
        self::assertGreaterThanOrEqual(1, $builder->getPageCount());
    }

    #[Test]
    public function handlesLargeContentSpanningMultiplePages(): void
    {
        // Arrange
        $paragraphs = str_repeat('<p>' . str_repeat('Lorem ipsum dolor sit amet. ', 20) . '</p>', 30);

        // Act
        $builder = HtmlConverter::fromHtml($paragraphs);

        // Assert
        self::assertGreaterThan(1, $builder->getPageCount());
    }
}
